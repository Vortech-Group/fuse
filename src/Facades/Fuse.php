<?php

declare(strict_types=1);

namespace Vortech\Fuse\Facades;

use Illuminate\Support\Facades\Facade;
use Vortech\Fuse\FuseManager;

/**
 * @method static \Vortech\Fuse\Data\ScanResult result()
 * @method static \Vortech\Fuse\Support\FuseCollection scan()
 * @method static \Vortech\Fuse\Support\FuseCollection expired()
 * @method static \Vortech\Fuse\Support\FuseCollection expiringWithin(int $days)
 * @method static \Vortech\Fuse\Data\CheckReport check(?int $failWithinDays = null, \Vortech\Fuse\Enums\FuseSeverity|string|null $severity = null)
 *
 * @see FuseManager
 */
final class Fuse extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FuseManager::class;
    }
}
