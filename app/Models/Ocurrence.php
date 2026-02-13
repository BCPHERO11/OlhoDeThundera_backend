<?php

namespace App\Models;

use App\Enums\EnumOccurrenceStatus;
use Illuminate\Database\Eloquent\Model;

class Occurrence extends Model
{
    protected $table = 'occurrences';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'external_id',
        'type',
        'status',
        'description',
        'reported_at',
    ];
    protected $casts = [
        'status' => EnumOccurrenceStatus::class,
        'reported_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function dispatches()
    {
        return $this->hasMany(Dispatch::class, 'occurrence_id', 'id');
    }

    public function logs()
    {
        return $this->morphMany(AuditLog::class, 'entity');
    }
}
