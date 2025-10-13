<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Http\Request;

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
        'current_approver_id',
        'venue_id',
        'e_organization_nexo_id',
        'e_advisor_name',
        'e_advisor_email',
        'e_advisor_phone',
        'e_organization_name',
        'e_title',
        'e_type',
        'e_description',
        'e_status',
        'e_status_code',
        'e_upload_status',
        'e_start_time',
        'e_end_time',
        'e_student_id',
        'e_student_phone',
        'e_guests',
        'e_alcohol_policy_agreement',
        'e_cleanup_policy_agreement',
    ];

    /**
     * The database connection that should be used by the model.
     *
     * @var string
     */
    protected $connection = 'mariadb';

    /**
     * Relationship between the Event and User
     * @return BelongsTo
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
        return $this->hasMany(EventRequestHistory::class);
    }
}
