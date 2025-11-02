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

  <nav class="navbar navbar-expand-lg bg-success navbar-dark">
    <div class="container">
      <a class="navbar-brand fw-semibold" href="{{ url('/') }}">EventFlow</a>
      <button class="navbar-toggler" type="button" aria-controls="navMain" aria-expanded="false"
        aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div id="navMain" class="collapse navbar-collapse">
        {{$pageActions}}

        <div class="d-flex align-items-center gap-2">
          <button class="btn btn-success-subtle text-white p-2" type="button" title="Notifications"
            aria-label="Open notifications">
            <i class="bi bi-bell"></i>
          </button>
          <button class="btn btn-success-subtle text-white p-2" type="button" title="Help" aria-label="Open help">
            <i class="bi bi-question-lg"></i>
          </button>
          <form method="POST" action="{{ route('logout') }}" class="m-0">
            @csrf
            <button class="btn btn-success-subtle text-white p-2" type="submit" title="Log out" aria-label="Log out">
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
    document.addEventListener('DOMContentLoaded', function() {
    const navCollapse = document.getElementById('navMain');
    const navToggler = document.querySelector('.navbar-toggler');

    // Toggle menu when hamburger is clicked
    navToggler.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      const isOpen = navCollapse.classList.contains('show');
      if (isOpen) {
        navCollapse.classList.remove('show');
        navToggler.setAttribute('aria-expanded', 'false');
      } else {
        navCollapse.classList.add('show');
        navToggler.setAttribute('aria-expanded', 'true');
      }
    });

    // Close menu when nav links are clicked
    document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
      link.addEventListener('click', () => {
        navCollapse.classList.remove('show');
        navToggler.setAttribute('aria-expanded', 'false');
      });
    });

    // Close menu when scrolling
    window.addEventListener('scroll', () => {
      if (navCollapse.classList.contains('show')) {
        navCollapse.classList.remove('show');
        navToggler.setAttribute('aria-expanded', 'false');
      }
    });
  });
  </script>
  <x-bs-bridge />
</body>

</html>
