<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Credential;

class CredentialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Credential::factory(50)->create();
    }
}
