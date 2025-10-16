<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditTrail extends Model
{
    protected $table      = 'AuditTrail';
    protected $primaryKey = 'audit_id';
    public $incrementing  = true;
    protected $keyType    = 'int';
    public $timestamps    = false; // ERD shows only u_updated_at-like stamp

    protected $fillable = [
        'u_id',
        'audit_action',
        'audit_target_type',
        'audit_target_id',
        'u_updated_at', // timestamp column in ERD
    ];

    protected $casts = [
        'u_updated_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class, 'u_id', 'u_id'); }
}
