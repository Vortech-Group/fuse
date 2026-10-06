<?php

declare(strict_types=1);

it('passes for a healthy project', function () {
    $this->scanning([__DIR__.'/../../Fixtures/ActiveFuse.php'], dirname(__DIR__, 3));

    $this->artisan('fuse:doctor')
        ->expectsOutputToContain('Configuration loaded')
        ->expectsOutputToContain('1 PHP file scanned')
        ->expectsOutputToContain('1 Fuse attribute found')
        ->expectsOutputToContain('All Fuse attributes are valid')
        ->assertExitCode(0);
});

it('detects invalid dates and other invalid metadata', function () {
    $this->scanning([__DIR__.'/../../Fixtures/InvalidFuse.php'], dirname(__DIR__, 3));

    $this->artisan('fuse:doctor')
        ->expectsOutputToContain('Invalid expiration date "next Friday"')
        ->expectsOutputToContain('Unsupported FuseSeverity case "Blocker"')
        ->assertExitCode(2);
});

it('warns about missing owner and issue', function () {
    $root = $this->sourceTree(['A.php' => "<?php\nuse Vortech\\Fuse\\Attributes\\Fuse;\n#[Fuse(reason: 'x', expires: '2099-01-01')]\nclass A {}"]);
    $this->scanning([$root], $root);

    $this->artisan('fuse:doctor')
        ->expectsOutputToContain('1 item has no owner')
        ->expectsOutputToContain('1 item has no issue')
        ->assertExitCode(0);
});

it('errors on missing owner or issue when they are required', function () {
    $root = $this->sourceTree(['A.php' => "<?php\nuse Vortech\\Fuse\\Attributes\\Fuse;\n#[Fuse(reason: 'x', expires: '2099-01-01')]\nclass A {}"]);
    $this->scanning([$root], $root);

    config(['fuse.require_owner' => true]);
    $this->artisan('fuse:doctor')->expectsOutputToContain('Missing required Fuse argument "owner".')->assertExitCode(2);

    config(['fuse.require_owner' => false, 'fuse.require_issue' => true]);
    $this->artisan('fuse:doctor')->expectsOutputToContain('Missing required Fuse argument "issue".')->assertExitCode(2);
});

it('warns about malformed issue references, duplicates and long-expired items', function () {
    $root = $this->sourceTree(['A.php' => <<<'PHP'
        <?php
        use Vortech\Fuse\Attributes\Fuse;

        #[Fuse(reason: 'dup', expires: '2099-01-01', issue: 'fix it later')]
        #[Fuse(reason: 'dup', expires: '2099-01-01', issue: 'github:company/repository#143')]
        #[Fuse(reason: 'dup', expires: '2099-01-01', issue: 'https://github.com/company/project/issues/1')]
        #[Fuse(reason: 'old', expires: '2020-01-01', issue: '#12')]
        class A {}
        PHP]);
    $this->scanning([$root], $root);

    $this->artisan('fuse:doctor')
        ->expectsOutputToContain('A.php:4 has a malformed issue reference "fix it later"')
        ->expectsOutputToContain('A.php:6 repeats an identical Fuse attribute')
        ->expectsOutputToContain('1 item has been expired for more than 365 days')
        ->assertExitCode(0);
});

it('reports missing scan paths and parser failures', function () {
    $root = $this->sourceTree(['Broken.php' => "<?php\n// fuse\nclass { "]);
    $this->scanning([$root, $root.'/missing'], $root);

    $this->artisan('fuse:doctor')
        ->expectsOutputToContain('Scan path does not exist')
        ->assertExitCode(3);
});

it('reports an invalid configuration', function () {
    $this->scanning([__DIR__.'/../../Fixtures/ActiveFuse.php']);
    config(['fuse.fail_at_severity' => 'blocker']);

    $this->artisan('fuse:doctor')->expectsOutputToContain('fail_at_severity')->assertExitCode(2);
});
