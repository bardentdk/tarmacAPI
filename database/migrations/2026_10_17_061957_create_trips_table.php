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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->double('distance_km')->default(0);
            $table->unsignedTinyInteger('health_score');
            $table->json('components')->nullable();
            $table->json('metrics')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('synced_at')->nullable()->index();

            $table->unique(['user_id', 'uuid']);
            $table->index(['user_id', 'started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
