<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Seeds the admin-editable settings from the demo defaults in `config/shop.php`
 * (ADR-013). `App\Support\ShopSettings` reads these and falls back to the config.
 */
class ShopSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'shop.name' => config('shop.name'),
            'shop.tagline' => config('shop.tagline'),
            'shop.phone' => config('shop.phone'),
            'shop.whatsapp' => config('shop.whatsapp'),
            'shop.email' => config('shop.email'),
            'shop.address' => config('shop.address'),
            'shop.hours' => config('shop.hours'),
            'shop.logo_path' => config('shop.logo_path'),
            'payment.upi_vpa' => config('shop.payment.upi_vpa'),
            'payment.upi_payee_name' => config('shop.payment.upi_payee_name'),
            'payment.cod_max_paise' => config('shop.payment.cod_max_paise'),
            'delivery.charge_paise' => config('shop.delivery.charge_paise'),
            'delivery.free_above_paise' => config('shop.delivery.free_above_paise'),
            'delivery.min_order_paise' => config('shop.delivery.min_order_paise'),
            'delivery.eta' => config('shop.delivery.eta'),
            'delivery.area' => config('shop.delivery.area'),
            'delivery.pincodes' => config('shop.delivery.pincodes'),
            'print.label_format' => config('shop.print.label_format'),
            'print.invoice_format' => config('shop.print.invoice_format'),
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
