<?php

declare(strict_types=1);

namespace Vortech\Fuse\Commands;

use Illuminate\Console\Command;
use Vortech\Fuse\Commands\Concerns\InteractsWithFuse;
use Vortech\Fuse\Data\FuseItem;
use Vortech\Fuse\Enums\FuseSeverity;
use Vortech\Fuse\Enums\FuseType;
use Vortech\Fuse\Exceptions\FuseException;

final class FuseListCommand extends Command
{
    use InteractsWithFuse;

    protected $signature = 'fuse:list
        {--expired : Only show expired items}
        {--expiring= : Only show items that expire within this many days}
        {--owner= : Only show items owned by this owner}
        {--severity= : Only show items of at least this severity (low, medium, high, critical)}
        {--type= : Only show items of this type}
        {--json : Output the items as JSON}
        {--no-cache : Ignore the scan cache}';

    protected $description = 'List all tracked Fuse items';

    public function handle(): int
    {
        try {
            $result = $this->fuse()->result();
            $items = $result->items;

            if ($this->option('expired')) {
                $items = $items->expired();
            }

            if (($days = $this->stringOption('expiring')) !== null) {
                if (! ctype_digit($days)) {
                    $this->components->error('The --expiring option must be a non-negative number of days.');

                    return 2;
                }

                $items = $items->expiringWithin((int) $days);
            }

            if (($owner = $this->stringOption('owner')) !== null) {
                $items = $items->ownedBy($owner);
            }

            if (($severity = $this->stringOption('severity')) !== null) {
                $items = $items->severityAtLeast(FuseSeverity::fromInput($severity));
            }

            if (($type = $this->stringOption('type')) !== null) {
                $items = $items->ofType(FuseType::fromInput($type));
            }
        } catch (FuseException $e) {
            return $this->failWith($e);
        }

        if ($this->option('json')) {
            $this->line(json_encode($items->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        }

        $this->line('<options=bold>Fuse items</>');
        $this->newLine();

        if ($items->isEmpty()) {
            $this->components->info('No Fuse items found.');
        } else {
            $this->table(
                ['Status', 'Expires', 'Severity', 'Owner', 'Location', 'Reason'],
                array_map(fn (FuseItem $item): array => [
                    $items->status($item)->value,
                    $item->expiresAt->format('Y-m-d'),
                    $item->severity->value,
                    $item->owner ?? '-',
                    $item->location(),
                    $item->reason,
                ], $items->sorted()->all()),
            );
        }

        foreach ($result->problems as $problem) {
            $this->components->warn($problem->location().' '.$problem->message);
        }

        return self::SUCCESS;
    }
}
