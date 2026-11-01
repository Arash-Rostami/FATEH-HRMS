<?php

namespace Tests\Feature\Config;

use Tests\TestCase;

class ReleasesConfigTest extends TestCase
{
    private const VALID_COLOR_ROLES = ['primary', 'secondary', 'tertiary', 'error'];

    public function test_every_release_has_required_keys_and_non_empty_values(): void
    {
        foreach (config('releases', []) as $release) {
            foreach (['id', 'version', 'badge', 'period', 'icon', 'color', 'theme_icon', 'theme_title'] as $key) {
                $this->assertNotEmpty($release[$key] ?? null, "release '{$release['id']}' is missing '{$key}'");
            }

            $this->assertNotEmpty($release['items'] ?? null, "release '{$release['id']}' has no items");
        }
    }

    public function test_release_ids_and_versions_are_unique(): void
    {
        $releases = config('releases', []);

        $ids = array_column($releases, 'id');
        $versions = array_column($releases, 'version');

        $this->assertCount(count($ids), array_unique($ids));
        $this->assertCount(count($versions), array_unique($versions));
    }

    public function test_every_item_has_non_empty_title_and_desc(): void
    {
        foreach (config('releases', []) as $release) {
            foreach ($release['items'] as $item) {
                $this->assertNotEmpty($item['title'] ?? null, "an item in release '{$release['id']}' is missing 'title'");
                $this->assertNotEmpty($item['desc'] ?? null, "an item in release '{$release['id']}' is missing 'desc'");
            }
        }
    }

    public function test_color_is_a_valid_material_color_role(): void
    {
        foreach (config('releases', []) as $release) {
            $this->assertContains(
                $release['color'],
                self::VALID_COLOR_ROLES,
                "release '{$release['id']}' has an unrecognized color role"
            );
        }
    }

    public function test_exactly_one_release_carries_the_newest_badge_and_it_is_the_last_entry(): void
    {
        $releases = config('releases', []);

        $newestIndexes = array_keys(array_filter(
            $releases,
            fn(array $release): bool => $release['badge'] === 'جدیدترین'
        ));

        $this->assertCount(1, $newestIndexes, "exactly one release must carry the 'جدیدترین' badge");
        $this->assertSame(array_key_last($releases), $newestIndexes[0], "the 'جدیدترین' badge must be on the last (newest) release");
    }
}
