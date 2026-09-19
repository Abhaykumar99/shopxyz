<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Development data: the shop's settings, its people, the sample catalogue and a
 * believable week of trading, so every admin screen has something to show.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ShopSettingSeeder::class,
            UserSeeder::class,
            CatalogSeeder::class,
            HomepageSeeder::class,
            OrderSeeder::class,
            WholesaleEnquirySeeder::class,
        ]);
    }
}
