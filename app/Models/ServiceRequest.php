<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'encounter_id',
        'requester_id',
        'code',
        'code_system',
        'code_display',
        'status',
        'priority',
        'ordered_at',
        'scheduled_for',
        'body_site',
        'notes',
    ];

    protected $casts = [
        'ordered_at' => 'datetime',
        'scheduled_for' => 'datetime',
    ];

    public function encounter()
    {
        return $this->belongsTo(Encounter::class, 'encounter_id');
    }
}
