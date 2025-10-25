<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Http\Request;
use App\Models\EventRequestHistory;


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
     * Get the user who created the event request.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'e_creator_id', 'user_id');
    }

    /**
     * Get the user who is currently assigned to approve the request.
     */
    public function currentApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'e_current_approver_id', 'user_id');
    }

    /**
     * The documents that belong to the event request.
     */
    public function documents(): HasMany
    {
        return $this->HasMany(Document::class);
    }

    /**
     * Get the history for the event request.
     */
    public function history(): HasMany
    {
        return $this->hasMany(EventHistory::class);
    }
}
