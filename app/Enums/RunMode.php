<?php

namespace App\Enums;

/**
 * Drag / timing run mode.
 */
enum RunMode: string
{
    case ZeroHundred = 'zero_100';
    case ZeroTwoHundred = 'zero_200';
    case HundredTwoHundred = 'hundred_200';
    case SixtyHundredThirty = 'sixty_130';
    case EightyHundredTwenty = 'eighty_120';
    case EighthMile = 'eighth_mile';
    case QuarterMile = 'quarter_mile';
    case HalfMile = 'half_mile';
    case Kilometer = 'km';
    case Dyno = 'dyno';
    case BrakingHundredZero = 'braking_100_0';
    case Custom = 'custom';

    /**
     * Human readable French label.
     */
    public function label(): string
    {
        return match ($this) {
            self::ZeroHundred => '0-100 km/h',
            self::ZeroTwoHundred => '0-200 km/h',
            self::HundredTwoHundred => '100-200 km/h',
            self::SixtyHundredThirty => '60-130 km/h',
            self::EightyHundredTwenty => '80-120 km/h',
            self::EighthMile => '201 m',
            self::QuarterMile => '402 m',
            self::HalfMile => '804 m',
            self::Kilometer => '1000 m',
            self::Dyno => 'Banc (puissance)',
            self::BrakingHundredZero => 'Freinage 100-0',
            self::Custom => 'Personnalisé',
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
