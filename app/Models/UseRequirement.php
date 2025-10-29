<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UseRequirement extends Model
{
<<<<<<< HEAD:app/Models/UseRequirements.php
    protected $table = 'use_requirements';   // @var string The table associated with the model.
    protected $primaryKey = 'ur_id';        // @var string The primary key associated with the table.
=======
    /** @use HasFactory<\Database\Factories\UseRequirementFactory> */
    use HasFactory;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
>>>>>>> origin/restructuring_and_optimizations:app/Models/UseRequirement.php

    protected $fillable = [
<<<<<<< HEAD:app/Models/UseRequirements.php
        'venue_id',         // FK to Venue
        'ur_doc_drive',
        'ur_instructions',
=======
        'venue_id',
        'name',
        'hyperlink',
        'description',
>>>>>>> origin/restructuring_and_optimizations:app/Models/UseRequirement.php
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
