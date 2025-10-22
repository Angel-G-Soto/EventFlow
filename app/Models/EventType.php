<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
        'et_is_active'
    ];

    protected $casts = [
        'et_is_active' => 'boolean'
    ];

    /**
     * Get all of the event requests that are of this type.
     */
    public function eventRequests(): HasMany
    {
        return $this->hasMany(Event::class, 'event_type_id', 'event_type_id');
    }

     /**
     * Scope a query to only include active event types.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('et_is_active', true);
    }
}