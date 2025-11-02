<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Collection;
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
    }

    /**
     * Relationship between the Venue and Event
     * @return HasMany
     */
    public function requests(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Relationship between the Venue and Department
     * @return BelongsTo
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

    /**
     * Returns an array of enabled features for this object.
     *
     * This method checks the internal `$features` array, where each element
     * is expected to be `1` (enabled) or `0` (disabled). It maps the enabled
     * entries to their corresponding names in the predefined `$allFeatures` list.
     *
     * @return array
     */
    public function getFeatures(): array
    {
        // The list of all possible features
        $allFeatures = ['online', 'multimedia', 'teaching', 'computers'];

        // Initialize the array to store the enabled features
        $enabledFeatures = [];

        // Iterate through the features and add the enabled ones
        foreach (str_split($this->features) as $index => $isEnabled) {
            if ($isEnabled === '1') { // If the feature at this index is enabled (1)
                $enabledFeatures[] = $allFeatures[$index]; // Add the corresponding feature to the array
            }
        }

        // Return the enabled features as an array
        return $enabledFeatures;
    }


    /**
     * Determine whether the venue is open at a given date and time.
     *
     * This method compares the provided time against the venue's defined opening
     * and closing hours. It supports venues that operate past midnight
     * (i.e., overnight schedules where closing time is earlier than opening time).
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
     * Check whether the venue has any approved event that conflicts
     * with the specified time range.
     *
     * A conflict occurs if there is any approved event that overlaps
     * with the provided start and end times — either starting, ending,
     * or fully encompassing the requested window.
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

    /**
     * Determine whether the venue is available for a given time range.
     *
     * The venue is considered available if:
     * - It is open at the specified start time.
     * - There are no approved events overlapping the provided time range.
     *
     * @param DateTime $startTime
     * @param DateTime $endTime
     * @return bool
     */
    public function isAvailable(DateTime $startTime, DateTime $endTime): bool
    {
        return $this->isOpenAt($startTime) && !$this->hasConflict($startTime, $endTime);
    }

}
