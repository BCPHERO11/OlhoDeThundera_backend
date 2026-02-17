<?php

namespace App\Models;

use App\Enums\EnumOccurrenceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use LogicException;

class Occurrence extends Model
{
    protected $table = 'occurrences';
    protected $keyType = 'string';
    public $incrementing = false;

    private ?array $auditBeforeSnapshot = null;

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

    protected static function booted(): void
    {
        static::creating(function (self $occurrence): void {
            $occurrence->id ??= (string) Str::uuid();
        });

        static::created(function (self $occurrence): void {
            $occurrence->logs()->create([
                'action' => 'created',
                'before' => null,
                'after' => $occurrence->fresh()->toArray(),
                'meta' => self::resolveAuditMeta(),
                'created_at' => now(),
            ]);
        });

        static::updating(function (self $occurrence): void {
            if ($occurrence->isDirty('id')) {
                throw new LogicException('Occurrence.id é imutável.');
            }

            if ($occurrence->isDirty('external_id')) {
                throw new LogicException('Occurrence.external_id é imutável.');
            }

            $occurrence->auditBeforeSnapshot = $occurrence->getOriginal();
        });

        static::updated(function (self $occurrence): void {
            $occurrence->logs()->create([
                'action' => 'updated',
                'before' => $occurrence->auditBeforeSnapshot,
                'after' => $occurrence->fresh()->toArray(),
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

    public function dispatches()
    {
        return $this->hasMany(Dispatch::class, 'occurrence_id', 'id');
    }

    public function logs()
    {
        return $this->morphMany(AuditLog::class, 'entity');
    }
}
