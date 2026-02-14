<?php

namespace App\Enums;

enum EnumCommandStatus: int implements EnumInterface

{
    case PENDING = 0;
    case PROCESSED = 1;
    case FAILED = 2;

    public function name(): string
    {
        return match ($this) {
            self::PENDING => 'pending',
            self::PROCESSED => 'processed',
            self::FAILED => 'failed',
        };
    }
}
