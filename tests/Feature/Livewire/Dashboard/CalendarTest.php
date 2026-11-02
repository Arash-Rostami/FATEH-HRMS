<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Livewire\Dashboard\Tab\Actions\MoveEventAction;
use App\Livewire\Dashboard\Tab\Actions\ResizeEventAction;
use App\Livewire\Dashboard\Tab\Calendar;
use App\Livewire\Dashboard\Tab\Presentation\CalendarPresenter;
use App\Livewire\Dashboard\EventReminder;
use App\Enums\ReservationStatus;
use App\Models\Event;
use App\Models\EventShare;
use App\Models\Profile;
use App\Models\Reminder;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\User;
use App\Rules\JalaliDateRule;
use App\Services\Cache\ModelCacheVersion;
use App\Services\HolidayService;
use App\Services\Menu\StateService;
use App\Values\CalendarLayout;
use App\Values\CalendarRange;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Morilog\Jalali\Jalalian;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        ModelCacheVersion::bump(Profile::class);
        Queue::fake();
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

    private function reservationDescription(int $reservationId): string
    {
        return "جلسه برنامه‌ریزی شده از طریق سیستم رزرواسیون #{$reservationId}";
    }

    public function test_calendar_event_lifecycle()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $recipient = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $now = Jalalian::now();

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->set('form.title', 'Probe event')
            ->set('form.dateYear', (string) $now->getYear())
            ->set('form.dateMonth', (string) $now->getMonth())
            ->set('form.dateDay', (string) $now->getDay())
            ->set('form.time', '12:00')
            ->call('saveEvent')
            ->assertHasNoErrors();

        $event = Event::where('user_id', $user->id)->where('title', 'Probe event')->firstOrFail();
        $this->assertSame($user->id, $event->user_id);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->call('openShareModal', $event->id)
            ->assertHasNoErrors()
            ->set('shareRecipientIds', [(string) $recipient->id])
            ->call('shareEvent')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('event_shares', [
            'event_id' => $event->id,
            'user_id' => $recipient->id,
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->call('deleteEvent', $event->id)
            ->assertHasNoErrors();

        $this->assertNull(Event::find($event->id));
    }

    public function test_calendar_view_toggle_renders_three_modes_through_the_shared_component_and_switches_view()
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)->test(Calendar::class)
            ->assertSeeHtml('title="نمای ماهانه"')
            ->assertSeeHtml('title="نمای هفتگی"')
            ->assertSeeHtml('title="نمای روزانه"')
            ->assertSeeHtml('view = &#039;month&#039;;$wire.toggleView(&#039;month&#039;)')
            ->assertSeeHtml('view = &#039;week&#039;;$wire.toggleView(&#039;week&#039;)')
            ->assertSeeHtml('view = &#039;day&#039;;$wire.toggleView(&#039;day&#039;)')
            ->assertDontSeeHtml('$wire.view = ')
            ->assertSeeHtml('title="مخفی کردن جمعه"')
            ->call('toggleView', 'week')
            ->assertSet('view', 'week')
            ->call('toggleView', 'day')
            ->assertSet('view', 'day')
            ->call('toggleView', 'bogus')
            ->assertSet('view', 'day');
    }

    public function test_calendar_view_is_locked_against_client_updates()
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

        Livewire::actingAs($user)->test(Calendar::class)->set('view', 'day');
    }

    public function test_event_reminder_is_hidden_when_calendar_is_off_and_shown_when_on(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Event::factory()->create(['user_id' => $user->id, 'date' => now()->addHours(2), 'private' => true, 'remind_hours' => 3]);

        try {
            config(['app.off' => ['calendar']]);
            Livewire::actingAs($user)->test(EventReminder::class)->assertViewHas('reminder', null);

            config(['app.off' => []]);
            Livewire::withoutLazyLoading();
            Livewire::actingAs($user)->test(EventReminder::class)->assertViewHas('reminder', fn ($r) => $r !== null);
        } finally {
            config(['app.off' => []]);
        }
    }

    public function test_long_event_title_and_description_render_without_truncation()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $longTitle = str_repeat('نام و عنوان بسیار طولانی رویداد ', 4);
        $longDescription = str_repeat('این یک توضیح بسیار طولانی برای رویداد است که باید قابل گسترش باشد. ', 5);

        Event::factory()->create([
            'user_id' => $user->id,
            'title' => $longTitle,
            'description' => $longDescription,
            'date' => Carbon::today()->setTime(12, 0),
            'private' => false,
        ]);

        $selectedDate = Jalalian::fromCarbon(Carbon::today())->format('Y-m-d');

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', $selectedDate)
            ->assertSee($longTitle)
            ->assertSee($longDescription)
            ->assertDontSeeHtml('truncate text-sm md:text-base')
            ->assertDontSeeHtml('line-clamp-2')
            ->assertSeeHtml('x-data="{ expanded: false }"')
            ->assertSeeHtml('مشاهده بیشتر');
    }

    public function test_owned_event_renders_deep_link_copy_button()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $isolatedDate = Carbon::create(2099, 7, 20, 9);

        $event = Event::factory()->create([
            'user_id' => $user->id,
            'title' => 'Deep link event',
            'date' => $isolatedDate,
            'private' => false,
        ]);

        $selectedDate = Jalalian::fromCarbon($isolatedDate)->format('Y-m-d');

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', $selectedDate)
            ->assertSee(route('dashboard', ['tab' => 'calendar', 'open' => $event->id]));
    }

    public function test_short_event_description_has_no_expand_toggle()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $shortDescription = 'توضیح کوتاه';
        $isolatedDate = Carbon::create(2099, 6, 15, 9);

        Event::factory()->create([
            'user_id' => $user->id,
            'title' => 'رویداد کوتاه',
            'description' => $shortDescription,
            'date' => $isolatedDate,
            'private' => false,
        ]);

        $selectedDate = Jalalian::fromCarbon($isolatedDate)->format('Y-m-d');

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', $selectedDate)
            ->assertSee($shortDescription)
            ->assertDontSeeHtml('مشاهده بیشتر');
    }

    public function test_birthday_avatar_resolves_to_storage_url()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $today = Carbon::today();
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'image' => 'profiles/birthday.jpg',
            'birthdate' => $today->format('Y-m-d'),
        ]);

        $selectedDate = Jalalian::fromCarbon($today)->format('Y-m-d');

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', $selectedDate);

        $birthday = $component->instance()->selectedDayEvents
            ->firstWhere('id', 'birthday-' . $profile->id);

        $this->assertNotNull($birthday);
        $this->assertStringContainsString('/storage/', $birthday['avatar']);
        $this->assertStringStartsWith('http', $birthday['avatar']);
    }

    public function test_profile_dates_cache_serves_repeat_reads_without_querying_profiles()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $profile = Profile::factory()->create(['user_id' => $user->id, 'birthdate' => Carbon::today()->format('Y-m-d')]);

        Profile::cachedWithDates();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $served = Profile::cachedWithDates();
        DB::disableQueryLog();

        $this->assertTrue($served->contains('id', $profile->id));
        $this->assertEmpty(array_filter(DB::getQueryLog(), fn($q) => str_contains($q['query'], 'from `profiles`')), 'a warm read must not touch the profiles table');
    }

    public function test_profile_dates_cache_follows_profile_and_user_writes()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'name' => 'Before Probe']);
        $profile = Profile::factory()->create(['user_id' => $user->id, 'birthdate' => Carbon::today()->format('Y-m-d')]);

        Profile::cachedWithDates();

        $newDate = Carbon::today()->subDays(3)->format('Y-m-d');
        $profile->update(['birthdate' => $newDate]);
        $this->assertSame($newDate, Profile::cachedWithDates()->firstWhere('id', $profile->id)->birthdate->format('Y-m-d'));

        $user->update(['name' => 'After Probe']);
        $this->assertSame('After Probe', Profile::cachedWithDates()->firstWhere('id', $profile->id)->user->name);
    }

    public function test_calendar_grid_marks_holiday_day()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $holidayDate = array_key_first(HolidayService::getAll());

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Calendar::class)
            ->set('navigationDate', $holidayDate);

        $day = collect($component->instance()->calendarDays)
            ->filter()
            ->firstWhere('date', $holidayDate);

        $this->assertNotNull($day);
        $this->assertTrue($day['hasHoliday']);
        $this->assertSame(HolidayService::getHolidaysForDate($holidayDate)[0]['title'], $day['holidayTitle']);
    }

    public function test_calendar_grid_marks_every_day_in_an_isolated_past_month_as_past()
    {
        // regression guard: month.blade.php's past-day styling (added 2026-10-05) reads
        // $day['isPast'] off this computed property — an isolated past month proves the
        // flag is correct without relying on "today" happening to sit in the grid.
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $pastMonth = Jalalian::fromCarbon(Carbon::create(2020, 3, 10, 9))->format('Y-m-d');

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Calendar::class)
            ->set('navigationDate', $pastMonth)
            ->set('selectedDate', $pastMonth);

        $days = collect($component->instance()->calendarDays)->filter();

        $this->assertTrue($days->isNotEmpty());
        $this->assertTrue($days->every(fn ($day) => $day['isPast'] === true));
    }

    public function test_calendar_grid_never_marks_days_in_an_isolated_future_month_as_past()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $futureMonth = Jalalian::fromCarbon(Carbon::create(2099, 6, 15, 9))->format('Y-m-d');

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Calendar::class)
            ->set('navigationDate', $futureMonth)
            ->set('selectedDate', $futureMonth);

        $days = collect($component->instance()->calendarDays)->filter();

        $this->assertTrue($days->isNotEmpty());
        $this->assertTrue($days->every(fn ($day) => $day['isPast'] === false));
    }

    public function test_selected_day_events_include_holiday_entry()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $holidayDate = array_key_first(HolidayService::getAll());
        $expected = HolidayService::getHolidaysForDate($holidayDate);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', $holidayDate)
            ->assertSee($expected[0]['title']);

        $holidayEntries = $component->instance()->selectedDayEvents
            ->where('type', 'holiday');

        $this->assertCount(count($expected), $holidayEntries);
        $this->assertSame($expected[0]['title'], $holidayEntries->first()['title']);
    }

    public function test_non_holiday_day_has_no_holiday_flag()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $isolatedDate = Carbon::create(2099, 6, 15, 9);
        $selectedDate = Jalalian::fromCarbon($isolatedDate)->format('Y-m-d');

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Calendar::class)
            ->set('navigationDate', $selectedDate)
            ->set('selectedDate', $selectedDate);

        $day = collect($component->instance()->calendarDays)
            ->filter()
            ->firstWhere('date', $selectedDate);

        $this->assertNotNull($day);
        $this->assertFalse($day['hasHoliday']);
        $this->assertNull($day['holidayTitle']);
        $this->assertCount(0, $component->instance()->selectedDayEvents->where('type', 'holiday'));
    }

    public function test_mount_defaults_to_jalali_today()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $now = Jalalian::now();

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Calendar::class);

        $this->assertSame($now->format('Y-m-d'), $component->instance()->navigationDate);
        $this->assertSame($now->format('Y-m-d'), $component->instance()->selectedDate);
        $this->assertSame('month', $component->instance()->view);
    }

    public function test_next_period_steps_jalali_month_for_month_view()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', '1405-12-15')
            ->set('navigationDate', '1405-12-15')
            ->call('toggleView', 'month')
            ->call('nextPeriod')
            ->assertSet('navigationDate', '1406-01-15');
    }

    public function test_prev_period_steps_jalali_month_for_month_view()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', '1406-01-15')
            ->set('navigationDate', '1406-01-15')
            ->call('toggleView', 'month')
            ->call('prevPeriod')
            ->assertSet('navigationDate', '1405-12-15');
    }

    public function test_next_period_steps_one_day_for_day_view()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', '1405-05-15')
            ->set('navigationDate', '1405-05-15')
            ->call('toggleView', 'day')
            ->call('nextPeriod')
            ->assertSet('navigationDate', '1405-05-16');
    }

    public function test_prev_period_steps_one_day_for_day_view()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', '1405-05-16')
            ->set('navigationDate', '1405-05-16')
            ->call('toggleView', 'day')
            ->call('prevPeriod')
            ->assertSet('navigationDate', '1405-05-15');
    }

    public function test_next_period_steps_seven_days_for_week_view()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $expected = Jalalian::fromCarbon(
            Jalalian::fromFormat('Y-m-d', '1405-05-15')->toCarbon()->addDays(7)
        )->format('Y-m-d');

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', '1405-05-15')
            ->set('navigationDate', '1405-05-15')
            ->call('toggleView', 'week')
            ->call('nextPeriod')
            ->assertSet('navigationDate', $expected);
    }

    public function test_go_to_today_resets_navigation_and_selected_date_to_jalali_now()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $now = Jalalian::now();

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->set('navigationDate', '1390-01-01')
            ->set('selectedDate', '1390-01-01')
            ->call('goToToday')
            ->assertSet('navigationDate', $now->format('Y-m-d'))
            ->assertSet('selectedDate', $now->format('Y-m-d'));
    }

    public function test_select_date_sets_selected_without_switching_view()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', '1405-05-10')
            ->set('navigationDate', '1405-05-10')
            ->call('toggleView', 'month')
            ->call('selectDate', '1405-05-15')
            ->assertSet('selectedDate', '1405-05-15')
            ->assertSet('navigationDate', '1405-05-10')
            ->assertSet('view', 'month');
    }

    public function test_select_date_does_not_flip_month_for_other_month_day()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', '1405-05-10')
            ->set('navigationDate', '1405-05-10')
            ->call('toggleView', 'month')
            ->call('selectDate', '1405-06-20')
            ->assertSet('selectedDate', '1405-06-20')
            ->assertSet('navigationDate', '1405-05-10')
            ->assertSet('view', 'month');
    }

    public function test_focus_record_snaps_navigation_and_selected_date_to_event_jalali_date()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $eventDate = Carbon::create(2099, 7, 23, 10);
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'date' => $eventDate,
            'private' => false,
        ]);
        $j = Jalalian::fromCarbon($eventDate);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->set('navigationDate', '1390-01-01')
            ->set('selectedDate', '1390-01-01')
            ->call('focusRecord', $event->id)
            ->assertSet('navigationDate', $j->format('Y-m-d'))
            ->assertSet('selectedDate', $j->format('Y-m-d'));
    }

    public function test_current_month_name_formats_jalali_month_and_year()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $expected = (new Jalalian(1405, 5, 1))->format('F Y');

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->set('navigationDate', '1405-05-15')
            ->assertSet('currentMonthName', $expected);
    }

    public function test_current_month_name_returns_empty_for_invalid_navigation_date()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->set('navigationDate', 'not-a-date')
            ->assertSet('currentMonthName', '');
    }

    public function test_range_label_formats_per_view()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $nav = '1405-05-15';

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', $nav)
            ->set('navigationDate', $nav);

        $this->assertSame((new Jalalian(1405, 5, 1))->format('F Y'), $component->instance()->rangeLabel);

        $component->call('toggleView', 'day');
        $this->assertSame((new Jalalian(1405, 5, 15))->format('d F Y'), $component->instance()->rangeLabel);

        $component->call('toggleView', 'week');
        $this->assertStringContainsString('–', $component->instance()->rangeLabel);
    }

    public function test_active_date_marks_today_when_selected_date_is_today()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $today = Jalalian::now()->format('Y-m-d');

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', $today);

        $this->assertTrue($component->instance()->activeDate['isToday']);
    }

    public function test_active_date_marks_not_today_for_other_date()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', '1390-06-15');

        $this->assertFalse($component->instance()->activeDate['isToday']);
    }

    public function test_active_date_falls_back_to_now_for_invalid_selected_date()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', 'not-a-date');

        $this->assertTrue($component->instance()->activeDate['isToday']);
    }

    public function test_invalid_selected_date_returns_empty_selected_day_events()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', 'not-a-date');

        $this->assertCount(0, $component->instance()->selectedDayEvents);
        $this->assertStringContainsString('wire:key="calendar-day-empty"', $component->html());
    }

    public function test_open_create_modal_resets_form_to_selected_date_defaults()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', '1405-05-15')
            ->call('openCreateModal')
            ->assertSet('isCreateModalOpen', true)
            ->assertSet('form.editingId', null)
            ->assertSet('form.dateYear', '1405')
            ->assertSet('form.dateMonth', '5')
            ->assertSet('form.dateDay', '15')
            ->assertSet('form.time', '12:00');
    }

    public function test_edit_event_populates_form_and_opens_modal_for_owner_only()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $stranger = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $eventDate = Carbon::create(2099, 7, 23, 14, 30);
        $event = Event::factory()->create([
            'user_id' => $owner->id,
            'title' => 'رویداد مالک',
            'description' => 'توضیح مالک',
            'date' => $eventDate,
            'private' => true,
        ]);
        $j = Jalalian::fromCarbon($eventDate);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($owner)->test(Calendar::class)
            ->call('editEvent', $event->id)
            ->assertSet('isCreateModalOpen', true)
            ->assertSet('form.editingId', $event->id)
            ->assertSet('form.title', 'رویداد مالک')
            ->assertSet('form.description', 'توضیح مالک')
            ->assertSet('form.dateYear', $j->getYear())
            ->assertSet('form.dateMonth', $j->getMonth())
            ->assertSet('form.dateDay', $j->getDay())
            ->assertSet('form.time', '14:30')
            ->assertSet('form.private', true);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($stranger)->test(Calendar::class)
            ->call('editEvent', $event->id)
            ->assertSet('isCreateModalOpen', false)
            ->assertSet('form.editingId', null)
            ->assertSet('form.title', '');
    }

    public function test_edit_event_refuses_reservation_linked_event()
    {
        $booker = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $event = Event::factory()->create([
            'user_id' => $booker->id,
            'description' => $this->reservationDescription(42),
            'date' => Carbon::create(2099, 7, 23, 9),
            'private' => true,
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($booker)->test(Calendar::class)
            ->call('editEvent', $event->id)
            ->assertSet('isCreateModalOpen', false)
            ->assertSet('form.editingId', null)
            ->assertDispatched('toast', message: 'این رویداد از طریق سیستم رزرو مدیریت می‌شود؛ برای تغییر آن به تب رزرو مراجعه کنید.', type: 'error');
    }

    public function test_save_event_updates_existing_event_when_editing_id_set()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'title' => 'عنوان اصلی',
            'date' => Carbon::create(2099, 7, 23, 9),
            'private' => false,
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->call('editEvent', $event->id)
            ->set('form.title', 'عنوان بروزرسانی‌شده')
            ->call('saveEvent')
            ->assertHasNoErrors()
            ->assertSet('isCreateModalOpen', false);

        $event->refresh();
        $this->assertSame('عنوان بروزرسانی‌شده', $event->title);
        $this->assertSame(1, Event::where('user_id', $user->id)->count());
    }

    public function test_save_event_sets_remind_hours()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $now = Jalalian::now();

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->set('form.title', 'رویداد با یادآوری')
            ->set('form.dateYear', (string) $now->getYear())
            ->set('form.dateMonth', (string) $now->getMonth())
            ->set('form.dateDay', (string) $now->getDay())
            ->set('form.time', '10:00')
            ->set('form.remindHours', 3)
            ->call('saveEvent')
            ->assertHasNoErrors();

        $this->assertSame(3, Event::where('user_id', $user->id)->value('remind_hours'));
    }

    public function test_save_event_clears_remind_hours_when_unset_on_edit()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::create(2099, 7, 23, 9),
            'private' => false,
            'remind_hours' => 3,
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->call('editEvent', $event->id)
            ->set('form.remindHours', '')
            ->call('saveEvent')
            ->assertHasNoErrors();

        $this->assertNull($event->fresh()->remind_hours);
    }

    public function test_save_event_validates_required_title()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $now = Jalalian::now();

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->set('form.title', '')
            ->set('form.dateYear', (string) $now->getYear())
            ->set('form.dateMonth', (string) $now->getMonth())
            ->set('form.dateDay', (string) $now->getDay())
            ->set('form.time', '12:00')
            ->call('saveEvent')
            ->assertHasErrors(['form.title']);
    }

    public function test_save_event_with_invalid_time_adds_form_time_error()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $now = Jalalian::now();

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->set('form.title', 'زمان نامعتبر')
            ->set('form.dateYear', (string) $now->getYear())
            ->set('form.dateMonth', (string) $now->getMonth())
            ->set('form.dateDay', (string) $now->getDay())
            ->set('form.time', 'invalid-time')
            ->call('saveEvent')
            ->assertHasErrors(['form.time']);
    }

    public function test_save_event_with_out_of_range_day_adds_form_date_day_error()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->set('form.title', 'روز نامعتبر')
            ->set('form.dateYear', '1404')
            ->set('form.dateMonth', '12')
            ->set('form.dateDay', '30')
            ->set('form.time', '12:00')
            ->call('saveEvent')
            ->assertHasErrors(['form.dateDay']);
    }

    public function test_confirm_delete_dispatches_open_confirmation_with_delete_event_method()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $event = Event::factory()->create(['user_id' => $user->id, 'date' => Carbon::create(2099, 7, 23, 9)]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->call('confirmDelete', $event->id)
            ->assertSet('deletingEventId', $event->id)
            ->assertDispatched('open-confirmation', function ($name, $params) use ($event) {
                return $name === 'open-confirmation'
                    && ($params[0]['method'] ?? null) === 'deleteEvent'
                    && ($params[0]['params'] ?? null) === $event->id;
            });
    }

    public function test_delete_event_clears_deleting_event_id_and_removes_row()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $event = Event::factory()->create(['user_id' => $user->id, 'date' => Carbon::create(2099, 7, 23, 9)]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->call('confirmDelete', $event->id)
            ->assertSet('deletingEventId', $event->id)
            ->call('deleteEvent', $event->id)
            ->assertSet('deletingEventId', null)
            ->assertHasNoErrors();

        $this->assertNull(Event::find($event->id));
    }

    public function test_delete_event_silently_refuses_non_owner()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $stranger = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $event = Event::factory()->create(['user_id' => $owner->id, 'date' => Carbon::create(2099, 7, 23, 9)]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($stranger)->test(Calendar::class)
            ->call('deleteEvent', $event->id)
            ->assertHasNoErrors();

        $this->assertNotNull(Event::find($event->id));
    }

    public function test_delete_event_refuses_reservation_linked_event()
    {
        $booker = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $event = Event::factory()->create([
            'user_id' => $booker->id,
            'description' => $this->reservationDescription(7),
            'date' => Carbon::create(2099, 7, 23, 9),
            'private' => true,
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($booker)->test(Calendar::class)
            ->call('deleteEvent', $event->id)
            ->assertSet('deletingEventId', null)
            ->assertDispatched('toast', message: 'این رویداد از طریق سیستم رزرو مدیریت می‌شود؛ برای لغو آن به تب رزرو مراجعه کنید.', type: 'error');

        $this->assertNotNull(Event::find($event->id));
    }

    public function test_open_share_modal_pre_fills_existing_recipients_as_strings()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $recipient = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $event = Event::factory()->create(['user_id' => $user->id, 'date' => Carbon::create(2099, 7, 23, 9)]);

        DB::table('event_shares')->insert([
            'event_id' => $event->id,
            'user_id' => $recipient->id,
            'shared_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->call('openShareModal', $event->id)
            ->assertSet('isShareModalOpen', true)
            ->assertSet('sharingEventId', $event->id)
            ->assertSet('shareRecipientIds', [(string) $recipient->id]);
    }

    public function test_open_share_modal_silently_refuses_non_owner()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $stranger = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $event = Event::factory()->create(['user_id' => $owner->id, 'date' => Carbon::create(2099, 7, 23, 9)]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($stranger)->test(Calendar::class)
            ->call('openShareModal', $event->id)
            ->assertSet('isShareModalOpen', false)
            ->assertSet('sharingEventId', null)
            ->assertSet('shareRecipientIds', []);
    }

    public function test_open_share_modal_refuses_reservation_linked_event()
    {
        $booker = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $event = Event::factory()->create([
            'user_id' => $booker->id,
            'description' => $this->reservationDescription(9),
            'date' => Carbon::create(2099, 7, 23, 9),
            'private' => true,
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($booker)->test(Calendar::class)
            ->call('openShareModal', $event->id)
            ->assertSet('isShareModalOpen', false)
            ->assertSet('sharingEventId', null)
            ->assertDispatched('toast', message: 'این رویداد از طریق سیستم رزرو به‌طور خودکار به‌اشتراک گذاشته شده است.', type: 'error');
    }

    public function test_share_event_diff_sync_with_no_change_dispatches_info_toast()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $recipient = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $event = Event::factory()->create(['user_id' => $user->id, 'date' => Carbon::create(2099, 7, 23, 9)]);

        DB::table('event_shares')->insert([
            'event_id' => $event->id,
            'user_id' => $recipient->id,
            'shared_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->call('openShareModal', $event->id)
            ->assertSet('shareRecipientIds', [(string) $recipient->id])
            ->call('shareEvent')
            ->assertDispatched('toast', message: 'تغییری در اشتراک‌گذاری اعمال نشد.', type: 'info');

        $this->assertDatabaseHas('event_shares', [
            'event_id' => $event->id,
            'user_id' => $recipient->id,
        ]);
    }

    public function test_share_event_removed_recipients_dispatches_success_toast_and_deletes_rows()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $recipient = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $event = Event::factory()->create(['user_id' => $user->id, 'date' => Carbon::create(2099, 7, 23, 9)]);

        DB::table('event_shares')->insert([
            'event_id' => $event->id,
            'user_id' => $recipient->id,
            'shared_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->call('openShareModal', $event->id)
            ->set('shareRecipientIds', [])
            ->call('shareEvent')
            ->assertDispatched('toast', message: 'اشتراک رویداد برای 1 نفر لغو شد.', type: 'success');

        $this->assertDatabaseMissing('event_shares', [
            'event_id' => $event->id,
            'user_id' => $recipient->id,
        ]);
    }

    public function test_share_event_drops_sharer_id_and_inactive_recipients()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $recipient = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $inactive = User::factory()->create(['status' => 'inactive', 'presence' => 'onsite']);
        $event = Event::factory()->create(['user_id' => $user->id, 'date' => Carbon::create(2099, 7, 23, 9)]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->call('openShareModal', $event->id)
            ->set('shareRecipientIds', [(string) $user->id, (string) $inactive->id, (string) $recipient->id])
            ->call('shareEvent')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('event_shares', ['event_id' => $event->id, 'user_id' => $recipient->id]);
        $this->assertDatabaseMissing('event_shares', ['event_id' => $event->id, 'user_id' => $user->id]);
        $this->assertDatabaseMissing('event_shares', ['event_id' => $event->id, 'user_id' => $inactive->id]);
    }

    public function test_share_event_for_non_owner_event_adds_share_error()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $stranger = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $recipient = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $event = Event::factory()->create(['user_id' => $owner->id, 'date' => Carbon::create(2099, 7, 23, 9)]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($stranger)->test(Calendar::class)
            ->set('sharingEventId', $event->id)
            ->set('shareRecipientIds', [(string) $recipient->id])
            ->call('shareEvent')
            ->assertHasErrors(['share']);

        $this->assertDatabaseMissing('event_shares', ['event_id' => $event->id, 'user_id' => $recipient->id]);
    }

    public function test_share_event_closes_modal_and_resets_state_on_success()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $recipient = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $event = Event::factory()->create(['user_id' => $user->id, 'date' => Carbon::create(2099, 7, 23, 9)]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->call('openShareModal', $event->id)
            ->set('shareRecipientIds', [(string) $recipient->id])
            ->call('shareEvent')
            ->assertHasNoErrors()
            ->assertSet('isShareModalOpen', false)
            ->assertSet('sharingEventId', null)
            ->assertSet('shareRecipientIds', []);
    }

    public function test_available_users_excludes_auth_user_and_maps_to_id_full_name()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $coworker = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Cache::forget('user_active_options');

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Calendar::class);

        $available = $component->instance()->availableUsers;
        $ids = collect($available)->pluck('id')->map(fn($i) => (string) $i)->all();

        $this->assertNotContains((string) $user->id, $ids);
        $this->assertContains((string) $coworker->id, $ids);
        $this->assertArrayHasKey('id', $available[0]);
        $this->assertArrayHasKey('full_name', $available[0]);
    }

    public function test_calendar_days_hides_other_user_private_event()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $other = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $eventDate = Carbon::create(2099, 7, 23, 9);
        Event::factory()->create([
            'user_id' => $other->id,
            'date' => $eventDate,
            'private' => true,
        ]);
        $j = Jalalian::fromCarbon($eventDate);
        $dateString = $j->format('Y-m-d');

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Calendar::class)
            ->set('navigationDate', $dateString);

        $day = collect($component->instance()->calendarDays)
            ->filter()
            ->firstWhere('date', $dateString);

        $this->assertNotNull($day);
        $this->assertFalse($day['hasEvents']);
    }

    public function test_calendar_days_shows_other_user_public_event()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $other = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $eventDate = Carbon::create(2099, 7, 23, 9);
        Event::factory()->create([
            'user_id' => $other->id,
            'date' => $eventDate,
            'private' => false,
        ]);
        $j = Jalalian::fromCarbon($eventDate);
        $dateString = $j->format('Y-m-d');

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Calendar::class)
            ->set('navigationDate', $dateString);

        $day = collect($component->instance()->calendarDays)
            ->filter()
            ->firstWhere('date', $dateString);

        $this->assertNotNull($day);
        $this->assertTrue($day['hasEvents']);
    }

    public function test_calendar_days_includes_private_event_shared_to_auth_user()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $other = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $eventDate = Carbon::create(2099, 7, 23, 9);
        $event = Event::factory()->create([
            'user_id' => $other->id,
            'date' => $eventDate,
            'private' => true,
        ]);
        DB::table('event_shares')->insert([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'shared_by' => $other->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $j = Jalalian::fromCarbon($eventDate);
        $dateString = $j->format('Y-m-d');

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Calendar::class)
            ->set('navigationDate', $dateString);

        $day = collect($component->instance()->calendarDays)
            ->filter()
            ->firstWhere('date', $dateString);

        $this->assertNotNull($day);
        $this->assertTrue($day['hasEvents']);
        $this->assertTrue($day['hasShared']);
    }

    public function test_calendar_days_marks_has_shared_for_owned_event_with_shares()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $recipient = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $eventDate = Carbon::create(2099, 7, 23, 9);
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'date' => $eventDate,
            'private' => false,
        ]);
        DB::table('event_shares')->insert([
            'event_id' => $event->id,
            'user_id' => $recipient->id,
            'shared_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $j = Jalalian::fromCarbon($eventDate);
        $dateString = $j->format('Y-m-d');

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Calendar::class)
            ->set('navigationDate', $dateString);

        $day = collect($component->instance()->calendarDays)
            ->filter()
            ->firstWhere('date', $dateString);

        $this->assertNotNull($day);
        $this->assertTrue($day['hasShared']);
    }

    public function test_calendar_days_marks_has_imminent_shared_for_shared_event_within_24h()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $recipient = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $eventDate = Carbon::now()->addHours(12);
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'date' => $eventDate,
            'private' => false,
        ]);
        DB::table('event_shares')->insert([
            'event_id' => $event->id,
            'user_id' => $recipient->id,
            'shared_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $j = Jalalian::fromCarbon($eventDate);
        $dateString = $j->format('Y-m-d');

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Calendar::class)
            ->set('navigationDate', $dateString);

        $day = collect($component->instance()->calendarDays)
            ->filter()
            ->firstWhere('date', $dateString);

        $this->assertNotNull($day);
        $this->assertTrue($day['hasShared']);
        $this->assertTrue($day['hasImminentShared']);
    }

    public function test_selected_day_events_orders_holidays_then_events_then_birthdays_then_anniversaries()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $holidayDate = '1405-01-12';
        $gregorian = Jalalian::fromFormat('Y-m-d', $holidayDate)->toCarbon();
        Profile::factory()->create([
            'user_id' => $user->id,
            'birthdate' => $gregorian->format('Y-m-d'),
            'start_date' => null,
        ]);
        Profile::factory()->create([
            'user_id' => User::factory()->create(['status' => 'active', 'presence' => 'onsite'])->id,
            'birthdate' => null,
            'start_date' => $gregorian->copy()->subYears(3)->format('Y-m-d'),
        ]);
        Event::factory()->create([
            'user_id' => $user->id,
            'date' => $gregorian->copy()->setTime(10, 0),
            'private' => false,
        ]);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', $holidayDate);

        $typesInOrder = $component->instance()->selectedDayEvents
            ->pluck('type')->unique()->values()->all();

        $this->assertSame(['holiday', 'event', 'birthday', 'anniversary'], $typesInOrder);
    }

    public function test_selected_day_events_marks_ownership_and_shared_flags()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $other = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $date = Carbon::create(2099, 7, 23, 10);
        $own = Event::factory()->create(['user_id' => $user->id, 'date' => $date, 'private' => false]);
        $sharedToMe = Event::factory()->create(['user_id' => $other->id, 'date' => $date, 'private' => true]);
        DB::table('event_shares')->insert([
            'event_id' => $sharedToMe->id,
            'user_id' => $user->id,
            'shared_by' => $other->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $publicOther = Event::factory()->create(['user_id' => $other->id, 'date' => $date, 'private' => false]);
        $selectedDate = Jalalian::fromCarbon($date)->format('Y-m-d');

        Livewire::withoutLazyLoading();
        $events = Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', $selectedDate)
            ->instance()->selectedDayEvents;

        $ownEntry = $events->firstWhere('id', $own->id);
        $this->assertNotNull($ownEntry);
        $this->assertTrue($ownEntry['is_owner']);
        $this->assertFalse($ownEntry['is_shared']);
        $this->assertNull($ownEntry['shared_by_name']);

        $sharedEntry = $events->firstWhere('id', $sharedToMe->id);
        $this->assertNotNull($sharedEntry);
        $this->assertFalse($sharedEntry['is_owner']);
        $this->assertTrue($sharedEntry['is_shared']);
        $this->assertSame($other->name, $sharedEntry['shared_by_name']);

        $publicEntry = $events->firstWhere('id', $publicOther->id);
        $this->assertNotNull($publicEntry);
        $this->assertFalse($publicEntry['is_owner']);
        $this->assertFalse($publicEntry['is_shared']);
        $this->assertNull($publicEntry['shared_by_name']);
    }

    public function test_selected_day_events_exposes_remind_hours()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $date = Carbon::create(2099, 7, 23, 10);
        $withReminder = Event::factory()->create([
            'user_id' => $user->id,
            'date' => $date,
            'private' => false,
            'remind_hours' => 3,
        ]);
        $withoutReminder = Event::factory()->create([
            'user_id' => $user->id,
            'date' => $date,
            'private' => false,
            'remind_hours' => null,
        ]);
        $selectedDate = Jalalian::fromCarbon($date)->format('Y-m-d');

        Livewire::withoutLazyLoading();
        $events = Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', $selectedDate)
            ->instance()->selectedDayEvents;

        $this->assertSame(3, $events->firstWhere('id', $withReminder->id)['remind_hours']);
        $this->assertNull($events->firstWhere('id', $withoutReminder->id)['remind_hours']);
    }

    public function test_selected_day_events_marks_reservation_linked_flag_and_id()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $date = Carbon::create(2099, 7, 23, 10);
        $reservationEvent = Event::factory()->create([
            'user_id' => $user->id,
            'description' => $this->reservationDescription(58),
            'date' => $date,
            'private' => true,
        ]);
        $ordinaryEvent = Event::factory()->create(['user_id' => $user->id, 'date' => $date, 'private' => false]);
        $selectedDate = Jalalian::fromCarbon($date)->format('Y-m-d');

        Livewire::withoutLazyLoading();
        $events = Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', $selectedDate)
            ->instance()->selectedDayEvents;

        $reservationEntry = $events->firstWhere('id', $reservationEvent->id);
        $this->assertNotNull($reservationEntry);
        $this->assertTrue($reservationEntry['is_reservation_linked']);
        $this->assertSame(58, $reservationEntry['reservation_id']);

        $ordinaryEntry = $events->firstWhere('id', $ordinaryEvent->id);
        $this->assertNotNull($ordinaryEntry);
        $this->assertFalse($ordinaryEntry['is_reservation_linked']);
        $this->assertNull($ordinaryEntry['reservation_id']);
    }

    public function test_selected_day_events_shows_long_hold_duration_only_on_its_start_day()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $resource = Resource::factory()->create(['type' => 'seat']);
        $start = Carbon::create(2036, 6, 10, 9);
        $end = $start->copy()->addDays(18);

        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'start_time' => $start->copy(),
            'end_time' => $end->copy(),
            'is_full_day' => false,
            'status' => ReservationStatus::Active->value,
        ]);

        Livewire::withoutLazyLoading();
        $startEntry = Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', Jalalian::fromCarbon($start)->format('Y-m-d'))
            ->instance()->selectedDayEvents
            ->firstWhere('is_reservation_linked', true);

        $this->assertSame(18, $startEntry['long_hold_days']);
        $this->assertNotEmpty($startEntry['long_hold_end_date']);

        Livewire::withoutLazyLoading();
        $endEntry = Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', Jalalian::fromCarbon($end)->format('Y-m-d'))
            ->instance()->selectedDayEvents
            ->firstWhere('is_reservation_linked', true);

        $this->assertNull($endEntry['long_hold_days']);
        $this->assertNull($endEntry['long_hold_end_date']);
    }

    public function test_selected_day_events_anniversary_title_includes_year_count_when_positive()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $date = Carbon::create(2099, 7, 23, 10);
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'birthdate' => null,
            'start_date' => $date->copy()->subYears(2)->format('Y-m-d'),
        ]);
        $selectedDate = Jalalian::fromCarbon($date)->format('Y-m-d');

        Livewire::withoutLazyLoading();
        $events = Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', $selectedDate)
            ->instance()->selectedDayEvents;

        $anniversary = $events->firstWhere('id', 'anniversary-' . $profile->id);

        $this->assertNotNull($anniversary);
        $this->assertStringContainsString('2مین', $anniversary['title']);
        $this->assertStringContainsString('سالگرد همکاری', $anniversary['title']);
    }

    public function test_mount_marks_calendar_viewed_for_today(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Cache::forget("tab_viewed:calendar:{$user->id}");

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class);

        $this->assertSame(now()->toDateString(), Cache::get("tab_viewed:calendar:{$user->id}"));
        $this->assertTrue(StateService::viewedToday('calendar'));
    }

    public function test_mount_does_not_bump_menu_state_version_on_second_same_day_open(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Cache::forget("tab_viewed:calendar:{$user->id}");

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class);
        $versionAfterFirstOpen = Cache::get('menu_state:version');

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class);
        $versionAfterSecondOpen = Cache::get('menu_state:version');

        $this->assertSame($versionAfterFirstOpen, $versionAfterSecondOpen);
    }

    public function test_visibility_legend_renders_privacy_and_ownership_explanations(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Calendar::class)
            ->assertSee('راهنمای دیدن و ویرایش رویدادها')
            ->assertSee('رویداد خصوصی')
            ->assertSee('رویداد عمومی')
            ->assertSee('مالکیت و ویرایش');
    }

    public function test_month_view_renders()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Calendar::class)
            ->call('toggleView', 'month')
            ->assertHasNoErrors()
            ->assertSet('view', 'month');

        $range = $component->instance()->rangeEvents;
        $this->assertArrayHasKey('byDay', $range);
        $this->assertArrayHasKey('allDayEntries', $range);
    }

    public function test_week_view_renders()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Calendar::class)
            ->call('toggleView', 'week')
            ->assertHasNoErrors()
            ->assertSet('view', 'week');

        $range = $component->instance()->rangeEvents;
        $this->assertArrayHasKey('byDay', $range);
        $this->assertArrayHasKey('allDayEntries', $range);
    }

    public function test_day_view_renders()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Calendar::class)
            ->call('toggleView', 'day')
            ->assertHasNoErrors()
            ->assertSet('view', 'day');

        $range = $component->instance()->rangeEvents;
        $this->assertArrayHasKey('byDay', $range);
        $this->assertArrayHasKey('allDayEntries', $range);
    }

    public function test_day_view_event_block_shows_end_time_when_duration_exceeds_thirty_minutes()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $date = Carbon::create(2099, 7, 23, 9, 0);
        Event::factory()->create([
            'user_id' => $user->id,
            'title' => 'Long duration probe',
            'date' => $date,
            'duration_minutes' => 90,
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', Jalalian::fromCarbon($date)->format('Y-m-d'))
            ->set('navigationDate', Jalalian::fromCarbon($date)->format('Y-m-d'))
            ->call('toggleView', 'day')
            ->assertSee(convertToPersian('09:00') . '–' . convertToPersian('10:30'));
    }

    public function test_day_view_event_block_hides_end_time_when_duration_is_thirty_minutes_or_less()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $date = Carbon::create(2099, 7, 23, 13, 0);
        Event::factory()->create([
            'user_id' => $user->id,
            'title' => 'Short duration probe',
            'date' => $date,
            'duration_minutes' => 30,
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', Jalalian::fromCarbon($date)->format('Y-m-d'))
            ->set('navigationDate', Jalalian::fromCarbon($date)->format('Y-m-d'))
            ->call('toggleView', 'day')
            ->assertSee(convertToPersian('13:00'))
            ->assertDontSee(convertToPersian('13:00') . '–');
    }

    public function test_day_view_event_block_caps_end_time_label_at_day_boundary_when_duration_crosses_midnight()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $date = Carbon::create(2099, 7, 23, 20, 0);
        Event::factory()->create([
            'user_id' => $user->id,
            'title' => 'Overnight probe',
            'date' => $date,
            'duration_minutes' => 480,
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', Jalalian::fromCarbon($date)->format('Y-m-d'))
            ->set('navigationDate', Jalalian::fromCarbon($date)->format('Y-m-d'))
            ->call('toggleView', 'day')
            ->assertSee(convertToPersian('20:00') . '–' . convertToPersian('24:00'))
            ->assertDontSee(convertToPersian('20:00') . '–' . convertToPersian('04:00'));
    }

    public function test_day_view_event_block_shows_hover_edit_icon_for_owned_non_reservation_event()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $date = Carbon::create(2099, 7, 23, 9, 0);
        Event::factory()->create([
            'user_id' => $user->id,
            'title' => 'Editable probe',
            'date' => $date,
            'duration_minutes' => 30,
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', Jalalian::fromCarbon($date)->format('Y-m-d'))
            ->set('navigationDate', Jalalian::fromCarbon($date)->format('Y-m-d'))
            ->call('toggleView', 'day')
            ->assertSee('group-hover:opacity-70', false);
    }

    public function test_selected_day_events_sidebar_shows_end_time_when_duration_exceeds_thirty_minutes()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $date = Carbon::create(2099, 7, 23, 9, 0);
        Event::factory()->create([
            'user_id' => $user->id,
            'title' => 'Sidebar long duration probe',
            'date' => $date,
            'duration_minutes' => 90,
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', Jalalian::fromCarbon($date)->format('Y-m-d'))
            ->assertSee(convertToPersian('09:00') . '–' . convertToPersian('10:30'));
    }

    public function test_selected_day_events_sidebar_hides_end_time_when_duration_is_thirty_minutes_or_less()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $date = Carbon::create(2099, 7, 23, 13, 0);
        Event::factory()->create([
            'user_id' => $user->id,
            'title' => 'Sidebar short duration probe',
            'date' => $date,
            'duration_minutes' => 30,
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', Jalalian::fromCarbon($date)->format('Y-m-d'))
            ->assertSee(convertToPersian('13:00'))
            ->assertDontSee(convertToPersian('13:00') . '–');
    }

    public function test_toggle_view_whitelists_and_persists_session()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->call('toggleView', 'week')
            ->assertSet('view', 'week');
        $this->assertSame('week', session('calendar_view_mode'));

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->call('toggleView', 'day')
            ->assertSet('view', 'day');
        $this->assertSame('day', session('calendar_view_mode'));

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->call('toggleView', 'bogus')
            ->assertSet('view', 'day');
        $this->assertSame('day', session('calendar_view_mode'));

        session(['calendar_view_mode' => 'week']);
        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->assertSet('view', 'week');

        session(['calendar_view_mode' => 'bogus']);
        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->assertSet('view', 'month');
    }

    public function test_move_event_owner_guard_non_owner_returns_not_owner_revert_with_persian_toast_not_404()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $stranger = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $event = Event::factory()->create([
            'user_id' => $owner->id,
            'date' => Carbon::create(2099, 7, 23, 9),
            'private' => false,
        ]);
        $clientMtime = $event->updated_at->toIso8601String();
        $original = $event->date->format('Y-m-d H:i:s');

        Livewire::withoutLazyLoading();
        Livewire::actingAs($stranger)->test(Calendar::class)
            ->call('moveEvent', $event->id, '1405-06-01', '14:30', $clientMtime)
            ->assertHasNoErrors()
            ->assertDispatched('toast', message: 'شما مالک این رویداد نیستید.', type: 'error')
            ->assertDispatched('revert-event-' . $event->id);

        $this->assertNotNull(Event::find($event->id));
        $this->assertSame($original, Event::find($event->id)->date->format('Y-m-d H:i:s'));
    }

    public function test_move_event_refuses_reservation_linked_event_with_locked_revert()
    {
        $booker = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $event = Event::factory()->create([
            'user_id' => $booker->id,
            'description' => $this->reservationDescription(42),
            'date' => Carbon::create(2099, 7, 23, 9),
            'private' => true,
        ]);
        $clientMtime = $event->updated_at->toIso8601String();
        $original = $event->date->format('Y-m-d H:i:s');

        Livewire::withoutLazyLoading();
        Livewire::actingAs($booker)->test(Calendar::class)
            ->call('moveEvent', $event->id, '1405-06-01', '14:30', $clientMtime)
            ->assertHasNoErrors()
            ->assertDispatched('toast', message: 'این رویداد از طریق سیستم رزرو مدیریت می‌شود؛ برای تغییر آن به تب رزرو مراجعه کنید.', type: 'error')
            ->assertDispatched('revert-event-' . $event->id);

        $this->assertSame($original, Event::find($event->id)->date->format('Y-m-d H:i:s'));
    }

    public function test_move_event_invalid_jalali_date_rejects_non_leap_esfand_30()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::create(2099, 7, 23, 9),
            'private' => false,
        ]);

        $leapClientMtime = $event->updated_at->toIso8601String();
        $leapGreg = Jalalian::fromFormat('Y-m-d', '1403-12-30')->toCarbon();
        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->call('moveEvent', $event->id, '1403-12-30', '09:00', $leapClientMtime)
            ->assertDispatched('toast', message: 'رویداد جابجا شد.', type: 'success');
        $event->refresh();
        $this->assertSame($leapGreg->format('Y-m-d'), $event->date->format('Y-m-d'));

        $nonLeapClientMtime = $event->updated_at->toIso8601String();
        $original = $event->date->format('Y-m-d H:i:s');
        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->call('moveEvent', $event->id, '1404-12-30', '09:00', $nonLeapClientMtime)
            ->assertDispatched('toast', message: 'تاریخ یا زمان نامعتبر است.', type: 'error')
            ->assertDispatched('revert-event-' . $event->id);
        $event->refresh();
        $this->assertSame($original, $event->date->format('Y-m-d H:i:s'));
    }

    public function test_move_event_invalid_time_regex_rejects_bad_time()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::create(2099, 7, 23, 9),
            'private' => false,
        ]);
        $clientMtime = $event->updated_at->toIso8601String();
        $original = $event->date->format('Y-m-d H:i:s');
        $currentJalali = Jalalian::fromCarbon($event->date)->format('Y-m-d');

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->call('moveEvent', $event->id, $currentJalali, '25:00', $clientMtime)
            ->assertDispatched('toast', message: 'تاریخ یا زمان نامعتبر است.', type: 'error')
            ->assertDispatched('revert-event-' . $event->id);

        $event->refresh();
        $this->assertSame($original, $event->date->format('Y-m-d H:i:s'));
    }

    public function test_move_event_valid_redate_via_date_jalali()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::create(2099, 7, 23, 9),
            'private' => false,
        ]);
        $clientMtime = $event->updated_at->toIso8601String();
        $newJalali = '1405-06-01';
        $newGreg = Jalalian::fromFormat('Y-m-d', $newJalali)->toCarbon();

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->call('moveEvent', $event->id, $newJalali, '09:00', $clientMtime)
            ->assertDispatched('toast', message: 'رویداد جابجا شد.', type: 'success');

        $event->refresh();
        $this->assertSame($newGreg->format('Y-m-d'), $event->date->format('Y-m-d'));
        $this->assertSame('09:00', $event->date->format('H:i'));
    }

    public function test_move_event_valid_retime_via_date_time_part()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::create(2099, 7, 23, 9),
            'private' => false,
        ]);
        $clientMtime = $event->updated_at->toIso8601String();
        $currentJalali = Jalalian::fromCarbon($event->date)->format('Y-m-d');

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->call('moveEvent', $event->id, $currentJalali, '14:30', $clientMtime)
            ->assertDispatched('toast', message: 'رویداد جابجا شد.', type: 'success');

        $event->refresh();
        $this->assertSame('2099-07-23', $event->date->format('Y-m-d'));
        $this->assertSame('14:30', $event->date->format('H:i'));
    }

    public function test_move_event_optimistic_concurrency_stale_revert()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::create(2099, 7, 23, 9),
            'private' => false,
        ]);
        $staleClientMtime = $event->updated_at->toIso8601String();

        $concurrentDate = Carbon::create(2099, 8, 15, 11, 30);
        Event::where('id', $event->id)->update([
            'date' => $concurrentDate,
            'updated_at' => now()->addSeconds(5),
        ]);
        $event->refresh();
        $concurrentJalali = Jalalian::fromCarbon($concurrentDate)->format('Y-m-d');

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->call('moveEvent', $event->id, '1405-06-01', '14:30', $staleClientMtime)
            ->assertDispatched('toast', message: 'رویداد توسط کاربر دیگری تغییر کرد؛ تغییر شما برگردانده شد.', type: 'error')
            ->assertDispatched('revert-event-' . $event->id, function ($name, $params) use ($concurrentJalali) {
                return ($params[0]['dateJalali'] ?? null) === $concurrentJalali
                    && ($params[0]['timePart'] ?? null) === '11:30';
            });

        $event->refresh();
        $this->assertSame($concurrentDate->format('Y-m-d H:i:s'), $event->date->format('Y-m-d H:i:s'));
    }

    public function test_move_event_action_returns_not_owner_for_non_owner()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $stranger = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $event = Event::factory()->create([
            'user_id' => $owner->id,
            'date' => Carbon::create(2099, 7, 23, 9),
            'private' => false,
        ]);
        RateLimiter::clear('calendar-move:' . $stranger->id);

        $this->actingAs($stranger);
        $res = app(MoveEventAction::class)->execute($event->id, '1405-06-01', '14:30', $event->updated_at->toIso8601String());

        $this->assertFalse($res['ok']);
        $this->assertSame('not_owner', $res['reason']);
    }

    public function test_move_event_action_rate_limits_after_30_per_minute_per_user()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::create(2099, 7, 1, 9),
            'private' => false,
        ]);
        RateLimiter::clear('calendar-move:' . $user->id);
        $this->actingAs($user);

        $action = app(MoveEventAction::class);
        for ($i = 0; $i < 30; $i++) {
            $target = Jalalian::fromCarbon(Carbon::create(2099, 7, 1, 9)->addDays($i))->format('Y-m-d');
            $res = $action->execute($event->id, $target, '09:00', $event->fresh()->updated_at->toIso8601String());
            $this->assertTrue($res['ok'], "move #{$i} should succeed");
        }

        $res = $action->execute($event->id, '1405-06-01', '09:00', $event->fresh()->updated_at->toIso8601String());
        $this->assertFalse($res['ok']);
        $this->assertSame('rate_limited', $res['reason']);
    }

    public function test_jalali_date_rule_accepts_leap_esfand_30_and_rejects_non_leap_and_malformed()
    {
        $passes = fn($d) => validator(['d' => $d], ['d' => new JalaliDateRule])->passes();

        $this->assertTrue($passes('1403-12-30'));
        $this->assertFalse($passes('1404-12-30'));
        $this->assertFalse($passes('not-a-date'));
        $this->assertFalse($passes('1405-13-01'));
    }

    public function test_save_event_persists_duration_minutes_on_create()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $now = Jalalian::now();

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->set('form.title', 'رویداد با مدت')
            ->set('form.dateYear', (string) $now->getYear())
            ->set('form.dateMonth', (string) $now->getMonth())
            ->set('form.dateDay', (string) $now->getDay())
            ->set('form.time', '10:00')
            ->set('form.durationMinutes', 90)
            ->call('saveEvent')
            ->assertHasNoErrors();

        $this->assertSame(90, Event::where('user_id', $user->id)->value('duration_minutes'));
    }

    public function test_save_event_updates_duration_minutes_on_edit()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::create(2099, 7, 23, 9),
            'duration_minutes' => 30,
            'private' => false,
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->call('editEvent', $event->id)
            ->set('form.durationMinutes', 120)
            ->call('saveEvent')
            ->assertHasNoErrors();

        $this->assertSame(120, $event->fresh()->duration_minutes);
    }

    public function test_edit_event_populates_duration_minutes_and_defaults_null_to_thirty()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $withDuration = Event::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::create(2099, 7, 23, 9),
            'duration_minutes' => 45,
            'private' => false,
        ]);
        $nullDuration = Event::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::create(2099, 7, 24, 9),
            'duration_minutes' => null,
            'private' => false,
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->call('editEvent', $withDuration->id)
            ->assertSet('form.durationMinutes', 45);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->call('editEvent', $nullDuration->id)
            ->assertSet('form.durationMinutes', Event::DEFAULT_DURATION_MINUTES);
    }

    public function test_resize_event_action_owner_guard_non_owner_returns_not_owner_without_revert()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $stranger = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $event = Event::factory()->create([
            'user_id' => $owner->id,
            'date' => Carbon::create(2099, 7, 23, 9),
            'duration_minutes' => 30,
            'private' => false,
        ]);
        RateLimiter::clear('calendar-resize:' . $stranger->id);
        $this->actingAs($stranger);

        $res = app(ResizeEventAction::class)->execute($event->id, 60, $event->updated_at->toIso8601String());

        $this->assertFalse($res['ok']);
        $this->assertSame('not_owner', $res['reason']);
        $this->assertArrayNotHasKey('revertTo', $res);
        $this->assertSame(30, $event->fresh()->duration_minutes);
    }

    public function test_resize_event_action_refuses_reservation_linked_event_with_locked_revert()
    {
        $booker = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $event = Event::factory()->create([
            'user_id' => $booker->id,
            'description' => $this->reservationDescription(42),
            'date' => Carbon::create(2099, 7, 23, 9),
            'duration_minutes' => 30,
            'private' => true,
        ]);
        RateLimiter::clear('calendar-resize:' . $booker->id);
        $this->actingAs($booker);

        $res = app(ResizeEventAction::class)->execute($event->id, 60, $event->updated_at->toIso8601String());

        $this->assertFalse($res['ok']);
        $this->assertSame('locked', $res['reason']);
        $this->assertSame(30, $res['revertTo']['durationMinutes']);
        $this->assertSame(30, $event->fresh()->duration_minutes);
    }

    public function test_resize_event_action_rejects_too_short_duration()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::create(2099, 7, 23, 9),
            'duration_minutes' => 30,
            'private' => false,
        ]);
        RateLimiter::clear('calendar-resize:' . $user->id);
        $this->actingAs($user);

        $res = app(ResizeEventAction::class)->execute($event->id, 10, $event->updated_at->toIso8601String());

        $this->assertFalse($res['ok']);
        $this->assertSame('invalid_input', $res['reason']);
        $this->assertSame(30, $res['revertTo']['durationMinutes']);
        $this->assertSame(30, $event->fresh()->duration_minutes);
    }

    public function test_resize_event_action_rejects_duration_exceeding_day_end()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::create(2099, 7, 23, 23, 0),
            'duration_minutes' => 30,
            'private' => false,
        ]);
        RateLimiter::clear('calendar-resize:' . $user->id);
        $this->actingAs($user);

        $res = app(ResizeEventAction::class)->execute($event->id, 120, $event->updated_at->toIso8601String());

        $this->assertFalse($res['ok']);
        $this->assertSame('invalid_input', $res['reason']);
        $this->assertSame(30, $event->fresh()->duration_minutes);
    }

    public function test_resize_event_action_success_persists_duration_minutes()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::create(2099, 7, 23, 9),
            'duration_minutes' => 30,
            'private' => false,
        ]);
        RateLimiter::clear('calendar-resize:' . $user->id);
        $this->actingAs($user);

        $res = app(ResizeEventAction::class)->execute($event->id, 120, $event->updated_at->toIso8601String());

        $this->assertTrue($res['ok']);
        $this->assertArrayHasKey('newMtime', $res);
        $this->assertSame(120, $event->fresh()->duration_minutes);
    }

    public function test_resize_event_action_stale_mtime_reverts()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::create(2099, 7, 23, 9),
            'duration_minutes' => 30,
            'private' => false,
        ]);
        $staleMtime = $event->updated_at->toIso8601String();
        Event::where('id', $event->id)->update(['duration_minutes' => 45, 'updated_at' => now()->addSeconds(5)]);
        RateLimiter::clear('calendar-resize:' . $user->id);
        $this->actingAs($user);

        $res = app(ResizeEventAction::class)->execute($event->id, 120, $staleMtime);

        $this->assertFalse($res['ok']);
        $this->assertSame('stale', $res['reason']);
        $this->assertSame(45, $res['revertTo']['durationMinutes']);
        $this->assertSame(45, $event->fresh()->duration_minutes);
    }

    public function test_resize_event_via_component_dispatches_success_toast_and_persists()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::create(2099, 7, 23, 9),
            'duration_minutes' => 30,
            'private' => false,
        ]);
        RateLimiter::clear('calendar-resize:' . $user->id);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->call('resizeEvent', $event->id, 90, $event->updated_at->toIso8601String())
            ->assertDispatched('toast', message: 'مدت رویداد به‌روزرسانی شد.', type: 'success');

        $this->assertSame(90, $event->fresh()->duration_minutes);
    }

    public function test_resize_event_via_component_dispatches_revert_on_locked_failure()
    {
        $booker = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $event = Event::factory()->create([
            'user_id' => $booker->id,
            'description' => $this->reservationDescription(7),
            'date' => Carbon::create(2099, 7, 23, 9),
            'duration_minutes' => 30,
            'private' => true,
        ]);
        RateLimiter::clear('calendar-resize:' . $booker->id);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($booker)->test(Calendar::class)
            ->call('resizeEvent', $event->id, 90, $event->updated_at->toIso8601String())
            ->assertDispatched('toast', message: 'این رویداد از طریق سیستم رزرو مدیریت می‌شود؛ برای تغییر آن به تب رزرو مراجعه کنید.', type: 'error')
            ->assertDispatched('revert-resize-' . $event->id);

        $this->assertSame(30, $event->fresh()->duration_minutes);
    }

    public function test_calendar_layout_pack_assigns_single_column_for_non_overlapping_events()
    {
        $pills = [
            ['id' => 1, 'start_minutes' => 540, 'duration_minutes' => 30],
            ['id' => 2, 'start_minutes' => 600, 'duration_minutes' => 30],
            ['id' => 3, 'start_minutes' => 660, 'duration_minutes' => 30],
        ];

        $packed = CalendarLayout::pack($pills);

        $this->assertCount(3, $packed);
        foreach ($packed as $p) {
            $this->assertSame(0, $p['col']);
            $this->assertSame(1, $p['span']);
        }
    }

    public function test_calendar_layout_pack_assigns_overlapping_cluster_columns_and_span()
    {
        $pills = [
            ['id' => 1, 'start_minutes' => 540, 'duration_minutes' => 90],
            ['id' => 2, 'start_minutes' => 570, 'duration_minutes' => 60],
            ['id' => 3, 'start_minutes' => 600, 'duration_minutes' => 30],
        ];

        $packed = collect(CalendarLayout::pack($pills))->keyBy('id');

        $this->assertSame(0, $packed[1]['col']);
        $this->assertSame(1, $packed[2]['col']);
        $this->assertSame(2, $packed[3]['col']);
        $this->assertSame(3, $packed[1]['span']);
        $this->assertSame(3, $packed[2]['span']);
        $this->assertSame(3, $packed[3]['span']);
    }

    public function test_calendar_layout_pack_longer_event_anchors_column_zero()
    {
        $pills = [
            ['id' => 1, 'start_minutes' => 540, 'duration_minutes' => 120],
            ['id' => 2, 'start_minutes' => 540, 'duration_minutes' => 30],
        ];

        $packed = collect(CalendarLayout::pack($pills))->keyBy('id');

        $this->assertSame(0, $packed[1]['col']);
        $this->assertSame(1, $packed[2]['col']);
        $this->assertSame(2, $packed[1]['span']);
        $this->assertSame(2, $packed[2]['span']);
    }

    public function test_range_events_packs_timed_events_with_col_span_and_defaults_null_duration()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $date = Carbon::create(2099, 7, 23, 9);
        $jKey = Jalalian::fromCarbon($date)->format('Y-m-d');
        Event::factory()->create([
            'user_id' => $user->id,
            'date' => $date->copy()->setTime(9, 0),
            'duration_minutes' => 60,
            'private' => false,
        ]);
        Event::factory()->create([
            'user_id' => $user->id,
            'date' => $date->copy()->setTime(9, 30),
            'duration_minutes' => null,
            'private' => false,
        ]);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', $jKey)
            ->set('navigationDate', $jKey)
            ->call('toggleView', 'day');

        $pills = $component->instance()->rangeEvents['byDay'][$jKey] ?? [];

        $this->assertCount(2, $pills);
        $this->assertSame(0, $pills[0]['col']);
        $this->assertSame(1, $pills[1]['col']);
        $this->assertSame(2, $pills[0]['span']);
        $this->assertSame(2, $pills[1]['span']);
        $this->assertSame(Event::DEFAULT_DURATION_MINUTES, $pills[1]['duration_minutes']);
    }

    public function test_range_events_packs_timed_reservation_pills_like_normal_pills()
    {
        $booker = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $date = Carbon::create(2099, 7, 23, 9);
        $jKey = Jalalian::fromCarbon($date)->format('Y-m-d');
        Event::factory()->create([
            'user_id' => $booker->id,
            'date' => $date->copy()->setTime(9, 0),
            'duration_minutes' => 60,
            'private' => false,
        ]);
        Event::factory()->create([
            'user_id' => $booker->id,
            'description' => $this->reservationDescription(11),
            'date' => $date->copy()->setTime(10, 0),
            'duration_minutes' => 30,
            'private' => true,
        ]);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($booker)->test(Calendar::class)
            ->set('selectedDate', $jKey)
            ->set('navigationDate', $jKey)
            ->call('toggleView', 'day');

        $pills = $component->instance()->rangeEvents['byDay'][$jKey] ?? [];

        $timed = collect($pills)->filter(fn($p) => empty($p['is_reservation_linked']))->values();
        $reservations = collect($pills)->filter(fn($p) => !empty($p['is_reservation_linked']))->values();

        $this->assertCount(1, $timed);
        $this->assertSame(0, $timed[0]['col']);
        $this->assertSame(1, $timed[0]['span']);
        $this->assertCount(1, $reservations);
        $this->assertSame(0, $reservations[0]['col']);
        $this->assertSame(1, $reservations[0]['span']);
        $this->assertTrue($reservations[0]['locked']);
        $this->assertSame('11', (string) $reservations[0]['reservation_id']);
    }

    public function test_event_block_data_shows_end_time_when_duration_exceeds_thirty_minutes()
    {
        $p = new CalendarPresenter();
        $b = $p->eventBlockData([
            'locked' => false, 'is_reservation_linked' => false, 'is_owner' => true,
            'top' => 120, 'height' => 60, 'left_pct' => 0, 'width_pct' => 100,
            'duration_minutes' => 90, 'start_minutes' => 540, 'time' => '09:00',
        ]);
        $this->assertTrue($b['draggable']);
        $this->assertSame(120, $b['topPx']);
        $this->assertSame(60, $b['heightPx']);
        $this->assertSame(convertToPersian('09:00') . '–' . convertToPersian('10:30'), $b['rangeLabel']);
    }

    public function test_event_block_data_hides_end_time_when_duration_is_thirty_minutes_or_less()
    {
        $p = new CalendarPresenter();
        $b = $p->eventBlockData([
            'is_owner' => true, 'duration_minutes' => 30, 'start_minutes' => 540, 'time' => '09:00',
        ]);
        $this->assertSame(convertToPersian('09:00'), $b['rangeLabel']);
    }

    public function test_event_block_data_caps_end_time_at_day_boundary_when_crossing_midnight()
    {
        $p = new CalendarPresenter();
        $b = $p->eventBlockData([
            'is_owner' => true, 'duration_minutes' => 480, 'start_minutes' => 1200, 'time' => '20:00',
        ]);
        $this->assertSame(convertToPersian('20:00') . '–' . convertToPersian('24:00'), $b['rangeLabel']);
    }

    public function test_event_block_data_marks_reservation_and_locked_events_non_draggable()
    {
        $p = new CalendarPresenter();
        $res = $p->eventBlockData(['is_owner' => true, 'is_reservation_linked' => true, 'duration_minutes' => 90, 'start_minutes' => 540, 'time' => '09:00']);
        $this->assertFalse($res['draggable']);
        $this->assertTrue($res['isReservation']);
        $locked = $p->eventBlockData(['is_owner' => true, 'locked' => true, 'duration_minutes' => 90, 'start_minutes' => 540, 'time' => '09:00']);
        $this->assertFalse($locked['draggable']);
        $this->assertTrue($locked['locked']);
    }

    public function test_day_event_range_label_appends_end_time_only_for_event_type_exceeding_thirty_minutes()
    {
        $p = new CalendarPresenter();
        $this->assertSame(convertToPersian('09:00') . '–' . convertToPersian('10:30'), $p->dayEventRangeLabel(['type' => 'event', 'duration_minutes' => 90, 'start_minutes' => 540, 'time' => '09:00']));
        $this->assertSame(convertToPersian('09:00'), $p->dayEventRangeLabel(['type' => 'event', 'duration_minutes' => 30, 'start_minutes' => 540, 'time' => '09:00']));
        $this->assertSame(convertToPersian('09:00'), $p->dayEventRangeLabel(['type' => 'birthday', 'duration_minutes' => 90, 'start_minutes' => 540, 'time' => '09:00']));
        $this->assertSame(convertToPersian('09:00'), $p->dayEventRangeLabel(['duration_minutes' => 90, 'start_minutes' => 540, 'time' => '09:00']));
    }

    public function test_mini_month_data_resolves_prev_and_next_month_navigation()
    {
        $p = new CalendarPresenter();
        $m = $p->miniMonthData('1403-03-15');
        $this->assertNotSame('1403-03-15', $m['prevMonth']);
        $this->assertNotSame('1403-03-15', $m['nextMonth']);
        $this->assertNotSame($m['prevMonth'], $m['nextMonth']);
        $this->assertNotEmpty($m['miniLabel']);
        $this->assertSame(['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'], $m['weekDays']);
    }

    public function test_mini_month_data_falls_back_gracefully_on_invalid_date()
    {
        $p = new CalendarPresenter();
        $m = $p->miniMonthData('not-a-date');
        $this->assertSame('', $m['miniLabel']);
        $this->assertSame('not-a-date', $m['prevMonth']);
        $this->assertSame('not-a-date', $m['nextMonth']);
    }

    public function test_guest_sees_no_reminder(): void
    {
        Livewire::test(EventReminder::class)
            ->assertViewHas('reminder', null);
    }

    public function test_authenticated_user_sees_reminder_within_window(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'date' => now()->addHours(2),
            'private' => true,
            'remind_hours' => 3,
        ]);

        Livewire::actingAs($user)->test(EventReminder::class)
            ->assertViewHas('reminder', function ($reminder) use ($event) {
                return $reminder['id'] === $event->id;
            });
    }

    public function test_authenticated_user_sees_no_reminder_before_window_opens(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Event::factory()->create([
            'user_id' => $user->id,
            'date' => now()->addHours(10),
            'private' => true,
            'remind_hours' => 3,
        ]);

        Livewire::actingAs($user)->test(EventReminder::class)
            ->assertViewHas('reminder', null);
    }

    public function test_authenticated_user_does_not_see_other_users_private_reminder(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        Event::factory()->create([
            'user_id' => $owner->id,
            'date' => now()->addHours(2),
            'private' => true,
            'remind_hours' => 3,
        ]);

        Livewire::actingAs($other)->test(EventReminder::class)
            ->assertViewHas('reminder', null);
    }

    public function test_authenticated_user_sees_reminder_for_event_shared_with_them(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $recipient = User::factory()->create(['status' => 'active']);
        $event = Event::factory()->create([
            'user_id' => $owner->id,
            'date' => now()->addHours(2),
            'private' => true,
            'remind_hours' => 3,
        ]);
        EventShare::create(['event_id' => $event->id, 'user_id' => $recipient->id, 'shared_by' => $owner->id]);

        Livewire::actingAs($recipient)->test(EventReminder::class)
            ->assertViewHas('reminder', function ($reminder) use ($event) {
                return $reminder['id'] === $event->id;
            });
    }

    public function test_due_reminder_arms_the_hidden_js_bridge(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Event::factory()->create([
            'user_id' => $user->id,
            'title' => 'جلسه بررسی پروژه',
            'date' => now()->addHour(),
            'remind_hours' => 2,
            'private' => true,
        ]);

        $html = Livewire::actingAs($user)->test(EventReminder::class)->html();

        $this->assertStringContainsString('eventAtIso', $html);
        $this->assertStringNotContainsString('event-reminder-none', $html);
    }

    public function test_event_reminder_placeholder_is_a_zero_cost_hidden_div(): void
    {
        $this->assertSame('<div class="hidden"></div>', (new EventReminder)->placeholder());
    }

    public function test_event_reminder_stays_error_free_when_the_pulse_event_fires(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)->test(EventReminder::class)
            ->dispatch('event-reminder-changed')
            ->assertHasNoErrors();
    }

    public function test_calendar_range_advance_months_recovers_from_invalid_input_and_clamps_month_end_dates(): void
    {
        $this->assertSame(Jalalian::now()->format('Y-m-d'), CalendarRange::advanceMonths('not-a-date', 1));
        $this->assertSame(Jalalian::now()->format('Y-m-d'), CalendarRange::advanceMonths('', 3));

        $year = (int) Jalalian::now()->format('Y');
        $shahrivarEnd = (new Jalalian($year, 6, 31))->format('Y-m-d');
        $this->assertSame((new Jalalian($year, 7, 30))->format('Y-m-d'), CalendarRange::advanceMonths($shahrivarEnd, 1));

        $mehrEnd = (new Jalalian($year, 7, 30))->format('Y-m-d');
        $this->assertSame((new Jalalian($year, 6, 30))->format('Y-m-d'), CalendarRange::advanceMonths($mehrEnd, -1));
    }

    public function test_week_labels_returns_seven_sat_first_persian_labels()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        $labels = Livewire::actingAs($user)->test(Calendar::class)
            ->instance()->weekLabels;

        $this->assertSame(['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'], $labels);
    }

    public function test_grid_data_returns_full_shape()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $date = Carbon::create(2099, 7, 23, 9);
        $jKey = Jalalian::fromCarbon($date)->format('Y-m-d');
        Event::factory()->create([
            'user_id' => $user->id,
            'date' => $date->copy()->setTime(9, 0),
            'duration_minutes' => 60,
            'private' => false,
        ]);

        Livewire::withoutLazyLoading();
        $grid = Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', $jKey)
            ->call('toggleView', 'day')
            ->instance()->gridData('day');

        $this->assertSame(
            ['daysMeta', 'hourOffsets', 'hourLabels', 'gridHeight', 'hourHeight', 'startHour', 'endHour', 'iconByType', 'allReservations', 'spanningReservations'],
            array_keys($grid)
        );
        $this->assertSame(1080, $grid['gridHeight']);
        $this->assertSame(60, $grid['hourHeight']);
        $this->assertSame(6, $grid['startHour']);
        $this->assertSame(24, $grid['endHour']);
        $this->assertSame(['holiday' => 'event_busy', 'birthday' => 'cake', 'anniversary' => 'celebration', 'reminder' => 'alarm'], $grid['iconByType']);
        $this->assertCount(18, $grid['hourOffsets']);
        $this->assertSame(0, $grid['hourOffsets'][6]);
        $this->assertSame(1020, $grid['hourOffsets'][23]);
        $this->assertSame('۰۶', $grid['hourLabels'][6]);
    }

    public function test_grid_data_enriches_pill_geometry()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $date = Carbon::create(2099, 7, 23, 9);
        $jKey = Jalalian::fromCarbon($date)->format('Y-m-d');
        Event::factory()->create([
            'user_id' => $user->id,
            'date' => $date->copy()->setTime(9, 0),
            'duration_minutes' => 60,
            'private' => false,
        ]);

        Livewire::withoutLazyLoading();
        $pills = Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', $jKey)
            ->call('toggleView', 'day')
            ->instance()->gridData('day')['daysMeta'][0]['dayPills'];

        $this->assertCount(1, $pills);
        $pill = $pills[0];
        $this->assertArrayHasKey('top', $pill);
        $this->assertArrayHasKey('height', $pill);
        $this->assertArrayHasKey('left_pct', $pill);
        $this->assertArrayHasKey('width_pct', $pill);
        $this->assertEquals(180, $pill['top']);
        $this->assertEquals(60, $pill['height']);
        $this->assertEquals(0, $pill['left_pct']);
        $this->assertEquals(100, $pill['width_pct']);
    }

    public function test_grid_data_gives_geometry_to_in_grid_reservation_pills()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $date = Carbon::create(2099, 7, 23, 9);
        $jKey = Jalalian::fromCarbon($date)->format('Y-m-d');
        Event::factory()->create([
            'user_id' => $user->id,
            'date' => $date->copy()->setTime(9, 0),
            'duration_minutes' => 60,
            'private' => false,
        ]);
        Event::factory()->create([
            'user_id' => $user->id,
            'description' => $this->reservationDescription(11),
            'date' => $date->copy()->setTime(10, 0),
            'duration_minutes' => 30,
            'private' => true,
        ]);

        Livewire::withoutLazyLoading();
        $grid = Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', $jKey)
            ->call('toggleView', 'day')
            ->instance()->gridData('day');

        $pills = $grid['daysMeta'][0]['dayPills'];
        $reservation = collect($pills)->firstWhere('is_reservation_linked', true);
        $ordinary = collect($pills)->firstWhere('is_reservation_linked', false);

        $this->assertNotNull($reservation);
        $this->assertArrayHasKey('top', $reservation);
        $this->assertArrayHasKey('height', $reservation);
        $this->assertArrayHasKey('left_pct', $reservation);
        $this->assertArrayHasKey('width_pct', $reservation);
        $this->assertEquals(240, $reservation['top']);
        $this->assertEquals(30, $reservation['height']);

        $this->assertNotNull($ordinary);
        $this->assertArrayHasKey('top', $ordinary);

        $this->assertCount(0, $grid['allReservations']);
    }

    public function test_grid_data_day_view_empty_day_fallback()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        $daysMeta = Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', 'not-a-valid-jalali-date')
            ->call('toggleView', 'day')
            ->instance()->gridData('day')['daysMeta'];

        $this->assertCount(1, $daysMeta);
        $meta = $daysMeta[0];
        $this->assertSame('not-a-valid-jalali-date', $meta['jKey']);
        $this->assertSame([], $meta['dayPills']);
        $this->assertSame([], $meta['dayAllDay']);
        $this->assertNull($meta['startIso']);
        $this->assertFalse($meta['isFriday']);
    }

    public function test_day_view_full_day_reservation_renders_as_full_height_block()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $resource = Resource::factory()->create(['type' => 'seat']);
        $date = Carbon::create(2035, 7, 23, 12);
        $jKey = Jalalian::fromCarbon($date)->format('Y-m-d');
        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'start_time' => $date->copy()->startOfDay(),
            'end_time' => $date->copy()->endOfDay(),
            'is_full_day' => true,
            'status' => ReservationStatus::Active->value,
        ]);

        Livewire::withoutLazyLoading();
        $grid = Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', $jKey)
            ->call('toggleView', 'day')
            ->instance()->gridData('day');

        $pills = $grid['daysMeta'][0]['dayPills'];
        $this->assertCount(1, $pills);
        $pill = $pills[0];
        $this->assertNotEmpty($pill['is_reservation_linked']);
        $this->assertNotEmpty($pill['is_full_day']);
        $this->assertEquals(0, $pill['top']);
        $this->assertEquals(1080, $pill['height']);
        $this->assertEquals(0, $pill['left_pct']);
        $this->assertEquals(100, $pill['width_pct']);
        $this->assertCount(0, $grid['allReservations']);
    }

    public function test_week_view_multi_day_reservation_renders_spanning_bar()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $resource = Resource::factory()->create(['type' => 'seat']);
        $anchor = Carbon::create(2035, 7, 23, 12);
        $weekStart = $anchor->copy()->subDays(Jalalian::fromCarbon($anchor)->getDayOfWeek())->startOfDay();
        $weekStartJKey = Jalalian::fromCarbon($weekStart)->format('Y-m-d');
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'start_time' => $weekStart->copy(),
            'end_time' => $weekStart->copy()->addDays(2)->endOfDay(),
            'is_full_day' => false,
            'status' => ReservationStatus::Active->value,
        ]);

        Livewire::withoutLazyLoading();
        $grid = Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', $weekStartJKey)
            ->call('toggleView', 'week')
            ->instance()->gridData('week');

        $this->assertCount(1, $grid['spanningReservations']);
        $span = $grid['spanningReservations'][0];
        $this->assertSame($reservation->id, $span['reservation_id']);
        $this->assertSame(0, $span['col_start']);
        $this->assertSame(3, $span['col_span']);
    }

    public function test_long_hold_reservation_dot_appears_only_on_start_and_end_days_in_month_grid()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $resource = Resource::factory()->create(['type' => 'seat']);
        $monthAnchor = Carbon::create(2036, 3, 15, 12);
        $jMonthAnchor = Jalalian::fromCarbon($monthAnchor);
        $navigationDate = $jMonthAnchor->format('Y-m-d');

        $start = Jalalian::fromFormat('Y-m-d', sprintf('%d-%02d-03', $jMonthAnchor->getYear(), $jMonthAnchor->getMonth()))->toCarbon()->setTime(9, 0);
        $end = Jalalian::fromFormat('Y-m-d', sprintf('%d-%02d-21', $jMonthAnchor->getYear(), $jMonthAnchor->getMonth()))->toCarbon()->setTime(9, 0);
        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'start_time' => $start->copy(),
            'end_time' => $end->copy(),
            'is_full_day' => false,
            'status' => ReservationStatus::Active->value,
        ]);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Calendar::class)
            ->set('navigationDate', $navigationDate);

        $days = collect($component->instance()->calendarDays)->filter();
        $startKey = Jalalian::fromCarbon($start)->format('Y-m-d');
        $midKey = Jalalian::fromCarbon($start->copy()->addDays(9))->format('Y-m-d');
        $endKey = Jalalian::fromCarbon($end)->format('Y-m-d');

        $this->assertTrue($days->firstWhere('date', $startKey)['hasReservations']);
        $this->assertTrue($days->firstWhere('date', $endKey)['hasReservations']);
        $this->assertFalse($days->firstWhere('date', $midKey)['hasReservations'], 'a long-hold reservation must not light every day it spans');

        $this->assertSame(18, $days->firstWhere('date', $startKey)['longHoldDays']);
        $this->assertNull($days->firstWhere('date', $endKey)['longHoldDays'], 'only the start day carries the day-count badge in the month grid');
    }

    public function test_long_hold_reservation_has_no_spanning_bar_and_marks_only_its_start_day_in_week_view()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $resource = Resource::factory()->create(['type' => 'seat']);
        $anchor = Carbon::create(2036, 4, 6, 12);
        $weekStart = $anchor->copy()->subDays(Jalalian::fromCarbon($anchor)->getDayOfWeek())->startOfDay();
        $weekStartJKey = Jalalian::fromCarbon($weekStart)->format('Y-m-d');

        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'start_time' => $weekStart->copy(),
            'end_time' => $weekStart->copy()->addDays(45),
            'is_full_day' => false,
            'status' => ReservationStatus::Active->value,
        ]);

        Livewire::withoutLazyLoading();
        $grid = Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', $weekStartJKey)
            ->call('toggleView', 'week')
            ->instance()->gridData('week');

        $this->assertCount(0, $grid['spanningReservations'], 'a long hold must not redraw a spanning bar every week it crosses');

        $startDayMeta = collect($grid['daysMeta'])->firstWhere('jKey', $weekStartJKey);
        $reservationPills = collect($startDayMeta['dayPills'])->where('is_reservation_linked', true);
        $this->assertCount(1, $reservationPills, 'the boundary pill renders once, on the start day only');
        $this->assertStringContainsString('شروع', $reservationPills->first()['title']);
        $this->assertSame(45, $reservationPills->first()['long_hold_days']);

        $otherDaysPills = collect($grid['daysMeta'])
            ->reject(fn($d) => $d['jKey'] === $weekStartJKey)
            ->flatMap(fn($d) => $d['dayPills'])
            ->where('is_reservation_linked', true);
        $this->assertCount(0, $otherDaysPills, 'mid-span days in this week must stay clean');
    }

    public function test_long_hold_end_day_pill_carries_no_duration_badge()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $resource = Resource::factory()->create(['type' => 'seat']);
        $end = Carbon::create(2036, 5, 20, 12);
        $start = $end->copy()->subDays(45);
        $endWeekStart = $end->copy()->subDays(Jalalian::fromCarbon($end)->getDayOfWeek())->startOfDay();
        $endWeekStartJKey = Jalalian::fromCarbon($endWeekStart)->format('Y-m-d');
        $endJKey = Jalalian::fromCarbon($end)->format('Y-m-d');

        Reservation::create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'start_time' => $start,
            'end_time' => $end,
            'is_full_day' => false,
            'status' => ReservationStatus::Active->value,
        ]);

        Livewire::withoutLazyLoading();
        $grid = Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', $endWeekStartJKey)
            ->call('toggleView', 'week')
            ->instance()->gridData('week');

        $endDayMeta = collect($grid['daysMeta'])->firstWhere('jKey', $endJKey);
        $pill = collect($endDayMeta['dayPills'])->firstWhere('is_reservation_linked', true);

        $this->assertNotNull($pill);
        $this->assertStringContainsString('پایان', $pill['title']);
        $this->assertNull($pill['long_hold_days'], 'only the start-day pill carries the duration badge, not the end-day one');
    }

    public function test_toggle_view_realigs_navigation_date_to_selected_date()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $jKey = Jalalian::fromCarbon(Carbon::create(2099, 7, 23, 9))->format('Y-m-d');

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', $jKey)
            ->call('toggleView', 'day')
            ->instance();

        $this->assertSame($jKey, $component->navigationDate);
        $this->assertSame('day', $component->view);
    }

    public function test_next_period_syncs_selected_date_to_navigation_date()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $jKey = Jalalian::fromCarbon(Carbon::create(2099, 7, 23, 9))->format('Y-m-d');
        $nextDay = Jalalian::fromCarbon(Carbon::create(2099, 7, 23, 9)->copy()->addDay())->format('Y-m-d');

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', $jKey)
            ->call('toggleView', 'day')
            ->call('nextPeriod')
            ->instance();

        $this->assertSame($nextDay, $component->navigationDate);
        $this->assertSame($nextDay, $component->selectedDate);
    }

    public function test_agenda_items_pre_converts_label_sorts_by_time_and_sets_clickability()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $other = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $date = Carbon::create(2099, 7, 23, 9);
        $jKey = Jalalian::fromCarbon($date)->format('Y-m-d');

        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'birthdate' => $date->format('Y-m-d'),
            'start_date' => null,
        ]);

        $eventB = Event::factory()->create(['user_id' => $user->id, 'date' => $date->copy()->setTime(9, 0), 'private' => false]);
        $eventA = Event::factory()->create(['user_id' => $user->id, 'date' => $date->copy()->setTime(9, 30), 'private' => false]);
        $eventR = Event::factory()->create([
            'user_id' => $user->id,
            'description' => $this->reservationDescription(7),
            'date' => $date->copy()->setTime(10, 0),
            'private' => true,
        ]);
        $eventC = Event::factory()->create(['user_id' => $other->id, 'date' => $date->copy()->setTime(11, 0), 'private' => false]);

        Livewire::withoutLazyLoading();
        $agenda = Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', $jKey)
            ->call('toggleView', 'day')
            ->instance()->agendaItems('day');

        $this->assertCount(1, $agenda);
        $day = $agenda[0];
        $this->assertSame($jKey, $day['jKey']);

        $rawLabel = Jalalian::fromCarbon($date)->format('l، d F Y');
        $this->assertSame(convertToPersian($rawLabel), $day['label']);
        $this->assertNotSame($rawLabel, $day['label']);

        $items = $day['items'];
        $times = array_column($items, 'time');
        $sorted = $times;
        sort($sorted);
        $this->assertSame($sorted, $times);

        $birthday = collect($items)->firstWhere('id', 'birthday-' . $profile->id);
        $this->assertNotNull($birthday);
        $this->assertSame('birthday', $birthday['type']);
        $this->assertSame('00:00', $birthday['time']);
        $this->assertFalse($birthday['clickable']);
        $this->assertSame('cake', $birthday['icon']);

        $b = collect($items)->firstWhere('id', $eventB->id);
        $this->assertSame('09:00', $b['time']);
        $this->assertTrue($b['clickable']);
        $this->assertSame('event', $b['icon']);

        $r = collect($items)->firstWhere('id', $eventR->id);
        $this->assertSame('10:00', $r['time']);
        $this->assertFalse($r['clickable']);
        $this->assertSame('event_seat', $r['icon']);

        $c = collect($items)->firstWhere('id', $eventC->id);
        $this->assertSame('11:00', $c['time']);
        $this->assertFalse($c['clickable']);
        $this->assertSame('event', $c['icon']);
    }

    public function test_go_to_date_navigates_and_syncs_mini_month()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $target = Jalalian::fromCarbon(Carbon::create(2035, 7, 23, 9))->format('Y-m-d');

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Calendar::class)
            ->call('goToDate', $target)
            ->instance();

        $this->assertSame($target, $component->navigationDate);
        $this->assertSame($target, $component->selectedDate);
        $this->assertSame($target, $component->miniMonthDate);
    }

    public function test_step_mini_month_shifts_picker_without_navigation()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $start = Jalalian::fromCarbon(Carbon::create(2035, 7, 23, 9))->format('Y-m-d');
        $nextMonth = Jalalian::fromFormat('Y-m-d', $start)->addMonths(1)->format('Y-m-d');
        $nextYear = Jalalian::fromFormat('Y-m-d', $start)->addMonths(12)->format('Y-m-d');

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Calendar::class)
            ->set('navigationDate', $start)
            ->set('selectedDate', $start)
            ->set('miniMonthDate', $start)
            ->call('stepMiniMonth', 1)
            ->assertSet('miniMonthDate', $nextMonth)
            ->assertSet('navigationDate', $start)
            ->assertSet('selectedDate', $start)
            ->set('miniMonthDate', $start)
            ->call('stepMiniMonth', 12)
            ->assertSet('miniMonthDate', $nextYear)
            ->assertSet('navigationDate', $start);
    }

    public function test_calendar_month_days_counts_active_reminder_and_marks_has_events()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $dueDate = Carbon::create(2099, 7, 23, 10);
        Reminder::factory()->create(['user_id' => $user->id, 'due_at' => $dueDate]);
        $dateString = Jalalian::fromCarbon($dueDate)->format('Y-m-d');

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Calendar::class)
            ->set('navigationDate', $dateString);

        $day = collect($component->instance()->calendarDays)
            ->filter()
            ->firstWhere('date', $dateString);

        $this->assertNotNull($day);
        $this->assertTrue($day['hasEvents']);
        $this->assertSame(1, $day['eventCount']);
    }

    public function test_range_events_places_reminder_in_the_timed_grid_when_within_grid_hours()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $dueDate = Carbon::create(2099, 7, 23, 10);
        $reminder = Reminder::factory()->create(['user_id' => $user->id, 'due_at' => $dueDate, 'title' => 'یادآور تست']);
        $dateString = Jalalian::fromCarbon($dueDate)->format('Y-m-d');

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', $dateString)
            ->set('navigationDate', $dateString)
            ->call('toggleView', 'day');

        $rangeEvents = $component->instance()->rangeEvents;

        $pill = collect($rangeEvents['byDay'][$dateString] ?? [])
            ->firstWhere('id', 'reminder-' . $reminder->id);

        $this->assertNotNull($pill);
        $this->assertSame('reminder', $pill['type']);
        $this->assertTrue($pill['locked']);
        $this->assertSame('یادآور تست', $pill['title']);
        $this->assertSame('10:00', $pill['time']);
        $this->assertSame(600, $pill['start_minutes']);
        $this->assertSame(30, $pill['duration_minutes']);

        $allDayIds = collect($rangeEvents['allDayEntries'])->pluck('id');
        $this->assertFalse($allDayIds->contains('reminder-' . $reminder->id));
    }

    public function test_range_events_falls_back_to_all_day_strip_when_reminder_is_before_grid_start()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $dueDate = Carbon::create(2099, 7, 23, 5, 0);
        $reminder = Reminder::factory()->create(['user_id' => $user->id, 'due_at' => $dueDate, 'title' => 'یادآور زودهنگام']);
        $dateString = Jalalian::fromCarbon($dueDate)->format('Y-m-d');

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', $dateString)
            ->set('navigationDate', $dateString)
            ->call('toggleView', 'day');

        $rangeEvents = $component->instance()->rangeEvents;

        $entry = collect($rangeEvents['allDayEntries'])
            ->firstWhere('id', 'reminder-' . $reminder->id);

        $this->assertNotNull($entry);
        $this->assertSame('reminder', $entry['type']);
        $this->assertSame('05:00', $entry['time']);

        $byDayPill = collect($rangeEvents['byDay'][$dateString] ?? [])
            ->firstWhere('id', 'reminder-' . $reminder->id);
        $this->assertNull($byDayPill);
    }

    public function test_selected_day_events_include_active_reminder_read_only()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $dueDate = Carbon::create(2099, 7, 23, 14, 30);
        $reminder = Reminder::factory()->create(['user_id' => $user->id, 'due_at' => $dueDate, 'title' => 'تماس با مشتری']);
        $selectedDate = Jalalian::fromCarbon($dueDate)->format('Y-m-d');

        Livewire::withoutLazyLoading();
        $events = Livewire::actingAs($user)->test(Calendar::class)
            ->set('selectedDate', $selectedDate)
            ->instance()->selectedDayEvents;

        $entry = $events->firstWhere('id', 'reminder-' . $reminder->id);

        $this->assertNotNull($entry);
        $this->assertSame('reminder', $entry['type']);
        $this->assertFalse($entry['is_owner']);
        $this->assertSame('14:30', $entry['time']);
    }

    public function test_completed_or_other_user_reminder_is_excluded_from_calendar_surfaces()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $other = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $dueDate = Carbon::create(2099, 7, 23, 10);
        $dateString = Jalalian::fromCarbon($dueDate)->format('Y-m-d');

        $completed = Reminder::factory()->completed()->create(['user_id' => $user->id, 'due_at' => $dueDate]);
        $othersReminder = Reminder::factory()->create(['user_id' => $other->id, 'due_at' => $dueDate]);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Calendar::class)
            ->set('navigationDate', $dateString)
            ->set('selectedDate', $dateString);

        $day = collect($component->instance()->calendarDays)
            ->filter()
            ->firstWhere('date', $dateString);

        $this->assertNotNull($day);
        $this->assertFalse($day['hasEvents']);
        $this->assertSame(0, $day['eventCount']);

        $allDayIds = collect($component->instance()->rangeEvents['allDayEntries'])->pluck('id');
        $this->assertFalse($allDayIds->contains('reminder-' . $completed->id));
        $this->assertFalse($allDayIds->contains('reminder-' . $othersReminder->id));

        $selectedIds = $component->instance()->selectedDayEvents->pluck('id');
        $this->assertFalse($selectedIds->contains('reminder-' . $completed->id));
        $this->assertFalse($selectedIds->contains('reminder-' . $othersReminder->id));
    }

    public function test_snoozed_reminder_still_appears_on_calendar_marked_with_hours_remaining()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $dueDate = Carbon::create(2099, 7, 23, 10);
        $dateString = Jalalian::fromCarbon($dueDate)->format('Y-m-d');

        $snoozed = Reminder::factory()->create([
            'user_id' => $user->id,
            'due_at' => $dueDate,
            'snoozed_until' => now()->addHours(3),
        ]);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Calendar::class)
            ->set('navigationDate', $dateString)
            ->set('selectedDate', $dateString);

        $day = collect($component->instance()->calendarDays)
            ->filter()
            ->firstWhere('date', $dateString);

        $this->assertNotNull($day);
        $this->assertTrue($day['hasEvents']);
        $this->assertSame(1, $day['eventCount']);

        $pill = collect($component->instance()->rangeEvents['byDay'][$dateString] ?? [])
            ->firstWhere('id', 'reminder-' . $snoozed->id);
        $this->assertNotNull($pill);
        $this->assertTrue($pill['is_snoozed']);
        $this->assertSame(3, $pill['snoozed_hours']);

        $selectedEntry = $component->instance()->selectedDayEvents->firstWhere('id', 'reminder-' . $snoozed->id);
        $this->assertNotNull($selectedEntry);
        $this->assertTrue($selectedEntry['is_snoozed']);
        $this->assertSame(3, $selectedEntry['snoozed_hours']);
    }
}
