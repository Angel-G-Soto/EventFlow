<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-hidden="true" wire:ignore.self>
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2 text-danger"></i>{{ $title ?? 'Confirm
          deletion' }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">{{ $message ?? 'Are you sure you want to delete this item? This action cannot be undone.' }}</p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-danger" type="button" wire:click="{{ $confirm ?? 'proceedDelete' }}">
          <i class="bi bi-trash3 me-1"></i>Delete
        </button>
      </div>
    </div>
  </div>
</div>