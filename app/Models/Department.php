<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;
    protected $table = 'department';                // @var string The table associated with the model.
    protected $primaryKey = 'department_id';        // @var string The primary key associated with the table.

    protected static function booted()
    {
        static::creating(function ($department) {
            if (empty($department->d_code) && !empty($department->d_name)) {
                $department->d_code = Str::slug($department->d_name);
            }
        });

        static::updating(function ($department) {
            if ($department->isDirty('e_status')) {
                $department->e_status_code = Str::slug($department->e_status);
            }
        });
    }

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
     * Get the requests form the department.
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
     * Usage: Department::findByIdentifier('Deparment Engineering' || department-engineering)->first();
     */
    public function scopeFindByIdentifier(Builder $query, string $deptIndentifier): Builder
    {
        return $query->where('d_code', $deptIndentifier)->orWhere('d_name', $deptIndentifier);
    }

     /**
     * An accessor to get a count of all requests in the department.
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
