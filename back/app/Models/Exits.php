<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exits extends Model
{
    protected $fillable = [
        'fk_product_id',
        'fk_participation_id',
        'fk_user_id',
        'qtd',
    ];
    protected $table = 'exits';
}