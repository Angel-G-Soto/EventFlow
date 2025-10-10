<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $table = 'role';                  // @var string The table associated with the model.
    protected $primaryKey = 'role_id';          // @var string The primary key associated with the table.
    protected $connection = 'mariadb';          // @var string The database connection that should be used by the model.

    // Enable timestamps and specify custom timestamp column names
    public $timestamps = true;
    const CREATED_AT = 'r_created_at';
    const UPDATED_AT = 'r_updated_at';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'r_name',             
        'r_code'
    ];

    /**
     * The users that belong to the role.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'Role Assignment', 'role_id', 'user_id');
    }
}
