<?php

namespace App\Services;

use App\Models\AuditTrail;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Service responsible for writing and reading audit log entries.
 *
 * The audit log captures a minimal, immutable record of who did what and to which target.
 * It centralizes write semantics (validation, length guards) and provides read helpers
 * with common filters for index screens.
 *
 * Columns written (from migration):
 *  - user_id (int, FK users.id)
 *  - action (string[255])         e.g. "EVENT_CREATED", "ADMIN_OVERRIDE"
 *  - target_type (string[255])    e.g. "event", "user", "system"
 *  - target_id (string[255])      e.g. "42", "ORG-abc123"
 *
 * @psalm-type AuditFilters=array{
 *     user_id?: int|null,
 *     action?: string|null,
 *     date_from?: string|null,  // 'Y-m-d' (date-only)
 *     date_to?: string|null     // 'Y-m-d' (date-only, >= date_from)
 * }
 */
class AuditService
{
    /**
     * Log a regular user action (non-admin).
     *
     * Semantics:
     *  - The caller must pass the acting user's ID.
     *  - `actionCode` should be short and machine-readable (<=255).
     *  - `targetType` and `targetId` are free-form strings (<=255) that identify context and target.
     *
     * Examples:
     *  $audit->logAction($userId, 'EVENT_CREATED', 'event', '42');
     *  $audit->logAction($userId, 'PROFILE_UPDATED', 'user', (string)$userId);
     *
     * @param int    $userId     Actor's user ID. Must be > 0.
     * @param string $actionCode Short machine code (e.g., 'EVENT_CREATED').
     * @param string $targetType Context label (e.g., 'event', 'user', 'system').
     * @param string $targetId   Target identifier (ID, slug, or human label).
     *
     * @return AuditTrail Newly created Eloquent model instance.
     *
     * @throws \InvalidArgumentException If any parameter is empty/invalid.
     */
    public function logAction(
        int $userId,
        string $actionCode,
        string $targetType,
        string $targetId
    ): AuditTrail {
        return $this->write($userId, $actionCode, $targetType, $targetId);
    }

    /**
     * Log a high-privilege admin action.
     *
     * Exactly the same schema as {@see self::logAction()}, but named to signal intent.
     * Pass the admin's user ID as $adminId.
     *
     * Example:
     *  $audit->logAdminAction($adminId, 'ADMIN_OVERRIDE', 'event', '42');
     *
     * @param int    $adminId    Admin actor's user ID. Must be > 0.
     * @param string $actionCode Short machine code (e.g., 'ADMIN_OVERRIDE').
     * @param string $targetType Context label (e.g., 'event', 'user', 'system').
     * @param string $targetId   Target identifier (ID, slug, or human label).
     *
     * @return AuditTrail Newly created Eloquent model instance.
     *
     * @throws \InvalidArgumentException If any parameter is empty/invalid.
     */
    public function logAdminAction(
        int $adminId,
        string $actionCode,
        string $targetType,
        string $targetId
    ): AuditTrail {
        return $this->write($adminId, $actionCode, $targetType, $targetId);
    }

    /**
     * Core writer that enforces basic validation and truncation to column limits.
     *
     * @param int    $userId
     * @param string $actionCode
     * @param string $targetType
     * @param string $targetId
     *
     * @return AuditTrail
     *
     * @throws \InvalidArgumentException If any parameter is empty/invalid.
     */
    protected function write(
        int $userId,
        string $actionCode,
        string $targetType,
        string $targetId
    ): AuditTrail {
        if ($userId <= 0 || $actionCode === '' || $targetType === '' || $targetId === '') {
            throw new \InvalidArgumentException('Invalid audit log parameters.');
        }

        return AuditTrail::create([
            'user_id'     => $userId,
            'action'      => mb_substr($actionCode, 0, 255),
            'target_type' => mb_substr($targetType, 0, 255),
            'target_id'   => mb_substr($targetId, 0, 255),
        ]);
    }

    /**
     * Retrieve paginated audit logs with optional filters.
     *
     * Filters supported:
     *  - user_id   : exact match on actor ID.
     *  - action    : substring match on action code.
     *  - date_from : inclusive lower bound on created_at (date only).
     *  - date_to   : inclusive upper bound on created_at (date only).
     *
     * Pagination:
     *  - Appends the current filters to the paginator for consistent links.
     *
     * @param array<string,mixed> $filters   See @psalm-type AuditFilters for shape.
     * @param int                 $perPage   Items per page (defaults to 25).
     *
     * @return LengthAwarePaginator<AuditTrail>
     */
    public function getPaginatedLogs(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $q = AuditTrail::query()
            ->select(['id', 'user_id', 'target_type', 'action', 'target_id', 'created_at'])
            ->orderByDesc('created_at');

        if (!empty($filters['user_id'])) {
            $q->where('user_id', (int) $filters['user_id']);
        }

        if (!empty($filters['action'])) {
            $q->where('action', 'like', '%' . trim((string) $filters['action']) . '%');
        }

        if (!empty($filters['date_from'])) {
            $q->whereDate('created_at', '>=', (string) $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $q->whereDate('created_at', '<=', (string) $filters['date_to']);
        }

        $paginator = $q->paginate($perPage);

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
     * Get a map of distinct audited users seen in the log.
     *
     * Note:
     *  - This assumes you store a human-friendly label in `target_type` for each row
     *    that refers to a user, or you can adapt this to join users if you prefer.
     *
     * @return array<int,string> Array keyed by user_id with display names as values.
     */
    public function getAuditedUsers(): array
    {
        return AuditTrail::query()
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
