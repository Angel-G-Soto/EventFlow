<?php

namespace App\Models;

<<<<<<< HEAD
=======
use Illuminate\Database\Eloquent\Builder;
>>>>>>> origin/restructuring_and_optimizations
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

<<<<<<< HEAD
    protected $table = 'role';                  // @var string The table associated with the model.
    protected $primaryKey = 'role_id';          // @var string The primary key associated with the table.
=======
    protected $table = 'roles';                  // @var string The table associated with the model.
>>>>>>> origin/restructuring_and_optimizations

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
<<<<<<< HEAD
        'r_name',             
        'r_code'
=======
        'name',
        'code'
>>>>>>> origin/restructuring_and_optimizations
    ];

    /**
     * The users that belong to the role.
     */
    public function users(): BelongsToMany
    {
<<<<<<< HEAD
        return $this->belongsToMany(User::class, 'Role Assignment', 'role_id', 'user_id');
=======
        return $this->belongsToMany(User::class, 'user_role');
    }
    /**
     * Scope a query to find a role by its unique machine-readable code.
     * This makes controller and service code much cleaner.
     *
     * Usage: Role::findByCode('system-admin')->first();
     */
    public function scopeFindByCode(Builder $query, string $code): Builder
    {
        return $query->where('code', $code);
    }

    /**
     * Assign this role to a given user.
     * A convenient, readable helper method.
     *
     * Usage: $adminRole->assignTo($user);
     */
    public function assignTo(User $user): void
    {
        $this->users()->syncWithoutDetaching($user->user_id);
    }

    /**
     * Remove this role from a given user.
     *
     * Usage: $adminRole->removeFrom($user);
     */
    public function removeFrom(User $user): void
    {
        $this->users()->detach($user->user_id);
    }

    /**
     * An accessor to get a count of all users who have this role.
     * This is useful for displaying stats on an admin dashboard.
     * Note: For performance on large lists, use withCount('users') in your query instead.
     *
     * Usage: $adminRole->user_count
     */
    public function getUserCountAttribute(): int
    {
        return $this->users()->count();
>>>>>>> origin/restructuring_and_optimizations
    }
}
