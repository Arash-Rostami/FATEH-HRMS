<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ReservationPolicy;

class ReservationPolicySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ReservationPolicy::factory(50)->create();
    }
}
