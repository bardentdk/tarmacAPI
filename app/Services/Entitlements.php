<?php

namespace App\Services;

use App\Enums\Plan;
use App\Models\User;

/**
 * Computes what a user is allowed to do, from their plan and trial.
 */
class Entitlements
{
    /**
     * The plan currently in effect (paid plan, else trial plan, else free).
     */
    public function effectivePlan(User $user): Plan
    {
        if ($this->hasActivePaidPlan($user)) {
            return $user->plan;
        }

        if ($this->isOnTrial($user)) {
            return Plan::from((string) config('tarmac.trial_plan'));
        }

        return Plan::Free;
    }

    /**
     * Whether the user pays for a plan that has not expired.
     */
    public function hasActivePaidPlan(User $user): bool
    {
        return $user->plan !== Plan::Free
            && ($user->plan_expires_at === null || $user->plan_expires_at->isFuture());
    }

    /**
     * Whether the effective plan comes from the trial.
     */
    public function isOnTrial(User $user): bool
    {
        return ! $this->hasActivePaidPlan($user)
            && $user->trial_ends_at !== null
            && $user->trial_ends_at->isFuture();
    }

    /**
     * Whether the user may use the given boolean feature.
     */
    public function allows(User $user, string $feature): bool
    {
        return (bool) ($this->features($user)[$feature] ?? false);
    }

    /**
     * @return array{all_modes: bool, power: bool, full_reports: bool, unlimited_history: bool, ai: bool, csv_logs: bool, compare: bool, max_vehicles: int|null, runs_per_month: int|null}
     */
    public function features(User $user): array
    {
        return $this->effectivePlan($user)->features();
    }

    /**
     * The entitlements payload shared by the API and the web client.
     *
     * @return array{plan: string, is_trial: bool, trial_ends_at: string|null, plan_expires_at: string|null, features: array{all_modes: bool, power: bool, full_reports: bool, unlimited_history: bool, ai: bool, csv_logs: bool, compare: bool, max_vehicles: int|null, runs_per_month: int|null}}
     */
    public function toArray(User $user): array
    {
        return [
            'plan' => $this->effectivePlan($user)->value,
            'is_trial' => $this->isOnTrial($user),
            'trial_ends_at' => $user->trial_ends_at?->utc()->toIso8601ZuluString(),
            'plan_expires_at' => $user->plan_expires_at?->utc()->toIso8601ZuluString(),
            'features' => $this->features($user),
        ];
    }
}
