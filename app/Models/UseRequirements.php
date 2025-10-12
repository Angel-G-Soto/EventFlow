<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UseRequirements extends Model
{
    protected $table = 'use_requirements';   // @var string The table associated with the model.
    protected $primaryKey = 'ur_id';        // @var string The primary key associated with the table.

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
