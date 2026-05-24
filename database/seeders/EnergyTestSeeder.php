<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EnergyTest;

class EnergyTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        EnergyTest::factory(50)->create();
    }
}
