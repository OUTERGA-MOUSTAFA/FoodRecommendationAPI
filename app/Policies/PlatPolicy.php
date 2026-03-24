<?php

namespace App\Policies;

use App\Models\Plat;
use App\Models\User;

class PlatPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Plat $plat): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user)
    {
        return $user->role === 'admin';
    }

    public function update(User $user)
    {
        return $user->role === 'admin';
    }

    public function delete(User $user)
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Plat $plat): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Plat $plat): bool
    {
        return false;
    }
}
