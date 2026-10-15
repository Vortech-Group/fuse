<?php

declare(strict_types=1);

namespace Vortech\Fuse\Enums;

use Vortech\Fuse\Attributes\Deprecated;
use Vortech\Fuse\Attributes\Fallback;
use Vortech\Fuse\Attributes\FuseAttribute;
use Vortech\Fuse\Attributes\Migration;
use Vortech\Fuse\Attributes\TechnicalDebt;
use Vortech\Fuse\Attributes\Temporary;
use Vortech\Fuse\Attributes\Workaround;
use Vortech\Fuse\Exceptions\InvalidFuseConfiguration;

enum FuseType: string
{
    case TechnicalDebt = 'technical-debt';
    case Workaround = 'workaround';
    case Temporary = 'temporary';
    case Deprecated = 'deprecated';
    case Migration = 'migration';
    case Fallback = 'fallback';

    /**
     * The type-specific attribute class that implies this type.
     *
     * @return class-string<FuseAttribute>
     */
    public function attributeClass(): string
    {
        return match ($this) {
            self::TechnicalDebt => TechnicalDebt::class,
            self::Workaround => Workaround::class,
            self::Temporary => Temporary::class,
            self::Deprecated => Deprecated::class,
            self::Migration => Migration::class,
            self::Fallback => Fallback::class,
        };
    }

    /**
     * The type implied by a type-specific attribute class name, if it is one.
     */
    public static function fromAttributeClass(string $class): ?self
    {
        $class = ltrim($class, '\\');

        foreach (self::cases() as $case) {
            if (strcasecmp($case->attributeClass(), $class) === 0) {
                return $case;
            }
        }

        return null;
    }

    public static function fromInput(self|string $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        return self::tryFrom(strtolower($value))
            ?? throw InvalidFuseConfiguration::invalidValue('type', $value, array_column(self::cases(), 'value'));
    }
}
