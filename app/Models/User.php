<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'user';              // @var string The table associated with the model.
    protected $primaryKey = 'user_id';      // @var string The primary key associated with the table.

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'department_id',            // FK (Nullable) to Deparments
        'u_name',
        'u_email'
    ];

    /**
     * Get the department that the user belongs to.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class,'department_id','department_id');
    }

    /**
     * The roles that belong to the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'Role Assignment', 'user_id', 'role_id');
    }

      /**
     * Check if the user has a specific role by its code or name.
     */
    public function hasRole(string $roleIdentifier): bool
    {
        return $this->roles()->where('r_code', $roleIdentifier)->orWhere('r_name', $roleIdentifier)->exists();
    }

    /**
     * An accessor to quickly check if the user is a system administrator.
     * This is very useful and readable in Blade templates and policies.
     *
     * Usage: if ($user->is_admin) { ... }
     */
    public function getIsAdminAttribute(): bool
    {
        return $this->hasRole('system-admin');
    }

    /**
     * Get the event requests created by the user.
     */
    public function createdEventRequests(): HasMany
    {
        return $this->hasMany(Event::class, 'e_creator_id','user_id');
    }

    /**
     * Get the event requests currently assigned to the user for approval.
     */
    public function assignedEventRequests(): HasMany
    {
        return $this->hasMany(Event::class, 'e_current_approver_id','user_id');
    }
    
    /**
     * A business logic method to check if this user is the assigned manager of a specific venue.
     * Centralizes this logic for use in policies.
     */
    public function isManagerOf(Venue $venue): bool
    {
        return $this->user_id === $venue->manager_id;
    }
    
    /**
     * Assign a role to this user.
     * A convenient and readable helper method.
     *
     * Usage: $user->assignRole('system-admin');
     */
    public function assignRole(string $roleIdentifier): void
    {
        $role = Role::findByIdentifier($roleIdentifier)->first();
        if ($role) {
            $this->roles()->syncWithoutDetaching($role->role_id);
        }
    }

    public static function findByIdentifier(string $identifier)
    {        
        return static::where('r_code', $identifier)
                    ->orWhere('r_name', $identifier);
    }

    /**
     * Scope a query to only include users with the 'system-admin' role.
     *
     * Usage: User::admins()->get();
     */
    public function scopeAdmins(Builder $query): Builder
    {
        return $query->whereHas('roles', fn ($q) => $q->where('r_code', 'system-admin')->orWhere('r_name', 'System Administrator'));
    }
}
