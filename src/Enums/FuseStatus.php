<?php

declare(strict_types=1);

namespace Vortech\Fuse\Enums;

enum FuseStatus: string
{
    case Active = 'active';
    case Expiring = 'expiring';
    case Expired = 'expired';
    case Invalid = 'invalid';
}
