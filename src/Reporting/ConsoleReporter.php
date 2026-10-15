<?php

declare(strict_types=1);

namespace Vortech\Fuse\Reporting;

use Symfony\Component\Console\Formatter\OutputFormatter;
use Vortech\Fuse\Contracts\FuseReporter;
use Vortech\Fuse\Data\CheckReport;
use Vortech\Fuse\Data\FuseItem;
use Vortech\Fuse\Enums\FuseEditor;
use Vortech\Fuse\Enums\FuseSeverity;
use Vortech\Fuse\Support\Path;

/**
 * Human-readable output. The text contains Symfony console style tags, so write it with a console output.
 */
final class ConsoleReporter implements FuseReporter
{
    /**
     * @param  string|null  $basePath  Project root the reported (relative) file paths belong to. Without it no links are rendered.
     * @param  FuseEditor|null  $editor  Editor to open files in. Without it links are plain file:// URLs.
     */
    public function __construct(
        private readonly ?string $basePath = null,
        private readonly ?FuseEditor $editor = null,
    ) {}

    public function report(CheckReport $report): string
    {
        $items = $report->items;

        $lines = [
            '',
            sprintf(
                ' <fg=black;bg=yellow;options=bold> FUSE </> <fg=gray>%s in %s</>',
                $this->plural($items->count(), 'item'),
                $this->plural($report->filesScanned, 'file'),
            ),
            '',
            sprintf(
                '  <fg=red;options=bold>✗ %d expired</>    <fg=yellow;options=bold>⚠ %d expiring</>    <fg=green;options=bold>✓ %d active</>',
                $items->expired()->count(),
                $items->expiring()->count(),
                $items->active()->count(),
            ),
        ];

        if (! $report->failing->isEmpty()) {
            $lines[] = '';
            $lines[] = sprintf(' <fg=red;options=bold>FAILING</> <fg=gray>(%d)</>', $report->failing->count());

            foreach ($report->failing->sorted() as $item) {
                array_push($lines, ...$this->item($report, $item, 'red'));
            }
        }

        if (! $report->warnings->isEmpty()) {
            $lines[] = '';
            $lines[] = sprintf(' <fg=yellow;options=bold>WARNINGS</> <fg=gray>(%d)</>', $report->warnings->count());

            foreach ($report->warnings->sorted() as $item) {
                array_push($lines, ...$this->item($report, $item, 'yellow'));
            }
        }

        if (filled($report->problems)) {
            $lines[] = '';
            $lines[] = sprintf(' <fg=red;options=bold>PROBLEMS</> <fg=gray>(%d)</>', count($report->problems));

            foreach ($report->problems as $problem) {
                $lines[] = '';
                $lines[] = '  '.$this->location($problem->file, $problem->line);
                $lines[] = '  '.OutputFormatter::escape($problem->message);
            }
        }

        $lines[] = '';
        $lines[] = $this->verdict($report);
        $lines[] = '';

        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    /**
     * @return list<string>
     */
    private function item(CheckReport $report, FuseItem $item, string $color): array
    {
        $lines = [
            '',
            sprintf('  %s %s', $this->severityBadge($item->severity), $this->location($item->file, $item->line)),
            '  '.OutputFormatter::escape($item->reason),
            '',
            sprintf('    <fg=%s>%s</>', $color, $this->message($report, $item)),
        ];

        $details = [
            'Target' => $item->target(),
            'Owner' => $item->owner,
            'Issue' => $item->issue,
            'Replacement' => $item->replacement,
        ];

        foreach ($details as $label => $value) {
            if (filled($value)) {
                $lines[] = sprintf('    <fg=gray>%s</> %s', str_pad($label, 12), OutputFormatter::escape($value));
            }
        }

        return $lines;
    }

    /**
     * Bold `file:line`, wrapped in a terminal hyperlink to the source when a base path is known.
     */
    private function location(string $file, int $line): string
    {
        $label = OutputFormatter::escape($line > 0 ? $file.':'.$line : $file);

        if ($this->basePath === null) {
            return sprintf('<options=bold>%s</>', $label);
        }

        $absolute = str_starts_with($file, '/') || preg_match('~^[A-Za-z]:/~', $file) === 1
            ? $file
            : Path::normalize($this->basePath).'/'.$file;

        $url = $this->editor?->url($absolute, $line)
            ?? 'file://'.str_replace('%2F', '/', rawurlencode($absolute));

        return sprintf('<href=%s;options=bold>%s</>', $url, $label);
    }

    private function severityBadge(FuseSeverity $severity): string
    {
        $style = match ($severity) {
            FuseSeverity::Critical => 'fg=white;bg=red;options=bold',
            FuseSeverity::High => 'fg=black;bg=red',
            FuseSeverity::Medium => 'fg=black;bg=yellow',
            FuseSeverity::Low => 'fg=black;bg=gray',
        };

        return sprintf('<%s> %s </>', $style, strtoupper($severity->value));
    }

    private function message(CheckReport $report, FuseItem $item): string
    {
        $remaining = $report->items->daysRemaining($item);
        $date = $item->expiresAt->format('Y-m-d');

        return match (true) {
            $remaining < 0 => sprintf('Expired %s (%s ago)', $date, $this->plural(-$remaining, 'day')),
            $remaining === 0 => sprintf('Expired %s (today)', $date),
            default => sprintf('Expires %s (in %s)', $date, $this->plural($remaining, 'day')),
        };
    }

    private function verdict(CheckReport $report): string
    {
        if ($report->passed()) {
            return ' <fg=black;bg=green;options=bold> PASS </> No fuse is past its deadline.';
        }

        $reasons = array_filter([
            $report->failing->isEmpty() ? null : $this->plural($report->failing->count(), 'fuse').' to fix',
            blank($report->problems) ? null : $this->plural(count($report->problems), 'problem'),
        ]);

        return sprintf(' <fg=white;bg=red;options=bold> FAIL </> %s', implode(', ', $reasons));
    }

    private function plural(int $count, string $noun): string
    {
        return sprintf('%d %s%s', $count, $noun, $count === 1 ? '' : 's');
    }
}
