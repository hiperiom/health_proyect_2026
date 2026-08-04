<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Encounter extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'encounter_class',
        'status',
        'start_at',
        'end_at',
        'reason_code',
        'reason_system',
        'reason_display',
        'location_id',
        'location_type',
        'country',
        'language',
        'notes',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class, 'encounter_id');
    }
}
