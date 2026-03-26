<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use Illuminate\Http\Request;

class IngredientController extends Controller
{
    public function index(Request $request)
    {
        $query = Ingredient::query();

        // 🔍 Filtrer par tag
        if ($request->has('tag')) {
            $query->whereJsonContains('tags', $request->tag);
        }

        $ingredients = $query->paginate($request->get('per_page', 10));

        return response()->json($ingredients);
    }

    public function store(Request $request)
    {
        // autorization
        $this->authorize('create', Ingredient::class);
        // Validation
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:ingredients,name',

            'tags' => 'nullable|array',

            'tags.*' => 'in:contains_meat,contains_sugar,contains_cholesterol,contains_gluten,contains_lactose',
        ]);

        // Création
        $ingredient = Ingredient::create($validated);

        return response()->json([
            'message' => 'Ingredient created successfully',
            'data' => $ingredient
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $ingredient = Ingredient::findOrFail($id);

        //  Policy
        $this->authorize('update', $ingredient);

        //  Validation
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100|unique:ingredients,name,' . $id,
            'tags' => 'nullable|array',
            'tags.*' => 'in:' . implode(',', Ingredient::TAGS),
        ]);

        //  Update
        $ingredient->update([
            'name' => $validated['name'] ?? $ingredient->name,
            'tags' => $validated['tags'] ?? $ingredient->tags,
        ]);
        return response()->json([
            'message' => 'Ingredient updated successfully',
            'data' => $ingredient
        ]);
    }

    public function destroy($id)
    {
        $ingredient = Ingredient::findOrFail($id);

        //  Policy
        $this->authorize('delete', $ingredient);

        // Vérifier relation avec plats
        if ($ingredient->plats()->exists()) {
            return response()->json([
                'message' => 'Cannot delete: ingredient is used in plats'
            ], 409);
        }

        $ingredient->delete();

        return response()->json([
            'message' => 'Ingredient deleted successfully'
        ]);
    }
}
