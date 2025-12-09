@php
$navbar = app(\App\Services\UserService::class)->getNavbarContext();
@endphp

<nav class="navbar navbar-expand-xl navbar-dark" aria-label="Primary site navigation"
  style="background-color: #24324a; border-bottom: 3px solid var(--bs-success)">
  <div class="container">
    <div class="d-flex align-items-center justify-content-between gap-2 d-xl-none mb-2 w-100">
      <a class="navbar-brand fw-semibold" href="https://eventflow.uprm.edu/">
        <img src="{{ asset('assets/images/UPRM-logo.png') }}" alt="UPRM Logo" height="50" class="me-2" loading="lazy">
        EventFlow</a>

      <div class="d-flex align-items-center gap-2 ms-auto">
        @if($navbar['shouldShowPendingBell'])
        <a href="{{ route('approver.pending.index') }}"
          class="btn btn-success-subtle p-2 text-white position-relative d-inline-flex align-items-center"
          title="Pending approvals" aria-label="View pending approvals ({{ $navbar['pendingApprovalsCount'] }})">
          <i class="bi {{ $navbar['pendingApprovalsCount'] ? 'bi-bell-fill' : 'bi-bell' }}"></i>
          @if($navbar['pendingApprovalsCount'] > 0)
          <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger text-white"
            style="transform: translate(-40%, -45%); padding: 0.22rem 0.42rem; min-width: 1.4rem;">
            {{ $navbar['pendingApprovalsCount'] > 99 ? '99+' : $navbar['pendingApprovalsCount'] }}
          </span>
          @endif
        </a>
        @endif
        <a class="btn btn-success-subtle p-2 text-white d-xl-none d-inline-flex align-items-center"
          href="{{ asset('assets/user guide/Eventflow-Guide-2025.pdf') }}" target="_blank" rel="noopener" title="Help"
          aria-label="Open help guide (PDF)">
          <i class="bi bi-question-lg"></i>
        </a>
        @if(auth()->check())
        <button class="btn p-2 text-white d-inline-flex align-items-center" type="button" title="Log out"
          aria-label="Log out" data-bs-toggle="modal" data-bs-target="#logoutConfirmModal"
          style="border: none; outline: none;">
          <i class="bi bi-box-arrow-right"></i>
        </button>
        @endif
        <button class="navbar-toggler d-flex align-items-center d-xl-none" type="button" data-bs-toggle="collapse"
          data-bs-target="#navMain" aria-controls="navMain" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
      </div>
    </div>

    <a class="navbar-brand fw-semibold d-none d-xl-inline-flex align-items-center" href="https://eventflow.uprm.edu/">
      <img src="{{ asset('assets/images/UPRM-logo.png') }}" alt="UPRM Logo" height="50" class="me-2" loading="lazy">
      EventFlow</a>

    <style>
      /* Accent color */
      :root {
        --app-accent: #3a5178;
      }

      .bg-accent {
        background-color: var(--app-accent) !important;
      }

      /* Smooth transition for dropdown items */
      .dropdown-menu .dropdown-item {
        transition: background-color 0.25s, color 0.25s, font-weight 0.25s;
      }

      .dropdown-menu .dropdown-item:hover,
      .dropdown-menu .dropdown-item:focus {

        font-weight: bold;
        background-color: var(--bs-success-bg-subtle);
      }
    </style>

    <div id="navMain" class="collapse navbar-collapse">
      <ul class="navbar-nav mr-auto">
        @if($navbar['user'])
        <li class="nav-item">
          <a class="nav-link text-nowrap fw-bold {{ Route::is('public.calendar') ? 'active' : '' }}"
            href="{{ route('public.calendar') }}">
            Calendar
          </a>
        </li>
        @endif

        @if($navbar['user'] && ($navbar['showMyRequests'] ?? false))
        <li class="nav-item">
          <a class="nav-link text-nowrap fw-bold {{ Route::is('user.index') ? 'active' : '' }}"
            href="{{ route('user.index') }}">
            My Requests
          </a>
        </li>
        @endif

        @if($navbar['isAdvisor'] || $navbar['isApprover'] || $navbar['isVenueManager'])
        <li class="nav-item dropdown">
          <a class="nav-link text-nowrap fw-bold dropdown-toggle {{ Route::is('approver.history.index') || Route::is('approver.pending.index') ? 'active' : '' }}"
            href="#" id="requestsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Requests
          </a>
          <ul class="dropdown-menu" aria-labelledby="requestsDropdown">
            <li>
              <a class="dropdown-item {{ Route::is('approver.pending.index') ? 'active fw-bold bg-accent' : '' }}"
                href="{{ route('approver.pending.index') }}">Pending Requests</a>
            </li>
            <li>
              <a class="dropdown-item {{ Route::is('approver.history.index') ? 'active fw-bold bg-accent' : '' }}"
                href="{{ route('approver.history.index') }}">Approval History</a>
            </li>
          </ul>
        </li>
        @endif

        @if($navbar['isVenueManager'])
        <li class="nav-item">
          <a class="nav-link text-nowrap fw-bold {{ (Route::is('venues.manage') || Route::is('venue.index')) ? 'active ' : '' }}"
            href="{{ route('venues.manage') }}">
            My Venues
          </a>
        </li>
        @endif

        @if($navbar['isDirector'])
        <li class="nav-item">
          <a class="nav-link text-nowrap fw-bold {{ Route::is('director.venues.index') ? 'active' : '' }}"
            href="{{ route('director.venues.index') }}">
            My Department
          </a>
        </li>
        @endif

        @if($navbar['isApprover'])
        <li class="nav-item">
          <a class="nav-link text-nowrap fw-bold {{ Route::is('dsca.categories') ? 'active' : '' }}"
            href="{{ route('dsca.categories') }}">
            Categories
          </a>
        </li>
        @endif

        @if($navbar['isAdmin'])
        <li class="nav-item dropdown">
          <a class="nav-link text-nowrap fw-bold dropdown-toggle {{ Route::is('admin.users') || Route::is('admin.departments') || Route::is('admin.venues') || Route::is('admin.events') || Route::is('admin.audit') ? 'active' : '' }}"
            href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Administrator
          </a>
          <ul class="dropdown-menu" aria-labelledby="adminDropdown">
            <li><a class="dropdown-item {{ Route::is('admin.users') ? 'active fw-bold bg-accent' : '' }}"
                href="{{ route('admin.users') }}">Users</a></li>
            <li><a class="dropdown-item {{ Route::is('admin.departments') ? 'active fw-bold bg-accent' : '' }}"
                href="{{ route('admin.departments') }}">Departments</a></li>
            <li><a class="dropdown-item {{ Route::is('admin.venues') ? 'active fw-bold bg-accent' : '' }}"
                href="{{ route('admin.venues') }}">Venues</a></li>
            <li><a class="dropdown-item {{ Route::is('admin.events') ? 'active fw-bold bg-accent' : '' }}"
                href="{{ route('admin.events') }}">Events</a></li>
            <li><a class="dropdown-item {{ Route::is('admin.audit') ? 'active fw-bold bg-accent' : '' }}"
                href="{{ route('admin.audit') }}">Audit Log</a></li>
            <li><a class="dropdown-item {{ Route::is('admin.backups') ? 'active fw-bold bg-accent' : '' }}"
                href="{{ route('admin.backups') }}">Backups</a></li>

          </ul>
        </li>
        @endif
      </ul>

      <div class="d-flex w-100 mt-2 mt-lg-0 justify-content-between justify-content-lg-end align-items-center gap-2">
        <div class="d-flex gap-2">
          @if($navbar['shouldShowPendingBell'])
          <a href="{{ route('approver.pending.index') }}"
            class="btn btn-success-subtle p-2 text-white position-relative d-none d-xl-inline-flex align-items-center"
            title="Pending approvals" aria-label="View pending approvals ({{ $navbar['pendingApprovalsCount'] }})">
            <i class="bi {{ $navbar['pendingApprovalsCount'] ? 'bi-bell-fill' : 'bi-bell' }}"></i>
            @if($navbar['pendingApprovalsCount'] > 0)
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger text-white"
              style="transform: translate(-40%, -45%); padding: 0.22rem 0.42rem; min-width: 1.4rem;">
              {{ $navbar['pendingApprovalsCount'] > 99 ? '99+' : $navbar['pendingApprovalsCount'] }}
            </span>
            @endif
          </a>
          @endif
          <a class="btn btn-success-subtle p-2 text-white d-none d-xl-inline-flex align-items-center"
            href="{{ asset('assets/user guide/Eventflow-Guide-2025.pdf') }}" target="_blank" rel="noopener" title="Help"
            aria-label="Open help guide (PDF)">
            <i class="bi bi-question-lg"></i>
          </a>
        </div>
        <div class="ms-auto ms-lg-0 d-none d-xl-flex">
          @if(auth()->check())
          <button class="btn p-2 d-flex align-items-center text-white fw-bold" type="button" title="Log out"
            aria-label="Log out" data-bs-toggle="modal" data-bs-target="#logoutConfirmModal"
            style="border: none; outline: none; transition: all 0.3s ease;">
            <span class="me-2">Log Out</span>
            <i class="bi bi-box-arrow-right"></i>
          </button>
          @else
          <form method="GET" action="{{ route('saml.login') }}" class="m-0" aria-label="Log in form">
            @csrf
            <button class="btn p-2 d-flex align-items-center text-white fw-bold" title="Log in" aria-label="Log in"
              style="border: none; outline: none; transition: all 0.3s ease;">
              <span class="me-2">Log In</span>
              <i class="bi bi-box-arrow-right"></i>
            </button>
          </form>
          @endif
        </div>
      </div>
    </div>
  </div>
</nav>

@if(auth()->check())
<form id="navbarLogoutForm" method="POST" action="{{ route('saml.logout') }}" class="d-none" aria-label="Log out form">
  @csrf
</form>
{{-- Confirm logout modal (shared for mobile/desktop) --}}
<x-confirm-logout id="logoutConfirmModal" title="Confirm logout" message="Are you sure you want to log out?"
  formId="navbarLogoutForm" confirmLabel="Log out" />
@endif
