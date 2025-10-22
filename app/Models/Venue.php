<?php

namespace App\Models;

use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'v_is_active',
        'open_time',
        'close_time'
    ];

     /**
     * The attributes that should be cast.
     * @var array
     */
    protected $casts = [
        'v_is_active' => 'boolean',
        'open_time' => 'datetime:H:i:s',
        'close_time' => 'datetime:H:i:s'
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
     * Checks if the venue is open at a specific point in time.
     *
     * @param DateTime $dateTime The time to check.
     * @return bool
     */
    public function isOpenAt($dateTime): bool
    {
        // If no hours are set, assume it's always open.
        if (!$this->open_time || !$this->close_time) {
            return true;
        }

        // We use Carbon for cleaner time comparisons.
        $timeToCheck = Carbon::parse($dateTime);

        // Check if the time falls between the opening and closing hours.
        return $timeToCheck->isBetween($this->open_time, $this->close_time);
    }

    /**
     * Checks if the venue is available for a given time interval.
     * A venue is available if it is open AND has no booking conflicts.
     *
     * @param DateTime $startTime The start of the interval.
     * @param DateTime $endTime The end of the interval.
     * @return bool
     */
    public function isAvailable($startTime, $endTime): bool
    {
        // 1. Check if the venue is open during the entire interval.
        if (!$this->isOpenAt($startTime) || !$this->isOpenAt($endTime)) {
            return false;
        }

        // 2. Check if there are any conflicting approved events.
        if ($this->hasConflict($startTime, $endTime)) {
            return false;
        }

        // If both checks pass, the venue is available.
        return true;
    }
}
