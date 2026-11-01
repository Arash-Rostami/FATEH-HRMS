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
        'livewire/dashboard/contact/sidebar.blade.php',
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

    private const ALLOWED_LIGHTBOX_SHELL = [];

    private const ALLOWED_AVATAR_STACK = [];

    private const ALLOWED_MAXIMIZE_TOGGLE = [
        'livewire/dashboard/messaging/header-actions.blade.php',
        'livewire/dashboard/project/header.blade.php',
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

    private function viewFiles(): \Generator
    {
        foreach (['resources/views/livewire', 'resources/views/layouts'] as $root) {
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