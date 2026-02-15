<?php

namespace App\Models;

use App\Enums\EnumCommandStatus;
use App\Enums\EnumCommandTypes;
use Illuminate\Database\Eloquent\Model;

class Command extends Model
{
    protected $table = 'commands';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false; // só tem created_at manual

    protected $fillable = [
        'id',
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

}
