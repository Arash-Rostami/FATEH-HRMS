<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Jobs\ReconcileNudge;
use App\Livewire\Dashboard\Contact\Actions\ForceDeleteMessageAction;
use App\Livewire\Dashboard\Contact\Actions\MarkMessagesAsReadAction;
use App\Livewire\Dashboard\Contact\Actions\SearchMessagesAction;
use App\Livewire\Dashboard\Contact\Actions\SendMessageAction;
use App\Livewire\Dashboard\Contact\Main;
use App\Models\Message;
use App\Models\Profile;
use App\Models\ProfileDetail;
use App\Models\User;
use App\Services\Messaging\PollDigestService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class ContactTest extends TestCase
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

    public function test_contact_messenger_crud_lifecycle()
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($me)
            ->test(Main::class)
            ->assertHasNoErrors();

        Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id)
            ->set('composer.body', 'Tinker contact probe')
            ->call('send')
            ->assertHasNoErrors();

        $message = Message::where('body', 'Tinker contact probe')->firstOrFail();
        $this->assertSame($me->id, $message->sender_id);
        $this->assertSame($contact->id, $message->recipient_id);

        Livewire::actingAs($contact)
            ->test(Main::class)
            ->call('selectContact', $me->id)
            ->assertHasNoErrors();

        $this->assertNotNull($message->refresh()->read_at);

        Livewire::actingAs($me)
            ->test(Main::class)
            ->set('edit.editingBody', 'Tinker contact probe (edited)')
            ->call('saveEdit', $message->id)
            ->assertHasNoErrors();

        $message->refresh();
        $this->assertSame('Tinker contact probe (edited)', $message->body);
        $this->assertTrue((bool) $message->is_edited);

        Livewire::actingAs($contact)
            ->test(Main::class)
            ->call('selectContact', $me->id)
            ->set('composer.body', 'Tinker contact probe reply')
            ->set('composer.replyToId', $message->id)
            ->call('send')
            ->assertHasNoErrors();

        $reply = Message::where('body', 'Tinker contact probe reply')->firstOrFail();
        $this->assertSame($message->id, $reply->reply_to_id);

        $component = Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id)
            ->call('deleteMessage', $message->id);

        $component->assertHasNoErrors();
        $this->assertNull(Message::withoutTrashed()->find($message->id));
        $this->assertTrue(Message::withTrashed()->find($message->id)->trashed());

        $component->call('undoDelete')->assertHasNoErrors();
        $this->assertNotNull(Message::withoutTrashed()->find($message->id));

        Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id)
            ->call('deleteMessage', $reply->id)
            ->assertHasNoErrors();

        $trashedReply = Message::withTrashed()->find($reply->id);
        app(ForceDeleteMessageAction::class)->execute($trashedReply);
        $this->assertNull(Message::withTrashed()->find($reply->id));
    }

    public function test_conversation_header_reflects_real_online_status_not_mood()
    {
        $source = file_get_contents(resource_path('views/livewire/dashboard/contact/header.blade.php'));

        $this->assertStringContainsString('x-dashboard.user-avatar :user="$contact"', $source);
        $this->assertStringContainsString(':dot="! $archived" :badge="! $archived"', $source);
        $this->assertStringNotContainsString('presence->activeClass()', $source);
    }

    public function test_search_messages_action_finds_committed_message()
    {
        $this->markTestSkipped('InnoDB FULLTEXT does not flush just-inserted rows to the searchable index until commit; the mandatory transaction-rollback harness keeps the row uncommitted, so MATCH...AGAINST cannot see it. The query itself is correct — verified manually against the live MySQL schema.');
    }

    public function test_contact_sidebar_carries_unit_and_section_detail()
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);
        $profile = Profile::factory()->create(['user_id' => $contact->id]);
        ProfileDetail::create(['profile_id' => $profile->id, 'section' => 'employment', 'key' => 'unit', 'value' => 'Operations']);
        ProfileDetail::create(['profile_id' => $profile->id, 'section' => 'employment', 'key' => 'section', 'value' => 'Logistics']);
        Message::create(['sender_id' => $me->id, 'recipient_id' => $contact->id, 'body' => 'thread seed']);

        $sidebar = Livewire::actingAs($me)->test(Main::class)->instance()->contacts();

        $row = collect($sidebar)->firstWhere('id', $contact->id);
        $this->assertNotNull($row);
        $this->assertSame('Operations', $row['unit']);
        $this->assertSame('Logistics', $row['section']);
    }

    public function test_contact_sidebar_carries_presence_and_focus_label_for_the_shared_avatar()
    {
        $me = User::factory()->create(['status' => 'active']);
        $focused = User::factory()->create(['status' => 'active', 'presence' => 'busy', 'focus_until' => now()->addMinutes(45)]);
        $plain = User::factory()->create(['status' => 'active', 'presence' => 'remote']);
        foreach ([$focused, $plain] as $contact) {
            Message::create(['sender_id' => $me->id, 'recipient_id' => $contact->id, 'body' => 'thread seed']);
        }

        $rows = collect(Livewire::actingAs($me)->test(Main::class)->instance()->contacts());

        $this->assertStringStartsWith('متمرکز تا', $rows->firstWhere('id', $focused->id)['focus']);
        $this->assertNull($rows->firstWhere('id', $plain->id)['focus']);
        $this->assertSame(\App\Enums\PresenceStatus::Remote, $rows->firstWhere('id', $plain->id)['presence']);
    }

    public function test_contact_sidebar_carries_display_title_over_raw_position()
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);
        $profile = Profile::factory()->create(['user_id' => $contact->id, 'position' => 'employee']);
        ProfileDetail::create(['profile_id' => $profile->id, 'section' => 'employment', 'key' => 'display_title', 'value' => 'Custom Title']);
        Message::create(['sender_id' => $me->id, 'recipient_id' => $contact->id, 'body' => 'thread seed']);

        $sidebar = Livewire::actingAs($me)->test(Main::class)->instance()->contacts();

        $row = collect($sidebar)->firstWhere('id', $contact->id);
        $this->assertNotNull($row);
        $this->assertSame('Custom Title', $row['display_position']);
    }

    public function test_contact_presenter_shows_display_position_over_raw_profile_position()
    {
        $presenter = new \App\Livewire\Dashboard\Contact\Presentation\ContactPresenter();

        $row = $presenter->contact([
            'id' => 1,
            'name' => 'Test User',
            'profile' => ['image' => null, 'position' => 'employee'],
            'display_position' => 'Custom Title',
            'occasion' => null,
        ], authId: 99);

        $this->assertSame('Custom Title', $row['position']);
    }

    public function test_contact_presenter_attachment_only_last_message_falls_back_to_attachment_label()
    {
        $presenter = new \App\Livewire\Dashboard\Contact\Presentation\ContactPresenter();

        $row = $presenter->contact([
            'id' => 1,
            'name' => 'Test User',
            'last_message' => ['sender_id' => 99, 'body' => '', 'attachments' => [['path' => 'x.pdf']], 'created_at' => '2026-08-31 10:00:00', 'read_at' => null],
        ], authId: 99);

        $this->assertSame('پیوست', $row['last_message']['body']);
    }

    public function test_contact_presenter_flags_first_unread_message_only()
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);

        $m1 = Message::create(['sender_id' => $me->id, 'recipient_id' => $contact->id, 'body' => 'mine', 'read_at' => now()]);
        $m2 = Message::create(['sender_id' => $contact->id, 'recipient_id' => $me->id, 'body' => 'read one', 'read_at' => now()]);
        $unreadOne = Message::create(['sender_id' => $contact->id, 'recipient_id' => $me->id, 'body' => 'unread one']);
        $m4 = Message::create(['sender_id' => $contact->id, 'recipient_id' => $me->id, 'body' => 'unread two']);

        $presenter = new \App\Livewire\Dashboard\Contact\Presentation\ContactPresenter();
        $allIds = [$m1->id, $m2->id, $unreadOne->id, $m4->id];
        $rows = $presenter->messages(
            \App\Models\Message::query()->whereIn('id', $allIds)->orderBy('id')->get()->toArray(),
            $me->id,
            600,
            $unreadOne->id
        );

        $flagged = array_values(array_filter($rows, fn($r) => $r['is_new_messages']));
        $this->assertCount(1, $flagged);
        $this->assertSame($unreadOne->id, $flagged[0]['id']);
    }

    public function test_contact_presenter_flags_single_divider_when_unread_spans_date_groups()
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);

        $y1 = Message::create(['sender_id' => $contact->id, 'recipient_id' => $me->id, 'body' => 'yesterday unread']);
        $y2 = Message::create(['sender_id' => $contact->id, 'recipient_id' => $me->id, 'body' => 'yesterday unread too']);
        $t1 = Message::create(['sender_id' => $contact->id, 'recipient_id' => $me->id, 'body' => 'today unread']);

        $presenter = new \App\Livewire\Dashboard\Contact\Presentation\ContactPresenter();
        $rows = $presenter->messages(
            collect([$y1->toArray(), $y2->toArray(), $t1->toArray()])->all(),
            $me->id,
            600,
            $y1->id
        );

        $flagged = array_values(array_filter($rows, fn($r) => $r['is_new_messages']));
        $this->assertCount(1, $flagged);
        $this->assertSame($y1->id, $flagged[0]['id']);
    }

    public function test_select_contact_captures_new_messages_anchor_before_marking_read()
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);

        $first = Message::create(['sender_id' => $contact->id, 'recipient_id' => $me->id, 'body' => 'unread one']);
        Message::create(['sender_id' => $contact->id, 'recipient_id' => $me->id, 'body' => 'unread two']);

        Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id)
            ->assertSet('newMessagesAnchorId', $first->id);

        $this->assertFalse(Message::query()->where('recipient_id', $me->id)->whereNull('read_at')->exists());
    }

    public function test_mark_read_dispatches_reconcile_nudge_for_the_sender()
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);

        Message::create(['sender_id' => $contact->id, 'recipient_id' => $me->id, 'body' => 'unread']);

        Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id);

        Queue::assertPushed(ReconcileNudge::class, fn(ReconcileNudge $job) => $job->ruleKey === 'contacts-controller:nudge'
            && $job->subjectClass === User::class
            && $job->subjectId === $contact->id);
    }

    public function test_mark_read_with_nothing_unread_dispatches_no_reconcile_nudge()
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id);

        Queue::assertNotPushed(ReconcileNudge::class);
    }

    public function test_mark_messages_as_read_action_runs_without_error_when_messages_become_read(): void
    {
        $sender = User::factory()->create(['status' => 'active']);
        $viewer = User::factory()->create(['status' => 'active']);

        Message::create(['sender_id' => $sender->id, 'recipient_id' => $viewer->id, 'body' => 'unread']);

        $count = app(MarkMessagesAsReadAction::class)->execute($sender->id, $viewer->id);

        $this->assertSame(1, $count);
        $this->assertTrue(Message::where('sender_id', $sender->id)->where('recipient_id', $viewer->id)->whereNotNull('read_at')->exists());
    }

    public function test_select_contact_with_no_unread_leaves_new_messages_anchor_null()
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);
        Message::create(['sender_id' => $contact->id, 'recipient_id' => $me->id, 'body' => 'already read', 'read_at' => now()]);

        Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id)
            ->assertSet('newMessagesAnchorId', null);
    }

    public function test_contact_filter_toggles()
    {
        $me = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($me)
            ->test(Main::class)
            ->assertSet('filter', 'all')
            ->call('setFilter', 'unread')
            ->assertSet('filter', 'unread')
            ->assertHasNoErrors();
    }

    public function test_contact_load_more_expands_message_window()
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);

        $component = Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id)
            ->assertSet('messagesLimit', 10);

        $component->call('loadMoreMessages')->assertHasNoErrors()->assertSet('messagesLimit', 35);
    }

    public function test_contact_selecting_second_contact_replaces_active()
    {
        $me = User::factory()->create(['status' => 'active']);
        $a = User::factory()->create(['status' => 'active']);
        $b = User::factory()->create(['status' => 'active']);
        Message::create(['sender_id' => $me->id, 'recipient_id' => $a->id, 'body' => 'to A']);
        Message::create(['sender_id' => $me->id, 'recipient_id' => $b->id, 'body' => 'to B']);

        $component = Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $a->id)
            ->assertSet('activeUserId', $a->id)
            ->call('selectContact', $b->id)
            ->assertSet('activeUserId', $b->id)
            ->assertHasNoErrors();
    }

    public function test_contact_send_rejects_empty_body_without_attachments()
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id)
            ->set('composer.body', '')
            ->call('send')
            ->assertDispatched('show-toast', message: 'وارد کردن متن پیام یا افزودن فایل ضمیمه الزامی است.', type: 'error');

        $this->assertSame(0, Message::where('sender_id', $me->id)->where('recipient_id', $contact->id)->count());
    }

    public function test_contact_send_with_attachment_persists_metadata()
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);
        $file = UploadedFile::fake()->image('scan.jpg');

        Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id)
            ->set('composer.attachments', [$file])
            ->set('composer.body', '')
            ->call('send')
            ->assertHasNoErrors();

        $msg = Message::where('sender_id', $me->id)->where('recipient_id', $contact->id)->firstOrFail();
        $this->assertNotNull($msg->attachments);
        $this->assertSame('scan.jpg', $msg->attachments[0]['name']);
        $this->assertNotEmpty($msg->attachments[0]['path']);
        $this->assertNotEmpty($msg->attachments[0]['mime']);
        $this->assertGreaterThan(0, $msg->attachments[0]['size']);
    }

    public function test_contact_edit_window_rejects_after_600s_and_allows_within()
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id)
            ->set('composer.body', 'original')
            ->call('send')
            ->assertHasNoErrors();

        $msg = Message::where('sender_id', $me->id)->where('recipient_id', $contact->id)->firstOrFail();
        $msg->created_at = now()->subSeconds(700);
        $msg->save();

        Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id)
            ->set('edit.editingBody', 'too late')
            ->call('saveEdit', $msg->id)
            ->assertHasNoErrors();

        $msg->refresh();
        $this->assertSame('original', $msg->body);
        $this->assertFalse((bool)$msg->is_edited);

        Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id)
            ->set('composer.body', 'fresh')
            ->call('send')
            ->assertHasNoErrors();

        $fresh = Message::where('body', 'fresh')->firstOrFail();
        Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id)
            ->set('edit.editingBody', 'fresh edited')
            ->call('saveEdit', $fresh->id)
            ->assertHasNoErrors();

        $fresh->refresh();
        $this->assertSame('fresh edited', $fresh->body);
        $this->assertTrue((bool)$fresh->is_edited);
    }

    public function test_contact_save_edit_still_rejects_true_empty_body_on_submit()
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id)
            ->set('composer.body', 'original body')
            ->call('send')
            ->assertHasNoErrors();

        $msg = Message::where('sender_id', $me->id)->where('recipient_id', $contact->id)->firstOrFail();

        Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id)
            ->set('edit.editingBody', '')
            ->call('saveEdit', $msg->id)
            ->assertHasErrors(['edit.editingBody' => 'required']);

        $this->assertSame('original body', $msg->refresh()->body);
    }

    public function test_contact_back_to_list_clears_active_contact_and_resets_state(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id)
            ->assertSet('activeUserId', $contact->id)
            ->set('messageSearch', 'query')
            ->call('loadMoreMessages')
            ->assertSet('messagesLimit', 35)
            ->call('backToList')
            ->assertSet('activeUserId', null)
            ->assertSet('messageSearch', '')
            ->assertSet('messagesLimit', 10)
            ->assertSet('mobileShowChat', false)
            ->assertHasNoErrors();
    }

    public function test_contact_online_filter_hides_offline_contacts(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $online = User::factory()->create(['status' => 'active', 'last_seen' => now()]);
        $offline = User::factory()->create(['status' => 'active', 'last_seen' => now()->subHour()]);

        $contacts = Livewire::actingAs($me)
            ->test(Main::class)
            ->call('setFilter', 'online')
            ->instance()->contacts;

        $this->assertTrue(collect($contacts)->contains('id', $online->id));
        $this->assertFalse(collect($contacts)->contains('id', $offline->id));
    }

    public function test_contact_unread_filter_only_lists_contacts_with_unread_messages(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contactUnread = User::factory()->create(['status' => 'active']);
        $contactRead = User::factory()->create(['status' => 'active']);
        Message::create(['sender_id' => $contactUnread->id, 'recipient_id' => $me->id, 'body' => 'unread']);
        Message::create(['sender_id' => $me->id, 'recipient_id' => $contactRead->id, 'body' => 'sent by me']);

        $contacts = Livewire::actingAs($me)
            ->test(Main::class)
            ->call('setFilter', 'unread')
            ->instance()->contacts;

        $this->assertTrue(collect($contacts)->contains('id', $contactUnread->id));
        $this->assertFalse(collect($contacts)->contains('id', $contactRead->id));
    }

    public function test_contact_search_by_name_filters_sidebar(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $target = User::factory()->create(['status' => 'active', 'name' => 'Zarathustra Unique']);
        $other = User::factory()->create(['status' => 'active', 'name' => 'Someone Else']);

        $contacts = Livewire::actingAs($me)
            ->test(Main::class)
            ->set('search', 'Zarathustra')
            ->instance()->contacts;

        $this->assertTrue(collect($contacts)->contains('id', $target->id));
        $this->assertFalse(collect($contacts)->contains('id', $other->id));
    }

    public function test_contact_messages_has_older_flag_true_when_thread_exceeds_limit(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);
        foreach (range(1, 11) as $i) {
            Message::create(['sender_id' => $me->id, 'recipient_id' => $contact->id, 'body' => "msg {$i}"]);
        }

        $component = Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id);

        $component->instance()->messages;

        $this->assertTrue($component->instance()->hasOlder);
    }

    public function test_contact_messages_has_older_flag_false_when_thread_under_limit(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);
        foreach (range(1, 5) as $i) {
            Message::create(['sender_id' => $me->id, 'recipient_id' => $contact->id, 'body' => "msg {$i}"]);
        }

        $component = Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id);

        $component->instance()->messages;

        $this->assertFalse($component->instance()->hasOlder);
    }

    private function conversationOracle(int $meId, int $contactId)
    {
        return Message::query()->where(fn($q) => $q
            ->where(fn($q2) => $q2->where('sender_id', $meId)->where('recipient_id', $contactId))
            ->orWhere(fn($q2) => $q2->where('sender_id', $contactId)->where('recipient_id', $meId))
        );
    }

    public function test_contact_messages_merge_interleaves_both_directions_in_ascending_id_order(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);

        $expectedOrder = [];
        foreach (range(1, 8) as $i) {
            $msg = $i % 2 === 0
                ? Message::create(['sender_id' => $me->id, 'recipient_id' => $contact->id, 'body' => "me {$i}"])
                : Message::create(['sender_id' => $contact->id, 'recipient_id' => $me->id, 'body' => "contact {$i}"]);
            $expectedOrder[] = $msg->id;
        }

        $component = Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id);

        $this->assertSame($expectedOrder, array_column($component->instance()->messages, 'id'));
    }

    public function test_contact_messages_window_picks_true_top_ten_across_both_directions(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);

        foreach (range(1, 6) as $i) {
            Message::create(['sender_id' => $me->id, 'recipient_id' => $contact->id, 'body' => "me {$i}"]);
        }
        foreach (range(1, 6) as $i) {
            Message::create(['sender_id' => $contact->id, 'recipient_id' => $me->id, 'body' => "contact {$i}"]);
        }

        $component = Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id);

        $ids = array_column($component->instance()->messages, 'id');
        $expectedTop10 = array_slice($this->conversationOracle($me->id, $contact->id)->orderBy('id')->pluck('id')->all(), -10);

        $this->assertTrue($component->instance()->hasOlder);
        $this->assertSame($expectedTop10, $ids);
    }

    public function test_contact_messages_anchor_window_merges_both_directions_correctly(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);

        $ids = [];
        foreach (range(1, 20) as $i) {
            $msg = $i % 2 === 0
                ? Message::create(['sender_id' => $me->id, 'recipient_id' => $contact->id, 'body' => "me {$i}"])
                : Message::create(['sender_id' => $contact->id, 'recipient_id' => $me->id, 'body' => "contact {$i}"]);
            $ids[] = $msg->id;
        }
        $anchorId = $ids[9];

        $component = Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id)
            ->call('focusMessage', $anchorId);

        $oracle = $this->conversationOracle($me->id, $contact->id);
        $olderRaw = (clone $oracle)->where('id', '<', $anchorId)->orderByDesc('id')->take(6)->pluck('id')->all();
        $olderTrimmed = array_slice($olderRaw, 0, 5);
        sort($olderTrimmed);
        $newer = (clone $oracle)->where('id', '>=', $anchorId)->orderBy('id')->take(6)->pluck('id')->all();
        $expected = array_merge($olderTrimmed, $newer);

        $this->assertSame($expected, array_column($component->instance()->messages, 'id'));
        $this->assertContains($anchorId, array_column($component->instance()->messages, 'id'));
    }

    public function test_contact_grouped_messages_partitions_by_day(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);
        Message::create(['sender_id' => $me->id, 'recipient_id' => $contact->id, 'body' => 'today msg']);
        $yesterday = Message::create(['sender_id' => $me->id, 'recipient_id' => $contact->id, 'body' => 'yesterday msg']);
        $yesterday->created_at = now()->subDay();
        $yesterday->save();

        $groups = Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id)
            ->instance()->groupedMessages;

        $this->assertCount(2, $groups);
    }

    public function test_contact_remove_attachment_drops_entry_and_reindexes(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);
        $fileA = UploadedFile::fake()->image('a.jpg');
        $fileB = UploadedFile::fake()->image('b.jpg');

        $component = Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id)
            ->set('composer.attachments', [$fileA, $fileB])
            ->call('removeAttachment', 0);

        $component->assertHasNoErrors();
        $attachments = $component->instance()->composer->attachments;
        $this->assertCount(1, $attachments);
        $this->assertSame('b.jpg', $attachments[0]->getClientOriginalName());
    }

    public function test_contact_download_attachment_rejects_non_participant(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);
        $stranger = User::factory()->create(['status' => 'active']);

        $msg = Message::create([
            'sender_id' => $me->id,
            'recipient_id' => $contact->id,
            'body' => 'with file',
            'attachments' => [['path' => 'messages/x/file.jpg', 'name' => 'file.jpg', 'mime' => 'image/jpeg', 'size' => 100]],
        ]);

        $result = Livewire::actingAs($stranger)->test(Main::class)->instance()->downloadAttachment($msg->id, 0);

        $this->assertNull($result);
    }

    public function test_contact_download_attachment_returns_null_for_missing_message(): void
    {
        $me = User::factory()->create(['status' => 'active']);

        $result = Livewire::actingAs($me)->test(Main::class)->instance()->downloadAttachment(999999, 0);

        $this->assertNull($result);
    }

    public function test_contact_attachment_path_is_scoped_to_sender_directory(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);
        $file = UploadedFile::fake()->image('scan.jpg');

        Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id)
            ->set('composer.attachments', [$file])
            ->set('composer.body', '')
            ->call('send')
            ->assertHasNoErrors();

        $msg = Message::where('sender_id', $me->id)->where('recipient_id', $contact->id)->firstOrFail();
        $this->assertStringStartsWith("messages/{$me->id}/", $msg->attachments[0]['path']);
    }

    public function test_contact_undo_delete_is_noop_when_no_last_deleted(): void
    {
        $me = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($me)
            ->test(Main::class)
            ->call('undoDelete')
            ->assertHasNoErrors()
            ->assertSet('lastDeleted', null);
    }

    public function test_contact_undo_delete_after_force_delete_preserves_timestamps_attachments_and_read_state(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);

        $attachments = [['path' => 'attachments/x.png', 'name' => 'x.png', 'mime' => 'image/png', 'size' => 1234]];

        $msg = Message::create([
            'sender_id'    => $me->id,
            'recipient_id' => $contact->id,
            'body'         => 'force-delete fallback',
            'attachments'  => $attachments,
            'read_at'      => now()->subMinutes(10),
        ]);
        $originalCreatedAt = $msg->fresh()->created_at->toISOString();
        $originalReadAt = $msg->fresh()->read_at->toISOString();

        $component = Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id)
            ->call('deleteMessage', $msg->id)
            ->assertHasNoErrors();

        app(ForceDeleteMessageAction::class)->execute(Message::withTrashed()->find($msg->id));
        $this->assertNull(Message::withTrashed()->find($msg->id));

        $component->call('undoDelete')->assertHasNoErrors();

        $restored = Message::withoutTrashed()->where('body', 'force-delete fallback')->latest('id')->firstOrFail();
        $this->assertSame($originalCreatedAt, $restored->created_at->toISOString());
        $this->assertSame($originalReadAt, $restored->read_at->toISOString());
        $this->assertEqualsCanonicalizing($attachments, $restored->attachments);
    }

    public function test_contact_save_edit_rejects_non_owner(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id)
            ->set('composer.body', 'owner original')
            ->call('send')
            ->assertHasNoErrors();

        $msg = Message::where('sender_id', $me->id)->where('recipient_id', $contact->id)->firstOrFail();

        Livewire::actingAs($contact)
            ->test(Main::class)
            ->call('selectContact', $me->id)
            ->set('edit.editingBody', 'hijack attempt')
            ->call('saveEdit', $msg->id)
            ->assertHasNoErrors();

        $this->assertSame('owner original', $msg->refresh()->body);
        $this->assertFalse((bool) $msg->refresh()->is_edited);
    }

    public function test_contact_delete_message_rejects_non_owner(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id)
            ->set('composer.body', 'mine')
            ->call('send')
            ->assertHasNoErrors();

        $msg = Message::where('sender_id', $me->id)->where('recipient_id', $contact->id)->firstOrFail();

        Livewire::actingAs($contact)
            ->test(Main::class)
            ->call('selectContact', $me->id)
            ->call('deleteMessage', $msg->id)
            ->assertHasNoErrors();

        $this->assertNotNull(Message::withoutTrashed()->find($msg->id));
    }

    public function test_contact_delete_message_rejects_after_edit_window(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id)
            ->set('composer.body', 'will age')
            ->call('send')
            ->assertHasNoErrors();

        $msg = Message::where('sender_id', $me->id)->where('recipient_id', $contact->id)->firstOrFail();
        $msg->created_at = now()->subSeconds(700);
        $msg->save();

        Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id)
            ->call('deleteMessage', $msg->id)
            ->assertHasNoErrors();

        $this->assertNotNull(Message::withoutTrashed()->find($msg->id));
    }

    public function test_contact_send_with_reply_to_id_outside_conversation_drops_reply_link(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);
        $strangerA = User::factory()->create(['status' => 'active']);
        $strangerB = User::factory()->create(['status' => 'active']);
        $otherMsg = Message::create(['sender_id' => $strangerA->id, 'recipient_id' => $strangerB->id, 'body' => 'not my thread']);

        Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id)
            ->set('composer.body', 'reply attempt')
            ->set('composer.replyToId', $otherMsg->id)
            ->call('send')
            ->assertHasNoErrors();

        $msg = Message::where('sender_id', $me->id)->where('recipient_id', $contact->id)->firstOrFail();
        $this->assertNull($msg->reply_to_id);
    }

    public function test_contact_select_contact_with_nonexistent_id_does_not_activate(): void
    {
        $me = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', PHP_INT_MAX)
            ->assertSet('activeUserId', null)
            ->assertHasNoErrors();
    }

    public function test_contact_send_dispatches_message_sent_event(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id)
            ->set('composer.body', 'dispatch me')
            ->call('send')
            ->assertDispatched('message-sent');
    }

    public function test_contact_send_marks_the_peers_earlier_messages_read(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);

        $component = Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id);

        $incoming = Message::create(['sender_id' => $contact->id, 'recipient_id' => $me->id, 'body' => 'arrived while open']);

        $component->set('composer.body', 'reply')->call('send');

        $this->assertNotNull($incoming->fresh()->read_at);
    }

    public function test_contact_send_rejects_more_than_five_attachments(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);
        $files = collect(range(1, 6))->map(fn() => UploadedFile::fake()->image('a.jpg'))->all();

        Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id)
            ->set('composer.attachments', $files)
            ->set('composer.body', 'overflow')
            ->call('send')
            ->assertHasErrors(['composer.attachments']);

        $this->assertSame(0, Message::where('sender_id', $me->id)->where('recipient_id', $contact->id)->count());
    }

    public function test_contact_send_rejects_disallowed_attachment_mime(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);
        $file = UploadedFile::fake()->createWithContent('notallowed.txt', 'plain text content');

        Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id)
            ->set('composer.attachments', [$file])
            ->set('composer.body', 'with bad file')
            ->call('send');

        $this->assertSame(0, Message::where('sender_id', $me->id)->where('recipient_id', $contact->id)->count());
    }

    public function test_contact_send_trims_body_whitespace(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id)
            ->set('composer.body', '  padded body  ')
            ->call('send')
            ->assertHasNoErrors();

        $msg = Message::where('sender_id', $me->id)->where('recipient_id', $contact->id)->firstOrFail();
        $this->assertSame('padded body', $msg->body);
    }

    public function test_contact_message_search_results_empty_when_no_active_contact(): void
    {
        $me = User::factory()->create(['status' => 'active']);

        $results = Livewire::actingAs($me)
            ->test(Main::class)
            ->set('messageSearch', 'anything')
            ->instance()->messageSearchResults;

        $this->assertSame([], $results);
    }

    public function test_contact_search_messages_action_returns_empty_for_short_query(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);
        Message::create(['sender_id' => $me->id, 'recipient_id' => $contact->id, 'body' => 'hi there']);

        $results = app(SearchMessagesAction::class)->execute($contact->id, 'ab', $me->id);

        $this->assertSame([], $results);
    }

    public function test_contact_search_messages_action_runs_fulltext_query_without_db_error(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);
        Message::create(['sender_id' => $me->id, 'recipient_id' => $contact->id, 'body' => 'Wintara report']);

        $this->assertIsArray(app(SearchMessagesAction::class)->execute($contact->id, 'Win', $me->id));
        $this->assertIsArray(app(SearchMessagesAction::class)->execute($contact->id, 'Wintara', $me->id));
    }

    public function test_contact_bookmarks_return_only_this_conversations_messages(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);
        $third = User::factory()->create(['status' => 'active']);
        $sent = Message::create(['sender_id' => $me->id, 'recipient_id' => $contact->id, 'body' => 'to contact']);
        $received = Message::create(['sender_id' => $contact->id, 'recipient_id' => $me->id, 'body' => 'from contact']);
        $other = Message::create(['sender_id' => $me->id, 'recipient_id' => $third->id, 'body' => 'other thread']);

        $rows = app(SearchMessagesAction::class)->bookmarks($contact->id, [$sent->id, $received->id, $other->id], $me->id);

        $this->assertSame([$received->id, $sent->id], array_column($rows, 'id'));
    }

    public function test_contact_days_list_merges_both_directions_and_excludes_other_threads(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);
        $third = User::factory()->create(['status' => 'active']);
        $sent = Message::create(['sender_id' => $me->id, 'recipient_id' => $contact->id, 'body' => 's']);
        $received = Message::create(['sender_id' => $contact->id, 'recipient_id' => $me->id, 'body' => 'r']);
        $other = Message::create(['sender_id' => $me->id, 'recipient_id' => $third->id, 'body' => 'o']);
        $sent->forceFill(['created_at' => '2026-10-01 10:00:00'])->saveQuietly();
        $received->forceFill(['created_at' => '2026-10-01 12:00:00'])->saveQuietly();
        $other->forceFill(['created_at' => '2026-09-20 12:00:00'])->saveQuietly();

        $days = app(SearchMessagesAction::class)->days($contact->id, $me->id);

        $this->assertSame([['day' => '2026-10-01', 'id' => $sent->id]], $days);
    }

    public function test_contact_files_gallery_returns_both_directions_and_excludes_other_threads(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);
        $third = User::factory()->create(['status' => 'active']);
        $sentImg = Message::create(['sender_id' => $me->id, 'recipient_id' => $contact->id, 'body' => 'pic',
            'attachments' => [['path' => 'chat/a.png', 'name' => 'a.png', 'mime' => 'image/png', 'size' => 2048]]]);
        $receivedDoc = Message::create(['sender_id' => $contact->id, 'recipient_id' => $me->id, 'body' => 'doc',
            'attachments' => [['path' => 'chat/b.pdf', 'name' => 'b.pdf', 'mime' => 'application/pdf', 'size' => 4096]]]);
        Message::create(['sender_id' => $me->id, 'recipient_id' => $third->id, 'body' => 'o',
            'attachments' => [['path' => 'chat/c.mp4', 'name' => 'c.mp4', 'mime' => 'video/mp4', 'size' => 1024]]]);
        Message::create(['sender_id' => $me->id, 'recipient_id' => $contact->id, 'body' => 'plain']);

        $rows = app(SearchMessagesAction::class)->files($contact->id, $me->id);

        // both directions, newest first, attachment-less + other-thread rows excluded
        $this->assertSame([$receivedDoc->id . ':0', $sentImg->id . ':0'], array_column($rows, 'key'));
        $this->assertSame(['doc', 'image'], array_column($rows, 'kind'));
        $this->assertSame([], app(SearchMessagesAction::class)->files($third->id, 0));
    }

    public function test_contact_empty_thread_returns_no_messages(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);

        $messages = Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id)
            ->instance()->messages;

        $this->assertSame([], $messages);
    }

    public function test_contact_focus_message_is_noop_without_active_contact(): void
    {
        $me = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($me)
            ->test(Main::class)
            ->call('focusMessage', 1)
            ->assertSet('focusAnchorId', null)
            ->assertHasNoErrors();
    }

    public function test_contact_focus_message_dispatches_record_focus_for_valid_anchor(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);
        $msg = Message::create(['sender_id' => $me->id, 'recipient_id' => $contact->id, 'body' => 'focus target']);

        Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id)
            ->call('focusMessage', $msg->id)
            ->assertDispatched('record-focus');
    }

    public function test_mount_with_open_only_still_dispatches_people_record_focus(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);

        Livewire::withQueryParams(['open' => $contact->id])
            ->actingAs($me)
            ->test(Main::class)
            ->assertDispatched('record-focus', type: 'people', id: $contact->id);
    }

    public function test_mount_with_open_and_focus_msg_dispatches_only_message_record_focus(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);
        $msg = Message::create(['sender_id' => $contact->id, 'recipient_id' => $me->id, 'body' => 'deep link target']);

        Livewire::withQueryParams(['open' => $contact->id, 'focus_msg' => $msg->id])
            ->actingAs($me)
            ->test(Main::class)
            ->assertDispatched('record-focus', type: 'message', id: $msg->id)
            ->assertNotDispatched('record-focus', type: 'people', id: $contact->id);
    }

    public function test_mount_with_open_and_invalid_focus_msg_falls_back_to_people_record_focus(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);

        Livewire::withQueryParams(['open' => $contact->id, 'focus_msg' => 999999])
            ->actingAs($me)
            ->test(Main::class)
            ->assertDispatched('record-focus', type: 'people', id: $contact->id)
            ->assertNotDispatched('record-focus', type: 'message', id: 999999);
    }

    public function test_refresh_unread_recomputes_contacts(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);

        // contacts() is now SQL-LIMIT-bound (contactsLimit) — scope via search so this
        // specific contact is guaranteed inside the page regardless of dev-DB noise.
        $component = Livewire::actingAs($me)->test(Main::class)->set('search', $contact->name);

        $before = collect($component->instance()->contacts)->firstWhere('id', $contact->id);
        $this->assertNotNull($before);
        $this->assertNull($before['last_message']);
        $this->assertSame(0, $before['unread_count']);

        Message::create(['sender_id' => $contact->id, 'recipient_id' => $me->id, 'body' => 'incoming after mount']);

        $component->call('refreshUnread');

        $after = collect($component->instance()->contacts)->firstWhere('id', $contact->id);
        $this->assertNotNull($after);
        $this->assertNotNull($after['last_message']);
        $this->assertSame('incoming after mount', $after['last_message']['body']);
        $this->assertSame(1, $after['unread_count']);
    }

    public function test_contacts_last_message_shape_locks_body_sender_created_and_read_fields(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);

        Message::create([
            'sender_id' => $contact->id,
            'recipient_id' => $me->id,
            'body' => 'hello there',
        ]);

        $row = collect(Livewire::actingAs($me)->test(Main::class)->instance()->contacts)
            ->firstWhere('id', $contact->id);

        $this->assertNotNull($row);
        $this->assertNotNull($row['last_message']);
        $this->assertSame('hello there', $row['last_message']['body']);
        $this->assertSame($contact->id, (int) $row['last_message']['sender_id']);
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',
            $row['last_message']['created_at'],
        );
        $this->assertNull($row['last_message']['read_at']);
    }

    public function test_refresh_active_recomputes_messages_when_conversation_open(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);
        Message::create(['sender_id' => $contact->id, 'recipient_id' => $me->id, 'body' => 'first']);

        $component = Livewire::actingAs($me)
            ->test(Main::class)
            ->call('selectContact', $contact->id);

        $this->assertCount(1, $component->instance()->messages);

        Message::create(['sender_id' => $contact->id, 'recipient_id' => $me->id, 'body' => 'second incoming']);

        $component->call('refreshActive');

        $messages = $component->instance()->messages;
        $this->assertCount(2, $messages);
        $this->assertTrue(collect($messages)->contains(fn($m) => $m['body'] === 'second incoming'));
    }

    public function test_messaging_legends_render_shared_guide_and_nudge_modal(): void
    {
        $me = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($me)
            ->test(Main::class)
            ->assertSee('messaging-badge-legend')
            ->assertSee('messaging-feature-legend')
            ->assertSee('راهنمای پیام‌رسان و گروه')
            ->assertSee('ویرایش و پاسخ')
            ->assertSee('سنجاق و نمایش')
            ->assertSee('رنگ‌آمیزی ردیف')
            ->assertSee('نشان پیام')
            ->assertSee('صدا و اعلان')
            ->assertSee('مالک گروه');
    }

    public function test_contact_sidebar_renders_pin_cluster_and_order_binding(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);
        Message::create(['sender_id' => $me->id, 'recipient_id' => $contact->id, 'body' => 'thread seed']);

        Livewire::actingAs($me)
            ->test(Main::class)
            ->assertSeeHtml('$store.pinned.togglePin(' . $contact->id . ', \'contact\')')
            ->assertSeeHtml('$store.sound.toggleMute(' . $contact->id . ', \'contact\')')
            ->assertSeeHtml('$store.pinned.isPinned(' . $contact->id . ', \'contact\') ? -1 : 0')
            ->assertSeeHtml('flex items-center gap-1 pointer-events-none')
            ->assertSeeHtml('font-fill rotate-45')
            ->assertSeeHtml('bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]')
            ->assertSee('push_pin')
            ->assertSeeHtml(trim(json_encode('گفتگو'), '"'))
            ->assertSeeHtml(trim(json_encode('مخاطب'), '"'))
            ->assertSeeHtml('x-data="{ tagOpen: false }"')
            ->assertSeeHtml('$store.tagged.tagBg(' . $contact->id . ', \'contact\')')
            ->assertSeeHtml('tagOpen = !tagOpen')
            ->assertSeeHtml('$store.tagged.palette')
            ->assertSeeHtml('$store.tagged.setTag(' . $contact->id . ', i, \'contact\')')
            ->assertSeeHtml('$store.tagged.clearTag(' . $contact->id . ', \'contact\')')
            ->assertSeeHtml('>palette<');
    }

    public function test_contact_message_actions_render_bookmark_button_marker_and_ring(): void
    {
        $msg = [
            'id' => 909, 'is_mine' => false, 'body' => 'x', 'body_html' => 'x',
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
        $this->assertStringContainsString('-translate-y-1/2 -translate-x-1/2', $html);
        $this->assertStringContainsString(trim(json_encode('نشان کردن پیام'), '"'), $html);
        $this->assertStringContainsString(trim(json_encode('برداشتن نشان پیام'), '"'), $html);

        $blade = file_get_contents(base_path('resources/views/livewire/dashboard/contact/messages.blade.php'));
        $this->assertStringContainsString("!ring-2 !ring-[var(--md-sys-color-primary)]", $blade);
        $this->assertStringContainsString("\$store.pinned.isPinned(@js(\$msg['id']), @js('message'))", $blade);
    }

    public function test_updated_attachments_dispatches_attachments_updated_self_event(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $file = UploadedFile::fake()->image('attach.jpg');

        Livewire::actingAs($me)
            ->test(Main::class)
            ->set('composer.attachments', [$file])
            ->assertDispatched('attachments-updated');
    }

    public function test_contact_sidebar_row_carries_occasion_type_for_a_contact_with_a_birthday_today(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);
        Profile::factory()->create([
            'user_id' => $contact->id,
            'birthdate' => now()->subYears(30),
            'employment_status' => 'working',
        ]);

        // contacts() is now SQL-LIMIT-bound (contactsLimit) — scope via search so this
        // specific contact is guaranteed inside the page regardless of dev-DB noise.
        $sidebar = Livewire::actingAs($me)->test(Main::class)->set('search', $contact->name)->instance()->contacts();

        $row = collect($sidebar)->firstWhere('id', $contact->id);
        $this->assertSame('birthday', $row['occasion']);
    }

    public function test_contact_sidebar_row_occasion_is_null_when_no_date_matches_today(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);
        Profile::factory()->create([
            'user_id' => $contact->id,
            'birthdate' => now()->subYears(30)->addDay(),
            'employment_status' => 'working',
        ]);

        $sidebar = Livewire::actingAs($me)->test(Main::class)->set('search', $contact->name)->instance()->contacts();

        $row = collect($sidebar)->firstWhere('id', $contact->id);
        $this->assertNull($row['occasion']);
    }

    public function test_presenter_shaped_sidebar_row_carries_occasion_and_renders_the_chip(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);
        Profile::factory()->create([
            'user_id' => $contact->id,
            'birthdate' => now()->subYears(30),
            'employment_status' => 'working',
        ]);

        $component = Livewire::actingAs($me)->test(Main::class)->set('search', $contact->name);
        $shaped = $component->instance()->presenter->sidebar($component->instance()->contacts(), $me->id);

        $row = collect($shaped)->firstWhere('id', $contact->id);
        $this->assertSame('birthday', $row['occasion']);
        $this->assertStringContainsString('auto_awesome', $component->html());
    }

    public function test_total_occasions_counts_only_contacts_with_an_occasion_today(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $before = Livewire::actingAs($me)->test(Main::class)->instance()->totalOccasions();

        $birthdayContact = User::factory()->create(['status' => 'active']);
        $anniversaryContact = User::factory()->create(['status' => 'active']);
        $plainContact = User::factory()->create(['status' => 'active']);
        Profile::factory()->create([
            'user_id' => $birthdayContact->id,
            'birthdate' => now()->subYears(30),
            'employment_status' => 'working',
        ]);
        Profile::factory()->create([
            'user_id' => $anniversaryContact->id,
            'start_date' => now()->subYears(3),
            'employment_status' => 'working',
        ]);
        Profile::factory()->create([
            'user_id' => $plainContact->id,
            'birthdate' => now()->subYears(30)->addDay(),
            'employment_status' => 'working',
        ]);

        $after = Livewire::actingAs($me)->test(Main::class)->instance()->totalOccasions();

        $this->assertSame($before + 2, $after);
    }

    public function test_contact_row_bakes_occasion_tone_and_org_title_into_the_shaped_array(): void
    {
        $presenter = new \App\Livewire\Dashboard\Contact\Presentation\ContactPresenter();

        $withOccasionAndOrg = $presenter->contact([
            'id' => 1,
            'name' => 'آرش',
            'profile' => ['image' => null, 'position' => 'کارشناس'],
            'occasion' => 'birthday',
            'unit' => 'واحد فنی',
            'section' => 'شبکه',
        ], 1);

        $this->assertSame('birthday', $withOccasionAndOrg['occasion']);
        $this->assertIsArray($withOccasionAndOrg['occasion_tone']);
        $this->assertStringContainsString('tool-gold', $withOccasionAndOrg['occasion_tone']['chip']);
        $this->assertSame('تولد', $withOccasionAndOrg['occasion_tone']['label']);
        $this->assertSame('واحد فنی › شبکه', $withOccasionAndOrg['org_title'], 'org_title is built from unit › section when both are present');

        $unitOnly = $presenter->contact([
            'id' => 2, 'name' => 'X', 'profile' => ['position' => 'P'], 'occasion' => null, 'unit' => 'واحد فنی', 'section' => null,
        ], 1);
        $this->assertNull($unitOnly['occasion_tone'], 'occasion_tone is null when there is no occasion');
        $this->assertSame('واحد فنی', $unitOnly['org_title'], 'org_title drops a missing section instead of showing a bare separator');

        $blank = $presenter->contact([
            'id' => 3, 'name' => 'Y', 'profile' => ['position' => 'P'], 'occasion' => null, 'unit' => null, 'section' => null,
        ], 1);
        $this->assertSame('', $blank['org_title'], 'org_title is an empty string when neither unit nor section is set, so the tooltip stays empty');
    }

    public function test_sidebar_shows_a_load_more_button_only_past_the_default_page_size(): void
    {
        $viewer = User::factory()->create(['status' => 'active']);
        User::factory()->count(35)->create(['status' => 'active']);

        $component = Livewire::actingAs($viewer)->test(Main::class);

        $component->assertSet('contactsLimit', 30)
            ->assertSeeHtml('نمایش بیشتر');

        $component->call('loadMoreContacts')
            ->assertSet('contactsLimit', 60)
            ->assertDontSeeHtml('نمایش بیشتر');
    }

    public function test_sidebar_load_more_button_is_scoped_to_the_sidebar_island(): void
    {
        $viewer = User::factory()->create(['status' => 'active']);
        User::factory()->count(35)->create(['status' => 'active']);

        Livewire::actingAs($viewer)->test(Main::class)
            ->assertSeeHtml('$wire.$island(&#039;sidebar&#039;).loadMoreContacts()');
    }

    public function test_messages_load_more_button_source_is_scoped_to_the_messages_island(): void
    {
        $blade = file_get_contents(base_path('resources/views/livewire/dashboard/contact/messages.blade.php'));

        $this->assertStringContainsString('action="loadMoreMessages"', $blade);
        $this->assertStringContainsString('island="messages"', $blade);
    }

    public function test_sidebar_load_more_visibility_matches_whether_the_list_exceeds_the_page_size(): void
    {
        // visibleOnBoard() is org-wide, so this dev DB's existing active users (not just
        // ones this test creates) determine the real total — assert the invariant, not a count.
        // contacts() itself is now SQL-limited to contactsLimit, so the "is there more" signal
        // is the hasMoreContacts flag it sets via the +1 probe, not the returned array's length.
        $viewer = User::factory()->create(['status' => 'active']);
        $component = Livewire::actingAs($viewer)->test(Main::class);
        $component->instance()->contacts();
        $hasMore = $component->instance()->hasMoreContacts;

        if ($hasMore) {
            $component->assertSeeHtml('نمایش بیشتر');
        } else {
            $component->assertDontSeeHtml('نمایش بیشتر');
        }
    }

    public function test_searching_resets_the_sidebar_page_size(): void
    {
        $viewer = User::factory()->create(['status' => 'active']);
        User::factory()->count(35)->create(['status' => 'active']);

        Livewire::actingAs($viewer)->test(Main::class)
            ->call('loadMoreContacts')
            ->assertSet('contactsLimit', 60)
            ->set('search', 'a')
            ->assertSet('contactsLimit', 30);
    }

    public function test_setting_filter_resets_the_sidebar_page_size(): void
    {
        $viewer = User::factory()->create(['status' => 'active']);
        User::factory()->count(35)->create(['status' => 'active']);

        Livewire::actingAs($viewer)->test(Main::class)
            ->call('loadMoreContacts')
            ->assertSet('contactsLimit', 60)
            ->call('setFilter', 'unread')
            ->assertSet('contactsLimit', 30);
    }

    public function test_contact_presenter_reply_preview_includes_message_id_for_click_to_scroll(): void
    {
        $presenter = new \App\Livewire\Dashboard\Contact\Presentation\ContactPresenter();

        $messages = [[
            'id' => 5,
            'sender_id' => 1,
            'body' => 'reply body',
            'created_at' => now()->toDateTimeString(),
            'reply_to' => ['id' => 42, 'sender' => ['name' => 'آرش'], 'body' => 'original message'],
        ]];

        $shaped = $presenter->messages($messages, authId: 1, editTimeLimit: 600);

        $this->assertSame(42, $shaped[0]['reply_to']['id']);
    }

    public function test_contact_presenter_shared_bubble_helpers_match_channel_presenter_output(): void
    {
        $contact = new \App\Livewire\Dashboard\Contact\Presentation\ContactPresenter();
        $channel = new \App\Livewire\Dashboard\Channel\Presentation\ChannelPresenter();

        $this->assertSame($channel->bubbleRadius(true, false, true), $contact->bubbleRadius(true, false, true));
        $this->assertSame($channel->linkify('see https://example.com now'), $contact->linkify('see https://example.com now'));

        $attachment = [['path' => 'messages/1/a.png', 'name' => 'a.png', 'mime' => 'image/png', 'size' => 2048]];
        $this->assertSame($channel->attachments($attachment), $contact->attachments($attachment));

        $replyTo = ['id' => 7, 'sender' => ['name' => 'X'], 'body' => 'hello world'];
        $this->assertSame($channel->replyPreview($replyTo), $contact->replyPreview($replyTo));
    }

    public function test_contact_presenter_type_flags_classify_text_image_and_pdf_rows(): void
    {
        $presenter = new \App\Livewire\Dashboard\Contact\Presentation\ContactPresenter();

        $shaped = $presenter->messages([
            ['id' => 1, 'sender_id' => 1, 'body' => 'plain text', 'created_at' => now()->toDateTimeString()],
            ['id' => 2, 'sender_id' => 1, 'body' => 'see attached', 'created_at' => now()->toDateTimeString(),
                'attachments' => [['path' => 'messages/1/a.png', 'name' => 'a.png', 'mime' => 'image/png', 'size' => 2048]]],
            ['id' => 3, 'sender_id' => 1, 'body' => '', 'created_at' => now()->toDateTimeString(),
                'attachments' => [['path' => 'messages/1/a.pdf', 'name' => 'a.pdf', 'mime' => 'application/pdf', 'size' => 2048]]],
        ], authId: 1, editTimeLimit: 600);

        $this->assertSame('t', $shaped[0]['type_flags']);
        $this->assertSame('t i', $shaped[1]['type_flags']);
        $this->assertSame('p', $shaped[2]['type_flags']);
    }

    public function test_read_only_recipient_cannot_tamper_with_locked_active_user_id_via_raw_set(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);

        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

        Livewire::actingAs($me)->test(Main::class)
            ->set('activeUserId', $contact->id);
    }

    public function test_send_message_action_rejects_recipient_not_visible_on_board(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $inactive = User::factory()->create(['status' => 'inactive']);

        $this->actingAs($me);
        $form = Livewire::test(Main::class)->instance()->composer;
        $form->body = 'hello';

        $this->expectException(ModelNotFoundException::class);

        app(SendMessageAction::class)->execute($form, $inactive->id);
    }

    public function test_send_without_an_active_contact_is_a_silent_no_op(): void
    {
        $me = User::factory()->create(['status' => 'active']);

        $component = Livewire::actingAs($me)->test(Main::class);
        $component->set('composer.body', 'hello');

        $component->call('send');

        $this->assertSame(0, Message::where('sender_id', $me->id)->count());
    }

    public function test_ping_typing_sets_flag_read_by_the_peer(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $peer = User::factory()->create(['status' => 'active']);
        Cache::forget("typing:dm:{$me->id}:{$peer->id}");

        Livewire::actingAs($me)->test(Main::class)
            ->call('selectContact', $peer->id)
            ->call('pingTyping');

        $this->assertTrue(Cache::get("typing:dm:{$me->id}:{$peer->id}"));

        $peerTyping = Livewire::actingAs($peer)->test(Main::class)
            ->call('selectContact', $me->id)
            ->instance()->peerTyping;

        $this->assertTrue($peerTyping);

        Cache::forget("typing:dm:{$me->id}:{$peer->id}");
    }

    public function test_peer_typing_is_false_without_an_open_conversation_or_expired_flag(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $peer = User::factory()->create(['status' => 'active']);

        $noConversation = Livewire::actingAs($me)->test(Main::class)->instance()->peerTyping;
        $this->assertFalse($noConversation);

        Cache::forget("typing:dm:{$peer->id}:{$me->id}");
        $noFlag = Livewire::actingAs($me)->test(Main::class)
            ->call('selectContact', $peer->id)
            ->instance()->peerTyping;
        $this->assertFalse($noFlag);
    }

    public function test_typing_with_an_empty_body_does_not_trigger_validation_errors(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($me)->test(Main::class)
            ->call('selectContact', $contact->id)
            ->set('composer.body', '')
            ->call('pingTyping')
            ->assertHasNoErrors();
    }

    public function test_sending_a_message_clears_the_senders_own_typing_flag(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $peer = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($me)->test(Main::class)
            ->call('selectContact', $peer->id)
            ->call('pingTyping')
            ->set('composer.body', 'stopping to send this')
            ->call('send');

        $this->assertNull(Cache::get("typing:dm:{$me->id}:{$peer->id}"));
    }

    public function test_poll_digest_changes_when_a_message_arrives_and_not_for_an_unrelated_send(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $peer = User::factory()->create(['status' => 'active']);
        $stranger = User::factory()->create(['status' => 'active']);

        // PollDigestService::bump() is called from Message's model-event hooks wrapped in
        // DB::afterCommit() (correct for production, and the single source of truth covering
        // both the user-panel Actions AND ContactResource's admin edit/delete/restore — see
        // contactPattern.md's digest section), but this project's tests wrap everything in one
        // never-committed transaction (livewireTestPattern §1), so afterCommit callbacks never
        // fire here. Bump directly to test pollDigest()'s own key-scoping in isolation, and
        // separately confirm the real send() flow at least reaches the model layer without error.
        // actingAs() switches the global auth user, so re-assert it before each digest check.
        $before = Livewire::actingAs($me)->test(Main::class)->instance()->pollDigest();

        PollDigestService::bump("dm:sv:{$stranger->id}");
        $this->assertSame($before, Livewire::actingAs($me)->test(Main::class)->instance()->pollDigest());

        PollDigestService::bump("dm:sv:{$me->id}");
        $this->assertNotSame($before, Livewire::actingAs($me)->test(Main::class)->instance()->pollDigest());

        Livewire::actingAs($peer)->test(Main::class)
            ->call('selectContact', $me->id)
            ->set('composer.body', 'incoming for me')
            ->call('send')
            ->assertHasNoErrors();
    }

    public function test_refresh_active_stamp_is_stable_when_unchanged_and_moves_when_a_message_arrives(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $contact = User::factory()->create(['status' => 'active']);
        Message::factory()->count(5)->create(['sender_id' => $contact->id, 'recipient_id' => $me->id]);

        $component = Livewire::actingAs($me)->test(Main::class)->call('selectContact', $contact->id);

        for ($i = 0; $i < 8; $i++) {
            $component->call('loadMoreMessages');
        }
        $component->assertSet('messagesLimit', 210);

        $component->call('refreshActive');
        $stampAfterFirst = $component->get('pollStamp');
        $this->assertNotNull($stampAfterFirst);

        $component->call('refreshActive');
        $this->assertSame($stampAfterFirst, $component->get('pollStamp'));

        Message::create(['sender_id' => $contact->id, 'recipient_id' => $me->id, 'body' => 'new one']);

        $component->call('refreshActive');
        $this->assertNotSame($stampAfterFirst, $component->get('pollStamp'));
    }

    public function test_archived_filter_lists_inactive_contact_with_message_history_and_not_under_all(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $inactive = User::factory()->create(['status' => 'inactive', 'type' => 'employee']);
        Message::create(['sender_id' => $inactive->id, 'recipient_id' => $me->id, 'body' => 'before you left']);

        $component = Livewire::actingAs($me)->test(Main::class);

        $all = $component->instance()->contacts;
        $this->assertFalse(collect($all)->contains('id', $inactive->id));

        $archived = $component->call('setFilter', 'archived')->instance()->contacts;
        $this->assertTrue(collect($archived)->contains('id', $inactive->id));
    }

    public function test_archived_filter_lists_guest_type_contact_with_message_history(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $guest = User::factory()->create(['status' => 'active', 'type' => 'guest']);
        Message::create(['sender_id' => $me->id, 'recipient_id' => $guest->id, 'body' => 'hello guest']);

        $archived = Livewire::actingAs($me)->test(Main::class)
            ->call('setFilter', 'archived')
            ->instance()->contacts;

        $this->assertTrue(collect($archived)->contains('id', $guest->id));
    }

    public function test_archived_filter_excludes_inactive_contact_without_message_history(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $inactiveNoHistory = User::factory()->create(['status' => 'inactive', 'type' => 'employee']);

        $component = Livewire::actingAs($me)->test(Main::class);

        $all = $component->instance()->contacts;
        $archived = $component->call('setFilter', 'archived')->instance()->contacts;

        $this->assertFalse(collect($all)->contains('id', $inactiveNoHistory->id));
        $this->assertFalse(collect($archived)->contains('id', $inactiveNoHistory->id));
    }

    public function test_archived_filter_never_includes_active_visible_contacts(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $activeContact = User::factory()->create(['status' => 'active', 'type' => 'employee']);
        Message::create(['sender_id' => $activeContact->id, 'recipient_id' => $me->id, 'body' => 'still around']);

        $archived = Livewire::actingAs($me)->test(Main::class)
            ->call('setFilter', 'archived')
            ->instance()->contacts;

        $this->assertFalse(collect($archived)->contains('id', $activeContact->id));
    }

    public function test_archived_filter_ignores_soft_deleted_message_history(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $inactive = User::factory()->create(['status' => 'inactive', 'type' => 'employee']);
        $message = Message::create(['sender_id' => $inactive->id, 'recipient_id' => $me->id, 'body' => 'deleted later']);
        $message->delete();

        $archived = Livewire::actingAs($me)->test(Main::class)
            ->call('setFilter', 'archived')
            ->instance()->contacts;

        $this->assertFalse(collect($archived)->contains('id', $inactive->id));
    }

    public function test_archived_filter_pagination_has_more_flag(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        foreach (range(1, 32) as $i) {
            $inactive = User::factory()->create(['status' => 'inactive', 'type' => 'employee']);
            Message::create(['sender_id' => $inactive->id, 'recipient_id' => $me->id, 'body' => "old msg {$i}"]);
        }

        $component = Livewire::actingAs($me)->test(Main::class)->call('setFilter', 'archived');
        $component->instance()->contacts();

        $this->assertTrue($component->instance()->hasMoreContacts);

        $component->call('loadMoreContacts')->assertSet('contactsLimit', 60);
        $component->instance()->contacts();
        $this->assertFalse($component->instance()->hasMoreContacts);
    }

    public function test_archived_contacts_and_their_messages_carry_the_inactive_flag(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $gone = User::factory()->create(['status' => 'inactive', 'type' => 'employee']);
        $stays = User::factory()->create(['status' => 'active']);
        Message::create(['sender_id' => $gone->id, 'recipient_id' => $me->id, 'body' => 'farewell']);
        Message::create(['sender_id' => $stays->id, 'recipient_id' => $me->id, 'body' => 'hello']);

        $archived = Livewire::actingAs($me)->test(Main::class)->call('setFilter', 'archived')->instance()->contacts;
        $this->assertTrue(collect($archived)->firstWhere('id', $gone->id)['inactive']);

        $presenter = new \App\Livewire\Dashboard\Contact\Presentation\ContactPresenter();
        $row = fn(bool $inactive) => [[
            'id' => 1, 'sender_id' => 2, 'body' => 'x', 'created_at' => now()->toISOString(),
            'sender' => ['name' => 'S', 'avatar' => null, 'online' => false, 'inactive' => $inactive],
        ]];

        $this->assertTrue($presenter->messages($row(true), $me->id, 600)[0]['sender_inactive']);
        $this->assertFalse($presenter->messages($row(false), $me->id, 600)[0]['sender_inactive']);
    }

    public function test_bubble_animation_delay_is_end_anchored_and_capped(): void
    {
        $rows = array_map(fn(int $id) => [
            'id' => $id, 'sender_id' => 2, 'body' => 'x', 'created_at' => now()->toISOString(),
            'sender' => ['name' => 'S', 'avatar' => null, 'online' => false],
        ], range(1, 15));

        $delays = array_column((new \App\Livewire\Dashboard\Contact\Presentation\ContactPresenter())->messages($rows, 1, 600), 'animation_delay');

        $this->assertEquals(0, end($delays));
        $this->assertEquals(0.4, $delays[0]);
        $this->assertEquals(0.4, max($delays));
    }

    public function test_an_inactive_user_cannot_send_direct_messages(): void
    {
        $ghost = User::factory()->create(['status' => 'inactive']);
        $recipient = User::factory()->create(['status' => 'active']);
        $this->actingAs($ghost);
        $form = Livewire::test(Main::class)->instance()->composer;
        $form->body = 'hello';

        try {
            app(SendMessageAction::class)->execute($form, $recipient->id);
            $this->fail('inactive sender must be refused');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->assertSame(0, Message::where('sender_id', $ghost->id)->count());
    }
}
