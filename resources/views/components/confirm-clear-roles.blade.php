<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-hidden="true" wire:ignore.self>
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-exclamation-triangle me-2 text-warning"></i>{{ $title ?? 'Clear user roles' }}
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">{{ $message ?? 'Are you sure you want to remove all roles for this user? The account will remain
          but lose all assigned permissions.' }}</p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-warning" type="button" wire:click="{{ $confirm ?? 'proceedClearRoles' }}">
          <i class="bi bi-person-x me-1"></i>{{ $confirmLabel ?? 'Clear roles' }}
        </button>
      </div>
    </div>
  </div>
</div>