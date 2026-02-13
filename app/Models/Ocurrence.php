<?php

namespace App\Models;

use App\Enums\EnumStatusOccurrence;
use Illuminate\Database\Eloquent\Model;

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
        'status' => EnumStatusOccurrence::class,
        'reported_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function dispatches()
    {
        return $this->hasMany(Dispatch::class, 'occurrence_id', 'id');
    }
}
