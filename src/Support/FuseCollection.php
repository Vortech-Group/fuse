<?php

declare(strict_types=1);

namespace Vortech\Fuse\Support;

use ArrayIterator;
use Carbon\CarbonImmutable;
use Countable;
use IteratorAggregate;
use Traversable;
use Vortech\Fuse\Data\FuseItem;
use Vortech\Fuse\Enums\FuseSeverity;
use Vortech\Fuse\Enums\FuseStatus;
use Vortech\Fuse\Enums\FuseType;

/**
 * Immutable collection of {@see FuseItem}s. Every filter returns a new collection that keeps the same
 * point in time and warning window, so statuses stay consistent across a chain of calls.
 *
 * @implements IteratorAggregate<int, FuseItem>
 */
final class FuseCollection implements Countable, IteratorAggregate
{
    private readonly CarbonImmutable $today;

    /**
     * @param  list<FuseItem>  $items
     */
    public function __construct(
        private readonly array $items = [],
        private readonly int $warnWithinDays = 14,
        ?CarbonImmutable $today = null,
    ) {
        $this->today = ($today ?? CarbonImmutable::today())->startOfDay();
    }

    public function today(): CarbonImmutable
    {
        return $this->today;
    }

    public function warnWithinDays(): int
    {
        return $this->warnWithinDays;
    }

    public function status(FuseItem $item): FuseStatus
    {
        return $item->status($this->today, $this->warnWithinDays);
    }

    public function daysRemaining(FuseItem $item): int
    {
        return $item->daysRemaining($this->today);
    }

    /** Items that are neither expired nor close to expiring. */
    public function active(): self
    {
        return $this->withStatus(FuseStatus::Active);
    }

    /** Items inside the configured warning window that have not expired yet. */
    public function expiring(): self
    {
        return $this->withStatus(FuseStatus::Expiring);
    }

    public function expired(): self
    {
        return $this->withStatus(FuseStatus::Expired);
    }

    /** Items that have not expired but will within the given number of days. */
    public function expiringWithin(int $days): self
    {
        return $this->filter(function (FuseItem $item) use ($days): bool {
            $remaining = $this->daysRemaining($item);

            return $remaining > 0 && $remaining <= $days;
        });
    }

    public function critical(): self
    {
        return $this->filter(fn (FuseItem $item): bool => $item->severity === FuseSeverity::Critical);
    }

    public function severityAtLeast(FuseSeverity $severity): self
    {
        return $this->filter(fn (FuseItem $item): bool => $item->severity->isAtLeast($severity));
    }

    public function ownedBy(string $owner): self
    {
        return $this->filter(fn (FuseItem $item): bool => $item->owner === $owner);
    }

    public function ofType(FuseType $type): self
    {
        return $this->filter(fn (FuseItem $item): bool => $item->type === $type);
    }

    public function withStatus(FuseStatus $status): self
    {
        return $this->filter(fn (FuseItem $item): bool => $this->status($item) === $status);
    }

    /**
     * @param  callable(FuseItem): bool  $callback
     */
    public function filter(callable $callback): self
    {
        return $this->with(array_values(array_filter($this->items, $callback)));
    }

    /** Soonest expiry first, then by location, so output is stable. */
    public function sorted(): self
    {
        $items = $this->items;

        usort($items, fn (FuseItem $a, FuseItem $b): int => [$a->expiresAt->getTimestamp(), $a->file, $a->line]
            <=> [$b->expiresAt->getTimestamp(), $b->file, $b->line]);

        return $this->with($items);
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return list<FuseItem>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * @return ArrayIterator<int, FuseItem>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(
            fn (FuseItem $item): array => $item->toArray($this->today, $this->warnWithinDays),
            $this->items,
        );
    }

    /**
     * @param  list<FuseItem>  $items
     */
    private function with(array $items): self
    {
        return new self($items, $this->warnWithinDays, $this->today);
    }
}
