<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopSetting extends Model
{
    protected $fillable = [
        'site_name',
        'support_email',
        'support_phone',
        'login_heading',
        'login_subheading',
        'login_showcase_title',
        'login_showcase_text',
        'login_image',
    ];
}