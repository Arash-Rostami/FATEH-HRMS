<?php

namespace Database\Seeders;

use App\Models\Feed;
use App\Models\Poll;
use App\Models\User;
use Illuminate\Database\Seeder;

class PollSeeder extends Seeder
{
    public function run(): void
    {
        if (Poll::count() > 0) {
            return;
        }

        $userIds = User::pluck('id');
        $feedIds = Feed::pluck('id');

        if ($userIds->isEmpty() || $feedIds->isEmpty()) {
            return;
        }

        $faker = fake('fa_IR');
        $seen = [];

        for ($i = 0; $i < 40; $i++) {
            $userId = $userIds->random();
            $feedId = $feedIds->random();
            $optionIndex = $faker->numberBetween(0, 3);
            $signature = $userId . ':' . $feedId . ':' . $optionIndex;

            if (isset($seen[$signature])) {
                continue;
            }
            $seen[$signature] = true;

            Poll::create([
                'user_id' => $userId,
                'feed_id' => $feedId,
                'option_index' => $optionIndex,
            ]);
        }
    }
};