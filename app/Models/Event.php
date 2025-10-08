<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Http\Request;

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
        'e_guests',
        'venue_id'
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

    /**
     * Determine if the event has any attached documents.
     *
     * @return bool
     */
    public function hasDocuments(): bool
    {
        return $this->documents()->exists();
    }

    /**
     * Get the venue associated with the event.
     *
     * @return Venue|null
     */
    public function getVenue(): ?Venue
    {
        return $this->venue;
    }

//    /**
//     * This method is used to update or create an event based on the request input.
//     * NOTE: This function does not update the documents. Use the documents equivalent
//     * method to update or create files.
//     *
//     * @param Request $request
//     * @return Event
//     */
//    public function updateOrCreateEvent(Request $request): Event
//    {
//
//        if (request()->has('event_id'))
//        {
//            $event = self::find($request->event_id);
//        }
//        else
//        {
//            $event = new self();
//        }
//
//        if ($request->has('e_student_id')) $event->e_student_id = $request->e_student_id;
//        if ($request->has('e_student_phone')) $event->e_student_phone = $request->e_student_phone;
//        if ($request->has('e_organization')) $event->e_organization = $request->e_organization;
//        if ($request->has('e_advisor_name')) $event->e_advisor_name = $request->e_advisor_name;
//        if ($request->has('e_advisor_email')) $event->e_advisor_email = $request->e_advisor_email;
//        if ($request->has('e_advisor_phone')) $event->e_advisor_phone = $request->e_advisor_phone;
//        if ($request->has('e_title')) $event->e_title = $request->e_title;
//        if ($request->has('e_category')) $event->e_category = $request->e_category;
//        if ($request->has('e_description')) $event->e_description = $request->e_description;
//        if ($request->has('e_status')) $event->e_status = $request->e_status;
//        if ($request->has('e_start_date')) $event->e_start_date = $request->e_start_date;
//        if ($request->has('e_end_date')) $event->e_end_date = $request->e_end_date;
//        if ($request->has('e_guests')) $event->e_guests = $request->e_guests;
//        if ($request->has('venue_id')) $event->venue_id = $request->venue_id;
//
//        $event->save();
//
//        return $event;
//    }
}
