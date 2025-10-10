<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventHistory extends Model
{
    protected $table = 'event_history';      // @var string The table associated with the model.
    protected $primaryKey = 'eh_id';        // @var string The primary key associated with the table.
    protected $connection = 'mariadb';      // @var string The database connection that should be used by the model.

    // Enable timestamps and specify custom timestamp column names
    public $timestamps = true;
    const CREATED_AT = 'eh_created_at';
    const UPDATED_AT = 'eh_updated_at';    

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
