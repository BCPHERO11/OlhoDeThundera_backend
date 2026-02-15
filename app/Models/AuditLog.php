<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use LogicException;

class AuditLog extends Model
{
    protected $table = 'logs';

    public $timestamps = false;

    protected $fillable = [
        'entity_type',
        'entity_id',
        'action',
        'before',
        'after',
        'meta',
        'created_at',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function entity()
    {
        return $this->morphTo();
    }

    protected static function booted(): void
    {
        Relation::morphMap([
            'dispatch' => Dispatch::class,
            'occurrence' => Occurrence::class,
        ]);

        static::updating(function (self $auditLog): void {
            if ($auditLog->isDirty('id')) {
                throw new LogicException('AuditLog.id é imutável.');
            }

            if ($auditLog->isDirty('entity_type') || $auditLog->isDirty('entity_id')) {
                throw new LogicException('AuditLog.entity_type e entity_id são imutáveis.');
            }
        });
    }
}
