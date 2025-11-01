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
    /**
     * Initialize the component by loading the base list of users from the repository.
     */
    public function mount()
    {
        $this->users = UserRepository::all();
    }

    /**
     * Returns a collection of all users, both seeded and created by users.
     * This function takes into account soft-deleted users (no hard delete), and will not include them in the collection.
     * It also normalizes the data by ensuring each user has a 'roles' key, and optionally includes 'department_id'.
     *
     * @return Collection An Eloquent Collection of User objects.
     */
    protected function allUsers(): Collection
    {
        // Base + newly created (session) users
        $combined = array_merge($this->users, session('new_users', []));

        // Exclude soft-deleted IDs
        $deletedIds   = array_map('intval', session('soft_deleted_user_ids', []));
        $deletedIndex = array_flip(array_unique($deletedIds));
        $combined = array_values(array_filter($combined, fn($user) => !isset($deletedIndex[(int) ($user['id'] ?? 0)])));

        // Apply edits and normalize in a single pass
        $edited = session('edited_users', []);
        $combined = array_map(function (array $user) use ($edited) {
            if (isset($user['id']) && isset($edited[$user['id']])) {
                $user = array_merge($user, $edited[$user['id']]);
            }

            // Ensure roles[] is present and unique (support legacy single 'role')
            $roles = $user['roles'] ?? ((isset($user['role']) && $user['role'] !== '') ? [$user['role']] : []);
            $user['roles'] = array_values(array_unique($roles));

            return $user;
        }, $combined);

        return collect($combined);
    }

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
        $this->editRoles = $user['roles'] ?? [];
        $this->editDepartment = $user['department'] ?? '';

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
        $roleWithoutDept = $this->roleExemptsDepartment($this->editRoles);
        return [
            'editName'       => 'required|string|max:255|regex:/^[a-zA-Z\s]+$/',
            'editEmail'      => 'required|email|regex:/@upr[a-z]*\.edu$/i',
            'editRoles'      => 'array|min:1',
            'editRoles.*'    => 'string',
            'editDepartment' => $roleWithoutDept ? 'nullable' : 'required|string',
            'justification'  => 'nullable|string|max:200',
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
     * Generates a unique user ID by taking the maximum ID of all users (both existing and new),
     * and incrementing it by 1.
     *
     * This function also takes into account IDs that were soft-deleted this session, to avoid
     * reusing them.
     *
     * @return int A unique user ID.
     */
    protected function generateUserId(): int
    {
        $baseIds = array_column(
            $this->users,
            'id'
        );
        $new     = session('new_users', []);
        $newIds  = array_column($new, 'id');

        // Also avoid reusing IDs that were soft-deleted this session.
        $soft = array_map('intval', session('soft_deleted_user_ids', []));
        $allIds = array_merge($baseIds, $newIds, $soft);
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
        $isEditing = (bool) $this->editId;

        if ($isEditing) {
            $this->validateJustification();
            $editedUsers = session('edited_users', []);
            $editedUsers[$this->editId] = [
                'name'       => $this->editName,
                'email'      => $this->editEmail,
                'roles'      => array_values(array_unique($this->editRoles)),
                'department' => $this->editDepartment,
            ];
            session(['edited_users' => $editedUsers]);
        } else {
            $newUsers   = session('new_users', []);
            $newUsers[] = [
                'id'         => $this->generateUserId(),
                'name'       => $this->editName,
                'email'      => $this->editEmail,
                'roles'      => array_values(array_unique($this->editRoles)),
                'department' => $this->editDepartment,
            ];
            session(['new_users' => $newUsers]);
        }

        $this->dispatch('bs:close', id: 'userJustify');
        $this->dispatch('bs:close', id: 'editUserModal');
        $this->dispatch('toast', message: $isEditing ? 'User updated' : 'User created');
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
     * Finally, it shows a toast message indicating the user was deleted.
     */
    public function confirmDelete(): void
    {
        if ($this->editId) {
            $this->validateJustification();
            session()->push('soft_deleted_user_ids', $this->editId);
        }

        $this->dispatch('bs:close', id: 'userJustify');
        $this->dispatch('toast', message: 'User deleted');
        $this->reset(['editId', 'justification', 'actionType']);
    }


    // Restore-all functionality removed

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
        return $this->roleExemptsDepartment($this->editRoles);
    }

    /**
     * Determine if any of the given roles do not require a department.
     *
     * @param array<int,string> $roles The set of role names to evaluate.
     * @return bool True when at least one role is exempt from having a department.
     */
    protected function roleExemptsDepartment(array $roles): bool
    {
        return count(array_intersect($roles, UserConstants::ROLES_WITHOUT_DEPARTMENT)) > 0;
    }


    /**
     * Renders the Livewire view for the users index page.
     *
     * @return \Illuminate\Http\Response
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
