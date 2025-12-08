{{--    View: Index (Livewire)--}}
{{--    Project: EventFlow (Laravel 12 + Livewire 3 + Bootstrap 5)--}}
{{--    Date: 2025-11-01--}}

<x-slot:pageActions>
    <ul class="navbar-nav mx-auto page-actions-nav">
        <li class="nav-item">
            <a class="fw-bold nav-link {{ request()->routeIs('public.calendar') ? 'active' : '' }}"
               href="{{ route('public.calendar') }}">
                Home
            </a>
        </li>

        <li class="nav-item">
            <a class="fw-bold nav-link {{ request()->routeIs('approver.history.index') ? 'active' : '' }}"
               href="{{ route('approver.history.index') }}">
                Request History
            </a>
        </li>
    </ul>
</x-slot:pageActions>

<div>
    <h1 class="h4 mb-3">My Requests</h1>

    <div class="card shadow-sm mb-3">
        <livewire:request.org.filters/>
    </div>

        <style>
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .status-indicator .status-dot {
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 50%;
            display: inline-block;
            background-color: currentColor;
        }

        .status-indicator--success {
            color: #146c43;
        }

        .status-indicator--danger {
            color: #b02a37;
        }

        .status-indicator--warning {
            color: #856404;
        }

        /* Mobile-specific tweaks (iPhone SE width) */
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

            /* Hide the "middle" table columns on mobile (Org, Date, Status) */
            .requests-table th:nth-child(2),
            .requests-table td:nth-child(2),
            .requests-table th:nth-child(3),
            .requests-table td:nth-child(3),
            .requests-table th:nth-child(4),
            .requests-table td:nth-child(4) {
                display: none;
            }

            /* Make stacked text more readable on small screens */
            .requests-table td:first-child {
                padding-top: 0.5rem;
                padding-bottom: 0.5rem;
            }

            .requests-table .request-title {
                font-size: 0.9rem;
                line-height: 1.25;
            }

            .requests-table .request-meta {
                font-size: 0.75rem;
                line-height: 1.3;
            }

            /* 🔽 Fix: shrink search box placeholder on small screens */
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
            <table class="table table-hover align-middle mb-0 requests-table">
                <thead class="table-light">
                <tr>
                    <th scope="col">Title</th>
                    <th scope="col" class="d-none d-md-table-cell">Organization</th>
                    <th scope="col" class="d-none d-md-table-cell">Date Submitted</th>
                    <th scope="col" class="d-none d-md-table-cell">Status</th>
                    <th scope="col">Actions</th>
                </tr>
                </thead>

                <tbody>
                @forelse ($events as $event)
                    @php
                        $statusVariant = match (true) {
                            in_array($event->status, ['cancelled', 'withdrawn', 'rejected']) => 'danger',
                            in_array($event->status, ['approved', 'completed']) => 'success',
                            default => 'warning',
                        };
                        $submittedAt = \Carbon\Carbon::parse($event->created_at)
                            ->format('D, M j, Y g:i A');
                    @endphp

                    <tr>
                        {{-- Title + stacked mobile info --}}
                        <td class="fw-medium">
                            <div class="fw-medium request-title">
                                {{ $event->title ?? '—' }}
                            </div>

                            {{-- Organization under title, smaller + lighter (mobile only) --}}
                            @if($event->organization_name)
                                <div class="text-muted request-meta d-md-none">
                                    {{ $event->organization_name }}
                                </div>
                            @endif

                            {{-- Date Submitted (mobile only, small text) --}}
                            <div class="text-muted request-meta d-md-none">
                                {{ $submittedAt }}
                            </div>
                            {{-- NOTE: status removed from mobile stack for readability --}}
                        </td>

                        {{-- Desktop / tablet-only columns --}}
                        <td class="fw-medium d-none d-md-table-cell">
                            {{ $event->organization_name ?? '—' }}
                        </td>

                        <td class="fw-medium d-none d-md-table-cell">
                            {{ $submittedAt }}
                        </td>

                        <td class="fw-medium d-none d-md-table-cell">
                            <span class="status-indicator status-indicator--{{ $statusVariant }}">
                                <span class="status-dot" aria-hidden="true"></span>
                                <span>{{ $event->getSimpleStatus() }}</span>
                            </span>
                        </td>

                        <td class="fw-medium">
                            <button type="button"
                                    class="btn btn-secondary btn-sm d-inline-flex align-items-center justify-content-center gap-2 text-nowrap table-action-btn"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    title="View details"
                                    aria-label="View details"
                                    onclick="window.location='{{ route('user.request',['event'=>$event]) }}'">
                                <i class="bi bi-info-circle" aria-hidden="true"></i>
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
        {{ $events->withQueryString()->onEachSide(1)->links() }}
    </div>
</div>
