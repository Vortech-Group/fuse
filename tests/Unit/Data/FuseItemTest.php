<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Vortech\Fuse\Data\FuseItem;
use Vortech\Fuse\Enums\FuseSeverity;
use Vortech\Fuse\Enums\FuseStatus;
use Vortech\Fuse\Enums\FuseType;

function fuseItem(string $expires = '2026-12-01', array $overrides = []): FuseItem
{
    return new FuseItem(...[
        'reason' => 'Reason',
        'expiresAt' => CarbonImmutable::parse($expires),
        'file' => 'app/A.php',
        'line' => 10,
        'class' => 'App\A',
        'method' => null,
        'property' => null,
        'owner' => null,
        'issue' => null,
        'severity' => FuseSeverity::Medium,
        'type' => FuseType::TechnicalDebt,
        'replacement' => null,
        'createdAt' => null,
        ...$overrides,
    ]);
}

it('computes its status from the current day and the warning window', function () {
    $item = fuseItem('2026-10-20');

    expect($item->status(CarbonImmutable::parse('2026-10-05'), 14))->toBe(FuseStatus::Active)
        ->and($item->status(CarbonImmutable::parse('2026-10-06'), 14))->toBe(FuseStatus::Expiring)
        ->and($item->status(CarbonImmutable::parse('2026-10-19'), 14))->toBe(FuseStatus::Expiring)
        ->and($item->status(CarbonImmutable::parse('2026-10-20'), 14))->toBe(FuseStatus::Expired)
        ->and($item->status(CarbonImmutable::parse('2026-10-21 23:59'), 14))->toBe(FuseStatus::Expired);
});

it('describes its target', function () {
    expect(fuseItem()->target())->toBe('App\A')
        ->and(fuseItem(overrides: ['method' => 'run'])->target())->toBe('App\A::run()')
        ->and(fuseItem(overrides: ['property' => 'x'])->target())->toBe('App\A::$x')
        ->and(fuseItem(overrides: ['class' => null, 'method' => 'helper'])->target())->toBe('helper()')
        ->and(fuseItem()->location())->toBe('app/A.php:10');
});

it('exports all of its metadata as an array', function () {
    $item = fuseItem(overrides: [
        'owner' => 'backend', 'issue' => 'APP-1', 'replacement' => 'B', 'createdAt' => CarbonImmutable::parse('2026-01-02'),
        'severity' => FuseSeverity::Critical, 'type' => FuseType::Fallback, 'method' => 'run',
    ]);

    expect($item->toArray())->toBe([
        'reason' => 'Reason',
        'expires' => '2026-12-01',
        'file' => 'app/A.php',
        'line' => 10,
        'class' => 'App\\A',
        'method' => 'run',
        'property' => null,
        'owner' => 'backend',
        'issue' => 'APP-1',
        'severity' => 'critical',
        'type' => 'fallback',
        'replacement' => 'B',
        'created' => '2026-01-02',
    ]);
});

it('adds status information to its array form on request', function () {
    $data = fuseItem('2026-10-09')->toArray(CarbonImmutable::parse('2026-10-05'), 14);

    expect($data)->toMatchArray(['expires' => '2026-10-09', 'status' => 'expiring', 'days_remaining' => 4]);
});
