<?php

namespace App\Models;

use App\Enums\EnumCommandStatus;
use App\Enums\EnumCommandTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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
        'payload' => 'array',
        'status' => EnumCommandStatus::class,
        'type' => EnumCommandTypes::class,
        'source' => 'string',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Command $command) {
            if (!$command->id) {
                $command->id = (string) Str::uuid();
            }

            if (!$command->status) {
                $command->status = EnumCommandStatus::PENDING;
            }
        });
    }

}
