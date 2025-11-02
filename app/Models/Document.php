<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use HasFactory;
<<<<<<< HEAD
    protected $table = 'documents';                 // @var string The table associated with the model.
    protected $primaryKey = 'id';                   // @var string The primary key associated with the table.
=======
    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
>>>>>>> origin/restructuring_and_optimizations


    /**
     * The attributes that are mass assignable.
     * @var string[]
     */
    protected $fillable = [
<<<<<<< HEAD
        'event_id',         // FK to Event
        'd_name',
        'd_file_path'
=======
        'event_id',
        'name',
        'file_path',
>>>>>>> origin/restructuring_and_optimizations
    ];

    /**
     * Relationship between the Document and Event
     * @return BelongsTo
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }


    public function getNameOfFile(): string
    {
        return $this->name;
    }

    public function getFilePath(): string
    {
        return $this->file_path;
    }
}
