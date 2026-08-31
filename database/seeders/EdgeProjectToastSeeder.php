<?php

namespace Database\Seeders;

use App\Models\Edge;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class EdgeProjectToastSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('edges')) {
            $this->command->warn('edges table missing — run `php artisan migrate` first.');
            return;
        }

        $user = User::first();
        $project = Project::first();

        if (!$user || !$project) {
            $this->command->warn('Need at least one User and one Project to seed an Edge toast.');
            return;
        }

        Edge::query()->for($user->id, 'projects-controller:edge', $project->id)->delete();

        Edge::create([
            'user_id' => $user->id,
            'edge_key' => 'projects-controller:edge',
            'subject_type' => Project::class,
            'subject_id' => (string) $project->id,
            'icon' => 'group_add',
            'title' => 'دعوت به پروژه: ' . $project->name,
            'body' => $project->name,
            'url' => route('projects', ['open' => $project->id]),
        ]);

        $this->command->info("Edge toast seeded: user #{$user->id} ← project #{$project->id} ({$project->name}). Log in as that user; the card shows on every page.");
    }
}