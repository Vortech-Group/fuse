<?php

declare(strict_types=1);

namespace Vortech\Fuse\Reporting;

use Vortech\Fuse\Contracts\FuseReporter;
use Vortech\Fuse\Data\CheckReport;
use Vortech\Fuse\Data\FuseProblem;

final class JsonReporter implements FuseReporter
{
    public function report(CheckReport $report): string
    {
        $items = $report->items;

        return json_encode([
            'status' => $report->passed() ? 'passed' : 'failed',
            'total' => $items->count(),
            'active' => $items->active()->count(),
            'expiring' => $items->expiring()->count(),
            'expired' => $items->expired()->count(),
            'items' => $items->toArray(),
            'problems' => array_map(fn (FuseProblem $problem): array => $problem->toArray(), $report->problems),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL;
    }
}
