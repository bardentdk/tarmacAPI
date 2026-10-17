<?php

namespace App\Policies;

use App\Models\SharedRun;
use App\Models\User;

class SharedRunPolicy
{
    /**
     * Determine whether the user can revoke the share link.
     */
    public function delete(User $user, SharedRun $sharedRun): bool
    {
        return $sharedRun->run()->where('user_id', $user->id)->exists();
    }
}
