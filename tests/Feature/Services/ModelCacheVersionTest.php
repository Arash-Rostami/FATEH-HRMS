<?php

namespace Tests\Feature\Services;

use App\Models\FAQ;
use App\Services\Cache\ModelCacheVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ModelCacheVersionTest extends TestCase
{
    private const TEST_MODEL = 'App\\Models\\__ModelCacheVersionTestFixture';

    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        Cache::forget('model_cache_version:' . self::TEST_MODEL);
        Cache::forget('model_cache_version:' . FAQ::class);
        foreach (['mcv:rg:test', 'mcv:rg:a', 'mcv:rg:b'] as $k) {
            Cache::forget($k);
        }
    }

    protected function tearDown(): void
    {
        Cache::forget('model_cache_version:' . self::TEST_MODEL);
        Cache::forget('model_cache_version:' . FAQ::class);
        foreach (['mcv:rg:test', 'mcv:rg:a', 'mcv:rg:b'] as $k) {
            Cache::forget($k);
        }
        parent::tearDown();
    }

    private function useMysql(): void
    {
        $env = base_path('.env');
        if (is_file($env)) {
            $vals = [];
            foreach (explode("\n", (string) file_get_contents($env)) as $line) {
                if (preg_match('/^\s*(DB_HOST|DB_PORT|DB_DATABASE|DB_USERNAME|DB_PASSWORD)\s*=\s*(.*)$/', $line, $m)) {
                    $vals[$m[1]] = trim(preg_replace('/\s+#.*$/', '', trim($m[2])), "\"' \t");
                }
            }
            $map = ['DB_HOST' => 'host', 'DB_PORT' => 'port', 'DB_DATABASE' => 'database', 'DB_USERNAME' => 'username', 'DB_PASSWORD' => 'password'];
            foreach ($map as $envKey => $cfgKey) {
                if (isset($vals[$envKey])) {
                    config(['database.connections.mysql.' . $cfgKey => $vals[$envKey]]);
                }
            }
        }
        DB::purge('mysql');
        config(['database.default' => 'mysql']);
    }

    public function test_version_defaults_to_zero_when_never_bumped(): void
    {
        $this->assertSame('0', ModelCacheVersion::version(self::TEST_MODEL));
    }

    public function test_bump_changes_the_version(): void
    {
        $before = ModelCacheVersion::version(self::TEST_MODEL);

        ModelCacheVersion::bump(self::TEST_MODEL);

        $this->assertNotSame($before, ModelCacheVersion::version(self::TEST_MODEL));
    }

    public function test_bump_persists_forever_and_is_readable_across_calls(): void
    {
        ModelCacheVersion::bump(self::TEST_MODEL);
        $first = ModelCacheVersion::version(self::TEST_MODEL);
        $second = ModelCacheVersion::version(self::TEST_MODEL);

        $this->assertSame($first, $second, 'reading the version twice without an intervening bump must return the same token');
    }

    public function test_bump_never_produces_scientific_notation_or_a_pure_float_string(): void
    {
        ModelCacheVersion::bump(self::TEST_MODEL);
        $version = ModelCacheVersion::version(self::TEST_MODEL);

        $this->assertDoesNotMatchRegularExpression('/[eE][+-]\d/', $version, 'a version token in scientific notation risks precision loss and is not a stable cache-key fragment');
    }

    public function test_key_embeds_the_current_version_and_changes_after_a_bump(): void
    {
        $keyBefore = ModelCacheVersion::key(self::TEST_MODEL, 'my_suffix');
        $this->assertStringContainsString(self::TEST_MODEL, $keyBefore);
        $this->assertStringContainsString('my_suffix', $keyBefore);

        ModelCacheVersion::bump(self::TEST_MODEL);
        $keyAfter = ModelCacheVersion::key(self::TEST_MODEL, 'my_suffix');

        $this->assertNotSame($keyBefore, $keyAfter);
    }

    public function test_different_suffixes_produce_different_keys_at_the_same_version(): void
    {
        $this->assertNotSame(
            ModelCacheVersion::key(self::TEST_MODEL, 'a'),
            ModelCacheVersion::key(self::TEST_MODEL, 'b'),
        );
    }

    public function test_a_cache_read_built_through_key_is_naturally_invalidated_by_a_bump(): void
    {
        $read = fn() => Cache::remember(ModelCacheVersion::key(self::TEST_MODEL, 'demo'), 60, fn() => 'fresh-' . uniqid());

        $first = $read();
        $this->assertSame($first, $read(), 'without a bump, the same key must still hit the same cached value');

        ModelCacheVersion::bump(self::TEST_MODEL);

        $this->assertNotSame($first, $read(), 'after a bump, the key changes, so this is a guaranteed cache miss producing a fresh value');
    }

    public function test_the_global_listener_bumps_the_version_on_real_model_save_and_delete(): void
    {
        $before = ModelCacheVersion::version(FAQ::class);

        $faq = FAQ::factory()->create();
        $afterCreate = ModelCacheVersion::version(FAQ::class);
        $this->assertNotSame($before, $afterCreate, 'saving a FAQ row (a model with zero hand-wired cache hooks) must still auto-bump its version');

        $faq->update(['question' => 'updated by ModelCacheVersionTest']);
        $afterUpdate = ModelCacheVersion::version(FAQ::class);
        $this->assertNotSame($afterCreate, $afterUpdate);

        $faq->delete();
        $afterDelete = ModelCacheVersion::version(FAQ::class);
        $this->assertNotSame($afterUpdate, $afterDelete);
    }

    public function test_remember_global_caches_under_the_caller_key_and_skips_the_callback_on_hit(): void
    {
        $calls = 0;
        $cb = function () use (&$calls) {
            $calls++;
            return 'value-' . $calls;
        };

        $first = ModelCacheVersion::rememberGlobal('mcv:rg:test', $cb);
        $second = ModelCacheVersion::rememberGlobal('mcv:rg:test', $cb);

        $this->assertSame('value-1', $first);
        $this->assertSame('value-1', $second, 'a second rememberGlobal under the same key must hit the cache, not re-invoke the callback');
        $this->assertSame(1, $calls);
    }

    public function test_remember_global_isolates_distinct_keys(): void
    {
        $a = ModelCacheVersion::rememberGlobal('mcv:rg:a', fn () => 'A');
        $b = ModelCacheVersion::rememberGlobal('mcv:rg:b', fn () => 'B');

        $this->assertSame('A', $a);
        $this->assertSame('B', $b);
    }
}
