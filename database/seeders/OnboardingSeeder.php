<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Onboarding;

class OnboardingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Onboarding::factory(50)->create();
    }
}
