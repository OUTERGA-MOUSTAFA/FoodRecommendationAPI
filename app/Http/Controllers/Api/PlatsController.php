<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plat;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;


class PlatsController extends Controller
{
    public function store(Request $request)
    {
        $this->authorize('create', Plat::class);

        $validated = $request->validate([
            'title' => 'required|string|max:40',
            'description' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:10',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_available' => 'boolean',
            'category_id' => 'required|exists:categories,id',
            'ingredient_ids' => 'nullable|array',
            'ingredient_ids.*' => 'exists:ingredients,id',
        ]);

        $imageUrl = null;

        if ($request->hasFile('image')) {
            try {
                $imageUrl = Cloudinary::upload(
                    $request->file('image')->getRealPath(),
                    ['folder' => 'plats']
                )->getSecurePath();
            } catch (\Exception $e) {

                $path = $request->file('image')->store('plats', 'public');

                // 🔥 stocke seulement le path
                $imageUrl = $path;
            }
        }

        $plat = Plat::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'image' => $imageUrl,
            'user_id' => auth()->id(),
        ]);

        $plat->categories()->attach($validated['category_id']);

        if (!empty($validated['ingredient_ids'])) {
            $plat->ingredients()->sync($validated['ingredient_ids']);
        }

        return response()->json([
            'message' => 'Plat créé avec succès',
            'plat' => $plat->load('categories', 'ingredients')
        ], 201);
    }

    public function show($id)
    {

        $plats = Plat::with('categories')->find($id);

        return response()->json([
            'data' => $plats
        ], 200);
    }

    public function index()
    {
        // Lazy Eager Loading
        // $plats = Plat::get()->load('categories')->map(function ($plat) {
        //// http://localhost:8000/storage/plats/image.webp
        //     if ($plat->image) {
        //         $plat->image = asset('storage/' . $plat->image);
        //     }
        //     return $plat;
        // });

        // Eager Loading => best practice
        // $plats = Plat::with('categories')->get()->map(function ($plat) {
        //             // http://localhost:8000/storage/plats/image.webp
        //             if ($plat->image) {
        //                 $plat->image = asset('storage/' . $plat->image);
        //             }
        //             return $plat;
        //         });
        $plats = Plat::with('categories')->get();

        return response()->json([
            'message' => 'Voici tous les plats',
            'data' => $plats
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('update', Plat::class);
        // if (auth()->user()->role !== 'admin') {
        //     return response()->json([
        //         'message' => 'Forbidden: Only admins can update plats'
        //     ], 403);
        // }

        $plat = Plat::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:40',
            'description' => 'sometimes|nullable|string|max:255',
            'price' => 'sometimes|required|numeric|min:10',
            'is_available' => 'sometimes|boolean',
            'image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'category_id' => 'sometimes|array',
            'category_id.*' => 'exists:categories,id',
            'ingredient_ids' => 'sometimes|array',
            'ingredient_ids.*' => 'exists:ingredients,id',
        ]);

        $data = $request->only([
            'title',
            'description',
            'price',
            'is_available'
        ]);

        // upload image (avec fallback)
        if ($request->hasFile('image')) {
            try {
                $data['image'] = Cloudinary::upload(
                    $request->file('image')->getRealPath(),
                    ['folder' => 'plats']
                )->getSecurePath();
            } catch (\Exception $e) {
                $data['image'] = $request->file('image')->store('plats', 'public');
            }
        }

        $plat->update($data);

        // categories
        if ($request->has('category_id')) {
            $plat->categories()->sync($request->category_id);
        }

        // ingredients
        if ($request->has('ingredient_ids')) {
            $plat->ingredients()->sync($request->ingredient_ids);
        }
        
        return response()->json([
            'message' => 'Plat updated successfully!',
            'data' => $plat->load('categories')
        ], 200);
    }

    function destroy($id)
    {
        $this->authorize('delete', Plat::class);

        $plat = Plat::findOrFail($id);
        if ($plat->image) {
            // public/storage/plats/image.webp 
            Storage::disk('public')->delete($plat->image);
        }
        $plat->delete();

        return response()->json([
            'message' => 'Plat deleted successfully!'
        ], 200);
    }

    public function generateRecommendation(array $userTags = [])
    {
        if (!$this->relationLoaded('ingredients')) {
            return [
                'score' => 0,
                'label' => null,
                'warning_message' => null,
                'status' => 'processing'
            ];
        }

        $conflicts = [];

        foreach ($this->ingredients as $ingredient) {
            foreach ($ingredient->tags ?? [] as $tag) {

                if ($tag === 'contains_meat' && in_array('vegan', $userTags)) {
                    $conflicts[] = 'contains meat';
                }

                if ($tag === 'contains_sugar' && in_array('no_sugar', $userTags)) {
                    $conflicts[] = 'contains sugar';
                }

                if ($tag === 'contains_cholesterol' && in_array('no_cholesterol', $userTags)) {
                    $conflicts[] = 'contains cholesterol';
                }

                if ($tag === 'contains_gluten' && in_array('gluten_free', $userTags)) {
                    $conflicts[] = 'contains gluten';
                }

                if ($tag === 'contains_lactose' && in_array('no_lactose', $userTags)) {
                    $conflicts[] = 'contains lactose';
                }
            }
        }

        $conflicts = array_unique($conflicts);

        $score = max(0, 100 - (count($conflicts) * 25));

        $label = match (true) {
            $score >= 80 => '✅ Highly Recommended',
            $score >= 50 => '🟡 Recommended with notes',
            default => '⚠️ Not Recommended',
        };

        $warning = null;

        if ($score < 50 && !empty($conflicts)) {
            $warning = 'Ce plat contient : ' . implode(', ', $conflicts);
        }

        return [
            'score' => $score,
            'label' => $label,
            'warning_message' => $warning,
            'status' => 'ready'
        ];
    }
}
