<?php

namespace App\Livewire\Admin;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Livewire\Traits\VenueFilters;
use App\Livewire\Traits\VenueEditState;
use App\Services\VenueService;
use App\Services\DepartmentService;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\Paginator;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessCsvFileUpload;

#[Layout('layouts.app')]
class VenuesIndex extends Component
{
    // Traits / shared state
    use VenueFilters, VenueEditState, WithFileUploads;

    // Properties / backing stores
    public $csvFile;
    public ?string $importKey = null;
    public ?string $importStatus = null;
    public ?string $importErrorMsg = null;

    // Details modal state
    public ?int $detailsId = null;
    public array $details = [];

    // Sorting
    public string $sortField = '';
    public string $sortDirection = 'asc';

    // Accessors and Mutators
    /**
     * Dynamic list of departments based on the current venues (excluding soft-deleted).
     * Sorted naturally, case-insensitive.
     *
     * @return array<int,string>
     */
    public function getDepartmentsProperty(): array
    {
        // Gather departments from current venues (excluding soft-deleted)
        $deps = $this->allVenues()
            ->pluck('department')
            ->filter(fn($venue) => is_string($venue) && trim($venue) !== '')
            ->map(fn($venue) => trim($venue))
            ->all();

        // Case-insensitive de-duplication while preserving first casing seen
        $map = [];
        foreach ($deps as $d) {
            $k = mb_strtolower($d);
            if (!isset($map[$k])) {
                $map[$k] = $d;
            }
        }

        // Natural, case-insensitive sort
        $values = array_values($map);
        usort($values, fn($a, $b) => strnatcasecmp($a, $b));
        return $values;
    }


    // Filters: clear/reset
    /**
     * Resets all filters to their default values, and resets the current page to 1.
     *
     * This method is called when the user clicks the "Clear" button on the filter form.
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->department = '';
        $this->capMin = null;
        $this->capMax = null;
        $this->page = 1;
    }

    // Pagination & filter reactions
    /**
     * Navigates to a given page number.
     *
     * @param int $target The target page number.
     *
     * This function will compute bounds from the current filters, and then
     * set the page number to the maximum of 1 and the minimum of the
     * target and the last page number. If the class has a 'selected'
     * property, it will be cleared when the page changes.
     */
    public function goToPage(int $target): void
    {
        // compute bounds from current filters
        $total = $this->filtered()->count();
        $last  = max(1, (int) ceil($total / max(1, $this->pageSize)));

        $this->page = max(1, min($target, $last));
    }

    // Filters: search update reaction
    /**
     * Resets the current page to 1 when the search filter is updated.
     *
     * This function will be called whenever the search filter is updated,
     * and will reset the current page to 1.
     */
    public function applySearch()
    {
        $this->page = 1;
    }

    /**
     * Toggle or set the active sort column and direction.
     */
    public function sortBy(string $field): void
    {
        if ($field === $this->sortField) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->page = 1;
    }

    // Create / modal workflows
    /**
     * Resets the edit fields to their default values and opens the edit venue modal.
     *
     * This function is called when the user clicks the "Add Venue" button.
     */
    public function openCreate(): void
    {
        $this->resetEdit();
        $this->dispatch('bs:open', id: 'venueModal');
    }

    /**
     * Resets the csvFile field to its default value and opens the CSV modal.
     *
     * This function will reset the csvFile field to its default value and
     * open the CSV modal. It is called when the user clicks the "Add
     * Venues by CSV" button.
     */
    public function openCsvModal(): void
    {
        $this->reset('csvFile');
        $this->importErrorMsg = null; // clear any previous error before a new attempt
        $this->dispatch('bs:open', id: 'csvModal');
    }

    /**
     * No-op handler when CSV upload is disabled; emits an informational toast.
     */
    public function csvUploadDisabled(): void
    {
        $this->dispatch('toast', message: 'CSV upload is disabled');
    }

    /**
     * Handle CSV upload to add venues in bulk.
     * - Validates file type and size
     * - Stores temporarily on uploads_temp disk
     * - Dispatches background job to virus-scan, parse, and import via VenueService
     */
    public function uploadCsv(): void
    {
        // Validate only the CSV file; accept common CSV MIME types and extensions
        $this->validate([
            'csvFile' => 'required|file|max:25600 |mimes:csv,txt', // 25 MB
        ]);

        try {
            // Clear any prior error message on new upload
            $this->importErrorMsg = null;
            $original = (string) ($this->csvFile->getClientOriginalName() ?? 'venues.csv');
            $ext = pathinfo($original, PATHINFO_EXTENSION) ?: 'csv';
            $safe = 'venues_' . now()->format('Ymd_His') . '_' . Str::random(6) . '.' . $ext;

            // Ensure the temp uploads root exists (Windows-friendly)
            try {
                $rootPath = Storage::disk('uploads_temp')->path('');
                if (!\is_dir($rootPath)) {
                    @\mkdir($rootPath, 0775, true);
                }
            } catch (\Throwable $e) {
                // Non-fatal: Storage::path should normally create on put; continue
            }

            // Store on the temporary uploads disk
            $this->csvFile->storeAs('', $safe, 'uploads_temp');

            // Dispatch async processing job (scan + parse + import)
            $admin = Auth::user();
            $adminId = is_object($admin) ? (int) $admin->id : 0;
            ProcessCsvFileUpload::dispatch($safe, $adminId);

            // Track status key for polling in the UI
            $this->importKey = $safe;
            $this->importStatus = 'queued';

            // Close modal and toast
            $this->dispatch('bs:close', id: 'csvModal');
            $this->dispatch('toast', message: 'CSV upload started. Venues will appear after processing.');
            $this->reset('csvFile');
        } catch (\Throwable $e) {
            Log::error('CSV upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->addError('csvFile', 'Unable to upload CSV: ' . $e->getMessage());
        }
    }

    /**
     * Poll for background CSV import status via cache
     */
    public function checkImportStatus(): void
    {
        if (!$this->importKey) return;
        try {
            $cacheBase = 'venues_import:' . $this->importKey;
            $status = Cache::get($cacheBase);
            if ($status) {
                $this->importStatus = (string) $status;
                if (in_array($status, ['done', 'failed', 'infected'], true)) {
                    // Notify result and clear tracking
                    if ($status === 'done') {
                        $this->dispatch('toast', message: 'Import complete.');
                        // Trigger a re-render by nudging pagination back to first page
                        $this->page = 1;
                    } elseif ($status === 'infected') {
                        $this->dispatch('toast', message: 'File infected. Import aborted.');
                        $this->importErrorMsg = 'The uploaded file appears to be infected. Import was aborted.';
                    } else {
                        $err = (string) (Cache::get($cacheBase . ':error') ?? 'Unknown error.');
                        $this->importErrorMsg = 'Import failed: ' . $err;
                        $this->dispatch('toast', message: 'Import failed.');
                    }
                    $this->importKey = null;
                    $this->importStatus = null;
                }
            }
        } catch (\Throwable $e) {
            // Swallow polling errors
        }
    }

    // Edit workflow
    /**
     * Opens the edit venue modal with the given ID.
     * If the venue is not found, the function does nothing.
     * It sets the currently edited venue ID and the values of the venue to be edited, and then opens the edit venue modal.
     * @param int $id The ID of the venue to edit
     */
    public function openEdit(int $id): void
    {
        // Fetch venue via service only (no direct model queries here)
        $venue = null;
        try {
            $venue = app(VenueService::class)->getVenueById($id);
        } catch (\Throwable $e) {
            $venue = null;
        }
        if (!$venue) return;
        $this->editId    = (int)$venue->id;
        $this->vName     = (string)$venue->name;
        $this->vRoom     = (string)($venue->code ?? '');
        $this->vCapacity = (int)($venue->capacity ?? 0);
        // Accessing relations may lazy-load as needed; still avoids static model usage in this view
        $this->vDepartment = (string)optional($venue->department)->name;
        $this->vManager  = (string)optional($venue->manager)->email;
        $this->vStatus   = 'Active'; // Model uses SoftDeletes; absence implies active
        // Map features string like '1101' to labels
        $this->vFeatures = $this->mapFeaturesStringToLabels((string)($venue->features ?? ''));
        // Map opening/closing to a single time range entry for edit convenience
        $this->timeRanges = [];
        if (!empty($venue->opening_time) || !empty($venue->closing_time)) {
            $this->timeRanges[] = [
                'from' => substr((string)$venue->opening_time, 0, 5),
                'to'   => substr((string)$venue->closing_time, 0, 5),
            ];
        }

        $this->dispatch('bs:open', id: 'venueModal');
    }

    /**
     * Populate and show the details modal for a venue using the service layer only.
     */
    public function showDetails(int $id): void
    {
        try {
            $venue = app(VenueService::class)->getVenueById($id);
            if (!$venue) {
                $this->addError('detailsId', 'Venue not found.');
                return;
            }
            // Normalize details payload for the view
            $features = $this->mapFeaturesStringToLabels((string)($venue->features ?? ''));
            $this->detailsId = (int) $venue->id;
            $this->details = [
                'id'         => (int) $venue->id,
                'name'       => (string) $venue->name,
                'department' => (string) (optional($venue->department)->name ?? ''),
                'code'       => (string) ($venue->code ?? ''),
                'capacity'   => (int) ($venue->capacity ?? 0),
                'manager'    => (string) (optional($venue->manager)->email ?? ''),
                'features'   => $features,
                'opening'    => $venue->opening_time ? substr((string)$venue->opening_time, 0, 5) : null,
                'closing'    => $venue->closing_time ? substr((string)$venue->closing_time, 0, 5) : null,
            ];
            $this->dispatch('bs:open', id: 'venueDetails');
        } catch (\Throwable $e) {
            $this->addError('detailsId', 'Unable to load venue.');
        }
    }

    // Validation / rules
    /**
     * Returns an array of rules for the edit venue form.
     *
     * @return array
     */
    protected function rules(): array
    {
        return [
            'vName'      => 'required|string|max:150',
            'vRoom'      => 'required|string|max:50',
            'vDepartment' => 'required|string|max:120',
            'vCapacity'  => 'required|integer|min:0', // >= 0
            'vManager'   => 'nullable|string|max:120',
            'vStatus'    => 'required|in:Active,Inactive',
            'vFeatures'  => 'array',
            // Justification validated separately on confirm; keep rule for Livewire error bag consistency
            'justification' => 'nullable|string|min:10|max:200',
            'timeRanges'            => 'array',
            'timeRanges.*.from'     => 'required|date_format:H:i',
            'timeRanges.*.to'       => 'required|date_format:H:i',
        ];
    }

    /**
     * Validates only the justification field.
     *
     * This function is a helper to validate only the justification length by calling
     * `validateOnly` with the justification field as the parameter.
     */
    protected function validateJustification(): void
    {
        // Enforce normalized justification requirement
        $this->validate([
            'justification' => ['required', 'string', 'min:10', 'max:200'],
        ]);
    }

    // Persist edits / session writes
    /**
     * Validates the venue modal form and then opens the justification modal.
     *
     * This method is called when the user clicks the "Save" button on the venue modal.
     */
    public function save(): void
    {
        $this->validate();

        // ensure each 'to' is after its 'from'
        foreach ($this->timeRanges as $idx => $tr) {
            $from = $tr['from'] ?? null;
            $to   = $tr['to']   ?? null;

            if ($from && $to && $to <= $from) {
                $this->addError("timeRanges.$idx.to", 'The end time must be after the start time.');
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return; // don’t proceed if there are errors
        }

        $this->actionType = 'save';
        $this->dispatch('bs:open', id: 'venueJustify');
    }

    /**
     * Confirms the save action and updates the session with the new/edited venue data.
     * If the venue is being created, it adds the new venue to the list.
     * If the venue is being edited, it updates the existing venue in the list.
     * Finally, it dispatches events to close the justification modal, edit venue modal, and show a toast message with a success message.
     */
    public function confirmSave(): void
    {
        $this->validateJustification();

        // Resolve relations via services
        $deptId = null;
        try {
            $deptName = trim((string)$this->vDepartment);
            if ($deptName !== '') {
                $dept = app(DepartmentService::class)->findByName($deptName);
                if (!$dept) {
                    $created = app(DepartmentService::class)->updateOrCreateDepartment([
                        ['name' => $deptName, 'code' => \Illuminate\Support\Str::slug($deptName)]
                    ]);
                    $dept = $created->first();
                }
                $deptId = $dept?->id;
            }
        } catch (\Throwable $e) {
            $deptId = null;
        }

        $managerId = null;
        try {
            $managerEmail = trim((string)$this->vManager);
            if ($managerEmail !== '') {
                $mgrs = app(UserService::class)->getUsersWithRole('venue-manager');
                $mgr = collect($mgrs)->firstWhere('email', $managerEmail);
                $managerId = $mgr?->id;
            }
        } catch (\Throwable $e) {
            $managerId = null;
        }

        // Encode features array to compact string '1010'
        $featuresStr = $this->mapFeatureLabelsToString($this->vFeatures);

        // Prepare data payload for service
        $from = $this->timeRanges[0]['from'] ?? null;
        $to   = $this->timeRanges[0]['to'] ?? null;
        $data = [
            'name' => $this->vName,
            'code' => $this->vRoom,
            'capacity' => (int)$this->vCapacity,
            'test_capacity' => (int)$this->vCapacity,
            'department_id' => $deptId,
            'manager_id' => $managerId,
            'features' => $featuresStr,
            'opening_time' => $from,
            'closing_time' => $to,
        ];

        $venue = null;
        $admin = \Illuminate\Support\Facades\Auth::user();
        try {
            if ($this->editId) {
                $existing = app(VenueService::class)->getVenueById((int)$this->editId);
                if ($existing) {
                    $venue = app(VenueService::class)->updateVenue($existing, array_filter($data, fn($v) => $v !== null), $admin);
                }
            } else {
                // create via service
                $venue = app(VenueService::class)->createVenue(array_filter($data, fn($v) => $v !== null), $admin);
            }
        } catch (\Throwable $e) {
            // Surface validation error
            $this->addError('vName', 'Unable to save venue.');
            return;
        }

        $this->dispatch('bs:close', id: 'venueJustify');
        $this->dispatch('bs:close', id: 'venueModal');
        $this->dispatch('toast', message: 'Venue saved');

        $this->reset(['justification', 'actionType', 'editId']);
    }

    /**
     * Unified confirmation entrypoint for the justification modal.
     * Routes to confirmDelete or confirmSave based on current actionType.
     */
    public function confirmJustify(): void
    {
        if (($this->actionType ?? '') === 'delete') {
            $this->confirmDelete();
        } else {
            $this->confirmSave();
        }
    }

    // Delete workflows
    /**
     * Opens the justification modal for the venue with the given ID.
     * This function should be called when the user wants to delete a venue.
     * It sets the currently edited venue ID and sets actionType to 'delete', then opens the justification modal.
     * @param int $id The ID of the venue to delete
     */
    public function delete(int $id): void
    {
        $this->editId = $id;
        $this->actionType = 'delete';
        $this->dispatch('bs:open', id: 'venueConfirm');
    }

    /**
     * Proceeds from the delete confirmation to the justification modal.
     */
    public function proceedDelete(): void
    {
        $this->dispatch('bs:close', id: 'venueConfirm');
        $this->dispatch('bs:open', id: 'venueJustify');
    }

    /**
     * Confirms the deletion of a venue.
     *
     * This function will validate the justification entered by the user, and then delete the venue with the given ID.
     * After deletion, it clamps the current page to prevent the page from becoming out of bounds.
     * Finally, it shows a toast message indicating the venue was deleted.
     */
    public function confirmDelete(): void
    {
        if ($this->editId) {
            $this->validateJustification();
            try {
                $venue = app(VenueService::class)->getVenueById((int)$this->editId);
                if ($venue) {
                    app(VenueService::class)->deactivateVenues([$venue], \Illuminate\Support\Facades\Auth::user());
                }
            } catch (\Throwable $e) {
                $this->addError('justification', 'Unable to delete venue.');
                return;
            }
        }
        $this->dispatch('bs:close', id: 'venueJustify');
        $this->dispatch('toast', message: 'Venue deleted');
        $this->reset(['editId', 'justification', 'actionType']);
    }

    // Restore-all functionality removed


    // Time range helpers
    /**
     * Adds a new time range to the list of time ranges.
     *
     * This function adds a new empty time range to the list of time ranges.
     * The new time range will be added to the end of the list.
     */
    public function addTimeRange(): void
    {
        $this->timeRanges[] = ['from' => '', 'to' => '', 'reason' => ''];
    }


    /**
     * Removes the time range at index $i from the list of time ranges.
     *
     * This function removes the time range at index $i from the list of time ranges,
     * and then re-indexes the list to maintain contiguous keys.
     *
     * @param int $i The index of the time range to remove.
     */
    public function removeTimeRange(int $i): void
    {
        unset($this->timeRanges[$i]);
        $this->timeRanges = array_values($this->timeRanges);
    }

    // Render
    /**
     * Renders the venues index page.
     *
     * This function renders the venues index page and provides the necessary data
     * to the view. It paginates the filtered collection of venues and ensures
     * that the current page is within the bounds of the paginator. It then
     * returns the view with the paginated data and the visible IDs.
     */
    public function render()
    {
        $paginator = $this->paginated();
        if ($this->page !== $paginator->currentPage()) {
            $paginator = $this->paginated();
        }
        $visibleIds = $paginator->pluck('id')->all();
        return view('livewire.admin.venues-index', [
            'rows' => $paginator,
            'visibleIds' => $visibleIds,
            'departments' => $this->departments,
        ]);
    }

    // Private/Protected Helper Methods
    /**
     * Returns a collection of all the venues.
     */
    protected function allVenues(): Collection
    {
        // Source data via VenueService (aggregate all pages)
        $svc = app(VenueService::class);
        $page = 1;
        $all = collect();
        $last = 1;
        do {
            Paginator::currentPageResolver(fn() => $page);
            try {
                $p = $svc->getAllVenues();
                $last = max(1, (int)$p->lastPage());
                foreach ($p->items() as $v) {
                    $all->push($v);
                }
            } catch (\Throwable $e) {
                break;
            }
            $page++;
        } while ($page <= $last);
        // Restore resolver to default behavior (request page)
        Paginator::currentPageResolver(fn() => (int) request()->input('page', 1));

        return $all->map(fn($v) => $this->mapVenueToRow($v));
    }

    /**
     * Normalize a Venue model into the row shape used by the UI.
     * @param object $v
     * @return array{id:int,name:string,room:string,capacity:int,department:string,manager:string,status:string,features:array,opening:?string,closing:?string}
     */
    protected function mapVenueToRow($v): array
    {
        return [
            'id' => (int)$v->id,
            'name' => (string)$v->name,
            'room' => (string)($v->code ?? ''),
            'capacity' => (int)($v->capacity ?? 0),
            'department' => (string)(optional($v->department)->name ?? ''),
            'manager' => (string)(optional($v->manager)->email ?? ''),
            'status' => 'Active',
            'features' => $this->mapFeaturesStringToLabels((string)($v->features ?? '')),
            'opening' => $v->opening_time ? substr((string)$v->opening_time, 0, 5) : null,
            'closing' => $v->closing_time ? substr((string)$v->closing_time, 0, 5) : null,
        ];
    }

    /**
     * Returns a filtered collection of venues based on the current search query.
     */
    protected function filtered(): Collection
    {
        $needle = mb_strtolower(trim($this->search));
        return $this->allVenues()->filter(function ($venue) use ($needle) {
            $hit = $needle === '' ||
                str_contains(mb_strtolower($venue['name']), $needle) ||
                str_contains(mb_strtolower($venue['room']), $needle) ||
                str_contains(mb_strtolower($venue['manager']), $needle);

            $deptOk = $this->department === '' || $venue['department'] === $this->department;

            $capOk = true;
            if ($this->capMin !== null) $capOk = $capOk && ($venue['capacity'] >= $this->capMin);
            if ($this->capMax !== null) $capOk = $capOk && ($venue['capacity'] <= $this->capMax);

            return $hit && $deptOk && $capOk;
        })->values();
    }

    /**
     * Paginate the filtered collection of venues.
     */
    protected function paginated(): LengthAwarePaginator
    {
        $data = $this->filtered();
        // Apply sorting only after user clicks a sort header
        if ($this->sortField !== '') {
            // Use numeric sort for numeric fields; natural/case-insensitive otherwise
            $options = $this->sortField === 'capacity' ? SORT_NUMERIC : (SORT_NATURAL | SORT_FLAG_CASE);
            $data = $data->sortBy(fn($row) => $row[$this->sortField] ?? '', $options, $this->sortDirection === 'desc')->values();
        }
        $items = $data->slice(($this->page - 1) * $this->pageSize, $this->pageSize)->values();
        return new LengthAwarePaginator($items, $data->count(), $this->pageSize, $this->page, [
            'path' => request()->url(),
            'query' => request()->query()
        ]);
    }

    /**
     * Resets the edit form properties to their default values.
     */
    protected function resetEdit(): void
    {
        $this->reset([
            'editId',
            'vName',
            'vDepartment',
            'vRoom',
            'vCapacity',
            'vManager',
            'vStatus',
            'vFeatures',
            'timeRanges'
        ]);
        $this->vCapacity = 0;
        $this->vStatus   = 'Active';
        $this->vFeatures = [];
        $this->timeRanges = [];
    }

    /**
     * Map a list of feature labels to storage string like "1010" in order [online, multimedia, computers, teaching].
     */
    protected function mapFeatureLabelsToString(array $labels): string
    {
        $order = [
            'Allow Teaching Online' => 0,
            'Allow Teaching With Multimedia' => 1,
            'Allow Teaching with computer' => 2,
            'Allow Teaching' => 3,
        ];
        $bits = ['0', '0', '0', '0'];
        foreach ($labels as $lab) {
            if (isset($order[$lab])) {
                $bits[$order[$lab]] = '1';
            }
        }
        return implode('', $bits);
    }

    /**
     * Map a features storage string like "1010" to human labels expected by the UI.
     */
    protected function mapFeaturesStringToLabels(string $features): array
    {
        $labels = ['Allow Teaching Online', 'Allow Teaching With Multimedia', 'Allow Teaching with computer', 'Allow Teaching'];
        $out = [];
        $chars = str_split($features);
        foreach ($labels as $i => $lab) {
            $outIdx = $chars[$i] ?? '0';
            if ($outIdx === '1') $out[] = $lab;
        }
        return $out;
    }

    
}
