<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 20)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status', 32);
            $table->string('payment_method', 16);
            $table->string('payment_status', 32);

            $table->string('ship_name');
            $table->string('ship_phone', 15);
            $table->string('ship_line1');
            $table->string('ship_line2')->nullable();
            $table->string('ship_landmark')->nullable();
            $table->string('ship_city', 60);
            $table->string('ship_state', 60);
            $table->char('ship_pincode', 6);

            $table->unsignedBigInteger('subtotal_paise');
            $table->unsignedBigInteger('discount_paise')->default(0);
            $table->unsignedBigInteger('delivery_charge_paise')->default(0);
            $table->unsignedBigInteger('total_paise');
            $table->boolean('has_wholesale_items')->default(false);

            $table->text('customer_note')->nullable();
            $table->string('cancel_reason')->nullable();

            $table->timestamp('placed_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('packed_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'placed_at']);
            $table->index(['payment_status', 'placed_at']);
            $table->index(['user_id', 'placed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
