<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Read;

class ReadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Read::factory(50)->create();
    }
}
