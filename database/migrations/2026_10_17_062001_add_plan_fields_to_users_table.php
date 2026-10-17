<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('plan', 16)->default('free');
            $table->string('plan_period', 16)->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('plan_expires_at')->nullable();
            $table->string('revenuecat_app_user_id')->nullable()->unique();
            $table->timestamp('last_synced_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['revenuecat_app_user_id']);
            $table->dropColumn(['plan', 'plan_period', 'trial_ends_at', 'plan_expires_at', 'revenuecat_app_user_id', 'last_synced_at']);
        });
    }
};
