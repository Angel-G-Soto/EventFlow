<?php

namespace App\Models;

<<<<<<< HEAD
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;
=======
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;
>>>>>>> origin/restructuring_and_optimizations
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
<<<<<<< HEAD
        'd_name',
        'd_code',
=======
        'name',
        'code',
>>>>>>> origin/restructuring_and_optimizations
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
<<<<<<< HEAD
    public function managers(): HasMany
    {
        return $this->hasMany(User::class);
    }
=======
    public function employees(): HasMany
    {
        return $this->hasMany(User::class);
    }

    ///////////////////////// VALIDATE FUNCTIONALITY OF THE ROLES ///////////////////////////////////////
    public function getDirector(): User|null
    {
        $employees = $this->employees()->get();

        foreach ($employees as $employee) {
            if ($employee->role()->name == 'department-director') {return $employee;}
        }
        return null;
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

>>>>>>> origin/restructuring_and_optimizations
}
