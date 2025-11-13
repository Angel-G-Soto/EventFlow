<?php

/**
 * Livewire Component: Create
 *
 * EventFlow (Laravel 12 + Livewire 3 + Bootstrap 5).
 * Presents a form to create a new resource (e.g., Event, Venue, Requirement)
 * and handles validation, persistence, and feedback to the user.
 *
 * Responsibilities:
 * - Initialize default form state in mount().
 * - Validate input and persist a new record in save()/store().
 * - Emit events or redirect after successful creation.
 *
 * @since   2025-11-01
 */

namespace App\Livewire\Request;

//use App\Services\DocumentRequirementService;
//use App\Services\UseRequirementService;
//use App\Services\VenueAvailabilityService;
//use Illuminate\Support\Facades\DB;

use App\Services\CategoryService;
use App\Services\DepartmentService;
use App\Services\EventService;
use App\Services\VenueService;
use App\Services\DocumentService;
use App\Services\UserService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Class Create
 *
 * Livewire component for creating a new resource.
 * Provides reactive form state, validation, and persistence.
 */

#[Layout('layouts.app')]
class Create extends Component
{
    use WithFileUploads;
    protected DocumentService $docs;

    private const DAYS_OF_WEEK = [
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
        'Sunday',
    ];


// Wizard state
/**
 * @var int
 */
    public int $step = 1; // 1..3


// Part 1
/**
 * @var string
 */
    public string $creator_phone_number = '';
/**
 * @var string
 */
    public string $creator_institutional_number = '';
/**
 * @var string
 */
    public string $title = '';
/**
 * @var string
 */
    public string $description = '';
/**
 * @var string
 */
    public string $guest_size = '';
/**
 * @var string
 */
    public string $start_time = ''; // datetime-local string
/**
 * @var string
 */
    public string $end_time = '';
/**
 * @var array
 */
    public array $category_ids = [];
    public string $categorySearch = '';

 /**
  * @var int[]
  */
    public array $uploadedDocumentIds = [];

/**
 * @var bool
 */
    public bool $use_institutional_funds = false;
/**
 * @var bool
 */
    public bool $handles_food = false;
/**
 * @var bool
 */
    public bool $external_guest = false;
/**
 * @var array
 */
    public array $requirementFiles = [];



// Prefilled org/advisor
/**
 * @var ?int
 */
    public ?int $organization_id = null;
/**
 * @var string
 */
    public string $organization_name = '';
/**
 * @var string
 */
    public string $organization_advisor_name = '';

///**
// * @var string
// */
//    public string $advisor_phone = '';

/**
 * @var string
 */
    public ?string $organization_advisor_email = '';


// Part 2
/**
 * @var ?int
 */
    public ?int $venue_id = null;
/**
 * @var array
 */
    public array $availableVenues = [];
/**
 * @var bool
 */
    public bool $loadingVenues = false;
/**
 * @var array|null
 */
    public ?array $selectedVenueDetails = null;
/**
 * @var bool
 */
    public bool $showVenueDescriptionModal = false;
/**
 * @var int
 */
    public int $venuePage = 1;
/**
 * @var int
 */
    public int $venuePerPage = 10;


// Part 3 (dynamic required docs)
/**
 * @var array
 */
    public array $requiredDocuments = []; // from service
/**
 * @var array
 */
    public array $uploads = []; // key => TemporaryUploadedFile


    public string $venueSearch = '';
    public ?int $venueCapacityFilter = null;
    public ?int $venueDepartmentFilter = null;

    /**
     * mount
     * Initialize defaults. This component expects an $organization array
     * to be optionally passed from the route/controller.
     *
     * @param array{id?:int,name?:string,organization_advisor_name?:string,advisor_phone?:string,advisor_email?:string}|null $organization
     */
    public function mount(array $organization = []): void
    {
        $this->organization_id   = $organization['id']            ?? null;  // optional
        $this->organization_name = $organization['name']          ?? '';
        $this->organization_advisor_name      = $organization['advisor_name']  ?? '';
//        $this->advisor_phone     = $organization['advisor_phone'] ?? '';
        $this->organization_advisor_email   = $organization['advisor_email'] ?? '';
    }


    public function boot(DocumentService $docs)
    {
        $this->docs = $docs;
    }


    /**
     * Return validation rules that apply *only* to the provided wizard step.
     * This lets us validate progressively as the user advances.
     *
     * @param  int $step
     * @return array<string,string|array<mixed>>
     */
    protected function rulesForStep(int $step): array
    {
        if ($step === 1) {
            return [
                'creator_phone_number' => ['required','string','regex:/^\D*(\d\D*){10}$/'],
                'creator_institutional_number' => ['required','string','max:30'],
                'title' => ['required','string','max:200'],
                'description' => ['required','string','min:10'],
                'guest_size' => ['required','integer','min:0'],
                'start_time' => ['required','date'],
                'end_time' => ['required','date','after:start_time'],
                'category_ids' => ['array','min:1'],
//                'category_ids.*' => ['integer', Rule::exists('categories','id')],
                'organization_name' => ['required','string','max:255'],
                'organization_id' => ['nullable','integer'],
                'organization_advisor_name' => ['required','string','max:150'],
//                'advisor_phone' => ['required','string','max:20'],
                'organization_advisor_email' => ['required','email','max:150'],
                'handles_food' => ['boolean'],
                'external_guest' => ['boolean'],
                'use_institutional_funds' => ['boolean'],

            ];
        }


        if ($step === 2) {
            return [
                'venue_id' => ['required','integer'],
            ];
        }

//        Step 3
        return [
            'requirementFiles'   => ['array'],
            'requirementFiles.*' => ['file', 'mimes:pdf', 'max:10240'], // 10 MB each
        ];


//// Step 3: dynamic files
//        $fileRules = [];
//        foreach ($this->requiredDocuments as $doc) {
//            $key = $doc['key'];
//            $base = ['file','max:'.$doc['max_kb']];
//            if (!empty($doc['mimes'])) {
//                $base[] = 'mimes:'.implode(',', $doc['mimes']);
//            }
//// required or nullable depending on rule
//            $fileRules["uploads.$key"] = $doc['required'] ? array_merge(['required'], $base) : array_merge(['nullable'], $base);
//        }
//        return $fileRules;
    }

    // Reactivity: if time window changes, refresh venues if we are on step 2
/**
 * RefreshVenuesIfPossible action.
 * @return void
 */

    protected function refreshVenuesIfPossible(): void
    {
        if ($this->step === 2 && $this->validTimeRange()) {
            $this->loadAvailableVenues();
        }
    }
/**
 * ValidTimeRange action.
 * @return bool
 */
    protected function validTimeRange(): bool
    {
        if (empty($this->start_time) || empty($this->end_time)) return false;
        try {
            return Carbon::parse($this->end_time)->gt(Carbon::parse($this->start_time));
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Move to the next step after validating the current one.
     * Also performs side‑effects needed before entering the next step
     * (e.g., loading venues upon entering the venue selection step).
     */
    public function next(/*DocumentRequirementService $docSvc*/): void
    {
        //$this->validate($this->rulesForStep($this->step));


        if ($this->step === 1) {
            // Step 1 complete ⇒ preload venues for Step 2
            $this->loadAvailableVenues();
            $this->step = 2;
            return;
        }


        if ($this->step === 2) {
            // Step 2 complete ⇒ fetch required docs for chosen venue
            //$this->requiredDocuments = $docSvc->forVenue((int)3);
            $this->requiredDocuments = app(VenueService::class)->getVenueRequirements((int)$this->venue_id)->toArray();
            //dd($this->requiredDocuments);
            $this->step = 3;
            return;
        }
    }
    /**
     * Go back one step.
     */
    public function back(): void
    {
        if ($this->step > 1) $this->step--;
    }

    /**
     * Compute available venues for the current time range.
     *
     */
    protected function loadAvailableVenues(?VenueService $service = null, array $filters = []): void
    {
        $this->loadingVenues = true;
        $this->availableVenues = [];
        try {
            $start = Carbon::parse($this->start_time);
            $end = Carbon::parse($this->end_time);
            $service = $service ?? app(VenueService::class);
            $this->availableVenues = $service->getAvailableVenues($start, $end, $filters)->toArray();
            //dd($this->availableVenues);
            $this->selectedVenueDetails = $this->resolveVenueDetails($this->venue_id);
            $this->resetVenuePagination();
        } catch (\Throwable $e) {
            $this->addError('venue_id', 'Could not load venue availability.');
        } finally {
            $this->loadingVenues = false;
        }
    }

    public function runVenueSearch(): void
    {
        if (!$this->validTimeRange()) {
            $this->addError('venue_id', 'Select a valid start and end time before searching for venues.');
            return;
        }

        $this->loadAvailableVenues(filters: $this->buildVenueFilters());
    }

    public function resetVenueFilters(): void
    {
        $this->venueSearch = '';
        $this->venueCapacityFilter = null;
        $this->venueDepartmentFilter = null;

        if ($this->validTimeRange()) {
            $this->loadAvailableVenues();
        }

        $this->resetVenuePagination();
    }

    /**
     * @return array<string,int|string>
     */
    protected function buildVenueFilters(): array
    {
        $filters = [];

        $search = trim($this->venueSearch);
        if ($search !== '') {
            $filters['search'] = $search;
        }

        if ($this->venueCapacityFilter !== null && $this->venueCapacityFilter > 0) {
            $filters['capacity'] = (int)$this->venueCapacityFilter;
        }

        if ($this->venueDepartmentFilter !== null && $this->venueDepartmentFilter > 0) {
            $filters['department_id'] = (int)$this->venueDepartmentFilter;
        }

        return $filters;
    }

    public function updatedVenueId($value): void
    {
        $this->selectedVenueDetails = $this->resolveVenueDetails((int)$value);
    }

    public function showVenueDescription(?int $venueId = null): void
    {
        $targetVenue = $venueId ?? $this->venue_id;
        if (!$targetVenue) {
            return;
        }

        $details = $this->resolveVenueDetails($targetVenue);
        if ($details) {
            $this->selectedVenueDetails = $details;
            $this->showVenueDescriptionModal = true;
        }
    }

    public function closeVenueDescription(): void
    {
        $this->showVenueDescriptionModal = false;
    }

    protected function resolveVenueDetails(?int $venueId): ?array
    {
        if (!$venueId) {
            return null;
        }

        foreach ($this->availableVenues as $venue) {
            if ((int)($venue['id'] ?? 0) !== (int)$venueId) {
                continue;
            }

            $venue['availabilities'] = $this->sortAvailabilitySlots($venue['availabilities'] ?? []);
            return $venue;
        }

        return null;
    }

    protected function sortAvailabilitySlots(array $slots): array
    {
        $order = array_flip(self::DAYS_OF_WEEK);
        usort($slots, function ($a, $b) use ($order) {
            $dayA = $order[$a['day'] ?? ''] ?? 99;
            $dayB = $order[$b['day'] ?? ''] ?? 99;

            if ($dayA === $dayB) {
                return strcmp((string)($a['opens_at'] ?? ''), (string)($b['opens_at'] ?? ''));
            }

            return $dayA <=> $dayB;
        });

        return $slots;
    }

    /**
     * Persist the Event and any related records/pivots atomically.
     * Wraps everything in a DB transaction for consistency.
     *
     * Side‑effects:
     * - Creates the Event row.
     * - Attaches categories (pivot table).
     * - Delegates document handling to {@see DocumentRequirementService}.
     */
    public function submit(): void
    {


//        $data['venue_id']
//        $data['organization_name']
//        $data['organization_advisor_name']
//        $data['organization_advisor_email']
//        $data['creator_institutional_number']
//        $data['creator_phone_number']
//        $data['title']
//        $data['description'
//        $data['guest_size']
//        $data['start_time']
//        $data['end_time']
//        $data['guests']
//        $data['handles_food']
//        $data['use_institutional_funds']
//        $data['external_guests']

// Validate step 3 (dynamic files)
        $this->validate($this->rulesForStep(3));

        // Roadmap
            //create event

        $userService = app(UserService::class);
        $user = Auth::user();



        $data = [
            'venue_id' => $this->venue_id,
            'creator_phone_number' => $this->creator_phone_number,
            'creator_institutional_number' => $this->creator_institutional_number,
            'title' => $this->title,
            'description' => $this->description,
            'guest_size' => $this->guest_size,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'organization_advisor_name' => $this->organization_advisor_name,
            'organization_advisor_email' => $this->organization_advisor_email,
            'handles_food' => $this->handles_food,
            'external_guest' => $this->external_guest,
            'use_institutional_funds' => $this->use_institutional_funds,
        ];

        $eventService = $eventService ?? app(EventService::class);

        $event = $eventService->updateOrCreateFromEventForm(
            data: $data,
            creator: $user,
            action: 'publish',
            categories_ids: $this->category_ids,
        );

        $data['id'] = $event->id;

            // create document and category models and store on DB. Store them on arrays

        $service = $this->docs ?? app(DocumentService::class);

        foreach ($this->requirementFiles as $file) {
            // Livewire’s TemporaryUploadedFile generally extends/behaves like UploadedFile.
            // If your version complains about the type hint, cast defensively:
            $uploaded = (is_object($file) && method_exists($file, 'toUploadedFile'))
                ? $file->toUploadedFile()
                : $file;

            try {
                $doc = $service->handleUpload(
                    file:   $uploaded,          // UploadedFile-compatible
                    userId: Auth::id(),  // or pass the student/submitter id you need
                    eventId:$event->id
                );

                $this->uploadedDocumentIds[] = $doc->id;

            } catch (\App\Exceptions\StorageException $e) {
                // Surface a friendly message but keep going (or break—your call)
                $this->addError('requirementFiles', "Failed to enqueue scan for {$file->getClientOriginalName()}.");
                report($e);
            }
        }

//        $eventService->updateOrCreateFromEventForm(
//            data: $data,
//            creator: $user,
//            action: 'publish',
//            document_ids: $this->uploadedDocumentIds,
//        );




            // call updateOrCreateFromEventForm


            // return home or pending requests or ...



        session()->flash('success', 'Event submitted successfully.');
        redirect()->route('public.calendar'); // or to a details/thanks page
    }

    public function clearCategories(): void
    {
        $this->category_ids = [];
    }

    public function removeCategory(int $categoryId): void
    {
        $this->category_ids = array_values(array_filter(
            $this->category_ids,
            fn ($id) => (int) $id !== (int) $categoryId
        ));
    }

    /**
     * Render the Livewire view.
     * The controller/route can also pass $organization externally when mounting.
     *
     * @return View
     */
    public function render()
    {
        $categories  = app(CategoryService::class)->getAllCategories()->toArray();
        $search = trim($this->categorySearch);
        $filtered = array_values(array_filter($categories, function ($cat) use ($search) {
            if ($search === '') {
                return true;
            }
            return str_contains(
                mb_strtolower($cat['name']),
                mb_strtolower($search)
            );
        }));

        $selectedLabels = collect($categories)
            ->whereIn('id', $this->category_ids)
            ->pluck('name', 'id')
            ->toArray();

        $departments = app(DepartmentService::class)
            ->getAllDepartments()
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->toArray();

        return view('livewire.request.create',[
            'filteredCategories' => $filtered,
            'selectedCategoryLabels' => $selectedLabels,
            'departments' => $departments,
        ]);
    }


    #[Computed]
    public function paginatedVenues(): array
    {
        $meta = $this->venuePagination();
        $offset = max(0, ($meta['current'] - 1) * $meta['per']);
        return array_slice($this->availableVenues, $offset, $meta['per']);
    }

    #[Computed]
    public function venuePagination(): array
    {
        $total = count($this->availableVenues);
        $perPage = max(1, (int)$this->venuePerPage);
        $lastPage = max(1, (int)ceil($total / $perPage));
        $current = max(1, min($this->venuePage, $lastPage));
        $from = $total === 0 ? 0 : (($current - 1) * $perPage) + 1;
        $to = $total === 0 ? 0 : min($from + $perPage - 1, $total);

        return [
            'total' => $total,
            'per' => $perPage,
            'current' => $current,
            'last' => $lastPage,
            'from' => $from,
            'to' => $to,
        ];
    }

    #[Computed]
    public function formattedStartTime(): string
    {
        return $this->formatDisplayDate($this->start_time);
    }

    #[Computed]
    public function formattedEndTime(): string
    {
        return $this->formatDisplayDate($this->end_time);
    }

    public function selectVenue(int $id): void
    {
        $this->venue_id = $id;
    }

    public function goToVenuePage(int $page): void
    {
        $this->venuePage = $this->normalizeVenuePage($page);
    }

    public function previousVenuePage(): void
    {
        $this->venuePage = $this->normalizeVenuePage($this->venuePage - 1);
    }

    public function nextVenuePage(): void
    {
        $this->venuePage = $this->normalizeVenuePage($this->venuePage + 1);
    }

    protected function resetVenuePagination(): void
    {
        $this->venuePage = 1;
    }

    protected function normalizeVenuePage(int $page): int
    {
        $meta = $this->venuePagination();
        $maxPage = max(1, $meta['last']);
        return max(1, min($page, $maxPage));
    }

    protected function formatDisplayDate(?string $value): string
    {
        if (empty($value)) {
            return '—';
        }

        try {
            return Carbon::parse($value)->format('M j, Y g:i A');
        } catch (\Throwable $e) {
            return (string)$value;
        }
    }
}
