<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Enums\ReleaseRequestStatus;
use App\Enums\ReleaseRequestType;
use App\Livewire\Dashboard\ReleaseRequest\Main;
use App\Models\ReleaseRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ReleaseRequestTest extends TestCase
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

    public function test_submit_creates_with_server_forced_user_and_status(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'remote']);

        Livewire::actingAs($user)->test(Main::class)
            ->call('open')
            ->assertSet('form.type', ReleaseRequestType::Recommendation->value)
            ->assertSet('activeTab', 'submit')
            ->set('form.title', 'پیشنهاد ماژول هزینه')
            ->set('form.body', 'ماژولی برای پیگیری هزینه‌های ماهانه دلخواه است.')
            ->call('submit')
            ->assertHasNoErrors();

        $rr = ReleaseRequest::where('title', 'پیشنهاد ماژول هزینه')->firstOrFail();

        $this->assertSame($user->id, $rr->user_id);
        $this->assertSame(ReleaseRequestType::Recommendation->value, $rr->type);
        $this->assertSame(ReleaseRequestStatus::Open->value, $rr->status);
        $this->assertSame(1, ReleaseRequest::where('user_id', $user->id)->count());
    }

    public function test_validation_rules_enforced(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'remote']);

        Livewire::actingAs($user)->test(Main::class)
            ->set('form.title', '')
            ->set('form.body', '')
            ->call('submit')
            ->assertHasErrors(['form.title' => 'required'])
            ->assertHasErrors(['form.body' => 'required']);

        Livewire::actingAs($user)->test(Main::class)
            ->set('form.title', 'ab')
            ->set('form.body', 'ab')
            ->call('submit')
            ->assertHasErrors(['form.title', 'form.body']);

        $this->assertSame(0, ReleaseRequest::where('user_id', $user->id)->count());
    }

    public function test_tag_only_title_and_body_are_rejected_instead_of_saving_as_blank(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'remote']);

        Livewire::actingAs($user)->test(Main::class)
            ->set('form.title', '<a>')
            ->set('form.body', '<div>')
            ->call('submit')
            ->assertHasErrors(['form.title', 'form.body']);

        $this->assertSame(0, ReleaseRequest::where('user_id', $user->id)->count());
    }

    public function test_bug_enum_case_submit_and_open_type_resolution(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'remote']);

        Livewire::actingAs($user)->test(Main::class)
            ->assertSee('پشتیبانی نرم افزار')
            ->assertSee('پیشنهاد ماژول')
            ->assertSee('گزارش باگ')
            ->call('open', ReleaseRequestType::Bug->value)
            ->assertSet('form.type', ReleaseRequestType::Bug->value)
            ->set('form.title', 'گزارش باگ صفحه تقویم')
            ->set('form.body', 'با کلیک روی روز خالی خطای سرور رخ می‌دهد.')
            ->call('submit')
            ->assertHasNoErrors();

        $rr = ReleaseRequest::where('title', 'گزارش باگ صفحه تقویم')->firstOrFail();
        $this->assertSame(ReleaseRequestType::Bug->value, $rr->type);
        $this->assertSame($user->id, $rr->user_id);

        Livewire::actingAs($user)->test(Main::class)
            ->call('open', 'invalid-type')
            ->assertSet('form.type', ReleaseRequestType::Recommendation->value)
            ->call('open')
            ->assertSet('form.type', ReleaseRequestType::Recommendation->value);
    }

    public function test_rate_limiter_blocks_sixth_attempt(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'remote']);
        $test = Livewire::actingAs($user)->test(Main::class);

        for ($i = 1; $i <= 5; $i++) {
            $test->set('form.title', 'درخواست شماره ' . $i)
                ->set('form.body', 'متن درخواست شماره ' . $i . ' برای آزمون محدودسازی نرخ.')
                ->call('submit')
                ->assertHasNoErrors();
        }

        $this->assertSame(5, ReleaseRequest::where('user_id', $user->id)->count());

        $test->set('form.title', 'درخواست شماره ۶')
            ->set('form.body', 'متن درخواست شماره ۶ برای آزمون محدودسازی نرخ.')
            ->call('submit')
            ->assertHasErrors(['form.body']);

        $this->assertSame(5, ReleaseRequest::where('user_id', $user->id)->count());
    }

    public function test_duplicate_title_and_body_rejected_for_same_user_but_allowed_for_another(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'remote']);

        Livewire::actingAs($user)->test(Main::class)
            ->set('form.title', 'عنوان تکراری برای آزمون')
            ->set('form.body', 'متن تکراری برای آزمون درخواست دوباره.')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(1, ReleaseRequest::where('user_id', $user->id)->count());

        Livewire::actingAs($user)->test(Main::class)
            ->set('form.title', 'عنوان تکراری برای آزمون')
            ->set('form.body', 'متن تکراری برای آزمون درخواست دوباره.')
            ->call('submit')
            ->assertHasErrors(['form.body']);

        $this->assertSame(1, ReleaseRequest::where('user_id', $user->id)->count());

        $other = User::factory()->create(['status' => 'active', 'presence' => 'remote']);

        Livewire::actingAs($other)->test(Main::class)
            ->set('form.title', 'عنوان تکراری برای آزمون')
            ->set('form.body', 'متن تکراری برای آزمون درخواست دوباره.')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(1, ReleaseRequest::where('user_id', $other->id)->count());
    }

    public function test_a_concurrent_in_flight_submission_for_the_same_user_is_rejected_instead_of_racing_the_duplicate_check(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'remote']);

        $lock = Cache::lock("release-request-submit:{$user->id}", 10);
        $lock->get();

        try {
            Livewire::actingAs($user)->test(Main::class)
                ->set('form.title', 'عنوان هم‌زمان برای آزمون قفل')
                ->set('form.body', 'متن هم‌زمان برای آزمون قفل ارسال.')
                ->call('submit')
                ->assertHasErrors(['form.body']);

            $this->assertSame(0, ReleaseRequest::where('user_id', $user->id)->count());
        } finally {
            $lock->release();
        }

        Livewire::actingAs($user)->test(Main::class)
            ->set('form.title', 'عنوان هم‌زمان برای آزمون قفل')
            ->set('form.body', 'متن هم‌زمان برای آزمون قفل ارسال.')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(1, ReleaseRequest::where('user_id', $user->id)->count());
    }

    public function test_body_strips_html_tags(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'remote']);

        Livewire::actingAs($user)->test(Main::class)
            ->set('form.title', 'کاوش امنیتی')
            ->set('form.body', '<script>alert(1)</script>متن ساده اینجا')
            ->call('submit')
            ->assertHasNoErrors();

        $rr = ReleaseRequest::where('title', 'کاوش امنیتی')->firstOrFail();

        $this->assertStringNotContainsString('<script>', $rr->body);
        $this->assertStringNotContainsString('</script>', $rr->body);
        $this->assertStringContainsString('متن ساده اینجا', $rr->body);
    }

    public function test_history_tab_lists_only_own_requests(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'remote']);
        $other = User::factory()->create(['status' => 'active', 'presence' => 'remote']);

        ReleaseRequest::factory()->create([
            'user_id' => $owner->id,
            'title'   => 'درخواست اختصاصی کاربر مالک',
        ]);

        ReleaseRequest::factory()->create([
            'user_id' => $other->id,
            'title'   => 'درخواست کاربر دیگر مخفی',
        ]);

        Livewire::actingAs($owner)->test(Main::class)
            ->call('switchTab', 'history')
            ->assertSet('activeTab', 'history')
            ->assertSee('درخواست اختصاصی کاربر مالک')
            ->assertDontSee('درخواست کاربر دیگر مخفی');
    }

    public function test_switch_tab_validates_input(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'remote']);

        Livewire::actingAs($user)->test(Main::class)
            ->call('switchTab', 'history')
            ->assertSet('activeTab', 'history')
            ->call('switchTab', 'submit')
            ->assertSet('activeTab', 'submit')
            ->call('switchTab', 'tampered')
            ->assertSet('activeTab', 'submit');
    }

    public function test_load_more_grows_pagination_and_open_resets_state(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'remote']);

        ReleaseRequest::factory()->count(7)->create(['user_id' => $user->id]);

        Livewire::actingAs($user)->test(Main::class)
            ->call('switchTab', 'history')
            ->assertSet('perPage', 5)
            ->assertSee('$wire.loadMore()', false)
            ->call('loadMore')
            ->assertSet('perPage', 10)
            ->assertDontSee('$wire.loadMore()', false)
            ->call('open')
            ->assertSet('activeTab', 'submit')
            ->assertSet('perPage', 5);
    }

    public function test_mount_aborts_401_for_guest(): void
    {
        Livewire::test(Main::class)->assertStatus(401);
    }

    public function test_open_without_arg_defaults_type_to_recommendation(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'remote']);

        Livewire::actingAs($user)->test(Main::class)
            ->assertSet('show', false)
            ->call('open')
            ->assertSet('show', true)
            ->assertSet('activeTab', 'submit')
            ->assertSet('perPage', 5)
            ->assertSet('form.type', ReleaseRequestType::Recommendation->value)
            ->assertSet('form.title', '')
            ->assertSet('form.body', '');
    }

    public function test_open_with_support_type_sets_form_type(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'remote']);

        Livewire::actingAs($user)->test(Main::class)
            ->call('open', ReleaseRequestType::Support->value)
            ->assertSet('form.type', ReleaseRequestType::Support->value)
            ->assertSet('show', true);
    }

    public function test_open_resets_stale_form_and_validation(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'remote']);

        Livewire::actingAs($user)->test(Main::class)
            ->set('form.title', 'ab')
            ->set('form.body', 'ab')
            ->call('submit')
            ->assertHasErrors(['form.title', 'form.body'])
            ->call('open')
            ->assertHasNoErrors()
            ->assertSet('form.title', '')
            ->assertSet('form.body', '');
    }

    public function test_close_resets_form_and_hides_modal(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'remote']);

        Livewire::actingAs($user)->test(Main::class)
            ->call('open')
            ->assertSet('show', true)
            ->set('form.title', 'عنوان آزمایشی')
            ->set('form.body', 'متن آزمایشی طولانی')
            ->call('close')
            ->assertSet('show', false)
            ->assertSet('form.title', '')
            ->assertSet('form.body', '');
    }

    public function test_types_computed_returns_all_release_request_type_cases(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'remote']);

        $types = Livewire::actingAs($user)->test(Main::class)->instance()->types;

        $this->assertSame(ReleaseRequestType::cases(), $types);
        $this->assertSame(
            ['support', 'recommendation', 'bug'],
            array_map(fn(ReleaseRequestType $t) => $t->value, $types),
        );
    }

    public function test_submit_dispatches_success_toast_and_closes_modal(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'remote']);

        Livewire::actingAs($user)->test(Main::class)
            ->call('open')
            ->set('form.title', 'درخواست پشتیبانی فعال')
            ->set('form.body', 'متن معتبر درخواست پشتیبانی برای آزمون ارسال.')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertDispatched('toast', message: 'درخواست شما با موفقیت ثبت شد.', type: 'success')
            ->assertSet('show', false)
            ->assertSet('form.title', '')
            ->assertSet('form.body', '');

        $this->assertSame(1, ReleaseRequest::where('user_id', $user->id)->count());
    }

    public function test_title_strips_html_tags(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'remote']);

        Livewire::actingAs($user)->test(Main::class)
            ->set('form.title', '<b>عنوان</b><script>bad</script>معتبر اینجا')
            ->set('form.body', 'متن معتبر برای آزمون پاکسازی عنوان.')
            ->call('submit')
            ->assertHasNoErrors();

        $rr = ReleaseRequest::where('body', 'متن معتبر برای آزمون پاکسازی عنوان.')->firstOrFail();

        $this->assertStringNotContainsString('<script>', $rr->title);
        $this->assertStringNotContainsString('<b>', $rr->title);
        $this->assertStringNotContainsString('</b>', $rr->title);
        $this->assertStringContainsString('عنوان', $rr->title);
        $this->assertStringContainsString('معتبر اینجا', $rr->title);
    }

    public function test_invalid_type_rejected_by_validation_on_submit(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'remote']);

        Livewire::actingAs($user)->test(Main::class)
            ->set('form.type', 'tampered')
            ->set('form.title', 'عنوان معتبر برای آزمون')
            ->set('form.body', 'متن معتبر برای آزمون نوع نامعتبر.')
            ->call('submit')
            ->assertHasErrors(['form.type']);

        $this->assertSame(0, ReleaseRequest::where('user_id', $user->id)->count());
    }

    public function test_title_exceeding_max_length_rejected(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'remote']);
        $longTitle = str_repeat('x', 192);

        Livewire::actingAs($user)->test(Main::class)
            ->set('form.type', ReleaseRequestType::Support->value)
            ->set('form.title', $longTitle)
            ->set('form.body', 'متن معتبر برای آزمون سقف طول عنوان.')
            ->call('submit')
            ->assertHasErrors(['form.title' => 'max']);

        $this->assertSame(0, ReleaseRequest::where('user_id', $user->id)->count());
    }

    public function test_body_exceeding_max_length_rejected(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'remote']);
        $longBody = str_repeat('x', 5001);

        Livewire::actingAs($user)->test(Main::class)
            ->set('form.type', ReleaseRequestType::Support->value)
            ->set('form.title', 'عنوان معتبر برای آزمون')
            ->set('form.body', $longBody)
            ->call('submit')
            ->assertHasErrors(['form.body' => 'max']);

        $this->assertSame(0, ReleaseRequest::where('user_id', $user->id)->count());
    }

    public function test_history_empty_state_shows_when_user_has_no_requests(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'remote']);

        Livewire::actingAs($user)->test(Main::class)
            ->call('switchTab', 'history')
            ->assertSet('activeTab', 'history')
            ->assertSee('درخواستی ثبت نشده')
            ->assertSee('wire:key="release-request-empty"', false);

        $this->assertSame(0, ReleaseRequest::where('user_id', $user->id)->count());
    }

    public function test_my_requests_orders_newest_first(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'remote']);

        $old = ReleaseRequest::factory()->create(['user_id' => $user->id, 'title' => 'قدیمی‌ترین درخواست']);
        $old->forceFill(['created_at' => now()->subDays(3)])->save();

        $mid = ReleaseRequest::factory()->create(['user_id' => $user->id, 'title' => 'درخواست میانه']);
        $mid->forceFill(['created_at' => now()->subDays(2)])->save();

        $new = ReleaseRequest::factory()->create(['user_id' => $user->id, 'title' => 'جدیدترین درخواست']);
        $new->forceFill(['created_at' => now()->subDay()])->save();

        $items = Livewire::actingAs($user)->test(Main::class)
            ->call('switchTab', 'history')
            ->instance()->myRequests;

        $this->assertSame('جدیدترین درخواست', $items->first()->title);
        $this->assertSame('قدیمی‌ترین درخواست', $items->last()->title);
    }

    public function test_validation_failure_does_not_consume_rate_limiter(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'remote']);
        $test = Livewire::actingAs($user)->test(Main::class);

        for ($i = 1; $i <= 4; $i++) {
            $test->set('form.title', 'درخواست شماره ' . $i)
                ->set('form.body', 'متن درخواست شماره ' . $i . ' برای آزمون محدودسازی نرخ.')
                ->call('submit')
                ->assertHasNoErrors();
        }

        $this->assertSame(4, ReleaseRequest::where('user_id', $user->id)->count());

        $test->set('form.title', 'ab')
            ->set('form.body', 'ab')
            ->call('submit')
            ->assertHasErrors(['form.title', 'form.body']);

        $this->assertSame(4, ReleaseRequest::where('user_id', $user->id)->count());

        $test->set('form.title', 'درخواست شماره پنج')
            ->set('form.body', 'متن درخواست شماره پنج برای آزمون محدودسازی نرخ.')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(5, ReleaseRequest::where('user_id', $user->id)->count());
    }

    public function test_submit_persists_canonical_attachment_shape(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['status' => 'active', 'presence' => 'remote']);
        $file = UploadedFile::fake()->image('proof.jpg');

        Livewire::actingAs($user)->test(Main::class)
            ->set('form.title', 'درخواست دارای پیوست')
            ->set('form.body', 'متن معتبر برای آزمون پیوست فایل.')
            ->set('form.attachments', [$file])
            ->call('submit')
            ->assertHasNoErrors();

        $rr = ReleaseRequest::where('title', 'درخواست دارای پیوست')->firstOrFail();
        $attachments = $rr->attachments;

        $this->assertCount(1, $attachments);
        $this->assertStringStartsWith('release_request/attachments/', $attachments[0]['path']);
        $this->assertSame('proof.jpg', $attachments[0]['name']);
        $this->assertSame('image/jpeg', $attachments[0]['mime']);
        $this->assertGreaterThan(0, $attachments[0]['size']);
        Storage::disk('public')->assertExists($attachments[0]['path']);
    }

    public function test_remove_attachment_drops_a_pending_upload_from_the_form(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['status' => 'active', 'presence' => 'remote']);
        $file1 = UploadedFile::fake()->image('a.jpg');
        $file2 = UploadedFile::fake()->image('b.jpg');

        $component = Livewire::actingAs($user)->test(Main::class)
            ->set('form.attachments', [$file1, $file2]);

        $this->assertCount(2, $component->instance()->form->attachments);

        $component->call('removeAttachment', 0);
        $this->assertCount(1, $component->instance()->form->attachments);
    }

    public function test_attachment_exceeding_max_items_is_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['status' => 'active', 'presence' => 'remote']);
        $files = array_map(fn($i) => UploadedFile::fake()->image("file{$i}.jpg"), range(1, 6));

        Livewire::actingAs($user)->test(Main::class)
            ->set('form.title', 'درخواست با پیوست بیش از حد')
            ->set('form.body', 'متن معتبر برای آزمون سقف تعداد پیوست.')
            ->set('form.attachments', $files)
            ->call('submit')
            ->assertHasErrors(['attachments']);

        $this->assertSame(0, ReleaseRequest::where('user_id', $user->id)->count());
    }

    public function test_attachment_with_disallowed_mime_is_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['status' => 'active', 'presence' => 'remote']);
        $file = UploadedFile::fake()->create('script.exe', 10);

        Livewire::actingAs($user)->test(Main::class)
            ->set('form.title', 'درخواست با فرمت نامعتبر')
            ->set('form.body', 'متن معتبر برای آزمون فرمت غیرمجاز.')
            ->set('form.attachments', [$file])
            ->call('submit')
            ->assertHasErrors(['attachments.0']);

        $this->assertSame(0, ReleaseRequest::where('user_id', $user->id)->count());
    }

    public function test_history_tab_shows_response_for_rejected_request(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'remote']);
        ReleaseRequest::factory()->create([
            'user_id' => $user->id,
            'title' => 'درخواست رد شده تست',
            'status' => ReleaseRequestStatus::Rejected->value,
            'response' => 'خارج از ضوابط',
        ]);

        Livewire::actingAs($user)->test(Main::class)
            ->call('switchTab', 'history')
            ->assertSee('درخواست رد شده تست')
            ->assertSee('خارج از ضوابط')
            ->assertSee('پاسخ');
    }

    public function test_history_tab_shows_response_for_resolved_request(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'remote']);
        ReleaseRequest::factory()->create([
            'user_id' => $user->id,
            'title' => 'درخواست حل‌شده تست',
            'status' => ReleaseRequestStatus::Resolved->value,
            'response' => 'انجام شد، متشکریم',
        ]);

        Livewire::actingAs($user)->test(Main::class)
            ->call('switchTab', 'history')
            ->assertSee('درخواست حل‌شده تست')
            ->assertSee('انجام شد، متشکریم');
    }

    public function test_history_tab_hides_response_box_when_response_is_empty(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'remote']);
        ReleaseRequest::factory()->create([
            'user_id' => $user->id,
            'title' => 'درخواست باز تست',
            'status' => ReleaseRequestStatus::Open->value,
            'response' => null,
        ]);

        Livewire::actingAs($user)->test(Main::class)
            ->call('switchTab', 'history')
            ->assertSee('درخواست باز تست')
            ->assertDontSee('data-response-box', false);
    }

    public function test_history_tab_shows_attachment_link(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'remote']);
        ReleaseRequest::factory()->create([
            'user_id' => $user->id,
            'title' => 'درخواست با پیوست موجود',
            'attachments' => [
                ['path' => 'release_request/attachments/seed.jpg', 'name' => 'seed.jpg', 'mime' => 'image/jpeg', 'size' => 100],
            ],
        ]);

        Livewire::actingAs($user)->test(Main::class)
            ->call('switchTab', 'history')
            ->assertSee('درخواست با پیوست موجود')
            ->assertSee('seed.jpg');
    }

    public function test_type_meta_maps_each_release_request_type_to_icon_color_label(): void
    {
        $presenter = new \App\Livewire\Dashboard\ReleaseRequest\Presentation\ReleaseRequestPresenter();

        $support = $presenter->typeMeta(ReleaseRequestType::Support->value);
        $this->assertSame('support_agent', $support['icon']);
        $this->assertSame('var(--md-sys-color-primary)', $support['color']);
        $this->assertSame('پشتیبانی نرم افزار', $support['label']);

        $recommendation = $presenter->typeMeta(ReleaseRequestType::Recommendation->value);
        $this->assertSame('lightbulb', $recommendation['icon']);
        $this->assertSame('var(--md-sys-color-secondary)', $recommendation['color']);

        $bug = $presenter->typeMeta(ReleaseRequestType::Bug->value);
        $this->assertSame('bug_report', $bug['icon']);
        $this->assertSame('var(--md-sys-color-error)', $bug['color']);
    }

    public function test_status_meta_returns_response_box_colors_for_every_status(): void
    {
        $presenter = new \App\Livewire\Dashboard\ReleaseRequest\Presentation\ReleaseRequestPresenter();

        foreach (ReleaseRequestStatus::cases() as $status) {
            $meta = $presenter->statusMeta($status->value);
            $this->assertArrayHasKey('icon', $meta);
            $this->assertArrayHasKey('color', $meta);
            $this->assertArrayHasKey('label', $meta);
            $this->assertArrayHasKey('responseColor', $meta, 'responseColor must exist for every status to avoid undefined-index in the response box');
            $this->assertArrayHasKey('responseBg', $meta);
            $this->assertArrayHasKey('responseIcon', $meta);
            $this->assertSame($status === ReleaseRequestStatus::Rejected, $meta['isRejected']);
        }

        $rejected = $presenter->statusMeta(ReleaseRequestStatus::Rejected->value);
        $this->assertSame('var(--md-sys-color-error)', $rejected['responseColor']);
        $this->assertSame('var(--md-sys-color-error-container)', $rejected['responseBg']);
        $this->assertSame('cancel', $rejected['responseIcon']);

        $resolved = $presenter->statusMeta(ReleaseRequestStatus::Resolved->value);
        $this->assertSame('var(--md-sys-color-primary)', $resolved['responseColor']);
        $this->assertSame('var(--md-sys-color-primary-container)', $resolved['responseBg']);
        $this->assertSame('forum', $resolved['responseIcon']);
    }
}