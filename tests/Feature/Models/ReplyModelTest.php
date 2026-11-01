<?php

namespace Tests\Feature\Models;

use App\Models\Reply;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReplyModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
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

    public function test_files_cast_round_trips_array_through_db(): void
    {
        $reply = $this->createReply(['files' => ['a', 'b']]);

        $this->assertSame(['a', 'b'], $reply->fresh()->files);
    }

    public function test_files_cast_returns_null_through_db_when_files_null(): void
    {
        $reply = $this->createReply(['files' => null]);

        $this->assertNull($reply->fresh()->files);
    }

    public function test_files_cast_returns_empty_array_through_db(): void
    {
        $reply = $this->createReply(['files' => []]);

        $this->assertSame([], $reply->fresh()->files);
    }

    public function test_files_cast_returns_null_in_memory_when_unset(): void
    {
        $reply = new Reply();

        $this->assertNull($reply->files);
    }

    public function test_files_cast_round_trips_array_in_memory(): void
    {
        $reply = new Reply();
        $reply->files = ['x', 'y'];

        $this->assertSame(['x', 'y'], $reply->files);
    }

    public function test_resolve_public_asset_url_returns_empty_string_for_empty_path(): void
    {
        $this->assertSame('', Reply::resolvePublicAssetUrl(''));
        $this->assertSame('', Reply::resolvePublicAssetUrl('   '));
    }

    public function test_resolve_public_asset_url_returns_empty_string_for_null_path(): void
    {
        $this->assertSame('', Reply::resolvePublicAssetUrl(null));
    }

    public function test_resolve_public_asset_url_returns_absolute_urls_unchanged(): void
    {
        $this->assertSame('https://cdn.test/x.png', Reply::resolvePublicAssetUrl('https://cdn.test/x.png'));
        $this->assertSame('http://cdn.test/x.png', Reply::resolvePublicAssetUrl('http://cdn.test/x.png'));
        $this->assertSame('//cdn.test/x.png', Reply::resolvePublicAssetUrl('//cdn.test/x.png'));
    }

    public function test_resolve_public_asset_url_returns_public_disk_url_for_existing_path(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('reply-asset.txt', 'x');

        $this->assertSame(
            Storage::disk('public')->url('reply-asset.txt'),
            Reply::resolvePublicAssetUrl('reply-asset.txt')
        );
    }

    public function test_resolve_public_asset_url_falls_back_to_asset_for_nonexistent_path(): void
    {
        $path = 'nonexistent/reply-asset-' . uniqid() . '.png';

        $this->assertSame(asset($path), Reply::resolvePublicAssetUrl($path));
    }

    public function test_deleting_removes_files_from_storage(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('replies/x.pdf', 'content');
        $reply = $this->createReply(['files' => [['path' => 'replies/x.pdf', 'name' => 'x.pdf', 'mime' => 'application/pdf', 'size' => 10]]]);

        $reply->delete();

        Storage::disk('public')->assertMissing('replies/x.pdf');
    }

    public function test_deleting_with_no_files_does_not_error(): void
    {
        Storage::fake('public');
        $reply = $this->createReply(['files' => null]);

        $reply->delete();

        $this->assertDatabaseMissing('replies', ['id' => $reply->id]);
    }

    public function test_type_is_fillable_and_casts_to_the_backed_enum(): void
    {
        $reply = $this->createReply(['type' => \App\Enums\TaskActivityType::StatusChange]);

        $this->assertSame(\App\Enums\TaskActivityType::StatusChange, $reply->fresh()->type);
    }

    public function test_type_column_defaults_to_comment_when_omitted_from_an_insert(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $ticket = Ticket::factory()->create(['requester_id' => $user->id, 'extra' => ['target_department' => 'PS']]);

        $reply = Reply::create([
            'repliable_type' => Ticket::class,
            'repliable_id' => $ticket->id,
            'user_id' => $user->id,
            'body' => 'no type specified',
        ]);

        $this->assertSame(\App\Enums\TaskActivityType::Comment, $reply->fresh()->type, 'the replies.type column default must back-fill to comment for a bare insert');
    }

    public function test_add_reply_defaults_to_comment_and_stores_an_explicit_type(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $ticket = Ticket::factory()->create(['requester_id' => $user->id, 'extra' => ['target_department' => 'PS']]);
        $task = \App\Models\Task::factory()->create();

        $this->assertSame(\App\Enums\TaskActivityType::Comment, $ticket->addReply($user, 'plain')->fresh()->type);
        $this->assertSame(\App\Enums\TaskActivityType::Comment, $task->addReply($user, 'plain')->fresh()->type);
        $this->assertSame(\App\Enums\TaskActivityType::Assignment, $ticket->addReply($user, 'sys', [], \App\Enums\TaskActivityType::Assignment)->fresh()->type);
    }

    public function test_payload_is_fillable_and_casts_to_array(): void
    {
        $reply = $this->createReply(['payload' => ['from' => 'todo', 'to' => 'done']]);

        $this->assertEquals(['from' => 'todo', 'to' => 'done'], $reply->fresh()->payload);
    }

    public function test_reactions_cast_returns_array_when_set_directly(): void
    {
        $reply = $this->createReply([]);
        $reply->forceFill(['reactions' => [['user_id' => 1, 'emoji' => '👍']]])->save();

        $this->assertEquals([['user_id' => 1, 'emoji' => '👍']], $reply->fresh()->reactions);
    }

    public function test_reactions_is_not_mass_assignable(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $ticket = Ticket::factory()->create(['requester_id' => $user->id, 'extra' => ['target_department' => 'PS']]);

        $reply = Reply::create([
            'repliable_type' => Ticket::class,
            'repliable_id' => $ticket->id,
            'user_id' => $user->id,
            'body' => 'hello',
            'reactions' => [['user_id' => 1, 'emoji' => '👍']],
        ]);

        $this->assertNull($reply->fresh()->reactions, 'reactions must be server-written only via ToggleReplyReactionAction, never mass-assignable');
    }

    public function test_replies_order_by_created_at_then_id_when_timestamps_collide(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $ticket = Ticket::factory()->create(['requester_id' => $user->id, 'extra' => ['target_department' => 'PS']]);
        $sameMoment = now();

        $first = $this->createReply(['repliable_id' => $ticket->id, 'created_at' => $sameMoment, 'body' => 'first']);
        $second = $this->createReply(['repliable_id' => $ticket->id, 'created_at' => $sameMoment, 'body' => 'second']);
        $third = $this->createReply(['repliable_id' => $ticket->id, 'created_at' => $sameMoment, 'body' => 'third']);

        $ordered = $ticket->replies()->get()->pluck('id')->all();

        $this->assertSame([$first->id, $second->id, $third->id], $ordered, 'when created_at ties, the id tiebreaker must preserve insertion order for Ticket too');
    }

    private function createReply(array $overrides): Reply
    {
        $user = User::factory()->create(['status' => 'active']);
        $ticket = Ticket::factory()->create([
            'requester_id' => $user->id,
            'extra' => ['target_department' => 'PS'],
        ]);

        return Reply::factory()->create(array_merge([
            'repliable_type' => Ticket::class,
            'repliable_id' => $ticket->id,
            'user_id' => $user->id,
        ], $overrides));
    }
}