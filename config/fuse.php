<?php

declare(strict_types=1);

use Vortech\Fuse\Enums\FuseSeverity;

return [

    /*
    |--------------------------------------------------------------------------
    | Scan paths
    |--------------------------------------------------------------------------
    |
    | Directories (or single files) that are scanned for #[Fuse] attributes.
    |
    */

    'paths' => [
        app_path(),
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignored paths
    |--------------------------------------------------------------------------
    */

    'ignore' => [
        storage_path(),
        base_path('vendor'),
        base_path('bootstrap/cache'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Expiring warning
    |--------------------------------------------------------------------------
    |
    | Items that expire within this many days are reported as "expiring".
    |
    */

    'warn_within_days' => 14,

    /*
    |--------------------------------------------------------------------------
    | CI failure window
    |--------------------------------------------------------------------------
    |
    | fuse:check also fails for items that expire within this many days.
    | 0 means only items that have already expired fail the check.
    |
    */

    'fail_within_days' => 0,

    /*
    |--------------------------------------------------------------------------
    | Minimum severity that can fail CI
    |--------------------------------------------------------------------------
    |
    | A FuseSeverity case or its string value: low, medium, high, critical.
    |
    */

    'fail_at_severity' => FuseSeverity::Low,

    /*
    |--------------------------------------------------------------------------
    | Require owner
    |--------------------------------------------------------------------------
    */

    'require_owner' => false,

    /*
    |--------------------------------------------------------------------------
    | Require issue
    |--------------------------------------------------------------------------
    */

    'require_issue' => false,

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Parsed metadata is cached per file and invalidated when the file's
    | modification time or size changes. Use `--no-cache` to bypass it.
    |
    */

    'cache' => [
        'enabled' => true,
        'store' => null,
        'ttl' => 3600,
    ],

];
