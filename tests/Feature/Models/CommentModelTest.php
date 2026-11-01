<?php

namespace Tests\Feature\Models;

use App\Models\Comment;
use App\Models\Feed;
use App\Models\User;
use App\Services\ContentSanitizerService;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use Tests\TestCase;

class CommentModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        $this->resetSanitizerCache();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
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

    public function test_get_type_label_attribute_returns_comment_when_no_parent_id(): void
    {
        $comment = new Comment();
        $comment->parent_id = null;

        $this->assertSame('comment', $comment->type_label);
    }

    public function test_get_type_label_attribute_returns_reply_when_parent_id_set(): void
    {
        $comment = new Comment();
        $comment->parent_id = 1;

        $this->assertSame('reply', $comment->type_label);
    }

    public function test_content_set_mutator_passes_null_through_unchanged(): void
    {
        $comment = new Comment();
        $comment->content = null;

        $this->assertNull($comment->getAttributes()['content']);
    }

    public function test_content_set_mutator_passes_empty_string_through_unchanged(): void
    {
        $comment = new Comment();
        $comment->content = '';

        $this->assertSame('', $comment->getAttributes()['content']);
    }

    public function test_content_set_mutator_strips_control_characters(): void
    {
        $comment = new Comment();
        $comment->content = "hello\x00world\x07end";

        $this->assertSame('helloworldend', $comment->getAttributes()['content']);
    }

    public function test_content_set_mutator_strips_zero_width_artifacts(): void
    {
        $zwsp = "\xE2\x80\x8B";
        $comment = new Comment();
        $comment->content = "a{$zwsp}b";

        $this->assertSame('ab', $comment->getAttributes()['content']);
    }

    public function test_content_set_mutator_normalizes_excess_blank_lines(): void
    {
        $comment = new Comment();
        $comment->content = "line1\n\n\n\nline2";

        $this->assertSame("line1\n\nline2", $comment->getAttributes()['content']);
    }

    public function test_content_set_mutator_truncates_to_max_bytes(): void
    {
        $comment = new Comment();
        $comment->content = str_repeat('a', 70000);

        $this->assertLessThanOrEqual(65000, strlen((string) $comment->getAttributes()['content']));
    }

    public function test_content_set_mutator_preserves_pre_block_blank_lines(): void
    {
        $comment = new Comment();
        $payload = "<pre>\n\n\n\n</pre>";
        $comment->content = $payload;

        $this->assertSame($payload, $comment->getAttributes()['content']);
    }

    public function test_children_relation_eager_loads_user(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $feed = Feed::factory()->create(['user_id' => $user->id]);
        $parent = Comment::factory()->create([
            'feed_id' => $feed->id,
            'user_id' => $user->id,
            'parent_id' => null,
        ]);
        Comment::factory()->create([
            'feed_id' => $feed->id,
            'user_id' => $user->id,
            'parent_id' => $parent->id,
        ]);

        $loaded = Comment::find($parent->id);
        $child = $loaded->children->first();

        $this->assertNotNull($child);
        $this->assertTrue($child->relationLoaded('user'));
    }

    public function test_parent_relation_returns_parent_comment(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $feed = Feed::factory()->create(['user_id' => $user->id]);
        $parent = Comment::factory()->create([
            'feed_id' => $feed->id,
            'user_id' => $user->id,
            'parent_id' => null,
        ]);
        $reply = Comment::factory()->create([
            'feed_id' => $feed->id,
            'user_id' => $user->id,
            'parent_id' => $parent->id,
        ]);

        $this->assertSame($parent->id, $reply->fresh()->parent->id);
    }
}