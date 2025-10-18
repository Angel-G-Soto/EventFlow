<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * EventType
 *
 * Represents a category of event (e.g., Lecture, Meeting, Concert).
 */
class EventType extends Model
{
    use HasFactory;

    protected $table = 'event_type';
    protected $primaryKey = 'event_type_id';

    /**
     * The attributes that are mass assignable.
     * @var string[]
     */
    protected $fillable = [
        'et_name', 
        'et_code',
    ];

    /**
     * Get all of the event requests that are of this type.
     */
    public function eventRequests(): HasMany
    {
        return $this->hasMany(Event::class, 'event_type_id', 'event_type_id');
    }

    /**
     * The venues that EXCLUDE this event type.
     * This defines the many-to-many relationship through the pivot table.
     */
    public function excludedVenues(): BelongsToMany
    {
        return $this->belongsToMany(Venue::class, 'venue_event_type_exclusions', 'event_type_id', 'venue_id');
    }
}