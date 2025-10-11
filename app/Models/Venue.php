<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;

class Venue extends Model
{
    protected $table = 'venue';                 // @var string The table associated with the model.
    protected $primaryKey = 'venue_id';         // @var string The primary key associated with the table.
    protected $connection = 'mariadb';          // @var string The database connection that should be used by the model.

    // Enable timestamps and specify custom timestamp column names
    public $timestamps = true;
    const CREATED_AT = 'v_created_at';
    const UPDATED_AT = 'v_updated_at';    

    /**
     * The attributes that are mass assignable.
     * @var string[]
     */
    protected $fillable = [
        'v_department',
        'v_manager_id',
        'v_name',
        'v_code',
        'v_features',
        'v_capacity',
        'v_test_capacity',
        'v_is_active'
    ];

    /**
     * Relationship between the Venue and Deparment
     * @return BelongsTo
     */
   public function deparment(): BelongsTo
   {
       return $this->belongsTo(Department::class);
   }

    /**
     * Get the manager (user) for the venue.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'v_manager_id', 'user_id');
    }

    /**
     * Relationship between the Venue and Use Requirement
     * @return HasMany
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(UseRequirements::class);
    }

    /**
     * Relationship between the Venue and Event
     * @return HasMany
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    // /**
    //  * Returns the venue usage requirements
    //  *
    //  * @param int $requirementId
    //  * @return UseRequirements|null
    //  */
    // public function getRequirementById(int $requirementId): ?UseRequirements
    // {
    //     return $this->requirements()->where('use_requirement_id', $requirementId)->first();
    // }

    // /**
    //  *  Returns the requests associated to the venue
    //  *
    //  * @param int $eventId
    //  * @return Event|null
    //  */
    // public function getRequestByEventId(int $eventId): ?Event
    // {
    //     return $this->requests()->where('event_id', $eventId)->first();
    // }


//    public function updateOrCreateVenue(Request $request): ?Venue
//    {
//        if ($request->has('venue_id')) {
//            $venue = self::find($request->venue_id);
//        } else {
//            $venue = new self();
//        }
//
//        if ($request->has('v_name')) $venue->v_name = $request->v_name;
//        if ($request->has('v_code')) $venue->v_code = $request->v_code;
//        if ($request->has('v_department')) $venue->v_department = $request->v_department;
//        if ($request->has('v_features')) $venue->v_features = $request->v_features;
//        if ($request->has('v_capacity')) $venue->v_capacity = $request->v_capacity;
//        if ($request->has('v_test_capacity')) $venue->v_test_capacity = $request->v_test_capacity;
//
//        $venue->save();
//
//        return $venue;
//    }
}
