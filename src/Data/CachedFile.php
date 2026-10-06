<?php

declare(strict_types=1);

namespace Vortech\Fuse\Data;

/**
 * What the scan cache remembers about one source file.
 */
final readonly class CachedFile
{
    /**
     * @param  list<FuseItem>  $items
     * @param  list<FuseProblem>  $problems
     */
    public function __construct(
        public int $mtime,
        public int $size,
        public string $display,
        public array $items,
        public array $problems,
    ) {}
}
