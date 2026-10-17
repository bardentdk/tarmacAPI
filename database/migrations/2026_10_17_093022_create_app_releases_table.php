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
        Schema::create('app_releases', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('project', 64);
            $table->string('platform', 16)->default('android');
            $table->string('channel', 16)->default('production');
            $table->string('kind', 8);
            $table->string('fingerprint', 191)->nullable();
            $table->string('version', 50);
            $table->unsignedInteger('version_code')->nullable();
            $table->text('notes')->nullable();
            $table->string('sha256', 64);
            $table->unsignedBigInteger('size');
            $table->string('path');
            $table->string('native_signature', 64)->nullable();
            $table->boolean('mandatory')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('published_at');
            $table->timestamps();

            $table->index(
                ['project', 'kind', 'channel', 'fingerprint', 'is_active', 'published_at'],
                'app_releases_lookup_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_releases');
    }
};
