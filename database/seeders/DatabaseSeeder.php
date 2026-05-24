<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            DepartmentSeeder::class,
            ProfileSeeder::class,
            ResourceSeeder::class,
            ReservationPolicySeeder::class,
            ReservationSeeder::class,
            AdSeeder::class,
            AuthoritySeeder::class,
            CommentSeeder::class,
            CredentialSeeder::class,
            DMSSeeder::class,
            EnergyTestSeeder::class,
            EventSeeder::class,
            FAQSeeder::class,
            FeedSeeder::class,
            LinkSeeder::class,
            MessageSeeder::class,
            OnboardingSeeder::class,
            PermissionSeeder::class,
            PhotoSeeder::class,
            PostSeeder::class,
            ReactionSeeder::class,
            ReadSeeder::class,
            ReportSeeder::class,
            ReviewSeeder::class,
            SuggestionSeeder::class,
            TaskSeeder::class,
            TicketSeeder::class,
        ]);
    }
}
