<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection;
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
        'u_email',
        'u_is_active'
    ];

    protected $casts = [
        'u_is_active' => 'boolean',
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
        return $this->belongsToMany(Role::class, 'role_assignment', 'user_id', 'role_id');
    }

    /**
     * Check if the user has a specific role by its code or name.
     */
    public function hasRole(string $roleIdentifier): bool
    {
        return $this->roles()
                ->where(function ($query) use ($roleIdentifier) {
                    $query->where('r_code', $roleIdentifier)
                        ->orWhere('r_name', $roleIdentifier);
                })
                ->exists();    
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

    /**
     * Get the users with a particular role.
     */
    public function getUsersWithRole(string $roleIdentifier): Collection
    {
        return User::whereHas('roles', function ($query) use ($roleIdentifier) {
            $query->where('r_code', $roleIdentifier)->orWhere('r_name', $roleIdentifier);
        })->get();
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

    /**
     * Get the events that are currently pending this user's approval.
     *
     * @return HasMany
     */
    public function pendingApprovals(): HasMany
    {
        $terminalStates = ['Approved', 'Denied', 'Canceled', 'Withdrawn', 'Completed'];

        // This relationship finds all events...
        return $this->hasMany(Event::class, 'e_creator_id', 'user_id')
            ->join('event_history', 'event.event_id', '=', 'event_history.event_id')

            // ...that are not in a terminal state...
            ->whereNotIn('event.e_status', $terminalStates)

            // ...and where this user is the actor of the LATEST history entry for that event.
            ->where('event_history.user_id', $this->user_id)
            ->whereIn('event_history.eh_id', function ($query) {
                $query->selectRaw('MAX(eh_id)')
                    ->from('event_history')
                    ->groupBy('event_id');
            });
    }

    /**
     * Get the event requests created by the user that are still pending.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function createdPendingEvents(): HasMany
    {
        $terminalStates = ['Approved', 'Denied', 'Canceled', 'Withdrawn', 'Completed'];

        // Reuse the existing 'createdEventRequests' relationship and add a condition.
        return $this->createdEventRequests()->whereNotIn('e_status', $terminalStates);
    }
}
