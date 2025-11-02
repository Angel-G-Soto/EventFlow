{{-- resources/views/partials/bs-bridge.blade.php --}}
<script>
  (function () {
    // ---- Modal manager -----------------------------------------------------
    const modalCache = new Map();

    function ensureModalInstance(el) {
      const BS = window.bootstrap;
      if (!BS) return null;
      let inst = BS.Modal.getInstance(el);
      if (!inst) {
        inst = new BS.Modal(el, { backdrop: true, keyboard: true, focus: true });
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

      const BS = window.bootstrap;
      if (!BS) return;
      const t = new BS.Toast(el, { autohide: true, delay });
      t.show();
    }

    // ---- Livewire/DOM event bindings (v3-friendly) ------------------------
    // Generic events you can dispatch from ANY component:
    // $this->dispatch('bs:open', id: 'modalId');
    // $this->dispatch('bs:close', id: 'modalId');
    // $this->dispatch('toast', message: 'Saved', id: 'appToast', delay: 2200, className: 'text-bg-success');

    function bindBridgeEvents() {
      // Listen for bubbling DOM events dispatched by Livewire v3
      document.addEventListener('bs:open', (e) => {
        const { id } = e.detail || {};
        if (id) openModal(id);
      });
      document.addEventListener('bs:close', (e) => {
        const { id } = e.detail || {};
        if (id) closeModal(id);
      });
      document.addEventListener('toast', (e) => {
        const payload = e.detail || {};
        showToast(payload);
      });

      // Fallback compatibility with older Livewire event bus (if present)
      if (window.Livewire && typeof Livewire.on === 'function') {
        Livewire.on('bs:open', (payload = {}) => {
          const { id } = payload || {};
          if (id) openModal(id);
        });
        Livewire.on('bs:close', (payload = {}) => {
          const { id } = payload || {};
          if (id) closeModal(id);
        });
        Livewire.on('toast', (payload = {}) => showToast(payload));
      }

      // Safety net after any patch
      document.addEventListener('livewire:update', cleanupBackdrops);
    }

    // Bind immediately and also after Livewire initializes (idempotent)
    bindBridgeEvents();
    document.addEventListener('livewire:init', bindBridgeEvents, { once: true });
  })();
</script>