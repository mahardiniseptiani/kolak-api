<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = ['seller_id', 'category_id', 'title', 'description',
    'price', 'condition', 'status', 'area', 'latitude', 'longitude'];

public function seller()
{
    return $this->belongsTo(User::class, 'seller_id');
}

public function category()
{
    return $this->belongsTo(Category::class);
}

public function images()
{
    return $this->hasMany(ItemImage::class);
}
}
