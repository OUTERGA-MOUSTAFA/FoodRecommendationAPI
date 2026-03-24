<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recommendation extends Model
{
    protected $fillable = [
        'user_id',
        'plat_id',
        'score',
        'label',
        'warning_message',
        'status',
    ];
}
