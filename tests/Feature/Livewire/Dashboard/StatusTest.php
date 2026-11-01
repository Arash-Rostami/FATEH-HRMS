<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Enums\PresenceStatus;
use App\Enums\ReservationStatus;
use App\Jobs\LogMissingSkillJob;
use App\Livewire\Dashboard\Tab\Status;
use App\Livewire\Dashboard\Tab\Presentation\StatusPresenter;
use App\Models\Department;
use App\Models\Profile;
use App\Models\ProfileDetail;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\Skill;
use App\Models\SkillUser;
use App\Models\User;
use App\Services\User\UserKeyGrouper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class StatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        User::resetTodaysDeskCache();
        Cache::forget(UserKeyGrouper::CACHE_KEY);
        Livewire::withoutLazyLoading();
        config(['services.kavenegar.key' => 'TESTKEY']);
        \Illuminate\Support\Facades\Http::fake(['api.kavenegar.com/*' => \Illuminate\Support\Facades\Http::response(['return' => ['status' => 200]])]);
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        Cache::forget(UserKeyGrouper::CACHE_KEY);
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

    private function makeDept(array $overrides = []): Department
    {
        return Department::factory()->create(array_merge(['code' => strtoupper(Str::random(8))], $overrides));
    }

    private function makeUser(string $deptCode, string $position = 'employee', array $details = []): User
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $profile = Profile::factory()->create(['user_id' => $user->id, 'department_id' => $deptCode, 'position' => $position]);

        foreach ($details as $key => $value) {
            ProfileDetail::create(['profile_id' => $profile->id, 'section' => 'employment', 'key' => $key, 'value' => $value]);
        }

        return $user->refresh();
    }

    private function orgTree(User $actor): array
    {
        Livewire::withoutLazyLoading();
        return Livewire::actingAs($actor)->test(Status::class)->instance()->orgTree();
    }

    public function test_status_send_sms_success_path()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $target = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $target->id, 'cellphone' => '09120000000']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($actor)
            ->test(Status::class)
            ->call('sendSms', (string) $target->id)
            ->assertHasNoErrors()
            ->assertDispatched('toast', message: 'پیامک با موفقیت ارسال شد', type: 'success');
    }

    public function test_status_send_sms_is_a_no_op_when_the_tenant_switched_sms_off()
    {
        config(['app.off' => ['notify:sms']]);
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $target = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $target->id, 'cellphone' => '09120000000']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($actor)
            ->test(Status::class)
            ->call('sendSms', (string) $target->id)
            ->assertNotDispatched('toast');
    }

    public function test_status_sms_modal_validates_then_sends_the_typed_text_prefixed_with_the_sender_name()
    {
        config(['services.kavenegar.key' => 'SECRETKEY']);
        \Illuminate\Support\Facades\Http::fake(['api.kavenegar.com/*' => \Illuminate\Support\Facades\Http::response(['return' => ['status' => 200]])]);
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'name' => 'آرش رستمی']);
        $target = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $target->id, 'cellphone' => '09120000000']);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($actor)->test(Status::class)
            ->set('smsUserId', $target->id)
            ->set('showSmsModal', true)
            ->call('submitSms')
            ->assertHasErrors(['smsText' => 'required'])
            ->set('smsText', str_repeat('ا', 141))
            ->call('submitSms')
            ->assertHasErrors(['smsText' => 'max']);

        \Illuminate\Support\Facades\Http::assertNothingSent();

        $component->set('smsText', 'جلسه ساعت ۱۰')
            ->call('submitSms')
            ->assertHasNoErrors()
            ->assertSet('showSmsModal', false)
            ->assertSet('smsText', '')
            ->assertDispatched('toast', message: 'پیامک با موفقیت ارسال شد', type: 'success');

        \Illuminate\Support\Facades\Http::assertSent(fn ($request) => $request['receptor'] === '09120000000'
            && $request['message'] === 'آرش رستمی: جلسه ساعت ۱۰');
        $this->assertDatabaseHas('notify_logs', ['source' => 'status', 'sender_id' => $actor->id, 'user_id' => $target->id, 'status' => 'sent', 'recipient' => '*******0000', 'notify_rule_id' => null]);
    }

    public function test_status_sms_tells_the_user_when_the_sms_service_is_not_configured()
    {
        config(['services.kavenegar.key' => null]);
        \Illuminate\Support\Facades\Http::fake();
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $target = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $target->id, 'cellphone' => '09120000000']);

        Livewire::actingAs($actor)->test(Status::class)
            ->call('sendSms', (string) $target->id)
            ->assertDispatched('toast', message: 'سرویس پیامک هنوز پیکربندی نشده است؛ با مدیر سیستم تماس بگیرید.', type: 'error');

        \Illuminate\Support\Facades\Http::assertNothingSent();
    }

    public function test_status_sms_reports_a_gateway_failure_and_refuses_do_not_disturb_colleagues()
    {
        config(['services.kavenegar.key' => 'SECRETKEY']);
        \Illuminate\Support\Facades\Http::swap(new \Illuminate\Http\Client\Factory);
        \Illuminate\Support\Facades\Http::fake(['api.kavenegar.com/*' => \Illuminate\Support\Facades\Http::response(['return' => ['status' => 418, 'message' => 'اعتبار کافی نیست']])]);
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $target = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $busy = User::factory()->create(['status' => 'active', 'presence' => PresenceStatus::Grumpy->value]);
        Profile::factory()->create(['user_id' => $target->id, 'cellphone' => '09120000000']);
        Profile::factory()->create(['user_id' => $busy->id, 'cellphone' => '09121111111']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($actor)->test(Status::class)
            ->set('smsUserId', $target->id)
            ->set('smsText', 'سلام')
            ->call('submitSms')
            ->assertDispatched('toast', message: 'ارسال پیامک ناموفق بود؛ کمی بعد دوباره تلاش کنید.', type: 'error');

        \Illuminate\Support\Facades\Http::assertSentCount(1);
        $this->assertDatabaseHas('notify_logs', ['source' => 'status', 'sender_id' => $actor->id, 'user_id' => $target->id, 'status' => 'failed']);
        $this->assertDatabaseMissing('notify_logs', ['source' => 'status', 'user_id' => $busy->id]);

        Livewire::actingAs($actor)->test(Status::class)
            ->call('sendSms', (string) $busy->id)
            ->assertDispatched('toast', message: 'این همکار در حال حاضر پیامک دریافت نمی‌کند.', type: 'error');
    }

    public function test_status_send_sms_no_phone_error_path()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $target = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $target->id, 'cellphone' => null]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($actor)
            ->test(Status::class)
            ->call('sendSms', (string) $target->id)
            ->assertHasNoErrors()
            ->assertDispatched('toast', message: 'شماره تلفن همراه یافت نشد', type: 'error');
    }

    public function test_status_send_sms_rate_limited_on_fourth_send_within_window()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $target = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $target->id, 'cellphone' => '09120000000']);
        RateLimiter::clear('sms-send:' . $actor->id);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($actor)->test(Status::class);
        for ($i = 0; $i < 3; $i++) {
            $component->call('sendSms', (string) $target->id);
        }

        $component->call('sendSms', (string) $target->id)
            ->assertHasNoErrors()
            ->assertDispatched('toast', message: 'درخواست‌های شما بیش از حد مجاز است؛ کمی بعد دوباره تلاش کنید.', type: 'error');
    }

    public function test_status_send_sms_from_one_sender_to_one_colleague_is_capped_per_hour_while_others_still_can()
    {
        $sender = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $target = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $target->id, 'cellphone' => '09120000000']);
        Livewire::withoutLazyLoading();

        foreach (range(1, 3) as $i) {
            RateLimiter::clear('sms-send:' . $sender->id);
            Livewire::actingAs($sender)->test(Status::class)->call('sendSms', (string) $target->id);
        }

        RateLimiter::clear('sms-send:' . $sender->id);
        $other = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($other)->test(Status::class)
            ->call('sendSms', (string) $target->id)
            ->assertDispatched('toast', type: 'success');

        Livewire::actingAs($sender)->test(Status::class)
            ->call('sendSms', (string) $target->id)
            ->assertDispatched('toast', message: 'به این همکار اخیراً چند پیامک فرستاده شده است؛ بعداً دوباره تلاش کنید.', type: 'error');
    }

    public function test_status_send_sms_for_nonexistent_user_dispatches_error_toast()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        RateLimiter::clear('sms-send:' . $actor->id);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($actor)
            ->test(Status::class)
            ->call('sendSms', '999999999')
            ->assertHasNoErrors()
            ->assertDispatched('toast', message: 'کاربر یافت نشد.', type: 'error');
    }

    public function test_status_renders()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($actor)
            ->test(Status::class)
            ->assertHasNoErrors();
    }

    public function test_status_view_toggle_renders_through_the_shared_component_and_switches_view()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($actor)
            ->test(Status::class)
            ->assertSeeHtml('title="نمای کارتی"')
            ->assertSeeHtml('title="نمای ساختار سازمانی"')
            ->assertSeeHtml('view = &#039;grid&#039;;$wire.toggleView(&#039;grid&#039;)')
            ->assertSeeHtml('view = &#039;chart&#039;;$wire.toggleView(&#039;chart&#039;)')
            ->assertSeeHtml('hidden md:flex')
            ->call('toggleView', 'chart')
            ->assertSet('view', 'chart')
            ->call('toggleView', 'grid')
            ->assertSet('view', 'grid');
    }

    public function test_todays_desk_extension_returns_extension_for_active_seat_today()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $seat = Resource::factory()->create([
            'type' => 'seat',
            'status' => 'active',
            'metadata' => ['unit' => '39', 'floor' => '7', 'extension' => '101'],
        ]);

        Reservation::factory()->create([
            'user_id' => $user->id,
            'resource_id' => $seat->id,
            'start_time' => now()->startOfDay(),
            'end_time' => now()->endOfDay(),
            'is_full_day' => true,
            'status' => ReservationStatus::Active->value,
        ]);

        User::primeTodaysDeskCache();

        $this->assertSame('101', $user->refresh()->getTodaysDeskExtension());
    }

    public function test_todays_desk_extension_null_without_reservation()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        User::primeTodaysDeskCache();

        $this->assertNull($user->refresh()->getTodaysDeskExtension());
    }

    public function test_todays_desk_extension_null_for_excluded_resource_type()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $spot = Resource::factory()->create([
            'type' => 'spot',
            'status' => 'active',
            'metadata' => ['floor' => '-1', 'card' => '200'],
        ]);

        Reservation::factory()->create([
            'user_id' => $user->id,
            'resource_id' => $spot->id,
            'start_time' => now()->startOfDay(),
            'end_time' => now()->endOfDay(),
            'is_full_day' => true,
            'status' => ReservationStatus::Active->value,
        ]);

        User::primeTodaysDeskCache();

        $this->assertNull($user->refresh()->getTodaysDeskExtension());
    }

    public function test_todays_desk_extension_includes_meeting_type_now()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $meeting = Resource::factory()->create([
            'type' => 'meeting',
            'status' => 'active',
            'metadata' => ['extension' => '200'],
        ]);

        Reservation::factory()->create([
            'user_id' => $user->id,
            'resource_id' => $meeting->id,
            'start_time' => now()->startOfDay(),
            'end_time' => now()->endOfDay(),
            'is_full_day' => true,
            'status' => ReservationStatus::Active->value,
        ]);

        User::primeTodaysDeskCache();

        $this->assertSame('200', $user->refresh()->getTodaysDeskExtension());
    }

    public function test_todays_desk_extension_null_for_cancelled_or_released_seat()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $seat = Resource::factory()->create([
            'type' => 'seat',
            'status' => 'active',
            'metadata' => ['extension' => '303'],
        ]);

        Reservation::factory()->create([
            'user_id' => $user->id,
            'resource_id' => $seat->id,
            'start_time' => now()->startOfDay(),
            'end_time' => now()->endOfDay(),
            'is_full_day' => true,
            'status' => ReservationStatus::CancelledUser->value,
        ]);

        User::primeTodaysDeskCache();

        $this->assertNull($user->refresh()->getTodaysDeskExtension());
    }

    public function test_status_card_hides_action_bar_and_dash_placeholders_when_no_contact_or_extension()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $target = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'last_seen' => null]);
        Profile::factory()->create(['user_id' => $target->id, 'cellphone' => null]);

        User::resetTodaysDeskCache();
        $card = $this->renderUserCard($actor, $target);

        $this->assertStringNotContainsString('---', $card);
        $this->assertStringNotContainsString('tel:', $card);
    }

    public function test_status_card_shows_extension_without_placeholder()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $target = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'last_seen' => null]);
        Profile::factory()->create(['user_id' => $target->id, 'cellphone' => null]);
        $seat = Resource::factory()->create([
            'type' => 'seat',
            'status' => 'active',
            'metadata' => ['extension' => '414'],
        ]);
        Reservation::factory()->create([
            'user_id' => $target->id,
            'resource_id' => $seat->id,
            'start_time' => now()->startOfDay(),
            'end_time' => now()->endOfDay(),
            'is_full_day' => true,
            'status' => ReservationStatus::Active->value,
        ]);

        $card = $this->renderUserCard($actor, $target);

        $this->assertStringContainsString('414', $card);
        $this->assertStringNotContainsString('tel:414', $card);
        $this->assertStringNotContainsString('---', $card);
    }

    public function test_status_card_extension_dials_office_number_then_extension_and_offers_copy()
    {
        config(['app.office_phone' => '021-26208352']);
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $target = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'last_seen' => null]);
        Profile::factory()->create(['user_id' => $target->id, 'cellphone' => null]);
        $seat = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => ['extension' => '414']]);
        Reservation::factory()->create([
            'user_id' => $target->id,
            'resource_id' => $seat->id,
            'start_time' => now()->startOfDay(),
            'end_time' => now()->endOfDay(),
            'is_full_day' => true,
            'status' => ReservationStatus::Active->value,
        ]);

        $card = $this->renderUserCard($actor, $target);

        $this->assertStringContainsString('href="tel:02126208352,414"', $card);
        $this->assertStringContainsString('content_copy', $card);
        $this->assertStringNotContainsString('021-26208352', $card);
    }

    public function test_status_card_hides_contact_rows_for_obscured_grumpy_but_keeps_chat()
    {
        config(['app.office_phone' => '02126208352']);
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $target = User::factory()->create(['status' => 'active', 'presence' => 'grumpy', 'last_seen' => null]);
        Profile::factory()->create(['user_id' => $target->id, 'cellphone' => '09120000000']);

        $card = $this->renderUserCard($actor, $target);

        $this->assertStringNotContainsString('09120000000', $card);
        $this->assertStringNotContainsString('mailto:', $card);
        $this->assertStringNotContainsString('href="tel:', $card);
        $this->assertStringContainsString('open=' . $target->id, $card);
        $this->assertStringContainsString('اطلاعات تماس در این وضعیت پنهان است', $card);
    }

    public function test_status_card_shows_chat_email_and_tenure_rows_but_no_chat_for_self()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $target = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'last_seen' => null]);
        Profile::factory()->create(['user_id' => $target->id, 'start_date' => now()->subYears(2)->subMonths(3)]);

        $card = $this->renderUserCard($actor, $target);

        $this->assertStringContainsString('open=' . $target->id, $card);
        $this->assertStringContainsString('mailto:' . $target->email, $card);
        $this->assertStringContainsString('سابقه', $card);

        $own = $this->renderUserCard($target, $target);
        $this->assertStringNotContainsString('open=' . $target->id, $own);
    }

    public function test_status_card_carries_the_focus_yoga_icon_and_label_for_timed_and_indefinite_focus()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $timed = User::factory()->create(['status' => 'active', 'presence' => 'busy', 'focus_until' => now()->addMinutes(45), 'last_seen' => null]);
        $indefinite = User::factory()->create(['status' => 'active', 'presence' => 'busy', 'focus_until' => now()->addMinutes(User::INDEFINITE_FOCUS_MINUTES), 'last_seen' => null]);
        $plainBusy = User::factory()->create(['status' => 'active', 'presence' => 'busy', 'focus_until' => null, 'last_seen' => null]);

        $timedCard = $this->renderUserCard($actor, $timed);
        $this->assertStringContainsString('self_improvement', $timedCard);
        $this->assertStringContainsString('title="مشغول — عدم مزاحمت · متمرکز تا', $timedCard);

        $indefiniteCard = $this->renderUserCard($actor, $indefinite);
        $this->assertStringContainsString('self_improvement', $indefiniteCard);
        $this->assertStringContainsString('title="مشغول — عدم مزاحمت · حالت تمرکز فعال"', $indefiniteCard);

        $plainCard = $this->renderUserCard($actor, $plainBusy);
        $this->assertStringContainsString('title="مشغول — عدم مزاحمت"', $plainCard);
        $this->assertStringNotContainsString('title="مشغول — عدم مزاحمت · ', $plainCard);
        $this->assertStringContainsString('do_not_disturb_on', $plainCard);
    }

    public function test_status_card_shows_dot_badge_and_unreachable_tag_only_for_leave_and_mission()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $onLeave = User::factory()->create(['status' => 'active', 'presence' => 'leave', 'last_seen' => now()]);
        $onsite = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'last_seen' => null]);

        $leaveCard = $this->renderUserCard($actor, $onLeave);
        $this->assertStringContainsString('پاسخ‌گو نیست', $leaveCard);
        $this->assertStringContainsString('آنلاین', $leaveCard);
        $this->assertStringContainsString('aspect-square', $leaveCard);

        $onsiteCard = $this->renderUserCard($actor, $onsite);
        $this->assertStringNotContainsString('پاسخ‌گو نیست', $onsiteCard);
        $this->assertStringContainsString('هرگز', $onsiteCard);
        $this->assertStringContainsString('shadow-[inset_0_0_0_2px_var(--md-sys-color-outline)]', $onsiteCard);
    }

    public function test_todays_reservations_label_returns_labeled_name_for_seat()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $seat = Resource::factory()->create([
            'type' => 'seat',
            'status' => 'active',
            'name' => '101',
            'metadata' => ['extension' => '101'],
        ]);

        Reservation::factory()->create([
            'user_id' => $user->id,
            'resource_id' => $seat->id,
            'start_time' => now()->startOfDay(),
            'end_time' => now()->endOfDay(),
            'is_full_day' => true,
            'status' => ReservationStatus::Active->value,
        ]);

        User::primeTodaysDeskCache();

        $this->assertSame('میز کار ⇄ 101', $user->refresh()->getTodaysReservationsLabel());
    }

    public function test_todays_reservations_label_returns_labeled_name_for_car_without_extension()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $car = Resource::factory()->create([
            'type' => 'car',
            'status' => 'active',
            'name' => 'B3',
            'metadata' => [],
        ]);

        Reservation::factory()->create([
            'user_id' => $user->id,
            'resource_id' => $car->id,
            'start_time' => now()->startOfDay(),
            'end_time' => now()->endOfDay(),
            'is_full_day' => true,
            'status' => ReservationStatus::Active->value,
        ]);

        User::primeTodaysDeskCache();
        $user->refresh();

        $this->assertSame('خودرو ⇄ B3', $user->getTodaysReservationsLabel());
        $this->assertNull($user->getTodaysDeskExtension());
    }

    public function test_todays_reservations_label_joins_multiple_reservations()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $seat = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'name' => '101', 'metadata' => ['extension' => '101']]);
        $car = Resource::factory()->create(['type' => 'car', 'status' => 'active', 'name' => 'B3', 'metadata' => []]);

        foreach ([$seat, $car] as $resource) {
            Reservation::factory()->create([
                'user_id' => $user->id,
                'resource_id' => $resource->id,
                'start_time' => now()->startOfDay(),
                'end_time' => now()->endOfDay(),
                'is_full_day' => true,
                'status' => ReservationStatus::Active->value,
            ]);
        }

        User::primeTodaysDeskCache();

        $this->assertSame('میز کار ⇄ 101، خودرو ⇄ B3', $user->refresh()->getTodaysReservationsLabel());
    }

    public function test_todays_reservations_label_null_without_reservation()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        User::primeTodaysDeskCache();

        $this->assertNull($user->refresh()->getTodaysReservationsLabel());
    }

    public function test_status_card_shows_icon_only_badge_for_car_reservation_without_extension()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $target = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'last_seen' => null]);
        Profile::factory()->create(['user_id' => $target->id, 'cellphone' => null]);
        $car = Resource::factory()->create([
            'type' => 'car',
            'status' => 'active',
            'name' => 'B3',
            'metadata' => [],
        ]);
        Reservation::factory()->create([
            'user_id' => $target->id,
            'resource_id' => $car->id,
            'start_time' => now()->startOfDay(),
            'end_time' => now()->endOfDay(),
            'is_full_day' => true,
            'status' => ReservationStatus::Active->value,
        ]);

        $card = $this->renderUserCard($actor, $target);

        $this->assertStringContainsString('خودرو ⇄ B3', $card);
        $this->assertStringContainsString('رزرو', $card);
        $this->assertStringNotContainsString('---', $card);
    }

    public function test_status_card_tooltip_shows_reservation_over_extension()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $target = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'last_seen' => null]);
        Profile::factory()->create(['user_id' => $target->id, 'cellphone' => null]);
        $seat = Resource::factory()->create([
            'type' => 'seat',
            'status' => 'active',
            'name' => '515',
            'metadata' => ['extension' => '515'],
        ]);
        Reservation::factory()->create([
            'user_id' => $target->id,
            'resource_id' => $seat->id,
            'start_time' => now()->startOfDay(),
            'end_time' => now()->endOfDay(),
            'is_full_day' => true,
            'status' => ReservationStatus::Active->value,
        ]);

        $card = $this->renderUserCard($actor, $target);

        $this->assertStringContainsString('515', $card);
        $this->assertStringContainsString('داخلی ۵۱۵', $card);
        $this->assertStringNotContainsString('داخلی: 515', $card);
    }

    private function renderUserCard(User $actor, User $target): string
    {
        Livewire::withoutLazyLoading();
        $html = Livewire::actingAs($actor)->test(Status::class)->html();
        $start = 'wire:key="user-' . $target->id . '-';
        $pos = strpos($html, $start);

        if ($pos === false) {
            return '';
        }

        $ends = array_filter([
            strpos($html, 'wire:key="user-', $pos + strlen($start)),
            strpos($html, 'x-show="view === \'chart\'"', $pos),
        ], fn ($p) => $p !== false);

        return substr($html, $pos, $ends ? min($ends) - $pos : null);
    }

    public function test_status_grid_user_carries_unit_and_section_detail()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        ProfileDetail::create(['profile_id' => $profile->id, 'section' => 'employment', 'key' => 'unit', 'value' => 'Operations']);
        ProfileDetail::create(['profile_id' => $profile->id, 'section' => 'employment', 'key' => 'section', 'value' => 'Logistics']);

        Livewire::withoutLazyLoading();
        $rendered = Livewire::actingAs($user)->test(Status::class)->instance()->users();

        $gridUser = $rendered->firstWhere('id', $user->id);
        $this->assertNotNull($gridUser);
        $this->assertSame('Operations', $gridUser->profile->detailsMap()->get('unit'));
        $this->assertSame('Logistics', $gridUser->profile->detailsMap()->get('section'));
    }

    public function test_status_grid_user_without_unit_section_renders_clean()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id]);

        Livewire::withoutLazyLoading();
        $rendered = Livewire::actingAs($user)->test(Status::class)->instance()->users();

        $gridUser = $rendered->firstWhere('id', $user->id);
        $this->assertNull($gridUser->profile->detailsMap()->get('unit'));
        $this->assertNull($gridUser->profile->detailsMap()->get('section'));
    }

    public function test_status_users_carries_display_title_detail_for_display_position()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $profile = Profile::factory()->create(['user_id' => $user->id, 'position' => 'employee']);
        ProfileDetail::create(['profile_id' => $profile->id, 'section' => 'employment', 'key' => 'display_title', 'value' => 'Custom Title']);

        Livewire::withoutLazyLoading();
        $rendered = Livewire::actingAs($user)->test(Status::class)->instance()->users();

        $gridUser = $rendered->firstWhere('id', $user->id);
        $this->assertNotNull($gridUser);
        $this->assertSame('Custom Title', $gridUser->profile->display_position);
        $this->assertTrue($gridUser->profile->has_display_position);
    }

    public function test_status_grid_renders_display_title_instead_of_position_fallback()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $profile = Profile::factory()->create(['user_id' => $user->id, 'position' => 'employee']);
        ProfileDetail::create(['profile_id' => $profile->id, 'section' => 'employment', 'key' => 'display_title', 'value' => 'Custom Rendered Title']);

        Livewire::withoutLazyLoading();
        $html = Livewire::actingAs($user)->test(Status::class)->html();

        $this->assertStringContainsString('Custom Rendered Title', $html);
    }

    public function test_status_board_excludes_guest_type_users()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee']);
        $employee = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee']);
        $guest = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'guest']);
        Profile::factory()->create(['user_id' => $employee->id]);
        Profile::factory()->create(['user_id' => $guest->id]);

        Livewire::withoutLazyLoading();
        $rendered = Livewire::actingAs($actor)->test(Status::class)->instance()->users();

        $this->assertNotNull($rendered->firstWhere('id', $employee->id));
        $this->assertNull($rendered->firstWhere('id', $guest->id), 'guest-type user must not appear on the colleague board');
    }

    public function test_status_stats_exclude_guest_type_users()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'remote', 'type' => 'employee']);

        Livewire::withoutLazyLoading();
        $baseline = Livewire::actingAs($actor)->test(Status::class)->instance()->stats;
        $baselineOnsite = $baseline['onsite'];

        User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee']);
        User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'guest']);

        Livewire::withoutLazyLoading();
        $stats = Livewire::actingAs($actor)->test(Status::class)->instance()->stats;

        $this->assertSame($baselineOnsite + 1, $stats['onsite'], 'guest must not be counted in the onsite presence stat');
    }

    public function test_status_board_keeps_guest_functional_off_board()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee']);
        $guest = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'guest']);

        Livewire::withoutLazyLoading();
        $boardUsers = Livewire::actingAs($actor)->test(Status::class)->instance()->users();
        $this->assertNull($boardUsers->firstWhere('id', $guest->id));

        $this->assertTrue($guest->fresh()->isActive(), 'guest stays active (functional everywhere else)');
        $this->assertSame('guest', $guest->fresh()->type, 'guest keeps its type');
    }

    public function test_status_classifier_write_does_not_override_existing_preferences()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $user->update(['extra' => ['preferences' => ['theme' => 'dark', 'sidebar_collapsible' => true]]]);

        $user->update(['extra' => ['Company' => 'Tehran']]);

        $fresh = $user->fresh();
        $this->assertSame('Tehran', $fresh->extra['admin']['Company'] ?? null);
        $this->assertSame('dark', $fresh->extra['preferences']['theme'] ?? null);
        $this->assertTrue($fresh->extra['preferences']['sidebar_collapsible'] ?? false);
    }

    public function test_status_saving_preferences_preserves_existing_classifier_data()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $user->update(['extra' => ['Company' => 'Tehran']]);

        $extra = $user->fresh()->extra ?? [];
        $extra['preferences'] = ['theme' => 'dark'];
        $user->update(['extra' => $extra]);

        $fresh = $user->fresh();
        $this->assertSame('dark', $fresh->extra['preferences']['theme'] ?? null);
        $this->assertSame('Tehran', $fresh->extra['admin']['Company'] ?? null);
    }

    public function test_status_classifier_groups_expose_dynamic_extra_admin_keys()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'extra' => ['Company' => 'Tehran']]);
        User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'extra' => ['Company' => 'Shiraz']]);

        Livewire::withoutLazyLoading();
        $groups = Livewire::actingAs($actor)->test(Status::class)->instance()->classifierGroups;

        $this->assertArrayHasKey('company', $groups);
        $this->assertSame('Company', $groups['company']['label']);
        $this->assertEqualsCanonicalizing(['Tehran', 'Shiraz'], array_values($groups['company']['values']));
    }

    public function test_status_users_filters_by_classifier_key_value()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $tehran = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'extra' => ['Company' => 'Tehran']]);
        $shiraz = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'extra' => ['Company' => 'Shiraz']]);

        Livewire::withoutLazyLoading();
        $rendered = Livewire::actingAs($actor)->test(Status::class)
            ->set('activeClassifier', 'company|Tehran')
            ->instance()->users();

        $this->assertNotNull($rendered->firstWhere('id', $tehran->id));
        $this->assertNull($rendered->firstWhere('id', $shiraz->id));
    }

    public function test_status_stats_apply_classifier_filter()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'remote', 'extra' => ['Company' => 'Tehran']]);
        User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'extra' => ['Company' => 'Tehran']]);
        User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'extra' => ['Company' => 'Shiraz']]);

        Livewire::withoutLazyLoading();
        $stats = Livewire::actingAs($actor)->test(Status::class)
            ->set('activeClassifier', 'company|Tehran')
            ->instance()->stats;

        $this->assertSame(1, $stats['onsite'], 'only Tehran-tagged onsite users should be counted once activeClassifier narrows the set');
    }

    public function test_status_activeClassifier_all_returns_everyone()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $tehran = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'extra' => ['Company' => 'Tehran']]);

        Livewire::withoutLazyLoading();
        $rendered = Livewire::actingAs($actor)->test(Status::class)->instance()->users();

        $this->assertNotNull($rendered->firstWhere('id', $tehran->id));
    }

    public function test_status_classifier_groups_hide_underscore_prefixed_keys()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'extra' => ['Company' => 'Tehran']]);
        User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'extra' => ['_Salary' => '100']]);

        Livewire::withoutLazyLoading();
        $groups = Livewire::actingAs($actor)->test(Status::class)->instance()->classifierGroups;

        $this->assertArrayHasKey('company', $groups);
        $this->assertArrayNotHasKey('_salary', $groups, 'underscore-prefixed admin-only keys must not surface on the board');
    }

    public function test_status_users_ignores_tampered_hidden_classifier_value()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $secret = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'extra' => ['_Salary' => '100']]);
        $other = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'extra' => ['_Salary' => '200']]);

        Livewire::withoutLazyLoading();
        $rendered = Livewire::actingAs($actor)->test(Status::class)
            ->set('activeClassifier', '_salary|100')
            ->instance()->users();

        $this->assertNotNull($rendered->firstWhere('id', $secret->id), 'a hidden-key filter must be a no-op, not narrow the list');
        $this->assertNotNull($rendered->firstWhere('id', $other->id));
    }

    public function test_set_filter_toggles_active_filter_from_all_to_value()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($actor)
            ->test(Status::class)
            ->assertSet('activeFilter', 'all')
            ->call('setFilter', 'remote')
            ->assertSet('activeFilter', 'remote');
    }

    public function test_set_filter_toggles_active_filter_back_to_all_when_called_with_same_value()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($actor)
            ->test(Status::class)
            ->call('setFilter', 'remote')
            ->assertSet('activeFilter', 'remote')
            ->call('setFilter', 'remote')
            ->assertSet('activeFilter', 'all');
    }

    public function test_set_filter_switches_active_filter_to_a_different_value()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($actor)
            ->test(Status::class)
            ->call('setFilter', 'remote')
            ->assertSet('activeFilter', 'remote')
            ->call('setFilter', 'busy')
            ->assertSet('activeFilter', 'busy');
    }

    public function test_active_filter_narrows_users_to_selected_presence()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $onsite = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee']);
        $remote = User::factory()->create(['status' => 'active', 'presence' => 'remote', 'type' => 'employee']);
        Profile::factory()->create(['user_id' => $onsite->id]);
        Profile::factory()->create(['user_id' => $remote->id]);

        Livewire::withoutLazyLoading();
        $rendered = Livewire::actingAs($actor)->test(Status::class)
            ->set('activeFilter', 'remote')
            ->instance()->users();

        $this->assertNotNull($rendered->firstWhere('id', $remote->id));
        $this->assertNull($rendered->firstWhere('id', $onsite->id));
    }

    public function test_stats_remain_unnarrowed_by_active_filter()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'remote', 'type' => 'employee']);
        User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee']);

        Livewire::withoutLazyLoading();
        $stats = Livewire::actingAs($actor)->test(Status::class)
            ->set('activeFilter', 'onsite')
            ->instance()->stats;

        $this->assertGreaterThanOrEqual(1, $stats['onsite']);
        $this->assertGreaterThanOrEqual(1, $stats['remote'], 'activeFilter must not collapse stats — chip counts stay stable across the selected filter');
    }

    public function test_search_filters_users_by_name()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $match = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee', 'name' => 'Zartosht Bahram']);
        $other = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee', 'name' => 'Someone Else']);
        Profile::factory()->create(['user_id' => $match->id]);
        Profile::factory()->create(['user_id' => $other->id]);

        Livewire::withoutLazyLoading();
        $rendered = Livewire::actingAs($actor)->test(Status::class)
            ->set('search', 'Zartosht')
            ->instance()->users();

        $this->assertNotNull($rendered->firstWhere('id', $match->id));
        $this->assertNull($rendered->firstWhere('id', $other->id));
    }

    public function test_search_filters_users_by_profile_position()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $match = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee']);
        $other = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee']);
        Profile::factory()->create(['user_id' => $match->id, 'position' => 'SeniorZartoshtEngineer']);
        Profile::factory()->create(['user_id' => $other->id, 'position' => 'RegularClerk']);

        Livewire::withoutLazyLoading();
        $rendered = Livewire::actingAs($actor)->test(Status::class)
            ->set('search', 'Zartosht')
            ->instance()->users();

        $this->assertNotNull($rendered->firstWhere('id', $match->id));
        $this->assertNull($rendered->firstWhere('id', $other->id));
    }

    public function test_search_filters_users_by_department_name()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Department::factory()->create(['code' => 'ZRN', 'name' => 'ZartoshtDevDept']);
        Department::factory()->create(['code' => 'OTH', 'name' => 'OtherDept']);
        $match = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee']);
        $other = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee']);
        Profile::factory()->create(['user_id' => $match->id, 'department_id' => 'ZRN']);
        Profile::factory()->create(['user_id' => $other->id, 'department_id' => 'OTH']);

        Livewire::withoutLazyLoading();
        $rendered = Livewire::actingAs($actor)->test(Status::class)
            ->set('search', 'ZartoshtDev')
            ->instance()->users();

        $this->assertNotNull($rendered->firstWhere('id', $match->id));
        $this->assertNull($rendered->firstWhere('id', $other->id));
    }

    public function test_search_with_single_zero_character_filters_to_matching_user_only()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $zero = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee', 'name' => '0']);
        $plain = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee', 'name' => 'Plain']);

        Livewire::withoutLazyLoading();
        $rendered = Livewire::actingAs($actor)->test(Status::class)
            ->set('search', '0')
            ->instance()->users();

        $this->assertNotNull($rendered->firstWhere('id', $zero->id));
        $this->assertNull($rendered->firstWhere('id', $plain->id));
    }

    public function test_search_applies_to_stats()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'remote', 'type' => 'employee']);
        $match = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee', 'name' => 'ZartoshtUnique']);
        Profile::factory()->create(['user_id' => $match->id]);

        Livewire::withoutLazyLoading();
        $stats = Livewire::actingAs($actor)->test(Status::class)
            ->set('search', 'ZartoshtUnique')
            ->instance()->stats;

        $this->assertSame(1, $stats['onsite']);
        $this->assertSame(0, $stats['remote'], 'actor is filtered out by the search term so remote count must be 0');
    }

    public function test_search_and_active_filter_combine_to_narrow_users()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $remoteMatch = User::factory()->create(['status' => 'active', 'presence' => 'remote', 'type' => 'employee', 'name' => 'ZartoshtShared']);
        $onsiteMatch = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee', 'name' => 'ZartoshtShared']);
        Profile::factory()->create(['user_id' => $remoteMatch->id]);
        Profile::factory()->create(['user_id' => $onsiteMatch->id]);

        Livewire::withoutLazyLoading();
        $rendered = Livewire::actingAs($actor)->test(Status::class)
            ->set('activeFilter', 'remote')
            ->set('search', 'ZartoshtShared')
            ->instance()->users();

        $this->assertNotNull($rendered->firstWhere('id', $remoteMatch->id));
        $this->assertNull($rendered->firstWhere('id', $onsiteMatch->id));
    }

    public function test_search_and_active_classifier_combine_to_narrow_users()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $match = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee', 'name' => 'ZartoshtName', 'extra' => ['Company' => 'Tehran']]);
        $sameCompany = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee', 'name' => 'DifferentName', 'extra' => ['Company' => 'Tehran']]);
        Profile::factory()->create(['user_id' => $match->id]);
        Profile::factory()->create(['user_id' => $sameCompany->id]);

        Livewire::withoutLazyLoading();
        $rendered = Livewire::actingAs($actor)->test(Status::class)
            ->set('activeClassifier', 'company|Tehran')
            ->set('search', 'ZartoshtName')
            ->instance()->users();

        $this->assertNotNull($rendered->firstWhere('id', $match->id));
        $this->assertNull($rendered->firstWhere('id', $sameCompany->id));
    }

    public function test_empty_state_renders_when_search_matches_no_one()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        $html = Livewire::actingAs($actor)->test(Status::class)
            ->set('search', 'NoSuchPersonExistsHere')
            ->html();

        $this->assertStringContainsString('کاربری یافت نشد', $html);
        $this->assertStringContainsString('wire:key="status-empty-none"', $html);
    }

    public function test_empty_state_renders_when_active_filter_matches_no_one()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        $html = Livewire::actingAs($actor)->test(Status::class)
            ->set('activeFilter', 'mission')
            ->set('search', 'NoSuchPersonMissionFilterZZZ')
            ->html();

        $this->assertStringContainsString('کاربری یافت نشد', $html);
    }

    public function test_stats_returns_zero_for_every_presence_case_when_no_users_match()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        $stats = Livewire::actingAs($actor)->test(Status::class)
            ->set('search', 'NoSuchPersonExistsHere')
            ->instance()->stats;

        $this->assertEqualsCanonicalizing(
            array_column(PresenceStatus::cases(), 'value'),
            array_keys($stats),
        );
        foreach (PresenceStatus::cases() as $case) {
            $this->assertSame(0, $stats[$case->value], "presence {$case->value} must be present as a 0-count key even when no users match");
        }
    }

    public function test_users_orders_higher_rank_before_lower_rank()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $employee = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee']);
        $manager = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee']);
        Profile::factory()->create(['user_id' => $employee->id, 'position' => 'employee']);
        Profile::factory()->create(['user_id' => $manager->id, 'position' => 'manager']);

        Livewire::withoutLazyLoading();
        $ordered = Livewire::actingAs($actor)->test(Status::class)->instance()->users();
        $ids = $ordered->pluck('id')->all();

        $this->assertGreaterThan(array_search($manager->id, $ids), array_search($employee->id, $ids), 'manager (rank 3) must sort before employee (rank 7)');
    }

    public function test_users_orders_by_last_seen_desc_within_same_rank()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $older = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee', 'last_seen' => now()->subDays(10)]);
        $newer = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee', 'last_seen' => now()->subDays(1)]);
        Profile::factory()->create(['user_id' => $older->id, 'position' => 'employee']);
        Profile::factory()->create(['user_id' => $newer->id, 'position' => 'employee']);

        Livewire::withoutLazyLoading();
        $ordered = Livewire::actingAs($actor)->test(Status::class)->instance()->users();
        $ids = $ordered->pluck('id')->all();

        $this->assertGreaterThan(array_search($newer->id, $ids), array_search($older->id, $ids), 'within the same rank the more recently seen user must sort first');
    }

    public function test_select_skill_then_users_returns_only_approved_non_private_holders()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $skill = Skill::create(['name' => 'Directory Skill ' . uniqid()]);
        $publicHolder = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee']);
        $privateHolder = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee']);
        $pendingHolder = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee']);
        Profile::factory()->create(['user_id' => $publicHolder->id]);
        Profile::factory()->create(['user_id' => $privateHolder->id]);
        Profile::factory()->create(['user_id' => $pendingHolder->id]);

        SkillUser::create(['user_id' => $publicHolder->id, 'skill_id' => $skill->id, 'status' => 'approved', 'approved_at' => now(), 'is_private' => false]);
        SkillUser::create(['user_id' => $privateHolder->id, 'skill_id' => $skill->id, 'status' => 'approved', 'approved_at' => now(), 'is_private' => true]);
        SkillUser::create(['user_id' => $pendingHolder->id, 'skill_id' => $skill->id, 'status' => 'pending']);

        Livewire::withoutLazyLoading();
        $rendered = Livewire::actingAs($actor)->test(Status::class)
            ->call('selectSkill', $skill->id)
            ->instance()->users();

        $this->assertNotNull($rendered->firstWhere('id', $publicHolder->id));
        $this->assertNull($rendered->firstWhere('id', $privateHolder->id));
        $this->assertNull($rendered->firstWhere('id', $pendingHolder->id));
    }

    public function test_users_tier_sorts_endorsed_before_active_before_unused_when_skill_selected()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $skill = Skill::create(['name' => 'Tier Sort Skill ' . uniqid()]);
        $endorser = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        $endorsedHolder = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee']);
        $activeHolder = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee']);
        $unusedHolder = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee']);
        Profile::factory()->create(['user_id' => $endorsedHolder->id]);
        Profile::factory()->create(['user_id' => $activeHolder->id]);
        Profile::factory()->create(['user_id' => $unusedHolder->id]);

        SkillUser::create(['user_id' => $endorsedHolder->id, 'skill_id' => $skill->id, 'status' => 'approved', 'approved_at' => now(), 'endorsers' => [$endorser->id], 'endorsements_count' => SkillUser::ENDORSEMENT_SATURATION_CAP]);
        SkillUser::create(['user_id' => $activeHolder->id, 'skill_id' => $skill->id, 'status' => 'approved', 'approved_at' => now(), 'last_used_at' => now()->subDay()]);
        SkillUser::create(['user_id' => $unusedHolder->id, 'skill_id' => $skill->id, 'status' => 'approved', 'approved_at' => now()]);

        Livewire::withoutLazyLoading();
        $ids = Livewire::actingAs($actor)->test(Status::class)
            ->call('selectSkill', $skill->id)
            ->instance()->users()
            ->pluck('id')->all();

        $endorsedPos = array_search($endorsedHolder->id, $ids);
        $activePos = array_search($activeHolder->id, $ids);
        $unusedPos = array_search($unusedHolder->id, $ids);

        $this->assertLessThan($activePos, $endorsedPos);
        $this->assertLessThan($unusedPos, $activePos);
    }

    public function test_users_tier_sort_breaks_ties_by_endorsement_count_within_tier()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $skill = Skill::create(['name' => 'Tier Tiebreak Sort Skill ' . uniqid()]);

        $moreEndorsedHolder = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee']);
        $lessEndorsedHolder = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee']);
        Profile::factory()->create(['user_id' => $moreEndorsedHolder->id]);
        Profile::factory()->create(['user_id' => $lessEndorsedHolder->id]);

        SkillUser::create(['user_id' => $moreEndorsedHolder->id, 'skill_id' => $skill->id, 'status' => 'approved', 'approved_at' => now(), 'endorsements_count' => 2, 'last_used_at' => now()->subDay()]);
        SkillUser::create(['user_id' => $lessEndorsedHolder->id, 'skill_id' => $skill->id, 'status' => 'approved', 'approved_at' => now(), 'endorsements_count' => 1, 'last_used_at' => now()->subDay()]);

        Livewire::withoutLazyLoading();
        $ids = Livewire::actingAs($actor)->test(Status::class)
            ->call('selectSkill', $skill->id)
            ->instance()->users()
            ->pluck('id')->all();

        $this->assertLessThan(array_search($lessEndorsedHolder->id, $ids), array_search($moreEndorsedHolder->id, $ids));
    }

    public function test_mentor_only_toggle_narrows_to_mentoring_holders()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $skill = Skill::create(['name' => 'Mentor Skill ' . uniqid()]);
        $mentor = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee']);
        $nonMentor = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee']);
        Profile::factory()->create(['user_id' => $mentor->id]);
        Profile::factory()->create(['user_id' => $nonMentor->id]);

        SkillUser::create(['user_id' => $mentor->id, 'skill_id' => $skill->id, 'status' => 'approved', 'approved_at' => now(), 'is_mentoring' => true]);
        SkillUser::create(['user_id' => $nonMentor->id, 'skill_id' => $skill->id, 'status' => 'approved', 'approved_at' => now(), 'is_mentoring' => false]);

        Livewire::withoutLazyLoading();
        $rendered = Livewire::actingAs($actor)->test(Status::class)
            ->call('selectSkill', $skill->id)
            ->set('mentorOnly', true)
            ->instance()->users();

        $this->assertNotNull($rendered->firstWhere('id', $mentor->id));
        $this->assertNull($rendered->firstWhere('id', $nonMentor->id));
    }

    public function test_search_skill_matching_existing_skill_does_not_dispatch_log_missing_skill_job()
    {
        Queue::fake();
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $skill = Skill::create(['name' => 'Findable Skill ' . uniqid()]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($actor)->test(Status::class)
            ->set('skillSearch', $skill->name)
            ->call('searchSkill');

        Queue::assertNotPushed(LogMissingSkillJob::class);
    }

    public function test_search_skill_matching_nothing_dispatches_log_missing_skill_job_once_and_dedupes_repeat_calls()
    {
        Queue::fake();
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $term = 'NoSuchSkillAnywhere' . uniqid();

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($actor)->test(Status::class)
            ->set('skillSearch', $term)
            ->call('searchSkill');

        Queue::assertPushed(LogMissingSkillJob::class, 1);

        $component->set('skillSearch', $term)->call('searchSkill');

        Queue::assertPushed(LogMissingSkillJob::class, 1);
    }

    public function test_live_typing_skill_search_does_not_dispatch_log_missing_skill_job_without_commit()
    {
        Queue::fake();
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $term = 'NoSuchSkillLiveTyping' . uniqid();

        Livewire::withoutLazyLoading();
        Livewire::actingAs($actor)->test(Status::class)
            ->set('skillSearch', $term);

        Queue::assertNotPushed(LogMissingSkillJob::class);
    }

    public function test_mount_preselects_skill_id_from_valid_query_param()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $skill = Skill::create(['name' => 'Deep Link Skill ' . uniqid()]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($actor)->withQueryParams(['skill' => $skill->id])
            ->test(Status::class)
            ->assertSet('skillId', $skill->id);
    }

    public function test_mount_with_invalid_skill_query_param_leaves_skill_id_null()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $inactiveSkill = Skill::create(['name' => 'Inactive Deep Link Skill ' . uniqid(), 'is_active' => false]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($actor)->withQueryParams(['skill' => $inactiveSkill->id])
            ->test(Status::class)
            ->assertSet('skillId', null);
    }

    public function test_endorse_and_revoke_from_status_component_on_a_colleagues_skill()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $skill = Skill::create(['name' => 'Status Endorse Skill ' . uniqid()]);
        $row = SkillUser::create(['user_id' => $owner->id, 'skill_id' => $skill->id, 'status' => 'approved', 'approved_at' => now()]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($actor)->test(Status::class)
            ->call('endorse', $row->id)
            ->assertDispatched('toast', message: 'مهارت با موفقیت تأیید شد.', type: 'success');

        $this->assertSame(1, $row->fresh()->endorsements_count);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($actor)->test(Status::class)
            ->call('revokeEndorsement', $row->id)
            ->assertDispatched('toast', message: 'تأیید شما لغو شد.', type: 'success');

        $this->assertSame(0, $row->fresh()->endorsements_count);
    }

    public function test_self_endorse_from_status_component_is_blocked()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $skill = Skill::create(['name' => 'Self Endorse Status Skill ' . uniqid()]);
        $row = SkillUser::create(['user_id' => $owner->id, 'skill_id' => $skill->id, 'status' => 'approved', 'approved_at' => now()]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($owner)->test(Status::class)
            ->call('endorse', $row->id)
            ->assertDispatched('toast', message: 'امکان تأیید مهارت خودتان وجود ندارد.', type: 'error');

        $this->assertSame(0, $row->fresh()->endorsements_count);
    }

    public function test_open_about_me_sets_approved_non_private_skills_for_visible_colleague()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $colleague = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee']);
        $publicSkill = Skill::create(['name' => 'About Me Public Skill ' . uniqid()]);
        $privateSkill = Skill::create(['name' => 'About Me Private Skill ' . uniqid()]);
        $pendingSkill = Skill::create(['name' => 'About Me Pending Skill ' . uniqid()]);

        SkillUser::create(['user_id' => $colleague->id, 'skill_id' => $publicSkill->id, 'status' => 'approved', 'approved_at' => now(), 'is_private' => false]);
        SkillUser::create(['user_id' => $colleague->id, 'skill_id' => $privateSkill->id, 'status' => 'approved', 'approved_at' => now(), 'is_private' => true]);
        SkillUser::create(['user_id' => $colleague->id, 'skill_id' => $pendingSkill->id, 'status' => 'pending']);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($actor)->test(Status::class)->call('openAboutMe', $colleague->id);
        $skillIds = $component->instance()->aboutMeSkills->pluck('skill_id')->all();

        $this->assertContains($publicSkill->id, $skillIds);
        $this->assertNotContains($privateSkill->id, $skillIds);
        $this->assertNotContains($pendingSkill->id, $skillIds);
    }

    public function test_open_about_me_for_a_user_not_visible_on_board_leaves_about_me_user_id_unset()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $guest = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'guest']);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($actor)->test(Status::class)->call('openAboutMe', $guest->id);

        $component->assertSet('aboutMeUserId', null);
        $this->assertTrue($component->instance()->aboutMeSkills->isEmpty());
    }

    public function test_about_me_modal_renders_endorse_button_for_a_colleagues_approved_public_skill_but_not_for_self()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $colleague = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $skill = Skill::create(['name' => 'Rendered Endorse Skill ' . uniqid()]);
        SkillUser::create(['user_id' => $colleague->id, 'skill_id' => $skill->id, 'status' => 'approved', 'approved_at' => now(), 'is_private' => false]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($actor)->test(Status::class)
            ->call('openAboutMe', $colleague->id)
            ->assertSee('تأیید مهارت');

        $ownSkill = Skill::create(['name' => 'Self Rendered Skill ' . uniqid()]);
        SkillUser::create(['user_id' => $actor->id, 'skill_id' => $ownSkill->id, 'status' => 'approved', 'approved_at' => now(), 'is_private' => false]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($actor)->test(Status::class)
            ->call('openAboutMe', $actor->id)
            ->assertDontSee('تأیید مهارت');
    }

    public function test_todays_occasions_includes_a_colleague_whose_birthday_is_today()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $colleague = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create([
            'user_id' => $colleague->id,
            'birthdate' => now()->subYears(30),
            'employment_status' => 'working',
        ]);

        Livewire::withoutLazyLoading();
        $occasions = Livewire::actingAs($actor)->test(Status::class)->instance()->todaysOccasions();

        $match = collect($occasions)->firstWhere('user.id', $colleague->id);
        $this->assertNotNull($match, 'colleague with a matching birthdate must appear in todaysOccasions');
        $this->assertSame('birthday', $match['type']);
    }

    public function test_todays_occasions_excludes_a_colleague_whose_date_does_not_match_today()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $colleague = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create([
            'user_id' => $colleague->id,
            'birthdate' => now()->subYears(30)->addDay(),
            'employment_status' => 'working',
        ]);

        Livewire::withoutLazyLoading();
        $occasions = Livewire::actingAs($actor)->test(Status::class)->instance()->todaysOccasions();

        $this->assertNull(collect($occasions)->firstWhere('user.id', $colleague->id));
    }

    public function test_todays_occasions_excludes_guest_type_users_even_on_their_birthday()
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'employee']);
        $guest = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'type' => 'guest']);
        Profile::factory()->create([
            'user_id' => $guest->id,
            'birthdate' => now()->subYears(30),
            'employment_status' => 'working',
        ]);

        Livewire::withoutLazyLoading();
        $occasions = Livewire::actingAs($actor)->test(Status::class)->instance()->todaysOccasions();

        $this->assertNull(collect($occasions)->firstWhere('user.id', $guest->id), 'guest-type user must not appear even on their birthday');
    }

    public function test_toggle_view_sets_and_persists_view_choice(): void
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($actor)->test(Status::class)->assertSet('view', 'grid')->call('toggleView', 'chart')->assertSet('view', 'chart');
        Livewire::withoutLazyLoading();
        Livewire::actingAs($actor)->test(Status::class)->assertSet('view', 'chart');
    }

    public function test_toggle_view_ignores_unsupported_value(): void
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($actor)->test(Status::class)
            ->call('toggleView', 'chart')->assertSet('view', 'chart')
            ->call('toggleView', 'list')->assertSet('view', 'chart');
    }

    public function test_todays_occasions_payload_carries_occasion_tone_for_birthday(): void
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $colleague = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create([
            'user_id' => $colleague->id,
            'birthdate' => now()->subYears(30),
            'employment_status' => 'working',
        ]);

        Livewire::withoutLazyLoading();
        $occasions = Livewire::actingAs($actor)->test(Status::class)->instance()->todaysOccasions();
        $match = collect($occasions)->firstWhere('user.id', $colleague->id);

        $this->assertNotNull($match);
        $this->assertArrayHasKey('tone', $match);
        $this->assertSame(Profile::occasionTone('birthday'), $match['tone']);
    }

    public function test_org_tree_orders_members_by_rank_then_name_and_rank_order_overrides_rank(): void
    {
        $dept = $this->makeDept(['level' => 1]);
        $head = $this->makeUser($dept->code, 'manager');
        $b = $this->makeUser($dept->code, 'expert');
        $a = $this->makeUser($dept->code, 'expert');
        $promoted = $this->makeUser($dept->code, 'employee', ['rank_order' => '3.5']);
        $b->update(['name' => 'ب']);
        $a->update(['name' => 'الف']);
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        $node = $this->orgTree($actor)['byCode'][$dept->code];

        $this->assertSame($head->id, $node['head']->id);
        $this->assertSame([$promoted->id, $a->id, $b->id], $node['allRemaining']->pluck('id')->all());
    }

    public function test_org_tree_keeps_star_ceo_in_apex_and_marks_its_department_tree_as_company(): void
    {
        $parent = $this->makeDept(['level' => 1]);
        $child = $this->makeDept(['level' => 1, 'subordinate_to' => $parent->code]);
        $main = $this->makeUser($this->makeDept(['level' => 1])->code, 'ceo');
        $company = $this->makeUser($parent->code, 'ceo', ['rank_order' => '*']);
        $this->makeUser($parent->code, 'expert');
        $this->makeUser($child->code, 'expert');
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        $tree = $this->orgTree($actor);

        $this->assertContains($main->id, $tree['apex']->pluck('id')->all());
        $this->assertContains($company->id, $tree['apex']->pluck('id')->all());
        $node = collect($tree['level1'])->firstWhere('code', $parent->code);
        $this->assertTrue($node['company']);
        $this->assertSame([$child->code], array_column($node['children'], 'code'));
        $this->assertTrue($node['children'][0]['company']);    }

    public function test_org_tree_hides_department_with_level_zero_and_no_subordinate_to(): void
    {
        $dept = $this->makeDept(['level' => 0]);
        $this->makeUser($dept->code);
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        $tree = $this->orgTree($actor);

        $this->assertNotContains($dept->code, collect($tree['level1'])->pluck('code')->all());
        $this->assertNotContains($dept->code, collect($tree['level2'])->pluck('code')->all());
    }

    public function test_org_tree_places_level_one_department_in_level_one_band(): void
    {
        $dept = $this->makeDept(['level' => 1]);
        $this->makeUser($dept->code);
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        $tree = $this->orgTree($actor);

        $this->assertContains($dept->code, collect($tree['level1'])->pluck('code')->all());
        $this->assertNotContains($dept->code, collect($tree['level2'])->pluck('code')->all());
    }

    public function test_org_tree_places_level_two_department_in_level_two_band(): void
    {
        $dept = $this->makeDept(['level' => 2]);
        $this->makeUser($dept->code);
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        $tree = $this->orgTree($actor);

        $this->assertContains($dept->code, collect($tree['level2'])->pluck('code')->all());
        $this->assertNotContains($dept->code, collect($tree['level1'])->pluck('code')->all());
    }

    public function test_org_tree_nests_subordinate_department_under_its_parent(): void
    {
        $parent = $this->makeDept(['level' => 1]);
        $child = $this->makeDept(['level' => 1, 'subordinate_to' => $parent->code]);
        $this->makeUser($parent->code);
        $this->makeUser($child->code);
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        $tree = $this->orgTree($actor);

        $this->assertNotContains($child->code, collect($tree['level1'])->pluck('code')->all());

        $parentNode = collect($tree['level1'])->firstWhere('code', $parent->code);
        $this->assertNotNull($parentNode);
        $this->assertContains($child->code, collect($parentNode['children'])->pluck('code')->all());
    }

    public function test_org_tree_falls_back_to_own_level_when_subordinate_to_parent_is_hidden(): void
    {
        $hiddenParent = $this->makeDept(['level' => 0]);
        $this->makeUser($hiddenParent->code);
        $child = $this->makeDept(['level' => 2, 'subordinate_to' => $hiddenParent->code]);
        $this->makeUser($child->code);
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        $tree = $this->orgTree($actor);

        $this->assertContains($child->code, collect($tree['level2'])->pluck('code')->all());
    }

    public function test_org_tree_hoists_chairman_and_ceo_to_apex_only(): void
    {
        $dept = $this->makeDept(['level' => 1]);
        $chairman = $this->makeUser($dept->code, 'chairman');
        $this->makeUser($dept->code, 'employee');
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        $tree = $this->orgTree($actor);

        $this->assertContains($chairman->id, $tree['apex']->pluck('id')->all());

        $deptNode = collect($tree['level1'])->firstWhere('code', $dept->code);
        $this->assertNotSame($chairman->id, $deptNode['head']->id);
        $this->assertNotContains($chairman->id, $deptNode['allRemaining']->pluck('id')->all());
    }

    public function test_org_tree_management_department_follows_the_same_level_rules(): void
    {
        $ma = Department::query()->find('MA') ?? $this->makeDept(['code' => 'MA']);
        $ma->forceFill(['level' => 1, 'subordinate_to' => null])->save();

        $ceo = $this->makeUser('MA', 'ceo');
        $this->makeUser('MA', 'employee');
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        $tree = $this->orgTree($actor);

        $this->assertContains($ceo->id, $tree['apex']->pluck('id')->all());
        $this->assertContains('MA', collect($tree['level1'])->pluck('code')->all());
    }

    public function test_org_tree_places_direct_members_without_unit_in_members_bucket(): void
    {
        $dept = $this->makeDept(['level' => 1]);
        $this->makeUser($dept->code, 'manager');
        $direct = $this->makeUser($dept->code, 'employee');
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        $tree = $this->orgTree($actor);
        $deptNode = collect($tree['level1'])->firstWhere('code', $dept->code);

        $this->assertContains($direct->id, $deptNode['members']->pluck('id')->all());
        $this->assertEmpty($deptNode['units']);
    }

    public function test_org_tree_places_unit_members_under_their_unit_cluster(): void
    {
        $dept = $this->makeDept(['level' => 1]);
        $this->makeUser($dept->code, 'manager');
        $unitUser = $this->makeUser($dept->code, 'employee', ['unit' => 'Logistics']);
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        $tree = $this->orgTree($actor);
        $deptNode = collect($tree['level1'])->firstWhere('code', $dept->code);
        $unit = collect($deptNode['units'])->firstWhere('name', 'Logistics');

        $this->assertNotNull($unit);
        $this->assertContains($unitUser->id, $unit['members']->pluck('id')->all());
        $this->assertNotContains($unitUser->id, $deptNode['members']->pluck('id')->all());
    }

    public function test_org_tree_places_section_members_under_their_unit_and_section(): void
    {
        $dept = $this->makeDept(['level' => 1]);
        $this->makeUser($dept->code, 'manager');
        $sectionUser = $this->makeUser($dept->code, 'employee', ['unit' => 'Logistics', 'section' => 'Fleet']);
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        $tree = $this->orgTree($actor);
        $deptNode = collect($tree['level1'])->firstWhere('code', $dept->code);
        $unit = collect($deptNode['units'])->firstWhere('name', 'Logistics');
        $section = collect($unit['sections'])->firstWhere('name', 'Fleet');

        $this->assertNotNull($section);
        $this->assertContains($sectionUser->id, $section['members']->pluck('id')->all());
        $this->assertEmpty($unit['members']);
    }

    public function test_org_tree_does_not_run_extra_queries_beyond_the_users_fetch(): void
    {
        $deptA = $this->makeDept(['level' => 1]);
        $deptB = $this->makeDept(['level' => 2, 'subordinate_to' => $deptA->code]);
        $this->makeUser($deptA->code, 'manager');
        $this->makeUser($deptA->code, 'employee', ['unit' => 'Logistics', 'section' => 'Fleet']);
        $this->makeUser($deptB->code, 'employee');
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($actor)->test(Status::class);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $component->instance()->orgTree();
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(0, $queryCount, 'orgTree() must build purely from the already-loaded users() collection, no extra queries');
    }

    public function test_chart_data_stats_count_colleagues_departments_and_apex(): void
    {
        $deptA = $this->makeDept(['level' => 1, 'name' => 'Aaaa']);
        $deptB = $this->makeDept(['level' => 1, 'name' => 'Bbbb']);
        $this->makeUser($deptA->code, 'manager');
        $this->makeUser($deptA->code, 'employee');
        $this->makeUser($deptB->code, 'manager');
        $chairman = $this->makeUser($deptA->code, 'chairman');
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        $instance = Livewire::actingAs($actor)->test(Status::class)->instance();
        $data = (new StatusPresenter())->chartData($instance->orgTree, $instance->users);
        $stats = collect($data['stats'])->mapWithKeys(fn (array $s) => [$s['label'] => $s])->all();

        $this->assertSame($instance->users->count(), $stats['همکاران']['value']);
        $this->assertSame(count($instance->orgTree['byCode']), $stats['دپارتمان']['value']);
        $this->assertSame($instance->orgTree['apex']->count(), $stats['رأس سازمان']['value']);
        $this->assertContains($chairman->id, $instance->orgTree['apex']->pluck('id')->all());
        $this->assertSame('groups', $stats['همکاران']['icon']);
        $this->assertSame('corporate_fare', $stats['دپارتمان']['icon']);
        $this->assertSame('stars', $stats['رأس سازمان']['icon']);
    }

    public function test_org_view_data_matches_chart_data_of_org_tree_and_users(): void
    {
        $dept = $this->makeDept(['level' => 1, 'name' => 'ViewData']);
        $this->makeUser($dept->code, 'manager');
        $this->makeUser($dept->code, 'employee');
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        $instance = Livewire::actingAs($actor)->test(Status::class)->instance();

        $expected = (new StatusPresenter())->chartData($instance->orgTree, $instance->users);
        $this->assertSame($expected, $instance->orgViewData);
    }

    public function test_chart_data_interleaves_level2_between_level1_singleton_halves(): void
    {
        $singleton = ['head' => null, 'count' => 1, 'level' => 1];
        $orgTree = [
            'apex' => collect(),
            'level1' => [
                ['code' => 'CDA'] + $singleton,
                ['code' => 'CDB'] + $singleton,
                ['code' => 'CDC'] + $singleton,
                ['code' => 'CDD'] + $singleton,
            ],
            'level2' => [
                ['code' => 'CDM', 'head' => null, 'count' => 1, 'level' => 2],
            ],
            'byCode' => ['CDA' => 1, 'CDB' => 1, 'CDC' => 1, 'CDD' => 1, 'CDM' => 1],
        ];

        $data = (new StatusPresenter())->chartData($orgTree, collect());

        $this->assertSame(['CDA', 'CDB', 'CDM', 'CDC', 'CDD'], collect($data['depts'])->pluck('code')->all(), 'level2 dept must sit between the two singleton level1 halves at the best-mid split');
    }

    public function test_node_data_rank_meta_for_chairman_uses_stars_icon(): void
    {
        $dept = $this->makeDept(['level' => 1, 'name' => 'Exec']);
        $chairman = $this->makeUser($dept->code, 'chairman');

        $d = (new StatusPresenter())->nodeData($chairman, 'apex');

        $this->assertSame('stars', $d['rankMeta']['icon']);
    }

    public function test_grid_data_exposes_dialable_mobile_and_email_when_sms_present(): void
    {
        $dept = $this->makeDept(['level' => 1, 'name' => 'Ops']);
        $user = $this->makeUser($dept->code, 'employee');
        $user->profile->forceFill(['cellphone' => '0912-000 0000'])->save();

        $d = (new StatusPresenter())->gridData($user->fresh(), null);

        $this->assertSame('0912-000 0000', $d['sms']);
        $this->assertSame('09120000000', $d['smsTel']);
        $this->assertSame($user->email, $d['email']);
        $this->assertNull($d['skillTier']);
    }

    public function test_grid_data_has_no_contact_keys_when_no_contact_reservation_or_skill(): void
    {
        $dept = $this->makeDept(['level' => 1, 'name' => 'Quiet']);
        $user = $this->makeUser($dept->code, 'employee');
        $user->profile->forceFill(['cellphone' => null])->save();
        User::resetTodaysDeskCache();

        $d = (new StatusPresenter())->gridData($user->fresh(), null);

        $this->assertNull($d['sms']);
        $this->assertNull($d['smsTel']);
        $this->assertNull($d['ext']);
        $this->assertNull($d['extTel']);
        $this->assertNull($d['extCopy']);
        $this->assertNull($d['reserved']);
    }

    public function test_grid_data_builds_ext_tel_only_when_office_phone_is_configured_and_sanitizes_it(): void
    {
        $dept = $this->makeDept(['level' => 1, 'name' => 'Dial']);
        $user = $this->makeUser($dept->code, 'employee');
        $seat = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'metadata' => ['extension' => '41;4']]);
        Reservation::factory()->create([
            'user_id' => $user->id,
            'resource_id' => $seat->id,
            'start_time' => now()->startOfDay(),
            'end_time' => now()->endOfDay(),
            'is_full_day' => true,
            'status' => ReservationStatus::Active->value,
        ]);

        config(['app.office_phone' => '']);
        User::resetTodaysDeskCache();
        User::primeTodaysDeskCache();
        $this->assertNull((new StatusPresenter())->gridData($user->fresh(), null)['extTel']);

        config(['app.office_phone' => '+98 21-2620 8352']);
        $d = (new StatusPresenter())->gridData($user->fresh(), null);
        $this->assertSame('+982126208352,414', $d['extTel']);
    }

    public function test_grid_data_tenure_label_counts_years_and_months_and_handles_missing_or_future_start(): void
    {
        $dept = $this->makeDept(['level' => 1, 'name' => 'Tenure']);
        $user = $this->makeUser($dept->code, 'employee');

        $user->profile->forceFill(['start_date' => now()->subYears(3)->subMonths(2)->subDays(3)])->save();
        $this->assertSame('۳ سال و ۲ ماه', (new StatusPresenter())->gridData($user->fresh(), null)['tenure']);

        $user->profile->forceFill(['start_date' => now()->subDays(5)])->save();
        $this->assertSame('کمتر از یک ماه', (new StatusPresenter())->gridData($user->fresh(), null)['tenure']);

        $user->profile->forceFill(['start_date' => now()->addMonth()])->save();
        $this->assertNull((new StatusPresenter())->gridData($user->fresh(), null)['tenure']);
    }

    public function test_grid_data_builds_skill_tier_when_skill_id_and_endorsements_present(): void
    {
        $dept = $this->makeDept(['level' => 1, 'name' => 'Skilled']);
        $user = $this->makeUser($dept->code, 'employee');
        $user->skill_tier_endorsements_count = 2;
        $user->skill_tier_last_used_at = now();

        $d = (new StatusPresenter())->gridData($user, 7);

        $this->assertNotNull($d['skillTier']);
    }

    public function test_grid_data_org_title_joins_department_unit_and_section(): void
    {
        $dept = $this->makeDept(['level' => 1, 'name' => 'Logistics']);
        $user = $this->makeUser($dept->code, 'employee', ['unit' => 'Operations', 'section' => 'Fleet']);

        $d = (new StatusPresenter())->gridData($user->refresh(), null);

        $this->assertSame($dept->displayLabel() . ' › Operations › Fleet', $d['orgTitle']);
    }

    public function test_about_payload_flattens_nested_about_me_and_carries_user_identity(): void
    {
        $dept = $this->makeDept(['level' => 1, 'name' => 'About']);
        $user = $this->makeUser($dept->code, 'employee');
        $profile = $user->profile;
        $profile->forceFill(['about_me' => ['hobbies' => ['reading', 'coding'], 'note' => 'hi'], 'favorite_colors' => ['#f00']])->save();

        $d = (new StatusPresenter())->nodeData($user->refresh(), 'member');
        $payload = $d['aboutPayload'];

        $this->assertSame($user->id, $payload['user']['id']);
        $this->assertSame(['#f00'], $payload['user']['favoriteColors']);
        $this->assertSame('reading, coding', $payload['aboutMe']['hobbies']);
        $this->assertSame('hi', $payload['aboutMe']['note']);
    }

    public function test_grid_data_about_payload_matches_node_data_about_payload_for_same_user(): void
    {
        $dept = $this->makeDept(['level' => 1, 'name' => 'Unified']);
        $user = $this->makeUser($dept->code, 'employee');

        $node = (new StatusPresenter())->nodeData($user->refresh(), 'member');
        $grid = (new StatusPresenter())->gridData($user->refresh(), null);

        $this->assertSame($node['aboutPayload'], $grid['aboutPayload'], 'node and grid must share one unified aboutPayload helper');
    }
}
