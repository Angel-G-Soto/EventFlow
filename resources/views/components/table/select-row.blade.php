@props([
'rowId', // int
'selected' => [], // array<int,bool>
  'pageKey' => 1, // for wire:key uniqueness
  ])

  <input type="checkbox" class="form-check-input" wire:change="toggleSelect({{ (int)$rowId }}, $event.target.checked)"
    @checked(data_get($selected, (int)$rowId, false)) wire:key="select-{{ (int)$rowId }}-{{ $pageKey }}"
    aria-label="Select row {{ (int)$rowId }}">