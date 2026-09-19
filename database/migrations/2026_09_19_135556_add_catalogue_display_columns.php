<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two display nouns the shop has always shown but the schema had nowhere to
 * keep, so the seeder dropped them when it turned the sample catalogue into
 * rows (ADR-027).
 *
 * `products.variant_label` is what a product calls its options — "Shade",
 * "Weight", "Box", "Pack" — and drives the copy "Choose shade" and "3 weights".
 * `product_variants.unit` is what a wholesale line is counted in, "box" or
 * "kg", shown beside the price bands. `products.highlights` is the short list
 * of selling points the product page shows under the description.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('variant_label', 24)->default('Option')->after('brand');
            $table->json('highlights')->nullable()->after('description');
        });

        Schema::table('product_variants', function (Blueprint $table): void {
            $table->string('unit', 24)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['variant_label', 'highlights']);
        });

        Schema::table('product_variants', function (Blueprint $table): void {
            $table->dropColumn('unit');
        });
    }
};
