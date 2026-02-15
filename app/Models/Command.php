<?php

namespace App\Models;

use App\Enums\EnumCommandStatus;
use App\Enums\EnumCommandTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use LogicException;

class Command extends Model
{
    protected $table = 'commands';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false; // só tem created_at manual

    protected $fillable = [
        'idempotency_key',
        'source',
        'type',
        'payload',
        'status',
        'processed_at',
        'error',
    ];

    protected $casts = [
        'payload' => 'json',
        'status' => EnumCommandStatus::class,
        'type' => EnumCommandTypes::class,
        'source' => 'string',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $command): void {
            $command->id ??= (string) Str::uuid();
        });

        static::updating(function (self $command): void {
            if ($command->isDirty('id')) {
                throw new LogicException('Command.id é imutável.');
            }

            if ($command->isDirty('idempotency_key')) {
                throw new LogicException('Command.idempotency_key é imutável.');
            }
        });
    }
}
