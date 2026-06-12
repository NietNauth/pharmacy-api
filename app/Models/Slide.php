<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Slide extends Model
{
    protected $fillable = [
        'image_url',
        'title',
        'description',
        'link',
        'order',
        'is_active',
    ];
}
