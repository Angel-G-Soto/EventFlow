<div>
    {{-- Department Venues --}}
    <div class="container py-4">
        <h1 class="h4 mb-3">Department Venues</h1>
        <h6 class="text-muted">
            {{ $department->name ?? 'Department' }}
        </h6>

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col" class="d-none d-sm-table-cell">Room</th>
                            <th scope="col" class="d-none d-sm-table-cell">Capacity</th>
                            <th scope="col" class="d-none d-sm-table-cell">Test Capacity</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($venues as $v)
                        <tr>
                            <td class="fw-medium">
                                <div>{{ $v['name'] }}</div>
                                {{-- Mobile-only extra info under the name --}}
                                <div class="text-muted small d-sm-none mt-1">
                                    Room: {{ $v['code'] }}<br>
                                    Capacity: {{ $v['capacity'] }}<br>
                                    Test capacity: {{ $v['test_capacity'] }}
                                </div>
                            </td>
                            <td class="fw-medium d-none d-sm-table-cell">{{ $v['code'] }}</td>
                            <td class="fw-medium d-none d-sm-table-cell">{{ $v['capacity'] }}</td>
                            <td class="fw-medium d-none d-sm-table-cell">{{ $v['test_capacity'] }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No venues found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Paginator (same style as Manage Venues) --}}
        <div class="mt-3">
            {{ $venues->links() }}
        </div>
    </div>

    {{-- Department Managers --}}
    <div class="container py-4">
        {{-- Header + Add button to the right --}}
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <h1 class="h4 mb-0">Venue Managers</h1>

            {{-- Desktop: actions to the right of the title --}}
            @php($bulkRemovalDisabled = empty($selectedManagerIds))
            <div class="d-none d-md-flex align-items-center gap-2">
                <span class="text-muted small">{{ count($selectedManagerIds) }} selected</span>
                <button type="button"
                    @class([
                        'btn',
                        'btn-sm',
                        'd-inline-flex',
                        'align-items-center',
                        'gap-2',
                        'btn-danger' => ! $bulkRemovalDisabled,
                        'btn-outline-danger' => $bulkRemovalDisabled,
                    ])
                    wire:click="requestBulkManagerRemoval" aria-label="Remove selected managers"
                    title="Remove selected managers" @disabled($bulkRemovalDisabled)>
                    <i class="bi bi-trash" aria-hidden="true"></i>
                    <span>Remove Selected</span>
                </button>
                <button type="button" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2"
                    wire:click="$dispatch('open-modal', { id: 'emailModal' })" aria-label="Add manager"
                    title="Add manager to department">
                    <i class="bi bi-person-plus" aria-hidden="true"></i>
                    <span>Add Venue Manager</span>
                </button>
            </div>
        </div>

        {{-- Mobile: full-width buttons below title --}}
        <div class="d-flex d-md-none flex-column flex-sm-row gap-2 mb-3">
            <button type="button"
                @class([
                    'btn',
                    'btn-sm',
                    'w-100',
                    'd-inline-flex',
                    'align-items-center',
                    'justify-content-center',
                    'gap-2',
                    'btn-danger' => ! $bulkRemovalDisabled,
                    'btn-outline-danger' => $bulkRemovalDisabled,
                ])
                wire:click="requestBulkManagerRemoval" aria-label="Remove selected managers"
                title="Remove selected managers" @disabled($bulkRemovalDisabled)>
                <i class="bi bi-trash" aria-hidden="true"></i>
                <span>Remove Selected ({{ count($selectedManagerIds) }})</span>
            </button>
            <button type="button"
                class="btn btn-primary btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-2"
                wire:click="$dispatch('open-modal', { id: 'emailModal' })" aria-label="Add manager"
                title="Add manager to department">
                <i class="bi bi-person-plus" aria-hidden="true"></i>
                <span>Add Venue Manager</span>
            </button>
        </div>

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="text-center" style="width: 48px;">
                                <input type="checkbox" class="form-check-input" wire:model.live="selectAllManagers"
                                    aria-label="Select all managers on this page">
                            </th>
                            <th scope="col">Name</th>
                            <th scope="col" class="d-none d-sm-table-cell">Email</th>
                            <th scope="col" class="text-center text-sm-end" style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $employee)
                        <tr wire:key="manager-row-{{ $employee['id'] }}">
                            <td class="text-center" style="width: 48px;">
                                <input type="checkbox" class="form-check-input" value="{{ $employee['id'] }}"
                                    wire:model.live="selectedManagerIds"
                                    aria-label="Select {{ $employee['first_name'].' '.$employee['last_name'] }}">
                            </td>
                            <td class="fw-medium">
                                <div>{{ $employee['first_name'].' '.$employee['last_name'] }}</div>
                                {{-- Mobile-only email under the name --}}
                                <div class="text-muted small d-sm-none mt-1">
                                    {{ $employee['email'] }}
                                </div>
                            </td>
                            <td class="fw-medium d-none d-sm-table-cell">
                                {{ $employee['email'] }}
                            </td>
                            <td class="fw-medium" style="width: 120px;">
                                <div
                                    class="d-flex flex-column flex-sm-row gap-2 align-items-end align-items-sm-center justify-content-sm-end w-100">
                                    <button type="button"
                                        class="btn btn-danger btn-sm d-inline-flex align-items-center justify-content-center gap-2 text-nowrap table-action-btn"
                                        wire:click="requestManagerRemoval({{ $employee['id'] }})"
                                        aria-label="Remove manager" title="Remove manager">
                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                        <span class="d-none d-sm-inline">Remove</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No managers found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Paginator (same style as Manage Venues) --}}
        <div class="mt-3">
            {{ $employees->links() }}
        </div>

        {{-- Add Manager modal --}}
        <div class="modal fade" id="emailModal" tabindex="-1" aria-labelledby="emailModalLabel" aria-hidden="true"
            wire:ignore.self>
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="emailModalLabel">Add New Manager</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <form wire:submit.prevent="addManager" novalidate>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="emailInput" class="form-label">Email address <span
                                        class="text-danger">*</span></label>
                                <input id="emailInput" type="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    placeholder="name@example.com" wire:model.defer="email" onpaste="return false"
                                    oncopy="return false" oncut="return false" required
                                    aria-describedby="emailHelp emailError" />
                                <div id="emailHelp" class="form-text visually-hidden">
                                    Add email for new manager.
                                </div>
                                @error('email')
                                <div id="emailError" class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="emailConfirmation" class="form-label">Confirm email address <span
                                        class="text-danger">*</span></label>
                                <input id="emailConfirmation" type="email"
                                    class="form-control @error('emailConfirmation') is-invalid @enderror"
                                    placeholder="Repeat email address" wire:model.defer="emailConfirmation"
                                    onpaste="return false" oncopy="return false" oncut="return false" required
                                    aria-describedby="emailConfirmationHelp emailConfirmationError" />
                                <div id="emailConfirmationHelp" class="form-text visually-hidden">
                                    Re-enter the email to confirm.
                                </div>
                                @error('emailConfirmation')
                                <div id="emailConfirmationError" class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Back</button>
                            <button type="submit" class="btn btn-primary" wire:click="addManager">
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Confirm reassignment modal --}}
        <div class="modal fade" id="confirmManagerTransferModal" tabindex="-1"
            aria-labelledby="confirmManagerTransferModalLabel" aria-hidden="true" wire:ignore.self
            data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="confirmManagerTransferModalLabel">Confirm reassignment</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                            wire:click="cancelManagerTransfer"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">
                            <strong>{{ $pendingManagerEmail ?: 'This user' }}</strong>
                            currently belongs to
                            <strong>{{ $pendingManagerDepartment ?: 'another department' }}</strong>.
                        </p>
                        <p class="mb-0">
                            Continuing will reassign them to {{ $department->name ?? 'this department' }}
                            and give them manager access here.
                            Do you want to proceed?
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                            wire:click="cancelManagerTransfer">
                            Back
                        </button>
                        <button type="button" class="btn btn-primary" wire:click="confirmManagerTransfer"
                            wire:loading.attr="disabled">
                            Reassign & Add Manager
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <x-justification id="departmentJustificationModal" submit="confirmJustification" model="justification" />
    </div>
</div>

<script>
    window.addEventListener('open-modal', (e) => {
        const id = e.detail?.id || 'actionModal';
        const el = document.getElementById(id);
        if (!el) return;
        bootstrap.Modal.getOrCreateInstance(el).show();
    });

    window.addEventListener('close-modal', (e) => {
        const id = e.detail?.id || 'actionModal';
        const el = document.getElementById(id);
        if (!el) return;
        bootstrap.Modal.getOrCreateInstance(el).hide();
    });
</script>
