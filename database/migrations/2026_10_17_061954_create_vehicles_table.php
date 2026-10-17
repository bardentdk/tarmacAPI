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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid');
            $table->string('name');
            $table->string('make', 64);
            $table->string('model', 64);
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('engine', 64)->nullable();
            $table->string('fuel', 16);
            $table->string('gearbox', 16);
            $table->string('drivetrain', 16);
            $table->unsignedInteger('mass_kg');
            $table->string('tire_size', 32)->nullable();
            $table->double('wheel_radius_m');
            $table->double('cda');
            $table->string('tuning_stage', 16)->default('stock');
            $table->unsignedInteger('redline_rpm');
            $table->unsignedInteger('shift_light_start_rpm');
            $table->double('k_cal')->default(1);
            $table->string('vin', 17)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('synced_at')->nullable()->index();

            $table->unique(['user_id', 'uuid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
