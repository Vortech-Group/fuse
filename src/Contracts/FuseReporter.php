<?php

declare(strict_types=1);

namespace Vortech\Fuse\Contracts;

use Vortech\Fuse\Data\CheckReport;

interface FuseReporter
{
    /**
     * Render the report. The command decides how the returned text is written.
     */
    public function report(CheckReport $report): string;
}
