<?php

namespace Database\Seeders;

use App\Models\ShippingMethod;
use App\Models\ShippingSetting;
use Illuminate\Database\Seeder;

class ShippingMethodSeeder extends Seeder
{
    public function run(): void
    {
        ShippingSetting::updateOrCreate(
            ['id' => 1],
            [
                'country' => 'Indonesia',
                'province' => 'Jawa Tengah',
                'city' => 'Jepara',
                'district' => 'Bangsri',
                'postal_code' => null,
            ]
        );

        ShippingMethod::updateOrCreate(
            ['code' => 'reguler'],
            [
                'name' => 'Reguler',
                'description' => 'Pengiriman reguler dengan estimasi standar.',
                'base_cost' => 20000,
                'same_district_cost' => 8000,
                'same_city_cost' => 12000,
                'same_province_cost' => 20000,
                'outside_province_cost' => 35000,
                'free_shipping_min' => 3000000,
                'estimate' => '1-4 hari',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        ShippingMethod::updateOrCreate(
            ['code' => 'instant-area-jepara'],
            [
                'name' => 'Instant Area Jepara',
                'description' => 'Pengiriman cepat khusus area Jepara dan sekitar.',
                'base_cost' => 25000,
                'same_district_cost' => 10000,
                'same_city_cost' => 20000,
                'same_province_cost' => 35000,
                'outside_province_cost' => 60000,
                'free_shipping_min' => 5000000,
                'estimate' => 'Same day / 1 hari',
                'is_active' => true,
                'sort_order' => 2,
            ]
        );
    }
}