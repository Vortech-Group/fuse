<?php

declare(strict_types=1);

namespace Vortech\Fuse\Scanner;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use PhpParser\Error as ParseError;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Throwable;
use Vortech\Fuse\Contracts\FuseScanner;
use Vortech\Fuse\Data\CachedFile;
use Vortech\Fuse\Data\FuseItem;
use Vortech\Fuse\Data\FuseProblem;
use Vortech\Fuse\Data\ScanResult;
use Vortech\Fuse\Enums\FuseProblemType;
use Vortech\Fuse\Exceptions\FuseScanException;
use Vortech\Fuse\Support\FuseCollection;
use Vortech\Fuse\Support\Path;

final class PhpParserFuseScanner implements FuseScanner
{
    /** Bump whenever the shape of cached entries or the scanning rules change. */
    private const string CACHE_VERSION = '1';

    private ?Parser $parser = null;

    public function __construct(
        private readonly SourceFinder $finder,
        private readonly AttributeResolver $resolver,
        private readonly string $basePath,
        private readonly int $warnWithinDays = 14,
        private readonly ?CacheRepository $cache = null,
        private readonly int $cacheTtl = 3600,
    ) {}

    public function scan(): ScanResult
    {
        $items = [];
        $problems = [];

        try {
            $files = $this->finder->find();
        } catch (Throwable $e) {
            throw new FuseScanException('Unable to discover source files: '.$e->getMessage(), previous: $e);
        }

        foreach ($files as $file) {
            [$fileItems, $fileProblems] = $this->scanFile($file);

            array_push($items, ...$fileItems);
            array_push($problems, ...$fileProblems);
        }

        return new ScanResult(
            items: (new FuseCollection($items, $this->warnWithinDays))->sorted(),
            problems: $problems,
            filesScanned: count($files),
        );
    }

    /**
     * @return array{list<FuseItem>, list<FuseProblem>}
     */
    private function scanFile(string $file): array
    {
        $display = Path::relative($file, (string) (realpath($this->basePath) ?: $this->basePath));

        $mtime = @filemtime($file);
        $size = @filesize($file);
        $cacheKey = 'fuse:'.self::CACHE_VERSION.':'.sha1($file);

        if ($this->cache !== null && $mtime !== false && $size !== false) {
            $cached = $this->cache->get($cacheKey);

            if ($cached instanceof CachedFile && $cached->mtime === $mtime && $cached->size === $size && $cached->display === $display) {
                return [$cached->items, $cached->problems];
            }
        }

        [$items, $problems] = $this->parseFile($file, $display);

        if ($this->cache !== null && $mtime !== false && $size !== false) {
            $this->cache->put($cacheKey, new CachedFile($mtime, $size, $display, $items, $problems), $this->cacheTtl);
        }

        return [$items, $problems];
    }

    /**
     * @return array{list<FuseItem>, list<FuseProblem>}
     */
    private function parseFile(string $file, string $display): array
    {
        $code = @file_get_contents($file);

        if ($code === false) {
            return [[], [new FuseProblem(FuseProblemType::ParseError, 'Unable to read the file.', $display)]];
        }

        // Cheap pre-filter: a file that never mentions "fuse" cannot contain the attribute (not even aliased).
        if (stripos($code, 'fuse') === false) {
            return [[], []];
        }

        try {
            $ast = $this->parser()->parse($code) ?? [];
        } catch (ParseError $e) {
            return [[], [new FuseProblem(FuseProblemType::ParseError, $e->getRawMessage(), $display, max($e->getStartLine(), 0))]];
        }

        $visitor = new FuseNodeVisitor($this->resolver, $display);

        // Names must be fully resolved before the visitor runs: a single traversal would visit an attribute's
        // owner before the NameResolver reached the attribute's arguments.
        $ast = (new NodeTraverser(new NameResolver))->traverse($ast);
        (new NodeTraverser($visitor))->traverse($ast);

        return [$visitor->items(), $visitor->problems()];
    }

    private function parser(): Parser
    {
        return $this->parser ??= (new ParserFactory)->createForNewestSupportedVersion();
    }
}
