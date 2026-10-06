<?php

declare(strict_types=1);

beforeEach(function () {
    $this->configPath = config_path('fuse.php');

    @unlink($this->configPath);
});

afterEach(function () {
    @unlink($this->configPath);
});

it('publishes the config file', function () {
    $this->artisan('fuse:install')->assertSuccessful();

    expect($this->configPath)->toBeFile();
});

it('does not overwrite an existing config without --force', function () {
    file_put_contents($this->configPath, '<?php return [];');

    $this->artisan('fuse:install')->assertSuccessful();

    expect(file_get_contents($this->configPath))->toBe('<?php return [];');
});

it('overwrites an existing config with --force', function () {
    file_put_contents($this->configPath, '<?php return [];');

    $this->artisan('fuse:install', ['--force' => true])->assertSuccessful();

    expect(file_get_contents($this->configPath))->toContain('warn_within_days');
});

it('never touches the package config', function () {
    $this->artisan('fuse:install', ['--force' => true])->assertSuccessful();

    expect(realpath(__DIR__.'/../../../config/fuse.php'))->toBeFile();
});
