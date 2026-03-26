<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StatsResource;
use App\Models\Categorie;
use App\Models\Plat;
use App\Models\Ingredient;
use App\Models\Recommendation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function stats()
    {
        // authorisation
        $this->authorize('viewStats', User::class);

        //  Totaux simples
        $totalPlates = Plat::count();
        $totalCategories = Categorie::count();
        $totalIngredients = Ingredient::count();
        $totalRecommendations = Recommendation::count();

        //  Plat le plus recommandé (moyenne score)
        $mostRecommended = Recommendation::select(
                'plat_id',
                DB::raw('AVG(score) as avg_score')
            )
            ->groupBy('plat_id')
            ->orderByDesc('avg_score')
            ->with('plat:id,title')
            ->first();

        //  Plat le moins recommandé
        $leastRecommended = Recommendation::select(
                'plat_id',
                DB::raw('AVG(score) as avg_score')
            )
            ->groupBy('plat_id')
            ->orderBy('avg_score')
            ->with('plat:id,title')
            ->first();

        //  Catégorie avec le plus de plats
        $topCategory = Categorie::withCount('plats')
            ->orderByDesc('plats_count')
            ->first();

        return new StatsResource([
            'total_plates' => $totalPlates,
            'total_categories' => $totalCategories,
            'total_ingredients' => $totalIngredients,
            'total_recommendations' => $totalRecommendations,

            'most_recommended_plate' => $mostRecommended,
            'least_recommended_plate' => $leastRecommended,

            'category_with_most_plates' => $topCategory,
        ]);
    }
}