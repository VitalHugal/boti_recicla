<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'credit',
        'qtd',
    ];
    protected $table = 'products';
}