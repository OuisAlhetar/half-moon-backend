<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCat extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'desc'
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'cat_id');
    }
}
