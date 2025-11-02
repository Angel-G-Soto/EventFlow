<?php

namespace App\Services;

use App\Models\AuditTrail;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * NOTE: THIS IS NELSON'S IMPLEMENTATION
 *
 * AuditService
 *
 * This service is a critical component responsible for creating an immutable, chronological log
 * of all significant actions performed within EventFlow. It is designed to be a lightweight,
 * decoupled utility that receives all necessary data as simple parameters.
 */
class AuditService
{
    /**
     * This is the primary method used to log a standard action performed by any authenticated user.
     *
     * @param int    $userId The ID of the authenticated user performing the action.
     * @param string $userName The display name of the user performing the action.
     * @param string $actionCode A machine-readable string identifying the action (e.g., EVENT_CREATED).
     * @param string $description A human-readable sentence describing the action.
     * @return AuditTrail The newly created AuditTrail Eloquent model instance.
     */
    public function logAction(?int $userId, ?string $userName, ?string $actionCode, ?string $description): AuditTrail
    {
        return $this->write($userId, $userName, $actionCode, $description);
    }

    /**
     * Logs a high-privilege action performed by a System Administrator.
     *
     * @param int    $adminId The ID of the authenticated admin performing the action.
     * @param string $actionCode A concise, machine-readable action code (e.g., ADMIN_OVERRIDE).
     * @param string $description A human-readable description of what happened.
     * @param string|null $ipAddress Optional. The IP address from which the request originated.
     * @return AuditTrail The newly created AuditTrail Eloquent model instance.
     */
    public function logAdminAction(?int $adminId, ?string $userName, ?string $actionCode, ?string $description): AuditTrail
    {
        return $this->write($adminId, $userName, $actionCode, $description);
    }

    /**
     * Core writer method that creates the audit trail record in the database.
     *
     * @param int $userId
     * @param string $actionCode
     * @param string $description
     * @return AuditTrail
     */
    protected function write(?int $userId, ?string $userName, ?string $actionCode, ?string $description): AuditTrail
    {
        if (is_null($userId) || is_null($userName) || is_null($actionCode) || is_null($description)) {
            throw new \TypeError('Required argument was null.');
        }

        // action => action code
        // target_type => free-form context (use user display name)
        // target_id => free-form identifier (use description summary)
        return AuditTrail::create([
            'user_id'     => $userId,
            'action'      => mb_substr($actionCode, 0, 255),
            'target_type' => mb_substr($userName, 0, 255),
            'target_id'   => mb_substr($description, 0, 255),
        ]);
    }

    /**
     * Returns paginated audit logs applying optional filters.
     *
     * @param array{user_id?:int|null, action?:string|null, date_from?:string|null, date_to?:string|null} $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedLogs(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = AuditTrail::query()
            ->select(['id', 'user_id', 'target_type', 'action', 'target_id', 'created_at'])
            ->orderByDesc('created_at');

        if (!empty($filters['user_id'])) {
            $query->where('user_id', (int)$filters['user_id']);
        }

        if (!empty($filters['action'])) {
            $query->where('action', 'like', '%' . trim($filters['action']) . '%');
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $paginator = $query->paginate($perPage);
        // Preserve query string for pagination links in the view
        $paginator->appends(array_filter([
            'user_id'   => $filters['user_id']   ?? null,
            'action'    => $filters['action']    ?? null,
            'date_from' => $filters['date_from'] ?? null,
            'date_to'   => $filters['date_to']   ?? null,
            'per_page'  => $perPage,
        ]));

        return $paginator;
    }

    /**
     * Returns distinct audited users seen in the audit trail,
     * useful for a dropdown without depending on the User model.
     *
     * @return array<int, string> key=id, value=display name
     */
    public function getAuditedUsers(): array
    {
        return AuditTrail::query()
            // target_type stores the display name captured at log time
            ->select('user_id', 'target_type')
            ->whereNotNull('user_id')
            ->whereNotNull('target_type')
            ->distinct()
            ->orderBy('target_type')
            ->get()
            ->pluck('target_type', 'user_id')
            ->toArray();
    }
}
