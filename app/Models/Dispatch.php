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

    private ?array $auditBeforeSnapshot = null;

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

        static::created(function (self $dispatch): void {
            $dispatch->logs()->create([
                'action' => 'created',
                'before' => null,
                'after' => $dispatch->fresh()->toArray(),
                'meta' => self::resolveAuditMeta(),
                'created_at' => now(),
            ]);
        });

        static::updating(function (self $dispatch): void {
            if ($dispatch->isDirty('id')) {
                throw new LogicException('Dispatch.id é imutável.');
            }

            $dispatch->auditBeforeSnapshot = $dispatch->getOriginal();
        });

        static::updated(function (self $dispatch): void {
            $dispatch->logs()->create([
                'action' => 'updated',
                'before' => $dispatch->auditBeforeSnapshot,
                'after' => $dispatch->fresh()->toArray(),
                'meta' => self::resolveAuditMeta(),
                'created_at' => now(),
            ]);
        });
    }

    private static function resolveAuditMeta(): ?array
    {
        if (!app()->bound('audit.command_id')) {
            return null;
        }

        return [
            'command_id' => app('audit.command_id'),
        ];
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
