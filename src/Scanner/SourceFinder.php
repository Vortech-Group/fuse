<?php

declare(strict_types=1);

namespace Vortech\Fuse\Scanner;

use Symfony\Component\Finder\Finder;
use Vortech\Fuse\Support\Path;

final readonly class SourceFinder
{
    /** @var list<string> */
    private array $paths;

    /** @var list<string> */
    private array $ignore;

    /**
     * @param  list<string>  $paths  Directories (or single files) to scan
     * @param  list<string>  $ignore  Directories (or files) to skip
     */
    public function __construct(array $paths, array $ignore = [])
    {
        $this->paths = $paths;
        $this->ignore = array_map(self::canonical(...), $ignore);
    }

    /**
     * Absolute, normalised paths of every PHP file that should be scanned, in a stable order.
     *
     * @return list<string>
     */
    public function find(): array
    {
        $files = [];

        foreach ($this->existingPaths() as $path) {
            if (is_file($path)) {
                $files[] = self::canonical($path);

                continue;
            }

            foreach (Finder::create()->files()->in($path)->name('*.php')->ignoreDotFiles(true)->ignoreVCS(true) as $file) {
                $files[] = self::canonical($file->getPathname());
            }
        }

        $files = array_filter($files, fn (string $file): bool => ! $this->isIgnored($file));
        $files = array_values(array_unique($files));
        sort($files);

        return $files;
    }

    /**
     * Configured scan paths that do not exist on disk.
     *
     * @return list<string>
     */
    public function missingPaths(): array
    {
        return array_values(array_filter($this->paths, fn (string $path): bool => ! file_exists($path)));
    }

    /**
     * @return list<string>
     */
    public function ignoredPaths(): array
    {
        return $this->ignore;
    }

    /**
     * @return list<string>
     */
    private function existingPaths(): array
    {
        return array_values(array_filter($this->paths, file_exists(...)));
    }

    private function isIgnored(string $file): bool
    {
        foreach ($this->ignore as $ignored) {
            if (Path::isWithin($file, $ignored)) {
                return true;
            }
        }

        return false;
    }

    private static function canonical(string $path): string
    {
        $real = realpath($path);

        return Path::normalize($real === false ? $path : $real);
    }
}
