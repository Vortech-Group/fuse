<?php

declare(strict_types=1);

namespace Vortech\Fuse\Tests\Fixtures;

use Vortech\Fuse\Attributes\Fuse;
use Vortech\Fuse\Enums\FuseSeverity;
use Vortech\Fuse\Enums\FuseType;

#[Fuse(
    reason: 'Legacy API adapter',
    expires: '2099-12-01',
    owner: 'backend',
    issue: 'APP-1',
    severity: FuseSeverity::Low,
    type: FuseType::Migration,
    replacement: 'ApiAdapterV2',
    created: '2026-10-05',
)]
final class ActiveFuse {}
