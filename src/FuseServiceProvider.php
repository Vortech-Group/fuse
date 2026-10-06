<?php

declare(strict_types=1);

namespace Vortech\Fuse;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Vortech\Fuse\Commands\FuseCheckCommand;
use Vortech\Fuse\Commands\FuseDoctorCommand;
use Vortech\Fuse\Commands\FuseInstallCommand;
use Vortech\Fuse\Commands\FuseListCommand;
use Vortech\Fuse\Commands\FuseStatsCommand;
use Vortech\Fuse\Contracts\FuseScanner;
use Vortech\Fuse\Scanner\AttributeResolver;
use Vortech\Fuse\Scanner\PhpParserFuseScanner;
use Vortech\Fuse\Scanner\SourceFinder;
use Vortech\Fuse\Support\FuseConfig;

final class FuseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/fuse.php', 'fuse');

        $this->app->singleton(FuseConfig::class, fn (Application $app): FuseConfig => new FuseConfig($app->make(Config::class)));

        $this->app->singleton(SourceFinder::class, fn (Application $app): SourceFinder => new SourceFinder(
            paths: $app->make(FuseConfig::class)->paths(),
            ignore: $app->make(FuseConfig::class)->ignore(),
        ));

        $this->app->singleton(FuseScanner::class, function (Application $app): FuseScanner {
            $config = $app->make(FuseConfig::class);

            return new PhpParserFuseScanner(
                finder: $app->make(SourceFinder::class),
                resolver: new AttributeResolver,
                basePath: $app->basePath(),
                warnWithinDays: $config->warnWithinDays(),
                cache: $config->cacheEnabled() ? $app->make(CacheFactory::class)->store($config->cacheStore()) : null,
                cacheTtl: $config->cacheTtl(),
            );
        });

        $this->app->singleton(FuseManager::class);
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/fuse.php' => config_path('fuse.php'),
        ], 'fuse-config');

        $this->commands([
            FuseInstallCommand::class,
            FuseListCommand::class,
            FuseCheckCommand::class,
            FuseDoctorCommand::class,
            FuseStatsCommand::class,
        ]);
    }
}
