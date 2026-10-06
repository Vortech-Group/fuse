<?php

declare(strict_types=1);

namespace Vortech\Fuse\Data;

use Vortech\Fuse\Enums\FuseProblemType;

final readonly class FuseProblem
{
    public function __construct(
        public FuseProblemType $type,
        public string $message,
        public string $file,
        public int $line = 0,
    ) {}

    public function location(): string
    {
        return $this->line > 0 ? $this->file.':'.$this->line : $this->file;
    }

    /**
     * Exit code a command should use when this problem is present.
     */
    public function exitCode(): int
    {
        return match ($this->type) {
            FuseProblemType::InvalidAttribute => 2,
            FuseProblemType::ParseError => 3,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'message' => $this->message,
            'file' => $this->file,
            'line' => $this->line,
        ];
    }
}
