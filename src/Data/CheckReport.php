<?php

declare(strict_types=1);

namespace Vortech\Fuse\Data;

use Vortech\Fuse\Support\FuseCollection;

/**
 * Outcome of `fuse:check`: what fails the build, what only warns, and what is wrong with the metadata itself.
 */
final readonly class CheckReport
{
    /**
     * @param  FuseCollection  $items  Every discovered item
     * @param  FuseCollection  $failing  Items that fail the check
     * @param  FuseCollection  $warnings  Expired or expiring items that do not fail the check
     * @param  list<FuseProblem>  $problems  Invalid attributes, missing required metadata and parse errors
     */
    public function __construct(
        public FuseCollection $items,
        public FuseCollection $failing,
        public FuseCollection $warnings,
        public array $problems,
        public int $failWithinDays,
        public int $filesScanned,
    ) {}

    public function passed(): bool
    {
        return $this->exitCode() === 0;
    }

    /**
     * 0 = success, 1 = expired / threshold violation, 2 = invalid Fuse metadata, 3 = scanner failure.
     */
    public function exitCode(): int
    {
        $codes = array_map(fn (FuseProblem $problem): int => $problem->exitCode(), $this->problems);

        if (! $this->failing->isEmpty()) {
            $codes[] = 1;
        }

        return $codes === [] ? 0 : max($codes);
    }
}
