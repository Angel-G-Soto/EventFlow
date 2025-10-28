<?php

namespace App\Livewire\Demo;

use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class FilterSandbox extends Component
{
    public array $orgOptions   = [];
    public array $catOptions   = [];
    public array $venueOptions = [];

    // current filters (match MultiFilter payload shape)
    public array $orgIds = [];
    public array $catIds = [];
    public array $venueIds = [];

    // mirror into URL to test query-string behavior (optional)
    #[Url(as: 'orgs', except: '')] public string $orgsCsv   = '';
    #[Url(as: 'cats', except: '')] public string $catsCsv   = '';
    #[Url(as: 'venues', except: '')] public string $venuesCsv = '';

    /** Dummy data: pretend “events table” */
    public array $allEvents = [];

    public function mount(): void
    {
        // Options (id => label)
        $this->orgOptions = [1=>'IEEE', 2=>'ACM', 3=>'HKN'];
        $this->catOptions = [10=>'Workshop', 11=>'Talk', 12=>'Hackathon'];
        $this->venueOptions = [100=>'Student Center', 101=>'Auditorium', 102=>'Library'];

        // Fake events (shape similar to what your real list uses)
        $this->allEvents = [
            ['id'=>1, 'title'=>'FPGA 101',      'organization_id'=>1, 'venue_id'=>101, 'categories'=>[11]],
            ['id'=>2, 'title'=>'Hack Night',    'organization_id'=>2, 'venue_id'=>100, 'categories'=>[12]],
            ['id'=>3, 'title'=>'Intro to PHP',  'organization_id'=>2, 'venue_id'=>102, 'categories'=>[10,11]],
            ['id'=>4, 'title'=>'Robotics Talk', 'organization_id'=>3, 'venue_id'=>101, 'categories'=>[11]],
        ];

        // If URL already has filters, hydrate local arrays
        if ($this->orgsCsv !== '')   $this->orgIds   = $this->fromCsv($this->orgsCsv);
        if ($this->catsCsv !== '')   $this->catIds   = $this->fromCsv($this->catsCsv);
        if ($this->venuesCsv !== '') $this->venueIds = $this->fromCsv($this->venuesCsv);
    }

    #[On('filters.updated')]
    public function handleFiltersUpdated(array $orgIds = [], array $categoryIds = [], array $venueIds = []): void
    {
        $this->orgIds   = $this->sanitize($orgIds);
        $this->catIds   = $this->sanitize($categoryIds);
        $this->venueIds = $this->sanitize($venueIds);

        // Keep URL CSV in sync so you can test sharing links
        $this->orgsCsv   = implode(',', $this->orgIds);
        $this->catsCsv   = implode(',', $this->catIds);
        $this->venuesCsv = implode(',', $this->venueIds);
    }

    public function getFilteredEvents(): array
    {
        return array_values(array_filter($this->allEvents, function ($e) {
            if ($this->orgIds && !in_array($e['organization_id'], $this->orgIds, true)) return false;
            if ($this->venueIds && !in_array($e['venue_id'], $this->venueIds, true)) return false;
            if ($this->catIds && count(array_intersect($e['categories'], $this->catIds)) === 0) return false;
            return true;
        }));
    }

    public function render()
    {
        return view('livewire.demo.filter-sandbox', [
            'events' => $this->getFilteredEvents(),
        ]);
    }

    private function fromCsv(string $csv): array
    {
        if (trim($csv) === '') return [];
        return $this->sanitize(array_map('intval', explode(',', $csv)));
    }

    private function sanitize(array $ids): array
    {
        $ids = array_map('intval', $ids);
        $ids = array_values(array_unique(array_filter($ids, fn($v) => $v > 0)));
        sort($ids);
        return $ids;
    }
}
