<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleAssignment extends Model
{
    protected $table      = 'RoleAssignment';
    public    $timestamps = false;

    /**
     * ERD shows PK on both u_id and r_id (composite).
     * In Eloquent, leave $primaryKey unset and mark incrementing=false
     * so updates work via where keys.
     */
    public $incrementing = false;
    protected $primaryKey = null;

    protected $fillable = ['u_id', 'r_id'];

    public function user() { return $this->belongsTo(User::class, 'u_id', 'u_id'); }
    public function role() { return $this->belongsTo(Role::class, 'r_id', 'r_id'); }
}
