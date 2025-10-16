<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VenueRequirement extends Model
{
    use HasFactory;
    protected $table = 'venue_requirement';   // @var string The table associated with the model.
    protected $primaryKey = 'vr_id';        // @var string The primary key associated with the table.

    protected $fillable = [
        'venue_id',         // FK to Venue
        'vr_drive_link',
        'vr_label',
        'vr_description'
    ];

    /**
     * Relationship between the Use Requirement and Venue
     * @return BelongsTo
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * A business logic method to determine if this is a document requirement.
     * This is very useful in Blade views to conditionally show a download link.
     *
     * Usage: if ($requirement->isDocumentRequirement()) { ... }
     */
    public function isDocumentRequirement(): bool
    {
        return !empty($this->vr_drive_link);
    }

    /**
     * A business logic method to determine if this is an acknowledgment.
     *
     * Usage: if ($requirement->isAcknowledgement()) { ... }
     */
    public function isAcknowledgement(): bool
    {
        return empty($this->vr_drive_link);
    }

    /**
     * Scope a query to only include requirements for a specific venue.
     *
     * Usage: UseRequirements::forVenue($venue)->get();
     */
    public function scopeForVenue(Builder $query, Venue $venue): void
    {
        $query->where('venue_id', $venue->venue_id);
    }

    /**
     * Scope a query to only include document-type requirements.
     *
     * Usage: UseRequirements::forVenue($venue)->documents()->get();
     */
    public function scopeDocuments(Builder $query): void
    {
        $query->whereNotNull('vr_drive_link');
    }

    /**
     * Scope a query to only include acknowledgement-type requirements.
     *
     * Usage: UseRequirements::forVenue($venue)->acknowledgements()->get();
     */
    public function scopeAcknowledgements(Builder $query): void
    {
        $query->whereNull('vr_drive_link');
    }
}
