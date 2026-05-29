<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingSetting extends Model
{
    protected $fillable = [
        'country',
        'province',
        'city',
        'district',
        'postal_code',
    ];
}