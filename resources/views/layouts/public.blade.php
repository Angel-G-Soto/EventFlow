<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'EventFlow' }}</title>

    @vite(['resources/js/app.js','resources/scss/app.scss', 'resources/js/fullcalendar.js'])
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
{{--            <ul class="navbar-nav mx-auto">--}}
{{--                <li class="nav-item">--}}
{{--                    <a class="fw-bold nav-link {{ Route::is('admin.users') ? 'active' : '' }}" href="{{ route('admin.users') }}">--}}
{{--                        Users--}}
{{--                    </a>--}}
{{--                </li>--}}
{{--                <li class="nav-item">--}}
{{--                    <a class="fw-bold nav-link {{ Route::is('admin.venues') ? 'active' : '' }}" href="{{ route('admin.venues') }}">--}}
{{--                        Venues--}}
{{--                    </a>--}}
{{--                </li>--}}
{{--                <li class="nav-item">--}}
{{--                    <a class="fw-bold nav-link {{ Route::is('admin.events') ? 'active' : '' }}" href="{{ route('admin.events') }}">--}}
{{--                        Events--}}
{{--                    </a>--}}
{{--                </li>--}}
{{--                <li class="nav-item">--}}
{{--                    <a class="fw-bold nav-link {{ Route::is('admin.overrides') ? 'active' : '' }}" href="{{ route('admin.overrides') }}">--}}
{{--                        Overrides--}}
{{--                    </a>--}}
{{--                </li>--}}
{{--            </ul>--}}

            <div class="d-flex ms-auto">
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
    {{$slot}}
</main>


@livewireScripts
@stack('scripts')
</body>
</html>
