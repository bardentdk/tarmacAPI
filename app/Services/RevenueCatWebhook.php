<?php

namespace App\Services;

use App\Enums\Plan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

/**
 * Applies RevenueCat subscription events to user plans.
 *
 * The app configures RevenueCat with the Tarmac user id as `app_user_id`.
 */
class RevenueCatWebhook
{
    /**
     * Events granting (or extending) a plan.
     *
     * @var list<string>
     */
    private const GRANTING_EVENTS = ['INITIAL_PURCHASE', 'RENEWAL', 'PRODUCT_CHANGE', 'UNCANCELLATION', 'NON_RENEWING_PURCHASE'];

    /**
     * @param  array<string, mixed>  $event
     */
    public function handle(array $event): bool
    {
        $type = (string) ($event['type'] ?? '');

        if ($type === 'TEST') {
            return true;
        }

        $user = $this->findUser($event);

        if ($user === null) {
            Log::info('RevenueCat event for an unknown user', ['type' => $type]);

            return false;
        }

        $expiresAt = isset($event['expiration_at_ms']) && is_numeric($event['expiration_at_ms'])
            ? CarbonImmutable::createFromTimestampMs((int) $event['expiration_at_ms'])
            : null;

        if (in_array($type, self::GRANTING_EVENTS, true)) {
            $productId = (string) ($type === 'PRODUCT_CHANGE' && ! empty($event['new_product_id']) ? $event['new_product_id'] : ($event['product_id'] ?? ''));
            $product = config("tarmac.revenuecat.products.{$productId}");

            if (! is_array($product)) {
                Log::warning('RevenueCat event for an unmapped product', ['product_id' => $productId]);

                return false;
            }

            $user->forceFill([
                'plan' => Plan::from($product['plan']),
                'plan_period' => $product['period'],
                'plan_expires_at' => $expiresAt,
            ])->save();

            return true;
        }

        if ($type === 'CANCELLATION') {
            // Access is kept until the end of the paid period, then falls back to free.
            $user->forceFill(['plan_expires_at' => $expiresAt ?? now()])->save();

            return true;
        }

        if ($type === 'EXPIRATION') {
            $user->forceFill([
                'plan' => Plan::Free,
                'plan_period' => null,
                'plan_expires_at' => null,
            ])->save();

            return true;
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function findUser(array $event): ?User
    {
        $ids = array_values(array_unique(array_filter([
            $event['app_user_id'] ?? null,
            $event['original_app_user_id'] ?? null,
            ...((array) ($event['aliases'] ?? [])),
        ], fn (mixed $id): bool => is_string($id) && $id !== '')));

        if ($ids === []) {
            return null;
        }

        $user = User::query()->whereIn('revenuecat_app_user_id', $ids)->first();

        if ($user !== null) {
            return $user;
        }

        foreach ($ids as $id) {
            if (ctype_digit($id) && ($user = User::find((int) $id)) !== null) {
                $user->forceFill(['revenuecat_app_user_id' => $id])->save();

                return $user;
            }
        }

        return null;
    }
}
