<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-hidden="true" wire:ignore.self>
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $title ?? 'Cancel event' }}
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">
          {{ $message ?? 'Are you sure you want to cancel this event? This action cannot be undone.' }}
        </p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal" aria-label="Back">
          <i class="bi bi-arrow-left me-1"></i>Back
        </button>
        <button
          class="btn btn-danger"
          type="button"
          wire:click="{{ $confirm ?? 'confirmCancel' }}"
        >
          <i class="bi bi-x-circle me-1"></i>{{ $confirmLabel ?? 'Cancel event' }}
        </button>
      </div>
    </div>
  </div>
</div>

