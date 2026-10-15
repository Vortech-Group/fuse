# Changelog

All notable changes to `laravel-fuse` will be documented in this file

## 1.2.0 - 2026-10-15

### Added

- Clickable `file:line` locations in the `fuse:check` console output, with an `fuse.editor` / `FUSE_EDITOR` setting (PhpStorm by default) (`phpstorm`, `idea`, `vscode`, `cursor`, `zed`, `sublime`, `textmate`) to open files at the right line

### Changed

- Redesigned the `fuse:check` console output with severity badges, a summary line and a final PASS/FAIL verdict

## 1.1.0 - 2026-10-15

### Added

- Type-specific attributes `#[TechnicalDebt]`, `#[Workaround]`, `#[Temporary]`, `#[Deprecated]`, `#[Migration]` and `#[Fallback]` as shortcuts for `#[Fuse(type: ...)]`
- Laravel 12 support and PHP 8.3 support

### Changed

- Minimum versions are now PHP 8.3 and Laravel 12 (Laravel 13 remains supported)
- The scan cache version is bumped so existing caches pick up the new attributes
- Stricter PHPUnit settings, Pint rules and CI timeouts; added `composer lint` and `composer ci`

## 1.0.0 - 2026-10-06

### Added

- Initial release
- `#[Fuse]` attribute for classes, methods, properties (including promoted ones) and functions, with `reason`, `expires`, `owner`, `issue`, `severity`, `type`, `replacement` and `created` metadata
- Static scanner built on `nikic/php-parser`: application classes are never loaded or instantiated
- `fuse:install` Artisan command that publishes the config file
- `fuse:list` with `--expired`, `--expiring`, `--owner`, `--severity`, `--type` and `--json` filters
- `fuse:check` for CI with `--fail-within`, `--severity` and `--format=console|json|github`, and exit codes `0` (ok), `1` (expired), `2` (invalid metadata) and `3` (scanner failure)
- `fuse:doctor` to validate the setup and the metadata of all Fuse items
- `fuse:stats` with a status, severity, type and owner breakdown
- `FuseManager` and `Fuse` facade for programmatic access
- Per-file scan cache keyed on modification time and size, bypassed with `--no-cache`
- `fuse.require_owner` and `fuse.require_issue` config options
- Laravel 13 support (PHP 8.4+)
