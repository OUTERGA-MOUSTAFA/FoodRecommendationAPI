<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plat extends Model
{
    protected $fillable = [
        'title',
        'description',
        'price',
        'image',
        'is_available',
        'category_id',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'price' => 'decimal:2',
    ];

    function user()
    {
        return $this->belongsTo(User::class);
    }
    public function categories()
    {
        return $this->belongsToMany(Categorie::class, 'category_plats', 'plat_id', 'category_id')->withTimestamps();
    }
    // for Don't Repeat Yourself
    protected function getImageAttribute($value)
    {
        if ($value) {
            return asset('storage/' . $value);
        }
        return null;
    }

    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'ingredient_plat', 'plat_id', 'ingredient_id')->withTimestamps();
    }


    public function calculateScore($user): array
    {
        // Si pas de profil
        if (!$user || !$user->profile) {
            return [
                'score' => 0,
                'label' => 'unknown',
                'status' => 'processing'
            ];
        }

        $preferences = $user->profile->preferences ?? [];
        // ex: ['contains_gluten', 'contains_lactose']

        $ingredients = $this->ingredients;

        if ($ingredients->isEmpty()) {
            return [
                'score' => 1,
                'label' => 'safe',
                'status' => 'ready'
            ];
        }

        $total = $ingredients->count();
        $bad = 0;

        foreach ($ingredients as $ingredient) {
            $tags = $ingredient->tags ?? [];

            foreach ($tags as $tag) {
                if (in_array($tag, $preferences)) {
                    $bad++;
                    break;
                }
            }
        }

        // Score entre 0 et 1
        $score = 1 - ($bad / $total);

        // Label
        if ($score == 1) {
            $label = 'excellent';
        } elseif ($score >= 0.7) {
            $label = 'good';
        } elseif ($score >= 0.4) {
            $label = 'average';
        } else {
            $label = 'risky';
        }

        return [
            'score' => round($score, 2),
            'label' => $label,
            'status' => 'ready'
        ];
    }
}
