<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `make` and `engine` are stored as normalized lowercase strings, with an
     * empty string meaning "generic" so the unique index works on every driver.
     */
    public function up(): void
    {
        Schema::create('dtc_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8);
            $table->string('make', 64)->default('');
            $table->string('engine', 64)->default('');
            $table->string('title');
            $table->string('system', 64);
            $table->text('explanation');
            $table->string('urgency', 12);
            $table->text('can_drive_note')->nullable();
            $table->string('source', 8)->default('kb');
            $table->timestamps();

            $table->unique(['code', 'make', 'engine']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dtc_definitions');
    }
};
