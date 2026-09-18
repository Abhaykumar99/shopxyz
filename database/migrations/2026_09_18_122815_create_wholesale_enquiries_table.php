<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wholesale_enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('business_name', 120);
            $table->string('contact_name', 80);
            $table->string('phone', 15);
            $table->string('email', 120)->nullable();
            $table->string('gstin', 15)->nullable();
            $table->string('business_type', 24);
            $table->string('city', 60);
            $table->char('pincode', 6);
            $table->date('needed_by')->nullable();
            $table->text('message')->nullable();
            $table->string('status', 16)->default('new');
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('items')->nullable();
            $table->unsignedBigInteger('estimate_paise')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wholesale_enquiries');
    }
};
