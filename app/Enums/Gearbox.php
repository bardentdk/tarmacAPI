<?php

namespace App\Enums;

/**
 * Vehicle gearbox type.
 */
enum Gearbox: string
{
    case Manual = 'manual';
    case Dct = 'dct';
    case Auto = 'auto';

    /**
     * Human readable French label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manuelle',
            self::Dct => 'Double embrayage',
            self::Auto => 'Automatique',
        };
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
