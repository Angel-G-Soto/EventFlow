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
        'organization_name',
        'organization_advisor_email',
        'organization_advisor_name',
        //'organization_advisor_phone',
        'creator_institutional_number',
        'creator_phone_number',
        'title',
        'description',
        'start_time',
        'end_time',
        'status',
        'guest_size',
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

    /**
     * Retrieve the full approval or modification history for this model.
     *
     * This method returns all related history records associated with the current model,
     * typically representing approval steps, changes, or actions performed over time.
     *
     * @return Collection
     */
    public function getHistory(): Collection
    {
        return $this->history()->get();
    }

    /**
     * Get the user who most recently acted as approver for this model.
     *
     * This method fetches the latest history record (by creation date) and returns
     * the associated approver user instance. It assumes that at least one history
     * record exists; otherwise, a null reference error may occur.
     *
     * @return User
     */
    public function getCurrentApprover(): User
    {
        return $this->history()->orderBy('created_at', 'desc')->first()->approver;
    }


    public function getSimpleStatus(): string
    {
        if (str_contains($this->status, 'advisor')) {
            return 'Awaiting Advisor Approval';
        }
        if (str_contains($this->status, 'venue manager')) {
            return 'Awaiting Venue Manager Approval';
        }
        if (str_contains($this->status, 'dsca')) {
            return 'Awaiting DSCA Approval';
        }
        else {
            return ucfirst($this->status);
        }
    }

}
