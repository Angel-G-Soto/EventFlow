<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentRequirement extends Model
{
    /** @use HasFactory<\Database\Factories\DepartmentRequirementFactory> */
    use HasFactory;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The database connection that should be used by the model.
     *
     * @var string
     */
    protected $connection = 'mariadb';

    /**
     * The attributes that are mass assignable.
     * @var string[]
     */
    protected $fillable = [
        'dr_document_link',
        'dr_document_name',
        'dr_document_description',
    ];

    /**
     * @return BelongsTo
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
