<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactSetting extends Model
{
    protected $fillable = [
        'heading',
        'subheading',
        'description',
        'phone',
        'email',
        'address',
        'address_city',
        'address_country',
        'open_hours',
        'notify_email',
        'notify_phone',
    ];

    /**
     * Ambil satu-satunya baris setting, buat kalau belum ada.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'heading'         => 'Contact Us',
            'subheading'      => 'Hubungi Kami',
            'description'     => 'Ada pertanyaan atau butuh bantuan? Tim kami siap membantu kamu.',
            'address_country' => 'Indonesia',
        ]);
    }
}
