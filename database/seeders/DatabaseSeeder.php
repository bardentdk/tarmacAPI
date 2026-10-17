<?php

namespace Database\Seeders;

use App\Models\DtcRecord;
use App\Models\Run;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * The DTC knowledge base is also seeded by a migration (on-device style),
     * calling it again here is harmless because the seeder is idempotent.
     */
    public function run(): void
    {
        $this->call(DtcDefinitionSeeder::class);

        $user = User::factory()->onTrial()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $vehicle = Vehicle::factory()->for($user)->create([
            'name' => 'Golf 7 GTI',
            'make' => 'Volkswagen',
            'model' => 'Golf GTI',
            'engine' => 'EA888 2.0 TSI',
            'tuning_stage' => 'stage1',
        ]);

        Run::factory()->count(12)->for($user)->for($vehicle)->create();
        Trip::factory()->count(15)->for($user)->for($vehicle)->create();
        DtcRecord::factory()->count(3)->for($user)->for($vehicle)->create();
    }
}
