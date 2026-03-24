<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use Illuminate\Http\Request;

class IngredientController extends Controller
{
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
}
