@php
use Illuminate\Support\Facades\Auth;
$user = Auth::user();
$roleNames = $user && method_exists($user, 'getRoleNames') ? $user->getRoleNames()->map(fn($r) =>
Illuminate\Support\Str::slug($r)) : collect();
$isAdmin = $roleNames->contains('system-admin') || $roleNames->contains('system-administrator');
$isAdvisor = $roleNames->contains('advisor');
$isApprover = $roleNames->contains('event-approver');
$isVenueManager = $roleNames->contains('venue-manager');
$isDirector = $roleNames->contains('department-director');
@endphp

<nav class="navbar navbar-expand-lg navbar-dark"
  style="background-color: #24324a; border-bottom: 3px solid var(--bs-success)">
  <div class="container">
    <a class="navbar-brand fw-semibold" href="#">
      <img src="{{ asset('assets/images/UPRM-logo.png') }}" alt="UPRM Logo" height="50" class="me-2" loading="lazy">
      EventFlow</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain"
      aria-controls="navMain" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <style>
      /* Smooth transition for dropdown items */
      .dropdown-menu .dropdown-item {
        transition: background-color 0.25s, color 0.25s, font-weight 0.25s;
      }

      .dropdown-menu .dropdown-item:hover,
      .dropdown-menu .dropdown-item:focus {

        font-weight: bold;
      }
    </style>

    <div id="navMain" class="collapse navbar-collapse">
      <ul class="navbar-nav mr-auto">
        <li class="nav-item">
          <a class="fw-bold nav-link {{ Route::is('public.calendar') ? 'active' : '' }}"
            href="{{ route('public.calendar') }}">
            Calendar
          </a>
        </li>
        @if($user)

        <li class="nav-item">
          <a class="fw-bold nav-link {{ Route::is('user.index') ? 'active' : '' }}" href="{{ route('user.index') }}">
            My Requests
          </a>
        </li>
        @endif

        @if($isAdvisor || $isApprover)
        <li class="nav-item dropdown">
          <a class="fw-bold nav-link dropdown-toggle {{ Route::is('approver.history.index') || Route::is('approver.pending.index') ? 'active' : '' }}"
            href="#" id="requestsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Requests
          </a>
          <ul class="dropdown-menu" aria-labelledby="requestsDropdown">
            <li>
              <a class="dropdown-item {{ Route::is('approver.pending.index') ? 'active fw-bold bg-success' : '' }}"
                href="{{ route('approver.pending.index') }}">Pending Requests</a>
            </li>
            <li>
              <a class="dropdown-item {{ Route::is('approver.history.index') ? 'active fw-bold bg-success' : '' }}"
                href="{{ route('approver.history.index') }}">Approval History</a>
            </li>
          </ul>
        </li>
        @endif

        @if($isVenueManager)
        <li class="nav-item">
          <a class="fw-bold nav-link {{ (Route::is('venues.manage') || Route::is('venue.index')) ? 'active' : '' }}"
            href="{{ route('venues.manage') }}">
            My Venues
          </a>
        </li>
        @endif

        @if($isDirector)
        <li class="nav-item">
          <a class="fw-bold nav-link {{ Route::is('director.venues.index') ? 'active' : '' }}"
            href="{{ route('director.venues.index') }}">
            My Departments
          </a>
        </li>
        @endif

        @if($isAdmin)
        <li class="nav-item dropdown">
          <a class="fw-bold nav-link dropdown-toggle {{ Route::is('admin.users') || Route::is('admin.departments') || Route::is('admin.venues') || Route::is('admin.events') || Route::is('admin.audit') ? 'active' : '' }}"
            href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Administrator
          </a>
          <ul class="dropdown-menu" aria-labelledby="adminDropdown">
            <li><a class="dropdown-item {{ Route::is('admin.users') ? 'active fw-bold bg-success' : '' }}"
                href="{{ route('admin.users') }}">Users</a></li>
            <li><a class="dropdown-item {{ Route::is('admin.departments') ? 'active fw-bold bg-success' : '' }}"
                href="{{ route('admin.departments') }}">Departments</a></li>
            <li><a class="dropdown-item {{ Route::is('admin.venues') ? 'active fw-bold bg-success' : '' }}"
                href="{{ route('admin.venues') }}">Venues</a></li>
            <li><a class="dropdown-item {{ Route::is('admin.events') ? 'active fw-bold bg-success' : '' }}"
                href="{{ route('admin.events') }}">Events</a></li>
            <li><a class="dropdown-item {{ Route::is('admin.audit') ? 'active fw-bold bg-success' : '' }}"
                href="{{ route('admin.audit') }}">Audit Log</a></li>
          </ul>
        </li>
        @endif
      </ul>

      <div class="d-flex ms-auto gap-2">
        <button class="btn btn-success-subtle p-2 text-white" type="button" title="Notifications"
          aria-label="Open notifications">
          <i class="bi bi-bell"></i>
        </button>
        <button class="btn btn-success-subtle p-2 text-white" type="button" title="Help" aria-label="Open help">
          <i class="bi bi-question-lg"></i>
        </button>
        @if(Auth::check())
        <form method="POST" action="{{ route('saml.logout') }}" class="m-0">
          @csrf
          <button class="btn p-2 d-flex align-items-center text-white" type="submit" title="Log out"
            aria-label="Log out" style="border: none; outline: none; transition: all 0.3s ease;">
            <span class="me-2">Log out</span>
            <i class="bi bi-box-arrow-right"></i>
          </button>
        </form>
        @else
        <form method="GET" action="{{ route('saml.login') }}" class="m-0">
          @csrf
          <button class="btn p-2 d-flex align-items-center text-white" title="Log in" aria-label="Log in"
            style="border: none; outline: none; transition: all 0.3s ease;">
            <span class="me-2">Log in</span>
            <i class="bi bi-box-arrow-right"></i>
          </button>
        </form>
        @endif
      </div>
    </div>
  </div>
</nav>