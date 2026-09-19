<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Homepage banners: the desktop hero carousel, the phone hero and the promo
     * cards, all managed by the admin with a schedule (ADR-024).
     */
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('placement', 24);
            $table->string('eyebrow', 40)->nullable();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('body')->nullable();
            $table->string('cta_label', 40)->nullable();
            $table->string('cta_url')->nullable();
            $table->string('secondary_cta_label', 40)->nullable();
            $table->string('secondary_cta_url')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_alt')->nullable();
            $table->string('theme', 16)->default('brand');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['placement', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
