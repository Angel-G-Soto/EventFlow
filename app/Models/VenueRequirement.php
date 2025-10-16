<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VenueRequirement extends Model
{
    use HasFactory;

    protected $table = 'venue_requirement';
    protected $primaryKey = 'vr_id';

    /**
     * The attributes that are mass assignable.
     * @var string[]
     */
    protected $fillable = [
        'venue_id',
        'vr_name',         
        'vr_type',         // The new type field ('document' or 'acknowledgement')
        'vr_content',      // The content field (URL or description)
    ];

    /**
     * Get the venue that this requirement belongs to.
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class, 'venue_id', 'venue_id');
    }

    /**
     * A business logic method to determine if this is a document requirement.
     * Checks the explicit type column.
     */
    public function isDocumentRequirement(): bool
    {
        return $this->vr_type === 'document';
    }

    /**
     * A business logic method to determine if this is an acknowledgment checkbox.
     */
    public function isAcknowledgement(): bool
    {
        return $this->vr_type === 'acknowledgement';
    }

    /**
     * Scope a query to only include document-type requirements.
     */
    public function scopeDocuments(Builder $query): void
    {
        $query->where('vr_type', 'document');
    }

    /**
     * Scope a query to only include acknowledgement-type requirements.
     */
    public function scopeAcknowledgements(Builder $query): void
    {
        $query->where('vr_type', 'acknowledgement');
    }
}
