<?php

namespace App\Livewire\Admin;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')] // loads your Bootstrap layout
class UsersIndex extends Component
{
    //Roles in one place (replaced by DB later)
    public const ROLES = [
        'Student Org Rep',
        'Student Org Advisor',
        'Venue Manager',
        'DSCA Staff',
        'Dean of Administration',
        'Admin',
    ];

    public const DEPARTMENTS = [
        'Engineering',
        'Business',
        'Arts & Sciences',
        'Education',
        'Agriculture',
    ];

    public const ROLES_WITHOUT_DEPARTMENT = [
        'Student Org Rep',
        'Student Org Advisor',
        'DSCA Staff',
        'Dean of Administration',
        'Admin',
    ];

    // Filters & paging
    public string $search = '';
    public string $role   = '';
    public int $page      = 1;
    public int $pageSize  = 10;

    // Selection state
    /** @var array<int,bool> userId => true */
    public array $selected = [];

    // Edit modal state
    public ?int $editId = null;
    public string $editName = '';
    public string $editEmail = '';
    public array $editRoles = [];
    public string $editDepartment = '';

    public string $justification = '';
    public bool $isDeleting = false;
    public bool $isBulkDeleting = false;
    public string $deleteType = 'soft'; // 'soft' or 'hard'

    // Persistent data storage
    private static array $users = [
        ['id' => 1, 'name' => 'Jane Doe', 'email' => 'jane@upr.edu', 'role' => 'DSCA Staff'],
        ['id' => 2, 'name' => 'Juan De la Cruz', 'email' => 'juan@upr.edu', 'role' => 'Student Org Rep'],
        ['id' => 3, 'name' => 'Alma Ruiz', 'email' => 'mruiz@upr.edu', 'role' => 'Admin'],
        ['id' => 4, 'name' => 'Leo Ortiz', 'email' => 'leo@upr.edu', 'role' => 'Venue Manager', 'department' => 'Arts & Sciences'],
        ['id' => 5, 'name' => 'Ana Diaz', 'email' => 'adiaz@upr.edu', 'role' => 'Student Org Advisor'],
        ['id' => 6, 'name' => 'Carlos Rivera', 'email' => 'crivera@upr.edu', 'role' => 'Student Org Rep'],
        ['id' => 7, 'name' => 'Sofia Martinez', 'email' => 'smartinez@upr.edu', 'role' => 'Venue Manager', 'department' => 'Education'],
        ['id' => 8, 'name' => 'Miguel Torres', 'email' => 'mtorres@upr.edu', 'role' => 'DSCA Staff'],
        ['id' => 9, 'name' => 'Isabella Garcia', 'email' => 'igarcia@upr.edu', 'role' => 'Student Org Advisor'],
        ['id' => 10, 'name' => 'Diego Morales', 'email' => 'dmorales@upr.edu', 'role' => 'Admin'],
        ['id' => 11, 'name' => 'Valentina Cruz', 'email' => 'vcruz@upr.edu', 'role' => 'Student Org Rep'],
        ['id' => 12, 'name' => 'Alejandro Vega', 'email' => 'avega@upr.edu', 'role' => 'Venue Manager', 'department' => 'Business'],
        ['id' => 13, 'name' => 'Camila Herrera', 'email' => 'cherrera@upr.edu', 'role' => 'DSCA Staff'],
        ['id' => 14, 'name' => 'Sebastian Luna', 'email' => 'sluna@upr.edu', 'role' => 'Student Org Advisor'],
        ['id' => 15, 'name' => 'Lucia Mendez', 'email' => 'lmendez@upr.edu', 'role' => 'Dean of Administration'],
        ['id' => 16, 'name' => 'Mateo Jimenez', 'email' => 'mjimenez@upr.edu', 'role' => 'Student Org Rep'],
        ['id' => 17, 'name' => 'Gabriela Santos', 'email' => 'gsantos@upr.edu', 'role' => 'Venue Manager', 'department' => 'Agriculture'],
        ['id' => 18, 'name' => 'Nicolas Flores', 'email' => 'nflores@upr.edu', 'role' => 'DSCA Staff'],
        ['id' => 19, 'name' => 'Antonella Ramos', 'email' => 'aramos@upr.edu', 'role' => 'Student Org Advisor'],
        ['id' => 20, 'name' => 'Emilio Castro', 'email' => 'ecastro@upr.edu', 'role' => 'Admin'],
        ['id' => 21, 'name' => 'Renata Vargas', 'email' => 'rvargas@upr.edu', 'role' => 'Student Org Rep'],
        ['id' => 22, 'name' => 'Joaquin Delgado', 'email' => 'jdelgado@upr.edu', 'role' => 'Venue Manager', 'department' => 'Arts & Sciences'],
        ['id' => 23, 'name' => 'Valeria Ortega', 'email' => 'vortega@upr.edu', 'role' => 'DSCA Staff'],
        ['id' => 24, 'name' => 'Andres Molina', 'email' => 'amolina@upr.edu', 'role' => 'Student Org Advisor'],
        ['id' => 25, 'name' => 'Martina Aguilar', 'email' => 'maguilar@upr.edu', 'role' => 'Student Org Rep'],
        ['id' => 26, 'name' => 'Fernando Reyes', 'email' => 'freyes@upr.edu', 'role' => 'Student Org Rep'],
        ['id' => 27, 'name' => 'Catalina Romero', 'email' => 'cromero@upr.edu', 'role' => 'Venue Manager', 'department' => 'Agriculture'],
    ];

    /**
     * Returns a collection of all users, taking into account any soft-deleted or hard-deleted users,
     * as well as any edits or new users created in the current session.
     *
     * @return Collection
     */
    protected function allUsers(): Collection
    {
        $combined = array_merge(self::$users, session('new_users', []));
        $deletedIndex = array_flip(array_unique(array_merge(
            array_map('intval', session('soft_deleted_user_ids', [])),
            array_map('intval', session('hard_deleted_user_ids', []))
        )));

        $combined = array_values(array_filter($combined, function ($u) use ($deletedIndex) {
            return !isset($deletedIndex[(int) $u['id']]);
        }));

        // Normalize: ensure each user has roles[]
        foreach ($combined as &$u) {
            if (!array_key_exists('roles', $u)) {
                // Map old single 'role' to roles[]
                $u['roles'] = isset($u['role']) && $u['role'] !== '' ? [$u['role']] : [];
            }
            // Optionally include department_id in the future; for now ignored/displayed as dash
        }
        unset($u);

        // Apply edited overrides from session (and normalize again if needed)
        $edited = session('edited_users', []);
        foreach ($combined as &$u) {
            if (isset($edited[$u['id']])) {
                $u = array_merge($u, $edited[$u['id']]);
                if (!array_key_exists('roles', $u)) {
                    $u['roles'] = isset($u['role']) && $u['role'] !== '' ? [$u['role']] : [];
                }
            }
        }
        unset($u);

        return collect($combined);
    }

    /**
     * Returns a Bootstrap class corresponding to the given role.
     * This can be used to color-code the roles in the user list.
     *
     * @param string $role The role to get a class for.
     * @return string The Bootstrap class name.
     */
    public function roleClass(string $role): string
    {
        return match ($role) {
            'Admin'   => 'text-bg-danger',
            'Student Org Rep'   => 'text-bg-primary',
            'Student Org Advisor'   => 'text-bg-secondary',
            'Venue Manager'   => 'text-bg-info',
            'DSCA Staff'   => 'text-bg-secondary',
            'Dean of Administration'   => 'text-bg-success',
        };
    }

    /**
     * Resets the current page to 1 when the search input is updated.
     * This ensures that the user is shown the first page of results when the search input is changed.
     */
    public function updatedSearch()
    {
        $this->page = 1;
        $this->selected = []; // Clear selections when search changes
    }

    /**
     * Resets the current page to 1 when the role filter is updated.
     */
    public function updatedRole()
    {
        $this->page = 1;
        $this->selected = []; // Clear selections when role filter changes
    }

    public function updatedPage() // NEW: clear selection when page changes
    {
        $this->selected = [];
    }

    /**
     * Resets the current page to 1 when the page size is updated.
     */
    public function updatedPageSize()
    {
        $this->page = 1;
    }

    /**
     * Clear all filters and selections
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->role = '';
        $this->selected = [];
        $this->page = 1;
    }

    /**
     * Toggles the selection of a user.
     *
     * @param int $userId The ID of the user to toggle.
     * @param bool $checked Whether to select or deselect the user.
     */
    public function toggleSelect(int $userId, bool $checked): void
    {
        if ($checked) {
            $this->selected[$userId] = true;
        } else {
            unset($this->selected[$userId]);
        }
        $this->dispatch(
            'selectionHydrate',
            visible: $this->paginated()->pluck('id')->all(),
            selected: array_keys($this->selected)
        ); // (optional)
    }

    /**
     * Select or deselect all rows on the current page.
     *
     * @param bool $checked Whether to select or deselect the rows.
     * @param array $ids The IDs of the rows to select or deselect.
     */
    public function selectAllOnPage(bool $checked, array $ids): void
    {
        foreach ($ids as $id) {
            if ($checked) $this->selected[$id] = true;
            else unset($this->selected[$id]);
        }
        $this->dispatch(
            'selectionHydrate',
            visible: $this->paginated()->pluck('id')->all(),
            selected: array_keys($this->selected)
        ); // (optional)
    }

    /**
     * Resets the edit fields and opens the edit user modal for adding a new user.
     *
     * This will reset the edit fields to their default values and open the edit user modal.
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
     * Opens the edit user modal for the user with the given ID.
     *
     * It will reset the edit fields to the values of the user with the given ID and open the edit user modal.
     * If the user with the given ID does not exist, it will do nothing.
     *
     * @param int $id The ID of the user to open the edit user modal for.
     */
    public function openEdit(int $id): void
    {
        $u = $this->allUsers()->firstWhere('id', $id);
        if (!$u) return;

        $this->editId     = $u['id'];
        $this->editName   = $u['name'];
        $this->editEmail  = $u['email'];
        $this->editRoles = $u['roles'] ?? [];
        $this->editDepartment = $u['department'] ?? '';

        $this->resetErrorBag();
        $this->resetValidation();

        $this->dispatch('bs:open', id: 'editUserModal');
    }

    /**
     * Validation rules for the user fields.
     *
     * The rules are as follows:
     * - editName: required, string, max length 255, and must only contain letters and spaces.
     * - editEmail: required, email, and must end with '@upr.edu'.
     * - editRole: required, and must be a string.
     *
     * @return array The validation rules.
     */
    protected function rules()
    {
        $hasRoleWithoutDept = !empty(array_intersect($this->editRoles, self::ROLES_WITHOUT_DEPARTMENT));

        return [
            'editName' => 'required|string|max:255|regex:/^[a-zA-Z\s]+$/',
            'editEmail' => 'required|email|regex:/@upr[a-z]*\.edu$/i',
            'editRoles' => 'array|min:1',
            'editRoles.*' => 'string',
            'editDepartment' => $hasRoleWithoutDept ? 'nullable' : 'required|string',
            'justification' => 'nullable|string|max:200'
        ];
    }

    protected function validateJustification(): void // NEW: helper to validate only justification length
    {
        $this->validateOnly('justification');
    }

    /**
     * Generates a unique ID for a new user.
     *
     * It generates the ID by finding the maximum ID in the existing users, new users created this session,
     * and IDs of users that were soft/hard deleted this session, and then adding 1 to it.
     *
     * @return int The generated ID.
     */
    protected function generateUserId(): int
    {
        $baseIds = array_column(self::$users, 'id');
        $new     = session('new_users', []);
        $newIds  = array_column($new, 'id');

        // Also avoid reusing IDs that were soft/hard deleted this session.
        $soft = array_map('intval', session('soft_deleted_user_ids', []));
        $hard = array_map('intval', session('hard_deleted_user_ids', []));

        $allIds = array_merge($baseIds, $newIds, $soft, $hard);
        $maxId  = $allIds ? max($allIds) : 0;

        return $maxId + 1;
    }

    /**
     * Saves the currently edited user.
     *
     * First, it checks email uniqueness before validation.
     * If the email is already taken by another user, it will add an error to the editEmail field and return.
     * Then, it validates the user fields according to the rules defined in the rules() method.
     * If any of the fields fail validation, it will add an error to the corresponding field and return.
     * If the user is new (i.e. editId is null), it will skip justification and call confirmSave() to confirm the save action.
     * If the user is existing (i.e. editId is not null), it will open the justification modal to confirm the save action.
     */
    public function save(): void
    {
        // Check email uniqueness before validation
        $existingUser = $this->allUsers()->firstWhere('email', $this->editEmail);
        if ($existingUser && (!$this->editId || $existingUser['id'] !== $this->editId)) {
            $this->addError('editEmail', 'This email is already taken.');
            return;
        }

        $this->validate();
        $this->isDeleting = false;

        // Skip justification for new users
        if (!$this->editId) {
            $this->confirmSave();
            $this->jumpToLastPageAfterCreate();
            return;
        }

        $this->dispatch('bs:open', id: 'userJustify');
    }

    /**
     * Confirms the save action for the currently edited user.
     *
     * If the user is existing (i.e. editId is not null), it will update the user with the given fields.
     * If the user is new (i.e. editId is null), it will create a new user with the given fields.
     * After saving the user, it will close the justification modal and edit user modal, and show a toast message indicating whether the user was updated or created.
     */
    public function confirmSave(): void
    {
        if ($this->editId) {
            // Update existing user
            $this->validateJustification();
            $editedUsers = session('edited_users', []);
            $editedUsers[$this->editId] = [
                'name'  => $this->editName,
                'email' => $this->editEmail,
                'roles' => array_values(array_unique($this->editRoles)),
                'department' => $this->editDepartment,
            ];
            session(['edited_users' => $editedUsers]);
            $message = 'User updated';
        } else {
            // Create new user (no justification needed)
            $newUsers = session('new_users', []);
            $newUsers[] = [
                'id'    => $this->generateUserId(),
                'name'  => $this->editName,
                'email' => $this->editEmail,
                'roles' => array_values(array_unique($this->editRoles)),
                'department' => $this->editDepartment,
            ];
            session(['new_users' => $newUsers]);
            $message = 'User created';
        }

        $this->dispatch('bs:close', id: 'userJustify');
        $this->dispatch('bs:close', id: 'editUserModal');
        $this->dispatch('toast', message: $message);
        $this->reset(['editId', 'justification', 'isDeleting', 'isBulkDeleting']);
    }

    protected function jumpToLastPageAfterCreate(): void
    {
        $total   = $this->filtered()->count();
        $last    = max(1, (int) ceil($total / max(1, $this->pageSize)));
        $this->page = $last;
        $this->selected = []; // also clear any selection since we navigated
    }

    /**
     * Clamp the current page after a mutation to prevent the page from becoming out of bounds.
     *
     * After a mutation, the total number of items in the filtered collection is calculated.
     * The last page is then calculated by dividing the total number of items by the page size and rounding up to the nearest integer.
     * If the current page is greater than the last page, the current page is clamped to the last page.
     */
    protected function clampPageAfterMutation(): void
    {
        $total   = $this->filtered()->count();
        $last    = max(1, (int) ceil($total / max(1, $this->pageSize)));
        if ($this->page > $last) {
            $this->page = $last;
        }
    }

    /**
     * Opens the justification modal to confirm deletion of a user.
     *
     * @param int $id The ID of the user to be deleted.
     */
    public function delete(int $id): void
    {
        $this->editId = $id;
        $this->isDeleting = true;
        $this->dispatch('bs:open', id: 'userJustify');
    }

    /**
     * Deletes the currently edited user.
     *
     * If the currently edited user exists (i.e. editId is not null), it will delete the user and remove it from the selected users.
     * It will then clamp the current page after the deletion to prevent the page from becoming out of bounds.
     * Finally, it will show a toast message indicating whether the user was permanently deleted or just deleted.
     */
    public function confirmDelete(): void
    {
        if ($this->editId) {
            $this->validateJustification();
            session()->push($this->deleteType === 'hard' ? 'hard_deleted_user_ids' : 'soft_deleted_user_ids', $this->editId);
            unset($this->selected[$this->editId]);
        }

        // Clamp page AFTER data changed
        $this->clampPageAfterMutation();

        $this->dispatch('bs:close', id: 'userJustify');
        $this->dispatch('toast', message: 'User ' . ($this->deleteType === 'hard' ? 'permanently deleted' : 'deleted'));
        $this->reset(['editId', 'justification', 'isDeleting', 'isBulkDeleting']);
    }

    /**
     * Opens the justification modal to confirm deletion of multiple users at once.
     *
     * If there are no selected users, it will return without doing anything.
     * Otherwise, it will set the isBulkDeleting flag to true and open the justification modal.
     */
    public function bulkDelete(): void
    {
        if (empty($this->selected)) return;
        $this->isBulkDeleting = true;
        $this->dispatch('bs:open', id: 'userJustify');
    }

    /**
     * Confirm deletion of multiple users at once.
     *
     * This function will delete all the selected users based on the delete type (hard or soft).
     * It will then clamp the current page after the deletion to prevent the page from becoming out of bounds.
     * Finally, it will show a toast message indicating whether the users were permanently deleted or just deleted.
     */
    public function confirmBulkDelete(): void
    {
        $selectedIds = array_keys($this->selected);
        if (empty($selectedIds)) return;

        $this->validateJustification();
        $sessionKey = $this->deleteType === 'hard' ? 'hard_deleted_user_ids' : 'soft_deleted_user_ids';
        $existingIds = session($sessionKey, []);
        $newIds = array_merge($existingIds, $selectedIds);
        session([$sessionKey => array_values(array_unique($newIds))]);

        $this->selected = [];

        // Clamp page AFTER data changed
        $this->clampPageAfterMutation();

        $this->dispatch('bs:close', id: 'userJustify');
        $this->dispatch('toast', message: count($selectedIds) . " users " . ($this->deleteType === 'hard' ? 'permanently deleted' : 'deleted'));
        $this->reset(['justification', 'isBulkDeleting']);
    }


    /**
     * Restore all soft deleted users.
     *
     * This function will reset the 'soft_deleted_user_ids' session variable,
     * effectively restoring all soft deleted users to the list.
     *
     * After restoring, it will show a toast message indicating that all deleted users have been restored.
     */
    public function restoreUsers(): void
    {
        session(['soft_deleted_user_ids' => []]);
        //session(['hard_deleted_user_ids' => []]);
        $this->dispatch('toast', message: 'All deleted users restored');
    }

    /**
     * Filter the allUsers() collection based on the current search string and role.
     *
     * If search string is empty, it will return all users.
     * If role is empty, it will return all users regardless of their role.
     *
     * @return Collection
     */
    protected function filtered(): Collection
    {
        $s = mb_strtolower(trim($this->search));
        $selectedRole = $this->role;

        return $this->allUsers()
            ->filter(function ($u) use ($s, $selectedRole) {
                $hit = $s === '' ||
                    str_contains(mb_strtolower($u['name']), $s) ||
                    str_contains(mb_strtolower($u['email']), $s);

                $roles = $u['roles'] ?? [];
                $roleOk = $selectedRole === '' || in_array($selectedRole, $roles, true);
                return $hit && $roleOk;
            })
            ->values();
    }

    /**
     * Paginate the filtered collection of users.
     *
     * This function takes the filtered collection of users and paginates it
     * based on the current page and page size. It then returns a
     * LengthAwarePaginator object, which can be used to display the
     * paginated data.
     *
     * @return LengthAwarePaginator
     */
    protected function paginated(): LengthAwarePaginator
    {
        $data  = $this->filtered();
        $total = $data->count();

        // Keep page within bounds
        $lastPage = max(1, (int) ceil($total / max(1, $this->pageSize)));
        if ($this->page > $lastPage) $this->page = $lastPage;
        if ($this->page < 1) $this->page = 1;

        $items = $data
            ->slice(($this->page - 1) * $this->pageSize, $this->pageSize)
            ->values();

        return new LengthAwarePaginator(
            items: $items,
            total: $total,
            perPage: $this->pageSize,
            currentPage: $this->page,
            options: ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * Renders the Livewire component for the users index page.
     *
     * It takes in the paginated data and visible IDs and passes them to the view.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        $paginator  = $this->paginated();
        $visibleIds = $paginator->pluck('id')->all();
        $this->dispatch(
            'selectionHydrate',
            visible: $visibleIds,
            selected: array_keys($this->selected)
        );

        return view('livewire.admin.users-index', [
            'rows'       => $paginator,
            'visibleIds' => $visibleIds,
        ]);
    }
}
