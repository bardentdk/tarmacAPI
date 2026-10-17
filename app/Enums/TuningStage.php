<?php

namespace App\Enums;

/**
 * Vehicle tuning level.
 */
enum TuningStage: string
{
    case Stock = 'stock';
    case Stage1 = 'stage1';
    case Stage2 = 'stage2';
    case Stage3 = 'stage3';
    case Other = 'other';

    /**
     * Human readable French label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Stock => 'Origine',
            self::Stage1 => 'Stage 1',
            self::Stage2 => 'Stage 2',
            self::Stage3 => 'Stage 3',
            self::Other => 'Autre',
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
