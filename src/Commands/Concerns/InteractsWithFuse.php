<?php

declare(strict_types=1);

namespace Vortech\Fuse\Commands\Concerns;

use Vortech\Fuse\Contracts\FuseScanner;
use Vortech\Fuse\Exceptions\FuseException;
use Vortech\Fuse\FuseManager;
use Vortech\Fuse\Support\FuseConfig;

/**
 * Shared plumbing for the fuse:* commands. Expects the `--no-cache` option to be declared in the signature.
 */
trait InteractsWithFuse
{
    protected function fuse(): FuseManager
    {
        if ($this->option('no-cache')) {
            // The scanner is built once from config, so rebuild it without the cache.
            $this->laravel->make(FuseConfig::class)->disableCache();
            $this->laravel->forgetInstance(FuseScanner::class);
            $this->laravel->forgetInstance(FuseManager::class);
        }

        return $this->laravel->make(FuseManager::class);
    }

    /**
     * An option's value as text (ints appear when a command is called programmatically), otherwise null.
     */
    protected function stringOption(string $name): ?string
    {
        $value = $this->option($name);

        return is_string($value) || is_int($value) ? (string) $value : null;
    }

    protected function failWith(FuseException $exception): int
    {
        $this->components->error($exception->getMessage());

        return $exception->exitCode();
    }
}
