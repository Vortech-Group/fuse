<?php

declare(strict_types=1);

namespace Vortech\Fuse\Enums;

use Vortech\Fuse\Exceptions\InvalidFuseConfiguration;

enum FuseSeverity: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function weight(): int
    {
        return match ($this) {
            self::Low => 10,
            self::Medium => 20,
            self::High => 30,
            self::Critical => 40,
        };
    }

    public function isAtLeast(self $other): bool
    {
        return $this->weight() >= $other->weight();
    }

    /**
     * Accepts an enum case or its string value (as used in config files and CLI options).
     */
    public static function fromInput(self|string $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        return self::tryFrom(strtolower($value))
            ?? throw InvalidFuseConfiguration::invalidValue('severity', $value, array_column(self::cases(), 'value'));
    }
}
