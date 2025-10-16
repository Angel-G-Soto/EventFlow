<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpeningHour extends Model
{
    use HasFactory;

    protected $table = 'opening_hour';
    protected $primaryKey = 'oh_id';
    public $timestamps = false; // This table doesn't need created_at/updated_at

    protected $fillable = [
        'venue_id',
        'day_of_week',
        'open_time',
        'close_time',
    ];

    /**
     * Get the venue that these opening hours belong to.
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class,'venue_id','venue_id');
    }

    /**
     * An accessor to get the name of the day.
     * Usage: $openingHour->day_name
     */
    public function getDayNameAttribute(): string
    {
        return match ((int) $this->day_of_week) {
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
            default => 'Unknown',
        };
    }

    /**
     * An accessor to get the open time in a friendly format (e.g., "8:00 AM").
     * Usage: $openingHour->formatted_open_time
     */
    public function getFormattedOpenTimeAttribute(): string
    {
        return Carbon::parse($this->open_time)->format('g:i A');
    }

    /**
     * An accessor to get the close time in a friendly format (e.g., "5:00 PM").
     * Usage: $openingHour->formatted_close_time
     */
    public function getFormattedCloseTimeAttribute(): string
    {
        return Carbon::parse($this->close_time)->format('g:i A');
    }

    /**
     * An accessor to get the full, human-readable time range.
     * Usage: $openingHour->formatted_range
     */
    public function getFormattedRangeAttribute(): string
    {
        return sprintf(
            '%s: %s - %s',
            $this->day_name,
            $this->formatted_open_time,
            $this->formatted_close_time
        );
    }

    /**
     * Scope a query to only include hours for a specific day of the week.
     * Usage: OpeningHour::forDay(1)->get(); // Get all hours for Monday
     */
    public function scopeForDay(Builder $query, int $dayOfWeek): Builder
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    /**
     * Scope a query to find all opening hour records that are active at a given time.
     * Usage: OpeningHour::openAt(Carbon::now())->get();
     */
    public function scopeOpenAt(Builder $query, Carbon $dateTime): Builder
    {
        return $query->where('day_of_week', $dateTime->dayOfWeekIso)
              ->where('open_time', '<=', $dateTime->format('H:i:s'))
              ->where('close_time', '>=', $dateTime->format('H:i:s'));
    }
}