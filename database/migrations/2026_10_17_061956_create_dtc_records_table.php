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
        Schema::create('dtc_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid');
            $table->string('code', 8);
            $table->string('kind', 12);
            $table->json('freeze_frame')->nullable();
            $table->timestamp('read_at');
            $table->timestamp('cleared_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('synced_at')->nullable()->index();

            $table->unique(['user_id', 'uuid']);
            $table->index(['user_id', 'read_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dtc_records');
    }
};
