<?php

declare(strict_types=1);

namespace Vortech\Fuse\Support;

use Carbon\CarbonImmutable;
use Throwable;

final class Expiration
{
    /**
     * Strictly parse a YYYY-MM-DD date. Relative or overflowing dates ("tomorrow", "2026-02-31") are rejected
     * so the source code stays deterministic.
     */
    public static function parse(string $value): ?CarbonImmutable
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);
        } catch (Throwable) {
            return null;
        }

        return $date instanceof CarbonImmutable && $date->format('Y-m-d') === $value ? $date : null;
    }

    /**
     * Whole days from $today until $date. Zero or negative means the date has been reached.
     */
    public static function daysUntil(CarbonImmutable $date, CarbonImmutable $today): int
    {
        return (int) $today->startOfDay()->diffInDays($date->startOfDay(), false);
    }
}
