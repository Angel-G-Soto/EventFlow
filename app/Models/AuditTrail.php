<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class AuditTrail
 *
 * Immutable audit entries recording who did what and to which target.
 *
 * ERD (authoritative):
 *   Table: AuditTrail
 *   PK:    audit_id (int, auto-increment)
 *   FKs:   user_id  -> User.user_id
 *
 * Columns:
 *   - audit_id         (int, PK)
 *   - user_id          (int, FK -> users.user_id)
 *   - a_action         (string)       // machine-friendly action code
 *   - a_target_type    (string|null)  // e.g., 'Event', 'Venue'
 *   - a_target_id      (int|null)     // ID of the target row
 *   - a_created_at     (timestamp)    // created (non-Laravel name)
 *   - a_updated_at     (timestamp)    // updated (non-Laravel name)
 *
 * Timestamp mapping:
 *   We keep Eloquent timestamps enabled, but map them to ERD names:
 *     CREATED_AT = 'a_created_at'
 *     UPDATED_AT = 'a_updated_at'
 */
class AuditTrail extends Model
{
    use HasFactory;

    protected $table = 'audit_trail';       // @var string The table associated with the model.
    protected $primaryKey = 'at_id';        // @var string The primary key associated with the table.

    protected $fillable = [
        'user_id',          // FK to User
        'at_action',
        'at_description',
        'at_user'
    ];

    /**
     * Get the user (actor) who performed the audited action.
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*

    Column constants (avoid typos).

    public const COL_AUDIT_ID      = 'audit_id';
    public const COL_USER_ID       = 'user_id';
    public const COL_ACTION        = 'a_action';
    public const COL_TARGET_TYPE   = 'a_target_type';
    public const COL_TARGET_ID     = 'a_target_id';
    public const COL_CREATED_AT    = 'a_created_at';
    public const COL_UPDATED_AT    = 'a_updated_at';
     */
}
