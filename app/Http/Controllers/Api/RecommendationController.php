<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plat;
use App\Models\Recommendation;
use App\Jobs\GenerateRecommendationJob;

class RecommendationController extends Controller
{
    //  POST → lancer analyse
    public function analyze($plate_id)
    {
        $user = auth()->user();
        $plat = Plat::findOrFail($plate_id);

        // 🔍 éviter duplication
        $existing = Recommendation::where([
            'user_id' => $user->id,
            'plat_id' => $plat->id
        ])->first();

        //  créer / reset en processing
        Recommendation::updateOrCreate(
            [
                'user_id' => $user->id,
                'plat_id' => $plat->id
            ],
            [
                'score' => 0,
                'label' => null,
                'warning_message' => null,
                'status' => 'processing',
            ]
        );

        // dispatch job
        GenerateRecommendationJob::dispatch($plat->id, $user->id);

        return response()->json([
            'message' => 'Analysis started',
            'status' => 'processing'
        ], 202);
    }

    //  GET → historique
    public function index()
    {
        $user = auth()->user();

        $recommendations = Recommendation::where('user_id', $user->id)
            ->with('plat')
            ->latest()
            ->get();

        return response()->json([
            'data' => $recommendations
        ]);
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
