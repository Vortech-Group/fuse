<?php

declare(strict_types=1);

namespace Vortech\Fuse\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Vortech\Fuse\Contracts\FuseScanner;
use Vortech\Fuse\FuseManager;
use Vortech\Fuse\FuseServiceProvider;
use Vortech\Fuse\Scanner\SourceFinder;

abstract class TestCase extends Orchestra
{
    /** @var list<string> */
    private array $temporaryDirectories = [];

    protected function getPackageProviders($app): array
    {
        return [FuseServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('fuse.paths', [__DIR__.'/Fixtures']);
        $app['config']->set('fuse.ignore', []);
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryDirectories as $directory) {
            $this->removeDirectory($directory);
        }

        parent::tearDown();
    }

    /**
     * Create a temporary source tree from a map of relative path => file contents.
     *
     * @param  array<string, string>  $files
     */
    public function sourceTree(array $files): string
    {
        $root = sys_get_temp_dir().'/fuse-tests-'.bin2hex(random_bytes(6));
        mkdir($root, 0777, true);
        $this->temporaryDirectories[] = $root;

        foreach ($files as $path => $contents) {
            $file = $root.'/'.$path;

            if (! is_dir(dirname($file))) {
                mkdir(dirname($file), 0777, true);
            }

            file_put_contents($file, $contents);
        }

        return (string) realpath($root);
    }

    /**
     * Point Fuse at the given directories and make them the project root.
     *
     * @param  list<string>  $paths
     */
    public function scanning(array $paths, ?string $basePath = null): void
    {
        config(['fuse.paths' => $paths, 'fuse.ignore' => []]);

        // Services are singletons built from config, so rebuild them for the new paths.
        foreach ([FuseManager::class, FuseScanner::class, SourceFinder::class] as $abstract) {
            $this->app->forgetInstance($abstract);
        }

        if ($basePath !== null) {
            $this->app->setBasePath($basePath);
        }
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory.'/'.$entry;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($directory);
    }
}
