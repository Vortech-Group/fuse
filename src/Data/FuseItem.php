<?php

declare(strict_types=1);

namespace Vortech\Fuse\Data;

use Carbon\CarbonImmutable;
use Vortech\Fuse\Enums\FuseSeverity;
use Vortech\Fuse\Enums\FuseStatus;
use Vortech\Fuse\Enums\FuseType;
use Vortech\Fuse\Support\Expiration;

final readonly class FuseItem
{
    public function __construct(
        public string $reason,
        public CarbonImmutable $expiresAt,
        public string $file,
        public int $line,
        public ?string $class,
        public ?string $method,
        public ?string $property,
        public ?string $owner,
        public ?string $issue,
        public FuseSeverity $severity,
        public FuseType $type,
        public ?string $replacement,
        public ?CarbonImmutable $createdAt,
    ) {}

    public function daysRemaining(CarbonImmutable $today): int
    {
        return Expiration::daysUntil($this->expiresAt, $today);
    }

    public function status(CarbonImmutable $today, int $warnWithinDays): FuseStatus
    {
        $remaining = $this->daysRemaining($today);

        return match (true) {
            $remaining <= 0 => FuseStatus::Expired,
            $remaining <= $warnWithinDays => FuseStatus::Expiring,
            default => FuseStatus::Active,
        };
    }

    public function location(): string
    {
        return $this->file.':'.$this->line;
    }

    /**
     * Human-readable name of the target the attribute is attached to.
     */
    public function target(): string
    {
        return match (true) {
            $this->class !== null && $this->method !== null => $this->class.'::'.$this->method.'()',
            $this->class !== null && $this->property !== null => $this->class.'::$'.$this->property,
            $this->class !== null => $this->class,
            $this->method !== null => $this->method.'()',
            default => $this->file,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(?CarbonImmutable $today = null, int $warnWithinDays = 14): array
    {
        $data = [
            'reason' => $this->reason,
            'expires' => $this->expiresAt->format('Y-m-d'),
            'file' => $this->file,
            'line' => $this->line,
            'class' => $this->class,
            'method' => $this->method,
            'property' => $this->property,
            'owner' => $this->owner,
            'issue' => $this->issue,
            'severity' => $this->severity->value,
            'type' => $this->type->value,
            'replacement' => $this->replacement,
            'created' => $this->createdAt?->format('Y-m-d'),
        ];

        if ($today !== null) {
            $data['status'] = $this->status($today, $warnWithinDays)->value;
            $data['days_remaining'] = $this->daysRemaining($today);
        }

        return $data;
    }
}
