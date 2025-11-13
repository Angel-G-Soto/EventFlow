<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventHistory extends Model
{
    use HasFactory;
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
        'event_id',
        'approver_id',
        'action',
        'comment',
        'status_when_signed',
    ];

    //////////////////////////////////// RELATIONS //////////////////////////////////////////////////////
    /**
     * Relationship between the Event History and Event
     * @return BelongsTo
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Relationship between the Event History and User
     * @return BelongsTo
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    //////////////////////////////////// METHODS //////////////////////////////////////////////////////
    public function getActionTextAttribute(): string
    {
        return $this->action;
    }

    public function getCommentTextAttribute(): string
    {
        return $this->comment;
    }

    public function getSimpleStatus(): string
    {
        if (str_contains($this->status_when_signed, 'advisor')) {
            return 'Advisor Approval';
        }
        if (str_contains($this->status_when_signed, 'venue manager')) {
            return 'Venue Manager Approval';
        }
        if (str_contains($this->status_when_signed, 'dsca')) {
            return 'DSCA Approval';
        }
        else {
            return ucfirst($this->status_when_signed);
        }
    }
}
