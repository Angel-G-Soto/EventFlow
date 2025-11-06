<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        /*'name',*/
        'department_id',
        'email',
        'password',
        'first_name',
        'last_name',
        'auth_type',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    //////////////////////////////////// RELATIONS //////////////////////////////////////////////////////

    /**
     * Relationship between the User and Department
     * @return BelongsTo
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

//    /**
//     * Relationship between the User and Venue
//     * @return HasMany
//     */
//    public function manages(): HasMany
//    {
//        return $this->hasMany(Venue::class, 'manager_id');
//    }

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

    /**
     * Relation between User and Role
     * @return BelongsToMany
     */
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
