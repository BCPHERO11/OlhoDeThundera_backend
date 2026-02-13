<?php

namespace App\Enums;

enum EnumDispatchStatus: int implements EnumInterface
{
    case ASSIGNED = 0;
    case EN_ROUTE = 1;
    case ON_SITE = 2;
    case CLOSED = 3;

    public function name(): string
    {
        return match ($this){
            self::ASSIGNED => 'assigned',
            self::EN_ROUTE => 'en_route',
            self::ON_SITE => 'on_site',
            self::CLOSED => 'closed',
        };
    }
}
