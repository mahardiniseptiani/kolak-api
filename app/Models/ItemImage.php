<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ItemImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'path',
    ];

    protected $appends = ['url'];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn () => url(Storage::url($this->path)),
        );
    }
}