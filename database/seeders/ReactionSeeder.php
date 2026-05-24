<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Reaction;

class ReactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Reaction::factory(50)->create();
    }
}
