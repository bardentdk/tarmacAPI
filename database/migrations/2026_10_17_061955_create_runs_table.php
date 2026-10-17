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
        Schema::create('runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid');
            $table->string('mode', 24);
            $table->double('result_s')->nullable();
            $table->double('trap_kmh')->nullable();
            $table->double('distance_m')->nullable();
            $table->double('sixty_ft_s')->nullable();
            $table->double('reaction_s')->nullable();
            $table->json('splits')->nullable();
            $table->double('power_hp')->nullable();
            $table->double('power_ci_hp')->nullable();
            $table->double('torque_nm')->nullable();
            $table->unsignedInteger('peak_rpm')->nullable();
            $table->json('curve')->nullable();
            $table->json('conditions')->nullable();
            $table->string('source', 16);
            $table->double('accuracy_s')->default(0);
            $table->boolean('is_record')->default(false);
            $table->text('notes')->nullable();
            $table->json('samples')->nullable();
            $table->unsignedInteger('samples_bytes')->default(0);
            $table->timestamp('recorded_at');
            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('synced_at')->nullable()->index();

            $table->unique(['user_id', 'uuid']);
            $table->index(['user_id', 'mode']);
            $table->index(['user_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('runs');
    }
};
