<?php

declare(strict_types=1);

namespace Vortech\Fuse\Attributes;

use Vortech\Fuse\Enums\FuseSeverity;

/**
 * Shared metadata of the type-specific attributes (#[Workaround], #[Temporary], ...).
 * Each concrete attribute maps to one FuseType, so it has no `type` argument.
 */
abstract readonly class FuseAttribute
{
    public function __construct(
        public string $reason,
        public string $expires,
        public ?string $owner = null,
        public ?string $issue = null,
        public FuseSeverity $severity = FuseSeverity::Medium,
        public ?string $replacement = null,
        public ?string $created = null,
    ) {}
}
