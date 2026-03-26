<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\PlateResource;
use App\Models\Categorie;
use Illuminate\Http\Request;
use App\Jobs\GenerateRecommendationJob;
use App\Models\Plat;
use App\Models\Recommendation;

class CategorieController extends Controller
{

    public function store(Request $request)
    {
        $this->authorize('create', Categorie::class);

        $data = $request->validate([
            'name' => 'required|unique:categories,name|max:100',
            'description' => 'nullable|string',
        ]);

        $categorie = Categorie::create([
            ...$data,
            'is_active' => false,
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'message' => 'Category created successfully',
            'data' => new CategoryResource($categorie)
        ], 201);
    }

    public function index(Request $request)
    {
        $query = Categorie::query();

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $categories = $query
            ->select('id', 'name', 'description', 'is_active')
            ->latest()
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'message' => 'All categories retrieved successfully',
            'data' => CategoryResource::collection($categories),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ]
        ]);
    }

    public function show($id)
    {
        $user = auth()->user();

        $categorie = Categorie::findOrFail($id);

        $plats = $categorie->plats()
            ->where('is_available', true)
            ->with(['recommendation' => function ($q) use ($user) {
                $q->where('user_id', $user->id);
            }])
            ->get();

        return response()->json([
            'message' => "All plats for category: {$categorie->name}",
            'data' => PlateResource::collection($plats)
        ]);
    }

    public function update(Request $request, $id)
    {
        $categorie = Categorie::findOrFail($id);

        $this->authorize('update', $categorie);

        $data = $request->validate([
            'name' => 'sometimes|string|max:100|unique:categories,name,' . $id,
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean'
        ]);

        $categorie->update($data);

        return response()->json([
            'message' => 'Category updated successfully',
            'data' => new CategoryResource($categorie)
        ]);
    }

    public function destroy($id)
    {
        $categorie = Categorie::findOrFail($id);

        $this->authorize('delete', $categorie);

        $hasActivePlats = $categorie->plats()
            ->where('is_available', true)
            ->exists();

        if ($hasActivePlats) {
            return response()->json([
                'message' => 'Cannot delete category with active plats'
            ], 409);
        }

        $name = $categorie->name;

        $categorie->delete();

        return response()->json([
            'message' => "Category '{$name}' deleted successfully"
        ]);
    }
}
