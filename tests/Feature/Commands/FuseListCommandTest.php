<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

function listJson(array $options = []): array
{
    Artisan::call('fuse:list', [...$options, '--json' => true]);

    return json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
}

beforeEach(function () {
    $this->scanning([__DIR__.'/../../Fixtures/ActiveFuse.php', __DIR__.'/../../Fixtures/ExpiredFuse.php', __DIR__.'/../../Fixtures/MultipleFuses.php'], dirname(__DIR__, 3));
});

it('lists all Fuse items', function () {
    $this->artisan('fuse:list')
        ->expectsOutputToContain('Fuse items')
        ->expectsOutputToContain('Legacy API adapter')
        ->expectsOutputToContain('tests/Fixtures/ExpiredFuse.php:13')
        ->assertExitCode(0);

    expect(listJson())->toHaveCount(8);
});

it('says so when there is nothing to list', function () {
    $this->scanning([$this->sourceTree([])]);

    $this->artisan('fuse:list')->expectsOutputToContain('No Fuse items found.')->assertExitCode(0);
});

it('filters expired items', function () {
    expect(array_column(listJson(['--expired' => true]), 'reason'))->toBe(['Fallback until the payment gateway fixes duplicate callbacks']);
});

it('filters items expiring soon', function () {
    $this->travelTo('2099-11-20');

    expect(array_column(listJson(['--expiring' => 14]), 'reason'))->toBe(['Legacy API adapter'])
        ->and(listJson(['--expiring' => 1]))->toBe([]);
});

it('filters by owner, severity and type', function () {
    expect(array_column(listJson(['--owner' => 'payments']), 'reason'))->toBe(['Fallback until the payment gateway fixes duplicate callbacks'])
        ->and(listJson(['--severity' => 'high']))->toHaveCount(1)
        ->and(listJson(['--type' => 'workaround']))->toHaveCount(1)
        ->and(listJson(['--type' => 'migration']))->toHaveCount(1);
});

it('rejects invalid filters', function () {
    $this->artisan('fuse:list', ['--severity' => 'nope'])->assertExitCode(2);
    $this->artisan('fuse:list', ['--type' => 'nope'])->assertExitCode(2);
    $this->artisan('fuse:list', ['--expiring' => 'soon'])->assertExitCode(2);
});

it('warns about problems without failing', function () {
    $this->scanning([__DIR__.'/../../Fixtures/InvalidFuse.php'], dirname(__DIR__, 3));

    $this->artisan('fuse:list')->expectsOutputToContain('Missing required Fuse argument "reason".')->assertExitCode(0);
});
