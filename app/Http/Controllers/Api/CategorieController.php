<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

    public function index()
    {
        $categories = Categorie::get();
        return response()->json([
            'categories' => $categories,
        ], 200);
        return;
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
            'is_active' => 'boolean'
        ]);

        $categorie->update($request->only(['name', 'description', 'is_active']));

        return response()->json([
            'message' => 'updated',
            'categorie' => $categorie
        ], 200);
    }

    public function destroy($id)
    {
        $this->authorize('delete', Categorie::class);
        Categorie::destroy($id);

        return response()->json(['message' => 'deleted'], 200);
    }

    function CategoriePlats($id)
    {
        $categorie = Categorie::with('plats')->findOrFail($id);

        return response()->json([
            'message' => 'Here is your plats of category: ' . $categorie->name,
            'data' => $categorie->plats
        ], 200);
    }
}
