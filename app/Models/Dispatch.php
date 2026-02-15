<?php

namespace App\Models;

use App\Enums\EnumDispatchStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use LogicException;

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

    protected static function booted(): void
    {
        static::creating(function (self $dispatch): void {
            $dispatch->id ??= (string) Str::uuid();
        });

        static::updating(function (self $dispatch): void {
            if ($dispatch->isDirty('id')) {
                throw new LogicException('Dispatch.id é imutável.');
            }
        });
    }

    public function occurrence()
    {
        return $this->belongsTo(Occurrence::class, 'occurrence_id', 'id');
    }

    public function logs()
    {
        return $this->morphMany(AuditLog::class, 'entity');
    }
}
