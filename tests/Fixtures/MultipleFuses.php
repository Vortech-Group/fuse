<?php

declare(strict_types=1);

namespace Vortech\Fuse\Tests\Fixtures;

use Vortech\Fuse\Attributes\Fuse;

#[Fuse(reason: 'Class level', expires: '2099-01-01', owner: 'backend')]
#[Fuse(reason: 'Second reason on the same class', expires: '2099-02-01')]
final class MultipleFuses
{
    #[Fuse(reason: 'Property level', expires: '2099-03-01')]
    public string $legacyValue = '';

    public function __construct(
        #[Fuse(reason: 'Promoted property', expires: '2099-04-01')]
        public readonly string $promoted = '',
    ) {}

    #[Fuse(reason: 'Method level', expires: '2099-05-01')]
    public function legacyMethod(): void {}
}

#[Fuse(reason: 'Function level', expires: '2099-06-01')]
function legacyHelper(): void {}
