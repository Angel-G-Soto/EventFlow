<?php


namespace App\Livewire\Filters;
use Livewire\Attributes\Url;


use Livewire\Component;


class MultiFilter extends Component
{
    /** Option lists: [id => label] */
    public array $organizations = [];
    public array $categories = [];
    public array $venues = [];


    /** Selected ids */
    public array $selectedOrganizations = [];
    public array $selectedCategories = [];
    public array $selectedVenues = [];

    #[Url(as: 'orgs', except: '')] public string $orgsCsv = '';
    #[Url(as: 'cats', except: '')] public string $catsCsv = '';
    #[Url(as: 'venues', except: '')] public string $venuesCsv = '';


    public bool $autoApply = true; // emit on every change

    public function mount(
        array $organizations = [],
        array $categories = [],
        array $venues = [],
        bool $autoApply = true,
    ): void {
        $this->organizations = $organizations;
        $this->categories = $categories;
        $this->venues = $venues;
        $this->autoApply = $autoApply;


// Hydrate from URL if present
        if ($this->orgsCsv !== '') $this->selectedOrganizations = $this->fromCsv($this->orgsCsv);
        if ($this->catsCsv !== '') $this->selectedCategories = $this->fromCsv($this->catsCsv);
        if ($this->venuesCsv !== '') $this->selectedVenues = $this->fromCsv($this->venuesCsv);
    }

    public function updatedSelectedOrganizations(): void { $this->syncCsv('orgs'); $this->maybeApply(); }
    public function updatedSelectedCategories(): void { $this->syncCsv('cats'); $this->maybeApply(); }
    public function updatedSelectedVenues(): void { $this->syncCsv('venues'); $this->maybeApply(); }

    public function apply(): void
    {
        $payload = [
            'orgIds' => $this->sanitizeIds($this->selectedOrganizations),
            'categoryIds' => $this->sanitizeIds($this->selectedCategories),
            'venueIds' => $this->sanitizeIds($this->selectedVenues),
        ];


// Broadcast to parent(s)
        $this->dispatch('filters.updated', ...$payload);
    }


    public function clear(): void
    {
        $this->selectedOrganizations = $this->selectedCategories = $this->selectedVenues = [];
        $this->syncCsv('orgs');
        $this->syncCsv('cats');
        $this->syncCsv('venues');
        $this->apply();
    }

    private function maybeApply(): void
    {
        if ($this->autoApply) $this->apply();
    }


    private function syncCsv(string $which): void
    {
        switch ($which) {
            case 'orgs': $this->orgsCsv = $this->toCsv($this->selectedOrganizations); break;
            case 'cats': $this->catsCsv = $this->toCsv($this->selectedCategories); break;
            case 'venues': $this->venuesCsv = $this->toCsv($this->selectedVenues); break;
        }
    }


    private function toCsv(array $ids): string
    {
        $ids = $this->sanitizeIds($ids);
        return implode(',', $ids);
    }


    private function fromCsv(string $csv): array
    {
        if (trim($csv) === '') return [];
        $parts = array_filter(array_map('trim', explode(',', $csv)), fn($v) => $v !== '');
        return $this->sanitizeIds($parts);
    }

    private function sanitizeIds(array $ids): array
    {
        $ids = array_map('intval', $ids);
        $ids = array_values(array_unique(array_filter($ids, fn($v) => $v > 0)));
        sort($ids);
        return $ids;
    }


    public function render()
    {
        return view('livewire.filters.multi-filter');
    }
}
