<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UseRequirements extends Model
{
    protected $table = 'use_requirements';   // @var string The table associated with the model.
    protected $primaryKey = 'ur_id';        // @var string The primary key associated with the table.
    protected $connection = 'mariadb';      // @var string The database connection that should be used by the model.

    // Enable timestamps and specify custom timestamp column names
    public $timestamps = true;
    const CREATED_AT = 'ur_created_at';
    const UPDATED_AT = 'ur_updated_at';    

    protected $fillable = [
        'venue_id',         // FK to Venue
        'ur_doc_drive',
        'ur_instructions',
    ];

    /**
     * Relationship between the Use Requirement and Venue
     * @return BelongsTo
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }
}
