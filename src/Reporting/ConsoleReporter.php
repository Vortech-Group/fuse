<?php

declare(strict_types=1);

namespace Vortech\Fuse\Reporting;

use Symfony\Component\Console\Formatter\OutputFormatter;
use Vortech\Fuse\Contracts\FuseReporter;
use Vortech\Fuse\Data\CheckReport;
use Vortech\Fuse\Data\FuseItem;

/**
 * Human-readable output. The text contains Symfony console style tags, so write it with a console output.
 */
final class ConsoleReporter implements FuseReporter
{
    public function report(CheckReport $report): string
    {
        $items = $report->items;
        $lines = ['<options=bold>Fuse check</>', ''];

        $lines[] = sprintf('  <fg=green>✓</> %d active', $items->active()->count());
        $lines[] = sprintf('  <fg=yellow>⚠</> %d expiring', $items->expiring()->count());
        $lines[] = sprintf('  <fg=red>✗</> %d expired', $items->expired()->count());
        $lines[] = '';
        $lines[] = $report->passed() ? '<fg=green;options=bold>PASS</>' : '<fg=red;options=bold>FAIL</>';

        if (! $report->failing->isEmpty()) {
            $lines[] = '';
            $lines[] = '<fg=red;options=bold>FAILING</>';

            foreach ($report->failing->sorted() as $item) {
                array_push($lines, ...$this->item($item, $this->message($report, $item)));
            }
        }

        if (! $report->warnings->isEmpty()) {
            $lines[] = '';
            $lines[] = '<fg=yellow;options=bold>WARNINGS</>';

            foreach ($report->warnings->sorted() as $item) {
                array_push($lines, ...$this->item($item, $this->message($report, $item)));
            }
        }

        if ($report->problems !== []) {
            $lines[] = '';
            $lines[] = '<fg=red;options=bold>PROBLEMS</>';

            foreach ($report->problems as $problem) {
                $lines[] = '';
                $lines[] = sprintf('<options=bold>%s</>', OutputFormatter::escape($problem->location()));
                $lines[] = OutputFormatter::escape($problem->message);
            }
        }

        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    /**
     * @return list<string>
     */
    private function item(FuseItem $item, string $message): array
    {
        $lines = [
            '',
            sprintf('[%s] <options=bold>%s</>', strtoupper($item->severity->value), OutputFormatter::escape($item->location())),
            OutputFormatter::escape($item->reason),
            '',
            '  '.OutputFormatter::escape($message),
        ];

        $details = [
            'Target' => $item->target(),
            'Owner' => $item->owner,
            'Issue' => $item->issue,
            'Replacement' => $item->replacement,
        ];

        foreach ($details as $label => $value) {
            if ($value !== null) {
                $lines[] = sprintf('  %s %s', str_pad($label, 12), OutputFormatter::escape($value));
            }
        }

        return $lines;
    }

    private function message(CheckReport $report, FuseItem $item): string
    {
        $remaining = $report->items->daysRemaining($item);
        $date = $item->expiresAt->format('Y-m-d');

        return $remaining <= 0
            ? 'Expired '.$date
            : sprintf('Expires %s (in %d %s)', $date, $remaining, $remaining === 1 ? 'day' : 'days');
    }
}
