<?php

namespace App\Models;

<<<<<<< HEAD
=======
use Illuminate\Database\Eloquent\Factories\HasFactory;
>>>>>>> origin/restructuring_and_optimizations
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventHistory extends Model
{
<<<<<<< HEAD
    protected $table = 'event_history';      // @var string The table associated with the model.
    protected $primaryKey = 'eh_id';        // @var string The primary key associated with the table.
=======
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
>>>>>>> origin/restructuring_and_optimizations

    /**
     * The attributes that are mass assignable.
     * @var string[]
     */
    protected $fillable = [
<<<<<<< HEAD
        'event_id',         // FK to Event
        'user_id',          // FK to User
        'eh_action',
        'eh_comment',
    ];

    /**
     * Relationship between the Event Request History and Event
=======
        'event_id',
        'approver_id',
        'action',
        'comment',
    ];

    //////////////////////////////////// RELATIONS //////////////////////////////////////////////////////
    /**
     * Relationship between the Event History and Event
>>>>>>> origin/restructuring_and_optimizations
     * @return BelongsTo
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
<<<<<<< HEAD
     * Relationship between the Event Request History and User
     * @return BelongsTo
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class);
=======
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
>>>>>>> origin/restructuring_and_optimizations
    }
}
