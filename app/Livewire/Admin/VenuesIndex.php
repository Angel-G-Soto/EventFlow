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
use Illuminate\Support\Facades\Auth;
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

    private const DAYS_OF_WEEK = [
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
        'Sunday',
    ];

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
        $this->authorize('manage-venues');

        try {
            return app(VenueService::class)->listVenueDepartments();
        } catch (\Throwable $e) {
            return [];
        }
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
        $this->page = max(1, $target);
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
     * Resets the csvFile field to its default value and opens the CSV modal.
     *
     * This function will reset the csvFile field to its default value and
     * open the CSV modal. It is called when the user clicks the "Add
     * Venues by CSV" button.
     */
    public function openCsvModal(): void
    {
        $this->authorize('manage-venues');

        $this->reset('csvFile');
        $this->importErrorMsg = null; // clear any previous error before a new attempt
        $this->dispatch('bs:open', id: 'csvModal');
    }

    /**
     * Handle CSV upload to add venues in bulk.
     * - Validates file type and size
     * - Stores temporarily on uploads_temp disk
     * - Dispatches background job to virus-scan, parse, and import via VenueService
     */
    public function uploadCsv(): void
    {
        $this->authorize('manage-venues');

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
            $context = [];
            if (function_exists('request') && request()) {
                $context = [
                    'ip' => request()->ip(),
                    'ua' => request()->userAgent(),
                ];
            }
            ProcessCsvFileUpload::dispatch($safe, $adminId, $context);

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
        $this->authorize('manage-venues');

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
     * Populate and show the details modal for a venue using the service layer only.
     */
    public function showDetails(int $id): void
    {
        $this->authorize('manage-venues');

        try {
            $venue = app(VenueService::class)->getVenueById($id);
            if (!$venue) {
                $this->addError('detailsId', 'Venue not found.');
                return;
            }
            $venue->loadMissing('availabilities');
            // Normalize details payload for the view
            $features = $this->mapFeaturesStringToLabels((string)($venue->features ?? ''));
            $this->detailsId = (int) $venue->id;
            $this->details = [
                'id'         => (int) $venue->id,
                'name'       => (string) $venue->name,
                'department' => (string) (optional($venue->department)->name ?? ''),
                'code'       => (string) ($venue->code ?? ''),
                'capacity'   => (int) ($venue->capacity ?? 0),
                'features'       => $features,
                'availabilities' => $this->formatAvailabilityForDetails($venue->availabilities ?? collect()),
            ];
            $this->dispatch('bs:open', id: 'venueDetails');
        } catch (\Throwable $e) {
            $this->addError('detailsId', 'Unable to load venue.');
        }
    }

    protected function formatAvailabilityForDetails(Collection $availabilities): array
    {
        if ($availabilities->isEmpty()) {
            return [];
        }

        $order = array_flip(self::DAYS_OF_WEEK);

        return $availabilities
            ->sortBy(function ($slot) use ($order) {
                $day = (string) ($slot->day ?? '');
                $dayIndex = $order[$day] ?? 99;
                $time = (string) ($slot->opens_at ?? '');
                return sprintf('%02d_%s', $dayIndex, $time);
            })
            ->map(function ($slot) {
                return [
                    'day' => (string) ($slot->day ?? ''),
                    'opens' => substr((string) ($slot->opens_at ?? ''), 0, 5),
                    'closes' => substr((string) ($slot->closes_at ?? ''), 0, 5),
                ];
            })
            ->filter(function ($slot) {
                return $slot['day'] !== '';
            })
            ->values()
            ->all();
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
    // Delete workflows
    /**
     * Opens the justification modal for the venue with the given ID.
     * This function should be called when the user wants to delete a venue.
     * It sets the currently edited venue ID and opens the confirmation modal.
     * @param int $id The ID of the venue to delete
     */
    public function delete(int $id): void
    {
        $this->authorize('manage-venues');

        $this->editId = $id;
        $this->dispatch('bs:open', id: 'venueConfirm');
    }

    /**
     * Proceeds from the delete confirmation to the justification modal.
     */
    public function proceedDelete(): void
    {
        $this->authorize('manage-venues');

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
        $this->authorize('manage-venues');

        if ($this->editId) {
            $this->validateJustification();
            try {
                $venue = app(VenueService::class)->getVenueById((int)$this->editId);
                if ($venue) {
                    app(VenueService::class)->deactivateVenues([$venue], Auth::user());
                }
            } catch (\Throwable $e) {
                $this->addError('justification', 'Unable to delete venue.');
                return;
            }
        }
        $this->dispatch('bs:close', id: 'venueJustify');
        $this->dispatch('toast', message: 'Venue deleted');
        $this->reset(['editId', 'justification']);
    }

    // Restore-all functionality removed


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
        $this->authorize('manage-venues');

        $paginator = $this->venuesPaginator();
        $visibleIds = $paginator->pluck('id')->all();
        return view('livewire.admin.venues-index', [
            'rows' => $paginator,
            'visibleIds' => $visibleIds,
            'departments' => $this->departments,
        ]);
    }

    protected function venuesPaginator(): LengthAwarePaginator
    {
        $svc = app(VenueService::class);
        $sort = $this->sortField !== '' ? ['field' => $this->sortField, 'direction' => $this->sortDirection] : null;
        $filters = [
            'search' => $this->search,
            'department_name' => $this->department ?: null,
            'cap_min' => $this->capMin,
            'cap_max' => $this->capMax,
        ];
        $paginator = $svc->paginateVenueRows(
            $filters,
            $this->pageSize,
            $this->page,
            $sort
        );

        $last = max(1, (int)$paginator->lastPage());
        if ($this->page > $last) {
            $this->page = $last;
            if ((int)$paginator->currentPage() !== $last) {
                $paginator = $svc->paginateVenueRows(
                    $filters,
                    $this->pageSize,
                    $this->page,
                    $sort
                );
            }
        }

        return $paginator;
    }
    /**
     * Resets the edit form properties to their default values.
     */
    /**
     * Map a list of feature labels to storage string like "1010" in order [online, multimedia, computers, teaching].
     */
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
