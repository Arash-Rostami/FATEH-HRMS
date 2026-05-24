<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Suggestion;

class SuggestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Suggestion::factory(50)->create();
    }
}
