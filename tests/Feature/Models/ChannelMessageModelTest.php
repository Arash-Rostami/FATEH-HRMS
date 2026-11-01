<?php

namespace Tests\Feature\Models;

use App\Models\Channel;
use App\Models\ChannelMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChannelMessageModelTest extends TestCase
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

    public function test_attachments_cast_round_trips_array(): void
    {
        $channel = Channel::factory()->create();
        $sender = User::factory()->create(['status' => 'active']);
        $msg = ChannelMessage::factory()->create([
            'channel_id' => $channel->id,
            'sender_id' => $sender->id,
            'body' => 'test',
            'attachments' => [['path' => 'cm/x.pdf', 'name' => 'x.pdf']],
        ]);

        $this->assertEquals([['path' => 'cm/x.pdf', 'name' => 'x.pdf']], $msg->fresh()->attachments);
    }

    public function test_is_edited_cast_round_trips_boolean(): void
    {
        $channel = Channel::factory()->create();
        $sender = User::factory()->create(['status' => 'active']);
        $msg = ChannelMessage::factory()->create([
            'channel_id' => $channel->id,
            'sender_id' => $sender->id,
            'body' => 'test',
            'is_edited' => true,
        ]);

        $this->assertTrue($msg->fresh()->is_edited);
    }

    public function test_attachment_urls_maps_url_key_onto_each_attachment(): void
    {
        $msg = new ChannelMessage();
        $msg->attachments = [['path' => 'cm/x.pdf', 'name' => 'x.pdf']];

        $urls = $msg->attachmentUrls();

        $this->assertArrayHasKey('url', $urls[0]);
        $this->assertStringContainsString('cm/x.pdf', $urls[0]['url']);
    }

    public function test_attachment_urls_returns_empty_array_for_null_attachments(): void
    {
        $msg = new ChannelMessage();

        $this->assertSame([], $msg->attachmentUrls());
    }

    public function test_body_set_mutator_strips_control_characters(): void
    {
        $channel = Channel::factory()->create();
        $sender = User::factory()->create(['status' => 'active']);
        $msg = ChannelMessage::factory()->create([
            'channel_id' => $channel->id,
            'sender_id' => $sender->id,
            'body' => "hello\x00world",
        ]);

        $this->assertSame('helloworld', $msg->fresh()->body);
    }

    public function test_last_id_for_channel_excludes_soft_deleted_messages(): void
    {
        $channel = Channel::factory()->create();
        $sender = User::factory()->create(['status' => 'active']);
        ChannelMessage::factory()->create(['channel_id' => $channel->id, 'sender_id' => $sender->id, 'body' => 'test']);
        $msg2 = ChannelMessage::factory()->create(['channel_id' => $channel->id, 'sender_id' => $sender->id, 'body' => 'test']);
        ChannelMessage::factory()->create(['channel_id' => $channel->id, 'sender_id' => $sender->id, 'body' => 'test', 'deleted_at' => now()]);

        $this->assertSame($msg2->id, ChannelMessage::lastIdForChannel($channel->id));
    }

    public function test_last_id_for_channel_returns_null_when_no_messages(): void
    {
        $channel = Channel::factory()->create();

        $this->assertNull(ChannelMessage::lastIdForChannel($channel->id));
    }

    public function test_total_unread_for_returns_count_of_unread_for_user(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $member = User::factory()->create(['status' => 'active']);
        $channel = Channel::factory()->create(['owner_id' => $owner->id]);
        $channel->memberUsers()->attach($member->id, [
            'joined_at' => now(),
            'entered_at' => now(),
            'last_read_message_id' => null,
        ]);

        ChannelMessage::factory()->create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'test']);
        ChannelMessage::factory()->create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'test']);

        $baseline = ChannelMessage::totalUnreadFor($member->id);

        $this->assertSame(2, $baseline);
    }

    public function test_force_deleting_removes_attachment_directory(): void
    {
        Storage::fake('public');
        $channel = Channel::factory()->create();
        $sender = User::factory()->create(['status' => 'active']);
        $msg = ChannelMessage::factory()->create([
            'channel_id' => $channel->id,
            'sender_id' => $sender->id,
            'body' => 'test',
        ]);
        Storage::disk('public')->put("channel_messages/{$channel->id}/{$msg->id}/x.jpg", 'content');

        $msg->forceDelete();

        Storage::disk('public')->assertMissing("channel_messages/{$channel->id}/{$msg->id}/x.jpg");
    }

    public function test_soft_deleting_does_not_remove_attachment_directory(): void
    {
        Storage::fake('public');
        $channel = Channel::factory()->create();
        $sender = User::factory()->create(['status' => 'active']);
        $msg = ChannelMessage::factory()->create([
            'channel_id' => $channel->id,
            'sender_id' => $sender->id,
            'body' => 'test',
        ]);
        Storage::disk('public')->put("channel_messages/{$channel->id}/{$msg->id}/x.jpg", 'content');

        $msg->delete();

        Storage::disk('public')->assertExists("channel_messages/{$channel->id}/{$msg->id}/x.jpg");
    }
}