<?php

declare(strict_types=1);

namespace Vortech\Fuse\Exceptions;

use RuntimeException;

abstract class FuseException extends RuntimeException
{
    /**
     * The process exit code a command should use for this error.
     */
    abstract public function exitCode(): int;
}
