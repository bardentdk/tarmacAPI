<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicle;

class VehiclePolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Vehicle $vehicle): bool
    {
        return $vehicle->user_id === $user->id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Vehicle $vehicle): bool
    {
        return $vehicle->user_id === $user->id;
    }
}
