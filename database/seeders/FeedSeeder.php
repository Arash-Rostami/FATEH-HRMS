<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Feed;

class FeedSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Feed::factory(50)->create();
    }
}
