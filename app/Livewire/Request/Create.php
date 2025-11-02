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

use App\Models\Event;
//use App\Services\DocumentRequirementService;
use App\Services\UseRequirementService;
//use App\Services\VenueAvailabilityService;
use App\Services\VenueService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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

#[Layout('layouts.user')]
class Create extends Component
{
    use WithFileUploads;


// Wizard state
/**
 * @var int
 */
    public int $step = 1; // 1..3


// Part 1
/**
 * @var string
 */
    public string $student_phone = '';
/**
 * @var string
 */
    public string $student_number = '';
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
    public string $start_at = ''; // datetime-local string
/**
 * @var string
 */
    public string $end_at = '';
/**
 * @var array
 */
    public array $category_ids = [];
/**
 * @var bool
 */
    public bool $has_funds = false;
/**
 * @var bool
 */
    public bool $sells_food = false;
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
    public string $advisor_name = '';
/**
 * @var string
 */
    public string $advisor_phone = '';
/**
 * @var string
 */
    public string $advisor_email = '';


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


// Part 3 (dynamic required docs)
/**
 * @var array
 */
    public array $requiredDocuments = []; // from service
/**
 * @var array
 */
    public array $uploads = []; // key => TemporaryUploadedFile

    /**
     * mount
     * Initialize defaults. This component expects an $organization array
     * to be optionally passed from the route/controller.
     *
     * @param array{id?:int,name?:string,advisor_name?:string,advisor_phone?:string,advisor_email?:string}|null $organization
     */
    public function mount(array $organization = []): void
    {
        $this->organization_id   = $organization['id']            ?? null;  // optional
        $this->organization_name = $organization['name']          ?? '';
        $this->advisor_name      = $organization['advisor_name']  ?? '';
        $this->advisor_phone     = $organization['advisor_phone'] ?? '';
        $this->advisor_email     = $organization['advisor_email'] ?? '';
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
                'student_phone' => ['required','string','regex:/^\D*(\d\D*){10}$/'],
                'student_number' => ['required','string','max:30'],
                'title' => ['required','string','max:200'],
                'description' => ['required','string','min:10'],
                'start_at' => ['required','date'],
                'end_at' => ['required','date','after:start_at'],
                'category_ids' => ['array','min:0'],
//                'category_ids.*' => ['integer', Rule::exists('categories','id')],
                'organization_id' => ['nullable','integer'],
                'advisor_name' => ['required','string','max:150'],
                'advisor_phone' => ['required','string','max:20'],
                'advisor_email' => ['required','email','max:150'],
                'sells_food' => ['boolean'],
                'external_guest' => ['boolean'],
                'has_funds' => ['boolean'],

            ];
        }


        if ($step === 2) {
            return [
                'venue_id' => ['required','integer'],
            ];
        }


// Step 3: dynamic files
        $fileRules = [];
        foreach ($this->requiredDocuments as $doc) {
            $key = $doc['key'];
            $base = ['file','max:'.$doc['max_kb']];
            if (!empty($doc['mimes'])) {
                $base[] = 'mimes:'.implode(',', $doc['mimes']);
            }
// required or nullable depending on rule
            $fileRules["uploads.$key"] = $doc['required'] ? array_merge(['required'], $base) : array_merge(['nullable'], $base);
        }
        return $fileRules;
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
        if (empty($this->start_at) || empty($this->end_at)) return false;
        try {
            return Carbon::parse($this->end_at)->gt(Carbon::parse($this->start_at));
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
            $this->requiredDocuments = app(VenueService::class)->getVenueRequirements($this->venue_id)->toArray();
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
    protected function loadAvailableVenues(?VenueService $service = null): void
    {
        $this->loadingVenues = true;
        $this->availableVenues = [];
        try {
            $start = Carbon::parse($this->start_at);
            $end = Carbon::parse($this->end_at);
            $service = $service ?? app(VenueService::class);
            $this->availableVenues = $service->getAvailableVenues($start, $end)->toArray();
            //dd($this->availableVenues);
        } catch (\Throwable $e) {
            $this->addError('venue_id', 'Could not load venue availability.');
        } finally {
            $this->loadingVenues = false;
        }
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
// Validate step 3 (dynamic files)
        $this->validate($this->rulesForStep(3));

        // Roadmap

            // create document and category models and store on DB. Store them on arrays

            // call updateOrCreateFromEventForm

            // call document service that scans documents. Dispatches job

            // return home or pending requests or ...

        DB::transaction(function () {
            $event = Event::create([
                'student_phone' => $this->student_phone,
                'student_number' => $this->student_number,
                'title' => $this->title,
                'description' => $this->description,
                'start_at' => Carbon::parse($this->start_at),
                'end_at' => Carbon::parse($this->end_at),
                'organization_id' => $this->organization_id,
                'venue_id' => $this->venue_id,
                'advisor_name' => $this->advisor_name,
                'advisor_phone' => $this->advisor_phone,
                'advisor_email' => $this->advisor_email,
            ]);


            $event->categories()->sync($this->category_ids);


// Store uploads
            foreach ($this->requiredDocuments as $doc) {
                $key = $doc['key'];
                $file = $this->uploads[$key] ?? null; // may be null if not required/omitted
                if (!$file) continue;


                $storedPath = $file->store("events/{$event->id}", 'public');
                $event->documents()->create([
                    'key' => $key,
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $storedPath,
                    'mime' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        });


        session()->flash('success', 'Event submitted successfully.');
        redirect()->route('events.create'); // or to a details/thanks page
    }

    /**
     * Render the Livewire view.
     * The controller/route can also pass $organization externally when mounting.
     *
     * @return View
     */
    public function render()
    {
        $category  = [
            [
                'id' => 1,
                'name' => 'Food Sell',
            ],
            [
                'id' => 2,
                'name' => 'Workshop',
            ],
            [
                'id' => 4,
                'name' => 'Engineering',
            ]
        ];

        return view('livewire.request.create',[
            'allCategories' => $category,
        ]);
    }
}
