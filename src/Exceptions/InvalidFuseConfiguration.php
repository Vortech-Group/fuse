<?php

declare(strict_types=1);

namespace Vortech\Fuse\Exceptions;

final class InvalidFuseConfiguration extends FuseException
{
    /**
     * @param  list<string>  $allowed
     */
    public static function invalidValue(string $name, string $value, array $allowed): self
    {
        return new self(sprintf(
            'Invalid %s "%s". Allowed values: %s.',
            $name,
            $value,
            implode(', ', $allowed),
        ));
    }

    public static function invalidType(string $key, string $expected): self
    {
        return new self(sprintf('The "fuse.%s" config value must be %s.', $key, $expected));
    }

    public function exitCode(): int
    {
        return 2;
    }
}
