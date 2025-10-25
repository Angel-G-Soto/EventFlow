<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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

}
