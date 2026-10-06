<?php

declare(strict_types=1);

namespace Vortech\Fuse\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Vortech\Fuse\Commands\Concerns\InteractsWithFuse;
use Vortech\Fuse\Contracts\FuseReporter;
use Vortech\Fuse\Exceptions\FuseException;
use Vortech\Fuse\Exceptions\InvalidFuseConfiguration;
use Vortech\Fuse\Reporting\ConsoleReporter;
use Vortech\Fuse\Reporting\GithubReporter;
use Vortech\Fuse\Reporting\JsonReporter;

final class FuseCheckCommand extends Command
{
    use InteractsWithFuse;

    protected $signature = 'fuse:check
        {--fail-within= : Also fail for items that expire within this many days}
        {--severity= : Only items of at least this severity fail the check}
        {--format=console : Output format: console, json or github}
        {--no-cache : Ignore the scan cache}';

    protected $description = 'Fail when Fuse items have expired';

    public function handle(): int
    {
        try {
            $format = $this->stringOption('format') ?? 'console';
            $reporter = $this->reporter($format);

            $failWithin = $this->stringOption('fail-within');

            if ($failWithin !== null && ! ctype_digit($failWithin)) {
                throw new InvalidFuseConfiguration('The --fail-within option must be a non-negative number of days.');
            }

            $report = $this->fuse()->check(
                failWithinDays: $failWithin === null ? null : (int) $failWithin,
                severity: $this->stringOption('severity'),
            );
        } catch (FuseException $e) {
            return $this->failWith($e);
        }

        $output = $reporter->report($report);

        if ($format === 'console') {
            foreach (explode(PHP_EOL, rtrim($output)) as $line) {
                $this->line($line);
            }

            return $report->exitCode();
        }

        // Machine-readable formats must reach the output untouched, without style tags being interpreted.
        $this->output->write($output, false, OutputInterface::OUTPUT_RAW);

        return $report->exitCode();
    }

    /**
     * @throws InvalidFuseConfiguration
     */
    private function reporter(string $format): FuseReporter
    {
        return match ($format) {
            'console' => new ConsoleReporter,
            'json' => new JsonReporter,
            'github' => new GithubReporter,
            default => throw InvalidFuseConfiguration::invalidValue('format', $format, ['console', 'json', 'github']),
        };
    }
}
