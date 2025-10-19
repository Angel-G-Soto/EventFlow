<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Venue extends Model
{
    use softDeletes, HasFactory;
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
        'v_name',
        'v_code',
        'v_features',
        'v_capacity',
        'v_test_capacity',
        'use_requirement_id',
        'department_id',
        'category_id',
        'id'
    ];

    /**
     * Relationship between the Venue and Requirement
     * @return HasMany
     */
    public function requirements(): HasMany
    {
        return $this->HasMany(UseRequirement::class);
    }

    /**
     * Relationship between the Venue and Event
     * @return HasMany
     */
    public function requests(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Relationship between the Venue and Department
     * @return BelongsTo
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }
}
