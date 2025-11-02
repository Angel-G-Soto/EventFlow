<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;
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
        'code',
    ];

    /**
     * Relationship between the Department and Venues
     * @return HasMany
     */
    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class);
    }

    /**
     * Relationship between the Department and User
     * @return HasMany
     */
    public function employees(): HasMany
    {
        return $this->hasMany(User::class);
    }

    ////////////////////////////////////////////// Methods //////////////////////////////////////////////////////////

    /**
     * Retrieve the department director associated with this model.
     *
     * This method returns the first employee (User) related to this model
     * who has been assigned the role named "department-director".
     *
     * @return User|null
     */
    public function getDirector(): User|null
    {
        return $this->employees()
            ->with('roles')
            ->whereHas('roles', fn($q) => $q->where('name', 'department-director'))
            ->first();
    }
    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getEmployees(): Collection
    {
        return $this->employees()->get();
    }

    public function getEmployeeCount(): int
    {
        return $this->employees()->count();
    }

    public function getVenues(): Collection
    {
        return $this->venues()->get();
    }

    public function getVenueCount(): int
    {
        return $this->venues()->count();
    }

}
