<?php

namespace App\Enums;

/**
 * Subscription plan.
 */
enum Plan: string
{
    case Free = 'free';
    case Pro = 'pro';
    case Tuner = 'tuner';
    case Garage = 'garage';

    /**
     * Human readable French label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Free => 'Gratuit',
            self::Pro => 'Pro',
            self::Tuner => 'Tuner',
            self::Garage => 'Garage',
        };
    }

    /**
     * Feature flags unlocked by the plan. `null` limits mean "unlimited".
     *
     * @return array{all_modes: bool, power: bool, full_reports: bool, unlimited_history: bool, ai: bool, csv_logs: bool, compare: bool, max_vehicles: int|null, runs_per_month: int|null}
     */
    public function features(): array
    {
        $isPaid = $this !== self::Free;
        $isTunerOrAbove = in_array($this, [self::Tuner, self::Garage], true);

        return [
            'all_modes' => $isPaid,
            'power' => $isPaid,
            'full_reports' => $isPaid,
            'unlimited_history' => $isPaid,
            'ai' => $isPaid,
            'csv_logs' => $isTunerOrAbove,
            'compare' => $isTunerOrAbove,
            'max_vehicles' => match ($this) {
                self::Free => 1,
                self::Pro => 3,
                self::Tuner => 5,
                self::Garage => null,
            },
            'runs_per_month' => $isPaid ? null : 3,
        ];
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
