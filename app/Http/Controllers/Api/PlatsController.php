<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlateResource;
use App\Models\Plat;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PlatsController extends Controller
{
    // =========================
    //  STORE
    // =========================
    public function store(Request $request)
    {
        $this->authorize('create', Plat::class);

        $data = $request->validate([
            'title' => 'required|string|max:40',
            'description' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:10',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_available' => 'boolean',
            'category_id' => 'required|exists:categories,id',
            'ingredient_ids' => 'nullable|array',
            'ingredient_ids.*' => 'exists:ingredients,id',
        ]);

        // upload image
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

        $plat = Plat::create([
            ...$data,
            'user_id' => auth()->id(),
        ]);

        $plat->categories()->attach($data['category_id']);

        if (!empty($data['ingredient_ids'])) {
            $plat->ingredients()->sync($data['ingredient_ids']);
        }

        return response()->json([
            'message' => 'Plat created successfully',
            'data' => new PlateResource($plat->load('categories', 'ingredients'))
        ], 201);
    }

    // =========================
    //  INDEX
    // =========================
    public function index()
    {
        $plats = Plat::with('categories', 'ingredients')
            ->select('id', 'title', 'description', 'price', 'image', 'is_available')
            ->latest()
            ->get();

        return response()->json([
            'message' => 'All plats retrieved successfully',
            'data' => PlateResource::collection($plats)
        ]);
    }

    // =========================
    //  SHOW
    // =========================
    public function show($id)
    {
        $plat = Plat::with('categories', 'ingredients')->findOrFail($id);

        return response()->json([
            'message' => "Plat: {$plat->title}",
            'data' => new PlateResource($plat)
        ]);
    }

    // =========================
    //  UPDATE
    // =========================
    public function update(Request $request, $id)
    {
        $plat = Plat::findOrFail($id);

        $this->authorize('update', $plat);

        $data = $request->validate([
            'title' => 'sometimes|string|max:40',
            'description' => 'nullable|string|max:255',
            'price' => 'sometimes|numeric|min:10',
            'is_available' => 'sometimes|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'category_id' => 'sometimes|array',
            'category_id.*' => 'exists:categories,id',
            'ingredient_ids' => 'sometimes|array',
            'ingredient_ids.*' => 'exists:ingredients,id',
        ]);

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

        if (isset($data['category_id'])) {
            $plat->categories()->sync($data['category_id']);
        }

        if (isset($data['ingredient_ids'])) {
            $plat->ingredients()->sync($data['ingredient_ids']);
        }

        return response()->json([
            'message' => 'Plat updated successfully',
            'data' => new PlateResource($plat->load('categories', 'ingredients'))
        ]);
    }

    // =========================
    //  DELETE
    // =========================
    public function destroy($id)
    {
        $plat = Plat::findOrFail($id);

        $this->authorize('delete', $plat);

        if ($plat->image) {
            Storage::disk('public')->delete($plat->image);
        }

        $title = $plat->title;

        $plat->delete();

        return response()->json([
            'message' => "Plat '{$title}' deleted successfully"
        ]);
    }
}