<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserOrganizationAffiliation extends Model
{
    protected $table      = 'UserOrganizationAffiliation';
    protected $primaryKey = 'oa_id';
    public $incrementing  = true;
    protected $keyType    = 'int';
    public $timestamps    = false; // ERD gives oa_updated_at only

    protected $fillable = [
        'u_id',
        'organization_nexo_id',
        'oa_position',
        'oa_updated_at',
    ];

    protected $casts = [
        'oa_updated_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class, 'u_id', 'u_id'); }
}
