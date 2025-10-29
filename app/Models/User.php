<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
<<<<<<< HEAD
=======
use Illuminate\Database\Eloquent\Model;
>>>>>>> origin/restructuring_and_optimizations
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
<<<<<<< HEAD
=======
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
>>>>>>> origin/restructuring_and_optimizations

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
<<<<<<< HEAD
        'department_id',            // FK (Nullable) to Deparments
        'u_name',
        'u_email'
=======
        /*'name',*/
        'department_id',
        'email',
        'password',
        'first_name',
        'last_name',
        'auth_type',
>>>>>>> origin/restructuring_and_optimizations
    ];

    /**
     * Get the department that the user belongs to.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * The roles that belong to the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'Role Assignment', 'user_id', 'role_id');
    }

<<<<<<< HEAD
    /**
     * Get the event requests created by the user.
     */
    public function createdEventRequests(): HasMany
    {
        return $this->hasMany(EventRequest::class, 'e_creator_id');
    }

    /**
     * Get the event requests currently assigned to the user for approval.
     */
    public function assignedEventRequests(): HasMany
    {
        return $this->hasMany(EventRequest::class, 'e_current_approver_id');
    }
}
=======
    //////////////////////////////////// RELATIONS //////////////////////////////////////////////////////

    /**
     * Relationship between the User and Department
     * @return BelongsTo
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Relationship between the User and Venue
     * @return HasMany
     */
    public function manages(): HasMany
    {
        return $this->hasMany(Venue::class, 'manager_id');
    }

    /**
     * Relation between User and Event Request History
     * @return HasMany
     */
    public function requestActionLog(): HasMany
    {
        return $this->hasMany(EventHistory::class, 'approver_id');
    }

    /**
     * Relation between User and Events
     * @return HasMany
     */
    public function requests(): HasMany
    {
        return $this->hasMany(Event::class, 'creator_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role');
    }

    //////////////////////////////////// METHODS //////////////////////////////////////////////////////

    public function getRoleNames(): Collection
    {
        return $this->roles()->pluck('name')->unique();
    }
}
>>>>>>> origin/restructuring_and_optimizations
