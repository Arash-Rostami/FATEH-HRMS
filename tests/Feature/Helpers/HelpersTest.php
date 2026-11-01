<?php

namespace Tests\Feature\Helpers;

use App\Enums\PresenceStatus;
use App\Models\Permission;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;
use Tests\TestCase;

class HelpersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Auth::logout();
        DB::rollBack();
        Cache::flush();
        parent::tearDown();
    }

    private function useMysql(): void
    {
        $env = base_path('.env');
        if (is_file($env)) {
            $vals = [];
            foreach (explode("\n", (string)file_get_contents($env)) as $line) {
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

    public function test_convert_to_persian_maps_digits_only(): void
    {
        $this->assertSame('۱۲۳', convertToPersian('123'));
        $this->assertSame('abc ۴۵۶ def', convertToPersian('abc 456 def'));
        $this->assertSame('no digits here', convertToPersian('no digits here'));
    }

    public function test_convert_to_persian_blank_returns_null(): void
    {
        $this->assertNull(convertToPersian(null));
        $this->assertNull(convertToPersian(''));
        $this->assertNull(convertToPersian('   '));
    }

    public function test_strip_html_strips_tags(): void
    {
        $this->assertSame('hello world', stripHtml('<p>hello <b>world</b></p>'));
    }

    public function test_strip_html_decodes_nbsp_to_space_not_literal_entity(): void
    {
        $out = stripHtml('<p>hello&nbsp;world</p>');

        $this->assertStringNotContainsString('&nbsp;', $out);
        $this->assertStringNotContainsString("\u{a0}", $out);
        $this->assertSame('hello world', $out);
    }

    public function test_strip_html_collapses_whitespace_and_trims(): void
    {
        $this->assertSame('a b c', stripHtml("  <p>a   b\n\nc</p>  "));
    }

    public function test_strip_html_preserves_simple_emoji(): void
    {
        $this->assertSame('hi ☕️ there', stripHtml('<p>hi ☕️ there</p>'));
    }

    public function test_strip_html_preserves_zwj_emoji_sequence(): void
    {
        $this->assertSame('👨‍👩‍👧 family', stripHtml('<p>👨‍👩‍👧 family</p>'));
    }

    public function test_strip_html_preserves_persian_text(): void
    {
        $this->assertSame('سلام دنیا', stripHtml('<p>سلام&nbsp;دنیا</p>'));
    }

    public function test_strip_html_blank_returns_empty_string(): void
    {
        $this->assertSame('', stripHtml(null));
        $this->assertSame('', stripHtml(''));
        $this->assertSame('', stripHtml('   '));
    }

    public function test_super_clean_strips_tags_and_decodes_entities(): void
    {
        $this->assertSame('hello world', superClean('<p>hello <b>world</b></p>'));
    }

    public function test_super_clean_decodes_nbsp_to_space_not_literal_entity(): void
    {
        $out = superClean('<p>hello&nbsp;world</p>');

        $this->assertStringNotContainsString('&nbsp;', $out);
        $this->assertSame('hello world', $out);
    }

    public function test_super_clean_preserves_emoji(): void
    {
        $this->assertSame('hi ☕️ there', superClean('<p>hi ☕️ there</p>'));
    }

    public function test_super_clean_preserves_zwj_emoji_sequence(): void
    {
        $this->assertSame('👨‍👩‍👧 family', superClean('<p>👨‍👩‍👧 family</p>'));
    }

    public function test_super_clean_collapses_newlines_when_nl2br_off(): void
    {
        $this->assertSame('line1 line2', superClean("line1\nline2"));
    }

    public function test_super_clean_nl2br_preserves_newlines_as_br(): void
    {
        $this->assertSame("line1<br>\nline2", superClean("line1\nline2", 100, true));
    }

    public function test_super_clean_honors_limit(): void
    {
        $this->assertSame('abcde...', superClean('abcdefghijklmnopqrstuvwxyz', 5));
    }

    public function test_super_clean_blank_returns_empty_string(): void
    {
        $this->assertSame('', superClean(null));
        $this->assertSame('', superClean(''));
        $this->assertSame('', superClean('   '));
    }

    public function test_render_comment_double_star_wraps_to_strong(): void
    {
        $this->assertSame('<strong>bold</strong>', renderComment('**bold**'));
    }

    public function test_render_comment_mixed_bold_and_plain(): void
    {
        $this->assertSame('hello <strong>bold</strong> world', renderComment('hello **bold** world'));
    }

    public function test_render_comment_multiple_bold_segments(): void
    {
        $this->assertSame('<strong>a</strong> and <strong>b</strong>', renderComment('**a** and **b**'));
    }

    public function test_render_comment_newlines_become_br(): void
    {
        $this->assertSame("line1<br>\nline2", renderComment("line1\nline2"));
    }

    public function test_render_comment_bold_and_newline_together(): void
    {
        $this->assertSame("<strong>a</strong><br>\n<strong>b</strong>", renderComment("**a**\n**b**"));
    }

    public function test_render_comment_script_tag_is_stripped_entirely(): void
    {
        $out = renderComment('<script>alert(1)</script>');
        $this->assertStringNotContainsString('<script', $out);
        $this->assertStringNotContainsString('alert(1)', $out);
    }

    public function test_render_comment_dangerous_attribute_is_stripped_but_safe_element_kept(): void
    {
        $out = renderComment('**<img src=x onerror=alert(1)>**');
        $this->assertStringNotContainsString('onerror', $out);
        $this->assertStringNotContainsString('alert(1)', $out);
        $this->assertStringContainsString('<strong>', $out);
        $this->assertStringContainsString('<img', $out);
    }

    public function test_render_comment_admin_rich_editor_color_mark_is_preserved(): void
    {
        $out = renderComment('<p><span data-color="success" class="color" style="--color: #22c55e; --dark-color: #4ade80">hello</span></p>');
        $this->assertStringContainsString('data-color="success"', $out);
        $this->assertStringContainsString('class="color"', $out);
        $this->assertStringContainsString('hello', $out);
    }

    public function test_render_comment_unterminated_stars_are_left_literal(): void
    {
        $this->assertSame('a ** not bold', renderComment('a ** not bold'));
    }

    public function test_render_comment_blank_returns_empty_string(): void
    {
        $this->assertSame('', renderComment(null));
        $this->assertSame('', renderComment(''));
        $this->assertSame('', renderComment('   '));
    }

    public function test_jdate_returns_a_jalalian_instance_for_no_argument(): void
    {
        $this->assertInstanceOf(Jalalian::class, jdate());
    }

    public function test_is_past_handles_blank_past_future_and_invalid(): void
    {
        $this->assertFalse(isPast(null));
        $this->assertTrue(isPast('2000-01-01'));
        $this->assertFalse(isPast('2099-12-31 23:59'));
        $this->assertFalse(isPast('not-a-date'));
    }

    public function test_app_update_is_recent_true_within_default_window(): void
    {
        config(['app.last_update' => Carbon::now()->subDays(3)->toDateTimeString()]);

        $this->assertTrue(appUpdateIsRecent());
    }

    public function test_app_update_is_recent_false_older_than_default_window(): void
    {
        config(['app.last_update' => Carbon::now()->subDays(10)->toDateTimeString()]);

        $this->assertFalse(appUpdateIsRecent());
    }

    public function test_app_update_is_recent_false_for_unparseable_date_string(): void
    {
        config(['app.last_update' => 'Sep, 12 2026']);

        $this->assertFalse(appUpdateIsRecent());
    }

    public function test_app_update_is_recent_respects_custom_days_argument(): void
    {
        config(['app.last_update' => Carbon::now()->subDays(3)->toDateTimeString()]);

        $this->assertFalse(appUpdateIsRecent(1));
        $this->assertTrue(appUpdateIsRecent(5));
    }

    public function test_app_update_is_recent_boundary_exactly_days_ago_returns_true(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-19 12:00:00'));
        config(['app.last_update' => Carbon::now()->subDays(7)->toDateTimeString()]);

        $this->assertTrue(appUpdateIsRecent(7));

        Carbon::setTestNow();
    }

    public function test_presence_passes_enum_resolves_string_defaults_to_onsite(): void
    {
        $this->assertSame(PresenceStatus::Remote, presence(PresenceStatus::Remote));
        $this->assertSame(PresenceStatus::Remote, presence('remote'));
        $this->assertSame(PresenceStatus::Leave, presence('leave'));
        $this->assertSame(PresenceStatus::Onsite, presence('bogus'));
        $this->assertSame(PresenceStatus::Onsite, presence(null));
    }

    public function test_get_event_styles_returns_a_class_string_per_type(): void
    {
        $this->assertSame('bg-pink-50 text-pink-600 ring-1 ring-pink-100', getEventStyles('birthday'));
        $this->assertSame('bg-amber-50 text-amber-600 ring-1 ring-amber-100', getEventStyles('anniversary'));
        $this->assertSame('bg-rose-50 text-rose-600 ring-1 ring-rose-100', getEventStyles('holiday'));
        $this->assertSame(
            'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
            getEventStyles('anything-else')
        );
    }

    public function test_format_jalali_date_blank_returns_empty_and_invalid_falls_back_to_persian_input(): void
    {
        $this->assertSame('', formatJalaliDate(null));
        $this->assertSame('', formatJalaliDate(''));
        $this->assertSame('not-a-date', formatJalaliDate('not-a-date'));
    }

    public function test_format_jalali_date_formats_a_valid_jalali_date_with_persian_digits(): void
    {
        $this->assertStringContainsString('۱۵', formatJalaliDate('1403-05-15'));
    }

    public function test_is_video_detects_video_extensions_through_query_and_fragment(): void
    {
        $this->assertTrue(isVideo('https://site.com/clips/v.mp4'));
        $this->assertTrue(isVideo('https://site.com/clips/v.mp4?token=abc'));
        $this->assertTrue(isVideo('https://site.com/clips/v.mp4#t=1'));
        $this->assertTrue(isVideo('https://site.com/clips/v.WEBM'));
        $this->assertFalse(isVideo('https://site.com/docs/report.pdf'));
        $this->assertFalse(isVideo(null));
        $this->assertFalse(isVideo(''));
    }

    public function test_get_file_extension_lowercases_extension_and_handles_missing(): void
    {
        $this->assertSame('png', getFileExtension('a/b/c.PNG'));
        $this->assertSame('', getFileExtension('noext'));
        $this->assertSame('', getFileExtension(null));
    }

    public function test_to_jalali_blank_returns_empty_and_invalid_returns_empty(): void
    {
        $this->assertSame('', toJalali(null));
        $this->assertSame('', toJalali(''));
        $this->assertSame('', toJalali('not-a-date'));
    }

    public function test_to_jalali_passes_through_jalali_range_string_with_persian_digits(): void
    {
        $this->assertSame('۱۴۰۳-۰۵-۱۵ ۱۰:۳۰', toJalali('1403-05-15 10:30'));
    }

    public function test_to_jalali_normalizes_utc_offset_strings_to_app_timezone(): void
    {
        $utc = '2024-01-15T20:30:00.000000Z';
        $local = Carbon::parse($utc)->setTimezone(config('app.timezone'));

        $this->assertSame(toJalali($local, 'H:i'), toJalali($utc, 'H:i'));
    }

    public function test_to_jalali_converts_carbon_instance_and_gregorian_string(): void
    {
        $carbon = Carbon::parse('2024-01-15');
        $this->assertSame(toJalali($carbon, 'Y/m/d'), toJalali($carbon, 'Y/m/d'));

        $out = toJalali('2024-01-15 10:30');
        $this->assertNotSame('', $out);
        $this->assertStringContainsString('/', $out);
        $this->assertStringContainsString(':', $out);
    }

    public function test_to_jalali_smart_blank_returns_dash_and_omits_time_at_midnight(): void
    {
        $this->assertSame('—', toJalaliSmart(null));
        $this->assertSame('—', toJalaliSmart(''));

        $midnight = Carbon::parse('2024-01-15 00:00');
        $this->assertSame(toJalali($midnight, 'Y/m/d'), toJalaliSmart($midnight));

        $withTime = Carbon::parse('2024-01-15 10:30');
        $this->assertSame(toJalali($withTime, 'Y/m/d H:i'), toJalaliSmart($withTime));
    }

    public function test_to_jalali_relative_buckets_by_elapsed_time(): void
    {
        $this->assertSame('همین الان', toJalaliRelative(Carbon::now()));
        $this->assertSame('الان', toJalaliRelative(Carbon::now(), true));
        $this->assertSame('۵ دقیقه پیش', toJalaliRelative(Carbon::now()->subMinutes(5)));
        $this->assertSame('۲ ساعت پیش', toJalaliRelative(Carbon::now()->subHours(2)));
        $this->assertSame('۳ روز پیش', toJalaliRelative(Carbon::now()->subDays(3)));
        $this->assertSame('۲ هفته پیش', toJalaliRelative(Carbon::now()->subDays(20)));

        $old = Carbon::now()->subDays(40);
        $this->assertSame(toJalali($old, 'Y/m/d'), toJalaliRelative($old));
        $this->assertSame('۲ ماه پیش', toJalaliRelative(Carbon::now()->subDays(75), false, 0));
        $this->assertSame('۲ سال پیش', toJalaliRelative(Carbon::now()->subDays(800), false, 0));
        $this->assertSame(toJalali(Carbon::now()->subDays(75), 'Y/m/d'), toJalaliRelative(Carbon::now()->subDays(75)));
    }

    public function test_j_now_returns_current_jalali_calendar_part(): void
    {
        $this->assertIsInt(jNow());
        $this->assertGreaterThanOrEqual(1300, jNow());
        $this->assertLessThanOrEqual(1500, jNow());
        $this->assertGreaterThanOrEqual(1, jNow('month'));
        $this->assertLessThanOrEqual(12, jNow('month'));
        $this->assertGreaterThanOrEqual(1, jNow('day'));
        $this->assertLessThanOrEqual(31, jNow('day'));
    }

    public function test_quotes_returns_a_non_empty_array_of_strings(): void
    {
        $quotes = quotes();

        $this->assertNotEmpty($quotes);
        $this->assertContainsOnly('string', $quotes);
    }

    public function test_short_greeting_interpolates_the_given_name(): void
    {
        $this->assertStringContainsString('علی', shortGreeting('علی'));
    }

    public function test_greeting_returns_a_non_empty_string(): void
    {
        $this->assertNotEmpty(greeting('علی'));
    }

    public function test_can_admin_returns_false_without_an_authenticated_user(): void
    {
        $this->assertFalse(canAdmin());
    }

    public function test_can_admin_returns_true_for_a_developer(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'developer']);
        Auth::login($user);

        $this->assertTrue(canAdmin());
    }

    public function test_can_admin_returns_false_for_a_regular_user_without_permissions(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Auth::login($user);

        $this->assertFalse(canAdmin());
    }

    public function test_can_admin_returns_true_for_a_super_admin_permission_row(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Permission::factory()->create([
            'user_id' => $user->id,
            'is_super_admin' => true,
            'abilities' => null,
        ]);
        Auth::login($user);

        $this->assertTrue(canAdmin());
    }

    public function test_is_special_day_returns_false_without_an_authenticated_user(): void
    {
        $this->assertFalse(isSpecialDay('birthdate'));
    }

    public function test_is_special_day_true_when_birthdate_matches_today(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'birthdate' => today()]);
        Auth::login($user);

        $this->assertTrue(isSpecialDay('birthdate'));
    }

    public function test_is_special_day_false_when_cooldown_flag_is_set(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'birthdate' => today()]);
        Cache::put('birthdate_' . $user->id, true, now()->addHour());
        Auth::login($user);

        $this->assertFalse(isSpecialDay('birthdate'));
    }

    public function test_is_special_day_false_for_an_unknown_type(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'birthdate' => today()]);
        Auth::login($user);

        $this->assertFalse(isSpecialDay('whatever'));
    }

    public function test_tenant_asset_resolves_role_file_regardless_of_extension(): void
    {
        $this->assertSame('build/assets/img/fateh/logo.png', tenantAsset('fateh', 'img', 'logo', 'fallback'));
        $this->assertSame('build/assets/img/persol/logo.png', tenantAsset('persol', 'img', 'logo', 'fallback'));
    }

    public function test_tenant_asset_falls_back_when_role_file_is_missing(): void
    {
        $this->assertSame('fallback-value', tenantAsset('fateh', 'img', 'no-such-role', 'fallback-value'));
        $this->assertNull(tenantAsset('fateh', 'img', 'no-such-role'));
    }

    public function test_tenant_asset_falls_back_for_an_unknown_tenant_slug(): void
    {
        $this->assertSame('fallback-value', tenantAsset('does-not-exist', 'img', 'logo', 'fallback-value'));
    }

    public function test_tenant_asset_ignores_an_extra_subfolder(): void
    {
        $this->assertSame('build/assets/img/persol/dark.png', tenantAsset('persol', 'img', 'dark', 'fallback'));
    }

    public function test_tenant_videos_returns_numerically_sorted_paths(): void
    {
        foreach (['fateh', 'persol'] as $tenant) {
            $expectedCount = count(glob(resource_path("assets/video/{$tenant}/*.*")) ?: []);
            $videos = tenantVideos($tenant);

            $this->assertCount($expectedCount, $videos, "expected {$expectedCount} videos for tenant {$tenant}");
            if ($expectedCount === 0) {
                continue;
            }
            $this->assertSame("build/assets/video/{$tenant}/1.mp4", $videos[0]);
            $this->assertSame("build/assets/video/{$tenant}/{$expectedCount}.mp4", $videos[$expectedCount - 1]);
        }
    }

    public function test_tenant_videos_returns_empty_array_for_an_unknown_tenant_slug(): void
    {
        $this->assertSame([], tenantVideos('does-not-exist'));
    }

    public function test_tenant_backdrops_returns_alphabetically_sorted_paths(): void
    {
        foreach (['fateh', 'persol'] as $tenant) {
            $files = glob(resource_path("assets/img/{$tenant}/bg/*.*")) ?: [];
            natsort($files);
            $expected = array_map(
                fn(string $file): string => "build/assets/img/{$tenant}/bg/" . basename($file),
                array_values($files)
            );

            $this->assertSame($expected, tenantBackdrops($tenant));
        }
    }

    public function test_tenant_backdrops_returns_empty_array_for_an_unknown_tenant_slug(): void
    {
        $this->assertSame([], tenantBackdrops('does-not-exist'));
    }

    public function test_tenant_logo_maps_dark_and_light_normally_when_not_reversed(): void
    {
        config([
            'app.user_reverse_logo' => false,
            'app.app_logo_dark' => 'dark.png',
            'app.app_logo_light' => 'light.png',
        ]);

        $this->assertSame('dark.png', tenantLogo(true));
        $this->assertSame('light.png', tenantLogo(false));
    }

    public function test_tenant_logo_swaps_dark_and_light_when_reversed(): void
    {
        config([
            'app.user_reverse_logo' => true,
            'app.app_logo_dark' => 'dark.png',
            'app.app_logo_light' => 'light.png',
        ]);

        $this->assertSame('light.png', tenantLogo(true));
        $this->assertSame('dark.png', tenantLogo(false));
    }

    public function test_tenant_logo_returns_company_logo_when_scope_uses_it(): void
    {
        config([
            'app.admin_use_company_logo' => true,
            'app.company_logo' => 'company.png',
        ]);

        $this->assertSame('company.png', tenantLogo(true, 'admin'));
        $this->assertSame('company.png', tenantLogo(false, 'admin'));
    }

    public function test_tenant_logo_admin_scope_reverses_normally_but_auth_context_uses_its_own_flag(): void
    {
        config([
            'app.admin_use_company_logo' => false,
            'app.admin_reverse_logo' => true,
            'app.admin_auth_reverse_logo' => false,
            'app.app_logo_dark' => 'dark.png',
            'app.app_logo_light' => 'light.png',
        ]);

        $this->assertSame('light.png', tenantLogo(true, 'admin'));
        $this->assertSame('dark.png', tenantLogo(false, 'admin'));

        $this->assertSame('dark.png', tenantLogo(true, 'admin', true));
        $this->assertSame('light.png', tenantLogo(false, 'admin', true));
    }

    public function test_tenant_logo_admin_scope_no_swap_when_not_reversed(): void
    {
        config([
            'app.admin_use_company_logo' => false,
            'app.admin_reverse_logo' => false,
            'app.app_logo_dark' => 'dark.png',
            'app.app_logo_light' => 'light.png',
        ]);

        $this->assertSame('dark.png', tenantLogo(true, 'admin'));
        $this->assertSame('light.png', tenantLogo(false, 'admin'));
    }

    public function test_confirm_dialog_builds_the_open_confirmation_dispatch_payload(): void
    {
        $this->assertSame(
            "\$dispatch('open-confirmation', {\"title\":\"حذف وظیفه\",\"message\":\"مطمئن هستید؟\",\"method\":\"deleteTask\",\"params\":5,\"type\":\"dispatch\"})",
            confirmDialog('deleteTask', 'حذف وظیفه', 'مطمئن هستید؟', 5)
        );
    }

    public function test_confirm_dialog_omits_the_params_key_when_null_and_honors_a_custom_type(): void
    {
        $this->assertSame(
            "\$dispatch('open-confirmation', {\"title\":\"حذف نظر\",\"message\":\"مطمئن؟\",\"method\":\"delete-comment-confirmed\",\"type\":\"js\"})",
            confirmDialog('delete-comment-confirmed', 'حذف نظر', 'مطمئن؟', null, 'js')
        );
    }

    public function test_confirm_dialog_omits_the_type_key_when_null_so_the_modal_keeps_its_livewire_default(): void
    {
        $js = confirmDialog('confirmAction', 'حذف تصویر پروفایل', 'مطمئن؟', 'confirm-delete-profile-image', null);

        $this->assertStringNotContainsString('type', $js);
        $this->assertStringContainsString('"params":"confirm-delete-profile-image"', $js);
    }

    public function test_confirm_dialog_omits_the_tone_key_unless_given_and_keeps_the_argument_order(): void
    {
        $this->assertStringNotContainsString('tone', confirmDialog('deleteTask', 'حذف وظیفه', 'مطمئن؟', 5));

        $js = confirmDialog('deleteTask', 'حذف وظیفه', 'مطمئن؟', 5, 'dispatch', 'danger');
        $payload = json_decode(substr($js, strlen("\$dispatch('open-confirmation', "), -1), true);

        $this->assertSame(['title' => 'حذف وظیفه', 'message' => 'مطمئن؟', 'method' => 'deleteTask', 'params' => 5, 'type' => 'dispatch', 'tone' => 'danger'], $payload);

        $noType = confirmDialog('confirmAction', 'حذف', 'مطمئن؟', 'x', null, 'danger');
        $this->assertStringNotContainsString('"type"', $noType);
        $this->assertStringContainsString('"tone":"danger"', $noType);
    }

    public function test_confirmation_component_renders_dialog_semantics_and_the_danger_tone_hooks(): void
    {
        $html = (string) $this->blade('<x-ui.modals.confirmation />');

        $this->assertStringContainsString('role="dialog"', $html);
        $this->assertStringContainsString('aria-modal="true"', $html);
        $this->assertStringContainsString('aria-labelledby="confirmation-title"', $html);
        $this->assertSame(1, substr_count($html, 'id="confirmation-title"'));
        $this->assertStringContainsString("tone = payload.tone || ''", $html);
        $this->assertSame(2, substr_count($html, "tone === 'danger' ? 'bg-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-error)]' : 'bg-[var(--md-sys-color-primary)]"));
        $this->assertStringContainsString("tone === 'danger' ? 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]' : 'bg-[var(--md-sys-color-primary-container)]", $html);
        $this->assertSame(3, preg_match_all("/:class=\"tone === 'danger' \\?/", $html));
        $this->assertStringContainsString("tone === 'danger' ? 'warning' : 'help'", $html);
        $this->assertStringContainsString('setTimeout(() => active = show, 50)', $html);
        $this->assertStringNotContainsString('active = true', $html);
        $this->assertStringContainsString('x-trap="active"', $html);
        $this->assertStringNotContainsString('noautofocus', $html);
    }

    public function test_confirm_dialog_escapes_a_quoted_title_and_emits_valid_payload_json(): void
    {
        $js = confirmDialog('deleteTask', 'عنوان "نقل‌قول" است', 'مطمئن؟', 7);

        $this->assertStringContainsString('\"نقل‌قول\"', $js);
        $payload = substr($js, strlen("\$dispatch('open-confirmation', "), -1);
        $this->assertSame(
            ['title' => 'عنوان "نقل‌قول" است', 'message' => 'مطمئن؟', 'method' => 'deleteTask', 'params' => 7, 'type' => 'dispatch'],
            json_decode($payload, true)
        );
    }

    public function test_confirm_dialog_listener_routes_each_method_with_and_without_params(): void
    {
        $this->assertSame(
            "if (\$event.detail.method === 'deleteTask') \$wire.call('deleteTask', \$event.detail.params); if (\$event.detail.method === 'closeTicket') \$wire.call('closeTicket')",
            confirmDialogListener(['deleteTask' => true, 'closeTicket' => false])
        );
    }

    public function test_tenant_logo_user_scope_auth_context_uses_its_own_flag_independent_of_non_auth(): void
    {
        config([
            'app.user_reverse_logo' => false,
            'app.user_auth_reverse_logo' => true,
            'app.app_logo_dark' => 'dark.png',
            'app.app_logo_light' => 'light.png',
        ]);

        $this->assertSame('dark.png', tenantLogo(true));
        $this->assertSame('light.png', tenantLogo(false));

        $this->assertSame('light.png', tenantLogo(true, 'user', true));
        $this->assertSame('dark.png', tenantLogo(false, 'user', true));
    }

    public function test_tenant_off_matches_module_aliases_and_subview_prefixes_and_follows_config_changes(): void
    {
        config(['app.off' => ['announce', 'taskboard', 'reservation']]);

        $this->assertTrue(tenantOff('post'));
        $this->assertTrue(tenantOff('tasks'));
        $this->assertTrue(tenantOff('reservation:avantgarde'));
        $this->assertFalse(tenantOff('status'));

        config(['app.off' => ['status:chart']]);

        $this->assertTrue(tenantOff('status:chart'));
        $this->assertFalse(tenantOff('status'));
        $this->assertFalse(tenantOff('post'));

        config(['app.off' => []]);

        $this->assertFalse(tenantOff('reservation'));
    }

    public function test_tenant_off_name_matches_the_module_slot_only(): void
    {
        config(['app.off' => ['reservation', 'ads', 'gallery', 'faq', 'skills', 'status', 'profile']]);

        $this->assertTrue(tenantOffName('filament.admin.resources.reservations.index'));
        $this->assertTrue(tenantOffName('filament.admin.resources.reservation-policies.index'));
        $this->assertTrue(tenantOffName('filament.admin.resources.galleries.index'));
        $this->assertTrue(tenantOffName('filament.admin.resources.f-a-q-s.index'));
        $this->assertTrue(tenantOffName('dashboard.reservation.main'));
        $this->assertTrue(tenantOffName('dashboard.tab.faqs'));
        $this->assertTrue(tenantOffName('ads'));
        $this->assertTrue(tenantOffName('dashboard.profile.skills'));
        $this->assertFalse(tenantOffName('downloads.file'));
        $this->assertFalse(tenantOffName('filament.admin.auth.login'));
        $this->assertFalse(tenantOffName('user-profile-information.update'));
        $this->assertFalse(tenantOffName('livewire.update'));
        $this->assertFalse(tenantOffName('dashboard.navbar.status-switcher'));
        $this->assertFalse(tenantOffName('app.filament.widgets.module-analytics-widget'));
    }

    public function test_tenant_off_cascades_through_with_groups_one_way_only(): void
    {
        config(['app.off' => ['taskboard']]);

        $this->assertTrue(tenantOff('projects'));
        $this->assertTrue(tenantOff('tasksheet'));
        $this->assertTrue(tenantOffName('dashboard.project.kanban'));

        config(['app.off' => ['projects']]);

        $this->assertFalse(tenantOff('taskboard'));
    }
}