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
        // if (auth()->user()->role !== 'admin') {
        //     return response()->json([
        //         'message' => 'Forbidden: Only admins can create categories'
        //     ], 403);
        // }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $categorie = Categorie::create([
            'name' => $request->name,
            'description' => $request->description,
            'user_id' => auth()->id()
        ]);

        return response()->json($categorie, 201);
    }

    public function index()
    {
        $categories = Categorie::get();
        return response()->json([
            'categories' => $categories,
        ],200);
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
        ],200);
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
        $categorie->update($request->all());

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
