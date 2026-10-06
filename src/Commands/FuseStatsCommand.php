<?php

declare(strict_types=1);

namespace Vortech\Fuse\Commands;

use Illuminate\Console\Command;
use Vortech\Fuse\Commands\Concerns\InteractsWithFuse;
use Vortech\Fuse\Data\FuseItem;
use Vortech\Fuse\Enums\FuseSeverity;
use Vortech\Fuse\Enums\FuseType;
use Vortech\Fuse\Exceptions\FuseException;

final class FuseStatsCommand extends Command
{
    use InteractsWithFuse;

    protected $signature = 'fuse:stats
        {--no-cache : Ignore the scan cache}';

    protected $description = 'Show technical debt statistics';

    public function handle(): int
    {
        try {
            $items = $this->fuse()->scan();
        } catch (FuseException $e) {
            return $this->failWith($e);
        }

        $window = $items->warnWithinDays();

        $this->line('<options=bold>Fuse statistics</>');
        $this->newLine();

        $this->row('Total', $items->count());
        $this->row('Active', $items->active()->count());
        $this->row(sprintf('Expiring within %dd', $window), $items->expiring()->count());
        $this->row('Expired', $items->expired()->count());

        $this->section('Severity', array_combine(
            array_map(fn (FuseSeverity $severity): string => ucfirst($severity->value), array_reverse(FuseSeverity::cases())),
            array_map(fn (FuseSeverity $severity): int => $items->filter(fn (FuseItem $item): bool => $item->severity === $severity)->count(), array_reverse(FuseSeverity::cases())),
        ));

        $this->section('Type', $this->sortedDescending(array_combine(
            array_map(fn (FuseType $type): string => ucfirst(str_replace('-', ' ', $type->value)), FuseType::cases()),
            array_map(fn (FuseType $type): int => $items->ofType($type)->count(), FuseType::cases()),
        )));

        $owners = [];

        foreach ($items as $item) {
            $owner = $item->owner ?? 'unassigned';
            $owners[$owner] = ($owners[$owner] ?? 0) + 1;
        }

        $this->section('Owners', $this->sortedDescending($owners));

        return self::SUCCESS;
    }

    /**
     * @param  array<string, int>  $counts
     */
    private function section(string $title, array $counts): void
    {
        $this->newLine();
        $this->line('<options=bold>'.$title.'</>');
        $this->newLine();

        foreach ($counts as $label => $count) {
            if ($count > 0) {
                $this->row($label, $count);
            }
        }
    }

    private function row(string $label, int $count): void
    {
        $this->line(sprintf('%s %3d', str_pad($label, 22), $count));
    }

    /**
     * @param  array<string, int>  $counts
     * @return array<string, int>
     */
    private function sortedDescending(array $counts): array
    {
        arsort($counts);

        return $counts;
    }
}
