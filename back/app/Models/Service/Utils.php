<?php

namespace App\Models\Service;

use App\Models\Exits;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;

class Utils extends Model
{
    public static function getProductQuantities($productId)
    {
        return [
            'qtdProduct' => Product::where('id', $productId)->sum('qtd'),
            'qtdExits' => Exits::where('fk_product_id', $productId)->sum('qtd'),
        ];
    }
}