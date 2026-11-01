<?php

namespace Tests\Feature\Models;

use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MessageModelTest extends TestCase
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

    public function test_is_read_returns_true_when_read_at_is_set(): void
    {
        $message = new Message();
        $message->read_at = now();

        $this->assertTrue($message->isRead());
    }

    public function test_is_read_returns_false_when_read_at_is_null(): void
    {
        $message = new Message();

        $this->assertFalse($message->isRead());
    }

    public function test_mark_as_read_sets_read_at_only_when_unread(): void
    {
        $sender = User::factory()->create(['status' => 'active']);
        $recipient = User::factory()->create(['status' => 'active']);
        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'read_at' => null,
        ]);

        $message->markAsRead();
        $this->assertNotNull($message->fresh()->read_at);
    }

    public function test_mark_as_read_does_not_overwrite_existing_read_at(): void
    {
        $sender = User::factory()->create(['status' => 'active']);
        $recipient = User::factory()->create(['status' => 'active']);
        $original = now()->subHour()->startOfSecond();
        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'read_at' => $original,
        ]);

        $message->markAsRead();
        $this->assertSame($original->format('Y-m-d H:i:s'), $message->fresh()->read_at->format('Y-m-d H:i:s'));
    }

    public function test_has_unread_for_returns_true_when_user_has_unread_messages(): void
    {
        $sender = User::factory()->create(['status' => 'active']);
        $recipient = User::factory()->create(['status' => 'active']);
        Message::factory()->create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'read_at' => null,
        ]);

        $this->assertTrue(Message::hasUnreadFor($recipient->id));
    }

    public function test_has_unread_for_returns_false_when_all_messages_read(): void
    {
        $sender = User::factory()->create(['status' => 'active']);
        $recipient = User::factory()->create(['status' => 'active']);
        Message::factory()->create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'read_at' => now(),
        ]);

        $this->assertFalse(Message::hasUnreadFor($recipient->id));
    }

    public function test_total_unread_for_returns_count_of_unread_for_recipient(): void
    {
        $sender = User::factory()->create(['status' => 'active']);
        $recipient = User::factory()->create(['status' => 'active']);
        $baseline = Message::totalUnreadFor($recipient->id);

        Message::factory()->create(['sender_id' => $sender->id, 'recipient_id' => $recipient->id, 'read_at' => null]);
        Message::factory()->create(['sender_id' => $sender->id, 'recipient_id' => $recipient->id, 'read_at' => null]);
        Message::factory()->create(['sender_id' => $sender->id, 'recipient_id' => $recipient->id, 'read_at' => now()]);

        $this->assertSame($baseline + 2, Message::totalUnreadFor($recipient->id));
    }

    public function test_unread_counts_from_returns_per_recipient_unread_counts(): void
    {
        $sender = User::factory()->create(['status' => 'active']);
        $r1 = User::factory()->create(['status' => 'active']);
        $r2 = User::factory()->create(['status' => 'active']);
        Message::factory()->create(['sender_id' => $sender->id, 'recipient_id' => $r1->id, 'read_at' => null]);
        Message::factory()->create(['sender_id' => $sender->id, 'recipient_id' => $r1->id, 'read_at' => null]);
        Message::factory()->create(['sender_id' => $sender->id, 'recipient_id' => $r2->id, 'read_at' => null]);

        $counts = Message::unreadCountsFrom($sender->id);

        $this->assertSame(2, $counts[$r1->id]);
        $this->assertSame(1, $counts[$r2->id]);
    }

    public function test_attachments_cast_round_trips_array(): void
    {
        $sender = User::factory()->create(['status' => 'active']);
        $recipient = User::factory()->create(['status' => 'active']);
        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'attachments' => [['path' => 'msg/x.pdf', 'name' => 'x.pdf']],
        ]);

        $this->assertEquals([['path' => 'msg/x.pdf', 'name' => 'x.pdf']], $message->fresh()->attachments);
    }

    public function test_attachment_urls_maps_url_key_onto_each_attachment(): void
    {
        $message = new Message();
        $message->attachments = [['path' => 'msg/x.pdf', 'name' => 'x.pdf']];

        $urls = $message->attachmentUrls();

        $this->assertArrayHasKey('url', $urls[0]);
        $this->assertStringContainsString('msg/x.pdf', $urls[0]['url']);
    }

    public function test_attachment_urls_returns_empty_array_for_null_attachments(): void
    {
        $message = new Message();

        $this->assertSame([], $message->attachmentUrls());
    }

    public function test_body_set_mutator_strips_control_characters(): void
    {
        $sender = User::factory()->create(['status' => 'active']);
        $recipient = User::factory()->create(['status' => 'active']);
        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'body' => "hello\x00world",
        ]);

        $this->assertSame('helloworld', $message->fresh()->body);
    }

    public function test_is_edited_cast_round_trips_boolean(): void
    {
        $sender = User::factory()->create(['status' => 'active']);
        $recipient = User::factory()->create(['status' => 'active']);
        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'is_edited' => true,
        ]);

        $this->assertTrue($message->fresh()->is_edited);
    }

    public function test_force_deleting_removes_attachment_files_from_storage(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('msg/x.pdf', 'content');
        $sender = User::factory()->create(['status' => 'active']);
        $recipient = User::factory()->create(['status' => 'active']);
        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'attachments' => [['path' => 'msg/x.pdf', 'name' => 'x.pdf']],
        ]);

        $message->forceDelete();

        Storage::disk('public')->assertMissing('msg/x.pdf');
    }

    public function test_soft_deleting_does_not_remove_attachment_files_from_storage(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('msg/y.pdf', 'content');
        $sender = User::factory()->create(['status' => 'active']);
        $recipient = User::factory()->create(['status' => 'active']);
        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'attachments' => [['path' => 'msg/y.pdf', 'name' => 'y.pdf']],
        ]);

        $message->delete();

        Storage::disk('public')->assertExists('msg/y.pdf');
    }

    public function test_force_deleting_with_no_attachments_does_not_error(): void
    {
        Storage::fake('public');
        $sender = User::factory()->create(['status' => 'active']);
        $recipient = User::factory()->create(['status' => 'active']);
        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'attachments' => null,
        ]);

        $message->forceDelete();

        $this->assertDatabaseMissing('messages', ['id' => $message->id]);
    }
}