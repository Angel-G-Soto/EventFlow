<?php

namespace App\Livewire\Admin;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use App\Support\UserConstants;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Livewire\Traits\UserFilters;
use App\Livewire\Traits\UserEditState;
use App\Repositories\UserRepository;

#[Layout('layouts.app')] // loads your Bootstrap layout
class UsersIndex extends Component
{
    use UserFilters, UserEditState;

    public array $users = [];
    public function mount()
    {
        $this->users = UserRepository::all();
    }

    /**
     * Returns a collection of all users, both seeded and created by users.
     * This function takes into account soft and hard deleted users, and will not include them in the collection.
     * It also normalizes the data by ensuring each user has a 'roles' key, and optionally includes 'department_id'.
     *
     * @return Collection An Eloquent Collection of User objects.
     */
    protected function allUsers(): Collection
    {
        $combined = array_merge(
            $this->users,
            session('new_users', [])
        );
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
     * Navigates to a given page number.
     *
     * @param int $target The target page number.
     *
     * This function will compute bounds from the current filters, and then
     * set the page number to the maximum of 1 and the minimum of the
     * target and the last page number. If the class has a 'selected'
     * property, it will be cleared when the page changes.
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
     * Additionally, clears all current selections when the role filter is updated.
     */
    public function updatedRole()
    {
        $this->page = 1;
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
        $this->page = 1;
    }

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
     * Returns an array of validation rules for the user edit form.
     *
     * The rules are as follows:
     * - editName: required, string, max 255 characters, regex: /^[a-zA-Z\s]+$/ (only letters and spaces)
     * - editEmail: required, email, regex: /@upr[a-z]*\.edu$/i (ends with @upr[a-z]*.edu)
     * - editRoles: array, min 1 items, each item is a string
     * - editRoles.*: string
     * - editDepartment: required if the user does not have a role without a department, otherwise nullable, string
     * - justification: nullable, string, max 200 characters
     */
    protected function rules(): array
    {
        $roleWithoutDept = in_array($this->editRoles, UserConstants::ROLES_WITHOUT_DEPARTMENT);
        return [
            'editName' => 'required|string|max:255|regex:/^[a-zA-Z\s]+$/',
            'editEmail' => 'required|email|regex:/@upr[a-z]*\.edu$/i',
            'editRoles' => 'array|min:1',
            'editRoles.*' => 'string',
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
        $baseIds = array_column(
            $this->users,
            'id'
        );
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
        }

        $this->dispatch('bs:close', id: 'userJustify');
        $this->dispatch('toast', message: 'User ' . ($this->deleteType === 'hard' ? 'permanently deleted' : 'deleted'));
        $this->reset(['editId', 'justification', 'actionType']);
    }

    // Bulk deletion removed

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
     * Returns a filtered collection of users based on the current search query and selected role.
     *
     * The filter function will return true if the search query is empty, or if the user's name or email contains the search query.
     * Additionally, the filter function will return true if the user has a role that matches the selected role.
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
     * Returns true if the current user has a role that does not require a department to be associated with them.
     *
     * This property is used to conditionally render a department select input for users who are being edited.
     *
     * @return bool True if the current user has a role that does not require a department, false otherwise.
     */
    public function getHasRoleWithoutDepartmentProperty(): bool
    {
        return in_array($this->editRoles, UserConstants::ROLES_WITHOUT_DEPARTMENT);
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

        return view('livewire.admin.users-index', [
            'rows' => $paginator,
            'visibleIds' => $visibleIds,
        ]);
    }
}
