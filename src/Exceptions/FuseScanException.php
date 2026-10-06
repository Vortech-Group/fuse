<?php

declare(strict_types=1);

namespace Vortech\Fuse\Exceptions;

final class FuseScanException extends FuseException
{
    public function exitCode(): int
    {
        return 3;
    }
}
