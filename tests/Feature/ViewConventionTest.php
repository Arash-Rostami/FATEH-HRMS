<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\DataProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

class ViewConventionTest extends TestCase
{
    private const ALLOWED_MD3_BTN = [
        'livewire/auth/forgot-password.blade.php',
        'livewire/auth/login.blade.php',
        'livewire/auth/logout-button.blade.php',
        'livewire/auth/register.blade.php',
        'livewire/auth/reset-password.blade.php',
        'livewire/auth/verify-notice.blade.php',
        'layouts/toCome.blade.php',
    ];

    private const ALLOWED_MD3_INPUT_GROUP = [
        'livewire/auth/forgot-password.blade.php',
        'livewire/auth/login.blade.php',
        'livewire/auth/register.blade.php',
        'livewire/auth/reset-password.blade.php',
        'livewire/dashboard/project/kanban.blade.php',
        'livewire/dashboard/taskboard/tools.blade.php',
    ];

    private const ALLOWED_ANIMATE_PULSE = [
        'layouts/auth.blade.php',
        'livewire/dashboard/ai-report/block-editor.blade.php',
        'livewire/dashboard/ai-report/composer.blade.php',
        'livewire/dashboard/contact/sidebar.blade.php',
        'livewire/dashboard/correspondence/composer.blade.php',
        'livewire/dashboard/dms/pdf-viewer.blade.php',
        'livewire/dashboard/dms/table.blade.php',
        'livewire/dashboard/energy/chart.blade.php',
        'livewire/dashboard/navbar/top/command-palette.blade.php',
        'livewire/dashboard/navbar/top/weather.blade.php',
        'livewire/dashboard/profile/documents.blade.php',
        'livewire/dashboard/profile/info.blade.php',
        'livewire/dashboard/profile/tickets.blade.php',
        'livewire/dashboard/project/activity.blade.php',
        'livewire/dashboard/project/report.blade.php',
        'livewire/dashboard/project/workflow/card.blade.php',
        'livewire/dashboard/reminder.blade.php',
        'livewire/dashboard/reservation/date.blade.php',
        'livewire/dashboard/tab/calendar/month.blade.php',
        'livewire/dashboard/tab/calendar/share.blade.php',
        'livewire/dashboard/tab/feeds/actions.blade.php',
        'livewire/dashboard/tab/feeds/comments.blade.php',
        'livewire/dashboard/tab/home.blade.php',
        'livewire/dashboard/tab/posts/pinned.blade.php',
        'livewire/dashboard/tab/status/occasions.blade.php',
        'livewire/dashboard/tab/status.blade.php',
        'livewire/dashboard/taskboard/card.blade.php',
        'livewire/dashboard/taskboard/meta-fields.blade.php',
        'livewire/dashboard/ths/ticket-table-row.blade.php',
    ];

    private const ALLOWED_NATIVE_CONFIRM = [
        'resources/js/components/alpine/stores/filament-menu.js' => 'Filament admin utilities page: the admin panel does not mount the user-panel shared confirmation modal',
    ];

    private const ALLOWED_LIGHTBOX_SHELL = [];

    private const ALLOWED_AVATAR_STACK = [];

    private const ALLOWED_MAXIMIZE_TOGGLE = [
        'livewire/dashboard/messaging/header-actions.blade.php',
        'livewire/dashboard/project/header.blade.php',
    ];

    private const ALLOWED_AI_SPARKLE = [
        'livewire/dashboard/ai-report/legend.blade.php',
        'livewire/dashboard/correspondence/composer.blade.php',
        'livewire/dashboard/tab/feeds/legend.blade.php',
        'components/dashboard/navbars/right.blade.php',
        'components/dashboard/modal/menu.blade.php',
        'components/dashboard/modal/menu/item.blade.php',
        'components/dashboard/ai-assistant.blade.php',
        'components/dashboard/navbars/top/ai-assistant.blade.php',
        'components/dashboard/ai-report.blade.php',
        'components/ui/buttons/form.blade.php',
        'components/ui/forms/ai-trigger.blade.php',
        'livewire/dashboard/ai-report.blade.php',
        'livewire/dashboard/launchpad.blade.php',
        'livewire/dashboard/channel/header.blade.php',
        'livewire/dashboard/channel/info.blade.php',
        'livewire/dashboard/messaging/message-actions.blade.php',
        'livewire/dashboard/messaging/feature-legend.blade.php',
    ];

    private const ALLOWED_SMART_SPARKLE = [
        'components/dashboard/navbars/bottom.blade.php',
        'livewire/dashboard/tab/home.blade.php',
        'livewire/dashboard/tab/status/filters.blade.php',
        'livewire/dashboard/tab/status/occasions.blade.php',
        'components/ui/modals/sms.blade.php',
    ];

    public static function smellProvider(): array
    {
        return [
            'raw md3-btn buttons bypass x-ui.buttons.form' => ['md3-btn', self::ALLOWED_MD3_BTN],
            'raw md3-input-group bypasses x-ui.forms.input' => ['md3-input-group', self::ALLOWED_MD3_INPUT_GROUP],
            'raw animate-pulse shimmers bypass x-ui.loaders.skeleton' => ['animate-pulse', self::ALLOWED_ANIMATE_PULSE],
            'hand-rolled lightbox shell bypasses x-ui.modals.lightbox' => ['min(96vw,1600px)', self::ALLOWED_LIGHTBOX_SHELL],
            'hand-rolled overlapping avatar stack bypasses x-ui.decor.avatar-stack' => ['-space-x-1.5 rtl:space-x-reverse', self::ALLOWED_AVATAR_STACK],
            'hand-rolled maximize toggle bypasses x-ui.modals.maximize-button' => [": 'open_in_full'", self::ALLOWED_MAXIMIZE_TOGGLE],
        ];
    }

    public function test_maximize_button_renders_the_lit_standard_without_border_or_ring(): void
    {
        $html = Blade::render('<x-ui.modals.maximize-button toggle="toggleMaximize(\'a\')" state="open === \'a\'" size="widget" label="الف" label-on="ب" alpine-class="headerBtnClass(false)" class="ml-auto" data-x="1" aria-describedby="d"/>');

        $this->assertStringContainsString('x-on:click="toggleMaximize(\'a\')"', $html);
        $this->assertStringContainsString('x-bind:title="open === \'a\' ? \'ب\' : \'الف\'"', $html);
        $this->assertStringContainsString("x-bind:class=\"[headerBtnClass(false), open === 'a' ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)]' : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)]']\"", $html);
        $this->assertStringContainsString("x-text=\"open === 'a' ? 'close_fullscreen' : 'open_in_full'\"", $html);
        $this->assertStringContainsString('size-8 rounded-lg', $html);
        $this->assertStringContainsString('ml-auto', $html);
        $this->assertStringContainsString('data-x="1"', $html);
        $this->assertStringContainsString('aria-describedby="d"', $html);
        $this->assertStringContainsString('focus:outline-none', $html);
        $this->assertDoesNotMatchRegularExpression('/(?<![\w-])(border|ring)(-|\s|")|focus:ring/', $html);

        $default = Blade::render('<x-ui.modals.maximize-button/>');
        $this->assertStringContainsString('x-on:click="toggleMaximize()"', $default);
        $this->assertStringContainsString("max ? 'کوچک کردن' : 'بزرگ کردن'", $default);
        $this->assertStringContainsString('h-8 w-8 rounded-xl', $default);
    }

    #[DataProvider('smellProvider')]
    public function test_no_component_family_bypass(string $needle, array $allowed): void
    {
        $offenders = [];
        foreach ($this->viewFiles() as $relativePath => $contents) {
            if (in_array($relativePath, $allowed, true)) {
                continue;
            }
            if (str_contains($contents, $needle)) {
                $offenders[] = $relativePath;
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "New digression from the design-system component family for '{$needle}':\n" . implode("\n", $offenders)
            . "\nAdopt the shared component instead, or add the file to this test's baseline with a justified reason.",
        );
    }

    public function test_confirmations_use_the_shared_modal_not_native_prompts_or_inline_yes_no_panels(): void
    {
        $offenders = [];
        foreach ($this->viewFiles(['resources/views/livewire', 'resources/views/layouts', 'resources/views/components']) as $relativePath => $contents) {
            if (str_contains($contents, 'wire:confirm') || preg_match('/(?<![\w.$-])confirm\(/', $contents) || preg_match('/x-show="!?confirming[A-Za-z]*"/', $contents)) {
                $offenders[] = 'resources/views/' . $relativePath;
            }
        }

        $scripts = array_merge(
            glob(base_path('resources/js/components/alpine/{data,stores,mixins}/*.js'), GLOB_BRACE) ?: [],
            glob(base_path('resources/js/core/*.js')) ?: [],
        );
        foreach ($scripts as $script) {
            if (preg_match('/(?<![\w.$-])confirm\(/', file_get_contents($script))) {
                $offenders[] = str_replace('\\', '/', substr($script, strlen(base_path()) + 1));
            }
        }

        $offenders = array_values(array_diff($offenders, array_keys(self::ALLOWED_NATIVE_CONFIRM)));
        sort($offenders);

        $this->assertSame(
            [],
            $offenders,
            "Confirmations go through the shared modal (viewPattern 'Confirmation modal'): confirmDialog()/confirmDialogListener() in Blade, an open-confirmation dispatch in PHP/JS. Native confirm(), wire:confirm and inline confirming* panels are forbidden outside ALLOWED_NATIVE_CONFIRM:\n" . implode("\n", $offenders),
        );
    }

    public function test_info_drawers_ignore_clicks_inside_the_fancybox_viewer(): void
    {
        foreach (['ths/modal/info', 'channel/info', 'contact/info'] as $view) {
            $contents = file_get_contents(resource_path("views/livewire/dashboard/{$view}.blade.php"));

            $this->assertMatchesRegularExpression('/click\.outside="[^"]*fancybox/', $contents, "{$view} must not close behind an open Fancybox viewer");
        }
    }

    public function test_ai_sparkle_icon_is_limited_to_ai_and_the_listed_smart_features(): void
    {
        $allowed = [...self::ALLOWED_AI_SPARKLE, ...self::ALLOWED_SMART_SPARKLE];
        $offenders = [];
        foreach ($this->viewFiles(['resources/views/livewire', 'resources/views/layouts', 'resources/views/components']) as $relativePath => $contents) {
            if (str_contains($contents, 'auto_awesome') && ! in_array($relativePath, $allowed, true)) {
                $offenders[] = $relativePath;
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "The auto_awesome sparkle is the app-wide AI signature, with 'smart feature' chips as the only second use (viewPattern §8.38); add the file to ALLOWED_AI_SPARKLE or ALLOWED_SMART_SPARKLE with a reason, or use another icon:\n" . implode("\n", $offenders),
        );
    }

    public function test_every_sparkle_use_carries_the_ai_colour_not_just_the_icon(): void
    {
        $files = iterator_to_array($this->viewFiles(['resources/views/livewire', 'resources/views/layouts', 'resources/views/components']));
        $missing = [];
        foreach ([...self::ALLOWED_AI_SPARKLE, ...self::ALLOWED_SMART_SPARKLE] as $path) {
            $contents = $files[$path] ?? null;
            if ($contents === null || str_contains($path, 'legend.blade.php')) {
                continue;
            }
            if (! str_contains($contents, '--ai-') && ! str_contains($contents, ':ai="true"') && ! str_contains($contents, 'variant="ai"')) {
                $missing[] = $path;
            }
        }

        $this->assertSame(
            [],
            $missing,
            "The AI sparkle and the AI colour travel together (viewPattern §8.38): use the --ai-* tokens (or variant=\"ai\" / :ai=\"true\") in:\n" . implode("\n", $missing),
        );
    }

    public function test_ai_variant_button_uses_the_ai_tokens_and_defaults_to_the_sparkle_icon(): void
    {
        $ai = Blade::render('<x-ui.buttons.form variant="ai">Go</x-ui.buttons.form>');
        $this->assertStringContainsString('bg-[var(--ai-bg)]', $ai);
        $this->assertStringContainsString('text-[var(--ai-text)]', $ai);
        $this->assertStringContainsString('auto_awesome', $ai);
        $this->assertStringContainsString('rounded-xl h-10 px-4 text-sm font-bold', $ai);
        $this->assertStringNotContainsString('md3-btn', $ai);

        $custom = Blade::render('<x-ui.buttons.form variant="ai" icon="send">Go</x-ui.buttons.form>');
        $this->assertStringContainsString('send', $custom);
        $this->assertStringNotContainsString('auto_awesome', $custom);

        $plain = Blade::render('<x-ui.buttons.form>Go</x-ui.buttons.form>');
        $this->assertStringNotContainsString('auto_awesome', $plain);
        $this->assertStringNotContainsString('--ai-', $plain);
    }

    public function test_ai_tokens_derive_from_the_theme_tertiary_once_and_no_theme_redefines_them(): void
    {
        $css = (string) file_get_contents(base_path('resources/css/core/theme.css'));

        $this->assertSame(1, substr_count($css, '--ai-color: color-mix(in srgb, var(--md-sys-color-tertiary), var(--md-sys-color-on-surface) var(--ai-dim));'));
        $this->assertSame(1, substr_count($css, '--ai-bg: color-mix(in oklab, var(--ai-color) 16%, var(--md-sys-color-surface));'));
        $this->assertSame(1, substr_count($css, '--ai-text: color-mix(in oklab, var(--ai-color) 22%, var(--md-sys-color-on-surface));'));
        $this->assertSame(3, preg_match_all('/^\s*--ai-(color|bg|text)\s*:/m', $css));
    }

    public function test_quota_meter_renders_its_progress_contract_and_optional_warning(): void
    {
        $html = Blade::render('<x-ui.quota-meter label="سهمیه" :used="2" :cap="6" :pct="33" :tone="$tone" aria="سهمیه روزانه" warn="نزدیک سقف" bar-key="quota-bar" warn-key="quota-warning">توضیح</x-ui.quota-meter>', [
            'tone' => ['text' => 'text-test', 'fill' => 'bg-test'],
        ]);

        $this->assertStringContainsString('role="progressbar"', $html);
        $this->assertStringContainsString('aria-valuenow="2"', $html);
        $this->assertStringContainsString('aria-valuemax="6"', $html);
        $this->assertStringContainsString('wire:key="quota-bar"', $html);
        $this->assertStringContainsString('wire:key="quota-warning"', $html);
        $this->assertStringContainsString('width: 33%', $html);
        $this->assertStringContainsString('نزدیک سقف', $html);
        $this->assertStringContainsString('توضیح', $html);
    }

    public function test_alpine_roots_with_their_own_init_method_do_not_call_it_a_second_time(): void
    {
        foreach ([
            'livewire/dashboard/contact.blade.php',
            'livewire/dashboard/channel.blade.php',
            'livewire/dashboard/analytics.blade.php',
            'components/dashboard/tools/calculator.blade.php',
            'components/dashboard/tools/stopwatch.blade.php',
            'components/dashboard/tools/radio.blade.php',
            'components/dashboard/modal/menu/player.blade.php',
        ] as $file) {
            $this->assertStringNotContainsString('x-init="init()"', file_get_contents(resource_path("views/{$file}")), $file);
        }
    }

    public function test_form_button_with_href_renders_an_anchor_and_maps_disabled_to_aria(): void
    {
        $html = Blade::render('<x-ui.buttons.form href="/correspondence/12/pdf" variant="secondary" disabled>دریافت PDF</x-ui.buttons.form>');

        $this->assertMatchesRegularExpression('/^\s*<a[\s>]/', $html);
        $this->assertStringContainsString('href="/correspondence/12/pdf"', $html);
        $this->assertStringContainsString('aria-disabled="true"', $html);
        $this->assertStringContainsString('tabindex="-1"', $html);
        $this->assertDoesNotMatchRegularExpression('/\sdisabled(?==|\s|>)/', $html); // no bare disabled attribute (Tailwind's disabled:* classes are fine)
        $this->assertStringNotContainsString('type="button"', $html);
        $this->assertStringContainsString('دریافت PDF', $html);
    }

    private function viewFiles(array $roots = ['resources/views/livewire', 'resources/views/layouts']): \Generator
    {
        foreach ($roots as $root) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(base_path($root)),
                RecursiveIteratorIterator::LEAVES_ONLY,
            );

            foreach ($iterator as $file) {
                if ($file instanceof SplFileInfo && str_ends_with($file->getFilename(), '.blade.php')) {
                    $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen(base_path('resources/views')))), '/');
                    yield $relative => file_get_contents($file->getPathname());
                }
            }
        }
    }
}
