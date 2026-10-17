<?php

namespace App\Enums;

/**
 * OBD trouble code kind (mode 03 / 07 / 0A).
 */
enum DtcKind: string
{
    case Confirmed = 'confirmed';
    case Pending = 'pending';
    case Permanent = 'permanent';

    /**
     * Human readable French label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Confirmed => 'Confirmé',
            self::Pending => 'En attente',
            self::Permanent => 'Permanent',
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
