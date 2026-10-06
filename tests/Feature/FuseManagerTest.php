<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;
use Vortech\Fuse\Facades\Fuse;
use Vortech\Fuse\FuseManager;
use Vortech\Fuse\FuseServiceProvider;

beforeEach(function () {
    $this->scanning([__DIR__.'/../Fixtures/ActiveFuse.php', __DIR__.'/../Fixtures/ExpiredFuse.php'], dirname(__DIR__, 2));
});

it('exposes the scan through a programmatic API', function () {
    $fuse = app(FuseManager::class);

    expect($fuse->scan())->toHaveCount(2)
        ->and($fuse->expired())->toHaveCount(1)
        ->and($fuse->expiringWithin(14))->toHaveCount(0)
        ->and($fuse->result()->filesScanned)->toBe(2);
});

it('is available as a facade', function () {
    expect(Fuse::expired())->toHaveCount(1);
});

it('is registered by the service provider', function () {
    expect(config('fuse.warn_within_days'))->toBe(14)
        ->and(array_keys(Artisan::all()))->toContain('fuse:install', 'fuse:list', 'fuse:check', 'fuse:doctor', 'fuse:stats');
});

it('offers its config file for publishing', function () {
    // Only inspect the publish map: Testbench's config_path() points at the package's own config directory.
    $paths = ServiceProvider::pathsToPublish(FuseServiceProvider::class, 'fuse-config');

    expect(array_keys($paths)[0])->toEndWith('config/fuse.php')
        ->and(array_values($paths)[0])->toEndWith('config/fuse.php');
});
