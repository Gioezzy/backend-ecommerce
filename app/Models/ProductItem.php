<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductItem extends Model
{
    use HasFactory;

    protected $table = 'product_items';

    protected $fillable = [
        'name',
        'price',
        'promo',
        'description',
        'images',
        'stock',
        'vendors',
        'category_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
    ];

    protected function images(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => str_starts_with($value, 'http')
                ? $value
                : asset('storage/' . $value),
        );
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
