<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class Document extends Model
{
    protected $table = 'document';                  // @var string The table associated with the model.
    protected $primaryKey = 'document_id';          // @var string The primary key associated with the table.

   
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
        return $this->belongsTo(Event::class, 'document_id','document_id');
    }
    
    /**
     * An accessor to generate a secure, temporary download URL for the document.
     * This is the recommended way to provide file access from the front end.
     *
     * Usage: $document->download_url
     */
    public function getDownloadUrlAttribute(): string
    {
        // Creates a temporary signed URL that is valid for a limited time (e.g., 1 hour).
        // This prevents public, permanent links to the secure files.
        return URL::temporarySignedRoute(
            'documents.download', // Assumes you have a named route for downloading
            now()->addHour(),
            ['document' => $this->doc_id]
        );
    }

    /**
     * An accessor to get the file's size in a human-readable format.
     *
     * Usage: $document->formatted_size
     */
    public function getFormattedSizeAttribute(): string
    {
        if (!Storage::exists($this->doc_path)) {
            return 'File not found';
        }

        $bytes = Storage::size($this->doc_path);
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
