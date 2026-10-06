<?php

declare(strict_types=1);

namespace Vortech\Fuse\Attributes;

use Attribute;
use Vortech\Fuse\Enums\FuseSeverity;
use Vortech\Fuse\Enums\FuseType;

#[Attribute(
    Attribute::TARGET_CLASS
    | Attribute::TARGET_METHOD
    | Attribute::TARGET_FUNCTION
    | Attribute::TARGET_PROPERTY
    | Attribute::IS_REPEATABLE
)]
final readonly class Fuse
{
    public function __construct(
        public string $reason,
        public string $expires,
        public ?string $owner = null,
        public ?string $issue = null,
        public FuseSeverity $severity = FuseSeverity::Medium,
        public FuseType $type = FuseType::TechnicalDebt,
        public ?string $replacement = null,
        public ?string $created = null,
    ) {}
}
