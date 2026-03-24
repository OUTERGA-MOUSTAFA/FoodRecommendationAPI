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
        'user_id',
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
}
