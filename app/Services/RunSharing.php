<?php

namespace App\Services;

use App\Models\Run;
use App\Models\SharedRun;
use Illuminate\Support\Str;

/**
 * Creates and revokes public read-only links to runs.
 */
class RunSharing
{
    /**
     * Return the active link of the run, creating one when needed.
     */
    public function share(Run $run, ?string $recipient = null): SharedRun
    {
        /** @var SharedRun|null $share */
        $share = $run->shares()->active()->latest('id')->first();

        if ($share === null) {
            /** @var SharedRun $share */
            $share = $run->shares()->create([
                'token' => Str::random(40),
                'recipient' => $recipient,
            ]);

            return $share;
        }

        if ($recipient !== null && $recipient !== $share->recipient) {
            $share->update(['recipient' => $recipient]);
        }

        return $share;
    }

    /**
     * Revoke every active link of the run.
     */
    public function revoke(Run $run): int
    {
        return $run->shares()->active()->update(['revoked_at' => now()]);
    }
}
