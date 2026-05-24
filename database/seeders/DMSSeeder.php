<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DMS;

class DMSSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DMS::factory(50)->create();
    }
}
