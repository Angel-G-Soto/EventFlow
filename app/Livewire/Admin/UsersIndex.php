<?php

namespace App\Livewire\Admin;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Livewire\Concerns\TableSelection;
use App\Livewire\Concerns\WithPaginationClamping;

#[Layout('layouts.app')] // loads your Bootstrap layout
class UsersIndex extends Component
{
    use TableSelection, WithPaginationClamping;


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
    public string $editDepartment = '';
    public string $editRole = 'Student Org Rep';

    public string $justification = '';
    public string $actionType = '';
    public string $deleteType = 'soft'; // 'soft' or 'hard'

    public function getIsDeletingProperty(): bool
    {
        return $this->actionType === 'delete';
    }

    public function getIsBulkDeletingProperty(): bool
    {
        return $this->actionType === 'bulkDelete';
    }

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
     * Returns a collection of all users that are not deleted.
     *
     * This function takes into account both soft and hard deleted users,
     * and also applies any edits that have been made to the users.
     *
     * @return Collection
     */
    protected function allUsers(): Collection
    {
        $deletedIndex = array_flip(array_unique(array_merge(
            array_map('intval', session('soft_deleted_user_ids', [])),
            array_map('intval', session('hard_deleted_user_ids', []))
        )));

        $combined = array_filter(
            array_merge(self::$users, session('new_users', [])),
            function (array $u) use ($deletedIndex) {
                return !isset($deletedIndex[(int) $u['id']]);
            }
        );

        $editedUsers = session('edited_users', []);
        foreach ($combined as &$u) {
            if (isset($editedUsers[$u['id']])) {
                $u = array_merge($u, $editedUsers[$u['id']]);
            }
        }
        unset($u);

        return collect($combined);
    }

    /**
     * Returns a Bootstrap CSS class based on the given role.
     *
     * This function returns a string that corresponds to a Bootstrap CSS class
     * that can be used to style a badge based on the given role.
     *
     * @param string $role The role to get the class for.
     * @return string The Bootstrap CSS class for the given role.
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
     * Resets the current page to 1 when the search filter is updated.
     *
     * Also clears all current selections when the search filter is updated.
     */
    public function updatedSearch()
    {
        $this->page = 1;
        $this->selected = []; // Clear selections when search changes
    }

    /**
     * Resets the current page to 1 when the role filter is updated.
     *
     * Additionally, clears all current selections when the role filter is updated.
     */
    public function updatedRole()
    {
        $this->page = 1;
        $this->selected = []; // Clear selections when role filter changes
    }

    /**
     * Clears all filters and resets the page to 1.
     *
     * This will clear the search filter, the role filter, and the selected users,
     * and reset the page to 1.
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->role = '';
        $this->selected = [];
        $this->page = 1;
    }

    /**
     * Resets the edit fields to their default values and opens the edit user modal.
     *
     * This function will reset the edit fields to their default values and open the edit user modal.
     * The edit role field will be set to 'Student Org Rep'.
     */
    public function openCreate(): void
    {
        $this->reset(['editId', 'editName', 'editEmail', 'editRole', 'editDepartment']);
        $this->editRole = 'Student Org Rep';
        $this->resetErrorBag();
        $this->resetValidation();
        $this->dispatch('bs:open', id: 'editUserModal');
    }

    /**
     * Opens the edit user modal for the given user ID.
     *
     * This function will open the edit user modal and populate the fields with the user's information.
     * If the user ID does not exist, the function will return without performing any action.
     *
     * @param int $id The ID of the user to edit
     */
    public function openEdit(int $id): void
    {
        $u = $this->allUsers()->firstWhere('id', $id);
        if (!$u) return;

        $this->editId     = $u['id'];
        $this->editName   = $u['name'];
        $this->editEmail  = $u['email'];
        $this->editRole   = $u['role'];
        $this->editDepartment = $u['department'] ?? '';

        $this->resetErrorBag();
        $this->resetValidation();

        $this->dispatch('bs:open', id: 'editUserModal');
    }

    /**
     * Validation rules for the user editing form.
     *
     * The rules are as follows:
     * - editName: required, string, max 255 characters, regex matching only alphabetical characters and whitespace
     * - editEmail: required, email, regex matching only UPRA email addresses
     * - editRole: required, string
     * - editDepartment: required if the role is not one of the following, string: Student Org Rep, Student Org Advisor, Venue Manager, DSCA Staff, Dean of Administration
     * - justification: nullable, string, max 200 characters
     */
    protected function rules(): array
    {
        $roleWithoutDept = in_array($this->editRole, self::ROLES_WITHOUT_DEPARTMENT);
        return [
            'editName' => 'required|string|max:255|regex:/^[a-zA-Z\s]+$/',
            'editEmail' => 'required|email|regex:/@upr[a-z]*\.edu$/i',
            'editRole' => 'required|string',
            'editDepartment' => $roleWithoutDept ? 'nullable' : 'required|string',

            'justification' => 'nullable|string|max:200'
        ];
    }

    /**
     * Validates only the justification length.
     *
     * This function is a helper to validate only the justification length by calling
     * `validateOnly` with the justification field as the parameter.
     */
    protected function validateJustification(): void
    {
        $this->validateOnly('justification');
    }

    /**
     * Generates a new unique user ID.
     *
     * This function generates a new unique user ID by combining IDs from the base users array, new users array,
     * soft deleted user IDs array, and hard deleted user IDs array. It then returns the maximum ID in the combined array
     * plus 1.
     *
     * @return int The new unique user ID.
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
            $this->jumpToLastPageAfterCreate();
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
        if ($this->editId) {
            // Update existing user
            $this->validateJustification();
            $editedUsers = session('edited_users', []);
            $editedUsers[$this->editId] = [
                'name'  => $this->editName,
                'email' => $this->editEmail,
                'role'  => $this->editRole,
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
                'role'  => $this->editRole,
                'department' => $this->editDepartment,
            ];
            session(['new_users' => $newUsers]);
            $message = 'User created';
        }

        $this->dispatch('bs:close', id: 'userJustify');
        $this->dispatch('bs:close', id: 'editUserModal');
        $this->dispatch('toast', message: $message);
        $this->reset(['editId', 'justification', 'actionType']);
    }

    /**
     * Opens the justification modal for the user with the given ID.
     * This function should be called when the user wants to delete a user.
     * It sets the currently edited user ID and the isDeleting flag to true, and then opens the justification modal.
     * @param int $id The ID of the user to delete
     */
    public function delete(int $id): void
    {
        $this->editId = $id;
        $this->actionType = 'delete';
        $this->dispatch('bs:open', id: 'userJustify');
    }

    /**
     * Confirms the deletion of a user.
     *
     * This function will validate the justification entered by the user, and then delete the user with the given ID.
     * After deletion, it clamps the current page to prevent the page from becoming out of bounds.
     * Finally, it shows a toast message indicating whether the user was permanently deleted or just deleted.
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
        $this->reset(['editId', 'justification', 'actionType']);
    }

    /**
     * Opens the justification modal for bulk deletion of users.
     *
     * This function is called when the user wants to delete multiple users at once.
     * It sets the isBulkDeleting flag to true, and then opens the justification modal.
     */
    public function bulkDelete(): void
    {
        if (empty($this->selected)) return;
        $this->actionType = 'bulkDelete';
        $this->dispatch('bs:open', id: 'userJustify');
    }

    /**
     * Confirms the bulk deletion of users.
     *
     * This function will validate the justification entered by the user, and then delete the users with the given IDs.
     * After deletion, it clamps the current page to prevent the page from becoming out of bounds.
     * Finally, it shows a toast message indicating whether the users were permanently deleted or just deleted.
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
        $this->reset(['justification', 'actionType']);
    }

    /**
     * Restores all soft deleted users.
     *
     * This function will reset the soft_deleted_user_ids session key to an empty array,
     * effectively restoring all soft deleted users.
     *
     * @return void
     */
    public function restoreUsers(): void
    {
        session(['soft_deleted_user_ids' => []]);
        //session(['hard_deleted_user_ids' => []]);
        $this->dispatch('toast', message: 'All deleted users restored');
    }

    /**
     * Returns a filtered collection of users based on the search string and role.
     *
     * This function will filter the users based on the search string, which is case-insensitive.
     * It will also filter out users that do not match the given role, if any.
     *
     * @return Collection The filtered collection of users.
     */
    protected function filtered(): Collection
    {
        $search = mb_strtolower(trim($this->search));
        $users = $this->allUsers();

        return $users->filter(function ($user) use ($search) {
            // Ensure user has required keys
            if (!isset($user['role'], $user['name'], $user['email'])) {
                return false;
            }

            // Role filter first (cheaper operation)
            if ($this->role && $user['role'] !== $this->role) {
                return false;
            }

            // Search filter (only if search term exists)
            if ($search) {
                return str_contains(mb_strtolower($user['name']), $search) ||
                    str_contains(mb_strtolower($user['email']), $search);
            }

            return true;
        });
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
        $data = $this->filtered();
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
     * Check if the current user has any role that cannot have departments.
     * Only Venue Managers can have departments.
     *
     * @return bool True if user has roles without departments
     */
    public function getHasRoleWithoutDepartmentProperty(): bool
    {
        return in_array($this->editRole, self::ROLES_WITHOUT_DEPARTMENT);
    }

    /**
     * Renders the Livewire view for the users index page.
     *
     * The view is passed the following variables:
     * - $rows: The paginated collection of User objects
     * - $visibleIds: The array of visible user IDs
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        $paginator = $this->paginated();
        $visibleIds = $paginator->pluck('id')->all();

        $this->dispatch(
            'selectionHydrate',
            visible: $visibleIds,
            selected: array_keys($this->selected)
        );

        return view('livewire.admin.users-index', [
            'rows' => $paginator,
            'visibleIds' => $visibleIds,
        ]);
    }
}
