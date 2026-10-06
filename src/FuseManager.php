<?php

declare(strict_types=1);

namespace Vortech\Fuse;

use Vortech\Fuse\Contracts\FuseScanner;
use Vortech\Fuse\Data\CheckReport;
use Vortech\Fuse\Data\FuseItem;
use Vortech\Fuse\Data\FuseProblem;
use Vortech\Fuse\Data\ScanResult;
use Vortech\Fuse\Enums\FuseProblemType;
use Vortech\Fuse\Enums\FuseSeverity;
use Vortech\Fuse\Exceptions\FuseException;
use Vortech\Fuse\Support\FuseCollection;
use Vortech\Fuse\Support\FuseConfig;

final readonly class FuseManager
{
    public function __construct(
        private FuseScanner $scanner,
        private FuseConfig $config,
    ) {}

    /**
     * @throws FuseException
     */
    public function result(): ScanResult
    {
        return $this->scanner->scan();
    }

    /**
     * @throws FuseException
     */
    public function scan(): FuseCollection
    {
        return $this->result()->items;
    }

    /**
     * @throws FuseException
     */
    public function expired(): FuseCollection
    {
        return $this->scan()->expired();
    }

    /**
     * @throws FuseException
     */
    public function expiringWithin(int $days): FuseCollection
    {
        return $this->scan()->expiringWithin($days);
    }

    /**
     * Scan and decide which items fail the build.
     *
     * @param  int|null  $failWithinDays  Fail items expiring within this many days. Defaults to `fuse.fail_within_days`.
     * @param  FuseSeverity|string|null  $severity  Lowest severity that can fail. Defaults to `fuse.fail_at_severity`.
     *
     * @throws FuseException
     */
    public function check(?int $failWithinDays = null, FuseSeverity|string|null $severity = null): CheckReport
    {
        $failWithinDays = max($failWithinDays ?? $this->config->failWithinDays(), 0);
        $threshold = $severity === null ? $this->config->failAtSeverity() : FuseSeverity::fromInput($severity);

        $result = $this->result();

        $failing = $result->items
            ->severityAtLeast($threshold)
            ->filter(fn (FuseItem $item): bool => $result->items->daysRemaining($item) <= $failWithinDays);

        $warnings = $result->items->filter(
            fn (FuseItem $item): bool => ! in_array($item, $failing->all(), true)
                && $result->items->daysRemaining($item) <= $result->items->warnWithinDays(),
        );

        return new CheckReport(
            items: $result->items,
            failing: $failing,
            warnings: $warnings,
            problems: [...$result->problems, ...$this->missingMetadata($result->items)],
            failWithinDays: $failWithinDays,
            filesScanned: $result->filesScanned,
        );
    }

    /**
     * Problems for items that lack metadata the project requires through `fuse.require_owner` / `fuse.require_issue`.
     *
     * @return list<FuseProblem>
     *
     * @throws FuseException
     */
    public function missingMetadata(FuseCollection $items): array
    {
        $problems = [];

        foreach ($items as $item) {
            if ($this->config->requireOwner() && $item->owner === null) {
                $problems[] = new FuseProblem(FuseProblemType::InvalidAttribute, 'Missing required Fuse argument "owner".', $item->file, $item->line);
            }

            if ($this->config->requireIssue() && $item->issue === null) {
                $problems[] = new FuseProblem(FuseProblemType::InvalidAttribute, 'Missing required Fuse argument "issue".', $item->file, $item->line);
            }
        }

        return $problems;
    }
}
