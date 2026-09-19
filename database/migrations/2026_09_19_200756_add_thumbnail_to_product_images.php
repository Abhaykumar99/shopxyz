<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Each product photo is stored at two sizes: the full one for the product page
 * and a smaller one for cards. A category page shows twenty-four cards, so
 * serving the full image everywhere would cost a phone several megabytes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_images', function (Blueprint $table): void {
            $table->string('thumbnail_path')->nullable()->after('path');
        });
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table): void {
            $table->dropColumn('thumbnail_path');
        });
    }
};
