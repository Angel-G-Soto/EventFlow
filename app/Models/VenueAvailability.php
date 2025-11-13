<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VenueAvailability extends Model
{
    use HasFactory;

    protected $fillable = [
        'venue_id',
        'day',
        'opens_at',
        'closes_at',
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function coversRange(string $startTime, string $endTime): bool
    {
        return $this->opens_at <= $startTime && $this->closes_at >= $endTime;
    }
}
