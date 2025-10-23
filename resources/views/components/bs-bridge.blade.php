{{-- resources/views/partials/bs-bridge.blade.php --}}
<script>
  (function () {
    // ---- Modal manager -----------------------------------------------------
    const modalCache = new Map();

    function ensureModalInstance(el) {
      let inst = bootstrap.Modal.getInstance(el);
      if (!inst) {
        inst = new bootstrap.Modal(el, { backdrop: true, keyboard: true, focus: true });
        modalCache.set(el.id, inst);

        el.addEventListener('hidden.bs.modal', () => {
          // If no modal is open, scrub leftovers
          if (!document.querySelector('.modal.show')) {
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('padding-right');
            document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
          }
        });
      }
      return inst;
    }

    function cleanupBackdrops() {
      if (!document.querySelector('.modal.show')) {
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('padding-right');
        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
      }
    }

    function openModal(id) {
      const el = document.getElementById(id);
      if (!el) return;
      ensureModalInstance(el).show();
    }

    function closeModal(id) {
      const el = document.getElementById(id);
      if (!el) return;

      const inst = bootstrap.Modal.getInstance(el) || modalCache.get(id);
      if (!inst) return;

      // Hide → on hidden, dispose and cleanup
      el.addEventListener('hidden.bs.modal', function onHidden() {
        el.removeEventListener('hidden.bs.modal', onHidden);
        inst.dispose?.();
        modalCache.delete(id);
        cleanupBackdrops();
      }, { once: true });

      inst.hide();
    }

    // ---- Toast manager -----------------------------------------------------
    // Will create a toast on the fly if the id doesn't exist.
    function showToast({ id = 'appToast', message = 'Done', delay = 2200, className = 'text-bg-success' } = {}) {
      let el = document.getElementById(id);
      if (!el) {
        el = document.createElement('div');
        el.id = id;
        el.className = `toast align-items-center ${className}`;
        el.role = 'alert';
        el.ariaLive = 'assertive';
        el.ariaAtomic = 'true';
        el.innerHTML = `
          <div class="d-flex">
            <div class="toast-body" data-toast-body>Done</div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
          </div>
        `;
        Object.assign(el.style, { position: 'fixed', top: '1rem', right: '1rem', zIndex: 1080 });
        document.body.appendChild(el);
      }
      const body = el.querySelector('[data-toast-body]');
      if (body) body.textContent = message;

      const t = new bootstrap.Toast(el, { autohide: true, delay });
      t.show();
    }

    // ---- Livewire bindings (global) ---------------------------------------
    document.addEventListener('livewire:init', () => {
      // Generic events you can dispatch from ANY component:
      // $this->dispatch('bs:open', id: 'modalId');
      // $this->dispatch('bs:close', id: 'modalId');
      // $this->dispatch('toast', message: 'Saved', id: 'appToast', delay: 2200, className: 'text-bg-success');

      Livewire.on('bs:open', ({ id }) => openModal(id));
      Livewire.on('bs:close', ({ id }) => closeModal(id));

      Livewire.on('toast', (payload = {}) => showToast(payload));

      // Safety net after any patch
      document.addEventListener('livewire:update', cleanupBackdrops);
    });
  })();
</script>
