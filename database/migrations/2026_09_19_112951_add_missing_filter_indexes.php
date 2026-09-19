<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two columns the app already filters on had no index.
 *
 * `orders.has_wholesale_items` is the whole basis of the Wholesale Orders screen
 * and a filter on the main orders table, so every visit scanned the order book.
 * `categories.is_active` had no index at all, and the storefront will filter on
 * it on every catalogue page once it reads the database.
 *
 * Both are paired with the column the same screens sort by, so the index covers
 * the sort as well as the filter.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->index(['has_wholesale_items', 'placed_at'], 'orders_wholesale_placed_index');
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->index(['is_active', 'sort_order'], 'categories_active_sort_index');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('orders_wholesale_placed_index');
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropIndex('categories_active_sort_index');
        });
    }
};
