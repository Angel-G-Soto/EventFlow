<?php

namespace App\Services;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Marks an Event as "completed" if it's currently "approved" and its end_time has passed.
 *
 * This implementation uses the Event Eloquent model directly (no repository abstraction).
 * It's still easily unit-testable by mocking Event::newQuery() and the Builder chain.
 */
class EventCompletionService
{
    public const STATUS_APPROVED  = 'approved';
    public const STATUS_COMPLETED = 'completed';

    public function __construct(
        private Event $events,
        private AuditService $audit // optional; remove if we don't want auditing here
    ) {}

    /**
     * Returns true if the event status was changed to "completed".
     */
    public function completeIfPast(int $eventId): bool
    {
        // Load a lightweight snapshot (id, status, end_time)
        $row = $this->events->newQuery()
            ->where('id', $eventId)
            ->first(['id', 'status', 'end_time']);

        if (!$row instanceof Model) {
            // nothing to do if not found
            return false;
        }

        $status  = (string) $row->getAttribute('status');
        $endTime = $row->getAttribute('end_time');

        if ($status !== self::STATUS_APPROVED) {
            return false;
        }

        $endAt = $endTime ? Carbon::parse($endTime) : null;
        if (!$endAt || $endAt->isFuture()) {
            return false;
        }

        // Update status
        $this->events->newQuery()
            ->where('id', $eventId)
            ->update(['status' => self::STATUS_COMPLETED]);

        // Optional audit trail
        if ($systemUserId = (int) config('eventflow.system_user_id', 0)) {
            $meta = [
                'status'   => self::STATUS_COMPLETED,
                'source'   => 'auto_completion_cron',
                'event_id' => (int) $eventId,
            ];
            $ctx = ['meta' => $meta];
            if (function_exists('request') && request()) {
                $ctx = $this->audit->buildContextFromRequest(request(), $meta);
            }

            $label = 'Event #' . (string) $eventId;

            $this->audit->logAdminAction(
                $systemUserId,
                'event',
                'EVENT_COMPLETED_AUTO',
                $label,
                $ctx
            );
        }

        return true;
    }
}
