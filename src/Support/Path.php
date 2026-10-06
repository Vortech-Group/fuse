<?php

declare(strict_types=1);

namespace Vortech\Fuse\Support;

final class Path
{
    /**
     * Normalise separators so Windows and Unix paths compare and print the same way.
     */
    public static function normalize(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        return $path === '/' ? $path : rtrim($path, '/');
    }

    /**
     * Whether $path equals $directory or is located inside of it.
     */
    public static function isWithin(string $path, string $directory): bool
    {
        $path = self::normalize($path);
        $directory = self::normalize($directory);

        return $path === $directory || str_starts_with($path, $directory.'/');
    }

    /**
     * Path relative to $base when located inside of it, otherwise the normalised absolute path.
     */
    public static function relative(string $path, string $base): string
    {
        $path = self::normalize($path);
        $base = self::normalize($base);

        if ($base !== '' && str_starts_with($path, $base.'/')) {
            return substr($path, strlen($base) + 1);
        }

        return $path;
    }
}
