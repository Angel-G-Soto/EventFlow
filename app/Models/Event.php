<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Event extends Model
{
    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'event_id';

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
        'e_student_id',
        'e_student_phone',
        'e_organization',
        'e_advisor_name',
        'e_advisor_email',
        'e_advisor_phone',
        'e_title',
        'e_category',
        'e_description',
        'e_status',
        'e_start_date',
        'e_end_date',
        'e_guests'
    ];

    /**
     * Relationship between the Event and User
     * @return BelongsTo
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relationship between the Event and Venue
     * @return BelongsTo
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class, 'venue_id');
    }

    /**
     * Relationship between the Event and Document
     * @return HasMany
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'document_id');
    }

    /**
     * Relationship between the Event and Event History
     * @return HasMany
     */
    public function history(): HasMany
    {
        return $this->hasMany(EventRequestHistory::class, 'event_request_id');
    }
}
