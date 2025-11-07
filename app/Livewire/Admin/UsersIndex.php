<?php

namespace App\Livewire\Admin;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use App\Support\UserConstants;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Livewire\Traits\UserFilters;
use App\Livewire\Traits\UserEditState;
use App\Services\UserService;
use App\Services\DepartmentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

#[Layout('layouts.app')] // loads your Bootstrap layout
class UsersIndex extends Component
{
    // Constants
    public const ROLES = UserConstants::ROLES;

    // Traits / shared state
    use UserFilters, UserEditState;


    // Sorting
    public string $sortField = '';
    public string $sortDirection = 'asc';

    // Accessors and Mutators
    /**
     * Returns true if the current user has a role that does not require a department to be associated with them.
     *
     * This property is used to conditionally render a department select input for users who are being edited.
     *
     * @return bool True if the current user has a role that does not require a department, false otherwise.
     */
    public function getHasRoleWithoutDepartmentProperty(): bool
    {
        return $this->roleExemptsDepartment($this->editRoles);
    }

    // Lifecycle
    // No mount preload required when querying directly from the DB

    // Pagination & filter reactions
    /**
     * Navigates to a given page number.
     *
     * @param int $target The target page number.
     *
     * This function will compute bounds from the current filters, and then
     * set the page number to the maximum of 1 and the minimum of the
     * target and the last page number.
     */
    public function goToPage(int $target): void
    {
        // compute bounds from current filters
        $total = $this->filtered()->count();
        $last  = max(1, (int) ceil($total / max(1, $this->pageSize)));

        $this->page = max(1, min($target, $last));
    }

    /**
     * Resets the current page to 1 when the search filter is updated.
     *
     * This function will be called whenever the search filter is updated,
     * and will reset the current page to 1.
     */
    public function applySearch()
    {
        $this->page = 1;
    }

    /**
     * Resets the current page to 1 when the role filter is updated.
     *
     * This function will be called whenever the role filter is updated,
     * and will reset the current page to 1.
     */
    public function updatedRole()
    {
        $this->page = 1;
    }

    /**
     * Toggle or set the active sort column and direction.
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

    // Filters: clear/reset
    /**
     * Resets the search filter and the current page to 1.
     *
     * This function is called when the user clicks the "Clear" button on the filter form.
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->role = '';
        $this->page = 1;
    }

    protected function splitName(string $full): array
    {
        $parts = preg_split('/\s+/', trim($full), 2);
        return [$parts[0] ?? '', $parts[1] ?? ''];
    }

    // Edit/Create workflows
    /**
     * Resets the edit fields to their default values and opens the edit user modal.
     *
     * This function is called when the user clicks the "Add User" button.
     */
    public function openCreate(): void
    {
        $this->reset(['editId', 'editName', 'editEmail', 'editDepartment']);
        $this->editRoles = [];
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
     */
    public function openEdit(int $id): void
    {
        $user = $this->allUsers()->firstWhere('id', $id);
        if (!$user) return;

        $this->editId     = $user['id'];
        $this->editName   = $user['name'];
        $this->editEmail  = $user['email'];
        // Roles are tracked as role CODES internally (e.g., 'venue-manager')
        $this->editRoles = $user['roles'] ?? [];
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
     */
    public function save(): void
    {
        // Check email uniqueness before validation
        $users = $this->allUsers();
        $existingUser = $users->firstWhere('email', $this->editEmail);
        if ($existingUser && (!$this->editId || $existingUser['id'] !== $this->editId)) {
            $this->addError('editEmail', 'This email is already taken.');
            return;
        }

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
     */
    public function confirmSave(): void
    {
        $this->validate();

        $svc = app(UserService::class);
        if ($this->editId) {
            try {
                $user = $svc->findUserById((int)$this->editId);
                [$first, $last] = $this->splitName($this->editName);
                // Auth disabled: skip audit by passing a null admin or lightweight stub
                $svc->updateUserProfile($user, [
                    'first_name' => $first,
                    'last_name'  => $last,
                    'email'      => $this->editEmail,
                ], $this->fakeAdminUser());
                $svc->updateUserRoles($user, $this->editRoles, $this->fakeAdminUser());
                // Department resolution
                $deptId = $this->resolveDepartmentIdFromName($this->editDepartment);
                if ($deptId && $this->roleRequiresDepartment($this->editRoles)) {
                    $deptSvc = app(DepartmentService::class);
                    $dept = $deptSvc->getDepartmentByID($deptId);
                    if ($dept) {
                        $deptSvc->updateUserDepartment($dept, $user);
                    }
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
                ], $this->fakeAdminUser());
                $svc->updateUserRoles($user, $this->editRoles, $this->fakeAdminUser());
                $deptId = $this->resolveDepartmentIdFromName($this->editDepartment);
                if ($deptId && $this->roleRequiresDepartment($this->editRoles)) {
                    $deptSvc = app(DepartmentService::class);
                    $dept = $deptSvc->getDepartmentByID($deptId);
                    if ($dept) {
                        $deptSvc->updateUserDepartment($dept, $user);
                    }
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
     */
    public function delete(int $id): void
    {
        $this->editId = $id;
        $this->actionType = 'delete';
        $this->dispatch('bs:open', id: 'userConfirm');
    }

    /**
     * Proceeds from the delete confirmation to the justification modal.
     */
    public function proceedDelete(): void
    {
        $this->dispatch('bs:close', id: 'userConfirm');
        $this->dispatch('bs:open', id: 'userJustify');
    }

    /**
     * Confirms the deletion of a user.
     *
     * This function will validate the justification entered by the user, and then delete the user with the given ID.
     * After deletion, it clamps the current page to prevent the page from becoming out of bounds.
     * Finally, it shows a toast message indicating the user was deleted.
     */
    public function confirmDelete(): void
    {
        $this->validateOnly('justification');

        if ($this->editId) {
            try {
                $user = app(UserService::class)->findUserById((int)$this->editId);
                if ($user) {
                    // Delete via service; if no admin available, surface an error
                    // Auth disabled: use fake admin for audit or skip if null
                    $admin = $this->fakeAdminUser();
                    app(UserService::class)->deleteUser($user, $admin);
                }
            } catch (\Throwable $e) {
                $this->addError('justification', 'Unable to delete user.');
                return;
            }
        }

        $this->dispatch('bs:close', id: 'userJustify');
        $this->toast('User deleted');
        $this->reset(['editId', 'justification']);
    }

    /**
     * Unified justification submit handler to route to save/delete.
     */
    public function confirmJustify(): void
    {
        if (($this->actionType ?? '') === 'delete') {
            $this->confirmDelete();
        } else {
            $this->confirmSave();
        }
    }

    // Private/Protected Helper Methods
    /**
     * Returns a collection of all users, both seeded and created by users.
     * This function takes into account soft-deleted users (no hard delete), and will not include them in the collection.
     * It also normalizes the data by ensuring each user has a 'roles' key, and optionally includes 'department_id'.
     *
     * @return Collection An Eloquent Collection of User objects.
     */
    protected function allUsers(): Collection
    {
        // Fetch users via service to avoid direct model queries from the view
        $svc = app(UserService::class);
        $users = collect();

        if (!empty($this->role)) {
            try {
                $users = $svc->getUsersWithRole($this->role);
            } catch (\Throwable $e) {
                $users = collect();
            }
        } else {
            // Iterate over ROLE CODES, not display names
            foreach ($this->roleCodes() as $code) {
                try {
                    $users = $users->merge($svc->getUsersWithRole($code));
                } catch (\Throwable $e) {
                    // ignore service errors for individual role fetches
                }
            }
            // De-duplicate by primary key (id or user_id depending on schema)
            $users = $users->unique(function ($u) {
                return $u->id ?? $u->user_id ?? spl_object_id($u);
            })->values();
        }

        // Apply search filter in-memory to avoid coupling to schema in service
        $s = mb_strtolower(trim((string)($this->search ?? '')));

        return collect($users)
            ->filter(function ($u) use ($s) {
                $name = trim(trim((string)($u->first_name ?? '')) . ' ' . trim((string)($u->last_name ?? '')));
                $hay = mb_strtolower($name . ' ' . (string)($u->email ?? ''));
                return $s === '' || str_contains($hay, $s);
            })
            ->map(fn($u) => $this->mapUserToRow($u))
            ->values();
    }

    /**
     * Normalize a User model into the row shape used by the UI.
     * @param object $u
     * @return array{id:int,name:string,email:string,department:string,roles:array}
     */
    protected function mapUserToRow($u): array
    {
        $name = trim(trim((string)($u->first_name ?? '')) . ' ' . trim((string)($u->last_name ?? '')));
        // Ensure unique role codes for display
        $roles = method_exists($u, 'roles')
            ? $u->roles
                ->map(fn($r) => $r->code ?? \Illuminate\Support\Str::slug((string)$r->name))
                ->filter()
                ->unique()
                ->values()
                ->all()
            : [];

        return [
            'id' => (int)($u->id ?? $u->user_id),
            'name' => $name,
            'email' => (string)($u->email ?? ''),
            'department' => (string)optional($u->department)->name ?? '—',
            // Track roles internally as unique CODES; if code missing, fall back to slug(name)
            'roles' => $roles,
        ];
    }

    /**
     * Returns an array of validation rules for the user edit form.
     */
    protected function rules(): array
    {
        // Department should only be provided when the user has the "Venue Manager" role
        $deptRequired = $this->roleRequiresDepartment($this->editRoles);
        // Allowed role codes derived from constants
        $allowedRoleCodes = $this->roleCodes();

        return [
            'editName'       => 'required|string|max:255|regex:/^[a-zA-Z\s]+$/',
            'editEmail'      => 'required|email|regex:/@upr[a-z]*\.edu$/i',
            'editRoles'      => 'array|min:1',
            'editRoles.*'    => 'string|in:' . implode(',', $allowedRoleCodes), // validate by ROLE CODE
            'editDepartment' => $deptRequired ? 'required|string' : 'nullable|string',
            'justification'  => 'nullable|string|min:10|max:200',
        ];
    }

    /**
     * Validates only the justification length.
     */
    protected function validateJustification(): void
    {
        $this->validate([
            'justification' => ['required', 'string', 'min:10', 'max:200']
        ]);
    }

    // Removed legacy in-memory ID generator; DB auto-increment IDs are used

    /**
     * Returns a filtered collection of users based on the current search query and selected role.
     */
    protected function filtered(): Collection
    {
        $s = mb_strtolower(trim($this->search));
        $selectedRole = $this->role;

        return $this->allUsers()
            ->filter(function ($user) use ($s, $selectedRole) {
                $hit = $s === '' ||
                    str_contains(mb_strtolower($user['name']), $s) ||
                    str_contains(mb_strtolower($user['email']), $s);

                $roles = $user['roles'] ?? [];
                $roleOk = $selectedRole === '' || in_array($selectedRole, $roles, true);
                return $hit && $roleOk;
            })
            ->values();
    }

    /**
     * Paginate the filtered collection of users.
     */
    protected function paginated(): LengthAwarePaginator
    {
        $data = $this->filtered();
        // Apply sorting only after user clicks a sort header
        if ($this->sortField !== '') {
            // Sort using natural, case-insensitive order by the active field
            $options = SORT_NATURAL | SORT_FLAG_CASE;
            $data = $data->sortBy(fn($row) => $row[$this->sortField] ?? '', $options, $this->sortDirection === 'desc')->values();
        }
        $total = $data->count();
        $pageSize = max(1, $this->pageSize); // Prevent division by zero

        $items = $data->slice(($this->page - 1) * $pageSize, $pageSize);

        return new LengthAwarePaginator(
            $items,
            $total,
            $pageSize,
            $this->page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * Determine if any of the given roles do not require a department.
     *
     * @param array<int,string> $roles The set of role names to evaluate.
     */
    protected function roleExemptsDepartment(array $roles): bool
    {
        return count(array_intersect($roles, UserConstants::ROLES_WITHOUT_DEPARTMENT)) > 0;
    }

    /**
     * Determine if any of the given roles explicitly require a department.
     * Only the "Venue Manager" role requires a department.
     *
     * @param array<int,string> $roles
     */
    protected function roleRequiresDepartment(array $roles): bool
    {
        // Only the 'venue-manager' role requires a department
        return in_array('venue-manager', $roles, true);
    }

    /**
     * Fire a UI toast event for the front-end to display a message.
     */
    protected function toast(string $message): void
    {
        // Normalized toast event name across admin views
        $this->dispatch('toast', message: $message);
    }

    /**
     * Provide a minimal fake admin user when auth is disabled.
     * Returns an existing first user or a transient in-memory User model.
     */
    protected function fakeAdminUser(): ?\App\Models\User
    {
        try {
            $u = app(\App\Services\UserService::class)->getFirstUser();
            if ($u) return $u; // reuse real user to keep audit foreign key valid
            // Build an unsaved transient user object for downstream type expectations
            return new \App\Models\User([
                'first_name' => 'System',
                'last_name'  => 'Admin',
                'email'      => 'system@localhost',
            ]);
        } catch (\Throwable $e) {
            return null; // downstream service methods should guard null admin
        }
    }

    /**
     * Compute the list of role CODES from the UserConstants names by slugging with dashes.
     * Example: "Venue Manager" => "venue-manager".
     *
     * @return array<int,string>
     */
    protected function roleCodes(): array
    {
        return array_map(fn(string $n) => Str::slug($n), UserConstants::ROLES);
    }

    /**
     * Resolve department id from the selected department name.
     * Treat empty or placeholder values (e.g., '—') as null. If roles exempt department, also return null.
     */
    protected function resolveDepartmentIdFromName(?string $name): ?int
    {
        // Only set department when the role requires it (Venue Manager)
        if (!$this->roleRequiresDepartment($this->editRoles)) {
            return null;
        }
        $name = trim((string)$name);
        if ($name === '' || $name === '—') {
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
     * @return \Illuminate\Http\Response
     */
    public function render()
    {
        $paginator = $this->paginated();

        // Load departments via service
        try {
            $departments = app(DepartmentService::class)->getAllDepartments()->sortBy('name')->values();
        } catch (\Throwable $e) {
            $departments = collect();
        }

        // Provide roles as CODES for UI value binding; view will prettify labels
        $roleCodes = $this->roleCodes();

        return view('livewire.admin.users-index', [
            'rows'        => $paginator,
            'visibleIds'  => $paginator->pluck('id')->all(),
            'departments' => $departments,
            'allRoles'    => $roleCodes, // codes used as values; labels prettified in view
        ]);
    }
}
