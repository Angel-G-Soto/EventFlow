<!doctype html>
<html lang="en" data-bs-theme="light">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $title ?? 'EventFlow' }}</title>

  @vite(['resources/js/app.js','resources/scss/app.scss'])
  @livewireStyles
</head>

<body class="bg-body-tertiary">

  <nav class="navbar navbar-expand-lg navbar-light" style="border-bottom: 3px solid var(--bs-success)">
    <div class="container">
      <a class="navbar-brand fw-semibold" href="{{ url('/') }}">EventFlow</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain"
        aria-controls="navMain" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div id="navMain" class="collapse navbar-collapse">
        <ul class="navbar-nav mr-auto">
          <li class="nav-item">
            <a class="fw-bold  nav-link {{ Route::is('admin.users') ? 'active' : '' }}"
              href="{{ route('admin.users') }}">
              Users
            </a>
          </li>
          <li class="nav-item">
            <a class="fw-bold nav-link {{ Route::is('admin.departments') ? 'active' : '' }}"
              href="{{ route('admin.departments') }}">
              Departments
            </a>
          </li>
          <li class="nav-item">
            <a class="fw-bold nav-link {{ Route::is('admin.venues') ? 'active' : '' }}"
              href="{{ route('admin.venues') }}">
              Venues
            </a>
          </li>
          <li class="nav-item">
            <a class="fw-bold nav-link {{ Route::is('admin.events') ? 'active' : '' }}"
              href="{{ route('admin.events') }}">
              Events
            </a>
          </li>
          <li class="nav-item">
            <a class="fw-bold nav-link {{ Route::is('admin.audit') ? 'active' : '' }}"
              href="{{ route('admin.audit') }}">
              Audit Log
            </a>
          </li>
        </ul>

        <div class="d-flex ms-auto gap-2">
          <button class="btn btn-success-subtle p-2" type="button" title="Notifications"
            aria-label="Open notifications">
            <i class="bi bi-bell"></i>
          </button>
          <button class="btn btn-success-subtle p-2" type="button" title="Help" aria-label="Open help">
            <i class="bi bi-question-lg"></i>
          </button>
          <form method="POST" action="{{ route('logout') }}" class="m-0">
            @csrf
            <button class="btn btn-success-subtle p-2" type="submit" title="Log out" aria-label="Log out">
              <i class="bi bi-box-arrow-right"></i>
            </button>
          </form>
        </div>
      </div>
    </div>
  </nav>

  <main class="container py-4">
    {{ $slot }}
  </main>

  @livewireScripts

  <script>
    // Optional: close the collapse after clicking a nav link (mobile UX)
    document.addEventListener('DOMContentLoaded', function() {
      const navCollapse = document.getElementById('navMain');
      const navToggler = document.querySelector('.navbar-toggler');
      document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
        link.addEventListener('click', () => {
          if (navCollapse.classList.contains('show')) {
            const collapse = bootstrap.Collapse.getOrCreateInstance(navCollapse);
            collapse.hide();
            navToggler?.setAttribute('aria-expanded', 'false');
          }
        });
      });
    });
  </script>
  <x-bs-bridge />
</body>

</html>