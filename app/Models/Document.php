<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    protected $table = 'document';                  // @var string The table associated with the model.
    protected $primaryKey = 'document_id';          // @var string The primary key associated with the table.
    protected $connection = 'mariadb';              // @var string The database connection that should be used by the model.

    // Enable timestamps and specify custom timestamp column names
    public $timestamps = true;
    const CREATED_AT = 'doc_created_at';
    const UPDATED_AT = 'doc_updated_at';

    /**
     * The attributes that are mass assignable.
     * @var string[]
     */
    protected $fillable = [
        'event_id',         // FK to Event
        'doc_name',
        'doc_path'
    ];

    /**
     * Relationship between the Document and Event
     * @return BelongsTo
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
