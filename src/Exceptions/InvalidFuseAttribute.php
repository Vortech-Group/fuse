<?php

declare(strict_types=1);

namespace Vortech\Fuse\Exceptions;

final class InvalidFuseAttribute extends FuseException
{
    public function exitCode(): int
    {
        return 2;
    }
}
