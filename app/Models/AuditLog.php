<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class AuditLog extends Model
{
    use HasFactory;

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
    }
}
