<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Http\Request;
use \Illuminate\Database\Eloquent\Collection;

class Event extends Model
{
    use HasFactory;
    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     * @var string[]
     */
    protected $fillable = [
        'creator_id',
        'venue_id',
        'organization_nexo_id',
        'organization_nexo_name',
        'organization_advisor_email',
        'organization_advisor_name',
        'organization_advisor_phone',
        'student_number',
        'student_phone',
        'title',
        'description',
        'start_time',
        'end_time',
        'status',
        'guests',
        'handles_food',
        'use_institutional_funds',
        'external_guest',
    ];

    /**
     * The database connection that should be used by the model.
     *
     * @var string
     */
    protected $connection = 'mariadb';

    //////////////////////////////////// RELATIONS //////////////////////////////////////////////////////

    /**
     * Relationship between the Event and User
     * @return BelongsTo
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Relationship between the Event and Venue
     * @return BelongsTo
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * Relationship between the Event and Document
     * @return HasMany
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Relationship between the Event and Event History
     * @return HasMany
     */
    public function history(): HasMany
    {
        return $this->hasMany(EventHistory::class);
    }

    /**
     *
     * @return BelongsToMany
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }


    //////////////////////////////////// METHODS //////////////////////////////////////////////////////
    public function getHistory(): Collection
    {
        return $this->history()->get();
    }

    public function getCurrentApprover(): User
    {
        return $this->history()->orderBy('created_at', 'desc')->first()->approver;
    }

    public function getCurrentState(): User
    {
        return $this->status;
    }

    public function getCategories(): Collection
    {
        return $this->categories()->get();
    }

    public function getVenue(): Venue
    {
        return $this->venue;
    }

    public function getEventsByState(?string $state): Collection
    {
        if (!in_array(
            strtolower($state),
            ['draft', 'pending approval - advisor', 'pending approval - manager', 'pending approval - event approver', 'pending approval - deanship of administration', 'approved', 'rejected', 'cancelled', 'withdrawn', 'completed']
        )
        ){
            throw new \InvalidArgumentException('');
        }
        elseif ($state === null) return $this->events()->get();
        return $this->events()->where('state', strtolower($state))->get();
    }
}
