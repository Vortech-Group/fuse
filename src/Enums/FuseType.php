<?php

declare(strict_types=1);

namespace Vortech\Fuse\Enums;

use Vortech\Fuse\Exceptions\InvalidFuseConfiguration;

enum FuseType: string
{
    case TechnicalDebt = 'technical-debt';
    case Workaround = 'workaround';
    case Temporary = 'temporary';
    case Deprecated = 'deprecated';
    case Migration = 'migration';
    case Fallback = 'fallback';

    public static function fromInput(self|string $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        return self::tryFrom(strtolower($value))
            ?? throw InvalidFuseConfiguration::invalidValue('type', $value, array_column(self::cases(), 'value'));
    }
}
