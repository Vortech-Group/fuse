<?php

declare(strict_types=1);

namespace Vortech\Fuse\Reporting;

use Vortech\Fuse\Contracts\FuseReporter;
use Vortech\Fuse\Data\CheckReport;
use Vortech\Fuse\Data\FuseItem;

/**
 * GitHub Actions workflow commands, rendered as annotations on the source files.
 */
final class GithubReporter implements FuseReporter
{
    public function report(CheckReport $report): string
    {
        $lines = [];

        foreach ($report->failing->sorted() as $item) {
            $lines[] = $this->annotation('error', $item->file, $item->line, $this->message($report, $item));
        }

        foreach ($report->warnings->sorted() as $item) {
            $lines[] = $this->annotation('warning', $item->file, $item->line, $this->message($report, $item));
        }

        foreach ($report->problems as $problem) {
            $lines[] = $this->annotation('error', $problem->file, $problem->line, 'Fuse problem: '.$problem->message);
        }

        return $lines === [] ? '' : implode(PHP_EOL, $lines).PHP_EOL;
    }

    private function message(CheckReport $report, FuseItem $item): string
    {
        $remaining = $report->items->daysRemaining($item);

        return $remaining <= 0
            ? 'Fuse expired: '.$item->reason
            : sprintf('Fuse expires in %d %s: %s', $remaining, $remaining === 1 ? 'day' : 'days', $item->reason);
    }

    private function annotation(string $level, string $file, int $line, string $message): string
    {
        $properties = 'file='.$this->escape($file, true);

        if ($line > 0) {
            $properties .= ',line='.$line;
        }

        return sprintf('::%s %s::%s', $level, $properties, $this->escape($message, false));
    }

    private function escape(string $value, bool $property): string
    {
        $value = str_replace(['%', "\r", "\n"], ['%25', '%0D', '%0A'], $value);

        return $property ? str_replace([':', ','], ['%3A', '%2C'], $value) : $value;
    }
}
