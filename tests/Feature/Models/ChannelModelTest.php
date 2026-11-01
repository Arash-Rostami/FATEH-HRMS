<?php

namespace Tests\Feature\Models;

use App\Enums\ChannelType;
use App\Models\Channel;
use App\Models\ChannelMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChannelModelTest extends TestCase
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

    public function test_type_cast_round_trips_enum_through_db(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $channel = Channel::factory()->create([
            'owner_id' => $owner->id,
            'type' => ChannelType::Private,
        ]);

        $this->assertSame(ChannelType::Private, $channel->fresh()->type);
    }

    public function test_created_channel_auto_attaches_owner_as_member(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $channel = Channel::factory()->create(['owner_id' => $owner->id]);

        $this->assertTrue($channel->memberUsers()->where('users.id', $owner->id)->exists());
    }

    public function test_created_channel_without_owner_does_not_attach_member(): void
    {
        $channel = Channel::factory()->create(['owner_id' => null]);

        $this->assertSame(0, $channel->members()->count());
    }

    public function test_invited_user_ids_returns_users_with_null_entered_at(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $invited = User::factory()->create(['status' => 'active']);
        $channel = Channel::factory()->create(['owner_id' => $owner->id]);
        $channel->memberUsers()->attach($invited->id, [
            'joined_at' => now(),
            'entered_at' => null,
            'last_read_message_id' => null,
        ]);

        $ids = Channel::invitedUserIds($channel->id);

        $this->assertContains($invited->id, $ids);
        $this->assertNotContains($owner->id, $ids);
    }

    public function test_unread_counts_for_returns_per_user_unread_message_count(): void
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

        $counts = Channel::unreadCountsFor($channel->id);

        $this->assertSame(2, $counts[$member->id] ?? 0);
    }

    public function test_unread_counts_for_excludes_entered_null_members(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $invited = User::factory()->create(['status' => 'active']);
        $channel = Channel::factory()->create(['owner_id' => $owner->id]);
        $channel->memberUsers()->attach($invited->id, [
            'joined_at' => now(),
            'entered_at' => null,
            'last_read_message_id' => null,
        ]);

        ChannelMessage::factory()->create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'test']);

        $counts = Channel::unreadCountsFor($channel->id);

        $this->assertArrayNotHasKey($invited->id, $counts);
    }

    public function test_deleted_at_is_cast_to_datetime(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $channel = Channel::factory()->create([
            'owner_id' => $owner->id,
            'deleted_at' => now(),
        ]);

        $this->assertInstanceOf(\Carbon\CarbonInterface::class, Channel::withTrashed()->find($channel->id)->deleted_at);
    }

    public function test_force_deleting_removes_channel_messages_directory(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create(['status' => 'active']);
        $channel = Channel::factory()->create(['owner_id' => $owner->id]);
        Storage::disk('public')->put("channel_messages/{$channel->id}/somefile.jpg", 'content');

        $channel->forceDelete();

        Storage::disk('public')->assertMissing("channel_messages/{$channel->id}/somefile.jpg");
    }

    public function test_soft_deleting_does_not_remove_channel_messages_directory(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create(['status' => 'active']);
        $channel = Channel::factory()->create(['owner_id' => $owner->id]);
        Storage::disk('public')->put("channel_messages/{$channel->id}/somefile.jpg", 'content');

        $channel->delete();

        Storage::disk('public')->assertExists("channel_messages/{$channel->id}/somefile.jpg");
    }

    public function test_a_project_linked_channel_refuses_soft_delete_but_still_force_deletes(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $channel = Channel::factory()->create(['owner_id' => $owner->id]);
        \App\Models\Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => [], 'channel_id' => $channel->id]);

        $this->assertFalse($channel->fresh()->delete());
        $this->assertNull($channel->fresh()->deleted_at);

        $this->assertTrue($channel->fresh()->forceDelete());
        $this->assertNull(Channel::withTrashed()->find($channel->id));
    }

    public function test_an_unlinked_channel_still_soft_deletes(): void
    {
        $channel = Channel::factory()->create();

        $this->assertTrue($channel->delete());
        $this->assertNotNull(Channel::withTrashed()->find($channel->id)->deleted_at);
    }
}
