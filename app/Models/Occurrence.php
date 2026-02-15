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

        static::updating(function (self $occurrence): void {
            if ($occurrence->isDirty('id')) {
                throw new LogicException('Occurrence.id é imutável.');
            }

            if ($occurrence->isDirty('external_id')) {
                throw new LogicException('Occurrence.external_id é imutável.');
            }
        });
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
