<?php

use Database\Seeders\DtcDefinitionSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Seed the DTC knowledge base. The seeder is idempotent (upsert by
     * code/make/engine), so this is safe on fresh installs and on updates.
     */
    public function up(): void
    {
        (new DtcDefinitionSeeder)->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
