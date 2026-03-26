<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class AdminPolicy
{
    public function viewStats(User $user): Response
    {
        return $user->role === 'admin'
            ? Response::allow()
            : Response::deny('You are not authorized to access admin statistiques.');
    }
}