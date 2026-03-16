<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PlatsController extends Controller
{
    public function store(Request $request)
    {
        $this->authorize('create', Plat::class);
        // if (auth()->user()->role !== 'admin') {
        //     return response()->json([
        //         'message' => 'Forbidden: Only admins can create categories'
        //     ], 403);
        // }

        $validate = $request->validate([
            'title' => 'required|string|max:40',
            'description' => 'nullable|string|max:255',
            'price'       => 'required|numeric|min:10',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // 2MB
            'category_id' => 'nullable|array',
            'category_id.*' => 'exists:categories,id',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            //storage/app/public/plats
            $imagePath = $request->file('image')->store('plats', 'public');
        }

        $plat = Plat::create([
            'title'       => $validate['title'],
            'description' => $validate['description'],
            'price'       => $validate['price'],
            'image'       => $imagePath, // image path => storage/app/public/plats
            'user_id'     => auth()->id(),
        ]);


        if ($request->has('category_id') && is_array($request->category_id)) {
            $plat->categories()->attach($request->category_id);
        }

        return response()->json([
            'message' => 'Plat créé avec succès',
            'plat'    => $plat->load('categories') //Eager Loading plat infos and category
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

        $request->validate([
            'title' => 'sometimes|required|string|max:40',
            'description' => 'sometimes|nullable|string|max:255',
            'price' => 'sometimes|required|numeric|min:10',
            'image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'category_id' => 'sometimes|nullable|array',
            'category_id.*' => 'exists:categories,id',
        ]);

        $data = $request->only(['title', 'description', 'price']);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('plats', 'public');
        }

        $plat->update($data);

        if ($request->has('category_id')) {
            $plat->categories()->sync($request->input('category_id', []));
        }

        return response()->json([
            'message' => 'Plat updated successfully!',
            'data' => $plat->load('categories')
        ], 200);
    }

    function destroy($id)
    {
        $this->authorize('delete', Plat::class);
        // if (auth()->user()->role !== 'admin') {
        //     return response()->json([
        //         'message' => 'Forbidden: Only admins can delete plats'
        //     ], 403);
        // }

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
}
