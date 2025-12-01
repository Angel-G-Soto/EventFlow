{{--    View: Index (Livewire)--}}
{{--    Project: EventFlow (Laravel 12 + Livewire 3 + Bootstrap 5)--}}
{{--    Date: 2025-11-01--}}

{{--    Description:--}}
{{--    - Renders a paginated, filterable table/list (e.g., events/requests).--}}
{{--    - Integrates with Livewire for reactive filtering and pagination links.--}}

{{--    Variables (typical):--}}
{{--    @var \Illuminate\Pagination\LengthAwarePaginator<\App\Models\Event> $items--}}
{{--    @var array{categories:array<int|string>,venues:array<int|string>,orgs:array<int|string>} $filters--}}

{{--    Accessibility notes:--}}
{{--    - Use <th scope="col"> for headers; give rows <th scope="row"> if first cell is a label.--}}
{{--    - Ensure interactive elements have discernible text; use aria-labels as needed.--}}
{{--    - Pagination via $items->links() includes ARIA attributes; place it within <nav>.--}}

<x-slot:pageActions>
    <ul class="navbar-nav mx-auto page-actions-nav">
        <li class="nav-item">
            <a class="fw-bold nav-link {{ request()->routeIs('public.calendar') ? 'active' : '' }}"
               href="{{ route('public.calendar') }}">
                Home
            </a>
        </li>
        <li class="nav-item">
            <a class="fw-bold nav-link {{ request()->routeIs('approver.pending.index') ? 'active' : '' }}"
               href="{{ route('approver.pending.index') }}">
                Pending Request
            </a>
        </li>

        <li class="nav-item">
            <a class="fw-bold nav-link {{ request()->routeIs('approver.history.index') ? 'active' : '' }}"
               href="{{ route('approver.history.index') }}">
                Request History
            </a>
        </li>

        {{--        <li class="nav-item">--}}
        {{--            <a class="fw-bold nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">My Venues</a>--}}
        {{--        </li>--}}
    </ul>
</x-slot:pageActions>

<div>
    <h1 class="h4 mb-3">Pending Requests</h1>

    <div wire:ignore class="card shadow-sm mb-3 pb-1">
        <livewire:request.pending.filters/>
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

            /* Only Title + Actions as columns on mobile; org/date/status are stacked */
            .pending-table th:nth-child(2),
            .pending-table td:nth-child(2),
            .pending-table th:nth-child(3),
            .pending-table td:nth-child(3),
            .pending-table th:nth-child(4),
            .pending-table td:nth-child(4) {
                display: none;
            }

            .pending-table td:first-child {
                padding-top: 0.5rem;
                padding-bottom: 0.5rem;
            }

            .pending-table .pending-title {
                font-size: 0.9rem;
                line-height: 1.25;
            }

            .pending-table .pending-meta,
            .pending-table .pending-status-mobile {
                font-size: 0.75rem;
                line-height: 1.3;
            }

            .pending-table .pending-status-mobile {
                margin-top: 0.1rem;
                font-weight: 600;
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
            <table class="table table-hover align-middle mb-0 pending-table">
                <thead class="table-light">
                <tr>
                    <th scope="col">Title</th>
                    <th scope="col" class="d-none d-md-table-cell">Organization</th>
                    <th scope="col" class="d-none d-md-table-cell">Date Submitted</th>
                    <th scope="col" class="d-none d-md-table-cell">Status</th>
                    <th scope="col" class="text-end text-nowrap" style="width: 170px;">Actions</th>
                </tr>
                </thead>

                <tbody>
                @forelse ($events as $event)
                    @php
                        $submittedAt = \Carbon\Carbon::parse($event->created_at)
                            ->format('D, M j, Y g:i A');
                        $statusVariant = match (true) {
                            in_array($event->status, ['cancelled', 'withdrawn', 'rejected']) => 'danger',
                            in_array($event->status, ['approved', 'completed']) => 'success',
                            default => 'warning',
                        };
                        $simpleStatus = $event->getSimpleStatus();

                        // Mobile label: convert "Awaiting DSCA Approval" -> "Approval Type: DSCA"
                        if (
                            \Illuminate\Support\Str::startsWith($simpleStatus, 'Awaiting ')
                            && \Illuminate\Support\Str::endsWith($simpleStatus, ' Approval')
                        ) {
                            $role = \Illuminate\Support\Str::between($simpleStatus, 'Awaiting ', ' Approval');
                            $approvalLabel = 'Approval Type: ' . $role;
                        } else {
                            $approvalLabel = 'Status: ' . $simpleStatus;
                        }
                    @endphp
                    <tr>
                        {{-- Title + stacked mobile info --}}
                        <td class="fw-medium">
                            <div class="fw-medium pending-title">
                                {{ $event->title ?? '—' }}
                            </div>

                            {{-- Organization (mobile only) --}}
                            @if($event->organization_name)
                                <div class="text-muted pending-meta d-md-none">
                                    {{ $event->organization_name }}
                                </div>
                            @endif

                            {{-- Date Submitted (mobile only) --}}
                            <div class="text-muted pending-meta d-md-none">
                                {{ $submittedAt }}
                            </div>

                            {{-- Status/Approval type (mobile only, plain text, below others) --}}
                            <div class="pending-status-mobile d-md-none">
                                {{ $approvalLabel }}
                            </div>
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
                                <span>{{ $simpleStatus }}</span>
                            </span>
                        </td>

                        <td class="fw-medium text-end">
                            <button type="button"
                                    class="btn btn-secondary btn-sm d-inline-flex align-items-center justify-content-center gap-2 text-nowrap table-action-btn"
                                    data-bs-toggle="tooltip" data-bs-placement="top" title="View details"
                                    aria-label="View details"
                                    onclick="window.location='{{ route('approver.pending.request',['event'=>$event]) }}'">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                                <span class="d-none d-sm-inline">View details</span>
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
