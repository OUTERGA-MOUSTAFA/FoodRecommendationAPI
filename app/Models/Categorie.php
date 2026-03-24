<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Categorie extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'description',
        'is_active',
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
