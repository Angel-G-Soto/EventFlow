<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditTrail extends Model
{
    use HasFactory;

    protected $table = 'audit_trail';       // @var string The table associated with the model.
    protected $primaryKey = 'at_id';        // @var string The primary key associated with the table.

    protected $fillable = [
        'user_id',          // FK to User
        'at_action',
        'at_description',
        'is_admin_action'
    ];

    /**
     * Get the user (actor) who performed the audited action.
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id','user_id');
    }

    /**
     * Scope a query to only include actions performed by a specific user.
     * Usage: AuditTrail::forUser($user)->get();
     */
    public function scopeForUser(Builder $query, User $user): void
    {
        $query->where('user_id', $user->user_id);
    }
    /**
     * An accessor to get the timestamp in a human-readable "time ago" format.
     * Usage: $auditTrail->time_ago
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->audit_timestamp ? $this->audit_timestamp->diffForHumans() : 'N/A';
    }

    /**
     * Scope a query to only include actions of a specific type.
     * Usage: AuditTrail::ofType('EVENT_CREATED')->get();
     */
    public function scopeOfType(Builder $query, string $actionCode): Builder
    {
        return $query->where('at_action', $actionCode);
    }

     /**
     * Scope a query to only include actions performed by administrators.
     * Usage: AuditTrail::adminActions()->get();
     */
    public function scopeAdminActions(Builder $query, bool $isAdmin = true): Builder
    {
        return $query->where('is_admin_action', $isAdmin);
    }
}
