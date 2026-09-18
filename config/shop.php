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
    | UPI details shown on the payment QR (placeholder until the admin sets them),
    | and the ceiling for cash on delivery. Above it only UPI is offered, which
    | keeps large bulk orders off credit (ADR-019). Zero removes the ceiling.
    */
    'payment' => [
        'upi_vpa' => 'demo.shop@okaxis',
        'upi_payee_name' => 'Shop owner (demo)',
        'cod_max_paise' => 2000000,
    ],

    /*
    | Delivery rules, all amounts in paise (client questions 3 and 4, defaults).
    */
    'delivery' => [
        'charge_paise' => 4000,
        'free_above_paise' => 49900,
        'min_order_paise' => 9900,
        'eta' => 'Same day if ordered before 5 pm',
        'pincodes' => ['800001', '800002', '800003', '800004', '800006', '800008', '800013', '800014', '800016', '800020', '800025'],
        'area' => 'Patna',
    ],

    /*
    | Default print formats (ADR-014). Values of App\Enums\PrintFormat.
    */
    'print' => [
        'label_format' => 'thermal_4x6',
        'invoice_format' => 'a4',
    ],

];
