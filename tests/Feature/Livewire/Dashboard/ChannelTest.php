<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Enums\ChannelType;
use App\Jobs\ReconcileEdge;
use App\Jobs\ReconcileNudge;
use App\Livewire\Dashboard\Channel\Actions\DownloadChannelAttachmentAction;
use App\Livewire\Dashboard\Channel\Actions\ForceDeleteChannelMessageAction;
use App\Livewire\Dashboard\Channel\Actions\MarkChannelReadAction;
use App\Livewire\Dashboard\Channel\Actions\SearchChannelMessagesAction;
use App\Livewire\Dashboard\Channel\Actions\SendChannelMessageAction;
use App\Livewire\Dashboard\Channel\Presentation\ChannelPresenter;
use App\Livewire\Dashboard\Tab\Actions\SendSmsAction;
use App\Livewire\Dashboard\Tab\Presentation\TabPresenter;
use Illuminate\Http\UploadedFile;
use App\Livewire\Dashboard\Channel\Main;
use App\Models\Channel;
use App\Models\ChannelMember;
use App\Models\ChannelMessage;
use App\Models\Edge;
use App\Models\User;
use App\Services\Menu\EdgeService;
use App\Services\Menu\Toasts\ChannelToast;
use App\Services\Messaging\PollDigestService;
use App\Services\Notify\Channels\SmsChannel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class ChannelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        Queue::fake();
        DB::beginTransaction();
        Livewire::withoutLazyLoading();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        Mockery::close();
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

    public function test_channel_crud_lifecycle()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->set('create.name', 'Probe')
            ->set('create.type', 'open')
            ->call('createChannel')
            ->assertHasNoErrors()
            ->set('composer.body', 'probe body')
            ->call('send')
            ->assertHasNoErrors();

        $channel = Channel::where('name', 'Probe')->firstOrFail();
        $this->assertTrue(ChannelMember::where('user_id', $owner->id)->where('channel_id', $channel->id)->exists());

        $msg = ChannelMessage::where('body', 'probe body')->firstOrFail();

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('replyTo', $msg->id)
            ->set('composer.body', 'probe reply')
            ->call('send')
            ->assertHasNoErrors();

        $reply = ChannelMessage::where('body', 'probe reply')->firstOrFail();
        $this->assertSame($msg->id, $reply->reply_to_id);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->set('edit.editingBody', 'probe body (edited)')
            ->call('saveEdit', $msg->id, 'probe body (edited)')
            ->assertHasNoErrors();

        $msg->refresh();
        $this->assertTrue((bool) $msg->is_edited);
        $this->assertSame('probe body (edited)', $msg->body);

        $c = Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('deleteMessage', $msg->id);

        $c->assertHasNoErrors();
        $this->assertNull(ChannelMessage::withoutTrashed()->find($msg->id));
        $this->assertTrue(ChannelMessage::withTrashed()->find($msg->id)->trashed());

        $c->call('undoDelete')->assertHasNoErrors();
        $this->assertNotNull(ChannelMessage::withoutTrashed()->find($msg->id));

        app(ForceDeleteChannelMessageAction::class)->execute(ChannelMessage::withTrashed()->find($reply->id));
        $this->assertNull(ChannelMessage::withTrashed()->find($reply->id));

        $other = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($other)
            ->test(Main::class)
            ->call('joinChannel', $channel->id)
            ->assertHasNoErrors();

        $this->assertTrue(ChannelMember::where(['user_id' => $other->id, 'channel_id' => $channel->id])->exists());

        Livewire::actingAs($other)
            ->test(Main::class)
            ->call('leaveChannel', $channel->id)
            ->assertHasNoErrors();

        $this->assertFalse(ChannelMember::where(['user_id' => $other->id, 'channel_id' => $channel->id])->exists());
    }

    public function test_channel_search_finds_message()
    {
        $this->markTestSkipped('InnoDB FULLTEXT does not flush just-inserted rows to the searchable index until commit; the mandatory transaction-rollback harness keeps the row uncommitted, so MATCH...AGAINST cannot see it. The query itself is correct — verified manually against the live MySQL schema.');
    }

    public function test_bookmarks_return_only_this_channels_messages_and_nothing_for_non_members()
    {
        $owner = User::factory()->create(['status' => 'active']);
        $outsider = User::factory()->create(['status' => 'active']);
        $channel = Channel::create(['name' => 'Bm', 'slug' => 'bm-slug', 'type' => 'open', 'owner_id' => $owner->id]);
        $other = Channel::create(['name' => 'Bm2', 'slug' => 'bm2-slug', 'type' => 'open', 'owner_id' => $owner->id]);
        $mine = ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'pinned one']);
        $foreign = ChannelMessage::create(['channel_id' => $other->id, 'sender_id' => $owner->id, 'body' => 'elsewhere']);

        $rows = app(SearchChannelMessagesAction::class)->bookmarks($channel->id, [$mine->id, $foreign->id, 0, -4], $owner->id);

        $this->assertSame([$mine->id], array_column($rows, 'id'));
        $this->assertSame([], app(SearchChannelMessagesAction::class)->bookmarks($channel->id, [$mine->id], $outsider->id));
    }

    public function test_days_list_groups_older_messages_by_day_and_is_member_only()
    {
        $owner = User::factory()->create(['status' => 'active']);
        $outsider = User::factory()->create(['status' => 'active']);
        $channel = Channel::create(['name' => 'Dy', 'slug' => 'dy-slug', 'type' => 'open', 'owner_id' => $owner->id]);
        $a1 = ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'a1']);
        $a2 = ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'a2']);
        $b = ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'b']);
        $a1->forceFill(['created_at' => '2026-10-01 10:00:00'])->saveQuietly();
        $a2->forceFill(['created_at' => '2026-10-01 11:00:00'])->saveQuietly();
        $b->forceFill(['created_at' => '2026-10-03 09:00:00'])->saveQuietly();

        $days = app(SearchChannelMessagesAction::class)->days($channel->id, $owner->id);

        $this->assertSame([['day' => '2026-10-03', 'id' => $b->id], ['day' => '2026-10-01', 'id' => $a1->id]], $days);
        $this->assertSame([], app(SearchChannelMessagesAction::class)->days($channel->id, $outsider->id));
    }

    public function test_files_gallery_returns_this_channels_attachments_by_kind_and_is_member_only()
    {
        $owner = User::factory()->create(['status' => 'active']);
        $outsider = User::factory()->create(['status' => 'active']);
        $channel = Channel::create(['name' => 'Fl', 'slug' => 'fl-slug', 'type' => 'open', 'owner_id' => $owner->id]);
        $other = Channel::create(['name' => 'Fl2', 'slug' => 'fl2-slug', 'type' => 'open', 'owner_id' => $owner->id]);

        $img = ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'pic',
            'attachments' => [['path' => 'chat/a.png', 'name' => 'a.png', 'mime' => 'image/png', 'size' => 2048]]]);
        ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'plain']);
        $doc = ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'doc',
            'attachments' => [['path' => 'chat/b.pdf', 'name' => 'b.pdf', 'mime' => 'application/pdf', 'size' => 4096]]]);
        $foreign = ChannelMessage::create(['channel_id' => $other->id, 'sender_id' => $owner->id, 'body' => 'clip',
            'attachments' => [['path' => 'chat/c.mp4', 'name' => 'c.mp4', 'mime' => 'video/mp4', 'size' => 1024]]]);

        $rows = app(SearchChannelMessagesAction::class)->files($channel->id, $owner->id);

        // attachment-less message + the other channel's video are both excluded; newest first
        $this->assertSame([$doc->id . ':0', $img->id . ':0'], array_column($rows, 'key'));
        $this->assertSame(['doc', 'image'], array_column($rows, 'kind'));
        $this->assertSame([], app(SearchChannelMessagesAction::class)->files($channel->id, $outsider->id));
    }

    public function test_channel_create_with_recipients_and_member_management()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $keep = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $drop = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('openCreate')
            ->set('create.name', 'Probe Team')
            ->set('create.slug', 'probe-team')
            ->set('create.type', 'open')
            ->set('createRecipientIds', [$keep->id, $drop->id])
            ->call('createChannel')
            ->assertHasNoErrors();

        $channel = Channel::where('name', 'Probe Team')->firstOrFail();
        $this->assertTrue(ChannelMember::where(['user_id' => $drop->id, 'channel_id' => $channel->id])->exists());

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('openManageMembers', $channel->id)
            ->assertHasNoErrors()
            ->set('memberRecipientIds', [$keep->id])
            ->call('saveManageMembers')
            ->assertHasNoErrors();

        $this->assertTrue(ChannelMember::where(['user_id' => $keep->id, 'channel_id' => $channel->id])->exists());
        $this->assertFalse(ChannelMember::where(['user_id' => $drop->id, 'channel_id' => $channel->id])->exists());
    }

    public function test_channel_owner_cannot_be_removed_from_members()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $other = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->set('create.name', 'Owned')
            ->set('create.slug', 'owned-slug')
            ->set('create.type', 'open')
            ->set('createRecipientIds', [$other->id])
            ->call('createChannel')
            ->assertHasNoErrors();

        $channel = Channel::where('name', 'Owned')->firstOrFail();

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('openManageMembers', $channel->id)
            ->set('memberRecipientIds', [$other->id])
            ->call('saveManageMembers')
            ->assertHasNoErrors();

        $this->assertTrue(ChannelMember::where(['user_id' => $owner->id, 'channel_id' => $channel->id])->exists());
    }

    public function test_save_manage_members_can_remove_all_non_owner_members()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $other = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = Channel::create(['name' => 'ClearAll', 'slug' => 'clear-all-slug', 'type' => 'open', 'owner_id' => $owner->id]);
        $channel->memberUsers()->attach($other->id, [
            'joined_at' => now(), 'entered_at' => now(), 'last_read_message_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('openManageMembers', $channel->id)
            ->set('memberRecipientIds', [])
            ->call('saveManageMembers')
            ->assertHasNoErrors();

        $this->assertFalse(ChannelMember::where(['user_id' => $other->id, 'channel_id' => $channel->id])->exists());
        $this->assertTrue(ChannelMember::where(['user_id' => $owner->id, 'channel_id' => $channel->id])->exists());
    }

    public function test_channel_filter_and_browse_toggles()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->assertSet('filter', 'all')
            ->call('setFilter', 'mine')
            ->assertSet('filter', 'mine')
            ->assertSet('browseMode', false)
            ->call('toggleBrowse')
            ->assertSet('browseMode', true)
            ->call('toggleBrowse')
            ->assertSet('browseMode', false)
            ->assertHasNoErrors();
    }

    public function test_channel_load_older_messages_expands_window()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->set('create.name', 'History')
            ->set('create.slug', 'history-slug')
            ->set('create.type', 'open')
            ->call('createChannel')
            ->assertHasNoErrors();

        $channel = Channel::where('name', 'History')->firstOrFail();
        foreach (range(1, 12) as $i) {
            ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => "older $i"]);
        }

        $component = Livewire::actingAs($owner)->test(Main::class)->call('selectChannel', $channel->id);
        $before = count($component->instance()->messages());
        $this->assertLessThanOrEqual(11, $before);
        $component->call('loadOlder')->assertHasNoErrors();
        $this->assertGreaterThan($before, count($component->instance()->messages()));
    }

    public function test_channel_mark_read_updates_pivot()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->set('create.name', 'Read')
            ->set('create.slug', 'read-slug')
            ->set('create.type', 'open')
            ->call('createChannel')
            ->assertHasNoErrors();

        $channel = Channel::where('name', 'Read')->firstOrFail();
        $msg = ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'read me']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('markRead', $channel->id)
            ->assertHasNoErrors();

        $pivot = ChannelMember::where(['user_id' => $owner->id, 'channel_id' => $channel->id])->first();
        $this->assertSame($msg->id, (int)$pivot->last_read_message_id);
    }

    public function test_channel_send_rejects_empty_body_without_attachments()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->set('create.name', 'Guard')
            ->set('create.slug', 'guard-slug')
            ->set('create.type', 'open')
            ->call('createChannel')
            ->assertHasNoErrors();

        $channel = Channel::where('name', 'Guard')->firstOrFail();

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->set('composer.body', '')
            ->call('send')
            ->assertDispatched('show-toast', message: 'وارد کردن متن پیام یا افزودن فایل ضمیمه الزامی است.', type: 'error');

        $this->assertSame(0, ChannelMessage::where('channel_id', $channel->id)->count());
    }

    public function test_channel_send_with_attachment_persists_metadata()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->set('create.name', 'Attach')
            ->set('create.slug', 'attach-slug')
            ->set('create.type', 'open')
            ->call('createChannel')
            ->assertHasNoErrors();

        $channel = Channel::where('name', 'Attach')->firstOrFail();
        $file = UploadedFile::fake()->image('report.jpg');

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->set('composer.attachments', [$file])
            ->set('composer.body', '')
            ->call('send')
            ->assertHasNoErrors();

        $msg = ChannelMessage::where('channel_id', $channel->id)->firstOrFail();
        $this->assertNotNull($msg->attachments);
        $this->assertSame('report.jpg', $msg->attachments[0]['name']);
        $this->assertNotEmpty($msg->attachments[0]['path']);
        $this->assertNotEmpty($msg->attachments[0]['mime']);
        $this->assertGreaterThan(0, $msg->attachments[0]['size']);
    }

    public function test_send_channel_message_rolls_back_when_attachment_storage_fails()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = Channel::create(['name' => 'RollbackSend', 'slug' => 'rollback-send-slug', 'type' => 'open', 'owner_id' => $owner->id]);

        $fakeImage = UploadedFile::fake()->image('x.jpg');
        $failingFile = new class('x.jpg', fopen($fakeImage->getPathname(), 'r')) extends \Illuminate\Http\Testing\File {
            public function storeAs($path, $name = null, $options = []): string
            {
                throw new \RuntimeException('simulated disk failure');
            }
        };

        $this->actingAs($owner);
        $form = Livewire::test(Main::class)->instance()->composer;
        $form->body = '';
        $form->attachments = [$failingFile];

        try {
            app(SendChannelMessageAction::class)->execute($form, $channel->id);
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertSame('simulated disk failure', $e->getMessage());
        }

        $this->assertSame(0, ChannelMessage::where('channel_id', $channel->id)->count());
    }

    public function test_send_channel_message_cleans_up_already_stored_files_when_a_later_file_fails()
    {
        Storage::fake('public');
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = Channel::create(['name' => 'RollbackCleanup', 'slug' => 'rollback-cleanup-slug', 'type' => 'open', 'owner_id' => $owner->id]);

        $goodFile = UploadedFile::fake()->image('good.jpg');

        $fakeImage = UploadedFile::fake()->image('bad.jpg');
        $failingFile = new class('bad.jpg', fopen($fakeImage->getPathname(), 'r')) extends \Illuminate\Http\Testing\File {
            public function storeAs($path, $name = null, $options = []): string
            {
                throw new \RuntimeException('simulated disk failure');
            }
        };

        $this->actingAs($owner);
        $form = Livewire::test(Main::class)->instance()->composer;
        $form->body = '';
        $form->attachments = [$goodFile, $failingFile];

        try {
            app(SendChannelMessageAction::class)->execute($form, $channel->id);
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertSame('simulated disk failure', $e->getMessage());
        }

        $this->assertSame(0, ChannelMessage::where('channel_id', $channel->id)->count());
        $this->assertEmpty(Storage::disk('public')->allFiles("channel_messages/{$channel->id}"));
    }

    public function test_download_attachment_records_viewing_users_id_under_views(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $member = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = Channel::create(['name' => 'ViewTrack', 'slug' => 'view-track-slug', 'type' => 'open', 'owner_id' => $owner->id]);
        $channel->memberUsers()->attach($member->id);

        Storage::disk('public')->put('channel_messages/x/file.jpg', 'content');
        $msg = ChannelMessage::create([
            'channel_id' => $channel->id,
            'sender_id' => $owner->id,
            'body' => 'with file',
            'attachments' => [['path' => 'channel_messages/x/file.jpg', 'name' => 'file.jpg', 'mime' => 'image/jpeg', 'size' => 100]],
        ]);

        $result = Livewire::actingAs($member)->test(Main::class)->instance()->downloadAttachment($msg->id, 0);

        $this->assertNotNull($result);
        $msg->refresh();
        $this->assertArrayHasKey($member->id, $msg->attachments[0]['views']);
    }

    public function test_download_attachment_repeat_download_by_same_user_keeps_original_timestamp(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $member = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = Channel::create(['name' => 'ViewTrackRepeat', 'slug' => 'view-track-repeat-slug', 'type' => 'open', 'owner_id' => $owner->id]);
        $channel->memberUsers()->attach($member->id);

        Storage::disk('public')->put('channel_messages/x/file.jpg', 'content');
        $msg = ChannelMessage::create([
            'channel_id' => $channel->id,
            'sender_id' => $owner->id,
            'body' => 'with file',
            'attachments' => [['path' => 'channel_messages/x/file.jpg', 'name' => 'file.jpg', 'mime' => 'image/jpeg', 'size' => 100]],
        ]);

        app(DownloadChannelAttachmentAction::class)->execute($msg->id, 0, $member->id);
        $firstTimestamp = $msg->refresh()->attachments[0]['views'][$member->id];

        Carbon::setTestNow(now()->addMinutes(5));
        app(DownloadChannelAttachmentAction::class)->execute($msg->id, 0, $member->id);
        Carbon::setTestNow();

        $this->assertSame($firstTimestamp, $msg->refresh()->attachments[0]['views'][$member->id]);
    }

    public function test_download_attachment_by_non_member_returns_null_and_does_not_record_view(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $stranger = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = Channel::create(['name' => 'ViewTrackGuard', 'slug' => 'view-track-guard-slug', 'type' => 'open', 'owner_id' => $owner->id]);

        $msg = ChannelMessage::create([
            'channel_id' => $channel->id,
            'sender_id' => $owner->id,
            'body' => 'with file',
            'attachments' => [['path' => 'channel_messages/x/file.jpg', 'name' => 'file.jpg', 'mime' => 'image/jpeg', 'size' => 100]],
        ]);

        $result = app(DownloadChannelAttachmentAction::class)->execute($msg->id, 0, $stranger->id);

        $this->assertNull($result);
        $this->assertArrayNotHasKey('views', $msg->refresh()->attachments[0]);
    }

    public function test_channel_edit_window_rejects_after_600s_and_allows_within()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->set('create.name', 'Expire')
            ->set('create.slug', 'expire-slug')
            ->set('create.type', 'open')
            ->call('createChannel')
            ->assertHasNoErrors();

        $channel = Channel::where('name', 'Expire')->firstOrFail();
        $msg = ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'original']);
        $msg->created_at = now()->subSeconds(700);
        $msg->save();

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('editMessage', $msg->id)
            ->set('edit.editingBody', 'too late')
            ->call('saveEdit', $msg->id, 'too late')
            ->assertHasNoErrors();

        $msg->refresh();
        $this->assertSame('original', $msg->body);
        $this->assertFalse((bool)$msg->is_edited);

        $fresh = ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'fresh']);
        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('editMessage', $fresh->id)
            ->set('edit.editingBody', 'fresh edited')
            ->call('saveEdit', $fresh->id, 'fresh edited')
            ->assertHasNoErrors();

        $fresh->refresh();
        $this->assertSame('fresh edited', $fresh->body);
        $this->assertTrue((bool)$fresh->is_edited);
    }

    public function test_non_member_select_channel_is_silently_ignored()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $stranger = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = Channel::create(['name' => 'Gated', 'slug' => 'gated-slug', 'type' => 'open', 'owner_id' => $owner->id]);

        Livewire::actingAs($stranger)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->assertSet('activeChannelId', null);

        $this->assertFalse(ChannelMember::where(['user_id' => $stranger->id, 'channel_id' => $channel->id])->exists());
    }

    public function test_removed_member_immediately_loses_active_channel_access()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $victim = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = Channel::create(['name' => 'Kick', 'slug' => 'kick-slug', 'type' => 'open', 'owner_id' => $owner->id]);
        $channel->memberUsers()->attach($victim->id, [
            'joined_at' => now(), 'entered_at' => now(), 'last_read_message_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $victimComponent = Livewire::actingAs($victim)
            ->test(Main::class)
            ->call('selectChannel', $channel->id);

        $victimComponent->assertSet('activeChannelId', $channel->id);
        $this->assertNotNull($victimComponent->instance()->activeChannel());

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('openManageMembers', $channel->id)
            ->set('memberRecipientIds', [])
            ->call('saveManageMembers')
            ->assertHasNoErrors();

        $this->assertFalse(ChannelMember::where(['user_id' => $victim->id, 'channel_id' => $channel->id])->exists());

        $this->actingAs($victim);
        $victimComponent->assertSet('activeChannelId', $channel->id);
        $this->assertNull($victimComponent->instance()->activeChannel());
    }

    public function test_non_owner_open_manage_members_is_silently_ignored()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $other = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = Channel::create(['name' => 'Owned2', 'slug' => 'owned2-slug', 'type' => 'open', 'owner_id' => $owner->id]);
        $channel->memberUsers()->attach($other->id, [
            'joined_at' => now(), 'entered_at' => now(), 'last_read_message_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Livewire::actingAs($other)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('openManageMembers', $channel->id)
            ->assertSet('isManageMembersOpen', false);
    }

    public function test_non_owner_save_manage_members_is_silently_refused()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $other = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $keep = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = Channel::create(['name' => 'Sync', 'slug' => 'sync-slug', 'type' => 'open', 'owner_id' => $owner->id]);
        $channel->memberUsers()->attach($other->id, [
            'joined_at' => now(), 'entered_at' => now(), 'last_read_message_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Livewire::actingAs($other)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->set('memberRecipientIds', [$keep->id])
            ->call('saveManageMembers')
            ->assertHasNoErrors();

        $this->assertFalse(ChannelMember::where(['user_id' => $keep->id, 'channel_id' => $channel->id])->exists());
    }

    public function test_pending_invitees_flag_cancelability_and_refuse_entered_members()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $seen = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $fresh = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $entered = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = Channel::create(['name' => 'Invite', 'slug' => 'invite-slug', 'type' => 'open', 'owner_id' => $owner->id]);

        foreach ([$seen, $fresh, $entered] as $i => $member) {
            $channel->memberUsers()->attach($member->id, [
                'joined_at' => now(), 'entered_at' => $member === $entered ? now() : null, 'last_read_message_id' => null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        Edge::create([
            'user_id' => $seen->id, 'edge_key' => 'channels-controller:nudge', 'subject_type' => Channel::class,
            'subject_id' => $channel->id, 'title' => 't', 'body' => 'b', 'dismissed_at' => now(),
        ]);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->tap(function ($component) use ($seen, $fresh, $entered) {
                $pending = collect($component->instance()->pendingInvitees())->keyBy('id');
                $this->assertSame(['id' => $seen->id, 'name' => $seen->name, 'cancellable' => false], $pending->get($seen->id));
                $this->assertSame(['id' => $fresh->id, 'name' => $fresh->name, 'cancellable' => true], $pending->get($fresh->id));
                $this->assertNull($pending->get($entered->id));
            })
            ->call('cancelInvite', $channel->id, $entered->id)
            ->assertNotDispatched('show-toast');

        $this->assertTrue(ChannelMember::where(['user_id' => $entered->id, 'channel_id' => $channel->id])->exists());
    }

    public function test_owner_cancel_invite_detaches_pending_member_and_keeps_untouched()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $pending = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = Channel::create(['name' => 'Cancel', 'slug' => 'cancel-slug', 'type' => 'open', 'owner_id' => $owner->id]);
        $channel->memberUsers()->attach($pending->id, [
            'joined_at' => now(), 'entered_at' => null, 'last_read_message_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('cancelInvite', $channel->id, $pending->id)
            ->assertDispatched('show-toast', message: 'دعوت لغو شد', type: 'info');

        $this->assertFalse(ChannelMember::where(['user_id' => $pending->id, 'channel_id' => $channel->id])->exists());
    }

    public function test_non_owner_cancel_invite_is_silently_ignored()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $other = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $pending = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = Channel::create(['name' => 'NoCancel', 'slug' => 'nocancel-slug', 'type' => 'open', 'owner_id' => $owner->id]);
        $channel->memberUsers()->attach($pending->id, [
            'joined_at' => now(), 'entered_at' => null, 'last_read_message_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Livewire::actingAs($other)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('cancelInvite', $channel->id, $pending->id);

        $this->assertTrue(ChannelMember::where(['user_id' => $pending->id, 'channel_id' => $channel->id])->exists());
    }

    public function test_non_sender_edit_message_is_silently_ignored()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $other = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = Channel::create(['name' => 'Edit', 'slug' => 'edit-slug', 'type' => 'open', 'owner_id' => $owner->id]);
        $channel->memberUsers()->attach($other->id, [
            'joined_at' => now(), 'entered_at' => now(), 'last_read_message_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $msg = ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'mine']);

        Livewire::actingAs($other)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('editMessage', $msg->id)
            ->assertSet('editingMsg', null);
    }

    public function test_non_sender_delete_message_dispatches_error_toast_and_skips_trash()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $other = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = Channel::create(['name' => 'Del', 'slug' => 'del-slug', 'type' => 'open', 'owner_id' => $owner->id]);
        $channel->memberUsers()->attach($other->id, [
            'joined_at' => now(), 'entered_at' => now(), 'last_read_message_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $msg = ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'keep me']);

        Livewire::actingAs($other)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('deleteMessage', $msg->id)
            ->assertDispatched('show-toast', message: 'این پیام دیگر قابل حذف نیست.', type: 'error');

        $this->assertNotNull(ChannelMessage::withoutTrashed()->find($msg->id));
    }

    public function test_delete_message_rejects_after_edit_window()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = Channel::create(['name' => 'Aged', 'slug' => 'aged-slug', 'type' => 'open', 'owner_id' => $owner->id]);
        $msg = ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'will age']);
        $msg->created_at = now()->subSeconds(700);
        $msg->save();

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('deleteMessage', $msg->id)
            ->assertDispatched('show-toast', message: 'این پیام دیگر قابل حذف نیست.', type: 'error');

        $this->assertNotNull(ChannelMessage::withoutTrashed()->find($msg->id));
    }

    public function test_join_private_channel_is_rejected_and_no_member_added()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $other = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = Channel::create(['name' => 'Secret', 'slug' => 'secret-slug', 'type' => ChannelType::Private->value, 'owner_id' => $owner->id]);

        Livewire::actingAs($other)
            ->test(Main::class)
            ->call('joinChannel', $channel->id)
            ->assertDispatched('show-toast', message: 'این گروه دیگر در دسترس نیست.', type: 'error')
            ->assertHasNoErrors();

        $this->assertFalse(ChannelMember::where(['user_id' => $other->id, 'channel_id' => $channel->id])->exists());
    }

    public function test_join_deleted_channel_shows_a_friendly_toast_instead_of_an_error_dialog()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $other = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = Channel::create(['name' => 'Gone', 'slug' => 'gone-slug', 'type' => ChannelType::Open->value, 'owner_id' => $owner->id]);
        $channel->delete();

        Livewire::actingAs($other)
            ->test(Main::class)
            ->call('joinChannel', $channel->id)
            ->assertDispatched('show-toast', message: 'این گروه دیگر در دسترس نیست.', type: 'error')
            ->assertHasNoErrors();

        $this->assertFalse(ChannelMember::where(['user_id' => $other->id, 'channel_id' => $channel->id])->exists());
    }

    public function test_close_create_resets_create_and_mobile_flags()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('openCreate')
            ->assertSet('createMode', true)
            ->assertSet('mobileShowChat', true)
            ->call('closeCreate')
            ->assertSet('createMode', false)
            ->assertSet('mobileShowChat', false);
    }

    public function test_back_to_list_clears_active_channel_and_resets_window()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->set('create.name', 'Back')
            ->set('create.type', 'open')
            ->call('createChannel')
            ->assertHasNoErrors();

        $channel = Channel::where('name', 'Back')->firstOrFail();

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('loadOlder')
            ->assertSet('loadedLimit', 35)
            ->call('backToList')
            ->assertSet('activeChannelId', null)
            ->assertSet('loadedLimit', 10)
            ->assertSet('hasOlder', false)
            ->assertSet('focusAnchorId', null);
    }

    public function test_open_create_initializes_form_defaults_and_recipients()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->set('create.name', 'stale')
            ->set('create.type', 'private')
            ->set('createRecipientIds', [99])
            ->call('openCreate')
            ->assertSet('createMode', true)
            ->assertSet('create.type', 'open')
            ->assertSet('create.name', '')
            ->assertSet('createRecipientIds', []);
    }

    public function test_select_channel_resets_loaded_limit_and_message_search()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->set('create.name', 'Reset')
            ->set('create.type', 'open')
            ->call('createChannel')
            ->assertHasNoErrors();

        $channel = Channel::where('name', 'Reset')->firstOrFail();

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('loadOlder')
            ->assertSet('loadedLimit', 35)
            ->set('messageSearch', 'stale term')
            ->call('selectChannel', $channel->id)
            ->assertSet('loadedLimit', 10)
            ->assertSet('messageSearch', '');
    }

    public function test_remove_attachment_drops_entry_by_index()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $file1 = UploadedFile::fake()->image('a.jpg');
        $file2 = UploadedFile::fake()->image('b.jpg');

        $component = Livewire::actingAs($owner)
            ->test(Main::class)
            ->set('composer.attachments', [$file1, $file2])
            ->call('removeAttachment', 0);

        $this->assertCount(1, $component->instance()->composer->attachments);
    }

    public function test_reply_to_then_send_clears_reply_to_id()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->set('create.name', 'Reply')
            ->set('create.type', 'open')
            ->call('createChannel')
            ->assertHasNoErrors();

        $channel = Channel::where('name', 'Reply')->firstOrFail();
        $msg = ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'orig']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('replyTo', $msg->id)
            ->assertSet('composer.replyToId', $msg->id)
            ->set('composer.body', 'a reply')
            ->call('send')
            ->assertSet('composer.replyToId', null);
    }

    public function test_edit_message_populates_edit_form_and_clears_reply_to()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->set('create.name', 'EditForm')
            ->set('create.type', 'open')
            ->call('createChannel')
            ->assertHasNoErrors();

        $channel = Channel::where('name', 'EditForm')->firstOrFail();
        $orig = ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'original body']);
        $anchor = ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'anchor']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('replyTo', $anchor->id)
            ->assertSet('composer.replyToId', $anchor->id)
            ->call('editMessage', $orig->id)
            ->assertSet('composer.replyToId', null)
            ->assertSet('edit.editingBody', 'original body')
            ->assertSet('editingMsg', ['id' => $orig->id, 'body' => 'original body']);
    }

    public function test_joinable_channels_lists_open_channels_user_has_not_joined()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $viewer = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $openCh = Channel::create(['name' => 'OpenJoin', 'slug' => 'open-join', 'type' => ChannelType::Open->value, 'owner_id' => $owner->id]);
        $joinedCh = Channel::create(['name' => 'AlreadyIn', 'slug' => 'already-in', 'type' => ChannelType::Open->value, 'owner_id' => $owner->id]);
        $joinedCh->memberUsers()->attach($viewer->id, [
            'joined_at' => now(), 'entered_at' => now(), 'last_read_message_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $component = Livewire::actingAs($viewer)->test(Main::class);
        $ids = array_column($component->instance()->joinableChannels(), 'id');

        $this->assertContains($openCh->id, $ids);
        $this->assertNotContains($joinedCh->id, $ids);
    }

    public function test_readers_returns_member_cursor_map_keyed_by_user_id()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $other = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = Channel::create(['name' => 'Readers', 'slug' => 'readers-slug', 'type' => ChannelType::Open->value, 'owner_id' => $owner->id]);
        $read = ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'read']);
        $channel->memberUsers()->attach($other->id, [
            'joined_at' => now(), 'entered_at' => now(), 'last_read_message_id' => $read->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $component = Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id);

        $readers = $component->instance()->readers();

        $this->assertArrayHasKey($owner->id, $readers);
        $this->assertArrayHasKey($other->id, $readers);
        $this->assertSame($read->id, $readers[$other->id]['cursor']);
    }

    public function test_channel_members_returns_all_members_sorted_owner_first()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $memberA = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $memberB = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = Channel::create(['name' => 'Members', 'slug' => 'members-slug', 'type' => ChannelType::Open->value, 'owner_id' => $owner->id]);
        $channel->memberUsers()->attach([$memberA->id, $memberB->id]);
        $channel->memberUsers()->detach($owner->id);
        $channel->memberUsers()->attach($owner->id, [
            'joined_at' => now(), 'entered_at' => now(), 'last_read_message_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $component = Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id);

        $members = $component->instance()->channelMembers();

        $this->assertCount(3, $members);
        $this->assertEqualsCanonicalizing([$owner->id, $memberA->id, $memberB->id], array_column($members, 'id'));
        $this->assertSame($owner->id, $members[0]['id']);
        $this->assertTrue($members[0]['is_owner']);
    }

    public function test_channel_members_flags_is_owner_correctly()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $other = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = Channel::create(['name' => 'OwnerFlag', 'slug' => 'owner-flag-slug', 'type' => ChannelType::Open->value, 'owner_id' => $owner->id]);
        $channel->memberUsers()->attach($other->id, [
            'joined_at' => now(), 'entered_at' => now(), 'last_read_message_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $component = Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id);

        $members = collect($component->instance()->channelMembers())->keyBy('id');

        $this->assertTrue($members[$owner->id]['is_owner']);
        $this->assertFalse($members[$other->id]['is_owner']);
    }

    public function test_channel_members_and_mentions_carry_presence_focus_and_reachability_for_the_shared_avatar()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $focused = User::factory()->create(['status' => 'active', 'presence' => 'busy', 'focus_until' => now()->addMinutes(30), 'name' => 'Focus Person']);
        $leaver = User::factory()->create(['status' => 'active', 'presence' => 'leave', 'name' => 'Leave Person']);
        $channel = Channel::create(['name' => 'Signs', 'slug' => 'signs-slug', 'type' => ChannelType::Open->value, 'owner_id' => $owner->id]);
        foreach ([$focused, $leaver] as $member) {
            $channel->memberUsers()->attach($member->id, [
                'joined_at' => now(), 'entered_at' => now(), 'last_read_message_id' => null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $component = Livewire::actingAs($owner)->test(Main::class)->call('selectChannel', $channel->id);
        $members = collect($component->instance()->channelMembers())->keyBy('id');
        $mentions = $component->instance()->mentionMemberPresence();

        $this->assertSame(\App\Enums\PresenceStatus::Busy, $members[$focused->id]['presence']);
        $this->assertStringStartsWith('متمرکز تا', $members[$focused->id]['focus']);
        $this->assertNull($members[$leaver->id]['focus']);
        $this->assertTrue($mentions['Focus Person']['reachable']);
        $this->assertFalse($mentions['Leave Person']['reachable']);
    }

    public function test_channel_members_returns_empty_array_when_no_channel_selected()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        $component = Livewire::actingAs($owner)->test(Main::class);

        $this->assertSame([], $component->instance()->channelMembers());
    }

    public function test_readers_and_channel_members_share_one_member_users_query()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $other = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = Channel::create(['name' => 'SharedQuery', 'slug' => 'shared-query-slug', 'type' => ChannelType::Open->value, 'owner_id' => $owner->id]);
        $channel->memberUsers()->attach($other->id, [
            'joined_at' => now(), 'entered_at' => now(), 'last_read_message_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $test = Livewire::actingAs($owner)->test(Main::class)->call('selectChannel', $channel->id);

        try {
            DB::flushQueryLog();
            DB::enableQueryLog();

            $test->instance()->readers();
            $test->instance()->channelMembers();

            $memberQueries = collect(DB::getQueryLog())->pluck('query')
                ->filter(fn($q) => str_contains($q, 'select `users`.*, `channel_members`.`channel_id` as `pivot_channel_id`'));
            $this->assertCount(1, $memberQueries, 'readers and channelMembers must share one underlying memberUsers query');
        } finally {
            DB::disableQueryLog();
        }
    }

    public function test_grouped_messages_groups_by_date_string()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->set('create.name', 'Grouped')
            ->set('create.type', 'open')
            ->call('createChannel')
            ->assertHasNoErrors();

        $channel = Channel::where('name', 'Grouped')->firstOrFail();
        $message = ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'a']);
        ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'b']);

        $component = Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id);

        $grouped = $component->instance()->groupedMessages();

        $this->assertNotEmpty($grouped);
        $this->assertSame(1, count($grouped));
        $key = array_key_first($grouped);
        $this->assertSame(Carbon::parse($message->fresh()->created_at->toISOString())->setTimezone(config('app.timezone'))->toDateString(), $key);
    }

    public function test_message_search_results_empty_when_no_active_channel()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        $component = Livewire::actingAs($owner)->test(Main::class);

        $this->assertSame([], $component->instance()->messageSearchResults());
    }

    public function test_message_search_results_empty_when_query_below_min_length()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->set('create.name', 'SearchGuard')
            ->set('create.type', 'open')
            ->call('createChannel')
            ->assertHasNoErrors();

        $channel = Channel::where('name', 'SearchGuard')->firstOrFail();

        $component = Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->set('messageSearch', 'ab');

        $this->assertSame([], $component->instance()->messageSearchResults());
    }

    public function test_create_channel_dispatches_success_toast()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->set('create.name', 'ToastCreate')
            ->set('create.type', 'open')
            ->call('createChannel')
            ->assertDispatched('show-toast', message: 'گروه ایجاد شد', type: 'success');
    }

    public function test_create_channel_with_blank_name_dispatches_error_toast()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->set('create.name', '')
            ->set('create.type', 'open')
            ->call('createChannel')
            ->assertHasErrors(['create.name'])
            ->assertDispatched('show-toast', message: 'نام گروه الزامی است.', type: 'error');

        $this->assertSame(0, Channel::where('name', '')->count());
    }

    public function test_send_dispatches_message_sent_event()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->set('create.name', 'SentEvt')
            ->set('create.type', 'open')
            ->call('createChannel')
            ->assertHasNoErrors();

        $channel = Channel::where('name', 'SentEvt')->firstOrFail();

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->set('composer.body', 'ping')
            ->call('send')
            ->assertDispatched('message-sent');
    }

    public function test_save_edit_dispatches_success_toast()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->set('create.name', 'EditToast')
            ->set('create.type', 'open')
            ->call('createChannel')
            ->assertHasNoErrors();

        $channel = Channel::where('name', 'EditToast')->firstOrFail();
        $msg = ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'orig']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('saveEdit', $msg->id, 'edited')
            ->assertDispatched('show-toast', message: 'پیام ویرایش شد', type: 'success');
    }

    public function test_delete_message_dispatches_undo_toast()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->set('create.name', 'UndoToast')
            ->set('create.type', 'open')
            ->call('createChannel')
            ->assertHasNoErrors();

        $channel = Channel::where('name', 'UndoToast')->firstOrFail();
        $msg = ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'gone']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('deleteMessage', $msg->id)
            ->assertDispatched('show-undo-toast', message: 'پیام حذف شد', type: 'warning');
    }

    public function test_undo_delete_dispatches_success_toast()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->set('create.name', 'UndoDone')
            ->set('create.type', 'open')
            ->call('createChannel')
            ->assertHasNoErrors();

        $channel = Channel::where('name', 'UndoDone')->firstOrFail();
        $msg = ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'restore me']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('deleteMessage', $msg->id)
            ->call('undoDelete')
            ->assertDispatched('show-toast', message: 'پیام بازیابی شد', type: 'success');
    }

    public function test_undo_delete_after_force_delete_preserves_created_at_and_attachments(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->set('create.name', 'ForceFallback')
            ->set('create.type', 'open')
            ->call('createChannel')
            ->assertHasNoErrors();

        $channel = Channel::where('name', 'ForceFallback')->firstOrFail();

        $attachments = [['path' => 'attachments/y.png', 'name' => 'y.png', 'mime' => 'image/png', 'size' => 4321]];

        $msg = ChannelMessage::create([
            'channel_id'  => $channel->id,
            'sender_id'   => $owner->id,
            'body'        => 'force-delete fallback',
            'attachments' => $attachments,
        ]);
        $originalCreatedAt = $msg->fresh()->created_at->toISOString();

        $component = Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('deleteMessage', $msg->id)
            ->assertHasNoErrors();

        app(ForceDeleteChannelMessageAction::class)->execute(ChannelMessage::withTrashed()->find($msg->id));
        $this->assertNull(ChannelMessage::withTrashed()->find($msg->id));

        $component->call('undoDelete')->assertHasNoErrors();

        $restored = ChannelMessage::withoutTrashed()->where('body', 'force-delete fallback')->latest('id')->firstOrFail();
        $this->assertSame($originalCreatedAt, $restored->created_at->toISOString());
        $this->assertEqualsCanonicalizing($attachments, $restored->attachments);
    }

    public function test_leave_channel_dispatches_info_toast()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $other = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->set('create.name', 'LeaveToast')
            ->set('create.type', 'open')
            ->call('createChannel')
            ->assertHasNoErrors();

        $channel = Channel::where('name', 'LeaveToast')->firstOrFail();
        $channel->memberUsers()->attach($other->id, [
            'joined_at' => now(), 'entered_at' => now(), 'last_read_message_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Livewire::actingAs($other)
            ->test(Main::class)
            ->call('joinChannel', $channel->id)
            ->call('leaveChannel', $channel->id)
            ->assertDispatched('show-toast', message: 'از گروه خارج شدید', type: 'info');
    }

    public function test_mark_read_dispatches_reconcile_nudge_job()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->set('create.name', 'JobRead')
            ->set('create.type', 'open')
            ->call('createChannel')
            ->assertHasNoErrors();

        $channel = Channel::where('name', 'JobRead')->firstOrFail();
        ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'read']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('markRead', $channel->id)
            ->assertHasNoErrors();

        Queue::assertPushed(ReconcileNudge::class);

        Queue::fake();

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('markRead', $channel->id)
            ->assertHasNoErrors();

        Queue::assertNotPushed(ReconcileNudge::class);
        Queue::assertNotPushed(ReconcileEdge::class);
    }

    public function test_mark_channel_read_flips_only_the_readers_home_stat_stamp()
    {
        $reader = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        $channel = Channel::create(['name' => 'HomeStamp', 'slug' => 'home-stamp-slug', 'type' => ChannelType::Open->value, 'owner_id' => $reader->id]);
        ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $reader->id, 'body' => 'stamp']);

        $beforeReader = TabPresenter::statStamp($reader->id);
        $beforeOther = TabPresenter::statStamp($other->id);

        app(MarkChannelReadAction::class)->execute($channel->id, $reader->id);

        $this->assertNotSame($beforeReader, TabPresenter::statStamp($reader->id));
        $this->assertSame($beforeOther, TabPresenter::statStamp($other->id));
    }

    public function test_channel_owner_cannot_leave_own_channel()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = Channel::create(['name' => 'OwnerLeave', 'slug' => 'owner-leave-slug', 'type' => ChannelType::Open->value, 'owner_id' => $owner->id]);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('leaveChannel', $channel->id)
            ->assertHasNoErrors()
            ->assertNotDispatched('show-toast')
            ->assertSet('activeChannelId', $channel->id);

        $this->assertTrue(ChannelMember::where(['user_id' => $owner->id, 'channel_id' => $channel->id])->exists());
    }

    public function test_leave_channel_dispatches_reconcile_nudge_when_member_detached()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $other = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = Channel::create(['name' => 'JobLeave', 'slug' => 'job-leave', 'type' => ChannelType::Open->value, 'owner_id' => $owner->id]);
        $channel->memberUsers()->attach($other->id, [
            'joined_at' => now(), 'entered_at' => now(), 'last_read_message_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Livewire::actingAs($other)
            ->test(Main::class)
            ->call('leaveChannel', $channel->id)
            ->assertHasNoErrors();

        Queue::assertPushed(ReconcileNudge::class);
        Queue::assertPushed(ReconcileEdge::class, fn(ReconcileEdge $job) => $job->subjectId === $channel->id);
    }

    public function test_save_manage_members_dispatches_reconcile_nudge_when_roster_changes()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $keep = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = Channel::create(['name' => 'JobSync', 'slug' => 'job-sync', 'type' => ChannelType::Open->value, 'owner_id' => $owner->id]);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('openManageMembers', $channel->id)
            ->set('memberRecipientIds', [$keep->id])
            ->call('saveManageMembers')
            ->assertHasNoErrors();

        Queue::assertPushed(ReconcileNudge::class, 1);
        Queue::assertPushed(ReconcileEdge::class, fn(ReconcileEdge $job) => $job->subjectId === $channel->id);
    }

    public function test_focus_message_within_window_clears_focus_anchor_and_dispatches_record_focus()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->set('create.name', 'FocusWin')
            ->set('create.type', 'open')
            ->call('createChannel')
            ->assertHasNoErrors();

        $channel = Channel::where('name', 'FocusWin')->firstOrFail();
        foreach (range(1, 12) as $i) {
            ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => "f $i"]);
        }
        $early = ChannelMessage::where('channel_id', $channel->id)->orderBy('id')->skip(1)->firstOrFail();
        $recent = ChannelMessage::where('channel_id', $channel->id)->orderBy('id')->skip(11)->firstOrFail();

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('focusMessage', $early->id)
            ->assertSet('focusAnchorId', $early->id)
            ->call('focusMessage', $recent->id)
            ->assertSet('focusAnchorId', null)
            ->assertDispatched('record-focus', type: 'channel-message', id: $recent->id);
    }

    public function test_mount_with_open_only_still_dispatches_channel_record_focus()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = Channel::create(['name' => 'DeepLinkOnly', 'slug' => 'deep-link-only', 'type' => ChannelType::Open->value, 'owner_id' => $owner->id]);

        Livewire::withQueryParams(['open' => $channel->id])
            ->actingAs($owner)
            ->test(Main::class)
            ->assertDispatched('record-focus', type: 'channel', id: $channel->id);
    }

    public function test_mount_with_open_and_focus_msg_dispatches_only_channel_message_record_focus()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = Channel::create(['name' => 'DeepLinkMsg', 'slug' => 'deep-link-msg', 'type' => ChannelType::Open->value, 'owner_id' => $owner->id]);
        $msg = ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'deep link target']);

        Livewire::withQueryParams(['open' => $channel->id, 'focus_msg' => $msg->id])
            ->actingAs($owner)
            ->test(Main::class)
            ->assertDispatched('record-focus', type: 'channel-message', id: $msg->id)
            ->assertNotDispatched('record-focus', type: 'channel', id: $channel->id);
    }

    public function test_mount_with_open_and_invalid_focus_msg_falls_back_to_channel_record_focus()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = Channel::create(['name' => 'InvalidFocus', 'slug' => 'invalid-focus-slug', 'type' => ChannelType::Open->value, 'owner_id' => $owner->id]);

        Livewire::withQueryParams(['open' => $channel->id, 'focus_msg' => 999999])
            ->actingAs($owner)
            ->test(Main::class)
            ->assertDispatched('record-focus', type: 'channel', id: $channel->id)
            ->assertNotDispatched('record-focus', type: 'channel-message', id: 999999);
    }

    public function test_load_older_in_focus_mode_increases_focus_older_counter()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->set('create.name', 'FocusOlder')
            ->set('create.type', 'open')
            ->call('createChannel')
            ->assertHasNoErrors();

        $channel = Channel::where('name', 'FocusOlder')->firstOrFail();
        foreach (range(1, 12) as $i) {
            ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => "f $i"]);
        }
        $anchor = ChannelMessage::where('channel_id', $channel->id)->orderBy('id')->skip(1)->firstOrFail();

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('focusMessage', $anchor->id)
            ->assertSet('focusAnchorId', $anchor->id)
            ->assertSet('focusOlder', 5)
            ->call('loadOlder')
            ->assertSet('focusOlder', 30);
    }

    private function anchorChannel(int $count): array
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = $this->channelWithMembers($owner, [], 'open');

        $ids = [];
        foreach (range(1, $count) as $i) {
            $ids[] = ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => "n $i"])->id;
        }

        return [$owner, $channel, $ids];
    }

    public function test_anchor_mode_exposes_anchor_plus_five_newer_and_has_newer()
    {
        [$owner, $channel, $ids] = $this->anchorChannel(40);

        $component = Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('focusMessage', $ids[9]);

        $this->assertSame(array_slice($ids, 4, 11), array_column($component->instance()->messages, 'id'));
        $this->assertTrue($component->instance()->hasNewer);
        $component->assertSet('focusNewer', 5);
    }

    public function test_anchor_mode_stays_one_row_past_the_newer_window_and_exits_exactly_at_it()
    {
        [$owner, $channel, $ids] = $this->anchorChannel(41);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('focusMessage', $ids[9])
            ->call('loadNewer')
            ->assertSet('focusAnchorId', $ids[9])
            ->assertSet('focusNewer', 30)
            ->assertSet('hasNewer', true);

        [$owner, $channel, $ids] = $this->anchorChannel(40);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('focusMessage', $ids[9])
            ->call('loadNewer')
            ->assertSet('focusAnchorId', null)
            ->assertSet('focusNewer', 5)
            ->assertSet('hasNewer', false);
    }

    public function test_load_newer_pages_forward_then_exits_anchor_mode_at_the_tail()
    {
        [$owner, $channel, $ids] = $this->anchorChannel(80);

        $component = Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('focusMessage', $ids[9])
            ->call('loadNewer')
            ->assertSet('focusNewer', 30)
            ->assertSet('focusAnchorId', $ids[9]);

        $this->assertSame(array_slice($ids, 4, 36), array_column($component->instance()->messages, 'id'));
        $this->assertTrue($component->instance()->hasNewer);

        $component->call('loadNewer')->assertSet('focusNewer', 55);
        $this->assertTrue($component->instance()->hasNewer);

        $component->call('loadNewer')
            ->assertSet('focusAnchorId', null)
            ->assertSet('focusNewer', 5)
            ->assertSet('focusOlder', 5)
            ->assertSet('loadedLimit', 76);

        $this->assertSame(array_slice($ids, 4), array_column($component->instance()->messages, 'id'));
        $this->assertFalse($component->instance()->hasNewer);
    }

    public function test_load_newer_exit_advances_the_read_cursor_to_the_tail()
    {
        [$owner, $channel, $ids] = $this->anchorChannel(30);

        $component = Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('focusMessage', $ids[9]);

        ChannelMember::where(['user_id' => $owner->id, 'channel_id' => $channel->id])->update(['last_read_message_id' => null]);

        $component->call('loadNewer')->assertSet('focusAnchorId', null);

        $pivot = ChannelMember::where(['user_id' => $owner->id, 'channel_id' => $channel->id])->first();
        $this->assertSame(end($ids), (int) $pivot->last_read_message_id);
    }

    public function test_load_newer_is_noop_in_recent_mode()
    {
        [$owner, $channel] = $this->anchorChannel(30);

        $component = Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('loadNewer')
            ->assertSet('focusNewer', 5)
            ->assertSet('focusAnchorId', null)
            ->assertSet('loadedLimit', 10);

        $component->instance()->messages;

        $this->assertFalse($component->instance()->hasNewer);
    }

    public function test_focus_newer_resets_to_five_on_every_anchor_exit_path()
    {
        [$owner, $channel, $ids] = $this->anchorChannel(80);

        $anchored = fn() => Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('focusMessage', $ids[9])
            ->call('loadNewer')
            ->assertSet('focusNewer', 30);

        $anchored()->call('selectChannel', $channel->id)->assertSet('focusNewer', 5);
        $anchored()->call('backToList')->assertSet('focusNewer', 5);
        $anchored()->call('clearFocus')->assertSet('focusNewer', 5);
        $anchored()->call('focusMessage', $ids[40])->assertSet('focusNewer', 5);
        $anchored()->set('composer.body', 'reset probe')->call('send')->assertSet('focusNewer', 5)->assertSet('focusAnchorId', null);
    }

    public function test_updated_attachments_dispatches_attachments_updated_self_event()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $file = UploadedFile::fake()->image('attach.jpg');

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->set('composer.attachments', [$file])
            ->assertDispatched('attachments-updated');
    }

    public function test_messaging_legends_render_shared_guide_and_nudge_modal(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSee('messaging-badge-legend')
            ->assertSee('messaging-feature-legend')
            ->assertSee('راهنمای پیام‌رسان و گروه')
            ->assertSee('ویرایش و پاسخ')
            ->assertSee('سنجاق و نمایش')
            ->assertSee('رنگ‌آمیزی ردیف')
            ->assertSee('نشان پیام')
            ->assertSee('صدا و اعلان')
            ->assertSee('مالک گروه')
            ->assertSee('عضو عادی')
            ->assertSee('مدیریت اعضا')
            ->assertSee('خروج از گروه');
    }

    public function test_channel_sidebar_renders_pin_cluster_and_order_binding(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->set('create.name', 'PinProbe')
            ->set('create.type', 'open')
            ->call('createChannel')
            ->assertHasNoErrors();

        $channel = Channel::where('name', 'PinProbe')->firstOrFail();

        Livewire::withoutLazyLoading();

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->assertSeeHtml('$store.pinned.togglePin(' . $channel->id . ', \'channel\')')
            ->assertSeeHtml('$store.sound.toggleMute(' . $channel->id . ', \'channel\')')
            ->assertSeeHtml('$store.pinned.isPinned(' . $channel->id . ', \'channel\') ? -1 : 0')
            ->assertSeeHtml('flex items-center gap-1 pointer-events-none')
            ->assertSeeHtml('font-fill rotate-45')
            ->assertSeeHtml('bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]')
            ->assertSee('push_pin')
            ->assertSeeHtml(trim(json_encode('گروه'), '"'))
            ->assertSeeHtml('x-data="{ tagOpen: false }"')
            ->assertSeeHtml('$store.tagged.tagBg(' . $channel->id . ', \'channel\')')
            ->assertSeeHtml('tagOpen = !tagOpen')
            ->assertSeeHtml('$store.tagged.palette')
            ->assertSeeHtml('$store.tagged.setTag(' . $channel->id . ', i, \'channel\')')
            ->assertSeeHtml('$store.tagged.clearTag(' . $channel->id . ', \'channel\')')
            ->assertSeeHtml('>palette<');
    }

    public function test_channel_message_actions_render_bookmark_button_marker_and_ring(): void
    {
        $msg = [
            'id' => 909, 'is_mine' => true, 'body' => 'x', 'body_html' => 'x',
            'can_edit' => false, 'can_delete' => false, 'sender_name' => 'S',
            'bubble_radius' => 'rounded-2xl', 'is_last' => false, 'is_edited' => false,
            'mentions_you' => false, 'is_read' => true, 'is_read_by_all' => true,
            'readers' => [], 'read_count' => 0, 'datetime' => '', 'time' => '', 'reply_to' => null,
        ];

        $html = view('livewire.dashboard.messaging.message-actions', ['msg' => $msg])->render();

        $this->assertStringContainsString("\$store.pinned.togglePin(909, 'message')", $html);
        $this->assertStringContainsString("\$store.pinned.isPinned(909, 'message')", $html);
        $this->assertStringContainsString('>bookmark<', $html);
        $this->assertStringContainsString('font-fill', $html);
        $this->assertStringContainsString('-translate-y-1/2 translate-x-1/2', $html);
        $this->assertStringContainsString(trim(json_encode('نشان کردن پیام'), '"'), $html);
        $this->assertStringContainsString(trim(json_encode('برداشتن نشان پیام'), '"'), $html);

        $blade = file_get_contents(base_path('resources/views/livewire/dashboard/channel/messages.blade.php'));
        $this->assertStringContainsString("!ring-2 !ring-[var(--md-sys-color-primary)]", $blade);
        $this->assertStringContainsString("\$store.pinned.isPinned(@js(\$msg['id']), @js('message'))", $blade);
    }

    public function test_reader_summary_lists_all_names_caps_shown_at_three_and_counts_extra(): void
    {
        $presenter = new \App\Livewire\Dashboard\Channel\Presentation\ChannelPresenter();

        $msg = [
            'readers' => [
                ['name' => 'آرش', 'avatar' => null],
                ['name' => 'سارا', 'avatar' => null],
                ['name' => null, 'avatar' => null],
                ['name' => 'مهدی', 'avatar' => null],
                ['name' => 'نیما', 'avatar' => null],
            ],
            'read_count' => 5,
        ];

        $rs = $presenter->readerSummary($msg);

        $this->assertSame(['آرش', 'سارا', '—', 'مهدی', 'نیما'], $rs['names'], 'names must cover ALL readers with a — fallback, not just the shown three');
        $this->assertCount(3, $rs['shown'], 'shown is capped at three avatars');
        $this->assertSame('آرش', $rs['shown'][0]['name']);
        $this->assertSame(2, $rs['extra'], 'extra = read_count - count(shown)');

        $empty = $presenter->readerSummary(['readers' => [], 'read_count' => 0]);
        $this->assertSame([], $empty['names']);
        $this->assertSame([], $empty['shown']);
        $this->assertSame(0, $empty['extra']);
    }

    private function joinedChannel(User $viewer, string $name): Channel
    {
        $owner = User::factory()->create(['status' => 'active']);
        $channel = Channel::create(['name' => $name, 'slug' => \Illuminate\Support\Str::slug($name) . '-' . uniqid(), 'type' => 'open', 'owner_id' => $owner->id]);
        $channel->memberUsers()->attach($viewer->id, [
            'joined_at' => now(), 'entered_at' => now(), 'last_read_message_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $channel;
    }

    public function test_sidebar_shows_a_load_more_button_only_past_the_default_page_size(): void
    {
        $viewer = User::factory()->create(['status' => 'active']);
        foreach (range(1, 35) as $i) {
            $this->joinedChannel($viewer, "Channel $i");
        }

        $component = Livewire::actingAs($viewer)->test(Main::class);

        $component->assertSet('channelsLimit', 30)
            ->assertSeeHtml('نمایش بیشتر');

        $component->call('loadMoreChannels')
            ->assertSet('channelsLimit', 60)
            ->assertDontSeeHtml('نمایش بیشتر');
    }

    public function test_sidebar_load_more_button_is_scoped_to_the_sidebar_island(): void
    {
        $viewer = User::factory()->create(['status' => 'active']);
        foreach (range(1, 35) as $i) {
            $this->joinedChannel($viewer, "Channel $i");
        }

        Livewire::actingAs($viewer)->test(Main::class)
            ->assertSeeHtml('$wire.$island(&#039;sidebar&#039;).loadMoreChannels()');
    }

    public function test_messages_load_more_button_source_is_scoped_to_the_messages_island(): void
    {
        $blade = file_get_contents(base_path('resources/views/livewire/dashboard/channel/messages.blade.php'));

        $this->assertStringContainsString('action="loadOlder"', $blade);
        $this->assertStringContainsString('action="loadNewer"', $blade);
        $this->assertStringContainsString('wrap="loadNewer"', $blade);
        $this->assertStringContainsString('wire:key="load-newer"', $blade);
        $this->assertStringContainsString('island="messages"', $blade);
    }

    public function test_sidebar_page_size_does_not_grow_past_the_actual_channel_count(): void
    {
        $viewer = User::factory()->create(['status' => 'active']);
        foreach (range(1, 10) as $i) {
            $this->joinedChannel($viewer, "Small $i");
        }

        Livewire::actingAs($viewer)->test(Main::class)
            ->assertDontSeeHtml('نمایش بیشتر');
    }

    public function test_searching_resets_the_sidebar_page_size(): void
    {
        $viewer = User::factory()->create(['status' => 'active']);
        foreach (range(1, 35) as $i) {
            $this->joinedChannel($viewer, "Channel $i");
        }

        Livewire::actingAs($viewer)->test(Main::class)
            ->call('loadMoreChannels')
            ->assertSet('channelsLimit', 60)
            ->set('search', 'Channel 1')
            ->assertSet('channelsLimit', 30);
    }

    public function test_setting_filter_resets_the_sidebar_page_size(): void
    {
        $viewer = User::factory()->create(['status' => 'active']);
        foreach (range(1, 35) as $i) {
            $this->joinedChannel($viewer, "Channel $i");
        }

        Livewire::actingAs($viewer)->test(Main::class)
            ->call('loadMoreChannels')
            ->assertSet('channelsLimit', 60)
            ->call('setFilter', 'unread')
            ->assertSet('channelsLimit', 30);
    }

    private function channelViaLivewire(User $owner, string $name): Channel
    {
        Livewire::actingAs($owner)->test(Main::class)
            ->set('create.name', $name)
            ->set('create.type', 'open')
            ->call('createChannel')
            ->assertHasNoErrors();

        return Channel::where('name', $name)->firstOrFail();
    }

    private function msg(array $o): array
    {
        return array_merge([
            'id' => 1,
            'sender_id' => 1,
            'body' => '',
            'created_at' => now()->toDateTimeString(),
            'sender' => ['name' => 'S'],
        ], $o);
    }

    public function test_presenter_wraps_mentions_and_flags_mentions_you()
    {
        $p = new ChannelPresenter();
        $map = ['Alice' => [3, 5], 'Bob' => [7]];

        $out = $p->messages([$this->msg(['body' => 'hi @Alice and @Bob'])], 5, 60, [], $map);

        $this->assertStringContainsString('<span class="mention">@Alice</span>', $out[0]['body_html']);
        $this->assertStringContainsString('<span class="mention">@Bob</span>', $out[0]['body_html']);
        $this->assertTrue($out[0]['mentions_you']);
    }

    public function test_mention_skipped_inside_anchor_so_url_does_not_double_wrap()
    {
        $p = new ChannelPresenter();
        $map = ['Name' => [9]];

        $out = $p->messages([$this->msg(['body' => 'see https://x.com/@Name here @Name'])], 9, 60, [], $map);

        $this->assertSame(1, substr_count($out[0]['body_html'], '<span class="mention">'));
        $this->assertStringContainsString('href="https://x.com/@Name"', $out[0]['body_html']);
        $this->assertTrue($out[0]['mentions_you']);
    }

    public function test_non_member_mention_is_not_highlighted()
    {
        $p = new ChannelPresenter();
        $map = ['Alice' => [3]];

        $out = $p->messages([$this->msg(['body' => 'hi @Alice @Ghost'])], 99, 60, [], $map);

        $this->assertStringContainsString('<span class="mention">@Alice</span>', $out[0]['body_html']);
        $this->assertStringNotContainsString('<span class="mention">@Ghost', $out[0]['body_html']);
        $this->assertStringContainsString('@Ghost', $out[0]['body_html']);
        $this->assertFalse($out[0]['mentions_you']);
    }

    public function test_persian_and_metachar_member_names_match()
    {
        $p = new ChannelPresenter();
        $map = ['آرش' => [2], 'C++' => [4]];

        $out = $p->messages([$this->msg(['body' => '@آرش و @C++'])], 2, 60, [], $map);

        $this->assertStringContainsString('<span class="mention">@آرش</span>', $out[0]['body_html']);
        $this->assertStringContainsString('<span class="mention">@C++</span>', $out[0]['body_html']);
        $this->assertTrue($out[0]['mentions_you']);
    }

    public function test_longest_name_wins_over_prefix_name()
    {
        $p = new ChannelPresenter();
        $map = ['Al' => [11], 'Alice' => [22]];

        $out = $p->messages([$this->msg(['body' => 'hey @Alice'])], 22, 60, [], $map);

        $this->assertStringContainsString('<span class="mention">@Alice</span>', $out[0]['body_html']);
        $this->assertStringNotContainsString('<span class="mention">@Al</span>', $out[0]['body_html']);
        $this->assertTrue($out[0]['mentions_you']);
    }

    public function test_presenter_type_flags_classify_text_image_and_pdf_rows()
    {
        $p = new ChannelPresenter();

        $text = $p->messages([$this->msg(['body' => 'plain text'])], 1, 60);
        $this->assertSame('t', $text[0]['type_flags']);

        $image = $p->messages([$this->msg([
            'body' => 'see attached',
            'attachments' => [['path' => 'channel_messages/x/y.png', 'name' => 'y.png', 'mime' => 'image/png', 'size' => 2048]],
        ])], 1, 60);
        $this->assertSame('t i', $image[0]['type_flags']);

        $pdf = $p->messages([$this->msg([
            'attachments' => [['path' => 'channel_messages/x/y.pdf', 'name' => 'y.pdf', 'mime' => 'application/pdf', 'size' => 2048]],
        ])], 1, 60);
        $this->assertSame('p', $pdf[0]['type_flags']);
    }

    public function test_mention_member_map_preserves_duplicate_name_ids()
    {
        $owner = User::factory()->create(['name' => 'Ali', 'status' => 'active', 'presence' => 'onsite']);
        $john1 = User::factory()->create(['name' => 'John', 'status' => 'active', 'presence' => 'onsite']);
        $john2 = User::factory()->create(['name' => 'John', 'status' => 'active', 'presence' => 'onsite']);

        $channel = $this->channelViaLivewire($owner, 'MentionMap');
        $channel->memberUsers()->attach([$john1->id, $john2->id]);

        $map = Livewire::actingAs($owner)->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->instance()->mentionMemberMap;

        $this->assertEqualsCanonicalizing([$john1->id, $john2->id], $map['John'] ?? []);
        $this->assertContains($owner->id, $map['Ali'] ?? []);
    }

    public function test_mention_member_presence_reuses_mention_member_map_query()
    {
        $owner = User::factory()->create(['name' => 'Ali', 'status' => 'active', 'presence' => 'onsite']);
        $bob = User::factory()->create(['name' => 'Bob', 'status' => 'active', 'presence' => 'onsite']);

        $channel = $this->channelViaLivewire($owner, 'PresenceShare');
        $channel->memberUsers()->attach([$bob->id]);

        $test = Livewire::actingAs($owner)->test(Main::class)->call('selectChannel', $channel->id);

        try {
            DB::flushQueryLog();
            DB::enableQueryLog();

            $map = $test->instance()->mentionMemberMap();
            $presence = $test->instance()->mentionMemberPresence();

            $memberQueries = collect(DB::getQueryLog())->pluck('query')
                ->filter(fn($q) => str_contains($q, 'select `users`.`id`, `users`.`name`, `users`.`presence`'));
            $this->assertCount(1, $memberQueries, 'mentionMemberMap and mentionMemberPresence must share one underlying query');
        } finally {
            DB::disableQueryLog();
        }

        $this->assertContains($bob->id, $map['Bob'] ?? []);
        $this->assertNotNull($presence['Bob']['presence_label']);
    }

    public function test_mention_member_names_stays_empty_until_explicitly_loaded()
    {
        $owner = User::factory()->create(['name' => 'Ali', 'status' => 'active', 'presence' => 'onsite']);
        $bob = User::factory()->create(['name' => 'Bob', 'status' => 'active', 'presence' => 'onsite']);

        $channel = $this->channelViaLivewire($owner, 'NamesWire');
        $channel->memberUsers()->attach([$bob->id]);

        $test = Livewire::actingAs($owner)->test(Main::class)->call('selectChannel', $channel->id);
        $this->assertSame([], $test->get('mentionMemberNames'), 'selecting a channel alone must not eagerly load the mention pool');

        $test->call('loadMentionMemberNames');
        $this->assertEqualsCanonicalizing(['Ali', 'Bob'], $test->get('mentionMemberNames'));
    }

    public function test_mention_member_names_updates_after_switching_active_channel()
    {
        $owner = User::factory()->create(['name' => 'Ali', 'status' => 'active', 'presence' => 'onsite']);
        $bob = User::factory()->create(['name' => 'Bob', 'status' => 'active', 'presence' => 'onsite']);
        $carol = User::factory()->create(['name' => 'Carol', 'status' => 'active', 'presence' => 'onsite']);

        $channelA = $this->channelViaLivewire($owner, 'SwitchChanA');
        $channelA->memberUsers()->attach([$bob->id]);

        $channelB = $this->channelViaLivewire($owner, 'SwitchChanB');
        $channelB->memberUsers()->attach([$carol->id]);

        $test = Livewire::actingAs($owner)->test(Main::class)
            ->call('selectChannel', $channelA->id)
            ->call('loadMentionMemberNames');
        $this->assertEqualsCanonicalizing(['Ali', 'Bob'], $test->get('mentionMemberNames'));

        $test->call('selectChannel', $channelB->id)->call('loadMentionMemberNames');
        $this->assertEqualsCanonicalizing(['Ali', 'Carol'], $test->get('mentionMemberNames'));
        $this->assertNotContains('Bob', $test->get('mentionMemberNames'));
    }

    public function test_mention_member_names_query_only_fires_on_explicit_load()
    {
        $owner = User::factory()->create(['name' => 'Ali', 'status' => 'active', 'presence' => 'onsite']);
        $bob = User::factory()->create(['name' => 'Bob', 'status' => 'active', 'presence' => 'onsite']);

        $channel = $this->channelViaLivewire($owner, 'PerfGate');
        $channel->memberUsers()->attach([$bob->id]);

        $test = Livewire::actingAs($owner)->test(Main::class);

        try {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $test->call('selectChannel', $channel->id);
            $queries = collect(DB::getQueryLog())->pluck('query')->filter(fn($q) => str_contains($q, 'select `users`.`id`, `users`.`name`, `users`.`presence`'));
            $this->assertCount(0, $queries, 'selecting/viewing a channel must never query channel membership for the mention pool on its own');

            DB::flushQueryLog();
            $test->call('loadMentionMemberNames');
            $queries = collect(DB::getQueryLog())->pluck('query')->filter(fn($q) => str_contains($q, 'select `users`.`id`, `users`.`name`, `users`.`presence`'));
            $this->assertGreaterThan(0, $queries->count(), 'the explicit action must actually load the roster when the user opens the @ picker');
        } finally {
            DB::disableQueryLog();
        }
    }

    public function test_unread_mention_creates_global_edge_toast_and_clears_after_read()
    {
        $owner = User::factory()->create(['name' => 'Owner', 'status' => 'active', 'presence' => 'onsite']);
        $alice = User::factory()->create(['name' => 'Alice', 'status' => 'active', 'presence' => 'onsite']);

        $channel = $this->channelViaLivewire($owner, 'ToastChan');
        $channel->memberUsers()->attach([$alice->id]);

        ChannelMessage::create([
            'channel_id' => $channel->id,
            'sender_id' => $alice->id,
            'body' => 'hey @Owner check this',
        ]);

        $key = (new ChannelToast())->getKey();
        EdgeService::reconcile($key, Channel::class, $channel->id);

        $toast = collect(EdgeService::forUser($owner->id))
            ->firstWhere('subject_id', (string) $channel->id);

        $this->assertNotNull($toast);
        $this->assertSame('شما را منشن کرده است', $toast['title']);
        $this->assertSame('Alice', $toast['body']);
        $this->assertSame('alternate_email', $toast['icon']);

        Queue::fake();

        Livewire::actingAs($owner)->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->assertHasNoErrors();
        Queue::assertPushed(ReconcileEdge::class, fn(ReconcileEdge $job) => $job->subjectId === $channel->id);

        EdgeService::reconcile($key, Channel::class, $channel->id);
        $this->assertCount(
            0,
            collect(EdgeService::forUser($owner->id))->where('subject_id', (string) $channel->id)->all()
        );
    }

    public function test_self_sent_mention_does_not_toast()
    {
        $owner = User::factory()->create(['name' => 'Owner', 'status' => 'active', 'presence' => 'onsite']);
        $alice = User::factory()->create(['name' => 'Alice', 'status' => 'active', 'presence' => 'onsite']);

        $channel = $this->channelViaLivewire($owner, 'SelfChan');
        $channel->memberUsers()->attach([$alice->id]);

        ChannelMessage::create([
            'channel_id' => $channel->id,
            'sender_id' => $owner->id,
            'body' => 'note to @Owner and @Alice',
        ]);

        EdgeService::reconcile((new ChannelToast())->getKey(), Channel::class, $channel->id);

        $this->assertCount(
            0,
            collect(EdgeService::forUser($owner->id))->where('subject_id', (string) $channel->id)->all()
        );
    }

    public function test_autocomplete_pool_contains_active_channel_member_names()
    {
        $owner = User::factory()->create(['name' => 'Owner', 'status' => 'active', 'presence' => 'onsite']);
        $alice = User::factory()->create(['name' => 'Alice', 'status' => 'active', 'presence' => 'onsite']);

        $channel = $this->channelViaLivewire($owner, 'AutocompleteChan');
        $channel->memberUsers()->attach([$alice->id]);

        $map = Livewire::actingAs($owner)->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->instance()->mentionMemberMap;

        $pool = json_encode(array_keys($map), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        $this->assertEqualsCanonicalizing(['Owner', 'Alice'], json_decode($pool, true));
    }

    public function test_focus_sender_loads_latest_message_by_name_beyond_window()
    {
        $owner = User::factory()->create(['name' => 'Owner', 'status' => 'active', 'presence' => 'onsite']);
        $alice = User::factory()->create(['name' => 'Alice', 'status' => 'active', 'presence' => 'onsite']);

        $channel = $this->channelViaLivewire($owner, 'FocusSenderChan');
        $channel->memberUsers()->attach([$alice->id]);

        $aliceMsg = ChannelMessage::create([
            'channel_id' => $channel->id,
            'sender_id' => $alice->id,
            'body' => 'alice early',
        ]);
        for ($i = 0; $i < 11; $i++) {
            ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => "owner $i"]);
        }

        $c = Livewire::actingAs($owner)->test(Main::class)->call('selectChannel', $channel->id);
        $this->assertNull($c->instance()->focusAnchorId, 'recent window must not anchor by default');

        $c->call('focusSender', 'Alice');
        $this->assertSame((int) $aliceMsg->id, $c->instance()->focusAnchorId, 'focusSender must anchor on Alice\'s latest message when it is beyond the loaded window');
    }

    public function test_focus_sender_within_window_does_not_paginate()
    {
        $owner = User::factory()->create(['name' => 'Owner', 'status' => 'active', 'presence' => 'onsite']);
        $alice = User::factory()->create(['name' => 'Alice', 'status' => 'active', 'presence' => 'onsite']);

        $channel = $this->channelViaLivewire($owner, 'FocusWithinChan');
        $channel->memberUsers()->attach([$alice->id]);

        ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'o1']);
        ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $alice->id, 'body' => 'a1']);

        $c = Livewire::actingAs($owner)->test(Main::class)->call('selectChannel', $channel->id);
        $c->call('focusSender', 'Alice');
        $this->assertNull($c->instance()->focusAnchorId, 'within-window sender focus must not trigger anchor pagination');
    }

    public function test_focus_sender_unknown_name_is_noop()
    {
        $owner = User::factory()->create(['name' => 'Owner', 'status' => 'active', 'presence' => 'onsite']);
        $alice = User::factory()->create(['name' => 'Alice', 'status' => 'active', 'presence' => 'onsite']);

        $channel = $this->channelViaLivewire($owner, 'FocusGhostChan');
        $channel->memberUsers()->attach([$alice->id]);

        ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $alice->id, 'body' => 'hi']);

        $c = Livewire::actingAs($owner)->test(Main::class)->call('selectChannel', $channel->id);
        $c->call('focusSender', 'Ghost');
        $this->assertNull($c->instance()->focusAnchorId, 'unknown sender name must not change focus state');
    }

    public function test_clear_focus_resets_anchor_and_limit()
    {
        $owner = User::factory()->create(['name' => 'Owner', 'status' => 'active', 'presence' => 'onsite']);
        $alice = User::factory()->create(['name' => 'Alice', 'status' => 'active', 'presence' => 'onsite']);

        $channel = $this->channelViaLivewire($owner, 'ClearFocusChan');
        $channel->memberUsers()->attach([$alice->id]);

        ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $alice->id, 'body' => 'alice early']);
        for ($i = 0; $i < 11; $i++) {
            ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => "owner $i"]);
        }

        $c = Livewire::actingAs($owner)->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('focusSender', 'Alice');
        $this->assertNotNull($c->instance()->focusAnchorId);

        $c->call('clearFocus');
        $this->assertNull($c->instance()->focusAnchorId);
        $this->assertSame(10, $c->instance()->loadedLimit);
    }

    public function test_probe_join_private_aborts()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $other = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = Channel::create(['name' => 'Priv', 'slug' => 'priv-slug', 'type' => 'private', 'owner_id' => $owner->id]);

        Livewire::actingAs($other)->test(Main::class)->call('joinChannel', $channel->id);

        $this->assertFalse(ChannelMember::where(['user_id' => $other->id, 'channel_id' => $channel->id])->exists(), 'joining a private channel as a non-owner must not create a member row');
    }

    public function test_send_sms_rejects_inactive_user_via_visible_on_board_scope(): void
    {
        $inactive = User::factory()->create(['status' => 'inactive', 'type' => 'employee']);
        $sms = Mockery::mock(SmsChannel::class);
        $sms->shouldNotReceive('text');

        $result = app(SendSmsAction::class)->execute((string) $inactive->id, $sms);

        $this->assertSame('error', $result['type']);
        $this->assertSame('کاربر یافت نشد.', $result['message']);
    }

    public function test_send_sms_rejects_guest_user_via_visible_on_board_scope(): void
    {
        $guest = User::factory()->create(['status' => 'active', 'type' => 'guest']);
        $sms = Mockery::mock(SmsChannel::class);
        $sms->shouldNotReceive('text');

        $result = app(SendSmsAction::class)->execute((string) $guest->id, $sms);

        $this->assertSame('error', $result['type']);
        $this->assertSame('کاربر یافت نشد.', $result['message']);
    }

    public function test_send_sms_resolves_active_non_guest_user_without_404_and_skips_send_when_no_phone(): void
    {
        $user = User::factory()->create(['status' => 'active', 'type' => 'employee']);
        $sms = Mockery::mock(SmsChannel::class);
        $sms->shouldReceive('isConfigured')->andReturn(true);
        $sms->shouldNotReceive('text');

        $result = app(SendSmsAction::class)->execute((string) $user->id, $sms);

        $this->assertSame('error', $result['type']);
    }

    public function test_ping_typing_adds_self_to_the_channels_typing_map_excluding_self_on_read(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $other = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = Channel::create(['name' => 'TypingCh', 'slug' => 'typing-ch-slug', 'type' => 'open', 'owner_id' => $owner->id]);
        $channel->memberUsers()->attach($other->id, [
            'joined_at' => now(), 'entered_at' => now(), 'last_read_message_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        Cache::forget("typing:ch:{$channel->id}");

        Livewire::actingAs($owner)->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('pingTyping');

        $ownTypingView = Livewire::actingAs($owner)->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->instance()->typingMembers;
        $this->assertSame([], $ownTypingView, 'a typer must not see themself in their own typingMembers list');

        $othersView = Livewire::actingAs($other)->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->instance()->typingMembers;
        $this->assertSame([$owner->name], $othersView);

        Cache::forget("typing:ch:{$channel->id}");
    }

    public function test_typing_members_is_empty_without_an_open_channel_or_expired_entries(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = Channel::create(['name' => 'TypingCh2', 'slug' => 'typing-ch2-slug', 'type' => 'open', 'owner_id' => $owner->id]);

        $noChannelOpen = Livewire::actingAs($owner)->test(Main::class)->instance()->typingMembers;
        $this->assertSame([], $noChannelOpen);

        Cache::put("typing:ch:{$channel->id}", [999999 => ['Ghost', now()->subMinute()->timestamp]], now()->addSeconds(60));
        $expiredExcluded = Livewire::actingAs($owner)->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->instance()->typingMembers;
        $this->assertSame([], $expiredExcluded);

        Cache::forget("typing:ch:{$channel->id}");
    }

    public function test_typing_with_an_empty_body_does_not_trigger_validation_errors(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = Channel::create(['name' => 'TypingNoErr', 'slug' => 'typing-no-err-slug', 'type' => 'open', 'owner_id' => $owner->id]);

        Livewire::actingAs($owner)->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->set('composer.body', '')
            ->call('pingTyping')
            ->assertHasNoErrors();
    }

    public function test_sending_a_channel_message_clears_the_senders_own_typing_entry(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = Channel::create(['name' => 'TypingCh3', 'slug' => 'typing-ch3-slug', 'type' => 'open', 'owner_id' => $owner->id]);

        Livewire::actingAs($owner)->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('pingTyping')
            ->set('composer.body', 'stopping to send this')
            ->call('send');

        $map = Cache::get("typing:ch:{$channel->id}", []);
        $this->assertArrayNotHasKey($owner->id, $map);

        Cache::forget("typing:ch:{$channel->id}");
    }

    public function test_poll_digest_changes_only_for_my_own_channels_version(): void
    {
        // PollDigestService::bump() is called from ChannelMessage's model-event hooks wrapped in
        // DB::afterCommit() (correct for production — see channelPattern.md's digest section), but
        // this project's tests wrap everything in one never-committed transaction (livewireTestPattern
        // §1), so afterCommit callbacks never fire here. Bump directly to test pollDigest()'s own
        // combination formula (membership fingerprint + summed versions + own read-marker) in isolation.
        $viewer = User::factory()->create(['status' => 'active']);
        $myChannel = $this->joinedChannel($viewer, 'Mine');
        $otherViewer = User::factory()->create(['status' => 'active']);
        $unrelatedChannel = $this->joinedChannel($otherViewer, 'Unrelated');

        $before = Livewire::actingAs($viewer)->test(Main::class)->instance()->pollDigest();

        PollDigestService::bump("ch:tv:{$unrelatedChannel->id}");
        $this->assertSame($before, Livewire::actingAs($viewer)->test(Main::class)->instance()->pollDigest());

        PollDigestService::bump("ch:tv:{$myChannel->id}");
        $this->assertNotSame($before, Livewire::actingAs($viewer)->test(Main::class)->instance()->pollDigest());
    }

    public function test_refresh_active_stamp_is_stable_when_unchanged_and_moves_when_a_channel_message_arrives(): void
    {
        $viewer = User::factory()->create(['status' => 'active']);
        $channel = $this->joinedChannel($viewer, 'Deep History');
        ChannelMessage::factory()->count(5)->create(['channel_id' => $channel->id, 'sender_id' => $viewer->id, 'body' => 'seed']);

        $component = Livewire::actingAs($viewer)->test(Main::class)->call('selectChannel', $channel->id);

        for ($i = 0; $i < 8; $i++) {
            $component->call('loadOlder');
        }
        $component->assertSet('loadedLimit', 210);

        $component->call('refreshActive');
        $stampAfterFirst = $component->get('pollStamp');
        $this->assertNotNull($stampAfterFirst);

        $component->call('refreshActive');
        $this->assertSame($stampAfterFirst, $component->get('pollStamp'));

        ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $viewer->id, 'body' => 'new one']);

        $component->call('refreshActive');
        $this->assertNotSame($stampAfterFirst, $component->get('pollStamp'));
    }

    public function test_owner_with_only_self_can_delete_channel()
    {
        $owner = User::factory()->create(['status' => 'active']);
        $channel = Channel::factory()->ownedBy($owner)->create();

        $this->assertSame(1, $channel->memberUsers()->count());

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->assertSet('activeChannelId', $channel->id)
            ->call('deleteChannel', $channel->id)
            ->assertSet('activeChannelId', null);

        $this->assertSoftDeleted('channels', ['id' => $channel->id]);
    }

    public function test_owner_with_other_members_cannot_delete_channel()
    {
        $owner = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        $channel = Channel::factory()->ownedBy($owner)->create();
        $channel->memberUsers()->attach($other->id, [
            'joined_at' => now(),
            'entered_at' => now(),
            'last_read_message_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id)
            ->call('deleteChannel', $channel->id)
            ->assertSet('activeChannelId', $channel->id);

        $this->assertDatabaseHas('channels', ['id' => $channel->id, 'deleted_at' => null]);
    }

    public function test_non_owner_cannot_delete_channel()
    {
        $owner = User::factory()->create(['status' => 'active']);
        $member = User::factory()->create(['status' => 'active']);
        $channel = Channel::factory()->ownedBy($owner)->create();
        $channel->memberUsers()->attach($member->id, [
            'joined_at' => now(),
            'entered_at' => now(),
            'last_read_message_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $channel->memberUsers()->detach($owner->id);

        Livewire::actingAs($member)
            ->test(Main::class)
            ->call('deleteChannel', $channel->id);

        $this->assertDatabaseHas('channels', ['id' => $channel->id, 'deleted_at' => null]);
    }

    public function test_deleted_channel_disappears_from_sidebar_and_resets_active_id()
    {
        $owner = User::factory()->create(['status' => 'active']);
        $channel = Channel::factory()->ownedBy($owner)->create();

        $component = Livewire::actingAs($owner)
            ->test(Main::class)
            ->call('selectChannel', $channel->id);

        $this->assertContains($channel->id, array_column($component->instance()->channels(), 'id'));

        $component->call('deleteChannel', $channel->id)
            ->assertSet('activeChannelId', null)
            ->assertSet('mobileShowChat', false);

        $this->assertNotContains($channel->id, array_column($component->instance()->channels(), 'id'));
    }

    private function channelWithMembers(User $owner, array $members = [], string $type = 'private'): Channel
    {
        $channel = Channel::factory()->ownedBy($owner)->ofType(ChannelType::from($type))->create();

        foreach ($members as $member) {
            $channel->memberUsers()->attach($member->id, ['joined_at' => now(), 'entered_at' => now()]);
        }

        return $channel;
    }

    public function test_an_inactive_sender_is_flagged_in_message_arrays_and_dropped_from_mentions_but_kept_in_the_old_mention_map(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $leaver = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'name' => 'Leaver Person']);
        $channel = $this->channelWithMembers($owner, [$leaver]);
        ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $leaver->id, 'body' => 'before leaving']);
        ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'still here']);
        $leaver->forceFill(['status' => 'inactive'])->saveQuietly();

        $component = Livewire::actingAs($owner)->test(Main::class)->call('selectChannel', $channel->id);
        $messages = collect($component->instance()->messages());

        $this->assertTrue($messages->firstWhere('body', 'before leaving')['sender']['inactive']);
        $this->assertFalse($messages->firstWhere('body', 'still here')['sender']['inactive']);
        $this->assertArrayNotHasKey('Leaver Person', $component->instance()->mentionMemberPresence());
        $this->assertArrayHasKey('Leaver Person', $component->instance()->mentionMemberMap());
        $this->assertTrue(collect($component->instance()->channelMembers())->firstWhere('id', $leaver->id)['inactive']);
    }

    public function test_an_inactive_user_can_neither_post_nor_join(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $open = $this->channelWithMembers($owner, [], 'open');
        $ghost = User::factory()->create(['status' => 'inactive', 'presence' => 'onsite']);
        $this->actingAs($ghost);

        $this->assertFalse(app(\App\Livewire\Dashboard\Channel\Actions\JoinChannelAction::class)->execute($open->id, $ghost->id));
        $this->assertFalse(ChannelMember::where(['user_id' => $ghost->id, 'channel_id' => $open->id])->exists());

        $form = Livewire::test(Main::class)->instance()->composer;
        $form->body = 'boo';
        try {
            app(SendChannelMessageAction::class)->execute($form, $open->id);
            $this->fail('inactive sender must be refused');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        $this->assertSame(0, ChannelMessage::where('channel_id', $open->id)->count());
    }

    public function test_a_project_chat_can_not_be_deleted_from_the_channel_module_nor_made_open(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $project = \App\Models\Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $channel = app(\App\Services\ProjectTask\ChannelProvisioner::class)->resolve($project->fresh());
        $this->assertTrue($channel->isProjectLinked());

        $this->assertFalse(app(\App\Livewire\Dashboard\Channel\Actions\DeleteChannelAction::class)->execute($channel->id, $owner->id));
        $this->assertFalse(app(\App\Livewire\Dashboard\Channel\Actions\ForceDeleteChannelAction::class)->execute($channel));
        $this->assertNotNull(Channel::find($channel->id));

        Channel::find($channel->id)->update(['type' => ChannelType::Open]);
        $this->assertSame(ChannelType::Private, Channel::find($channel->id)->type);
    }

    public function test_every_own_in_window_message_offers_edit_and_delete_and_the_window_is_enforced_server_side(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $channel = $this->channelWithMembers($owner);
        $older = ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'older own']);
        $newer = ChannelMessage::create(['channel_id' => $channel->id, 'sender_id' => $owner->id, 'body' => 'newer own']);

        $flags = function () use ($owner, $channel) {
            $component = Livewire::actingAs($owner)->test(Main::class)->call('selectChannel', $channel->id);
            $instance = $component->instance();
            $group = $instance->presenter->messageGroup(now()->toDateString(), $instance->messages(), $owner->id, $instance->editTimeLimit);

            return collect($group['messages'])->keyBy('id');
        };

        $inWindow = $flags();
        foreach ([$older->id, $newer->id] as $id) {
            $this->assertTrue($inWindow[$id]['can_edit']);
            $this->assertTrue($inWindow[$id]['can_delete']);
        }

        $this->travel(11)->minutes();

        $late = $flags();
        $this->assertFalse($late[$older->id]['can_edit']);
        $this->assertFalse($late[$newer->id]['can_delete']);

        $form = Livewire::test(Main::class)->instance()->edit;
        $form->editingBody = 'late edit';
        $this->assertFalse(app(\App\Livewire\Dashboard\Channel\Actions\SaveEditChannelMessageAction::class)->execute($form, $older->id));
        $this->assertFalse(app(\App\Livewire\Dashboard\Channel\Actions\DeleteChannelMessageAction::class)->execute($older->id, 600));
        $this->assertSame('older own', ChannelMessage::find($older->id)->body);
    }
}
