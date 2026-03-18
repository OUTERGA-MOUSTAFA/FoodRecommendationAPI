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
            'dietary_tags' => 'array',
            'dietary_tags.*' => 'in:vegan,no_sugar,no_cholesterol,gluten_free,no_lactose',
        ]);

        $user->update([
            'dietary_tags' => $validated['dietary_tags'] ?? []
        ]);

        return response()->json([
            'message' => 'Dietary tags updated',
            'data' => $user->dietary_tags
        ]);
    }

    public function addTags(Request $request)
    {
        $user = auth()->user();

        $this->authorize('updateDietaryTags', $user);

        $validated = $request->validate([
            'tags' => 'required|array',
            'tags.*' => 'in:vegan,no_sugar,no_cholesterol,gluten_free,no_lactose'
        ]);

        $currentTags = $user->dietary_tags ?? [];

        // current tags + new tags
        $mergedTags = array_merge($currentTags, $validated['tags']);

        // no duplicate tag
        $uniqueTags = array_values(array_unique($mergedTags));

        // update
        $user->update([
            'dietary_tags' => $uniqueTags
        ]);

        return response()->json([
            'message' => 'Tags added successfully',
            'data' => $uniqueTags
        ]);
    }

    public function removeTag(Request $request)
    {
        $user = auth()->user();

        $this->authorize('updateDietaryTags', $user);

        $request->validate([
            'tag' => 'required|in:vegan,no_sugar,no_cholesterol,gluten_free,no_lactose'
        ]);

        $tags = $user->dietary_tags ?? [];

        $tags = array_values(array_filter($tags, function ($tag) use ($request) {
            return $tag !== $request->tag;
        }));

        $user->update([
            'dietary_tags' => $tags
        ]);

        return response()->json([
            'message' => 'Tag removed',
            'data' => $tags
        ]);
    }
}
