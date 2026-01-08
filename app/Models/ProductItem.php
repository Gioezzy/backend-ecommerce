<?php

namespace App\Models;

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
        // 'images' is currently stored as a JSON/String in the migration?
        // If it's a comma separated string or raw JSON string, we might want an accessor
        // But for now, let's keep it simple.
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
