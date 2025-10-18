<?php

namespace App\Models;

use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Venue extends Model
{
    use softDeletes, HasFactory;

    protected $table = 'venue';                 // @var string The table associated with the model.
    protected $primaryKey = 'venue_id';         // @var string The primary key associated with the table.

    /**
     * The attributes that are mass assignable.
     * @var string[]
     */
    protected $fillable = [
        'department_id',    // FK to Department
        'manager_id',       // FK to User
        'v_name',
        'v_code',
        'v_features',
        'v_capacity',
        'v_test_capacity',
        'v_is_active'
    ];

     /**
     * The attributes that should be cast.
     * @var array
     */
    protected $casts = [
        'v_is_active' => 'boolean',
    ];

    /**
     * Relationship between the Venue and Deparment
     * @return BelongsTo
     */
   public function deparment(): BelongsTo
   {
       return $this->belongsTo(Department::class, 'department_id','department_id');
   }

    /**
     * Relationship between manager (User) and the Venue.
     * @return BelongsTo
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id', 'user_id');
    }

    /**
     * Relationship between the Venue and Use Requirement
     * @return HasMany
     */
    public function requirements(): HasMany
    {
        return $this->HasMany(VenueRequirement::class,'venue_id','venue_id');
    }

    /**
     * Relationship between the Venue and Event
     * @return HasMany
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class,'venue_id','venue_id');
    }

    /**
     * Relationship of opening hours for the venue.
     */
    public function openingHours(): HasMany
    {
        return $this->hasMany(OpeningHour::class, 'venue_id', 'venue_id');
    }

    /**
     * The event types that are EXCLUDED from this venue.
     * This defines the many-to-many relationship via the pivot table.
     * @return BelongsToMany
     */
    public function excludedEventTypes(): BelongsToMany
    {
        return $this->belongsToMany(EventType::class, 'venue_event_type_exclusions', 'venue_id', 'event_type_id');
    }

    /**
     * Checks if the venue is open during a specified time window.
     *
     * @param DateTime $startTime The desired start time for an event.
     * @param DateTime $endTime The desired end time for an event.
     * @return bool
     */
    public function isAvailableDuring(DateTime $startTime, DateTime $endTime): bool
    {
        // Carbon is a date/time library included with Laravel.
        $start = Carbon::instance($startTime);
        $end = Carbon::instance($endTime);

        // Get the day of the week (Monday = 1, Sunday = 7)
        $dayOfWeek = $start->dayOfWeekIso;

        // Find the opening hours for that specific day.
        $hoursForDay = $this->openingHours()->where('day_of_week', $dayOfWeek)->first();

        // If no record exists, the venue is closed on that day.
        if (!$hoursForDay) {
            return false;
        }

        // Check if the event's start time is on or after the venue's open time,
        // and the event's end time is on or before the venue's close time.
        // We compare only the time part of the dates.
        return $start->format('H:i:s') >= $hoursForDay->open_time &&
               $end->format('H:i:s') <= $hoursForDay->close_time;
    }

    /**
     * Checks if the venue is currently open right now.
     * @return bool
     */
    public function isOpenNow(): bool
    {
        $now = Carbon::now();
        $dayOfWeek = $now->dayOfWeekIso;
        $currentTime = $now->format('H:i:s');

        $hoursForToday = $this->openingHours()->where('day_of_week', $dayOfWeek)->first();

        if (!$hoursForToday) {
            return false;
        }

        return $currentTime >= $hoursForToday->open_time && $currentTime <= $hoursForToday->close_time;
    }

    public function getManagerNameAttribute(): string
    {
        // The '??' operator provides a default value if the manager relationship is null
        return $this->manager?->u_name ?? 'Not Assigned';
    }

    /**
     * A business logic method to check if the venue has a booking conflict.
     * This centralizes the logic for checking for approved, overlapping events.
     * @return bool
     */
    public function hasConflict(DateTime $startTime, DateTime $endTime): bool
    {
        return $this->eventRequests()
            ->where('e_status', 'Approved')
            ->where(function (Builder $query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
            })
            ->exists();
    }
    
    /**
     * Scope a query to only include active venues.
     * This makes controller and service code cleaner and more readable.
     *
     * Usage: Venue::active()->get();
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('v_is_active', true);
    }

    /**
     * Scope a query to only include venues managed by a specific user.
     *
     * Usage: Venue::managedBy($user)->get();
     */
    public function scopeManagedBy(Builder $query, User $manager): Builder
    {
        return $query->where('manager_id', $manager->user_id);
    }

    /**
     * A business logic method to check if a specific event type is disallowed.
     * This makes the service layer code much cleaner.
     *
     * Usage: if ($venue->isEventTypeExcluded($eventType)) { ... }
     */
    public function isEventTypeExcluded(EventType $eventType): bool
    {
        // This checks if a record exists in the pivot table for this venue and event type.
        return $this->excludedEventTypes()->wherePivot('event_type_id', $eventType->event_type_id)->exists();
    }
}
