<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'v_name',
        'v_code',
        'v_features',
        'v_capacity',
        'v_test_capacity',
        'use_requirement_id',
        'department_id'
    ];

    /**
     * Relationship between the Venue and Requirement
     * @return HasMany
     */
    public function requirements(): HasMany
    {
        return $this->HasMany(VenueRequirement::class);
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

    /**
     * Returns the venue usage requirements
     *
     * @param int $requirementId
     * @return UseRequirements|null
     */
    public function getRequirementById(int $requirementId): ?UseRequirements
    {
        return $this->requirements()->where('use_requirement_id', $requirementId)->first();
    }

    /**
     *  Returns the requests associated to the venue
     *
     * @param int $eventId
     * @return Event|null
     */
    public function getRequestByEventId(int $eventId): ?Event
    {
        return $this->requests()->where('event_id', $eventId)->first();
    }
}
