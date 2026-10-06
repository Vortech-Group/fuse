<?php

declare(strict_types=1);

namespace Vortech\Fuse\Tests\Fixtures;

use Vortech\Fuse\Attributes\Fuse;
use Vortech\Fuse\Enums\FuseSeverity;
use Vortech\Fuse\Enums\FuseType;

final class ExpiredFuse
{
    #[Fuse(
        reason: 'Fallback until the payment gateway fixes duplicate callbacks',
        expires: '2020-01-01',
        owner: 'payments',
        issue: 'PAY-7',
        severity: FuseSeverity::High,
        type: FuseType::Workaround,
    )]
    public function handleLegacyCallback(): void {}
}
