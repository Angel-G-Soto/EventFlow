<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table      = 'Roles';
    protected $primaryKey = 'r_id';
    public $incrementing  = true;
    protected $keyType    = 'int';
    public $timestamps    = false;

    protected $fillable = ['r_name', 'r_code'];

    public function assignments()
    {
        return $this->hasMany(RoleAssignment::class, 'r_id', 'r_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'RoleAssignment', 'r_id', 'u_id');
    }
}
