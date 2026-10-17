<?php

namespace App\Enums;

/**
 * How urgent a trouble code is.
 */
enum DtcUrgency: string
{
    case Ok = 'ok';
    case Caution = 'caution';
    case Stop = 'stop';

    /**
     * Human readable French label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Ok => 'Tu peux rouler',
            self::Caution => 'Roule prudemment',
            self::Stop => 'Arrête-toi',
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
