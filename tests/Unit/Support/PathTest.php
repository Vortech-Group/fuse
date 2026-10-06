<?php

declare(strict_types=1);

use Vortech\Fuse\Support\Path;

it('normalises Windows and Unix separators', function () {
    expect(Path::normalize('C:\\project\\app\\'))->toBe('C:/project/app')
        ->and(Path::normalize('/var/www/'))->toBe('/var/www')
        ->and(Path::normalize('/'))->toBe('/');
});

it('makes paths relative to a base path on any platform', function () {
    expect(Path::relative('/var/www/app/Foo.php', '/var/www'))->toBe('app/Foo.php')
        ->and(Path::relative('C:\\project\\app\\Foo.php', 'C:\\project'))->toBe('app/Foo.php')
        ->and(Path::relative('/elsewhere/Foo.php', '/var/www'))->toBe('/elsewhere/Foo.php')
        ->and(Path::relative('/var/www-other/Foo.php', '/var/www'))->toBe('/var/www-other/Foo.php');
});

it('detects whether a path is inside a directory', function () {
    expect(Path::isWithin('/var/www/vendor/a.php', '/var/www/vendor'))->toBeTrue()
        ->and(Path::isWithin('/var/www/vendor', '/var/www/vendor/'))->toBeTrue()
        ->and(Path::isWithin('/var/www/vendor-x/a.php', '/var/www/vendor'))->toBeFalse()
        ->and(Path::isWithin('C:\\p\\vendor\\a.php', 'C:/p/vendor'))->toBeTrue();
});
