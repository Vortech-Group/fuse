<?php

declare(strict_types=1);

namespace Vortech\Fuse\Commands;

use Illuminate\Console\Command;
use PhpParser\ParserFactory;
use Vortech\Fuse\Commands\Concerns\InteractsWithFuse;
use Vortech\Fuse\Data\FuseItem;
use Vortech\Fuse\Exceptions\FuseException;
use Vortech\Fuse\Exceptions\InvalidFuseConfiguration;
use Vortech\Fuse\Scanner\SourceFinder;
use Vortech\Fuse\Support\FuseConfig;

final class FuseDoctorCommand extends Command
{
    use InteractsWithFuse;

    /** Items expired for longer than this are most likely forgotten. */
    private const int STALE_AFTER_DAYS = 365;

    protected $signature = 'fuse:doctor
        {--no-cache : Ignore the scan cache}';

    protected $description = 'Check the Fuse setup and the metadata of all Fuse items';

    /** @var list<array{string, string}> */
    private array $lines = [];

    private int $exitCode = 0;

    public function handle(): int
    {
        $this->line('<options=bold>Fuse Doctor</>');
        $this->newLine();

        $this->checkSetup();

        try {
            $this->checkItems();
        } catch (FuseException $e) {
            $this->addError('Scan failed: '.$e->getMessage(), $e->exitCode());
        }

        foreach ($this->lines as [$level, $message]) {
            $this->line(match ($level) {
                'ok' => '<fg=green>✓</> '.$message,
                'warning' => '<fg=yellow>⚠</> '.$message,
                default => '<fg=red>✗</> '.$message,
            });
        }

        return $this->exitCode;
    }

    private function checkSetup(): void
    {
        $config = $this->laravel->make(FuseConfig::class);

        if (! $config->isLoaded()) {
            $this->addError('Configuration not found. Publish it with: php artisan fuse:install', 2);
        } else {
            $this->addOk('Configuration loaded');
        }

        if (! class_exists(ParserFactory::class)) {
            $this->addError('nikic/php-parser is not installed', 2);
        }

        try {
            $config->failAtSeverity();
            $config->warnWithinDays();
            $config->failWithinDays();
            $config->requireOwner();
            $config->requireIssue();
            $this->addOk('Configuration values are valid');

            $finder = $this->laravel->make(SourceFinder::class);
        } catch (InvalidFuseConfiguration $e) {
            $this->addError($e->getMessage(), $e->exitCode());

            return;
        }

        foreach ($finder->missingPaths() as $path) {
            $this->addError('Scan path does not exist: '.$path, 2);
        }

        $this->addOk(sprintf('%d ignored %s', count($finder->ignoredPaths()), count($finder->ignoredPaths()) === 1 ? 'path' : 'paths'));
    }

    private function checkItems(): void
    {
        $fuse = $this->fuse();
        $result = $fuse->result();
        $items = $result->items;

        $this->addOk(sprintf('%d PHP %s scanned', $result->filesScanned, $result->filesScanned === 1 ? 'file' : 'files'));
        $this->addOk(sprintf('%d Fuse %s found', $items->count(), $items->count() === 1 ? 'attribute' : 'attributes'));

        $problems = [...$result->problems, ...$fuse->missingMetadata($items)];

        foreach ($problems as $problem) {
            $this->addError($problem->location().' '.$problem->message, $problem->exitCode());
        }

        if ($problems === []) {
            $this->addOk('All Fuse attributes are valid');
        }

        $this->warnAbout($items->filter(fn (FuseItem $item): bool => $item->owner === null)->count(), 'no owner');
        $this->warnAbout($items->filter(fn (FuseItem $item): bool => $item->issue === null)->count(), 'no issue');

        foreach ($items as $item) {
            if ($item->issue !== null && ! $this->isWellFormedIssue($item->issue)) {
                $this->addWarning(sprintf('%s has a malformed issue reference "%s"', $item->location(), $item->issue));
            }
        }

        foreach ($this->duplicates($items->all()) as $item) {
            $this->addWarning(sprintf('%s repeats an identical Fuse attribute', $item->location()));
        }

        $stale = $items->filter(fn (FuseItem $item): bool => $items->daysRemaining($item) < -self::STALE_AFTER_DAYS)->count();
        $this->warnAbout($stale, sprintf('been expired for more than %d days', self::STALE_AFTER_DAYS), 'has', 'have');
    }

    private function warnAbout(int $count, string $what, string $singular = 'has', string $plural = 'have'): void
    {
        if ($count > 0) {
            $this->addWarning(sprintf('%d %s %s %s', $count, $count === 1 ? 'item' : 'items', $count === 1 ? $singular : $plural, $what));
        }
    }

    private function isWellFormedIssue(string $issue): bool
    {
        return preg_match('/^[A-Za-z][A-Za-z0-9_]*-\d+$/', $issue) === 1      // FIS-142
            || preg_match('/^#\d+$/', $issue) === 1                            // #142
            || preg_match('/^[\w.-]+:[\w.\/-]+#\d+$/', $issue) === 1           // github:company/repository#143
            || filter_var($issue, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Items whose metadata and target are identical to an earlier item.
     *
     * @param  list<FuseItem>  $items
     * @return list<FuseItem>
     */
    private function duplicates(array $items): array
    {
        $seen = [];
        $duplicates = [];

        foreach ($items as $item) {
            $key = serialize([$item->file, $item->class, $item->method, $item->property, $item->reason, $item->expiresAt->format('Y-m-d')]);

            isset($seen[$key]) ? $duplicates[] = $item : $seen[$key] = true;
        }

        return $duplicates;
    }

    private function addOk(string $message): void
    {
        $this->lines[] = ['ok', $message];
    }

    private function addWarning(string $message): void
    {
        $this->lines[] = ['warning', $message];
    }

    private function addError(string $message, int $exitCode): void
    {
        $this->lines[] = ['error', $message];
        $this->exitCode = max($this->exitCode, $exitCode);
    }
}
