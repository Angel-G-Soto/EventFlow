<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UseRequirements extends Model
{
    use HasFactory;
    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

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
        'us_doc_drive',
        'us_instructions',
        'us_alcohol_policy',
        'us_cleanup_policy',
        'id',
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
