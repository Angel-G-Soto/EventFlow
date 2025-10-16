<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $table = 'event';              // @var string The table associated with the model.
    protected $primaryKey = 'event_id';      // @var string The primary key associated with the table.

  

    /**
     * The attributes that are mass assignable.
     * @var string[]
     */
    protected $fillable = [
        'creator_id',             // FK (Static) to User
        'current_approver_id',    // FK (Dynamic) to User
        'venue_id',                 // FK to Venue
        'e_student_id',
        'e_student_phone',
        'e_title',
        'e_category',               // Define what will fill this field
        'e_description',
        'e_status',
        'e_status_code',
        'e_start_date',
        'e_end_date',
        'e_guests',                 // Unsure of this field
        // Nexo Data
        'e_organization_nexo_id',
        'e_organization_nexo_name',
        'e_advisor_name',
        'e_advisor_email',
        'e_advisor_phone'
    ];

    /**
     * Get the venue for the event request.
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class, 'venue_id','venue_id');
    }

    /**
     * Get the user who created the event request.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id', 'user_id');
    }

    /**
     * Get the user who is currently assigned to approve the request.
     */
    public function currentApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_approver_id', 'user_id');
    }

    /**
     * The documents that belong to the event request.
     */
    public function documents(): HasMany
    {
        return $this->HasMany(Document::class, 'event_id','event_id');
    }

    /**
     * Get the history for the event request.
     */
    public function history(): HasMany
    {
        return $this->hasMany(EventHistory::class,'event_id','event_id');
    }

     /**
     * An accessor to check if the event is in a pending (non-terminal) state.
     * Usage: if ($eventRequest->is_pending) { ... }
     */
    public function getIsPendingAttribute(): bool
    {
        $terminalStates = ['Approved', 'Denied', 'Canceled', 'Withdrawn', 'Completed'];
        return !in_array($this->er_status, $terminalStates);
    }

    /**
     * Scope a query to only include events awaiting approval from a specific user.
     * Usage: EventRequest::awaitingApprovalFrom($user)->get();
     */
    public function scopeAwaitingApprovalFrom(Builder $query, User $user): void
    {
        $query->where('current_approver_id', $user->user_id);
    }

      /**
     * Scope a query to only include upcoming, approved events.
     * Usage: EventRequest::upcoming()->get();
     */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where('er_status', 'Approved')->where('start_time', '>=', now());
    }

    /**
     * A business logic method to check if this event conflicts with a given time range.
     */
    public function isOverlappingWith(DateTime $startTime, DateTime $endTime): bool
    {
        $eventStart = $this->start_time;
        $eventEnd = $this->end_time;

        // The logic to check for any overlap between the two time ranges.
        return $startTime < $eventEnd && $endTime > $eventStart;
    }
}
