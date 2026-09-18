<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Customers sign in with Google and have no password; staff sign in with a
     * password and never through Google (ADR-004).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('email_verified_at');
            $table->string('avatar_url')->nullable()->after('google_id');
            $table->string('phone', 15)->nullable()->after('avatar_url');
            $table->string('role', 16)->default('customer')->after('password');
            $table->boolean('is_active')->default(true)->after('role');
            $table->timestamp('last_login_at')->nullable()->after('is_active');

            // Filament app authentication (admin 2FA, ADR-004)
            $table->text('app_authentication_secret')->nullable()->after('last_login_at');
            $table->text('app_authentication_recovery_codes')->nullable()->after('app_authentication_secret');

            $table->softDeletes();

            $table->index('phone');
            $table->index(['role', 'is_active']);
        });

        DB::table('users')->update(['role' => 'customer']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['phone']);
            $table->dropIndex(['role', 'is_active']);
            $table->dropColumn([
                'google_id', 'avatar_url', 'phone', 'role', 'is_active', 'last_login_at',
                'app_authentication_secret', 'app_authentication_recovery_codes', 'deleted_at',
            ]);
        });
    }
};
