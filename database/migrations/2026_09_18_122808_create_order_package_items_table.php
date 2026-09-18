<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_package_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamps();

            $table->unique(['order_package_id', 'order_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_package_items');
    }
};
