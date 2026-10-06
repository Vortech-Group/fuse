<?php

declare(strict_types=1);

use Vortech\Fuse\Contracts\FuseScanner;
use Vortech\Fuse\Data\FuseItem;
use Vortech\Fuse\Enums\FuseProblemType;
use Vortech\Fuse\Enums\FuseSeverity;
use Vortech\Fuse\Enums\FuseType;

function scanSource(array $files): array
{
    $root = test()->sourceTree($files);
    test()->scanning([$root], $root);

    $result = app(FuseScanner::class)->scan();

    return [$result->items->all(), $result->problems, $result];
}

it('reads every metadata field of a class attribute', function () {
    $this->scanning([__DIR__.'/../../Fixtures/ActiveFuse.php'], dirname(__DIR__, 3));

    $item = app(FuseScanner::class)->scan()->items->all()[0];

    expect($item)->toBeInstanceOf(FuseItem::class)
        ->and($item->reason)->toBe('Legacy API adapter')
        ->and($item->expiresAt->format('Y-m-d'))->toBe('2099-12-01')
        ->and($item->class)->toBe('Vortech\Fuse\Tests\Fixtures\ActiveFuse')
        ->and($item->method)->toBeNull()
        ->and($item->property)->toBeNull()
        ->and($item->owner)->toBe('backend')
        ->and($item->issue)->toBe('APP-1')
        ->and($item->severity)->toBe(FuseSeverity::Low)
        ->and($item->type)->toBe(FuseType::Migration)
        ->and($item->replacement)->toBe('ApiAdapterV2')
        ->and($item->createdAt?->format('Y-m-d'))->toBe('2026-10-05')
        ->and($item->file)->toBe('tests/Fixtures/ActiveFuse.php')
        ->and($item->line)->toBe(11);
});

it('applies defaults for optional metadata', function () {
    [$items] = scanSource(['A.php' => <<<'PHP'
        <?php
        use Vortech\Fuse\Attributes\Fuse;

        #[Fuse('Positional reason', '2099-01-01')]
        class A {}
        PHP]);

    expect($items[0]->reason)->toBe('Positional reason')
        ->and($items[0]->severity)->toBe(FuseSeverity::Medium)
        ->and($items[0]->type)->toBe(FuseType::TechnicalDebt)
        ->and($items[0]->owner)->toBeNull()
        ->and($items[0]->createdAt)->toBeNull();
});

it('finds attributes on classes, methods, properties, promoted properties and functions', function () {
    $this->scanning([__DIR__.'/../../Fixtures/MultipleFuses.php'], dirname(__DIR__, 3));

    $items = app(FuseScanner::class)->scan()->items->all();
    $byReason = array_column(array_map(fn (FuseItem $i) => [$i->reason, $i], $items), 1, 0);

    expect($items)->toHaveCount(6)
        ->and($byReason['Class level']->class)->toBe('Vortech\Fuse\Tests\Fixtures\MultipleFuses')
        ->and($byReason['Second reason on the same class']->class)->toBe('Vortech\Fuse\Tests\Fixtures\MultipleFuses')
        ->and($byReason['Property level']->property)->toBe('legacyValue')
        ->and($byReason['Promoted property']->property)->toBe('promoted')
        ->and($byReason['Method level']->method)->toBe('legacyMethod')
        ->and($byReason['Function level']->class)->toBeNull()
        ->and($byReason['Function level']->method)->toBe('Vortech\Fuse\Tests\Fixtures\legacyHelper');
});

it('resolves aliased imports and fully qualified attribute names', function () {
    [$items, $problems] = scanSource(['A.php' => <<<'PHP'
        <?php
        namespace App;

        use Vortech\Fuse\Attributes\Fuse as Temporary;
        use Vortech\Fuse\Enums as E;

        #[Temporary(reason: 'Aliased', expires: '2099-01-01', severity: E\FuseSeverity::High)]
        class Aliased {}

        #[\Vortech\Fuse\Attributes\Fuse(reason: 'Fully qualified', expires: '2099-01-01')]
        class Qualified {}
        PHP]);

    expect($problems)->toBe([])
        ->and(array_column(array_map(fn ($i) => [$i->reason, $i->severity], $items), 1, 0))
        ->toBe(['Aliased' => FuseSeverity::High, 'Fully qualified' => FuseSeverity::Medium]);
});

it('ignores attributes that merely share the short name', function () {
    [$items, $problems] = scanSource(['A.php' => <<<'PHP'
        <?php
        namespace App;

        use Some\Other\Fuse;

        #[Fuse(reason: 'Not ours', expires: '2099-01-01')]
        class A {}
        PHP]);

    expect($items)->toBe([])->and($problems)->toBe([]);
});

it('supports nested namespaces, anonymous classes, enums, traits and several properties per declaration', function () {
    [$items] = scanSource(['A.php' => <<<'PHP'
        <?php
        namespace App\Deep\Er;

        use Vortech\Fuse\Attributes\Fuse;

        #[Fuse(reason: 'Enum', expires: '2099-01-01')]
        enum Status { case On; }

        #[Fuse(reason: 'Trait', expires: '2099-01-01')]
        trait Helper {}

        class Host
        {
            #[Fuse(reason: 'Shared', expires: '2099-01-01')]
            public int $a = 1, $b = 2;

            public function make(): object
            {
                return new #[Fuse(reason: 'Anonymous', expires: '2099-01-01')] class {};
            }
        }
        PHP]);

    $map = array_column(array_map(fn ($i) => [$i->reason.($i->property ?? ''), $i->class], $items), 1, 0);

    expect($map)->toBe([
        'Enum' => 'App\Deep\Er\Status',
        'Trait' => 'App\Deep\Er\Helper',
        'Shareda' => 'App\Deep\Er\Host',
        'Sharedb' => 'App\Deep\Er\Host',
        'Anonymous' => 'class@anonymous',
    ]);
});

it('reports invalid attributes as problems instead of items', function () {
    $this->scanning([__DIR__.'/../../Fixtures/InvalidFuse.php'], dirname(__DIR__, 3));

    $result = app(FuseScanner::class)->scan();

    expect($result->items)->toHaveCount(0)
        ->and(array_map(fn ($p) => $p->message, $result->problems))->toBe([
            'Invalid expiration date "next Friday". Use the strict YYYY-MM-DD format.',
            'Invalid expiration date "2026-02-31". Use the strict YYYY-MM-DD format.',
            'Missing required Fuse argument "reason".',
            'Unsupported FuseSeverity case "Blocker".',
        ])
        ->and($result->problems[0]->type)->toBe(FuseProblemType::InvalidAttribute)
        ->and($result->problems[0]->line)->toBe(12);
});

it('rejects values that are not literals', function (string $argument, string $message) {
    [, $problems] = scanSource(['A.php' => <<<PHP
        <?php
        use Vortech\Fuse\Attributes\Fuse;
        use Vortech\Fuse\Enums\FuseSeverity;

        #[Fuse(reason: 'x', expires: '2099-01-01', $argument)]
        class A {}
        PHP]);

    expect($problems)->toHaveCount(1)->and($problems[0]->message)->toBe($message);
})->with([
    'variable owner' => ['owner: $owner', 'Fuse argument "owner" must be a string literal.'],
    'string severity' => ["severity: 'high'", 'Fuse argument "severity" must be a FuseSeverity enum case.'],
    'unknown argument' => ["color: 'red'", 'Unknown Fuse argument "color".'],
    'empty owner' => ["owner: ''", 'Fuse argument "owner" must not be empty.'],
    'bad created date' => ["created: 'yesterday'", 'Invalid creation date "yesterday". Use the strict YYYY-MM-DD format.'],
]);

it('accepts concatenated string literals and explicit nulls', function () {
    [$items, $problems] = scanSource(['A.php' => <<<'PHP'
        <?php
        use Vortech\Fuse\Attributes\Fuse;

        #[Fuse(reason: 'Remove after ' . 'the migration', expires: '2099-01-01', owner: null)]
        class A {}
        PHP]);

    expect($problems)->toBe([])->and($items[0]->reason)->toBe('Remove after the migration')->and($items[0]->owner)->toBeNull();
});

it('reports parse errors and keeps scanning other files', function () {
    [$items, $problems] = scanSource([
        'Broken.php' => "<?php\n// fuse\nclass { ",
        'Good.php' => "<?php\nuse Vortech\\Fuse\\Attributes\\Fuse;\n#[Fuse(reason: 'ok', expires: '2099-01-01')]\nclass Good {}",
    ]);

    expect($items)->toHaveCount(1)
        ->and($problems)->toHaveCount(1)
        ->and($problems[0]->type)->toBe(FuseProblemType::ParseError)
        ->and($problems[0]->file)->toBe('Broken.php');
});

it('handles empty directories and files without attributes', function () {
    [$items, $problems, $result] = scanSource(['Plain.php' => '<?php class Plain {}', 'notes.txt' => 'fuse']);

    expect($items)->toBe([])->and($problems)->toBe([])->and($result->filesScanned)->toBe(1);

    $empty = $this->sourceTree([]);
    $this->scanning([$empty], $empty);

    expect(app(FuseScanner::class)->scan()->filesScanned)->toBe(0);
});

it('skips ignored paths and missing scan paths', function () {
    $root = $this->sourceTree([
        'src/A.php' => "<?php\nuse Vortech\\Fuse\\Attributes\\Fuse;\n#[Fuse(reason: 'a', expires: '2099-01-01')]\nclass A {}",
        'vendor/B.php' => "<?php\nuse Vortech\\Fuse\\Attributes\\Fuse;\n#[Fuse(reason: 'b', expires: '2099-01-01')]\nclass B {}",
    ]);

    config(['fuse.paths' => [$root, $root.'/does-not-exist'], 'fuse.ignore' => [$root.'/vendor']]);
    $this->app->setBasePath($root);

    expect(array_map(fn ($i) => $i->file, app(FuseScanner::class)->scan()->items->all()))->toBe(['src/A.php']);
});

it('scans a single file path', function () {
    $root = $this->sourceTree(['A.php' => "<?php\nuse Vortech\\Fuse\\Attributes\\Fuse;\n#[Fuse(reason: 'a', expires: '2099-01-01')]\nclass A {}"]);
    $this->scanning([$root.'/A.php'], $root);

    expect(app(FuseScanner::class)->scan()->items)->toHaveCount(1);
});

it('sorts items by expiry then location', function () {
    [$items] = scanSource(['A.php' => <<<'PHP'
        <?php
        use Vortech\Fuse\Attributes\Fuse;

        #[Fuse(reason: 'later', expires: '2099-06-01')]
        class A {}

        #[Fuse(reason: 'sooner', expires: '2099-01-01')]
        class B {}
        PHP]);

    expect(array_map(fn ($i) => $i->reason, $items))->toBe(['sooner', 'later']);
});

it('uses the scan cache and invalidates it when a file changes', function () {
    $root = $this->sourceTree(['A.php' => "<?php\nuse Vortech\\Fuse\\Attributes\\Fuse;\n#[Fuse(reason: 'first', expires: '2099-01-01')]\nclass A {}"]);
    $this->scanning([$root], $root);

    expect(app(FuseScanner::class)->scan()->items->all()[0]->reason)->toBe('first');

    // Same size and mtime: the cached entry is served even though the content differs.
    $mtime = filemtime($root.'/A.php');
    file_put_contents($root.'/A.php', "<?php\nuse Vortech\\Fuse\\Attributes\\Fuse;\n#[Fuse(reason: 'other', expires: '2099-01-01')]\nclass A {}");
    touch($root.'/A.php', $mtime);

    expect(app(FuseScanner::class)->scan()->items->all()[0]->reason)->toBe('first');

    // A changed mtime invalidates it.
    touch($root.'/A.php', $mtime + 10);

    expect(app(FuseScanner::class)->scan()->items->all()[0]->reason)->toBe('other');
});

it('does not cache when caching is disabled', function () {
    config(['fuse.cache.enabled' => false]);
    $root = $this->sourceTree(['A.php' => "<?php\nuse Vortech\\Fuse\\Attributes\\Fuse;\n#[Fuse(reason: 'first', expires: '2099-01-01')]\nclass A {}"]);
    $this->scanning([$root], $root);
    $mtime = filemtime($root.'/A.php');

    app(FuseScanner::class)->scan();
    file_put_contents($root.'/A.php', "<?php\nuse Vortech\\Fuse\\Attributes\\Fuse;\n#[Fuse(reason: 'other', expires: '2099-01-01')]\nclass A {}");
    touch($root.'/A.php', $mtime);

    expect(app(FuseScanner::class)->scan()->items->all()[0]->reason)->toBe('other');
});

it('round trips cached entries through a serializing cache store', function () {
    $cachePath = $this->sourceTree([]);
    config(['cache.default' => 'file', 'cache.stores.file' => ['driver' => 'file', 'path' => $cachePath]]);

    $root = $this->sourceTree(['A.php' => "<?php\nuse Vortech\\Fuse\\Attributes\\Fuse;\n#[Fuse(reason: 'cached', expires: '2099-01-01', owner: 'me')]\nclass A {}\n#[Fuse(reason: 'bad', expires: 'never')]\nclass B {}"]);
    $this->scanning([$root], $root);

    $first = app(FuseScanner::class)->scan();
    $this->scanning([$root], $root);
    $second = app(FuseScanner::class)->scan();

    expect(glob($cachePath.'/*/*/*'))->not->toBeEmpty()
        ->and($second->items->all())->toEqual($first->items->all())
        ->and($second->problems)->toEqual($first->problems)
        ->and($second->items->all()[0]->owner)->toBe('me');
});
