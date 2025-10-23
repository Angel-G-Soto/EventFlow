<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'EventFlow'))</title>
    @vite(['resources/scss/app.scss','resources/js/app.js','resources/js/fullcalendar.js'])
    @yield('head')
</head>
<body>
@include('partials.nav')

<main class="py-4">
   @yield('content')
</main>
@yield('scripts')

</body>
</html>
