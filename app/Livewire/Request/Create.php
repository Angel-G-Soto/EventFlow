<?php

namespace App\Livewire\Request;

use App\Models\Event;
use App\Services\DocumentRequirementService;
use App\Services\VenueAvailabilityService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

class Create extends Component
{
    use WithFileUploads;


// Wizard state
    public int $step = 3; // 1..3


// Part 1
    public string $student_phone = '';
    public string $student_number = '';
    public string $title = '';
    public string $description = '';
    public string $start_at = ''; // datetime-local string
    public string $end_at = '';
    public array $category_ids = [];


// Prefilled org/advisor
    public ?int $organization_id = null;
    public string $organization_name = '';
    public string $advisor_name = '';
    public string $advisor_phone = '';
    public string $advisor_email = '';


// Part 2
    public ?int $venue_id = null;
    public array $availableVenues = [];
    public bool $loadingVenues = false;


// Part 3 (dynamic required docs)
    public array $requiredDocuments = []; // from service
    public array $uploads = []; // key => TemporaryUploadedFile

    public function mount(array $organization = []): void
    {
// Prefill from the authenticated user's primary organization (customize as needed)
//        $user = Auth::user();
//        $organization = $org ?? ($user->organization ?? null); // adapt to your user->organization relation


        $this->organization_id   = $organization['id']            ?? null;  // optional
        $this->organization_name = $organization['name']          ?? '';
        $this->advisor_name      = $organization['advisor_name']  ?? '';
        $this->advisor_phone     = $organization['advisor_phone'] ?? '';
        $this->advisor_email     = $organization['advisor_email'] ?? '';
    }

// === Validation per step ===
    protected function rulesForStep(int $step): array
    {
        if ($step === 1) {
            return [
                'student_phone' => ['required','string','min:7','max:20'],
                'student_number' => ['required','string','max:30'],
                'title' => ['required','string','max:200'],
                'description' => ['required','string','min:10'],
                'start_at' => ['required','date'],
                'end_at' => ['required','date','after:start_at'],
                'category_ids' => ['required','array','min:1'],
//                'category_ids.*' => ['integer', Rule::exists('categories','id')],
                'organization_id' => ['nullable','integer'],
                'advisor_name' => ['required','string','max:150'],
                'advisor_phone' => ['required','string','max:20'],
                'advisor_email' => ['required','email','max:150'],
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
    public function updatedStartAt(): void { $this->refreshVenuesIfPossible(); }
    public function updatedEndAt(): void { $this->refreshVenuesIfPossible(); }


    protected function refreshVenuesIfPossible(): void
    {
        if ($this->step === 2 && $this->validTimeRange()) {
            $this->loadAvailableVenues();
        }
    }
    protected function validTimeRange(): bool
    {
        if (empty($this->start_at) || empty($this->end_at)) return false;
        try {
            return Carbon::parse($this->end_at)->gt(Carbon::parse($this->start_at));
        } catch (\Throwable $e) {
            return false;
        }
    }


    public function next(VenueAvailabilityService $venueService, DocumentRequirementService $docSvc): void
    {
        $this->validate($this->rulesForStep($this->step));


        if ($this->step === 1) {
            // Step 1 complete ⇒ preload venues for Step 2
            $this->loadAvailableVenues($venueService);
            $this->step = 2;
            return;
        }


        if ($this->step === 2) {
            // Step 2 complete ⇒ fetch required docs for chosen venue
            $this->requiredDocuments = $docSvc->forVenue((int) $this->venue_id);
            $this->step = 3;
            return;
        }
    }

    public function back(): void
    {
        if ($this->step > 1) $this->step--;
    }


    protected function loadAvailableVenues(?VenueAvailabilityService $service = null): void
    {
        $this->loadingVenues = true;
        $this->availableVenues = [];
        try {
            $start = Carbon::parse($this->start_at);
            $end = Carbon::parse($this->end_at);
            $service = $service ?? app(VenueAvailabilityService::class);
            $this->availableVenues = $service->availableBetween($start, $end);
        } catch (\Throwable $e) {
            $this->addError('venue_id', 'Could not load venue availability.');
        } finally {
            $this->loadingVenues = false;
        }
    }

    public function submit(): void
    {
// Validate step 3 (dynamic files)
        $this->validate($this->rulesForStep(3));


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
                'name' => 'Engoneering',
            ]
        ];

        return view('livewire.request.create',[
            'allCategories' => $category,
        ]);
    }
}
