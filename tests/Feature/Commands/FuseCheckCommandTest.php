<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Vortech\Fuse\Enums\FuseSeverity;

function useFixtures(object $test, array $only = []): void
{
    $paths = $only === [] ? [__DIR__.'/../../Fixtures'] : array_map(fn ($f) => __DIR__.'/../../Fixtures/'.$f, $only);
    $test->scanning($paths, dirname(__DIR__, 3));
}

it('returns 0 when nothing has expired', function () {
    useFixtures($this, ['ActiveFuse.php']);

    $this->artisan('fuse:check')->expectsOutputToContain('PASS')->assertExitCode(0);
});

it('returns 1 when a Fuse has expired', function () {
    useFixtures($this, ['ActiveFuse.php', 'ExpiredFuse.php']);

    $this->artisan('fuse:check')
        ->expectsOutputToContain('FAIL')
        ->expectsOutputToContain('tests/Fixtures/ExpiredFuse.php:13')
        ->expectsOutputToContain('Fallback until the payment gateway fixes duplicate callbacks')
        ->expectsOutputToContain('Expired 2020-01-01')
        ->expectsOutputToContain('payments')
        ->expectsOutputToContain('PAY-7')
        ->assertExitCode(1);
});

it('returns 2 for invalid attributes', function () {
    useFixtures($this, ['InvalidFuse.php']);

    $this->artisan('fuse:check')->expectsOutputToContain('PROBLEMS')->assertExitCode(2);
});

it('returns 3 when a file cannot be parsed', function () {
    $root = $this->sourceTree(['Broken.php' => "<?php\n// fuse\nclass { "]);
    $this->scanning([$root], $root);

    $this->artisan('fuse:check')->assertExitCode(3);
});

it('fails before the expiration date with --fail-within', function () {
    useFixtures($this, ['ActiveFuse.php']);
    $this->travelTo('2099-11-27');

    $this->artisan('fuse:check')->assertExitCode(0);
    $this->artisan('fuse:check', ['--fail-within' => 3])->assertExitCode(0);
    $this->artisan('fuse:check', ['--fail-within' => 4])->expectsOutputToContain('Expires 2099-12-01 (in 4 days)')->assertExitCode(1);
});

it('takes the failure window from config', function () {
    useFixtures($this, ['ActiveFuse.php']);
    $this->travelTo('2099-11-27');
    config(['fuse.fail_within_days' => 7]);

    $this->artisan('fuse:check')->assertExitCode(1);
});

it('only fails for items at or above the severity threshold', function () {
    useFixtures($this, ['ExpiredFuse.php']); // severity: high

    $this->artisan('fuse:check', ['--severity' => 'critical'])->expectsOutputToContain('WARNINGS')->assertExitCode(0);
    $this->artisan('fuse:check', ['--severity' => 'high'])->assertExitCode(1);
    $this->artisan('fuse:check', ['--severity' => 'low'])->assertExitCode(1);
});

it('honours fail_at_severity from config as enum or string', function () {
    useFixtures($this, ['ExpiredFuse.php']);

    config(['fuse.fail_at_severity' => 'critical']);
    $this->artisan('fuse:check')->assertExitCode(0);

    config(['fuse.fail_at_severity' => FuseSeverity::High]);
    $this->artisan('fuse:check')->assertExitCode(1);
});

it('fails when required metadata is missing', function () {
    $root = $this->sourceTree(['A.php' => "<?php\nuse Vortech\\Fuse\\Attributes\\Fuse;\n#[Fuse(reason: 'x', expires: '2099-01-01')]\nclass A {}"]);
    $this->scanning([$root], $root);

    $this->artisan('fuse:check')->assertExitCode(0);

    config(['fuse.require_owner' => true]);
    $this->artisan('fuse:check')->expectsOutputToContain('Missing required Fuse argument "owner".')->assertExitCode(2);

    config(['fuse.require_owner' => false, 'fuse.require_issue' => true]);
    $this->artisan('fuse:check')->expectsOutputToContain('Missing required Fuse argument "issue".')->assertExitCode(2);
});

it('prints a JSON report', function () {
    useFixtures($this, ['ActiveFuse.php', 'ExpiredFuse.php']);

    $exit = Artisan::call('fuse:check', ['--format' => 'json']);
    $json = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

    expect($exit)->toBe(1)
        ->and($json)->toMatchArray(['status' => 'failed', 'total' => 2, 'active' => 1, 'expiring' => 0, 'expired' => 1])
        ->and($json['items'])->toHaveCount(2)
        ->and($json['items'][0])->toMatchArray(['status' => 'expired', 'severity' => 'high', 'type' => 'workaround', 'owner' => 'payments'])
        ->and($json['problems'])->toBe([]);
});

it('prints GitHub annotations', function () {
    useFixtures($this, ['ExpiredFuse.php']);

    Artisan::call('fuse:check', ['--format' => 'github']);

    expect(Artisan::output())->toBe("::error file=tests/Fixtures/ExpiredFuse.php,line=13::Fuse expired: Fallback until the payment gateway fixes duplicate callbacks\n");
});

it('annotates expiring items as warnings and escapes special characters', function () {
    $root = $this->sourceTree(['A, b.php' => "<?php\nuse Vortech\\Fuse\\Attributes\\Fuse;\n#[Fuse(reason: '100% soon', expires: '2099-01-10')]\nclass A {}"]);
    $this->scanning([$root], $root);
    $this->travelTo('2099-01-06');

    Artisan::call('fuse:check', ['--format' => 'github']);

    expect(Artisan::output())->toBe("::warning file=A%2C b.php,line=3::Fuse expires in 4 days: 100%25 soon\n");
});

it('prints nothing in GitHub format when everything is fine', function () {
    useFixtures($this, ['ActiveFuse.php']);

    Artisan::call('fuse:check', ['--format' => 'github']);

    expect(Artisan::output())->toBe('');
});

it('rejects invalid options with exit code 2', function () {
    useFixtures($this, ['ActiveFuse.php']);

    $this->artisan('fuse:check', ['--format' => 'xml'])->assertExitCode(2);
    $this->artisan('fuse:check', ['--severity' => 'blocker'])->assertExitCode(2);
    $this->artisan('fuse:check', ['--fail-within' => 'soon'])->assertExitCode(2);
});

it('works with --no-cache', function () {
    useFixtures($this, ['ExpiredFuse.php']);

    $this->artisan('fuse:check', ['--no-cache' => true])->assertExitCode(1);
});
