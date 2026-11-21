<?php

namespace Database\Seeders;

use App\Models\Media;
use Illuminate\Database\Seeder;

class MediaSeeder extends Seeder
{
    public function run(): void
    {
        $series = Media::factory()->withEpisodes(3)->create(['title' => 'سری نمونه']);

        Media::factory()->locked()->create([
            'parent_id' => $series->id,
            'user_id' => $series->user_id,
            'position' => 4,
            'title' => 'جلسهٔ قفل',
        ]);

        Media::factory()->series()->expired()->create(['title' => 'سری منقضی']);
        Media::factory()->create(['kind' => Media::KIND_VIDEO, 'title' => 'ویدیو تکی', 'file_path' => 'media/files/' . fake()->uuid() . '.mp4']);
    }
}
