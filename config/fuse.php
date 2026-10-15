<?php

declare(strict_types=1);

use Vortech\Fuse\Enums\FuseEditor;
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
    | Editor
    |--------------------------------------------------------------------------
    |
    | Makes the file locations in the console output clickable in terminals
    | that support hyperlinks (iTerm2, VS Code, Ghostty, WezTerm, Kitty, ...).
    | Use a FuseEditor case or its name (phpstorm, idea, vscode, cursor, zed,
    | sublime, textmate) to open the file at the right line in that editor.
    | Defaults to phpstorm. Set it to null for plain file:// links that open
    | the file in the default application.
    |
    */

    'editor' => env('FUSE_EDITOR', FuseEditor::PhpStorm->value),

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
