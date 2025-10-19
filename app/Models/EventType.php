<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
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

    protected static function booted()
    {
        static::creating(function ($event_type) {
            if (empty($event_type->et_code) && !empty($event_type->et_name)) {
                $event_type->et_code = Str::slug($event_type->et_name);
            }
        });

        static::updating(function ($event_type) {
            if ($event_type->isDirty('e_status')) {
                $event_type->et_code = Str::slug($event_type->et_name);
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     * @var string[]
     */
    protected $fillable = [
        'et_name', 
        'et_code',
        'et_is_active'
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

     /**
     * Scope a query to only include active event types.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('et_is_active', true);
    }
}