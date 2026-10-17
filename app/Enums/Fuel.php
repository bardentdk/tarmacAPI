<?php

namespace App\Enums;

/**
 * Vehicle fuel type.
 */
enum Fuel: string
{
    case Petrol = 'petrol';
    case Diesel = 'diesel';
    case Hybrid = 'hybrid';

    /**
     * Human readable French label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Petrol => 'Essence',
            self::Diesel => 'Diesel',
            self::Hybrid => 'Hybride',
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
