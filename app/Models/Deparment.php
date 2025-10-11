<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;
    protected $table = 'deparment';                 // @var string The table associated with the model.
    protected $primaryKey = 'department_id';        // @var string The primary key associated with the table.
    protected $connection = 'mariadb';              // @var string The database connection that should be used by the model.

    // Enable timestamps and specify custom timestamp column names
    public $timestamps = true;
    const CREATED_AT = 'd_created_at';
    const UPDATED_AT = 'd_updated_at';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'd_name',             
        'd_code'
    ];

    /**
     * Get the users for the department.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the venues for the department.
     */
    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class);
    }
}
