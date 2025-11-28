<?php

namespace App\Services;

use App\Models\AuditTrail;
use App\Models\Event;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Service responsible for writing and reading audit log entries.
 *
 * Columns written (from migration + extension):
 *  - user_id (int, FK users.id)
 *  - action (string[255])         e.g. "EVENT_CREATED", "ADMIN_OVERRIDE"
 *  - target_type (string[255])    e.g. "event", "user", "system"
 *  - target_id (string[255])      e.g. "42", "ORG-abc123"
 *  - ip (string[45]|nullable)     request IP (IPv4/IPv6)
 *  - method (string[10]|nullable) HTTP method
 *  - path (string[2048]|nullable) request path
 *  - ua (text|nullable)           user agent
 *  - meta (json|nullable)         extra context (associative array -> JSON)
 *
 * @psalm-type AuditFilters=array{
 *     user_id?: int|null,
 *     action?: string|null,
 *     date_from?: string|null,  // 'Y-m-d'
 *     date_to?: string|null     // 'Y-m-d' (>= date_from)
 * }
 */
class AuditService
{
    /**
     * Log a regular user action (non-admin).
     *
     * @param int    $userId
     * @param string $targetType  Display label (e.g., user's display name)
     * @param string $actionCode  Action code (e.g., EVENT_CREATED)
     * @param string $targetId    Target identifier/description
     * @param array<string,mixed> $context Optional: ['ip','method','path','ua','meta'=>array]
     *
     * @return AuditTrail
     */
    public function logAction(
        int $userId,
        string $targetType,
        string $actionCode,
        string $targetId,
        array $context = []
    ): AuditTrail {
        return $this->write($userId, $actionCode, $targetType, $targetId, $context);
    }

    /**
     * Log a high-privilege admin action (same schema, different intent).
     *
     * @param int    $adminId
     * @param string $targetType  Display label (e.g., admin's display name)
     * @param string $actionCode  Action code
     * @param string $targetId    Target identifier/description
     * @param array<string,mixed> $context Optional: ['ip','method','path','ua','meta'=>array]
     *
     * @return AuditTrail
     */
    public function logAdminAction(
        int $adminId,
        string $targetType,
        string $actionCode,
        string $targetId,
        array $context = []
    ): AuditTrail {
        return $this->write($adminId, $actionCode, $targetType, $targetId, $context);
    }

    /**
     * Convenience: log a user action capturing request context automatically.
     *
     * @param Request $request
     * @param int     $userId
     * @param string  $targetType Display label
     * @param string  $actionCode Action code
     * @param string  $targetId   Target identifier/description
     * @param array<string,mixed> $extraMeta Extra meta merged into context['meta']
     */
    public function logActionFromRequest(
        Request $request,
        int $userId,
        string $targetType,
        string $actionCode,
        string $targetId,
        array $extraMeta = []
    ): AuditTrail {
        return $this->logAction(
            $userId,
            $targetType,
            $actionCode,
            $targetId,
            $this->buildContextFromRequest($request, $extraMeta)
        );
    }

    /**
     * Convenience: log an admin action capturing request context automatically.
     */
    public function logAdminActionFromRequest(
        Request $request,
        int $adminId,
        string $targetType,
        string $actionCode,
        string $targetId,
        array $extraMeta = []
    ): AuditTrail {
        return $this->logAdminAction(
            $adminId,
            $targetType,
            $actionCode,
            $targetId,
            $this->buildContextFromRequest($request, $extraMeta)
        );
    }

    /**
     * Convenience helper for logging admin actions tied to an Event entity.
     */
    public function logEventAdminAction(User $admin, Event $event, string $actionCode, array $extraMeta = []): AuditTrail
    {
        if ($admin->id <= 0) {
            throw new \InvalidArgumentException('Valid admin user required for audit.');
        }

        $context = [];
        if (!empty($extraMeta)) {
            $context['meta'] = $extraMeta;
        }

        try {
            if (function_exists('request') && request()) {
                $context = $this->buildContextFromRequest(request(), $extraMeta);
            }
        } catch (\Throwable) {
            // No HTTP context available; proceed with meta only.
        }

        $eventLabel = trim((string) ($event->title ?? ''));
        if ($eventLabel === '') {
            $eventLabel = 'Event #' . (string) $event->id;
        }

        return $this->logAdminAction(
            (int) $admin->id,
            'event',
            $actionCode,
            $eventLabel,
            $context
        );
    }

    /**
     * Core writer that enforces basic validation and truncation to column limits.
     *
     * @param array<string,mixed> $context Optional: ['ip','method','path','ua','meta'=>array]
     *
     * @throws \InvalidArgumentException
     */
    protected function write(
        int $userId,
        string $actionCode,
        string $targetType,
        string $targetId,
        array $context = []
    ): AuditTrail {
        // Only enforce valid user and non-empty action code. Allow empty targetType/targetId per tests.
        if ($userId <= 0 || $actionCode === '') {
            throw new \InvalidArgumentException('Invalid audit log parameters.');
        }

        // Allow only known contextual keys; coerce meta to array if provided.
        $allowed = array_intersect_key($context, array_flip(['ip', 'method', 'path', 'ua', 'meta']));
        if (isset($allowed['meta']) && !is_array($allowed['meta'])) {
            $allowed['meta'] = ['raw' => (string) $allowed['meta']];
        }

        try {
            return AuditTrail::create(array_merge([
                'user_id'     => $userId,
                'action'      => mb_substr($actionCode, 0, 255),
                'target_type' => mb_substr($targetType, 0, 255),
                'target_id'   => mb_substr($targetId, 0, 255),
            ], $allowed));
        } catch (\Throwable $exception) {
            Log::error('Failed to write audit log entry.', [
                'user_id'     => $userId,
                'action'      => $actionCode,
                'target_type' => $targetType,
                'target_id'   => $targetId,
                'context'     => $allowed,
                'exception'   => $exception,
            ]);

            throw $exception;
        }
    }

    /**
     * Build a whitelisted context array from the current Request.
     *
     * @param Request $request
     * @param array<string,mixed> $extraMeta Extra meta merged into 'meta'
     * @return array{ip?:string|null,method?:string|null,path?:string|null,ua?:string|null,meta?:array<string,mixed>}
     */
    public function buildContextFromRequest(Request $request, array $extraMeta = []): array
    {
        $ctx = [
            'ip'     => $request->ip(),
            'method' => $request->method(),
            'path'   => $request->path(),
            'ua'     => $request->userAgent(),
        ];

        if (!empty($extraMeta)) {
            $ctx['meta'] = $extraMeta;
        }

        return $ctx;
    }

    /**
     * Retrieve paginated audit logs with optional filters.
     *
     * @param array<string,mixed> $filters
     * @param int                 $perPage
     * @return LengthAwarePaginator<AuditTrail>
     */
    public function getPaginatedLogs(array $filters = [], int $perPage = 25, ?int $page = null): LengthAwarePaginator
    {
        // Select all columns to avoid referencing optional columns that may not exist
        $q = AuditTrail::query()
            ->with('actor')
            ->orderByDesc('created_at');

        $applyTextLike = function (string $value): string {
            return '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value) . '%';
        };

        if (!empty($filters['q'])) {
            $term = trim((string) $filters['q']);
            if ($term !== '') {
                $q->where(function ($sub) use ($term, $applyTextLike) {
                    $like = $applyTextLike($term);
                    $sub->orWhereHas('actor', function ($uq) use ($like) {
                        $uq->whereRaw(
                            "TRIM(CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,''))) LIKE ? ESCAPE '\\\\'",
                            [$like]
                        )->orWhere('name', 'like', $like)
                          ->orWhere('email', 'like', $like);
                    });
                    $sub->orWhere('action', 'like', $like);
                    $sub->orWhere('target_type', 'like', $like);
                    $sub->orWhere('target_id', 'like', $like);
                    $sub->orWhere('ip', 'like', $like);
                });
            }
        } else {
            if (!empty($filters['user'])) {
                $term = trim((string) $filters['user']);
                if ($term !== '') {
                    if (ctype_digit($term)) {
                        // Numeric: treat as exact user ID
                        $q->where('user_id', (int) $term);
                    } else {
                        // Text: search on related user name/email
                        $like = $applyTextLike($term);
                        $q->whereHas('actor', function ($uq) use ($like) {
                            $uq->whereRaw(
                                "TRIM(CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,''))) LIKE ? ESCAPE '\\\\'",
                                [$like]
                            )->orWhere('name', 'like', $like)
                              ->orWhere('email', 'like', $like);
                        });
                    }
                }
            }

            if (!empty($filters['action'])) {
                $q->where('action', 'like', $applyTextLike(trim((string) $filters['action'])));
            }
        }

        if (!empty($filters['date_from'])) {
            $q->whereDate('created_at', '>=', (string) $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $q->whereDate('created_at', '<=', (string) $filters['date_to']);
        }

        $currentPage = $page !== null ? max(1, $page) : null;

        $paginator = $currentPage === null
            ? $q->paginate($perPage)
            : $q->paginate($perPage, ['*'], 'page', $currentPage);

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
     * Distinct audited users (id => label).
     *
     * NOTE: This reads labels from target_type per your earlier pattern.
     * If you later want the real user name, switch to a join on users.
     *
     * @return array<int,string>
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

    /**
     * Retrieve a single audit log by its primary key.
     */
    public function getLogById(int $id): ?AuditTrail
    {
        if ($id <= 0) {
            return null;
        }
        return AuditTrail::find($id);
    }
}
