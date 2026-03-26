<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecommendationResource;
use App\Models\Plat;
use App\Models\Recommendation;
use App\Jobs\GenerateRecommendationJob;


class RecommendationController extends Controller
{
    //  POST → lancer analyse
    public function analyze($platId)
    {
        $user = auth()->user();

        $plat = Plat::findOrFail($platId);

        Recommendation::updateOrCreate(
            [
                'user_id' => $user->id,
                'plat_id' => $plat->id
            ],
            [
                'status' => Recommendation::STATUS_PROCESSING,
                'score' => 0,
                'label' => null,
                'warning_message' => null,
            ]
        );

        // 🔥 dispatch async
        GenerateRecommendationJob::dispatch($user->id, $plat->id);

        return response()->json([
            'message' => 'Analysis started',
            'status' => 'processing'
        ], 202);
    }

    //  GET → historique
    public function index()
{
    $user = auth()->user();

    $query = Recommendation::query()
        ->where('user_id', $user->id)
        ->with(['plat:id,title']) // ✅ eager loading optimisé
        ->latest();

    // 🔥 pagination
    $recommendations = $query->paginate(10);

    return RecommendationResource::collection($recommendations);
}
    

    //  GET → résultat d’un plat
    public function show($plate_id)
    {
        $user = auth()->user();

        $recommendation = Recommendation::where([
            'user_id' => $user->id,
            'plat_id' => $plate_id
        ])->with('plat')->first();

        if (!$recommendation) {
            return response()->json([
                'message' => 'Recommendation not found'
            ], 404);
        }

        return response()->json([
            'data' => $recommendation
        ]);
    }
}
