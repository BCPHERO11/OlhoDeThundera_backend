<?php

namespace App\Enums;

enum EnumOccurrenceStatus: int implements EnumInterface
{
    case REPORTED = 0;
    case IN_PROGRESS = 1;
    case RESOLVED = 2;
    case CANCELLED = 3;

    public function name(): string
    {
        return match ($this){
            self::REPORTED => 'reported',
            self::IN_PROGRESS => 'in_progress',
            self::RESOLVED => 'resolved',
            self::CANCELLED => 'cancelled',
        };
    }
}
