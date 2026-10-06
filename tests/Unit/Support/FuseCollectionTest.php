<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Vortech\Fuse\Enums\FuseSeverity;
use Vortech\Fuse\Enums\FuseType;
use Vortech\Fuse\Support\FuseCollection;

beforeEach(function () {
    require_once __DIR__.'/../Data/FuseItemTest.php';

    $this->collection = new FuseCollection([
        fuseItem('2026-09-01', ['reason' => 'expired', 'severity' => FuseSeverity::Critical, 'owner' => 'payments']),
        fuseItem('2026-10-10', ['reason' => 'expiring', 'severity' => FuseSeverity::High, 'owner' => 'backend', 'type' => FuseType::Workaround]),
        fuseItem('2027-01-01', ['reason' => 'active', 'severity' => FuseSeverity::Low]),
    ], 14, CarbonImmutable::parse('2026-10-05'));
});

it('filters by status', function () {
    $reasons = fn ($c) => array_map(fn ($i) => $i->reason, $c->all());

    expect($reasons($this->collection->expired()))->toBe(['expired'])
        ->and($reasons($this->collection->expiring()))->toBe(['expiring'])
        ->and($reasons($this->collection->active()))->toBe(['active'])
        ->and($reasons($this->collection->expiringWithin(5)))->toBe(['expiring'])
        ->and($reasons($this->collection->expiringWithin(4)))->toBe([])
        ->and($reasons($this->collection->expiringWithin(365)))->toBe(['expiring', 'active']);
});

it('filters by severity, owner and type', function () {
    expect($this->collection->critical())->toHaveCount(1)
        ->and($this->collection->severityAtLeast(FuseSeverity::High))->toHaveCount(2)
        ->and($this->collection->severityAtLeast(FuseSeverity::Low))->toHaveCount(3)
        ->and($this->collection->ownedBy('backend'))->toHaveCount(1)
        ->and($this->collection->ownedBy('nobody'))->toHaveCount(0)
        ->and($this->collection->ofType(FuseType::Workaround))->toHaveCount(1);
});

it('keeps its point in time across chained filters and is immutable', function () {
    $filtered = $this->collection->severityAtLeast(FuseSeverity::High)->expired();

    expect($filtered)->toHaveCount(1)
        ->and($filtered->today()->format('Y-m-d'))->toBe('2026-10-05')
        ->and($this->collection)->toHaveCount(3);
});

it('is countable, iterable and exportable', function () {
    expect(count($this->collection))->toBe(3)
        ->and(iterator_to_array($this->collection))->toHaveCount(3)
        ->and($this->collection->isEmpty())->toBeFalse()
        ->and($this->collection->toArray()[1])->toMatchArray(['status' => 'expiring', 'days_remaining' => 5]);
});

it('sorts the soonest expiry first', function () {
    $collection = new FuseCollection([fuseItem('2027-01-01', ['reason' => 'b']), fuseItem('2026-01-01', ['reason' => 'a'])]);

    expect(array_map(fn ($i) => $i->reason, $collection->sorted()->all()))->toBe(['a', 'b']);
});
