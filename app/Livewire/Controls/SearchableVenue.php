<?php

namespace App\Livewire\Controls;

use App\Models\Venue;
use Livewire\Attributes\Modelable;
use Livewire\Component;

class SearchableVenue extends Component
{
  #[Modelable]                 // <-- lets parent bind: <livewire:... wire:model="venue" />
  public ?int $value = null;   // selected venue id

  public string $label = 'Venue';
  public string $search = '';
  public int $limit = 50;      // max options shown in the menu

  // Show selected venue name in the button
  public function getSelectedNameProperty(): string
  {
    if (!$this->value) return '';
    return (string) (Venue::query()->whereKey($this->value)->value('name') ?? '');
  }

  // Options list filtered by search
  public function getOptionsProperty()
  {
    return Venue::query()
      ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
      ->orderBy('name')
      ->limit($this->limit)
      ->get(['id', 'name']);
  }

  public function select(int $id): void
  {
    $this->value = $id; // parent updated via #[Modelable]
    $this->dispatch('dropdown:close');
  }

  public function clear(): void
  {
    $this->value = null;
    $this->search = '';
    $this->dispatch('dropdown:close');
  }

  public function render()
  {
    return view('livewire.controls.searchable-venue');
  }
}
