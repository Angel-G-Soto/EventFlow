@props([
'visibleIds' => [], // array<int>
  'pageKey' => 1, // for wire:key uniqueness
  ])

  <input type="checkbox" class="form-check-input"
    wire:change="selectAllOnPage($event.target.checked, @json($visibleIds))" wire:key="select-all-{{ $pageKey }}"
    aria-label="Select all rows on this page">