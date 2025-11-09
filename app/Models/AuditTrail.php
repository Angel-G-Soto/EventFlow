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

    // Table is audit_trail with default primary key 'id' created by the migration
    protected $table = 'audit_trail';

    // Mass-assignable fields per migration schema
    protected $fillable = [
        'user_id',
        'action',
        'target_type',
        'target_id',
    ];

    protected $casts = [
        'user_id'   => 'int',
        'created_at'=> 'datetime',
        'updated_at'=> 'datetime',
    ];
    /**
     * Get the user (actor) who performed the audited action.
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Optionally, constants can be added later if needed by other tests
}
