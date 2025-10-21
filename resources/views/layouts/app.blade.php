<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $title ?? 'EventFlow' }}</title>

  @vite(['resources/js/app.js'])
  @livewireStyles
</head>
<body class="bg-body-tertiary">

  <nav class="navbar navbar-expand-lg bg-success navbar-dark">
    <div class="container">
      <a class="navbar-brand fw-semibold" href="{{ url('/') }}">EventFlow</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain"
              aria-controls="navMain" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div id="navMain" class="collapse navbar-collapse">
        <ul class="navbar-nav mx-auto">
          <li class="nav-item">
            <a class="fw-bold nav-link {{ Route::is('admin.users') ? 'active' : '' }}" href="{{ route('admin.users') }}">
              Users
            </a>
          </li>
          <li class="nav-item">
            <a class="fw-bold nav-link {{ Route::is('admin.venues') ? 'active' : '' }}" href="{{ route('admin.venues') }}">
              Venues
            </a>
          </li>
          <li class="nav-item">
            <a class="fw-bold nav-link {{ Route::is('admin.events') ? 'active' : '' }}" href="{{ route('admin.events') }}">
              Events
            </a>
          </li>
          <li class="nav-item">
            <a class="fw-bold nav-link {{ Route::is('admin.overrides') ? 'active' : '' }}" href="{{ route('admin.overrides') }}">
              Overrides
            </a>
          </li>
        </ul>

        <div class="d-flex align-items-center gap-2">
          <button class="btn btn-success-subtle text-white rounded-circle p-2" type="button" title="Notifications">
            <i class="bi bi-bell"></i>
          </button>
          <button class="btn btn-success-subtle text-white rounded-circle p-2" type="button" title="Help">
            <i class="bi bi-question-lg"></i>
          </button>
          <button class="btn btn-outline-light rounded-circle p-2" type="button" title="Profile">
            <i class="bi bi-person"></i>
          </button>
        </div>
      </div>
    </div>
  </nav>

  <main class="container py-4">
    {{ $slot }}
  </main>

  @livewireScripts
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  // Livewire ⇄ Bootstrap modal bridge (global, once)
  document.addEventListener('livewire:init', () => {
    Livewire.on('bs:open', ({ id }) => {
      const el = document.getElementById(id);
      if (el) new bootstrap.Modal(el).show();
    });
    Livewire.on('bs:close', ({ id }) => {
      const el = document.getElementById(id);
      if (!el) return;
      const inst = bootstrap.Modal.getInstance(el);
      if (inst) inst.hide();
    });
    Livewire.on('toast', ({ id = 'appToast', message = 'Done', delay = 2200 }) => {
      const toastEl = document.getElementById(id);
      if (!toastEl) return;
      const body = toastEl.querySelector('.toast-body');
      if (body) body.textContent = message;
      new bootstrap.Toast(toastEl, { delay }).show();
    });
  });
</script>
</body>
</html>
