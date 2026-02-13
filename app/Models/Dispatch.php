<?php

namespace App\Models;

use App\Enums\EnumDispatchStatus;
use Illuminate\Database\Eloquent\Model;

class Dispatch extends Model
{
    protected $table = 'dispatches';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'occurrence_id',
        'resource_code',
        'status',
    ];

    protected $casts = [
        'status' => EnumDispatchStatus::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function occurrence()
    {
        return $this->belongsTo(Occurrence::class, 'occurrence_id', 'id');
    }
    public function logs()
    {
        return $this->morphMany(AuditLog::class, 'entity');
    }
}
