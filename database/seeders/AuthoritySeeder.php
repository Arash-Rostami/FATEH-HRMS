<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Authority;

class AuthoritySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Authority::factory(50)->create();
    }
}
