<?php

namespace App\Enums;

use App\Enums\EnumInterface;
enum EnumStatusOccurrence: int implements EnumInterface
{
    case REPORTED = 0;
    case IN_PROGRESS = 1;
    case RESOLVED = 2;
    case CANCELLED = 3;

    public function name(): string
    {
        return match ($this){
            self::REPORTED => 'REPORTED',
            self::IN_PROGRESS => 'IN_PROGRESS',
            self::RESOLVED => 'RESOLVED',
            self::CANCELLED => 'CANCELLED',
        };
    }
}
