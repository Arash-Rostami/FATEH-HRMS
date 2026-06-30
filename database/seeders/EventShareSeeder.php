<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EventShare;
use App\Models\Event;
use App\Models\User;

class EventShareSeeder extends Seeder
{
    public function run(): void
    {
        if (EventShare::count() > 0) {
            return;
        }

        $eventIds = Event::pluck('id')->all();
        $userIds = User::pluck('id')->all();

        if (empty($eventIds) || empty($userIds)) {
            return;
        }

        $faker = fake('fa_IR');
        $target = min(30, count($eventIds) * count($userIds));
        $created = 0;
        $attempts = 0;
        $maxAttempts = $target * 10;

        while ($created < $target && $attempts < $maxAttempts) {
            $attempts++;
            $eventId = $faker->randomElement($eventIds);
            $userId = $faker->randomElement($userIds);
            $sharedBy = $faker->randomElement($userIds);

            if ($userId === $sharedBy) {
                continue;
            }

            if (EventShare::where('event_id', $eventId)->where('user_id', $userId)->exists()) {
                continue;
            }

            EventShare::create([
                'event_id' => $eventId,
                'user_id' => $userId,
                'shared_by' => $sharedBy,
            ]);
            $created++;
        }
    }
};