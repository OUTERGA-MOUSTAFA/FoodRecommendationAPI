<?php

namespace App\Policies;

use App\Models\Categorie;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CategoriePolicy
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
    public function view(User $user, Categorie $categorie): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user):Response
    {
        return $user->role === 'admin'? Response::allow()
        : Response::deny('you are not Admin of restaurant to create category!');
        
    }

    public function update(User $user)
    {
        return $user->role === 'admin'? Response::allow()
        : Response::deny('you are not Admin of restaurant to update category!');
    }

    public function delete(User $user)
    {
        return $user->role === 'admin'? Response::allow()
        : Response::deny('you are not Admin of restaurant to delete category!');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Categorie $categorie): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Categorie $categorie): bool
    {
        return false;
    }
}
