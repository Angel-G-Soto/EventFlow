@props([
  'id' => 'logoutConfirm',
  'title' => 'Confirm logout',
  'message' => 'Are you sure you want to log out?',
  'formId' => null,
  'confirmLabel' => 'Log out',
])

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-hidden="true" wire:ignore.self>
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-exclamation-triangle me-2 text-warning"></i>{{ $title }}
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">{{ $message }}</p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-warning" type="button"
          onclick="(function(){var f=document.getElementById('{{ $formId }}'); if(f){ f.submit(); }})();">
          <i class="bi bi-box-arrow-right me-1"></i>{{ $confirmLabel }}
        </button>
      </div>
    </div>
  </div>
  </div>
