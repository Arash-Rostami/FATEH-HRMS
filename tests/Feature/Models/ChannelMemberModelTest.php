<?php

namespace Tests\Feature\Models;

use App\Models\Channel;
use App\Models\ChannelMember;
use App\Models\ChannelMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ChannelMemberModelTest extends TestCase
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

    public function test_primary_key_is_composite_user_id_and_channel_id(): void
    {
        $member = new ChannelMember();

        $this->assertSame(['user_id', 'channel_id'], $member->getKeyName());
        $this->assertFalse($member->getIncrementing());
    }

    public function test_fillable_lists_the_pivot_columns(): void
    {
        $this->assertSame(
            ['channel_id', 'user_id', 'last_read_message_id', 'joined_at', 'entered_at'],
            (new ChannelMember())->getFillable()
        );
    }

    public function test_factory_persists_row_with_composite_primary_key(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $channel = Channel::factory()->create(['owner_id' => $owner->id]);
        $memberUser = User::factory()->create(['status' => 'active']);

        $member = ChannelMember::factory()->create([
            'channel_id' => $channel->id,
            'user_id' => $memberUser->id,
        ]);

        $this->assertDatabaseHas('channel_members', [
            'channel_id' => $channel->id,
            'user_id' => $memberUser->id,
        ]);

        $row = DB::table('channel_members')
            ->where('channel_id', $channel->id)
            ->where('user_id', $memberUser->id)
            ->first();

        $this->assertNotNull($row);
        $this->assertSame($channel->id, (int) $row->channel_id);
        $this->assertSame($memberUser->id, (int) $row->user_id);
    }

    public function test_entered_at_defaults_to_null_for_invited_member(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $channel = Channel::factory()->create(['owner_id' => $owner->id]);
        $memberUser = User::factory()->create(['status' => 'active']);

        $member = ChannelMember::factory()->create([
            'channel_id' => $channel->id,
            'user_id' => $memberUser->id,
            'entered_at' => null,
        ]);

        $row = DB::table('channel_members')
            ->where('channel_id', $channel->id)
            ->where('user_id', $memberUser->id)
            ->first();

        $this->assertNotNull($row);
        $this->assertNull($row->entered_at);
    }

    public function test_last_read_message_relation_resolves_via_custom_foreign_key(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $channel = Channel::factory()->create(['owner_id' => $owner->id]);
        $sender = User::factory()->create(['status' => 'active']);
        $message = ChannelMessage::factory()->create([
            'channel_id' => $channel->id,
            'sender_id' => $sender->id,
        ]);
        $memberUser = User::factory()->create(['status' => 'active']);

        $member = ChannelMember::factory()->create([
            'channel_id' => $channel->id,
            'user_id' => $memberUser->id,
            'last_read_message_id' => $message->id,
        ]);

        $member->load('lastReadMessage');

        $this->assertNotNull($member->lastReadMessage);
        $this->assertSame($message->id, $member->lastReadMessage->id);
    }

    public function test_last_read_message_relation_returns_null_when_pointer_is_null(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $channel = Channel::factory()->create(['owner_id' => $owner->id]);
        $memberUser = User::factory()->create(['status' => 'active']);

        $member = ChannelMember::factory()->create([
            'channel_id' => $channel->id,
            'user_id' => $memberUser->id,
            'last_read_message_id' => null,
        ]);

        $member->load('lastReadMessage');

        $this->assertNull($member->lastReadMessage);
    }
}