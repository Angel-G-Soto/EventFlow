<?php

namespace App\Livewire\Admin;

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Livewire\Traits\UserFilters;
use App\Livewire\Traits\UserEditState;
use App\Livewire\Traits\HasJustification;
use App\Services\UserService;
use App\Services\DepartmentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Admin users index / management view.
 *
 * Exposes filtering, pagination, and create/edit flows for user accounts,
 * routing all persistence through UserService/DepartmentService so that
 * audit logging and domain rules (roles, department requirement) remain
 * centralized in the service layer rather than Livewire or controllers.
 */
#[Layout('layouts.app')] // loads your Bootstrap layout
class UsersIndex extends Component
{


    // Traits / shared state
    use UserFilters, UserEditState, HasJustification;


    // Sorting
    /** @var string Column used for sorting users. */
    public string $sortField = 'id';

    /** @var string Sort direction for the users list. */
    public string $sortDirection = 'asc';

    // Pagination & filter reactions
    // Keep pagination within bounds when a page number is chosen
    /**
     * Toggle or set the active sort column and direction.
     *
     * @param string $field Column key to sort by.
     *
     * @return void
     */
    public function sortBy(string $field): void
    {
        if ($field === $this->sortField) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->page = 1;
    }

    /**
     * Resets the current page to 1 when the search form is submitted.
     *
     * This is necessary to prevent the pagination from breaking when a new search query is executed.
     *
     * @return void
     */
    public function applySearch(): void
    {
        $this->page = 1;
    }


    // Filters: clear/reset
    /**
     * Clears all user filters and resets pagination.
     *
     * @return void
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->role = '';
        $this->page = 1;
    }

    // Edit/Create workflows
    /**
     * Resets the edit fields to their default values and opens the edit user modal.
     *
     * This function is called when the user clicks the "Add User" button.
     *
     * @return void
     */
    public function openCreate(): void
    {
        $this->authorize('manage-users');

        $this->reset(['editId', 'editName', 'editEmail', 'editDepartment']);
        $this->editRoles = ['user'];
        $this->resetErrorBag();
        $this->resetValidation();
        $this->dispatch('bs:open', id: 'editUserModal');
    }

    /**
     * Opens the edit user modal, populating the edit fields with the user's current data.
     *
     * This function is called when the user clicks the "Edit" button next to a user.
     *
     * @param int $id The user ID to open the edit modal for.
     *
     * @return void
     */
    public function openEdit(int $id): void
    {
        $this->authorize('manage-users');

        $user = app(UserService::class)->getUserRowById($id);
        if (!$user) return;

        $this->editId     = $user['id'];
        $this->editName   = $user['name'];
        $this->editEmail  = $user['email'];
        // Roles are tracked as normalized CODES (slug-lower). Always include 'user'.
        $roles = collect($user['roles'] ?? [])
            ->map(fn($v) => Str::slug(mb_strtolower((string)$v)))
            ->push('user')
            ->unique()
            ->values()
            ->all();
        $this->editRoles = $roles;
        $this->editDepartment = $user['department'] ?? '';

        $this->resetErrorBag();
        $this->resetValidation();

        $this->dispatch('bs:open', id: 'editUserModal');
    }

    // Persist edits / session writes
    /**
     * Saves the user data after validation.
     *
     * First, it checks if the email address is already taken by another user.
     * If the email is taken, it adds an error to the editEmail field and returns.
     * If the email is not taken, it validates the form data and then checks if the user is being created (i.e. the editId is null).
     * If the user is being created, it confirms the save action and then jumps to the last page after creation.
     * If the user is being edited, it opens the justification modal for the user to enter a justification for the edit.
     *
     * @return void
     */
    public function save(): void
    {
        $this->authorize('manage-users');

        $this->validate();
        $this->actionType = 'save';

        // Skip justification for new users
        if (!$this->editId) {
            $this->confirmSave();
            return;
        }

        $this->dispatch('bs:open', id: 'userJustify');
    }

    /**
     * Confirms the save action and updates the session with the new/edited user data.
     * If the user is being edited, it validates the justification length and then updates the edited_users session.
     * If the user is being created, it updates the new_users session.
     * Finally, it dispatches events to close the justification modal, edit user modal, and show a toast message with a success message.
     *
     * @return void
     */
    public function confirmSave(): void
    {
        $this->authorize('manage-users');

        $this->validate();

        $svc = app(UserService::class);
        if ($this->editId) {
            try {
                // For edits, require a non-empty justification
                $this->validateJustificationField(true);

                $user = $svc->findUserById((int)$this->editId);
                [$first, $last] = $this->splitName($this->editName);
                $svc->updateUserProfile($user, [
                    'first_name' => $first,
                    'last_name'  => $last,
                    'email'      => $this->editEmail,
                ], Auth::user(), (string) $this->justification);
                $svc->updateUserRoles($user, $this->editRoles, Auth::user(), (string) $this->justification);
                // Department resolution
                $deptId = $this->resolveDepartmentIdFromName($this->editDepartment);
                if ($deptId && $this->roleRequiresDepartment($this->editRoles)) {
                    // Use UserService to assign department so audit logs include justification
                    $svc->assignUserToDepartment($user, (int) $deptId, Auth::user(), (string) $this->justification);
                }
                $this->toast('User updated');
            } catch (\Throwable $e) {
                $this->addError('editEmail', 'Unable to update user.');
                return;
            }
        } else {
            try {
                [$first, $last] = $this->splitName($this->editName);
                $user = $svc->createUser([
                    'first_name' => $first,
                    'last_name'  => $last,
                    'email'      => $this->editEmail,
                    'auth_type'  => 'saml',
                    'password'   => bcrypt(str()->random(16)),
                ], Auth::user());
                $svc->updateUserRoles($user, $this->editRoles, Auth::user(), (string) $this->justification);
                $deptId = $this->resolveDepartmentIdFromName($this->editDepartment);
                if ($deptId && $this->roleRequiresDepartment($this->editRoles)) {
                    $svc->assignUserToDepartment($user, (int) $deptId, Auth::user(), (string) $this->justification);
                }
                $this->toast('User created');
            } catch (\Throwable $e) {
                $this->addError('editEmail', 'Unable to create user.');
                return;
            }
        }

        // Assign department via DepartmentService if required and provided
        // Department assignment handled above; no direct model fallback here.

        $this->dispatch('bs:close', id: 'userJustify');
        $this->dispatch('bs:close', id: 'editUserModal');
        $this->reset(['editId', 'justification', 'editRoles', 'editDepartment']);
    }


    // Delete workflows
    /**
     * Opens the justification modal for the user with the given ID.
     * This function should be called when the user wants to delete a user.
     * It sets the currently edited user ID and sets actionType to 'delete', then opens the justification modal.
     * @param int $id The ID of the user to delete
     *
     * @return void
     */
    public function clearRoles(int $id): void
    {
        $this->authorize('manage-users');

        $this->editId = $id;
        $this->actionType = 'clear-roles';
        $this->dispatch('bs:open', id: 'userConfirm');
    }

    /**
     * Proceeds from the delete confirmation to the justification modal.
     *
     * @return void
     */
    public function proceedClearRoles(): void
    {
        $this->authorize('manage-users');

        $this->dispatch('bs:close', id: 'userConfirm');
        $this->dispatch('bs:open', id: 'userJustify');
    }

    /**
     * Confirms the deletion of a user.
     *
     * This function will validate the justification entered by the user, and then delete the user with the given ID.
     * After deletion, it clamps the current page to prevent the page from becoming out of bounds.
     * Finally, it shows a toast message indicating the user was deleted.
     *
     * @return void
     */
    public function confirmClearRoles(): void
    {
        $this->authorize('manage-users');

        // Require a non-empty justification for clearing roles
        $this->validateJustificationField(true);

        if ($this->editId) {
            try {
                $user = app(UserService::class)->findUserById((int)$this->editId);
                if ($user) {
                    // Clear roles instead of deleting the user record
                    app(UserService::class)->updateUserRoles($user, [], Auth::user(), (string) $this->justification);
                }
            } catch (\Throwable $e) {
                $this->addError('justification', 'Unable to clear user roles.');
                return;
            }
        }

        $this->dispatch('bs:close', id: 'userJustify');
        $this->toast('User roles cleared');
        $this->reset(['editId', 'justification']);
    }

    /**
     * Unified justification submit handler to route to save/delete.
     *
     * @return void
     */
    public function confirmJustify(): void
    {
        $this->authorize('manage-users');

        if (($this->actionType ?? '') === 'clear-roles') {
            $this->confirmClearRoles();
        } else {
            $this->confirmSave();
        }
    }

    // Private/Protected Helper Methods
    /**
     * Validation rules for the user edit form.
     *
     * Rules intentionally encode domain constraints such as:
     * - institutional email domains (upr/uprm),
     * - required presence of at least one role,
     * - preventing removal of the last remaining admin user, and
     * - requiring a valid department when the role implies departmental ownership.
     *
     * @return array<string,mixed>
     */
    protected function rules(): array
    {
        // Department should only be provided when the user has the "Department Director" role
        $deptRequired = $this->roleRequiresDepartment($this->editRoles);
        // Allowed role codes derived from DB role NAMEs (normalized to slug-lower)
        $allowedRoleCodes = app(UserService::class)->getAllRoles()
            ->pluck('name')
            ->map(fn($c) => Str::slug(mb_strtolower((string)$c)))
            ->unique()
            ->values()
            ->all();
        if (!in_array('user', $allowedRoleCodes, true)) {
            $allowedRoleCodes[] = 'user';
        }
        // Allowed departments from DB
        $allowedDepartments = app(DepartmentService::class)->getAllDepartments()->pluck('name')->all();

        return [
            'editName'       => [
                'required', 'string', 'min:5', 'max:255', 'regex:/^[A-Za-z\s\'\.-]+$/', 'not_regex:/^\s*$/',
            ],
            'editEmail'      => [
                'required',
                'email',
                // Allow dots and numbers (and common email characters) before the @, restricted to upr/uprm.edu domains
                'regex:/^[a-z0-9.]+@(uprm|upr)\.edu$/i',
                'not_regex:/^\s*$/',
                'unique:users,email,' . ($this->editId ?? 'NULL') . ',id',
            ],
            'editRoles'      => [ 'required', 'array', 'min:1',                
            function ($attribute, $value, $fail) {
                    // Prevent removal of the only admin account
                    $targetId = $this->editId ?? null;
                    $adminKept = in_array('admin', $value, true);
                    if ($targetId && !$adminKept && app(UserService::class)->isLastAdmin($targetId)) {
                        $fail('You cannot remove the last admin user.');
                    }
                }],
            'editRoles.*'    => ['string', 'in:' . implode(',', $allowedRoleCodes)], // validate by ROLE CODE
            'editDepartment' => $deptRequired ? ['required', 'string', 'in:' . implode(',', $allowedDepartments)] : ['nullable', 'string'],
            // Justification is validated separately when required (edits / clear-roles)
            'justification'  => $this->justificationRules(false),
        ];
    }

    // Removed legacy in-memory ID generator; DB auto-increment IDs are used

    /**
     * Determine whether the current role set requires a department assignment.
     *
     * Only "department-director" and "venue-manager" are allowed to (and must)
     * be tied to a department; all other role combinations are treated as
     * department-agnostic from the admin UI's perspective.
     *
     * @param array<int,string> $roles The set of role codes to evaluate.
     *
     * @return bool True when the role set requires a department.
     */
    protected function roleRequiresDepartment(array $roles): bool
    {
        // Roles are provided as codes (e.g., 'department-director', 'venue-manager')
        $codes = collect($roles)->map(fn($v) => (string)$v)->all();
        return in_array('department-director', $codes, true) || in_array('venue-manager', $codes, true);
    }

    /**
     * Fire a UI toast event for the front-end to display a message.
     *
     * @param string $message Message to send to the UI toast handler.
     *
     * @return void
     */
    protected function toast(string $message): void
    {
        // Normalized toast event name across admin views
        $this->dispatch('toast', message: $message);
    }

    /**
     * Split a full name string into first and last name components.
     *
     * @return array{0:string,1:string}
     */
    protected function splitName(string $fullName): array
    {
        $fullName = trim($fullName);
        if ($fullName === '') {
            return ['', ''];
        }

        // Collapse extra spaces and split into first + remainder (as last)
        $parts = preg_split('/\s+/', $fullName, 2, PREG_SPLIT_NO_EMPTY) ?: [];
        $first = $parts[0] ?? '';
        $last = $parts[1] ?? '';

        return [$first, $last];
    }


    /**
     * Resolve department id from the selected department name.
     * Treat empty or placeholder values (e.g., '—') as null. If roles exempt department, also return null.
     *
     * @param string|null $name Department name from the UI.
     *
     * @return int|null Matching department id or null when none found/required.
     */
    protected function resolveDepartmentIdFromName(?string $name): ?int
    {
        // Only set department when the role requires it (Department Director)
        if (!$this->roleRequiresDepartment($this->editRoles)) {
            return null;
        }
        $name = trim((string)$name);
        if ($name === '') {
            return null;
        }
        // Ensure the department exists; create it on-the-fly if missing so it appears in the table
        try {
            $dept = app(DepartmentService::class)->findByName($name);
            return (int)$dept->id;
        } catch (\Throwable $e) {
            return null;
        }
    }


    // Render
    /**
     * Renders the Livewire view for the users index page.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        $this->authorize('manage-users');

        $paginator = $this->usersPaginator();

        // Load departments via service
        try {
            $departments = app(DepartmentService::class)->getAllDepartments()->sortBy('name')->values();
        } catch (\Throwable $e) {
            $departments = collect();
        }

        // Fetch all roles (code + name) from the database via UserService (not UserConstants)
        $svc = app(UserService::class);
        try {
            // Provide code (value) and name (label) where code is the role NAME slug (DB 'name')
            $allRoles = $svc->getAllRoles()
                ->map(fn($r) => [
                    'code' => Str::slug(mb_strtolower((string)($r->name ?? $r->code ?? ''))),
                    'name' => (string)($r->name ?? ''),
                ])
                ->unique('code')
                ->sortBy('name')
                ->values();
        } catch (\Throwable $e) {
            $allRoles = [];
        }

        return view('livewire.admin.users-index', [
            'rows'        => $paginator,
            'visibleIds'  => $paginator->pluck('id')->all(),
            'departments' => $departments,
            'allRoles'    => $allRoles, // codes used as values; labels prettified in view
        ]);
    }

    /**
     * Keep pagination controls working with the shared pagination partial.
     *
     * @param int $target Desired page number.
     *
     * @return void
     */
    public function goToPage(int $target): void
    {
        $this->page = max(1, $target);
    }

    /**
     * Build a paginator for users using the service layer and current filters.
     *
     * @return LengthAwarePaginator
     */
    protected function usersPaginator(): LengthAwarePaginator
    {
        $svc = app(UserService::class);
        $sort = $this->sortField !== '' ? ['field' => $this->sortField, 'direction' => $this->sortDirection] : null;
        $paginator = $svc->paginateUserRows(
            [
                'search' => $this->search,
                'role' => $this->role,
            ],
            $this->pageSize,
            $this->page,
            $sort
        );

        $last = max(1, (int)$paginator->lastPage());
        if ($this->page > $last) {
            $this->page = $last;
            if ((int)$paginator->currentPage() !== $last) {
                $paginator = $svc->paginateUserRows(
                    [
                        'search' => $this->search,
                        'role' => $this->role,
                    ],
                    $this->pageSize,
                    $this->page,
                    $sort
                );
            }
        }

        return $paginator;
    }
}
