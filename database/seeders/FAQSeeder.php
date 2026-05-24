<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FAQ;

class FAQSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        FAQ::factory(50)->create();
    }
}
