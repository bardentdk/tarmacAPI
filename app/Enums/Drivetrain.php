<?php

namespace App\Enums;

/**
 * Vehicle drivetrain.
 */
enum Drivetrain: string
{
    case Fwd = 'fwd';
    case Rwd = 'rwd';
    case Awd = 'awd';

    /**
     * Human readable French label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Fwd => 'Traction',
            self::Rwd => 'Propulsion',
            self::Awd => 'Intégrale',
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
