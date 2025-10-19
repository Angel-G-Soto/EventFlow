<?php

namespace App\Services;

use App\Models\EventType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class EventTypeService
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Retrieves a paginated list of all event types.
     */
    public function getAllEventTypes(): LengthAwarePaginator
    {
        return EventType::orderBy('et_name')->paginate(25);
    }

    /**
     * Creates a new event type.
     * 
     * @param string Name of event type to be created
     * @param User $admin The administrator taking the action. 
     * @return EventType The newly created Eloquent EventType object.
     */
    public function createEventType(string $name, User $admin): EventType
    {
        // 1. Create the new EvenType based on data values
        $eventType = EventType::create(['et_name'=>$name]);

        // 2. Audit the action
        $actionCode =  'EVENT_TYPE_CREATED';
        $description = "Created event type '{$eventType->et_name}'.";

        $this->auditService->logAdminAction($admin->user_id, $admin->u_name, $actionCode, $description);

        // 3. Return created EventType object
        return $eventType;
    }

    /**
     * Updates an existing event type's details.
     * 
     * @param EventType The event type to updated.
     * @param string Modifiable field
     * @param User $admin The administrator taking the action. 
     * @return EventType The newly updated Eloquent EventType object.
     */
    public function updateEventType(EventType $eventType, string $name, User $admin): EventType
    {
        // 1. Update the target event type based on data values.
        $eventType->update(['et_name'=> $name]);

        // 2. Audit the action
        $actionCode =  'EVENT_TYPE_UPDATED';
        $description = "Updated event type '{$eventType->et_name}' .";

        $this->auditService->logAdminAction($admin->user_id, $admin->u_name, $actionCode, $description);

        // 3. Return updated EventType object
        return $eventType;
    }

    /**
     * Suspends an event type by setting it to inactive.
     * 
     * @param EventType The event type to be deactivated.
     * @param User The administrator taking the action.
     * @return EventType Deactivated Eloquent EventType object. 
     */
    public function suspendEventType(EventType $eventType, User $admin): EventType
    {
        // Wrap in DB::transation to ensure that both steps are executed
        DB::transaction(function () use ($eventType) {
            // 1. Set event type to inactive
            $eventType->update(['et_is_active' => false]);

            // 2. Detach this event type from all venues it was previously excluded from. This cleans up the pivot table.
            $eventType->excludedInVenues()->detach();
        });
       
        // 3. Audit the action
        $actionCode = 'EVENT_TYPE_SUSPENDED';
        $description = "Suspended event type '{$eventType->et_name}'.";

        $this->auditService->logAdminAction($admin->user_id, $admin->u_name, $actionCode, $description);        

        // 4. Return updated EventType object
        return $eventType;
    }
}