<?php

namespace App\Enums;

/**
 * Device that recorded a run.
 */
enum RunSource: string
{
    case Elm327 = 'elm327';
    case Stn = 'stn';
    case Tarmac = 'tarmac';
    case Custom = 'custom';
    case Demo = 'demo';

    /**
     * Human readable French label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Elm327 => 'ELM327',
            self::Stn => 'STN / OBDLink',
            self::Tarmac => 'Tarmac Box',
            self::Custom => 'Boîtier custom',
            self::Demo => 'Démo',
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
