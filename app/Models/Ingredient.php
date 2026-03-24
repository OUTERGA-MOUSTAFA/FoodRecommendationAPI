<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    protected $fillable = [
        'name',
        'tags',
    ];

    // Cast JSON → array automatique
    protected $casts = [
        'tags' => 'array',
    ];

    // Tags autorisés (centralisation) sugestions
    public const TAGS = [
        'contains_meat',
        'contains_sugar',
        'contains_cholesterol',
        'contains_gluten',
        'contains_lactose',
    ];

    public function plats()
    {
        return $this->belongsToMany(Plat::class, 'ingredient_plat', 'ingredient_id', 'plat_id')->withTimestamps();
    }
}
