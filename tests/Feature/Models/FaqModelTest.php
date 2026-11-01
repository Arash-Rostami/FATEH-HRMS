<?php

namespace Tests\Feature\Models;

use App\Models\FAQ;
use App\Services\ContentSanitizerService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use Tests\TestCase;

class FaqModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        $this->resetSanitizerCache();
        DB::beginTransaction();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        Cache::flush();
        $this->resetSanitizerCache();
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

    private function resetSanitizerCache(): void
    {
        $ref = new ReflectionClass(ContentSanitizerService::class);
        $prop = $ref->getProperty('dbIsUtf8mb4');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    public function test_answer_set_mutator_passes_null_through_unchanged(): void
    {
        $faq = new FAQ();
        $faq->answer = null;

        $this->assertNull($faq->getAttributes()['answer']);
    }

    public function test_answer_set_mutator_passes_empty_string_through_unchanged(): void
    {
        $faq = new FAQ();
        $faq->answer = '';

        $this->assertSame('', $faq->getAttributes()['answer']);
    }

    public function test_answer_set_mutator_strips_control_characters(): void
    {
        $faq = new FAQ();
        $faq->answer = "hello\x00world\x07end";

        $this->assertSame('helloworldend', $faq->getAttributes()['answer']);
    }

    public function test_answer_set_mutator_strips_zero_width_artifacts(): void
    {
        $zwsp = "\xE2\x80\x8B";
        $faq = new FAQ();
        $faq->answer = "a{$zwsp}b";

        $this->assertSame('ab', $faq->getAttributes()['answer']);
    }

    public function test_answer_set_mutator_normalizes_excess_blank_lines(): void
    {
        $faq = new FAQ();
        $faq->answer = "line1\n\n\n\nline2";

        $this->assertSame("line1\n\nline2", $faq->getAttributes()['answer']);
    }

    public function test_answer_set_mutator_truncates_to_max_bytes(): void
    {
        $faq = new FAQ();
        $faq->answer = str_repeat('a', 70000);

        $this->assertLessThanOrEqual(65000, strlen((string) $faq->getAttributes()['answer']));
    }

    public function test_answer_set_mutator_preserves_pre_block_blank_lines(): void
    {
        $faq = new FAQ();
        $payload = "<pre>\n\n\n\n</pre>";
        $faq->answer = $payload;

        $this->assertSame($payload, $faq->getAttributes()['answer']);
    }

    public function test_question_set_mutator_passes_null_through_unchanged(): void
    {
        $faq = new FAQ();
        $faq->question = null;

        $this->assertNull($faq->getAttributes()['question']);
    }

    public function test_question_set_mutator_passes_empty_string_through_unchanged(): void
    {
        $faq = new FAQ();
        $faq->question = '';

        $this->assertSame('', $faq->getAttributes()['question']);
    }

    public function test_question_set_mutator_strips_control_characters(): void
    {
        $faq = new FAQ();
        $faq->question = "hello\x00world\x07end";

        $this->assertSame('helloworldend', $faq->getAttributes()['question']);
    }

    public function test_question_set_mutator_strips_zero_width_artifacts(): void
    {
        $zwsp = "\xE2\x80\x8B";
        $faq = new FAQ();
        $faq->question = "a{$zwsp}b";

        $this->assertSame('ab', $faq->getAttributes()['question']);
    }

    public function test_question_set_mutator_normalizes_excess_blank_lines(): void
    {
        $faq = new FAQ();
        $faq->question = "line1\n\n\n\nline2";

        $this->assertSame("line1\n\nline2", $faq->getAttributes()['question']);
    }

    public function test_question_set_mutator_truncates_to_max_bytes(): void
    {
        $faq = new FAQ();
        $faq->question = str_repeat('a', 70000);

        $this->assertLessThanOrEqual(65000, strlen((string) $faq->getAttributes()['question']));
    }

    public function test_question_set_mutator_preserves_pre_block_blank_lines(): void
    {
        $faq = new FAQ();
        $payload = "<pre>\n\n\n\n</pre>";
        $faq->question = $payload;

        $this->assertSame($payload, $faq->getAttributes()['question']);
    }

    public function test_answer_and_question_round_trip_through_db(): void
    {
        $faq = FAQ::factory()->create([
            'question' => "  <p>what is this?</p>\x00  ",
            'answer' => "<p>an answer</p>\n\n\n\n<p>tail</p>",
        ]);

        $fresh = $faq->fresh();
        $this->assertSame('  <p>what is this?</p>  ', $fresh->question);
        $this->assertSame("<p>an answer</p>\n\n<p>tail</p>", $fresh->answer);
    }

    public function test_cached_categories_and_category_filter_auto_invalidate_on_delete(): void
    {
        $cat = 'ZZ-CAT-' . uniqid();
        $faq = FAQ::factory()->create(['category' => $cat]);

        $this->assertContains($cat, FAQ::cachedCategories());
        $this->assertArrayHasKey($cat, FAQ::cachedCategoryFilter());

        $faq->delete();
        $this->assertNotContains($cat, FAQ::cachedCategories(), 'deleting the last FAQ in a category must bump the version so cachedCategories recomputes without a manual forget');
        $this->assertArrayNotHasKey($cat, FAQ::cachedCategoryFilter());
    }
}