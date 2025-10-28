<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-hidden="true" wire:ignore.self>
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" wire:submit.prevent="{{ $submit ?? 'confirm' }}">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-clipboard-check me-2"></i>Justification
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        @if(isset($showDeleteType) && $showDeleteType)
        <div class="mb-3">
          <label class="form-label">Delete Type</label>
          <div class="form-check">
            <input class="form-check-input" type="radio" value="soft" wire:model.live="deleteType" id="soft">
            <label class="form-check-label" for="soft">
              <strong>Soft Delete</strong> - User can be restored later
            </label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" value="hard" wire:model.live="deleteType" id="hard">
            <label class="form-check-label" for="hard">
              <strong>Hard Delete</strong> - Permanently remove user
            </label>
          </div>
        </div>
        @endif
        <div class="mb-3">
          <label class="form-label">Reason</label>
          <textarea class="form-control" rows="4" required wire:model.live="{{ $model ?? 'justification' }}"
            placeholder="Enter your justification here..."></textarea>
          @error($model ?? 'justification')
          <small class="text-danger">{{ $message }}</small>
          @enderror
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" type="submit">
          <i class="bi bi-check2 me-1"></i>Confirm
        </button>
      </div>
    </form>
  </div>
</div>