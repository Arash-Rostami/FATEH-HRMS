<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Filament\Resources\UserResource\Enums\UserType;
use App\Livewire\Dashboard\Messaging\SwitchTabs;
use App\Models\Channel;
use App\Models\ChannelMessage;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class MessagingTest extends TestCase
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

    public function test_messaging_unread_counts()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $sender = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Message::factory()->create([
            'recipient_id' => $user->id,
            'sender_id' => $sender->id,
            'read_at' => null,
        ]);

        Livewire::actingAs($user)
            ->test(SwitchTabs::class)
            ->assertSet('contactsUnread', 1)
            ->assertSet('channelsUnread', 0)
            ->call('refreshCounts')
            ->assertHasNoErrors();
    }

    public function test_switch_tabs_puts_the_current_module_first_like_projects_and_tasks()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($user)
            ->test(SwitchTabs::class, ['active' => 'channels'])
            ->assertSeeInOrder(['گروه‌ها', 'پیام‌رسان']);

        Livewire::actingAs($user)
            ->test(SwitchTabs::class, ['active' => 'contacts'])
            ->assertSeeInOrder(['پیام‌رسان', 'گروه‌ها']);
    }

    public function test_switch_tabs_hides_the_off_sibling_and_zeroes_its_unread_count()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $sender = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Message::factory()->create(['recipient_id' => $user->id, 'sender_id' => $sender->id, 'read_at' => null]);

        try {
            config(['app.off' => ['contact']]);
            Livewire::actingAs($user)
                ->test(SwitchTabs::class, ['active' => 'channels'])
                ->assertSet('contactsUnread', 0)
                ->assertDontSee('پیام‌رسان')
                ->assertSee('گروه‌ها');

            config(['app.off' => ['channel']]);
            Livewire::actingAs($user)
                ->test(SwitchTabs::class)
                ->assertSet('contactsUnread', 1)
                ->assertSet('channelsUnread', 0)
                ->assertSee('پیام‌رسان')
                ->assertDontSee('گروه‌ها');

            config(['app.off' => []]);
            Livewire::actingAs($user)->test(SwitchTabs::class)->assertSee('پیام‌رسان')->assertSee('گروه‌ها');
        } finally {
            config(['app.off' => []]);
        }
    }

    public function test_active_tab_defaults_to_contacts_on_mount()
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(SwitchTabs::class)
            ->assertSet('active', 'contacts');
    }

    public function test_mount_accepts_channels_as_active_tab_argument()
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(SwitchTabs::class, ['active' => 'channels'])
            ->assertSet('active', 'channels');
    }

    public function test_refresh_counts_recomputes_contacts_unread_after_new_message_arrives()
    {
        $user = User::factory()->create(['status' => 'active']);
        $sender = User::factory()->create(['status' => 'active']);

        $component = Livewire::actingAs($user)->test(SwitchTabs::class);
        $component->assertSet('contactsUnread', 0);

        Message::factory()->create([
            'recipient_id' => $user->id,
            'sender_id' => $sender->id,
            'read_at' => null,
        ]);

        $component->call('refreshCounts')->assertSet('contactsUnread', 1);
    }

    public function test_messaging_poll_event_recomputes_both_unread_counts_and_the_view_carries_no_poll_of_its_own()
    {
        $user = User::factory()->create(['status' => 'active']);
        $sender = User::factory()->create(['status' => 'active']);
        $channel = Channel::factory()->ownedBy($user)->create();

        $component = Livewire::actingAs($user)->test(SwitchTabs::class)
            ->assertSet('contactsUnread', 0)
            ->assertSet('channelsUnread', 0)
            ->assertDontSeeHtml('wire:poll');

        Message::factory()->create(['recipient_id' => $user->id, 'sender_id' => $sender->id, 'read_at' => null]);
        ChannelMessage::factory()->create(['channel_id' => $channel->id, 'sender_id' => $sender->id, 'body' => 'ping']);

        $component->dispatch('messaging-poll')
            ->assertSet('contactsUnread', 1)
            ->assertSet('channelsUnread', 1);
    }

    public function test_read_message_with_read_at_set_is_not_counted_as_unread()
    {
        $user = User::factory()->create(['status' => 'active']);
        $sender = User::factory()->create(['status' => 'active']);

        Message::factory()->create([
            'recipient_id' => $user->id,
            'sender_id' => $sender->id,
            'read_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(SwitchTabs::class)
            ->assertSet('contactsUnread', 0);
    }

    public function test_soft_deleted_unread_message_is_excluded_from_contacts_unread()
    {
        $user = User::factory()->create(['status' => 'active']);
        $sender = User::factory()->create(['status' => 'active']);

        $message = Message::factory()->create([
            'recipient_id' => $user->id,
            'sender_id' => $sender->id,
            'read_at' => null,
        ]);
        $message->delete();

        Livewire::actingAs($user)
            ->test(SwitchTabs::class)
            ->assertSet('contactsUnread', 0);
    }

    public function test_contacts_unread_excludes_messages_where_user_is_the_sender()
    {
        $user = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);

        Message::factory()->create([
            'sender_id' => $user->id,
            'recipient_id' => $other->id,
            'read_at' => null,
        ]);

        Livewire::actingAs($user)
            ->test(SwitchTabs::class)
            ->assertSet('contactsUnread', 0);
    }

    public function test_contacts_unread_excludes_messages_from_a_deactivated_sender()
    {
        $user = User::factory()->create(['status' => 'active']);
        $sender = User::factory()->create(['status' => 'inactive']);

        Message::factory()->create([
            'recipient_id' => $user->id,
            'sender_id' => $sender->id,
            'read_at' => null,
        ]);

        Livewire::actingAs($user)
            ->test(SwitchTabs::class)
            ->assertSet('contactsUnread', 0);
    }

    public function test_contacts_unread_excludes_messages_from_a_guest_type_sender()
    {
        $user = User::factory()->create(['status' => 'active']);
        $sender = User::factory()->create(['status' => 'active', 'type' => UserType::Guest->value]);

        Message::factory()->create([
            'recipient_id' => $user->id,
            'sender_id' => $sender->id,
            'read_at' => null,
        ]);

        Livewire::actingAs($user)
            ->test(SwitchTabs::class)
            ->assertSet('contactsUnread', 0);
    }

    public function test_channels_unread_counts_new_message_in_channel_the_user_owns()
    {
        $user = User::factory()->create(['status' => 'active']);
        $sender = User::factory()->create(['status' => 'active']);
        $channel = Channel::factory()->ownedBy($user)->create();

        ChannelMessage::factory()->create([
            'channel_id' => $channel->id,
            'sender_id' => $sender->id,
            'body' => 'hello channel',
        ]);

        Livewire::actingAs($user)
            ->test(SwitchTabs::class)
            ->assertSet('channelsUnread', 1)
            ->assertSet('contactsUnread', 0);
    }

    public function test_channels_unread_excludes_soft_deleted_channel_messages()
    {
        $user = User::factory()->create(['status' => 'active']);
        $sender = User::factory()->create(['status' => 'active']);
        $channel = Channel::factory()->ownedBy($user)->create();

        $message = ChannelMessage::factory()->create([
            'channel_id' => $channel->id,
            'sender_id' => $sender->id,
            'body' => 'to be deleted',
        ]);
        $message->delete();

        Livewire::actingAs($user)
            ->test(SwitchTabs::class)
            ->assertSet('channelsUnread', 0);
    }

    public function test_channels_unread_excludes_messages_of_a_soft_deleted_channel()
    {
        $user = User::factory()->create(['status' => 'active']);
        $sender = User::factory()->create(['status' => 'active']);
        $channel = Channel::factory()->ownedBy($user)->create();

        ChannelMessage::factory()->create([
            'channel_id' => $channel->id,
            'sender_id' => $sender->id,
            'body' => 'channel will be deleted',
        ]);

        $component = Livewire::actingAs($user)->test(SwitchTabs::class)->assertSet('channelsUnread', 1);

        $channel->delete();

        $component->call('refreshCounts')->assertSet('channelsUnread', 0);
    }

    public function test_channels_unread_only_counts_messages_strictly_after_last_read_message_id()
    {
        $user = User::factory()->create(['status' => 'active']);
        $sender = User::factory()->create(['status' => 'active']);
        $channel = Channel::factory()->ownedBy($user)->create();

        $first = ChannelMessage::factory()->create([
            'channel_id' => $channel->id,
            'sender_id' => $sender->id,
            'body' => 'first',
        ]);

        $affected = DB::table('channel_members')
            ->where('user_id', $user->id)
            ->where('channel_id', $channel->id)
            ->update(['last_read_message_id' => $first->id]);
        $this->assertSame(1, $affected, 'membership row must exist for last-read boundary test');

        $component = Livewire::actingAs($user)->test(SwitchTabs::class);
        $component->assertSet('channelsUnread', 0);

        ChannelMessage::factory()->create([
            'channel_id' => $channel->id,
            'sender_id' => $sender->id,
            'body' => 'second',
        ]);

        $component->call('refreshCounts')->assertSet('channelsUnread', 1);
    }

    public function test_channels_unread_excludes_messages_in_channels_the_user_is_not_a_member_of()
    {
        $user = User::factory()->create(['status' => 'active']);
        $owner = User::factory()->create(['status' => 'active']);
        $sender = User::factory()->create(['status' => 'active']);
        $channel = Channel::factory()->ownedBy($owner)->create();

        ChannelMessage::factory()->create([
            'channel_id' => $channel->id,
            'sender_id' => $sender->id,
            'body' => 'not my channel',
        ]);

        Livewire::actingAs($user)
            ->test(SwitchTabs::class)
            ->assertSet('channelsUnread', 0);
    }

    public function test_active_tab_value_does_not_change_unread_counts()
    {
        $user = User::factory()->create(['status' => 'active']);
        $sender = User::factory()->create(['status' => 'active']);

        Message::factory()->create([
            'recipient_id' => $user->id,
            'sender_id' => $sender->id,
            'read_at' => null,
        ]);

        Livewire::actingAs($user)
            ->test(SwitchTabs::class, ['active' => 'channels'])
            ->assertSet('active', 'channels')
            ->assertSet('contactsUnread', 1)
            ->assertSet('channelsUnread', 0);
    }

    public function test_zero_unread_state_for_user_with_no_messages_and_no_channels()
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(SwitchTabs::class)
            ->assertSet('contactsUnread', 0)
            ->assertSet('channelsUnread', 0);
    }

    public function test_image_attachment_partial_renders_fancybox_grouped_by_message()
    {
        $html = view('livewire.dashboard.messaging.message-attachments', [
            'msg' => [
                'id' => 4242,
                'body' => 'photo',
                'is_mine' => true,
                'attachments' => [[
                    'path' => 'messages/x/photo.jpg',
                    'name' => 'photo.jpg',
                    'url' => 'http://example.test/storage/messages/x/photo.jpg',
                    'size_label' => '0.1 KB',
                    'is_image' => true,
                ]],
            ],
        ])->render();

        $this->assertStringContainsString('data-fancybox="msg-4242"', $html);
        $this->assertStringNotContainsString('target="_blank"', $html);
    }

    public function test_non_image_attachment_partial_still_renders_download_button()
    {
        $html = view('livewire.dashboard.messaging.message-attachments', [
            'msg' => [
                'id' => 4243,
                'body' => 'file',
                'is_mine' => true,
                'attachments' => [[
                    'path' => 'messages/x/doc.pdf',
                    'name' => 'doc.pdf',
                    'url' => 'http://example.test/storage/messages/x/doc.pdf',
                    'size_label' => '12.0 KB',
                    'is_image' => false,
                ]],
            ],
        ])->render();

        $this->assertStringNotContainsString('data-fancybox', $html);
        $this->assertStringContainsString('downloadAttachment(4243, 0)', $html);
    }
}