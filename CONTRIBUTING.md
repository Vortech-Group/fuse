# Contributing

Contributions are welcome through pull requests.

## Setup

```bash
composer install
```

## Before you open a pull request

```bash
composer ci
```

This runs Laravel Pint (`composer lint`), PHPStan at level `max` (`composer analyse`) and the Pest suite (`composer test`). Use `composer format` to fix code style automatically.

- Add or update tests for every behaviour change.
- Keep `declare(strict_types=1)`, `final` classes and `readonly` data objects.
- Fuse scans source code statically: never load, instantiate or reflect on application classes.
- Add a line to `CHANGELOG.md` for user-facing changes.
- Support the PHP and Laravel versions listed in `composer.json` and the test matrix.

## Security

Please report vulnerabilities by email to [mate@vortech.hu](mailto:mate@vortech.hu) instead of the issue tracker.
