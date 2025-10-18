<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $table = 'role';                  // @var string The table associated with the model.
    protected $primaryKey = 'role_id';          // @var string The primary key associated with the table.

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'r_name',             
        'r_code'
    ];

    /**
     * The users that belong to the role.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_assignment', 'role_id', 'user_id');
    }
     /**
     * Scope a query to find a role by its unique machine-readable code.
     * This makes controller and service code much cleaner.
     *
     * Usage: Role::findByCode('system-admin')->first();
     */
    public function scopeFindByCode(Builder $query, string $code): Builder
    {
        return $query->where('r_code', $code);
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
    }
}
