<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventHistory extends Model
{
    protected $table = 'event_history';      // @var string The table associated with the model.
    protected $primaryKey = 'eh_id';        // @var string The primary key associated with the table.

    /**
     * The attributes that are mass assignable.
     * @var string[]
     */
    protected $fillable = [
        'event_id',         // FK to Event
        'user_id',          // FK to User
        'eh_status',
        'eh_comment',
    ];

    /**
     * Relationship between the Event Request History and Event
     * @return BelongsTo
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class,'event_id','event_id');
    }

    /**
     * Relationship between the Event Request History and User
     * @return BelongsTo
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class,'user_id','user_id');
    }

     /**
     * An accessor to get the timestamp in a human-readable "time ago" format.
     * This is ideal for displaying the history log in the UI.
     * Usage: $history->time_ago
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->eh_timestamp ? $this->eh_timestamp->diffForHumans() : 'N/A';
    }

    /**
     * Scope a query to only include history records for a specific event.
     * Usage: EventRequestHistory::forEvent($event)->get();
     */
    public function scopeForEvent(Builder $query, Event $event): Builder
    {
       return $query->where('event_id', $event->event_id);
    }

    /**
     * Scope a query to only include actions performed by a specific user.
     * Usage: EventRequestHistory::byActor($user)->get();
     */
    public function scopeByActor(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->user_id);
    }
}
