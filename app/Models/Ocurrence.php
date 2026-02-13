<?php

namespace App\Models;

use App\Enums\EnumStatusOccurrence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Occurrence extends Model
{
    use HasFactory;
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
        'status' => EnumStatusOccurrence::class,
        'reported_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
