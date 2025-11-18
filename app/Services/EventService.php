<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\Event;
use App\Models\EventHistory;
use App\Models\Role;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Support\Collection as SupportCollection;
use Carbon\Carbon;
use DateTime;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use PHPUnit\Event\EventCollection;
use Illuminate\Database\Eloquent\Collection;
use function PHPUnit\Framework\isEmpty;
use DateTimeInterface;

class EventService
{

    // Injected services
    //protected $eventHistoryService;
    protected $venueService;
    protected $categoryService;
    protected $auditService;
    protected $documentService;

    /**
     * Create a new EventService instance.
     *
     * @param VenueService $venueService
     * @param CategoryService $categoryService
     * @param AuditService $auditService
     */
    public function __construct(VenueService $venueService, CategoryService $categoryService, AuditService $auditService, DocumentService $documentService)
    {
        $this->venueService = $venueService;
        $this->categoryService = $categoryService;
        $this->auditService = $auditService;
        $this->documentService = $documentService;
    }


        /**
         * Sync event categories by IDs (for admin edit modal multiselect)
         *
         * @param Event $event
         * @param array $categoryIds
         * @return void
         */
        public function syncEventCategoriesByIds(Event $event, array $categoryIds): void
        {
            $event->categories()->sync($categoryIds);
        }

    // Methods

    // FORM

    /**
     * Persist an event request originating from the public form.
     *
     * @param array $data Validated event payload.
     * @param User $creator Authenticated user creating the event.
     * @param string $action Either 'draft' or 'publish' to determine workflow.
     * @param array<int>|null $document_ids Optional uploaded document identifiers.
     * @param array<int>|null $categories_ids Optional category identifiers to sync.
     * @return Event
     */
    public function updateOrCreateFromEventForm(array $data, User $creator, string $action, ?array $document_ids = [], ?array $categories_ids = [])
    {
        return DB::transaction(function () use ($data, $document_ids, $categories_ids, $creator, $action) {
            // Validate existence of related entities
            if (!User::where('id', $creator->id)->exists()) {
                abort(404, 'Author not found.');
            }

            if (!isset($data['venue_id']) || !$this->venueService->findByID($data['venue_id'])->exists()) {
                abort(404, 'Venue not found.');
            }

            // Determine status based on user action and advisor presence
            $status = match ($action) {
                'publish' => !empty($data['organization_advisor_email'])
                    ? 'pending - advisor approval'
                    : 'pending - approval',
                default   => 'draft',
            };

            // Create or update the event
            $event = Event::updateOrCreate(
                [
                    'id' => $data['id'] ?? null, // If provided, updates existing event
                ],
                [
                    'creator_id' => $creator->id,
                    'venue_id' => $data['venue_id'],

                    'organization_name' => $data['organization_name'] ?? null,
                    'organization_advisor_name' => $data['organization_advisor_name'] ?? null,
                    'organization_advisor_email' => $data['organization_advisor_email'] ?? null,
                    'organization_advisor_phone' => $data['organization_advisor_phone'] ?? null,

                    'creator_institutional_number' => $data['creator_institutional_number'] ?? null,
                    'creator_phone_number' => $data['creator_phone_number'] ?? null,

                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'start_time' => $data['start_time'],
                    'end_time' => $data['end_time'],

                    'status' => $status,
                    'guest_size' => $data['guest_size'] ?? null,
                    'handles_food' => $data['handles_food'] ?? false,
                    'use_institutional_funds' => $data['use_institutional_funds'] ?? false,
                    'external_guest' => $data['external_guests'] ?? false,
                ]
            );

            // AUDIT: creator created/updated event
            try {
                $actorId   = (int) $creator->id;
                $actorName = trim((string)($creator->first_name ?? '').' '.(string)($creator->last_name ?? '')) ?: (string)($creator->email ?? '');
                // Always include the event title in meta so it is visible
                // in the audit log regardless of create vs update.
                $meta      = [
                    'status' => (string) $status,
                    'source' => 'event_form',
                    'title'  => (string) $event->title,
                ];

                $ctx = ['meta' => $meta];
                if (function_exists('request') && request()) {
                    $ctx = $this->auditService->buildContextFromRequest(request(), $meta);
                }

                $this->auditService->logAction(
                    $actorId,
                    'event',
                    $event->wasRecentlyCreated ? 'EVENT_REQUEST_CREATED' : 'EVENT_REQUEST_UPDATED',
                    (string) $event->id,
                    $ctx
                );
            } catch (\Throwable) { /* best-effort */ }

            if ($event->status === 'draft') {
                return $event;
            }

            // Attach documents (hasMany)
            $this->documentService->assignDocumentsToEvent($document_ids ?? [], (int) $event->id);

            // Attach categories (many-to-many)
            if (!empty($categories_ids)) {
                $event->categories()->sync($categories_ids);
            }

            // Event history & status transitions
            if (!empty($data['organization_advisor_email'])) {
                if ($status === 'pending - advisor approval') {
                    // Example: log event history
                    $event->history()->create([
                        'action' => 'pending',
                        'approver_id' => $creator->id,
                        'comment' => 'Event submitted for approval.',
                        'status_when_signed' => $status,
                    ]);

                    // AUDIT: creator submitted event for approval
                    try {
                        $actorId = (int) $creator->id;
                        $meta    = ['submitted_to' => 'advisor', 'status' => (string)$status];

                        $ctx = ['meta' => $meta];
                        if (function_exists('request') && request()) {
                            $ctx = $this->auditService->buildContextFromRequest(request(), $meta);
                        }

                        $this->auditService->logAction(
                            $actorId,
                            'event',
                            'EVENT_SUBMITTED',
                            (string) $event->id,
                            $ctx
                        );
                    } catch (\Throwable) { /* best-effort */ }
                }
            } else {
                // Non-student organization flow not supported yet
                //                    if ($action === 'publish') {
                //                        abort(403, 'Event requests generated by non-student organizations are not yet supported.');
                //                    }
            }

                if (!empty($data['organization_advisor_email'])) {
                $eventDetails = app(NotificationService::class)->getEventDetails($event);
                app(NotificationService::class)->dispatchApprovalRequiredNotification(
                    approverEmail: $event->organization_advisor_email,
                    eventDetails: $eventDetails,
                );
                }

                return $event;
            });
        }

    //  PIPELINE

    /**
     * Deny an event and set its status to 'rejected'.
     *
     * This method handles:
     *   - Atomic update of the event status to 'rejected' if it is not in a terminal state.
     *   - Updating the most recent pending history record with the approver and rejection comment.
     *   - Triggering audit trail processes.
     *   - Sending notifications to prior approvers and the event creator.
     *
     * Race conditions are prevented by using a conditional update
     * that only succeeds if the current status is not already terminal.
     *
     * @param string $justification Rejection justification/comment.
     * @param Event $event The event to deny.
     * @param User $approver The user performing the denial.
     *
     * @return Event The updated event with refreshed status and relationships.
     */
    public function denyEvent(string $justification, Event $event, User $approver): Event
    {
        $updated = Event::where('id', $event->id)
            ->whereNotIn('status', ['approved', 'withdrawn', 'cancelled', 'rejected'])
            ->update(['status' => 'rejected']);
        if ($updated === 0) return $event; // stop if race condition occurred

        $this->updateLastHistory($event, $approver, $justification ?: 'unjustified rejection', 'rejected');

        // Run audit trail
        // AUDIT: approver denied event
        try {
            $actorId   = (int) $approver->id;
            $actorName = trim((string)($approver->first_name ?? '').' '.(string)($approver->last_name ?? '')) ?: (string)($approver->email ?? '');
            $meta      = ['justification' => (string) $justification];

            $ctx = ['meta' => $meta];
            if (function_exists('request') && request()) {
                $ctx = $this->auditService->buildContextFromRequest(request(), $meta);
            }

            $this->auditService->logAdminAction(
                $actorId,
                'event',
                'EVENT_DENIED',
                (string) $event->id,
                $ctx
            );
        } catch (\Throwable) { /* best-effort */ }
        // Send rejection email to prior approvers and creator
            // Send rejection email to prior approvers and creator
            $creatorEmail = $event->requester->email;

        $eventDetails = app(NotificationService::class)->getEventDetails($event);
        $approverEmails = app(EventHistoryService::class)->getEventApproverEmails($event);
        app(NotificationService::class)->dispatchRejectionNotification(
                creatorEmail: $creatorEmail,
                recipientEmails: $approverEmails,
                eventDetails: $eventDetails,
                justification: $justification,
                creatorRoute: route('user.request', ['event' => $event->id]),
                approverRoute: route('approver.history.request', ['eventHistory' => $event->id]),

            );


        return $event->refresh();
    }

    /**
     * Approve an event and move it to the next approval stage.
     *
     * This method handles:
     *   - Atomic update of the event status to the next stage.
     *   - Updating the most recent pending history record with the approver and action.
     *   - Creating a new pending history record if the event is not fully approved.
     *   - Sending notifications to the next approver(s).
     *
     * Race conditions are prevented by using a conditional update
     * that only succeeds if the current status matches the expected value.
     *
     * @param array $data Approval data (e.g., 'comment').
     * @param Event $event The event to approve.
     * @param User $approver The user performing the approval.
     *
     * @return Event The updated event with refreshed status and relationships.
     *
     * @throws \InvalidArgumentException If the event has an invalid or unexpected status.
     */
    public function approveEvent(Event $event, User $approver, ?string $comment = null): Event
    {
        return DB::transaction(function () use ($event, $approver, $comment) {
            $statusFlow = $this->getStatusFlow();
            $currentStatus = $event->status;
            if (!isset($statusFlow[$currentStatus])) {
                throw new \InvalidArgumentException('Event contains an invalid status');
            }

            $nextStatus = $statusFlow[$currentStatus];

            // Atomic update
            $updated = $this->updateEventStatus($event, $currentStatus, $nextStatus);
            if ($updated === 0) return $event; // stop if race condition occurred

            // Update last history record
            $this->updateLastHistory($event, $approver, $comment, 'approved');

            // Run audit trail
            // AUDIT: approver advanced approval stage (or final approval)
            try {
                $actorId   = (int) $approver->id;
                $actorName = trim((string)($approver->first_name ?? '').' '.(string)($approver->last_name ?? '')) ?: (string)($approver->email ?? '');
                $action    = ($nextStatus === 'approved') ? 'EVENT_APPROVED_FINAL' : 'EVENT_APPROVED_NEXT_STAGE';
                $meta      = ['from' => (string)$currentStatus, 'to' => (string)$nextStatus];

                $ctx = ['meta' => $meta];
                if (function_exists('request') && request()) {
                    $ctx = $this->auditService->buildContextFromRequest(request(), $meta);
                }

                $this->auditService->logAdminAction(
                    $actorId,
                    'event',
                    $action,
                    (string) $event->id,
                    $ctx
                );
            } catch (\Throwable) { /* best-effort */ }

            // Create new pending history only if not final approval
            if ($nextStatus !== 'approved') {
                $this->createPendingHistory($event, $nextStatus, $approver);
            }

            if ($event->status === $nextStatus) {
                $this->auditService->logEventAdminAction(
                    $approver,
                    $event,
                    'EVENT_APPROVED',
                    [
                        'next_status' => $nextStatus,
                        'comment' => (string)($comment ?? ''),
                    ]
                );
            }

            $approverName = $approver->first_name . ' ' . $approver->last_name;
            $this->sendCreatorUpdateEmail($event, $approverName, $currentStatus);

            $this->sendApproverEmails($event);


            return $event->refresh();
        });
    }

    public function advanceEvent(Event $event, User $user, string $justification): Event
    {
        return DB::transaction(function () use ($event, $user, $justification) {
            $statusFlow = $this->getStatusFlow();
            $currentStatus = $event->status;
            if (!isset($statusFlow[$currentStatus])) {
                throw new \InvalidArgumentException('Event cannot be advanced from current status');
            }

            $nextStatus = $statusFlow[$currentStatus];
            $result = $this->commitStatusTransition($event, $nextStatus, $user, 'advance', $justification);

            if ($result->status === $nextStatus) {
                $this->auditService->logEventAdminAction(
                    $user,
                    $result,
                    'EVENT_ADVANCED',
                    [
                        'next_status' => $nextStatus,
                        'justification' => (string)$justification,
                    ]
                );
            }

            return $result;
        });
    }

    /**
     * Atomically update event status if the persisted status matches the expected one.
     *
     * @param Event $event
     * @param string $currentStatus Expected current status.
     * @param string $nextStatus Status to transition to.
     * @return int Number of affected rows (0 if race condition prevented update).
     */
    protected function updateEventStatus(Event $event, string $currentStatus, string $nextStatus): int
    {
        return Event::whereNotIn('status', ['approved', 'withdrawn', 'cancelled', 'rejected'])
            ->where('id', $event->id)
            ->where('status', $currentStatus)
            ->update(['status' => $nextStatus]);
    }

    protected function commitStatusTransition(Event $event, string $nextStatus, User $actor, string $action, ?string $comment = null): Event
    {
        $currentStatus = $event->status;
        $updated = $this->updateEventStatus($event, $currentStatus, $nextStatus);
        if ($updated === 0) {
            return $event;
        }
        $this->updateLastHistory($event, $actor, $comment, $action);
        return $event->refresh();
    }

    /**
     * Update the latest pending history entry with an action and comment.
     *
     * @param Event $event
     * @param User $approver
     * @param string|null $comment
     * @param string $action Final action label (approved/rejected/etc).
     */
    protected function updateLastHistory(Event $event, User $approver, ?string $comment = null, string $action)
    {
        $lastHistory = $event->history()
            ->where('action', 'pending')
            ->latest()
            ->first();

        if ($lastHistory) {
            $lastHistory->update([
                'approver_id' => $approver->id,
                'action' => $action,
                'comment' => $comment ?? 'Approved and forwarded to next approver.',
            ]);
        }
    }

    /**
     * Create a new pending history entry for the next approval step.
     *
     * @param Event $event
     * @param string $nextStatus
     * @param User $approver
     */
    protected function createPendingHistory(Event $event, string $nextStatus, User $approver)
    {
        $event->history()->create([
            'action' => 'pending',
            'approver_id' => $approver->id,
            'comment' => $nextStatus,
            'status_when_signed' => $nextStatus,
        ]);
    }

    /**
     * Allow the request creator to withdraw their pending event.
     *
     * @param Event $event
     * @param User $user
     * @param string|null $comment
     * @return Event
     */
    public function withdrawEvent(Event $event, User $user, $comment): Event
    {
        return DB::transaction(function () use ($event, $user, $comment) {
            Event::where('id', $event->id)
                ->where('status', 'like', '%pending%')
                ->update(['status' => 'withdrawn']);

            $lastHistory = $event->history()
                ->latest()
                ->first();

            if ($lastHistory) {
                $lastHistory->update([
                    'approver_id' => $user->id,
                    'action' => 'withdrawn',
                    'comment' => $comment ?? 'Event was withdrawn by the user.',
                ]);

                // AUDIT: requester withdrew event
                try {
                    $actorId = (int) $user->id;
                    $meta    = ['comment' => (string) ($comment ?? '')];

                    $ctx = ['meta' => $meta];
                    if (function_exists('request') && request()) {
                        $ctx = $this->auditService->buildContextFromRequest(request(), $meta);
                    }

                    $this->auditService->logAction(
                        $actorId,
                        'event',
                        'EVENT_WITHDRAWN',
                        (string) $event->id,
                        $ctx
                    );
                } catch (\Throwable) { /* best-effort */ }
            }

                // Run audit trail

                // Send email to the approvers
                $creatorEmail = $event->requester->email;
                $eventDetails = app(NotificationService::class)->getEventDetails($event);
                $approverEmails = app(EventHistoryService::class)->getEventApproverEmails($event);
                app(NotificationService::class)->dispatchWithdrawalNotifications(
                    creatorEmail: $creatorEmail,
                    recipientEmails: $approverEmails,
                    eventDetails: $eventDetails,
                    justification: $comment,
                    approverRoute: route('approver.history.request', ['eventHistory' => $event->id]),
                    creatorRoute: route('user.request', ['event' => $event->id])

                );


                return $event->refresh();
            });
        }


    // Request creator cancels event (older signature removed; unified below)

    /**
     * Mark any approved events that finished yesterday as completed.
     *
     * @return void
     */
    public function markEventAsCompleted(): void
    {
        DB::transaction(function () {
            // Get the start and end of yesterday
            $yesterdayStart = Carbon::yesterday()->startOfDay();
            $yesterdayEnd = Carbon::yesterday()->endOfDay();

            // Update events that ended yesterday and are approved
            $events = Event::where('status', 'approved')
                ->whereBetween('end_time', [$yesterdayStart, $yesterdayEnd])
                ->update(['status' => 'completed']);

            // AUDIT: system batch marked events as completed (yesterday range)
            try {
                $systemUserId = (int) config('eventflow.system_user_id', 0);
                if ($systemUserId > 0) {
                    $meta = [
                        'range_start' => (string) $yesterdayStart,
                        'range_end'   => (string) $yesterdayEnd,
                    ];
                    $ctx = ['meta' => $meta];
                    if (function_exists('request') && request()) {
                        $ctx = $this->auditService->buildContextFromRequest(request(), $meta);
                    }

                    $this->auditService->logAdminAction(
                        $systemUserId,
                        'event',
                        'EVENTS_COMPLETED_BATCH',
                        'batch',
                        $ctx
                    );
                }
            } catch (\Throwable) { /* best-effort */ }

        });
    }

    protected function logAutoCompletion(Event $event): void
    {
        $systemUserId = (int) config('eventflow.system_user_id', 0);
        if ($systemUserId <= 0) {
            return;
        }

        $this->auditService->logAdminAction(
            $systemUserId,
            'event',
            'EVENT_COMPLETED_AUTO',
            (string) $event->id,
            ['meta' => ['status' => 'completed']]
        );
    }

    /**
     * Build a base query for events created by the given user.
     *
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getMyRequestedEvents(User $user): \Illuminate\Database\Eloquent\Builder
    {
        return Event::where('creator_id', $user->id);
    }

    /**
     * Get pending requests routed to the supplied approver role.
     *
     * @param User $user
     * @param Role $role
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function genericGetPendingRequests(User $user, Role $role): \Illuminate\Database\Eloquent\Builder
    {
        return match ($role->name) {
            'advisor' => Event::query()
                ->where('organization_advisor_email', $user->email)
                ->where('status', 'pending - advisor approval'),
            'venue-manager' => Event::query()
                ->whereIn('venue_id', $user->department->venues()->pluck('id'))
                ->where('status', 'pending - venue manager approval'),
            'event-approver' => Event::query()
                ->where('status', 'pending - dsca approval'),
            'deanship-of-administration-approver' => Event::query()
                ->where('status', 'pending - deanship of administration approval'),
            default => Event::query()
                ->where('creator_id', $user->id),
        };
    }

    /**
     * Retrieve pending requests matching any of the user's active roles.
     *
     * @param User $user
     * @param array<int,string>|null $roles Optional subset of role names to filter by.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function genericGetPendingRequestsV2(User $user, ?array $roles = []): \Illuminate\Database\Eloquent\Builder
    {
        $query = Event::query();

        // Get roles the user actually has
        $userRoles = $user->roles->pluck('name')->toArray();

        // If the user has no roles, end immediately
        if (empty($userRoles)) {
            // Return an empty query (no results)
            return $query->whereRaw('1 = 0');
        }

        // If roles were passed, only use ones the user actually has
        $activeRoles = !empty($roles)
            ? array_intersect($roles, $userRoles)
            : $userRoles;

        // If after filtering there are no valid roles, end the query
        if (empty($activeRoles)) {
            return $query->whereRaw('1 = 0');
        }

        // Group all role conditions together
        $query->where(function ($outer) use ($activeRoles, $user) {
            foreach ($activeRoles as $role) {
                //dd($user->roles->contains('name', $role));
                $outer->orWhere(function ($q) use ($role, $user) {
                    switch ($role) {
                        case 'advisor':
                            $q->where('organization_advisor_email', $user->email)
                                ->where('status', 'pending - advisor approval');
                            break;

                        case 'venue-manager':
                            $q->whereIn('venue_id', $user->department->venues()->pluck('id'))
                                ->where('status', 'pending - venue manager approval');
                            break;

                        case 'event-approver':
                            $q->where('status', 'pending - dsca approval');
                            break;

                        case 'deanship-of-administration-approver':
                            $q->where('status', 'pending - deanship of administration approval');
                            break;
                    }
                });
            }
        });

        return $query;
    }



    /**
     * Build a query of events previously acted upon by the approver.
     *
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function genericApproverRequestHistory(User $user): \Illuminate\Database\Eloquent\Builder
    {
        return Event::select('id', 'title', 'description', 'start_time', 'end_time', 'venue_id', 'organization_name', 'created_at')
            ->whereHas('history', function ($query) use ($user) {
                $query->where('approver_id', $user->id);
            })
            ->with(['history' => function ($query) use ($user) {
                $query->select('id', 'approver_id', 'event_id')
                    ->where('approver_id', $user->id);
            }]);
    }

    /**
     * Build an approver history query filtered by the provided roles.
     *
     * @param User $user
     * @param array<int,string>|null $roles
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function genericApproverRequestHistoryV2(User $user, ?array $roles = []): \Illuminate\Database\Eloquent\Builder
    {
        $query = Event::select(
            'id',
            'title',
            'description',
            'start_time',
            'end_time',
            'venue_id',
            'organization_name',
            'created_at'
        )
            ->whereHas('history', function ($q) use ($user) {
                $q->where('approver_id', $user->id);
            })
            ->with(['history' => function ($q) use ($user) {
                $q->select('id', 'approver_id', 'event_id', 'status_when_signed')
                    ->where('approver_id', $user->id);
            }]);

        $activeRoles = !empty($roles)
            ? $roles
            : $user->roles->pluck('name')->toArray();

        // Apply role-based filters inside the history relationship
        $query->whereHas('history', function ($outer) use ($activeRoles, $user) {
            $outer->where('approver_id', $user->id)
                ->where(function ($roleQuery) use ($activeRoles) {
                    foreach ($activeRoles as $role) {
                        $roleQuery->orWhere(function ($q) use ($role) {
                            switch ($role) {
                                case 'advisor':
                                    $q->where('status_when_signed', 'pending - advisor approval');
                                    break;

                                case 'venue-manager':
                                    $q->where('status_when_signed', 'pending - venue manager approval');
                                    break;

                                case 'event-approver':
                                    $q->where('status_when_signed', 'pending - dsca approval');
                                    break;

                                case 'deanship-of-administration-approver':
                                    $q->where('status_when_signed', 'pending - deanship of administration approval');
                                    break;
                            }
                        });
                    }
                });
        });

        return $query;
    }

    /**
     * Build a query for events that overlap the supplied event window.
     *
     * @param Event $event
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function conflictingEvents(Event $event)
    {
        return Event::where(function ($query) {
            $query->where('status', 'like', '%venue manager%')
                ->orWhere('status', 'like', '%dsca%')
                ->orWhere('status', 'like', '%approved%');
        })
            ->where('venue_id', $event->venue_id)
            ->where('id', '!=', $event->id)
            ->where(function ($query) use ($event) {
                $query
                    ->whereBetween('start_time', [$event->start_time, $event->end_time])        // Event starts within window
                    ->orWhereBetween('end_time', [$event->start_time, $event->end_time])        // Event ends within window
                    ->orWhere(function ($query) use ($event) {    // Event fully covers window
                        $query->where('start_time', '<=', $event->start_time)
                            ->where('end_time', '>=', $event->end_time);
                    });
            });
    }


    /**
     * Retrieve the documents attached to an event.
     *
     * @param Event $event
     * @return Collection
     */
    public function getEventDocuments(Event $event): Collection
    {
        return $event->documents;
    }

    //        public function getEventsForApproverDashboard(User $user): LengthAwarePaginator
    //        {
    //            // Create table that matches role with the status
    //            $stateApprover = [
    //                'advisor' => 'pending - advisor approval',
    //                'venue-manager' => 'pending - venue manager approval',
    //                'event-approver' => 'pending - dsca approval',
    //                'deanship-of-administration-approver' => 'pending - deanship of administration approval',
    //            ];
    //
    //            // Query event by status and return it as a paginator
    //            return

    //        }

    // Administrator's overrides

    // NOTE: REMOVE OR FOCUS IT ON VENUE MANAGER SINCE THE OTHER APPROVERS ARE NOT SPECIFIC (ROLE IS THE ONLY DETERMINING FACTOR).
    // THE LATTER IS BASICALLY DONE BY THE DIRECTOR
    //        public function reroutePendingEventApproval(Event $event, User $old_manager, User $new_manager): Event
    //        {
    //            return DB::transaction(function () use ($event, $old_manager, $new_manager) {
    //                // Change venue manager
    //
    //                // Get pending requests on the venue manager step
    //
    //                // Email the request creator, old manager, new manager and the director. To the advisor as well if available
    //            });
    //        }


    public function performManualOverride(Event $event, array $data, User $user, string $justification, string $action): Event
    {
        return DB::transaction(function () use ($event, $data, $user, $justification, $action) {
            // Build a filtered payload; only include provided keys and map aliases
            $updates = [];
            $map = [
                'venue_id'                      => 'venue_id',
                'organization_name'             => 'organization_name',
                'organization_advisor_name'     => 'organization_advisor_name',
                'organization_advisor_email'    => 'organization_advisor_email',
                'organization_advisor_phone'    => 'organization_advisor_phone',
                'creator_institutional_number'  => 'creator_institutional_number',
                'creator_phone_number'          => 'creator_phone_number',
                'title'                         => 'title',
                'description'                   => 'description',
                'start_time'                    => 'start_time',
                'end_time'                      => 'end_time',
                'status'                        => 'status',
                'guest_size'                    => 'guest_size',
                'handles_food'                  => 'handles_food',
                'use_institutional_funds'       => 'use_institutional_funds',
                'external_guest'                => 'external_guest',
            ];

            foreach ($map as $in => $col) {
                if (array_key_exists($in, $data)) {
                    $updates[$col] = $data[$in];
                }
            }

            Log::debug('performManualOverride: update payload', ['event_id' => $event->id, 'updates' => $updates]);

            if (!empty($updates)) {
                $result = $event->update($updates);
                Log::debug('performManualOverride: update result', ['event_id' => $event->id, 'result' => $result]);
            } else {
                Log::debug('performManualOverride: no updates to apply', ['event_id' => $event->id]);
            }

            // Append event history with justification; keep standardized label
            $statusWhenSigned = (string)($updates['status'] ?? $event->status ?? 'manual override');
            EventHistory::create([
                'event_id'          => $event->id,
                'approver_id'       => (int)$user->id,
                'action'            => 'manual override',
                'comment'           => (string) $justification,
                'status_when_signed' => $statusWhenSigned,
            ]);

            // Build audit context including justification and changed fields
            $actorName = trim(((string)($user->first_name ?? '')) . ' ' . ((string)($user->last_name ?? '')));
            if ($actorName === '') {
                $actorName = (string)($user->name ?? ($user->email ?? ''));
            }
            $ctx = ['meta' => [
                'action'        => (string) $action,
                'justification' => (string) $justification,
                'fields'        => array_keys($updates),
            ]];
            try {
                if (function_exists('request') && request()) {
                    $ctx = $this->auditService->buildContextFromRequest(request(), $ctx['meta']);
                }
            } catch (\Throwable) { /* no-http context */ }

            // Run audit trail with event id as target
            $this->auditService->logAdminAction(
                $user->id,
                'event',
                'ADMIN_OVERRIDE',
                (string) $event->id,
                $ctx
            );

            $refreshed = $event->refresh();
            Log::debug('performManualOverride: DB state after refresh', [
                'event_id' => $refreshed->id,
                'attributes' => $refreshed->getAttributes()
            ]);
            return $refreshed;
        });
    }

    /**
     * Cancel an event and append audit/history. Centralizes status value to avoid UI hardcoding.
     */
    public function cancelEvent(Event $event, User $user, string $justification): Event
    {
        return DB::transaction(function () use ($event, $user, $justification) {
            // Guard: only transition to cancelled from approved
            $updated = Event::where('id', $event->id)
                ->where('status', 'approved')
                ->update(['status' => 'cancelled']);

            if ($updated === 0) {
                // No state change; return current model without side effects
                return $event;
            }

            // Append history with standardized action label and justification
            EventHistory::create([
                'event_id' => $event->id,
                'action'   => 'cancelled',
                'comment'  => $justification ?: 'Event was cancelled.',
            ]);

            // Audit with justification in meta
            $this->auditService->logAdminAction(
                $user->id,
                'event',
                'ADMIN_OVERRIDE',
                (string) $event->id,
                ['meta' => ['justification' => (string) $justification]]
            );

            //                // Send email to the approvers
            $creatorEmail = $event->requester->email;
            $eventDetails = app(NotificationService::class)->getEventDetails($event);
            $approverEmails = app(EventHistoryService::class)->getEventApproverEmails($event);


            app(NotificationService::class)->dispatchCancellationNotifications(
                creatorEmail: $creatorEmail,
                recipientEmails: $approverEmails,
                eventDetails: $eventDetails,
                justification: $justification ?: 'Event was cancelled.',
                creatorRoute: route('user.request', ['event' => $event->id]),
                approverRoute: route('approver.history.request', ['eventHistory' => $event->id]),
            );


            return $event->refresh();
        });
    }


    // GET

    public function getBookedVenues(DateTime $startTime, DateTime $endTime): Collection
    {
        return Event::where('status', 'Approved')
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            })->get();
    }

    public function getAllEvents(array $filters = []): LengthAwarePaginator
    {
        $q = Event::query()->with(['venue', 'requester', 'categories']);

        if (!empty($filters['status'])) {
            $q->where('status', (string) $filters['status']);
        }
        if (!empty($filters['venue_id'])) {
            $q->where('venue_id', (int) $filters['venue_id']);
        }
        if (!empty($filters['organization_name'])) {
            $q->where('organization_name', (string) $filters['organization_name']);
        }
        if (!empty($filters['from'])) {
            $q->where('start_time', '>=', (string) $filters['from']);
        }
        if (!empty($filters['to'])) {
            $q->where('end_time', '<=', (string) $filters['to']);
        }

        return $q->orderByDesc('created_at')->paginate(15);
    }

    /**
     * Status flow mapping for approvals: current => next.
     * Central source of truth so UI and transitions stay consistent.
     *
     * @return array<string,string>
     */
    public function getStatusFlow(): array
    {
        return [
            'pending - advisor approval'        => 'pending - venue manager approval',
            'pending - venue manager approval'  => 'pending - dsca approval',
            // 'pending - dsca approval'        => 'pending - deanship of administration approval',
            // 'pending - deanship of administration approval' => 'approved',
            'pending - dsca approval'           => 'approved',
        ];
    }

    /**
     * All statuses from the status flow plus terminal states.
     * Returned sorted case-insensitively and unique.
     */
    public function getFlowStatuses(bool $includeTerminals = true): SupportCollection
    {
        $flow = $this->getStatusFlow();
        $statuses = collect(array_keys($flow))->merge(array_values($flow));
        if ($includeTerminals) {
            // Include terminal states except 'draft'
            $statuses = $statuses->merge(['rejected', 'cancelled', 'withdrawn']);
        }
        return $statuses
            ->map(fn($v) => trim((string) $v))
            ->filter(fn($v) => $v !== '')
            ->unique(fn($v) => mb_strtolower($v))
            ->sort(fn($a, $b) => strnatcasecmp($a, $b))
            ->values();
    }

    /**
     * Find a single event by id with common relationships.
     * Centralizes model access so Livewire views use services only.
     */
    public function findEventById(int $id): ?Event
    {
        if ($id <= 0) return null;
        return Event::query()
            ->with(['venue', 'requester', 'categories', 'documents'])
            ->find($id);
    }

    /**
     * Distinct event statuses from the database, sorted case-insensitively.
     * Service-level helper so UI does not touch models/constants.
     *
     * @return \Illuminate\Support\Collection<int,string>
     */
    public function getDistinctEventStatuses(): SupportCollection
    {
        return Event::query()
            ->whereNotNull('status')
            ->select('status')
            ->distinct()
            ->pluck('status')
            ->filter(fn($v) => is_string($v) && trim($v) !== '')
            ->map(fn($v) => trim((string)$v))
            ->unique(function ($v) { return mb_strtolower($v); })
            ->sort(function ($a, $b) { return strnatcasecmp($a, $b); })
            ->values();
    }

    /**
     * Distinct organization names from the database, sorted case-insensitively.
     *
     * @return \Illuminate\Support\Collection<int,string>
     */
    public function getDistinctOrganizations(): SupportCollection
    {
        return Event::query()
            ->whereNotNull('organization_name')
            ->select('organization_name')
            ->distinct()
            ->pluck('organization_name')
            ->filter(fn($v) => is_string($v) && trim($v) !== '')
            ->map(fn($v) => trim((string)$v))
            ->unique(function ($v) { return mb_strtolower($v); })
            ->sort(function ($a, $b) { return strnatcasecmp($a, $b); })
            ->values();
    }

    /**
     * All venue names from DB, sorted by name. Exposed via EventService to keep
     * EventIndex decoupled from models and other services.
     *
     * @return \Illuminate\Support\Collection<int,string>
     */
    public function listVenueNames(): SupportCollection
    {
        return Venue::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->pluck('name');
    }

    /**
     * Return normalized Event DTO rows for oversight UI, using service-only mapping.
     *
     * Filters: status, venue_id, organization_name, from, to
     *
     * @param array<string,mixed> $filters
     * @return \Illuminate\Support\Collection<int,array<string,mixed>>
     */
    public function getEventRows(array $filters = []): SupportCollection
    {
        $q = Event::query()->with(['venue', 'requester', 'categories']);
        if (!empty($filters['status'])) {
            $q->where('status', (string) $filters['status']);
        }
        if (!empty($filters['venue_id'])) {
            $q->where('venue_id', (int) $filters['venue_id']);
        }
        if (!empty($filters['organization_name'])) {
            $q->where('organization_name', (string) $filters['organization_name']);
        }
        if (!empty($filters['from'])) {
            $q->where('start_time', '>=', (string) $filters['from']);
        }
        if (!empty($filters['to'])) {
            $q->where('end_time', '<=', (string) $filters['to']);
        }

        $events = $q->orderByDesc('created_at')->get();

        return $events->map(fn($event) => $this->mapEventToRow($event))->values();
    }

    /**
     * Paginate normalized event rows for Livewire using DB-side filtering.
     *
     * @param array<string,mixed> $filters
     */
    public function paginateEventRows(array $filters = [], int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        $query = Event::query()
            ->with(['venue', 'requester', 'categories']);
        $search = trim((string)($filters['search'] ?? ''));
        if ($search !== '') {
            $like = '%' . mb_strtolower($search) . '%';
            $query->where(function ($builder) use ($like) {
                $builder->whereRaw('LOWER(title) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(organization_name) LIKE ?', [$like])
                    ->orWhereHas('requester', function ($requesterQuery) use ($like) {
                        $requesterQuery->whereRaw("LOWER(CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,''))) LIKE ?", [$like])
                            ->orWhereRaw('LOWER(email) LIKE ?', [$like]);
                    });
            });
        }
        if (!empty($filters['status'])) {
            $query->where('status', (string)$filters['status']);
        }
        if (!empty($filters['venue_id'])) {
            $query->where('venue_id', (int)$filters['venue_id']);
        } elseif (!empty($filters['venue_name'])) {
            $venueName = (string)$filters['venue_name'];
            $query->whereHas('venue', function ($venueQuery) use ($venueName) {
                $venueQuery->where('name', $venueName);
            });
        }
        if (!empty($filters['category'])) {
            $category = (string)$filters['category'];
            $query->whereHas('categories', function ($categoryQuery) use ($category) {
                $categoryQuery->where('name', $category);
            });
        }
            if (!empty($filters['from'])) {
                try {
                    $from = Carbon::parse(str_replace('T', ' ', (string)$filters['from']));
                    $query->where('start_time', '>=', $from);
                    } catch (\Throwable) {}
        }
            if (!empty($filters['to'])) {
                try {
                $to = Carbon::parse(str_replace('T', ' ', (string)$filters['to']));
                $query->where('end_time', '<=', $to);
            } catch (\Throwable) {
                // ignore invalid date
            }
        }
        $paginator = $query->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', max(1, $page));
        $paginator->setCollection($paginator->getCollection()->map(fn($event) => $this->mapEventToRow($event)));
        return $paginator;
    }

    /**
     * Sync the event's category based on a provided category NAME string.
     * If name doesn't resolve, leave categories unchanged.
     */
    public function syncEventCategoryByName(Event $event, string $categoryName): void
    {
        $name = trim($categoryName);
        if ($name === '') return;
        try {
            $cat = Category::where('name', $name)->first();
            if ($cat) {
                $event->categories()->sync([$cat->id]);
            }
        } catch (\Throwable) {
            // ignore
        }
    }

    /**
     * Venue options for filters with duplicate names disambiguated as "Name (CODE)".
     *
     * @return \Illuminate\Support\Collection<int,array{id:int,label:string}>
     */
    public function listVenuesForFilter(): SupportCollection
    {
        $venues = Venue::query()
            ->whereNull('deleted_at')
            ->select('id', 'name', 'code')
            ->get();

        // Build label, always appending code in parentheses if available
        $options = $venues->map(function ($v) {
            $name = (string)($v->name ?? '');
            $code = trim((string)($v->code ?? ''));
            $label = $name;
            if ($code !== '') {
                $label .= ' (' . $code . ')';
            }
            return ['id' => (int)$v->id, 'label' => $label];
        });

        // Natural, case-insensitive sort by label
        return $options
            ->sort(fn($a, $b) => strnatcasecmp($a['label'], $b['label']))
            ->values();
    }

    /**
     * Normalize an Event model to the array shape used by admin views.
     *
     * @param \App\Models\Event $event
     * @return array<string,mixed>
     */
    protected function mapEventToRow(Event $event): array
    {
        $formatDate = function ($value): string {
            try {
                if ($value instanceof DateTimeInterface) {
                    return Carbon::instance($value)->toDayDateTimeString();
                }
                if (!empty($value)) {
                    return Carbon::parse((string) $value)->toDayDateTimeString();
                }
            } catch (\Throwable) {
                // fall through to raw string cast
            }
            return (string) ($value ?? '');
        };

        $from = $formatDate($event->start_time ?? null);
        $to   = $formatDate($event->end_time ?? null);

        $fromEdit = '';
        $toEdit = '';
        try {
            if ($event->start_time instanceof DateTimeInterface) {
                $fromEdit = Carbon::instance($event->start_time)->format('Y-m-d\TH:i');
            } elseif (!empty($event->start_time)) {
                $fromEdit = Carbon::parse((string)$event->start_time)->format('Y-m-d\TH:i');
            }
        } catch (\Throwable) {
            $fromEdit = '';
        }
        try {
            if ($event->end_time instanceof DateTimeInterface) {
                $toEdit = Carbon::instance($event->end_time)->format('Y-m-d\TH:i');
            } elseif (!empty($event->end_time)) {
                $toEdit = Carbon::parse((string)$event->end_time)->format('Y-m-d\TH:i');
            }
        } catch (\Throwable) {
            $toEdit = '';
        }

        $requestor = method_exists($event, 'requester') && $event->requester
            ? trim(($event->requester->first_name ?? '') . ' ' . ($event->requester->last_name ?? ''))
            : ('User ' . (string)($event->creator_id ?? ''));

        $venueName = '';
        if (method_exists($event, 'venue') && $event->venue) {
            $name = (string)($event->venue->name ?? '');
            $code = trim((string)($event->venue->code ?? ''));
            $venueName = $name;
            if ($code !== '') {
                $venueName .= ' (' . $code . ')';
            }
        }

        $category = method_exists($event, 'categories') && $event->categories?->first()?->name
            ? $event->categories->first()->name
            : '';
        $categoryIds = method_exists($event, 'categories') && $event->categories
            ? $event->categories->pluck('id')->map(fn($id) => (int) $id)->all()
            : [];

        $status = (string)($event->status ?? '');
        $statusNorm = mb_strtolower(trim($status));
        $statusCancelled = str_contains($statusNorm, 'cancel');
        $statusDenied = str_contains($statusNorm, 'reject') || str_contains($statusNorm, 'deny');
        $statusWithdrawn = str_contains($statusNorm, 'withdraw');
        $statusCompleted = str_contains($statusNorm, 'completed');
        $statusApproved = $statusNorm === 'approved';

        $isPast = false;
        try {
            $endAt = null;
            if ($event->end_time instanceof DateTimeInterface) {
                $endAt = Carbon::instance($event->end_time);
            } elseif (!empty($event->end_time)) {
                $endAt = Carbon::parse((string)$event->end_time);
            }
            if ($endAt) {
                $isPast = $endAt->isPast();
            }
        } catch (\Throwable) {
            $isPast = false;
        }

        return [
            'id' => (int)($event->id ?? 0),
            'title' => (string)($event->title ?? 'Untitled'),
            'requestor' => $requestor,
            'organization' => (string)($event->organization_name ?? ''),
            'organization_advisor_name' => (string)($event->organization_advisor_name ?? ''),
            'organization_advisor_email' => (string)($event->organization_advisor_email ?? ''),
            'organization_advisor_phone' => (string)($event->organization_advisor_phone ?? ''),
            'venue' => $venueName,
            'venue_id' => (int)($event->venue_id ?? 0),
            'from' => $from,
            'to' => $to,
            'from_edit' => $fromEdit,
            'to_edit' => $toEdit,
            'status' => $status,
            'category' => $category,
            'category_ids' => $categoryIds,
            'updated' => now()->format('Y-m-d H:i'),
            'description' => (string)($event->description ?? ''),
            'attendees' => (int)($event->guest_size ?? 0),
            'handles_food' => (bool)($event->handles_food ?? false),
            'use_institutional_funds' => (bool)($event->use_institutional_funds ?? false),
            'external_guest' => (bool)($event->external_guest ?? false),
            'creator_institutional_number' => (string)($event->creator_institutional_number ?? ''),
            'creator_phone_number' => (string)($event->creator_phone_number ?? ''),
            'status_is_cancelled' => $statusCancelled,
            'status_is_denied' => $statusDenied,
            'status_is_withdrawn' => $statusWithdrawn,
            'status_is_completed' => $statusCompleted,
            'status_is_approved' => $statusApproved,
            'is_past_event' => $isPast,
        ];
    }

    /**
     * Return a single normalized row for the admin views.
     */
    public function getEventRowById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $event = Event::with(['venue', 'requester', 'categories'])->find($id);

        return $event ? $this->mapEventToRow($event) : null;
    }



    // [
    /**
     * Fetch paginated history for an approver with optional filters.
     *
     * @param User $user
     * @param array<string,mixed> $filters
     * @return LengthAwarePaginator
     */
    public function getApproverRequestHistory(User $user, $filters = [])
    {
        $query = Event::select('id', 'title', 'description', 'start_time', 'end_time', 'venue_id', 'organization_name', 'created_at')
            ->whereHas('history', function ($query) use ($user) {
                $query->where('approver_id', $user->id);
            })
            ->with(['history' => function ($query) use ($user) {
                $query->select('id', 'approver_id', 'event_id')
                    ->where('approver_id', $user->id);
            }]);

        // Apply venue_id filter if values are provided
        if (!empty($filters['venue_id'])) {
            $query->whereIn('venue_id', $filters['venue_id']);
        }

        // Apply organization_name filter if values are provided
        if (!empty($filters['organization_name'])) {
            $query->whereIn('organization_name', $filters['organization_name']);
        }

        // Apply category_id filter if values are provided (Many-to-Many relationship)
        if (!empty($filters['category_id'])) {
            $query->whereHas('categories', function ($query) use ($filters) {
                $query->whereIn('category_id', $filters['category_id']);
            });
        }

            return $query->paginate(15);
        }

        public function sendApproverEmails(Event $event){
//            pending - venue manager approval' => 'pending - dsca approval',
            $eventDetails = app(NotificationService::class)->getEventDetails($event);



            switch ($event->status)
            {
                case 'pending - venue manager approval':
                    $venue = app(VenueService::class)->getVenueById($event->venue_id);
                    if($venue != null){
                        $departmentEmployees = Department::findOrFail($venue->department_id)->employees();
                        $recipientEmails = $departmentEmployees->pluck('email')->toArray();
                        foreach ($recipientEmails as $recipientEmail) {
                            app(NotificationService::class)->dispatchApprovalRequiredNotification(
                                $recipientEmail, $eventDetails);
                        }

                    }

                    break;
                case 'pending - dsca approval':
                    $eventApproverEmails= app(UserService::class)->getUsersWithRole('4')
                        ->pluck('email')->toArray();
                    foreach ($eventApproverEmails as $approverEmail) {
                        app(NotificationService::class)->dispatchApprovalRequiredNotification(
                            $approverEmail, $eventDetails);
                    }
                    break;
//                case 'approved':
//                    $creatorEmail = app(UserService::class)->findUserById($event->creator_id)->email;
//                    $eventApproverEmails = app(EventHistoryService::class)->getEventApproverEmails($event);
//
//                    app(NotificationService::class)->dispatchSanctionedNotification(
//                        creatorEmail:$creatorEmail,
//                        recipientEmails: $eventApproverEmails,
//                        eventDetails: $eventDetails
//                    );
//                    break;

            }

        }


    public function sendCreatorUpdateEmail(Event $event, string $approverName, string $statusWhenApproved){
//            pending - venue manager approval' => 'pending - dsca approval',

//        $creatorEmail = app(UserService::class)->findUserById($event->creator_id)->email;


        $creatorEmail = $event->requester->email;
        $eventDetails = app(NotificationService::class)->getEventDetails($event);


        switch ($statusWhenApproved)
        {
            case 'pending - advisor approval':
                app(NotificationService::class)->dispatchUpdateNotification(
                    creatorEmail: $creatorEmail,
                    eventDetails: $eventDetails,
                    approverName: $approverName,
                    role: 'Advisor'
                );

                break;

            case 'pending - venue manager approval':

                app(NotificationService::class)->dispatchUpdateNotification(
                    creatorEmail: $creatorEmail,
                    eventDetails: $eventDetails,
                    approverName: $approverName,
                    role: 'Venue Manager'
                );

                break;
            case 'pending - dsca approval':
                app(NotificationService::class)->dispatchUpdateNotification(
                    creatorEmail: $creatorEmail,
                    eventDetails: $eventDetails,
                    approverName: $approverName,
                    role: 'DSCA Staff'
                );

                break;
            case 'approved':
                $eventApproverEmails = app(EventHistoryService::class)->getEventApproverEmails($event);

                app(NotificationService::class)->dispatchSanctionedNotification(
                    creatorEmail:$creatorEmail,
                    recipientEmails: $eventApproverEmails,
                    eventDetails: $eventDetails,
                    creatorRoute: route('user.request', ['event' => $event->id]),
                    approverRoute: route('approver.history.request', ['eventHistory' => $event->id])
                );

        }

    }

}
