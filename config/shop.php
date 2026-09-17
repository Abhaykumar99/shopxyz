<?php

/*
|--------------------------------------------------------------------------
| Shop defaults
|--------------------------------------------------------------------------
|
| Fallback values for the admin-editable shop settings (ADR-013). Until the
| admin saves real values, these are what customers see. This is the ONLY
| place the temporary demo shop name may appear. Always read shop details
| through App\Support\ShopSettings, never from this file directly in views.
|
*/

return [

    'name' => env('SHOP_DEMO_NAME', 'Demo Gift Store'),

    'tagline' => 'Cosmetics, sweets and gifts, delivered to your door',

    'phone' => '+91 98765 43210',

    'whatsapp' => '+91 98765 43210',

    'email' => 'hello@example.com',

    'address' => 'Shop No. 4, Main Market, Patna, Bihar 800001',

    'hours' => 'Open daily, 9 am to 9 pm',

    'logo_path' => null,

    /*
    | Default print formats (ADR-014). Values of App\Enums\PrintFormat.
    */
    'print' => [
        'label_format' => 'thermal_4x6',
        'invoice_format' => 'a4',
    ],

];
