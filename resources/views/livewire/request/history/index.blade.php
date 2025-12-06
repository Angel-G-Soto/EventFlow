{{--
    View: Request History Index (Livewire)
    Project: EventFlow (Laravel 12 + Livewire 3 + Bootstrap 5)

    Description:
    - Renders a paginated table of Event requests for the current user/role.
    - Supports multi-filtering by categories, venues, and organizations.
    - Uses Bootstrap 5 for styling and Livewire pagination links.

    Variables:
    @var \Illuminate\Pagination\LengthAwarePaginator<\App\Models\Event> $eventhistories
    @var array{categories:array<int|string>,venues:array<int|string>,orgs:array<int|string>} $filters

    Accessibility notes:
    - Table headings should use <th scope="col">.
    - Pagination controls are generated via $eventhistories->links() which include proper ARIA labels.
    - Action links/buttons must have discernible text for screen readers.
--}}

<x-slot:pageActions>
    <ul class="navbar-nav mx-auto page-actions-nav">
        <li class="nav-item">
            <a class="fw-bold nav-link {{ Route::is('public.calendar') ? 'active' : '' }}"
               href="{{ route('public.calendar') }}">
                Home
            </a>
        </li>
        <li class="nav-item">
            <a class="fw-bold nav-link {{ Route::is('approver.pending.index') ? 'active' : '' }}"
               href="{{ route('approver.pending.index') }}">
                Pending Request
            </a>
        </li>

        <li class="nav-item">
            <a class="fw-bold nav-link {{ Route::is('approver.history.index') ? 'active' : '' }}"
               href="{{ route('approver.history.index') }}">
                Request History
            </a>
        </li>
        <li class="nav-item">
            <a class="fw-bold nav-link {{ Route::is('venues.manage') ? 'active' : '' }}"
               href="{{ route('venues.manage') }}">
                My Venues
            </a>
        </li>
    </ul>
</x-slot:pageActions>

<div>
    <h1 class="h4 mb-3">Approval History</h1>

    <div class="card shadow-sm mb-3">
        <livewire:request.history.filters/>
    </div>

    <style>
        /* Mobile-specific tweaks (iPhone SE-ish width) */
        @media (max-width: 575.98px) {
            /* Stack page actions vertically on phones */
            .page-actions-nav {
                flex-direction: column !important;
                align-items: center;
                gap: 0.25rem;
            }

            .page-actions-nav .nav-link {
                padding-left: 0;
                padding-right: 0;
            }

            /* Hide "middle" table columns (Organization, Action, Date Submitted) on mobile */
            .history-table th:nth-child(2),
            .history-table td:nth-child(2),
            .history-table th:nth-child(3),
            .history-table td:nth-child(3),
            .history-table th:nth-child(4),
            .history-table td:nth-child(4) {
                display: none;
            }

            /* Make stacked text more readable on small screens */
            .history-table td:first-child {
                padding-top: 0.5rem;
                padding-bottom: 0.5rem;
            }

            .history-table .history-title {
                font-size: 0.9rem;
                line-height: 1.25;
            }

            .history-table .history-meta {
                font-size: 0.75rem;
                line-height: 1.3;
            }

            /* Shrink filter/search inputs & placeholder inside cards */
            .card .form-control {
                font-size: 0.85rem;
                padding-top: 0.35rem;
                padding-bottom: 0.35rem;
            }

            .card .form-control::placeholder {
                font-size: 0.8rem;
                line-height: 1.2;
            }
        }
    </style>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 history-table">
                <thead class="table-light">
                <tr>
                    <th scope="col">Title</th>
                    <th scope="col" class="d-none d-md-table-cell">Organization</th>
                    <th scope="col" class="d-none d-md-table-cell">Action</th>
                    <th scope="col" class="d-none d-md-table-cell">Date Approved</th>
                    <th scope="col" class="text-end">Actions</th>
                </tr>
                </thead>

                <tbody>
                @forelse ($eventhistories as $history)
                    @php
                        $event = $history->event;
                        $orgName = $event->organization_name ?? '—';
                        $action  = $history->action ? ucfirst(strtolower($history->action)) : '—';
                        $submittedAt = \Carbon\Carbon::parse($history->updated_at)
                            ->format('D, M j, Y g:i A');
                    @endphp
                    <tr>
                        {{-- Title + stacked mobile info --}}
                        <td class="fw-medium">
                            <div class="fw-medium history-title">
                                {{ $event->title ?? '—' }}
                            </div>

                            {{-- Organization (mobile only, smaller & lighter) --}}
                            @if($orgName && $orgName !== '—')
                                <div class="text-muted history-meta d-md-none">
                                    {{ $orgName }}
                                </div>
                            @endif

                            {{-- Action · Date (mobile only, compact meta line) --}}
                            <div class="text-muted history-meta d-md-none">
                                {{ $action }} · {{ $submittedAt }}
                            </div>
                        </td>

                        {{-- Desktop / tablet-only columns --}}
                        <td class="fw-medium d-none d-md-table-cell">
                            {{ $orgName }}
                        </td>

                        <td class="fw-medium d-none d-md-table-cell">
                            {{ $action }}
                        </td>

                        <td class="fw-medium d-none d-md-table-cell">
                            {{ $submittedAt }}
                        </td>

                        <td class="fw-medium text-end">
                            <button type="button"
                                    class="btn btn-secondary btn-sm d-inline-flex align-items-center justify-content-center gap-2 text-nowrap table-action-btn"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    title="View details"
                                    aria-label="View details"
                                    onclick="window.location='{{ route('approver.history.request',['eventHistory'=>$history]) }}'">
                                <i class="bi bi-info-circle" aria-hidden="true"></i>
                                <span>View details</span>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-secondary py-4">
                            No requests found.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $eventhistories->withQueryString()->onEachSide(1)->links() }}
    </div>
</div>
