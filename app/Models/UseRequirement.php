<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UseRequirement extends Model
{
<<<<<<< HEAD
    use HasFactory;
=======
    /** @use HasFactory<\Database\Factories\UseRequirementFactory> */
    use HasFactory;

>>>>>>> origin/restructuring_and_optimizations
    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = [
<<<<<<< HEAD
        'us_doc_drive',
        'us_instructions',
        'us_alcohol_policy',
        'us_cleanup_policy',
        'id',
=======
        'venue_id',
        'name',
        'hyperlink',
        'description',
>>>>>>> origin/restructuring_and_optimizations
    ];

    /**
     * Relationship between the Use Requirement and Venue
     * @return HasMany
     */
    public function venue(): HasMany
    {
        return $this->HasMany(Venue::class);
    }
}
