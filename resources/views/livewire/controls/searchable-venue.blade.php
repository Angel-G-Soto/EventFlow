<div class="w-100">
  @isset($label)
  <label class="form-label">{{ $label }}</label>
  @endisset

  <div class="dropdown w-100" x-data
    x-on:dropdown\:close.window="$el.querySelector('[data-bs-toggle=dropdown]')?.dispatchEvent(new Event('click'))">
    <button class="btn btn-secondary w-100 text-start dropdown-toggle" type="button" data-bs-toggle="dropdown"
      aria-expanded="false">
      @if($this->selectedName)
      {{ $this->selectedName }}
      @else
      Select venue…
      @endif
    </button>

    <div class="dropdown-menu p-2" style="min-width: 20rem; max-height: 260px; overflow:auto;">
      <div class="d-flex gap-2 mb-2">
        <input type="text" class="form-control form-control-sm" placeholder="Search venue…"
          wire:model.live.debounce.300ms="search">
        <button class="btn btn-sm btn-outline-secondary" type="button" wire:click="clear">Clear</button>
      </div>

      @forelse($this->options as $opt)
      <button type="button" class="dropdown-item d-flex justify-content-between align-items-center"
        wire:click="select({{ $opt->id }})">
        <span>{{ $opt->name }}</span>
        @if($value === $opt->id)
        <i class="bi bi-check2 text-success"></i>
        @endif
      </button>
      @empty
      <div class="dropdown-item text-muted">No matches</div>
      @endforelse
    </div>
  </div>
</div>
