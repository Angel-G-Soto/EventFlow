<?php

namespace App\Models;

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
        'eh_action',
        'eh_comment',
    ];

    /**
     * Relationship between the Event Request History and Event
     * @return BelongsTo
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Relationship between the Event Request History and User
     * @return BelongsTo
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
