<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataVariation extends Model
{
    use HasFactory;

    protected $table = 'data_variations';

    protected $fillable = [
        'service_name',
        'service_id',
        'convinience_fee',
        'name',
        'variation_amount',
        'fixedPrice',
        'status',
        'variation_code',
    ];

    protected $casts = [
        'status' => 'boolean',
        'variation_amount' => 'float',
        'convinience_fee' => 'float',
    ];
}
