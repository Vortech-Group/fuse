<?php

declare(strict_types=1);

namespace Vortech\Fuse\Tests\Fixtures;

use Vortech\Fuse\Attributes\Fuse;
use Vortech\Fuse\Enums\FuseSeverity;

final class InvalidFuse
{
    #[Fuse(reason: 'Relative dates are not allowed', expires: 'next Friday')]
    public function relativeDate(): void {}

    #[Fuse(reason: 'Not a real calendar day', expires: '2026-02-31')]
    public function impossibleDate(): void {}

    #[Fuse(expires: '2099-01-01')]
    public function missingReason(): void {}

    #[Fuse(reason: 'Unsupported enum case', expires: '2099-01-01', severity: FuseSeverity::Blocker)]
    public function unsupportedSeverity(): void {}
}
