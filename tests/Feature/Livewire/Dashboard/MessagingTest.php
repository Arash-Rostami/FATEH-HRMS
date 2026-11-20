<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Enums\UserType;
use App\Livewire\Dashboard\Channel\Presentation\ChannelPresenter;
use App\Livewire\Dashboard\Contact\Presentation\ContactPresenter;
use App\Livewire\Dashboard\Messaging\Actions\ForwardMessageAction;
use App\Livewire\Dashboard\Messaging\SwitchTabs;
use App\Models\Channel;
use App\Models\ChannelMessage;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class MessagingTest extends TestCase
{
    public static function forwardingDirections(): array
    {
        return [
            'contact to contact' => ['contact', 'contact'],
            'contact to channel' => ['contact', 'channel'],
            'channel to contact' => ['channel', 'contact'],
            'channel to channel' => ['channel', 'channel'],
        ];
    }

    #[DataProvider('forwardingDirections')]
    public function test_forwarded_attachments_are_independent_and_survive_source_deletion(string $from, string $to): void
    {
        Storage::fake('public');
        [$me, $source] = $this->forwardSourceFixture($from);
        $target = $to === 'contact'
            ? User::factory()->create(['status' => 'active', 'type' => 'employee'])
            : Channel::factory()->ownedBy($me)->create();
        $path = $source instanceof Message
            ? "messages/{$source->sender_id}/original.pdf"
            : "channel_messages/{$source->channel_id}/{$source->id}/original.pdf";
        Storage::disk('public')->put($path, 'Original PDF contents');
        $source->update(['attachments' => [[
            'path' => $path, 'name' => 'report.pdf', 'mime' => 'application/pdf', 'size' => 21,
            'viewers' => [['id' => $me->id]],
        ]]]);

        $message = app(ForwardMessageAction::class)->execute($from, $source->id, $to, $target->id);
        $copy = $message->attachments[0];
        $this->assertSame($me->id, $message->sender_id);
        $this->assertSame(ForwardMessageAction::HEADING . "\n\nOriginal request", $message->body);
        $this->assertNull($message->reply_to_id);
        $this->assertNotSame($path, $copy['path']);
        $this->assertSame('report.pdf', $copy['name']);
        $this->assertSame('application/pdf', $copy['mime']);
        $this->assertArrayNotHasKey('viewers', $copy);
        $this->assertSame('Original PDF contents', Storage::disk('public')->get($copy['path']));

        $source->forceDelete();
        Storage::disk('public')->assertMissing($path);
        Storage::disk('public')->assertExists($copy['path']);
        $this->assertSame('Original PDF contents', Storage::disk('public')->get($copy['path']));
        $this->assertNotNull($message->fresh());
    }

    public static function forwardedLabels(): array
    {
        return [
            'plain forward' => [ForwardMessageAction::HEADING . "\n\nOriginal request"],
            'forward with earlier note' => ["Earlier note\n\n" . ForwardMessageAction::HEADING . "\n\nOriginal request"],
            'legacy heading' => [ForwardMessageAction::LEGACY_HEADING . "\n\nOriginal request"],
            'legacy heading with earlier note' => ["Earlier note\n\n" . ForwardMessageAction::LEGACY_HEADING . "\n\nOriginal request"],
        ];
    }

    #[DataProvider('forwardedLabels')]
    public function test_reforwarding_keeps_one_heading_and_drops_the_earlier_note(string $body): void
    {
        [$me, $source] = $this->forwardSourceFixture('contact');
        $source->update(['body' => $body]);
        $target = User::factory()->create(['status' => 'active', 'type' => 'employee']);

        $message = app(ForwardMessageAction::class)->execute('contact', $source->id, 'contact', $target->id);

        $this->assertSame(ForwardMessageAction::HEADING . "\n\nOriginal request", $message->body);
    }

    public function test_forward_parts_split_note_from_forwarded_text_and_leave_plain_bodies_alone(): void
    {
        $heading = ForwardMessageAction::HEADING;

        $this->assertSame(['note' => 'Hello', 'forwarded' => null], ForwardMessageAction::parts('Hello'));
        $this->assertSame(['note' => '', 'forwarded' => 'Original'], ForwardMessageAction::parts($heading . "\n\nOriginal"));
        $this->assertSame(['note' => 'My note', 'forwarded' => 'Original'], ForwardMessageAction::parts("My note\n\n" . $heading . "\n\nOriginal"));
        $this->assertSame(['note' => '', 'forwarded' => ''], ForwardMessageAction::parts($heading));
    }

    public function test_forward_parts_accept_both_the_current_and_the_legacy_heading(): void
    {
        $legacy = ForwardMessageAction::LEGACY_HEADING;

        $this->assertNotSame(ForwardMessageAction::HEADING, $legacy);
        $this->assertSame(['note' => '', 'forwarded' => 'Original'], ForwardMessageAction::parts($legacy . "\n\nOriginal"));
        $this->assertSame(['note' => 'My note', 'forwarded' => 'Original'], ForwardMessageAction::parts("My note\n\n" . $legacy . "\n\nOriginal"));
        $this->assertSame(['note' => '', 'forwarded' => ''], ForwardMessageAction::parts($legacy));
    }

    public function test_both_presenters_split_a_forward_into_note_and_forwarded_text(): void
    {
        $heading = ForwardMessageAction::HEADING;
        $bodies = ['plain', "My note\n\n{$heading}\n\nOriginal", $heading];
        $rows = array_map(fn($body, $i) => [
            'id' => $i + 1,
            'sender_id' => 7,
            'body' => $body,
            'created_at' => now()->toISOString(),
            'attachments' => [],
            'reactions' => [],
            'sender' => ['name' => 'Sender'],
        ], $bodies, array_keys($bodies));

        foreach ([(new ContactPresenter())->messages($rows, 1, 600), (new ChannelPresenter())->messages($rows, 1, 600)] as $out) {
            $this->assertNull($out[0]['forwarded_html']);
            $this->assertSame('plain', $out[0]['plain_text']);
            $this->assertSame('My note', $out[1]['body_html']);
            $this->assertSame('Original', $out[1]['forwarded_html']);
            $this->assertSame('My note', $out[1]['plain_text']);
            $this->assertSame('', $out[2]['body_html']);
            $this->assertSame('', $out[2]['forwarded_html']);
        }
    }

    public function test_forward_quote_renders_for_a_heading_only_forward_and_copy_text_includes_the_forwarded_text(): void
    {
        $base = ['id' => 5, 'is_mine' => true, 'body' => '', 'body_html' => '', 'reactions' => [], 'attachments' => [], 'can_edit' => false, 'can_delete' => false, 'sender_name' => 'A'];

        $this->assertStringContainsString('بازارسال‌شده', view('livewire.dashboard.messaging.forward-quote', ['msg' => $base + ['forwarded_html' => '']])->render());
        $this->assertStringNotContainsString('بازارسال‌شده', view('livewire.dashboard.messaging.forward-quote', ['msg' => $base + ['forwarded_html' => null]])->render());

        $actions = view('livewire.dashboard.messaging.message-actions', ['msg' => $base + ['forwarded_html' => 'Original text', 'plain_text' => 'Original text']])->render();
        $this->assertMatchesRegularExpression('/data-text="\s*Original text"/', $actions);
        $this->assertStringContainsString('data-body="Original text"', $actions);
    }

    public static function inaccessibleForwardSources(): array
    {
        return [
            'another private conversation' => ['contact', false],
            'channel membership removed' => ['channel', false],
            'deleted contact message' => ['contact', true],
            'deleted channel message' => ['channel', true],
        ];
    }

    #[DataProvider('inaccessibleForwardSources')]
    public function test_forwarding_rejects_an_inaccessible_source(string $type, bool $deleted): void
    {
        [$me, $source] = $this->forwardSourceFixture($type);
        $target = User::factory()->create(['status' => 'active', 'type' => 'employee']);
        if ($deleted) {
            $source->delete();
        } elseif ($source instanceof Message) {
            $source->update(['recipient_id' => $target->id]);
        } else {
            $source->channel->memberUsers()->detach($me->id);
        }

        $this->expectException(ModelNotFoundException::class);
        app(ForwardMessageAction::class)->execute($type, $source->id, 'contact', $target->id);
    }

    public static function unavailableForwardDestinations(): array
    {
        return [
            'inactive contact' => ['contact', 'inactive'],
            'guest contact' => ['contact', 'guest'],
            'self' => ['contact', 'self'],
            'unjoined channel' => ['channel', 'outside'],
            'deleted channel' => ['channel', 'deleted'],
        ];
    }

    #[DataProvider('unavailableForwardDestinations')]
    public function test_forwarding_rejects_an_unavailable_destination(string $type, string $condition): void
    {
        [$me, $source] = $this->forwardSourceFixture('contact');
        if ($type === 'contact') {
            $target = $condition === 'self' ? $me : User::factory()->create([
                'status' => $condition === 'inactive' ? 'inactive' : 'active',
                'type' => $condition === 'guest' ? 'guest' : 'employee',
            ]);
        } else {
            $owner = $condition === 'outside' ? User::factory()->create(['status' => 'active']) : $me;
            $target = Channel::factory()->ownedBy($owner)->create();
            if ($condition === 'deleted') $target->delete();
        }

        $this->expectException(ModelNotFoundException::class);
        app(ForwardMessageAction::class)->execute('contact', $source->id, $type, $target->id);
    }

    public function test_an_inactive_actor_cannot_forward_an_accessible_message(): void
    {
        [$me, $source] = $this->forwardSourceFixture('contact');
        $target = User::factory()->create(['status' => 'active', 'type' => 'employee']);
        $me->update(['status' => 'inactive']);

        $this->expectException(HttpException::class);
        app(ForwardMessageAction::class)->execute('contact', $source->id, 'contact', $target->id);
    }

    public function test_forwarding_rolls_back_the_message_and_copied_files_if_an_attachment_is_missing(): void
    {
        Storage::fake('public');
        [$me, $source] = $this->forwardSourceFixture('contact');
        $target = User::factory()->create(['status' => 'active', 'type' => 'employee']);
        $path = "messages/{$source->sender_id}/original.pdf";
        Storage::disk('public')->put($path, 'PDF');
        $source->update(['attachments' => [
            ['path' => $path, 'name' => 'present.pdf', 'mime' => 'application/pdf', 'size' => 3],
            ['path' => 'messages/missing.pdf', 'name' => 'missing.pdf'],
        ]]);

        try {
            app(ForwardMessageAction::class)->execute('contact', $source->id, 'contact', $target->id);
            $this->fail('A missing attachment must fail the entire forward.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('forwardMessage', $e->errors());
        }

        $this->assertSame(0, Message::where('recipient_id', $target->id)->count());
        $this->assertSame([$path], Storage::disk('public')->allFiles());
    }

    public function test_forwarding_a_long_channel_message_to_a_contact_fails_without_truncating_it(): void
    {
        [$me, $source] = $this->forwardSourceFixture('channel');
        $source->update(['body' => str_repeat('a', 2000)]);
        $target = User::factory()->create(['status' => 'active', 'type' => 'employee']);

        try {
            app(ForwardMessageAction::class)->execute('channel', $source->id, 'contact', $target->id);
            $this->fail('The heading counts toward the destination body limit.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('forwardMessage', $e->errors());
        }
        $this->assertSame(str_repeat('a', 2000), $source->fresh()->body);
        $this->assertSame(0, Message::where('recipient_id', $target->id)->count());
    }

    public static function destinationReadStates(): array
    {
        return ['unread history' => [false], 'already read history' => [true]];
    }

    #[DataProvider('destinationReadStates')]
    public function test_forwarding_only_advances_the_destination_channel_cursor_when_already_caught_up(bool $read): void
    {
        [$me, $source] = $this->forwardSourceFixture('contact');
        $target = Channel::factory()->ownedBy($me)->create();
        $previous = ChannelMessage::create(['channel_id' => $target->id, 'sender_id' => $source->sender_id, 'body' => 'Earlier unread message']);
        if ($read) $target->memberUsers()->updateExistingPivot($me->id, ['last_read_message_id' => $previous->id]);

        $forwarded = app(ForwardMessageAction::class)->execute('contact', $source->id, 'channel', $target->id);

        $cursor = $target->memberUsers()->where('users.id', $me->id)->first()->pivot->last_read_message_id;
        $this->assertEquals($read ? $forwarded->id : null, $cursor);
    }

    private function forwardSourceFixture(string $type): array
    {
        Queue::fake();
        [$me, $peer] = User::factory()->count(2)->create(['status' => 'active', 'type' => 'employee'])->all();
        $this->actingAs($me);
        if ($type === 'contact') {
            $source = Message::create(['sender_id' => $peer->id, 'recipient_id' => $me->id, 'body' => 'Original request']);
        } else {
            $channel = Channel::factory()->ownedBy($me)->create();
            $channel->memberUsers()->attach($peer->id);
            $source = ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $peer->id, 'body' => 'Original request']);
        }
        return [$me, $source];
    }

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
        $this->assertStringContainsString('target="_blank" rel="noopener" data-type="image"', $html);
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
