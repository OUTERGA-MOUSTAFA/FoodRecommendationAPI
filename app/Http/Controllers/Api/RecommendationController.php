<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecommendationResource;
use App\Models\Plat;
use App\Models\Recommendation;
use App\Jobs\GenerateRecommendationJob;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    // =========================
    //  POST → lancer analyse
    // =========================
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
                'warning_message' => 'Analyse en cours...',
            ]
        );

        GenerateRecommendationJob::dispatch($user->id, $plat->id);

        return response()->json([
            'message' => "Analysis started for plat: {$plat->title}",
            'status' => 'processing'
        ], 202);
    }

    // =========================
    //  GET → historique
    // =========================
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Recommendation::query()
            ->where('user_id', $user->id)
            ->with('plat:id,title')
            ->latest();

        //  filter by status (optionnel)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $recommendations = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'message' => 'Recommendations retrieved successfully',
            'data' => RecommendationResource::collection($recommendations),
            'meta' => [
                'current_page' => $recommendations->currentPage(),
                'last_page' => $recommendations->lastPage(),
                'per_page' => $recommendations->perPage(),
                'total' => $recommendations->total(),
            ]
        ]);
    }

    // =========================
    //  GET → résultat d’un plat
    // =========================
    public function show($platId)
    {
        $user = auth()->user();

        $recommendation = Recommendation::where([
            'user_id' => $user->id,
            'plat_id' => $platId
        ])
        ->with('plat:id,title')
        ->first();

        if (!$recommendation) {
            return response()->json([
                'success' => false,
                'message' => 'Recommendation not found'
            ], 404);
        }

        return response()->json([
            'message' => "Recommendation for plat: {$recommendation->plat->title}",
            'data' => new RecommendationResource($recommendation)
        ]);
    }
}