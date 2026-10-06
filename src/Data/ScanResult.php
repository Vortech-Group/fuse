<?php

declare(strict_types=1);

namespace Vortech\Fuse\Data;

use Vortech\Fuse\Support\FuseCollection;

final readonly class ScanResult
{
    /**
     * @param  list<FuseProblem>  $problems
     */
    public function __construct(
        public FuseCollection $items,
        public array $problems = [],
        public int $filesScanned = 0,
    ) {}
}
