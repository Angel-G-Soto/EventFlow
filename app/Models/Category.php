<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
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
        'name',
    ];

    /**
     * Relationship between category and events
     * @return BelongsToMany
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class);
    }

    public function getEventsByState(?string $state): Collection
    {
        if (!in_array(
            strtolower($state),
            ['draft', 'pending approval - advisor', 'pending approval - manager', 'pending approval - event approver', 'pending approval - deanship of administration', 'approved', 'rejected', 'cancelled', 'withdrawn', 'completed']
            )
        ){
            throw new \InvalidArgumentException('');
        }
        elseif ($state === null) return $this->events()->get();
        return $this->events()->where('state', strtolower($state))->get();
    }


}
