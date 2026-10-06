<?php

declare(strict_types=1);

use Vortech\Fuse\Enums\FuseSeverity;
use Vortech\Fuse\Enums\FuseType;
use Vortech\Fuse\Exceptions\InvalidFuseConfiguration;

it('orders severities by weight', function () {
    expect(FuseSeverity::Low->weight())->toBeLessThan(FuseSeverity::Medium->weight())
        ->and(FuseSeverity::Critical->isAtLeast(FuseSeverity::High))->toBeTrue()
        ->and(FuseSeverity::Low->isAtLeast(FuseSeverity::Medium))->toBeFalse()
        ->and(FuseSeverity::High->isAtLeast(FuseSeverity::High))->toBeTrue();
});

it('resolves enums from user input', function () {
    expect(FuseSeverity::fromInput('HIGH'))->toBe(FuseSeverity::High)
        ->and(FuseSeverity::fromInput(FuseSeverity::Low))->toBe(FuseSeverity::Low)
        ->and(FuseType::fromInput('technical-debt'))->toBe(FuseType::TechnicalDebt);
});

it('rejects unknown input with the allowed values', function () {
    FuseSeverity::fromInput('blocker');
})->throws(InvalidFuseConfiguration::class, 'Invalid severity "blocker". Allowed values: low, medium, high, critical.');
