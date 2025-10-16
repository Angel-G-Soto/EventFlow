<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;
    protected $table = 'department';                // @var string The table associated with the model.
    protected $primaryKey = 'department_id';        // @var string The primary key associated with the table.
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'd_name',             
        'd_code'
    ];

    /**
     * Get the users form the department.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'department_id', 'department_id');
    }

    /**
     * Relationship between the Department and Venues
     * @return HasMany
     */
    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class, 'department_id', 'department_id');
    }

     /**
     * Get the user who is the director of this department.
     * This centralizes the business logic for finding a department's director.
     *
     * @return User|null
     */
    public function getDirector(): ?User
    {
        // Find the first user in this department who has the 'department-director' role.
        return $this->users()->whereHas('roles', function ($query) {
            $query->where('r_code', 'department-director');
        })->first();
    }

    /**
     * Scope a query to find a department by its unique code.
     * This makes controller code cleaner and more readable.
     *
     * Usage: Department::findByCode('ENG')->first();
     */
    public function scopeFindByCode(Builder $query, string $code): void
    {
        $query->where('d_code', $code);
    }

     /**
     * An accessor to get a count of all users in the department.
     * This is useful for displaying stats on an admin dashboard.
     *
     * Usage: $department->user_count
     */
    public function getUserCountAttribute(): int
    {
        return $this->users()->count();
    }

    /**
     * An accessor to get a count of all venues in the department.
     *
     * Usage: $department->venue_count
     */
    public function getVenueCountAttribute(): int
    {
        return $this->venues()->count();
    }

}