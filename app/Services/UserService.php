<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use App\Services\AuditService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * UserService
 *
 * This service acts as the primary gateway for all business logic related to user entities.
 * It handles user creation, retrieval, and the management of internal roles.
 */
class UserService
{
    protected AuditService $auditService;
    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Finds a user by their email or creates a new one if they don't exist.
     *
     * @param string $email The unique email address from the SSO provider.
     * @param string $name The full name of the user from the SSO provider.
     * @return User The found or newly created Eloquent User object.
     */
    public function findOrCreateUser(string $email, ?string $name = null): User
    {
        $user = User::firstOrCreate(
            ['email' => $email]
            //            ['first_name' => $name],
            ,
            [
                'first_name' => 'first_name',
                'last_name' => 'last_name',
                'email' => $email,
                'password' => bcrypt('password'),
                'auth_type' => 'saml2',
            ]
        );

        // Ensure the default 'user' role exists on the account
        try {
            // Try to find 'user' by code or name
            $default = Role::where('code', 'user')->orWhere('name', 'user')->first();
            if (!$default) {
                $default = Role::firstOrCreate(['code' => 'user'], ['name' => 'user']);
            }
            if ($default && method_exists($user, 'roles')) {
                $user->roles()->syncWithoutDetaching([(int) $default->id]);
                // Audit Log Write
                try {
                    $ctx = ['meta' => ['source' => 'saml2_login']];
                    if (function_exists('request') && request()) {
                        $ctx = app(\App\Services\AuditService::class)
                            ->buildContextFromRequest(request(), $ctx['meta']);
                    }
                } catch (\Throwable $e) {
                    report($e); // log but don’t block the business action
                }
            }
        } catch (\Throwable $e) {
            // noop: default role assignment best-effort
        }


        // AUDIT: record SSO user bootstrap/login (created vs found)
        try {
            $ctx = ['meta' => ['source' => 'saml2_login']];
            if (function_exists('request') && request()) {
                $ctx = app(\App\Services\AuditService::class)
                    ->buildContextFromRequest(request(), $ctx['meta']);
            }
        } catch (\Throwable) { /* no-http/queue */ }

        if ($user->wasRecentlyCreated ?? false) {
            $this->auditService->logAction(
                (int) $user->id,
                'user',                    // targetType
                'USER_CREATED_SSO',        // actionCode
                (string) ($user->id ?? 0), // targetId
                $ctx
            );
        } else {
            $this->auditService->logAction(
                (int) $user->id,
                'user',
                'USER_LOGGED_IN_SSO',
                (string) ($user->id ?? 0),
                $ctx
            );
        }

        return $user;
    }

    /**
     * Retrieves a single user by their primary key.
     *
     * @param int $userId The primary key (user_id) of the user to find.
     * @return User|Error The Eloquent User object or Excemption if not found.
     */
    public function findUserById(int $userId): ?User
    {
        return User::findOrFail($userId);
    }

    /**
     * Retrieves all roles from the roles table.
     *
     * @return \Illuminate\Database\Eloquent\Collection|Role[]
     */
    public function getAllRoles(): Collection
    {
        return Role::all();
    }

    /**
     * Retrieves a collection of all users who have no roles assigned.
     *
     * @return Collection An Eloquent Collection of User objects.
     */
    public function getUsersWithNoRoles(): Collection
    {
        return User::whereDoesntHave('roles')
            ->with('department')
            ->get();
    }

    // Removed implicit actor fallback helpers; admin actor must be provided explicitly by callers when auditing.

    /**
     * Synchronizes the roles for a given user to match the provided list of role codes.
     *
     * @param User $user
     * @param array $roleCodes
     * @param User|null $admin
     * @param string|null $justification Optional admin-provided justification to include in audit log.
     */
    /**
     * Synchronizes the roles for a given user to match the provided list of role codes.
     *
     * @param User $user
     * @param array $roleCodes
     * @param User $admin
     * @param string $justification
     * @return User
     */
    public function updateUserRoles(User $user, array $roleCodes, User $admin, string $justification): User
    {
        $normalize = fn($v) => Str::slug(mb_strtolower((string) $v));

        $user->loadMissing('roles');
        $previousRoles = $this->getNormalizedRoles($user->roles, $normalize);
        $requested = $this->getNormalizedRequestedRoles($roleCodes, $normalize);

        $roleIds = $this->resolveRoleIds($requested);
        $user->roles()->sync(array_values(array_unique($roleIds)));

        $this->clearDepartmentIfNeeded($user, $previousRoles, $requested, $admin, $justification);
        $this->logUserRoleUpdate($user, $roleCodes, $admin, $justification);

        return $user->load('roles');
    }

    /**
     * @param \Illuminate\Support\Collection $roles
     * @param callable $normalize
     * @return array
     */
    private function getNormalizedRoles($roles, callable $normalize): array
    {
        return $roles
            ->map(fn($r) => $normalize($r->name ?? $r->code ?? ''))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param array $roleCodes
     * @param callable $normalize
     * @return array
     */
    private function getNormalizedRequestedRoles(array $roleCodes, callable $normalize): array
    {
        $requested = collect($roleCodes)
            ->map($normalize)
            ->filter(fn($v) => $v !== '')
            ->values()
            ->all();
        if (!in_array('user', $requested, true)) {
            $requested[] = 'user';
        }
        return $requested;
    }

    /**
     * @param array $requested
     * @return array
     */
    private function resolveRoleIds(array $requested): array
    {
        $roles = Role::query()
            ->whereIn(DB::raw('LOWER(code)'), $requested)
            ->orWhereIn(DB::raw('LOWER(name)'), $requested)
            ->get(['id', 'code', 'name']);

        $foundIds = $roles->pluck('id')->all();
        $foundKeysLower = $roles
            ->flatMap(function ($r) {
                return [
                    Str::slug(mb_strtolower((string)$r->code)) => true,
                    Str::slug(mb_strtolower((string)$r->name)) => true,
                ];
            })
            ->keys()
            ->all();

        $missing = array_values(array_diff($requested, $foundKeysLower));
        foreach ($missing as $rcode) {
            $created = Role::firstOrCreate(
                ['code' => $rcode],
                ['name' => Str::of($rcode)->replace('-', ' ')->title()]
            );
            $foundIds[] = (int) $created->id;
        }
        return $foundIds;
    }

    /**
     * @param User $user
     * @param array $previousRoles
     * @param array $requested
     * @param User $admin
     * @param string $justification
     */
    private function clearDepartmentIfNeeded(User $user, array $previousRoles, array $requested, User $admin, string $justification): void
    {
        $requiresDepartment = function (array $codes) {
            $codes = collect($codes)->map(fn($c) => Str::slug(mb_strtolower((string) $c)))->all();
            return in_array('department-director', $codes, true) || in_array('venue-manager', $codes, true);
        };
        $hadDeptRole = $requiresDepartment($previousRoles);
        $hasDeptRole = $requiresDepartment($requested);
        if ($hadDeptRole && !$hasDeptRole && $user->department_id !== null) {
            $user->department_id = null;
            $user->save();

            if ($admin && $admin->id) {
                $meta = [
                    'user_id'    => (int) ($user->id ?? 0),
                    'user_email' => (string) ($user->email ?? ''),
                    'removed_department' => true,
                    'source' => 'user_roles_update',
                ];
                if (($just = trim((string) $justification)) !== '') {
                    $meta['justification'] = $just;
                }
                $ctx = ['meta' => $meta];
                try {
                    if (function_exists('request') && request()) {
                        $ctx = app(AuditService::class)->buildContextFromRequest(request(), $meta);
                    }
                } catch (\Throwable) { /* best-effort */ }

                $this->auditService->logAdminAction(
                    $admin->id,
                    'department',
                    'USER_DEPT_REMOVED_ROLE',
                    (string) ($user->id ?? 0),
                    $ctx
                );
            }
        }
    }

    /**
     * @param User $user
     * @param array $roleCodes
     * @param User $admin
     * @param string $justification
     */
    private function logUserRoleUpdate(User $user, array $roleCodes, User $admin, string $justification): void
    {
        if ($admin && $admin->id) {
            $actorName = trim(((string)($admin->first_name ?? '')) . ' ' . ((string)($admin->last_name ?? '')));
            if ($actorName === '') {
                $actorName = (string)($admin->email ?? '');
            }

            $ctx = ['meta' => ['roles' => array_values($roleCodes), 'source' => 'user_roles_update']];
            if (($justification = trim((string)$justification)) !== '') {
                $ctx['meta']['justification'] = $justification;
            }
            try {
                if (request()) {
                    $ctx = app(AuditService::class)
                        ->buildContextFromRequest(request(), $ctx['meta']);
                }
            } catch (\Throwable) { /* queue/no-http */ }

            $this->auditService->logAdminAction(
                $admin->id,
                'user',
                'USER_ROLES_UPDATED',
                (string) ($user->id ?? 0),
                $ctx
            );
        }
    }

    /**
     * Assigns a staff member to a specific department.
     *
     * @param User $user
     * @param int $departmentId
     * @param User|null $admin
     * @param string|null $justification Optional admin-provided justification to include in audit log.
     */
    public function assignUserToDepartment(User $user, int $departmentId, User $admin, string $justification): User
    {
        // Ensure the department exists before assigning
        $department = Department::findOrFail($departmentId);

        // Department PK is 'id'; assign to user's foreign key column
        $user->department_id = (int) $department->id;
        $user->save();

        // Audit the action; require explicit admin, no fallback
        if ($admin && $admin->id) {
            $actorName = trim(((string)($admin->first_name ?? '')) . ' ' . ((string)($admin->last_name ?? '')));
            if ($actorName === '') {
                $actorName = (string)($admin->email ?? '');
            }

            $deptName = (string) ($department->d_name ?? $department->name ?? '');
            $targetId = $deptName !== '' ? $deptName : (string)($department->id ?? 'department');

            $ctx = ['meta' => [
                'user_id'        => (int) ($user->id ?? 0),
                'user_email'     => (string) ($user->email ?? ''),
                'department_id'  => (int) ($department->id ?? 0),
                'department_name' => $deptName,
                'source'         => 'user_dept_assign',
            ]];
            if (($justification = trim((string)$justification)) !== '') {
                $ctx['meta']['justification'] = $justification;
            }
            try {
                if (request()) {
                    $ctx = app(AuditService::class)
                        ->buildContextFromRequest(request(), $ctx['meta']);
                }
            } catch (\Throwable) { /* queue/no-http */
            }

            $this->auditService->logAdminAction(
                $admin->id,
                'department',
                'USER_DEPT_ASSIGNED',
                $targetId,
                $ctx
            );
        }
        return $user;
    }

    /**
     * Updates a user's profile information.

     * @param User $user
     * @param array $data
     * @param User|null $admin
     * @param string|null $justification Optional admin-provided justification to include in audit log.
     */
    // public function updateUserProfile(User $user, array $data, User $admin, string $justification): User
    // {
    //     // Define a whitelist of fields that are allowed to be updated to prevent mass assignment vulnerabilities.
    //     $fillableData = Arr::only($data, ['first_name', 'last_name', 'email']);

    //     $user->fill($fillableData);
    //     $user->save();

    //     if ($admin && $admin->id) {
    //         $adminName = trim(((string)($admin->first_name ?? '')) . ' ' . ((string)($admin->last_name ?? '')));
    //         if ($adminName === '') {
    //             $adminName = (string)($admin->email ?? '');
    //         }
    //         $ctx = ['meta' => [
    //             'fields' => array_keys($fillableData),
    //             'source' => 'user_profile_update',
    //         ]];
    //         if (($justification = trim((string)$justification)) !== '') {
    //             $ctx['meta']['justification'] = $justification;
    //         }
    //         try {
    //             if (request()) {
    //                 $ctx = app(AuditService::class)
    //                     ->buildContextFromRequest(request(), $ctx['meta']);
    //             }
    //         } catch (\Throwable) { /* queue/no-http */
    //         }
    //         $this->auditService->logAdminAction(
    //             (int) $admin->id,
    //             $adminName,
    //             'USER_PROFILE_UPDATED',
    //             (string) ($user->id ?? 0),
    //             $ctx
    //         );
    //     }

    //     return $user;
    // }

    /**
     * Create a new user with the provided data.
     *
     * @param array $data
     * @param User|null $admin
     * @param string|null $justification Optional admin-provided justification to include in audit log.
     */
    public function createUser(array $data, User $admin): User
    {
        $payload = [
            'first_name' => $data['first_name'] ?? '',
            'last_name'  => $data['last_name']  ?? '',
            'email'      => $data['email'],
            'auth_type'  => $data['auth_type'] ?? 'saml',
        ];
        if (!empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $user = User::create($payload);

        // Ensure the default 'user' role is attached on creation
        try {
            $default = Role::where('code', 'user')->orWhere('name', 'user')->first();
            if (!$default) {
                $default = Role::firstOrCreate(['code' => 'user'], ['name' => 'user']);
            }
            if ($default && method_exists($user, 'roles')) {
                $user->roles()->syncWithoutDetaching([(int) $default->id]);
            }
        } catch (\Throwable $e) {
            // best-effort; continue
        }

        // Only log if explicit admin is provided
        if ($admin && $admin->id) {
            $actorName = trim(((string)($admin->first_name ?? '')) . ' ' . ((string)($admin->last_name ?? '')));
            if ($actorName === '') {
                $actorName = (string)($admin->email ?? '');
            }

            // Optional HTTP context (falls back to meta-only if no request)
            $ctx = ['meta' => ['source' => 'user_create']];
            try {
                if (request()) {
                    $ctx = app(AuditService::class)
                        ->buildContextFromRequest(request(), $ctx['meta']);
                }
            } catch (\Throwable) {
                // keep $ctx as-is
            }

            $this->auditService->logAdminAction(
                $admin->id,
                'user',
                'USER_CREATED',
                (string) ($user->id ?? 0),
                $ctx
            );
        }

        return $user;
    }

    /**
     * Retrieves a collection of all users who have a specific role.
     *
     * @param string $roleCode The machine-readable code for the role (e.g., 'dsca-staff').
     * @return Collection An Eloquent Collection of User objects.
     */
    public function getUsersWithRole(string $roleCode): Collection
    {
        return User::whereHas('roles', function ($query) use ($roleCode) {
            // Roles table uses human-readable slug in 'name'; 'code' is numeric in this schema
            $query->where('name', $roleCode);
        })
            ->with(['department', 'roles'])
            ->get();
    }

    /**
     * Paginate normalized user rows for the admin list without exposing Eloquent models to the component.
     *
     * @param array<string,mixed> $filters
     * @param int $perPage
     * @param int $page
     * @param array{field?:string|null,direction?:string|null}|null $sort
     */
    public function paginateUserRows(array $filters = [], int $perPage = 10, int $page = 1, ?array $sort = null): LengthAwarePaginator
    {
        $query = User::query()
            ->with(['department', 'roles'])
            ->whereNull('deleted_at');

        $search = trim((string)($filters['search'] ?? ''));
        if ($search !== '') {
            $like = '%' . mb_strtolower($search) . '%';
            $query->where(function ($builder) use ($like) {
                $builder->whereRaw('LOWER(first_name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(last_name) LIKE ?', [$like])
                    ->orWhereRaw("LOWER(CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,''))) LIKE ?", [$like])
                    ->orWhereRaw('LOWER(email) LIKE ?', [$like]);
            });
        }

        $role = $filters['role'] ?? '';
        if ($role === '__none__') {
            $query->whereDoesntHave('roles');
        } elseif (is_string($role) && $role !== '') {
            $query->whereHas('roles', function ($builder) use ($role) {
                $builder->where('name', $role);
            });
        }

        $direction = strtolower($sort['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $field = $sort['field'] ?? null;
        if ($field === 'email') {
            $query->orderBy('email', $direction);
        } else {
            // Default to natural name sorting
            $query->orderBy('first_name', $direction)->orderBy('last_name', $direction);
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', max(1, $page));
        $collection = $paginator->getCollection()->map(fn(User $user) => $this->mapUserToRow($user));
        $paginator->setCollection($collection);

        return $paginator;
    }

    /**
     * Retrieve a single normalized row for the given user id.
     */
    public function getUserRowById(int $userId): ?array
    {
        $user = User::with(['department', 'roles'])->find($userId);
        if (!$user) {
            return null;
        }

        return $this->mapUserToRow($user);
    }

    /**
     * Build the lightweight data structure consumed by Livewire.
     */
    protected function mapUserToRow(User $user): array
    {
        $name = trim(trim((string)($user->first_name ?? '')) . ' ' . trim((string)($user->last_name ?? '')));
        if ($name === '') {
            $name = (string)($user->email ?? '');
        }

        $roles = $user->roles
            ->map(fn($role) => Str::slug(mb_strtolower((string)($role->name ?? ($role->code ?? '')))))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $departmentName = optional($user->department)->name;

        return [
            'id' => (int)($user->id ?? 0),
            'name' => $name,
            'email' => (string)($user->email ?? ''),
            'department' => $departmentName !== null ? (string)$departmentName : '',
            'roles' => $roles,
        ];
    }
}
