<?php

declare(strict_types=1);

namespace Vortech\Fuse\Support;

use Illuminate\Contracts\Config\Repository;
use Vortech\Fuse\Enums\FuseSeverity;
use Vortech\Fuse\Exceptions\InvalidFuseConfiguration;

/**
 * Typed, validated access to `config/fuse.php`.
 */
final readonly class FuseConfig
{
    public function __construct(private Repository $config) {}

    public function isLoaded(): bool
    {
        return is_array($this->config->get('fuse'));
    }

    /**
     * @return list<string>
     *
     * @throws InvalidFuseConfiguration
     */
    public function paths(): array
    {
        return $this->stringList('paths');
    }

    /**
     * @return list<string>
     *
     * @throws InvalidFuseConfiguration
     */
    public function ignore(): array
    {
        return $this->stringList('ignore');
    }

    /**
     * @throws InvalidFuseConfiguration
     */
    public function warnWithinDays(): int
    {
        return $this->days('warn_within_days', 14);
    }

    /**
     * @throws InvalidFuseConfiguration
     */
    public function failWithinDays(): int
    {
        return $this->days('fail_within_days', 0);
    }

    /**
     * @throws InvalidFuseConfiguration
     */
    public function failAtSeverity(): FuseSeverity
    {
        $value = $this->config->get('fuse.fail_at_severity', FuseSeverity::Low);

        if (! $value instanceof FuseSeverity && ! is_string($value)) {
            throw InvalidFuseConfiguration::invalidType('fail_at_severity', 'a FuseSeverity case or one of: low, medium, high, critical');
        }

        try {
            return FuseSeverity::fromInput($value);
        } catch (InvalidFuseConfiguration) {
            throw InvalidFuseConfiguration::invalidType('fail_at_severity', 'a FuseSeverity case or one of: low, medium, high, critical');
        }
    }

    /**
     * @throws InvalidFuseConfiguration
     */
    public function requireOwner(): bool
    {
        return $this->bool('require_owner', false);
    }

    /**
     * @throws InvalidFuseConfiguration
     */
    public function requireIssue(): bool
    {
        return $this->bool('require_issue', false);
    }

    /**
     * @throws InvalidFuseConfiguration
     */
    public function cacheEnabled(): bool
    {
        return $this->bool('cache.enabled', true);
    }

    /**
     * @throws InvalidFuseConfiguration
     */
    public function cacheStore(): ?string
    {
        $value = $this->config->get('fuse.cache.store');

        if ($value !== null && ! is_string($value)) {
            throw InvalidFuseConfiguration::invalidType('cache.store', 'a cache store name or null');
        }

        return $value;
    }

    /**
     * @throws InvalidFuseConfiguration
     */
    public function cacheTtl(): int
    {
        return $this->days('cache.ttl', 3600);
    }

    /**
     * Turn the scan cache off for this process, e.g. for `--no-cache`.
     */
    public function disableCache(): void
    {
        $this->config->set('fuse.cache.enabled', false);
    }

    /**
     * @return list<string>
     *
     * @throws InvalidFuseConfiguration
     */
    private function stringList(string $key): array
    {
        $value = $this->config->get('fuse.'.$key, []);

        if (! is_array($value)) {
            throw InvalidFuseConfiguration::invalidType($key, 'an array of paths');
        }

        $strings = [];

        foreach ($value as $entry) {
            if (! is_string($entry)) {
                throw InvalidFuseConfiguration::invalidType($key, 'an array of paths');
            }

            $strings[] = $entry;
        }

        return $strings;
    }

    /**
     * @throws InvalidFuseConfiguration
     */
    private function days(string $key, int $default): int
    {
        $value = $this->config->get('fuse.'.$key, $default);

        if (! is_int($value) || $value < 0) {
            throw InvalidFuseConfiguration::invalidType($key, 'a non-negative integer');
        }

        return $value;
    }

    /**
     * @throws InvalidFuseConfiguration
     */
    private function bool(string $key, bool $default): bool
    {
        $value = $this->config->get('fuse.'.$key, $default);

        if (! is_bool($value)) {
            throw InvalidFuseConfiguration::invalidType($key, 'a boolean');
        }

        return $value;
    }
}
