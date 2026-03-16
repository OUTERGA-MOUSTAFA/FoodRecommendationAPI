<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categorie extends Model
{

    protected $fillable = [
        'name',
        'description',
        'user_id'
    ];
    public function plats()
    {
        return $this->belongsToMany(Plat::class, 'category_plats', 'category_id', 'plat_id')->withTimestamps();
    }

    function user()
    {
        return $this->belongsTo(User::class);
    }
}
