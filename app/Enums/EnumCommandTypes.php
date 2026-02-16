<?php

namespace App\Enums;

enum EnumCommandTypes: int implements EnumInterface
{
    case OCCURRENCE_CREATED = 0;
    case OCCURRENCE_IN_PROGRESS = 1;
    case OCCURRENCE_RESOLVED = 2;
    case OCCURRENCE_CANCELLED = 3;
    case DISPATCH_ASSIGNED = 4;
    case DISPATCH_ON_SITE = 5;

    public function name(): string
    {
        return match ($this) {
            self::OCCURRENCE_CREATED => 'occurrence.created',
            self::OCCURRENCE_IN_PROGRESS => 'occurrence.in_progress',
            self::OCCURRENCE_RESOLVED => 'occurrence.resolved',
            self::OCCURRENCE_CANCELLED => 'occurrence.cancelled',
            self::DISPATCH_ASSIGNED => 'dispatch.assigned',
            self::DISPATCH_ON_SITE => 'dispatch.on_site',
        };
    }
}
