<?php

namespace Database\Factories;

use App\Models\HomeSection;
use App\Models\HomeSectionItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HomeSectionItem>
 */
class HomeSectionItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'home_section_id' => HomeSection::factory(),
            'product_id' => Product::factory(),
            'category_id' => null,
            'sort_order' => 0,
        ];
    }
}
