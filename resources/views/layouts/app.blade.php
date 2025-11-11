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

  <x-shareable-navbar />

  <main class="container py-4">
    {{ $slot }}
  </main>

  @livewireScripts

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      try {
        const navCollapse = document.getElementById('navMain');
        const navToggler = document.querySelector('.navbar-toggler');
        if (!navCollapse || !navToggler || !window.bootstrap) return;

        // Use Bootstrap's Collapse API to avoid conflicts
        const bsCollapse = bootstrap.Collapse.getOrCreateInstance(navCollapse, { toggle: false });

        // Toggle via API (let Bootstrap handle classes/animation)
        navToggler.addEventListener('click', function () {
          bsCollapse.toggle();
        });

        // Close on non-dropdown nav link clicks
        document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
          link.addEventListener('click', (e) => {
            const el = e.currentTarget;
            const togglesDropdown = el.classList.contains('dropdown-toggle') || el.getAttribute('data-bs-toggle') === 'dropdown';
            if (!togglesDropdown) {
              bsCollapse.hide();
            }
          });
        });

        // Optional: close on scroll
        window.addEventListener('scroll', () => {
          if (navCollapse.classList.contains('show')) {
            bsCollapse.hide();
          }
        });

        // Keep aria-expanded in sync for accessibility
        navCollapse.addEventListener('shown.bs.collapse', () => navToggler.setAttribute('aria-expanded', 'true'));
        navCollapse.addEventListener('hidden.bs.collapse', () => navToggler.setAttribute('aria-expanded', 'false'));
      } catch (_) { /* noop */ }
    });
  </script>
  <x-bs-bridge />
</body>

</html>