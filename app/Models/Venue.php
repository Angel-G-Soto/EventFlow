<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venue extends Model
{
    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'venue_id';

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
        'v_name',
        'v_code',
        'v_department',
        'v_features',
        'v_capacity',
        'v_test_capacity',
    ];

    /**
     * Relationship between the Venue and User
     * @return BelongsTo
     */
//    public function user(): BelongsTo
//    {
//        return $this->belongsTo(User::class, 'user_id');
//    }

    /**
     * Relationship between the Venue and Use Requirement
     * @return HasMany
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(UseRequirements::class, 'use_requirement_id');
    }

    /**
     * Relationship between the Venue and Event
     * @return HasMany
     */
    public function requests(): HasMany
    {
        return $this->hasMany(Event::class, 'event_id');
    }
}
