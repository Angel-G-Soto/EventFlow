<?php

namespace App\Models;

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
        return $this->belongsTo(Department::class);
    }

    /**
     * The roles that belong to the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'Role Assignment', 'user_id', 'role_id');
    }

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