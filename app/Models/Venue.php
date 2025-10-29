<?php

namespace App\Models;

<<<<<<< HEAD
=======
use DateTime;
use Illuminate\Database\Eloquent\Collection;
>>>>>>> origin/restructuring_and_optimizations
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Venue extends Model
{
    use softDeletes, HasFactory;
    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The database connection that should be used by the model.
     *
     * @var string
     */
    protected $connection = 'mariadb';

    /**
     * The attributes that are mass assignable.
     * @var string[]
     */
    protected $fillable = [
<<<<<<< HEAD
        'v_department',
        'v_manager_id',
        'v_name',
        'v_code',
        'v_features',
        'v_capacity',
        'v_test_capacity',
        'use_requirement_id',
        'department_id'
    ];

    /**
     * Relationship between the Venue and Use Requirement
     * @return BelongsTo
     */
    public function requirements(): BelongsTo
    {
        return $this->belongsTo(UseRequirements::class, 'use_requirement_id');
=======
        'manager_id',
        'department_id',
        'name',
        'code',
        'features',
        'capacity',
        'test_capacity',
        'opening_time',
        'closing_time',
    ];

    /**
     * Relationship between the Venue and Requirement
     * @return HasMany
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(UseRequirement::class, 'venue_id');
>>>>>>> origin/restructuring_and_optimizations
    }

    /**
     * Relationship between the Venue and Event
     * @return HasMany
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Relationship between the Venue and Department
     * @return BelongsTo
<<<<<<< HEAD
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Returns the venue usage requirements
     *
     * @param int $requirementId
     * @return UseRequirements|null
=======
>>>>>>> origin/restructuring_and_optimizations
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    //////////////////////////////////////////// METHODS //////////////////////////////////////////////////

    public function getDepartmentID(): int
    {
        return $this->department_id;
    }

    public function getFeatures(): array
    {
        // The list of all possible features
        $allFeatures = ['online', 'multimedia', 'teaching', 'computers'];

        // Initialize the array to store the enabled features
        $enabledFeatures = [];

        // Iterate through the features and add the enabled ones
        foreach ($this->features as $index => $isEnabled) {
            if ($isEnabled === 1) { // If the feature at this index is enabled (1)
                $enabledFeatures[] = $allFeatures[$index]; // Add the corresponding feature to the array
            }
        }

        // Return the enabled features as an array
        return $enabledFeatures;
    }


    /**
     * Verifies if the venue is open at the given date.
     *
     * @param DateTime $date
     * @return bool
     */
    public function isOpenAt(DateTime $date): bool
    {
        $hour = $date->format('H:i:s');
        $start = $this->opening_time;
        $end = $this->closing_time;

        if ($start <= $end) {
            // Normal Hours
            return $hour >= $start && $hour <= $end;
        } else {
            // Overnight Hours
            return $hour >= $start || $hour <= $end;
        }
    }

    /**
     * Verifies if the venue has an approved event for the given hours.
     *
     * @param DateTime $startTime
     * @param DateTime $endTime
     * @return bool
     */
    public function hasConflict(DateTime $startTime, DateTime $endTime): bool
    {
        return Event::where('status', 'approved')
            ->where('venue_id', $this->id)
            ->where(function ($query) use ($startTime, $endTime) {
                $query
                    ->whereBetween('start_time', [$startTime, $endTime])        // Event starts within window
                    ->orWhereBetween('end_time', [$startTime, $endTime])        // Event ends within window
                    ->orWhere(function ($query) use ($startTime, $endTime) {    // Event fully covers window
                        $query->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                    });
            })
            ->exists();
    }
<<<<<<< HEAD
=======

    /**
     * Verifies the availability of a venue for a given hour.
     *
     * @param DateTime $startTime
     * @param DateTime $endTime
     * @return bool
     */
    public function isAvailable(DateTime $startTime, DateTime $endTime): bool
    {
        return $this->isOpenAt($startTime) && !$this->hasConflict($startTime, $endTime);
    }

>>>>>>> origin/restructuring_and_optimizations
}
