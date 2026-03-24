<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Ingredient;
use Illuminate\Auth\Access\Response;

class IngredientPolicy
{
    /**
     * Voir tous les ingrédients
     */
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    /**
     * Voir un ingrédient
     */
    public function view(User $user, Ingredient $ingredient): Response
    {
        return Response::allow();
    }

    /**
     * Créer un ingrédient
     */
    public function create(User $user): Response
    {
        return $user->role === 'admin'
            ? Response::allow()
            : Response::deny('You are not admin to create ingredient!');
    }

    /**
     * Mettre à jour un ingrédient
     */
    public function update(User $user, Ingredient $ingredient): Response
    {
        return $user->role === 'admin'
            ? Response::allow()
            : Response::deny('You are not admin to update this ingredient!');
    }

    /**
     * Supprimer un ingrédient
     */
    public function delete(User $user, Ingredient $ingredient): Response
    {
        return $user->role === 'admin'
            ? Response::allow()
            : Response::deny('You are not admin to delete this ingredient!');
    }
}