<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\ServiceProvider;
use App\Policies\ProfilePolicy;

class AuthServiceProvider extends ServiceProvider
{    
    // Define the policies array to map models to their policies
    protected $policies = [
    User::class => ProfilePolicy::class,
];
    /**
     * Register services.
     */
    public function register(): void
    {
    
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
