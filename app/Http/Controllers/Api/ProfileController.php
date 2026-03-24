<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{


    function updateProfile(Request $request)
    {
        $user = auth()->user();

        $this->authorize('updateDietaryTags', $user);

        $validated = $request->validate([
            'dietary_tags' => 'required|array',
            'dietary_tags.*' => 'in:vegan,no_sugar,no_cholesterol,gluten_free,no_lactose'
        ]);

        $tags = collect($user->dietary_tags ?? [])
            ->concat($validated['dietary_tags'])
            ->unique()
            ->values()
            ->toArray();

        $user->update([
            'dietary_tags' => $tags
        ]);

        return response()->json([
            'message' => 'Tags added successfully',
            'data' => $tags
        ]);
    }


    public function removeTag(Request $request)
    {
        $user = auth()->user();

        $this->authorize('updateDietaryTags', $user);

        $validated = $request->validate([
            'dietary_tags' => 'required|array',
            'dietary_tags.*' => 'in:vegan,no_sugar,no_cholesterol,gluten_free,no_lactose'
        ]);

        $tags = collect($user->dietary_tags ?? []);

        $updatedTags = $tags
            ->diff($validated['dietary_tags']) // supprime plusieurs
            ->values()
            ->toArray();

        $user->update([
            'dietary_tags' => $updatedTags
        ]);

        return response()->json([
            'message' => 'Tag removed successfully',
            'data' => $updatedTags
        ]);
    }
}
