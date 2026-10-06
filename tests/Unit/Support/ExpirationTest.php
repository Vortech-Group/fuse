<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Vortech\Fuse\Support\Expiration;

it('parses strict YYYY-MM-DD dates', function () {
    $date = Expiration::parse('2026-12-01');

    expect($date)->toBeInstanceOf(CarbonImmutable::class)
        ->and($date->format('Y-m-d H:i:s'))->toBe('2026-12-01 00:00:00');
});

it('rejects ambiguous, relative and impossible dates', function (string $value) {
    expect(Expiration::parse($value))->toBeNull();
})->with(['next Friday', 'tomorrow', '+2 weeks', '2026-2-1', '2026/12/01', '01-12-2026', '2026-02-31', '2026-13-01', '2026-12-01 10:00', '']);

it('counts whole days until a date', function () {
    $today = CarbonImmutable::parse('2026-10-05 17:30:00');

    expect(Expiration::daysUntil(CarbonImmutable::parse('2026-10-09'), $today))->toBe(4)
        ->and(Expiration::daysUntil(CarbonImmutable::parse('2026-10-05'), $today))->toBe(0)
        ->and(Expiration::daysUntil(CarbonImmutable::parse('2026-10-01'), $today))->toBe(-4);
});
