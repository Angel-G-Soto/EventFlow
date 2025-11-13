<div class="container py-5" style="max-width:720px">
  <div class="card shadow-sm">
    <div class="card-body p-4">
      <h1 class="h4 mb-3">Choose your role</h1>
      <p class="text-muted">Select which role you want to use for this session.</p>

      <div class="mb-3">
        <select class="form-select" wire:model.live="selected">
          <option value="">— Select a role —</option>
          @foreach($roles as $r)
          <option value="{{ $r }}">{{ $r }}</option>
          @endforeach
        </select>
      </div>

      <button class="btn btn-primary" wire:click="continue" @disabled($selected==='' )>
        Continue
      </button>
    </div>
  </div>
</div>