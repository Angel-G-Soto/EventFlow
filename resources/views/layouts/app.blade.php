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
</body>

</html>