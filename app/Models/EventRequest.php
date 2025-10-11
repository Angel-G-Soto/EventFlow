<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventRequest extends Model
{
    protected $table      = 'EventRequest';
    protected $primaryKey = 'e_id';
    public $incrementing  = true;
    protected $keyType    = 'int';

    const CREATED_AT = 'e_created_at';
    const UPDATED_AT = null; // none shown on ERD

    protected $fillable = [
        'u_id', 'v_id',
        'organization_nexo_id',
        'organization_name',
        'organization_advisor_name',
        'advisor_title',
        'e_description',
        'e_status',
        'e_upload_sta',
        'e_start_time',
        'e_end_time',
        'e_stud_num',
        'e_stud_phone',
        'e_adv_phone',
        'e_guest',
    ];

    protected $casts = [
        'e_start_time' => 'datetime',
        'e_end_time'   => 'datetime',
        'e_created_at' => 'datetime',
    ];

    /** Relationships */
    public function requester()  { return $this->belongsTo(User::class, 'u_id', 'u_id'); }
    public function venue()      { return $this->belongsTo(Venue::class, 'v_id', 'v_id'); }
    public function documents()  { return $this->hasMany(Document::class, 'e_id', 'e_id'); }
    public function history()    { return $this->hasMany(EventRequestHistory::class, 'e_id', 'e_id'); }
}
