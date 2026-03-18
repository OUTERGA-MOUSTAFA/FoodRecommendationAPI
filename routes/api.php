<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategorieController;
use App\Http\Controllers\Api\PlatsController;
use App\Http\Controllers\Api\SocialAuthController;
use Illuminate\Support\Facades\Route;
// Auth Actions
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Google
Route::get('/google', [SocialAuthController::class, 'redirectToGoogle']);
Route::get('/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);

// Facebook
Route::get('/facebook', [SocialAuthController::class, 'redirectToFacebook']);
Route::get('/facebook/callback', [SocialAuthController::class, 'handleFacebookCallback']);

Route::middleware(['auth:sanctum'])->group(function () {

    //Logout
    Route::post('/logout', [AuthController::class, 'logout']);
    // get User
    Route::get('/user', [AuthController::class, 'me']);

    
    // Création du endpoint GET /api/categories
    // Route::apiResource('categories', CategorieController::class);
    Route::post('/categorie', [CategorieController::class, 'store']);

    
    // Création du endpoint POST /api/categories/{id}/plat
    Route::post('/categorie/{id}/plats', [CategorieController::class, 'CategoriePlats']);

    // Création du endpoint POST /api/categorie
    Route::apiResource('categories', CategorieController::class);

    // Création du endpoint GET /api/categorie/{id}
    // Création du endpoint PUT /api/categorie/{id}
    // Création du endpoint DELETE /api/categorie/{id}
    Route::apiResource('categorie', CategorieController::class);
    
    // GET /api/plat
    // GET /api/plat/{id}
    // POST /api/plat
    // PUT /api/plat/{id}
    // DELETE /api/plat/{id}
    Route::apiResource('plat', PlatsController::class);
});
