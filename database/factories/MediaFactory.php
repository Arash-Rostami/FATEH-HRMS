<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'parent_id' => null,
            'kind' => Media::KIND_AUDIO,
            'title' => fake()->sentence(3),
            'topic' => fake()->optional()->words(3, true),
            'description' => fake()->paragraph(),
            'cover_image' => null,
            'file_path' => 'media/files/' . fake()->uuid() . '.mp3',
            'transcript_path' => null,
            'chapters_path' => null,
            'icon' => null,
            'duration_seconds' => fake()->numberBetween(60, 3600),
            'position' => 0,
            'active' => true,
            'pinned' => false,
            'department_id' => null,
            'departments' => null,
            'report_date' => null,
            'expires_at' => null,
            'report_ids' => null,
        ];
    }

    public function series(): static
    {
        return $this->state(fn () => [
            'kind' => Media::KIND_SERIES,
            'file_path' => null,
            'duration_seconds' => null,
        ]);
    }

    public function withEpisodes(int $count = 3): static
    {
        return $this->series()->afterCreating(function (Media $series) use ($count) {
            Media::factory()->count($count)->sequence(fn ($sequence) => [
                'position' => $sequence->index + 1,
                'title' => 'جلسه ' . ($sequence->index + 1),
            ])->create([
                'parent_id' => $series->id,
                'user_id' => $series->user_id,
                'kind' => Media::KIND_AUDIO,
            ]);
        });
    }

    public function locked(): static
    {
        return $this->state(fn () => [
            'report_date' => now()->addDay()->toDateString(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->subDay()->toDateString(),
        ]);
    }
}
