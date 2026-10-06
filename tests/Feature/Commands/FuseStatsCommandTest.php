<?php

declare(strict_types=1);

function row(string $label, int $count): string
{
    return str_pad($label, 22).sprintf(' %3d', $count);
}

it('shows statistics', function () {
    $this->scanning([__DIR__.'/../../Fixtures/ActiveFuse.php', __DIR__.'/../../Fixtures/ExpiredFuse.php', __DIR__.'/../../Fixtures/MultipleFuses.php'], dirname(__DIR__, 3));

    $this->artisan('fuse:stats')
        ->expectsOutputToContain('Fuse statistics')
        ->expectsOutputToContain(row('Total', 8))
        ->expectsOutputToContain(row('Expired', 1))
        ->expectsOutputToContain(row('High', 1))
        ->expectsOutputToContain(row('Workaround', 1))
        ->expectsOutputToContain(row('unassigned', 5))
        ->expectsOutputToContain(row('backend', 2))
        ->assertExitCode(0);
});
