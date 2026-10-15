<?php

declare(strict_types=1);

namespace Vortech\Fuse\Enums;

use Vortech\Fuse\Exceptions\InvalidFuseConfiguration;

/**
 * Editors that can open a file at a given line through a URL scheme, used for clickable terminal links.
 */
enum FuseEditor: string
{
    case PhpStorm = 'phpstorm';
    case Idea = 'idea';
    case VsCode = 'vscode';
    case Cursor = 'cursor';
    case Zed = 'zed';
    case Sublime = 'sublime';
    case TextMate = 'textmate';

    /**
     * URL that opens the absolute $path at $line. A line of 0 or less opens the file only.
     */
    public function url(string $path, int $line): string
    {
        $line = max($line, 1);
        $encoded = str_replace('%2F', '/', rawurlencode($path));

        return match ($this) {
            self::PhpStorm => sprintf('phpstorm://open?file=%s&line=%d', $encoded, $line),
            self::Idea => sprintf('idea://open?file=%s&line=%d', $encoded, $line),
            self::VsCode => sprintf('vscode://file%s:%d', $encoded, $line),
            self::Cursor => sprintf('cursor://file%s:%d', $encoded, $line),
            self::Zed => sprintf('zed://file%s:%d', $encoded, $line),
            self::Sublime => sprintf('subl://open?url=file://%s&line=%d', $encoded, $line),
            self::TextMate => sprintf('txmt://open?url=file://%s&line=%d', $encoded, $line),
        };
    }

    /**
     * @throws InvalidFuseConfiguration
     */
    public static function fromInput(self|string $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        return self::tryFrom(strtolower($value))
            ?? throw InvalidFuseConfiguration::invalidValue('editor', $value, array_column(self::cases(), 'value'));
    }
}
