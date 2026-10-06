<?php

declare(strict_types=1);

namespace Vortech\Fuse\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Vortech\Fuse\FuseServiceProvider;

#[AsCommand(name: 'fuse:install', description: 'Publish the Fuse configuration file')]
final class FuseInstallCommand extends Command
{
    protected $signature = 'fuse:install {--force : Overwrite the existing configuration file}';

    public function handle(): int
    {
        $this->call(
            command: 'vendor:publish',
            arguments: [
                '--provider' => FuseServiceProvider::class,
                '--tag' => 'fuse-config',
                '--force' => $this->option('force'),
            ]
        );

        $this->components->info('Fuse installed. Mark temporary code with #[Fuse] and run "php artisan fuse:check" in your CI.');

        return self::SUCCESS;
    }
}
