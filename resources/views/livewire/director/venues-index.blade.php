
<div>
<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="d-flex align-items-center gap-2">
            <h1 class="h4 mb-0">Department Venues</h1>
            <span class="text-muted small">({{ $department->name ?? 'Department' }})</span>
        </div>

        <div class="d-none d-md-flex gap-2">
            <button type="button" class="btn btn-primary btn-sm"  wire:click="$dispatch('open-modal', { id: 'emailModal' })" aria-label="Add manager" title="Add manager to department">
                <i class="bi bi-plus-circle me-1" aria-hidden="true"></i> Add Venue Manager
            </button>
        </div>
    </div>

  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th scope="col">
                Name
            </th>
{{--            <th>Department</th>--}}
            <th>Room</th>
            <th>Capacity</th>
            <th class="text-end">Test Capacity</th>
          </tr>
        </thead>
        <tbody>
          @forelse($venues as $v)
          <tr>
            <td class="fw-medium">{{ $v['name'] }}</td>
{{--            <td>{{ $v['department'] }}</td>--}}
            <td>{{ $v['code'] }}</td>
            <td>{{ $v['capacity'] }}</td>
            <td class="text-end">{{$v['test_capacity']}}</td>
          </tr>
          @empty
          <tr>
            <td colspan="7" class="text-center text-muted py-4">No venues found.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>



    <div class="card-footer d-flex justify-content-between align-items-center">
      <small class="text-muted">
        {{ method_exists($venues,'total') ? $venues->total() : count($venues) }} results
      </small>
      {{ $venues->onEachSide(1)->links() }}
    </div>
  </div>

</div>

<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h4 mb-0">Department Managers</h1>
    </div>
    <div class="card shadow-sm">
        <div class="table-responsive">


            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th scope="col">
                            Name
                    </th>
                    {{--            <th>Department</th>--}}
                    <th>Email</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                        <tr>
                            <td>{{ $employee['first_name'].' '.$employee['last_name'] }}</td>
                            <td>{{ $employee['email'] }}</td>
                            <td class="text-end">
                                <button
                                    type="button"
                                    class="btn btn-danger btn-sm"
                                    wire:click="openModal({{ $employee }})" aria-label="Remove"
        {{--                            wire:click="removeManager('{{ $row['uuid'] }}')"--}}
                                    title="Remove"
                                >
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No venues found.</td>
                        </tr>
                    @endforelse

                </tbody>

                {{-- Reusable modal (single instance) --}}
                <div wire:ignore.self class="modal fade" id="actionModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    Action for user: @if($selectedEmployee){{ $selectedEmployee->first_name.' '.$selectedEmployee->last_name }}@endif
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                {{-- Put any preview/details here. If you need more info, load it from $selectedId in the component. --}}
                                Are you sure you want to remove
                                <strong>@if($selectedEmployee){{ $selectedEmployee->first_name.' '.$selectedEmployee->last_name}}@else(none selected)@endif</strong>?
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Back</button>
                                <button type="button"
                                        class="btn btn-danger"
                                        wire:click="removeManager"
                                        wire:loading.attr="disabled">
                                    Remove Manager
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </table>
        </div>

        <div class="card-footer d-flex justify-content-between align-items-center">
            <small class="text-muted">
                {{ method_exists($employees,'total') ? $employees->total() : count($employees) }} results
            </small>
            {{ $employees->onEachSide(1)->links() }}
        </div>
    </div>

    <!-- Modal (shield from Livewire diffs) -->
    <!-- Modal -->
    <div class="modal fade" id="emailModal" tabindex="-1" aria-labelledby="emailModalLabel"
         aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="emailModalLabel">Add New Manager</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form wire:submit.prevent="addManager" novalidate>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="emailInput" class="form-label">Email address</label>
                            <input id="emailInput"
                                   type="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   placeholder="name@example.com"
                                   wire:model.defer="email"
                                   required
                                   aria-describedby="emailHelp emailError" />
                            <div id="emailHelp" class="form-text visually-hidden">Add email for new manager.</div>
                            @error('email')
                            <div id="emailError" class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="emailConfirmation" class="form-label">Confirm email address</label>
                            <input id="emailConfirmation"
                                   type="email"
                                   class="form-control @error('emailConfirmation') is-invalid @enderror"
                                   placeholder="Repeat email address"
                                   wire:model.defer="emailConfirmation"
                                   required
                                   aria-describedby="emailConfirmationHelp emailConfirmationError" />
                            <div id="emailConfirmationHelp" class="form-text visually-hidden">Re-enter the email to confirm.</div>
                            @error('emailConfirmation')
                            <div id="emailConfirmationError" class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Back</button>
                        <button type="submit" class="btn btn-primary" wire:click="addManager">Save Email</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirmManagerTransferModal" tabindex="-1" aria-labelledby="confirmManagerTransferModalLabel"
         aria-hidden="true" wire:ignore.self data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="confirmManagerTransferModalLabel">Confirm reassignment</h1>
                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"
                            wire:click="cancelManagerTransfer"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">
                        <strong>{{ $pendingManagerEmail ?: 'This user' }}</strong>
                        currently belongs to
                        <strong>{{ $pendingManagerDepartment ?: 'another department' }}</strong>.
                    </p>
                    <p class="mb-0">
                        Continuing will reassign them to {{ $department->name ?? 'this department' }} and give them manager access here.
                        Do you want to proceed?
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal"
                            wire:click="cancelManagerTransfer">
                        Back
                    </button>
                    <button type="button"
                            class="btn btn-primary"
                            wire:click="confirmManagerTransfer"
                            wire:loading.attr="disabled">
                        Reassign & Add Manager
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

</div>

    <script>
        // Listen to Livewire event to open modal
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
