<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\ProfileDetail;
use App\Services\ProfileDetailCatalog;
use Illuminate\Database\Seeder;

class ProfileDetailSeeder extends Seeder
{
    public function run(): void
    {
        if (ProfileDetail::count() > 0) {
            return;
        }

        $profileIds = Profile::pluck('id');

        if ($profileIds->isEmpty()) {
            return;
        }

        $faker = fake('fa_IR');
        $definitions = ProfileDetailCatalog::definitions();

        foreach ($profileIds as $profileId) {
            $usedKeys = [];
            $count = $faker->numberBetween(3, 8);

            for ($i = 0; $i < $count; $i++) {
                $key = $faker->randomElement(array_keys($definitions));
                if (in_array($key, $usedKeys, true)) {
                    continue;
                }
                $usedKeys[] = $key;

                $def = $definitions[$key];
                $value = $this->resolveValue($def, $faker);

                ProfileDetail::create([
                    'profile_id' => $profileId,
                    'section' => $def['section'],
                    'key' => $key,
                    'value' => $value,
                ]);
            }
        }
    }

    private function resolveValue(array $def, $faker): ?string
    {
        $type = $def['type'] ?? 'text';

        if ($type === 'select' && isset($def['options']) && is_array($def['options'])) {
            return (string) $faker->randomElement(array_keys($def['options']));
        }

        if ($type === 'date') {
            return $faker->date();
        }

        return $faker->sentence();
    }
};