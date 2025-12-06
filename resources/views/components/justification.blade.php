@props([
  'id',
  'submit' => 'confirm',
  'model' => 'justification',
  'cancelLabel' => 'Back',
])

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-hidden="true" wire:ignore.self
  x-data="{ justification: @entangle($model) }">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" wire:submit.prevent="{{ $submit }}">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-clipboard-check me-2"></i>Justification
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label required">Reason</label>
          <textarea class="form-control" rows="4" required wire:model.live="{{ $model }}" x-model="justification"
            placeholder="Type at least 10 characters..."></textarea>
          @error($model)
          <small class="text-danger">{{ $message }}</small>
          @enderror
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">{{ $cancelLabel }}</button>
        <button class="btn btn-primary" type="submit"
          :disabled="(() => { const len = (justification || '').trim().length; return len < 10 || len > 200; })()"
          wire:loading.attr="disabled">
          <i class="bi me-1"></i>Confirm
        </button>
      </div>
    </form>
  </div>
</div>
