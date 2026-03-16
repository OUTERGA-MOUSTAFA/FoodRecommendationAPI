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
        // 1. اسم الجدول: category_plats
        // 2. المفتاح اللي كيربط هاد الموديل (Plat): plat_id
        // 3. المفتاح اللي كيربط الموديل الآخر (Categorie): category_id
        //return $this->belongsToMany(Categorie::class, 'category_plats', 'plat_id', 'category_id');
        // زدت هنا 'category_plats' (سمية الجدول) و 'plat_id' و 'category_id'
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
}
