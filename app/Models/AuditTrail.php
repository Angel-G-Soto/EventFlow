<?php

namespace App\Models;

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
    /** Explicit (legacy-cased) table name per ERD. */
    protected $table = 'AuditTrail';

    /** Primary key column per ERD. */
    protected $primaryKey = 'audit_id';

    /** Auto-incrementing integer PK. */
    public $incrementing = true;
    protected $keyType   = 'int';

    /** Enable Laravel timestamps, but point them to ERD columns. */
    public $timestamps        = true;
    public const CREATED_AT   = 'a_created_at';
    public const UPDATED_AT   = 'a_updated_at';

    /**
     * Column constants (avoid typos).
     */
    public const COL_AUDIT_ID      = 'audit_id';
    public const COL_USER_ID       = 'user_id';
    public const COL_ACTION        = 'a_action';
    public const COL_TARGET_TYPE   = 'a_target_type';
    public const COL_TARGET_ID     = 'a_target_id';
    public const COL_CREATED_AT    = 'a_created_at';
    public const COL_UPDATED_AT    = 'a_updated_at';

    /** Mass-assignable attributes. */
    protected $fillable = [
        self::COL_USER_ID,
        self::COL_ACTION,
        self::COL_TARGET_TYPE,
        self::COL_TARGET_ID,
        // Timestamps are handled by Eloquent; omit unless you set them manually.
    ];

    /** Attribute casting. */
    protected $casts = [
        self::COL_USER_ID    => 'integer',
        self::COL_TARGET_ID  => 'integer',
        self::COL_CREATED_AT => 'datetime',
        self::COL_UPDATED_AT => 'datetime',
    ];

    /**
     * Actor who performed the action.
     * Note: Owner key is `user_id` (per ERD), not the default `id`.
     *
     * @return BelongsTo<User, AuditTrail>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, self::COL_USER_ID, 'user_id');
    }
}
