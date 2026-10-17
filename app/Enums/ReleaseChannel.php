<?php

namespace App\Enums;

/**
 * Release lane (the "arc" of the OTA plugin).
 */
enum ReleaseChannel: string
{
    case Production = 'production';
    case Staging = 'staging';

    /**
     * Resolve a channel from client input, falling back to production.
     */
    public static function fromInput(?string $value): self
    {
        return self::tryFrom(strtolower((string) $value)) ?? self::Production;
    }

    /**
     * All raw values.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
