<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Categorie;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Http\Request;

class CategorieController extends Controller
{

    public function store(Request $request)
    {
        $this->authorize('create', Categorie::class);

        $request->validate([
            'name' => 'required|unique:categories,name|max:100',
            'description' => 'nullable|string',
        ]);

        $categorie = Categorie::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => false,
            'user_id' => auth()->id()
        ]);

        return response()->json($categorie, 201);
    }

    public function index(Request $request)
    {
        $query = Categorie::query();

        // 🔍 Filtrage par statut actif/inactif
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $perPage = $request->get('per_page', 10);

        $categories = $query->with('user')->paginate($perPage);

        return CategoryResource::collection($categories);
    }

    public function show($id)
    {
        // $categorie = Categorie::findOrFail($id); //Model NotFoundException
        $categorie = Categorie::find($id);
        if (!$categorie) {
            return response()->json(['message' => 'Category not found'], 404);
        }
        return response()->json([
            'categories' => $categorie,
        ], 200);
        return;
    }

    public function update(Request $request, $id)
    {
        $this->authorize('update', Categorie::class);
        // if (auth()->user()->role !== 'admin') {
        //     return response()->json([
        //         'message' => 'Forbidden: Only admins can Update categories'
        //     ], 403);
        // }
        $categorie = Categorie::findOrFail($id);
        $request->validate([
            'name' => 'sometimes|string|max:100|unique:categories,name,' . $id,
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean'
        ]);

        $categorie->update($request->only(['name', 'description', 'is_active']));

        return response()->json([
            'message' => 'updated',
            'categorie' => $categorie
        ], 200);
    }

    public function destroy($id)
    {
        $categorie = Categorie::findOrFail($id);

        // admin
        $this->authorize('delete', $categorie);

        // Vérifier s'il existe des plats actifs liés
        $hasActivePlats = $categorie->plats()
            ->where('is_active', true)
            ->exists();

        if ($hasActivePlats) {
            return response()->json([
                'message' => 'Cannot delete category: it has active plats'
            ], 409);
        }

        // Soft delete
        $categorie->delete();

        return response()->json([
            'message' => 'Category deleted successfully'
        ], 200);
    }
}
