<?php

namespace App\Models;

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
        'at_description'
    ];

    /**
     * Get the user (actor) who performed the audited action.
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
