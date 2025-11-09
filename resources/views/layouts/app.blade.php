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