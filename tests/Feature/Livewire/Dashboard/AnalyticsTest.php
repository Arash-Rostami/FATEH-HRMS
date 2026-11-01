<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Livewire\Dashboard\Analytics\Main;
use App\Livewire\Dashboard\Analytics\Presentation\AnalyticsPresenter;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    private const MODULE_KEYS = ['hr_a', 'hr_b', 'hr_c', 'hr_d', 'hr_e', 'hr_f', 'hr_g', 'hr_h', 'hr_i', 'hr_j', 'hr_k', 'hr_l', 'hr_m', 'hr_n', 'hr_o', 'hr_p', 'hr_q'];

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('fa');
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

    public function test_analytics_route_returns_lazy_placeholder_markup_for_authenticated_user(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->get('/analytics');

        $response->assertSuccessful();
        $response->assertSee('در حال بارگذاری تحلیل‌های سازمانی');
        $response->assertDontSee('analytics-chart-demographics', false);
    }

    public function test_guest_is_redirected_from_analytics_route(): void
    {
        $this->get('/analytics')->assertRedirect(route('login'));
    }

    private function presenter(): AnalyticsPresenter
    {
        return new AnalyticsPresenter();
    }

    public function test_chart_data_computed_returns_all_seventeen_module_keys_shaped_correctly(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::withoutLazyLoading();

        $component = Livewire::actingAs($user)->test(Main::class);
        $component->assertSuccessful();

        $chartData = $component->instance()->chartData;

        $this->assertSame(self::MODULE_KEYS, array_keys($chartData));

        foreach (self::MODULE_KEYS as $key) {
            $this->assertArrayHasKey('datasets', $chartData[$key]);
            $this->assertArrayHasKey('labels', $chartData[$key]);
            $this->assertIsArray($chartData[$key]['datasets']);
            $this->assertIsArray($chartData[$key]['labels']);
        }
    }

    public function test_full_render_shows_all_category_and_module_labels_once_lazy_loaded(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::withoutLazyLoading();

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSuccessful()
            ->assertSee('تحلیل‌های ترکیب جمعیتی و جایگاه')
            ->assertSee('تحلیل‌های صلاحیت، سابقه و تخصص')
            ->assertSee('تحلیل‌های سلامت واحدها')
            ->assertSee('تحلیل‌های حضور و مشارکت')
            ->assertSee('توزیع جایگاه سازمانی به تفکیک جنسیت');
    }

    public function test_chart_header_shows_freshness_caption(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::withoutLazyLoading();

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSuccessful()
            ->assertSee('بروزرسانی:');
    }

    public function test_chart_type_is_doughnut_only_for_hr_d_and_bar_otherwise(): void
    {
        $presenter = $this->presenter();

        $this->assertSame('doughnut', $presenter->chartType('hr_d'));

        foreach (array_diff(self::MODULE_KEYS, ['hr_d']) as $key) {
            $this->assertSame('bar', $presenter->chartType($key));
        }

        $this->assertSame('bar', $presenter->chartType('bogus'));
    }

    public function test_description_is_non_empty_and_distinct_per_module_key(): void
    {
        $presenter = $this->presenter();
        $seen = [];

        foreach (self::MODULE_KEYS as $key) {
            $description = $presenter->description($key);
            $this->assertNotSame('', $description);
            $this->assertArrayNotHasKey($description, $seen);
            $seen[$description] = true;
        }
    }

    public function test_description_falls_back_to_default_for_invalid_or_null_key(): void
    {
        $presenter = $this->presenter();
        $default = __('resources/dashboard/strings.chart_widgets.default_description');

        $this->assertSame($default, $presenter->description('bogus'));
        $this->assertSame($default, $presenter->description(''));

        foreach (self::MODULE_KEYS as $key) {
            $this->assertNotSame($default, $presenter->description($key));
        }
    }

    public function test_demographics_options_stack_only_hr_a_and_strip_scales_for_hr_d(): void
    {
        $presenter = $this->presenter();

        $optsD = $presenter->chartOptions('hr_d');
        $this->assertArrayNotHasKey('scales', $optsD);

        $optsA = $presenter->chartOptions('hr_a');
        $this->assertTrue($optsA['scales']['x']['stacked']);
        $this->assertTrue($optsA['scales']['y']['stacked']);

        foreach (['hr_b', 'hr_c'] as $key) {
            $opts = $presenter->chartOptions($key);
            $this->assertArrayHasKey('scales', $opts);
            $this->assertArrayNotHasKey('stacked', $opts['scales']['x']);
            $this->assertArrayNotHasKey('stacked', $opts['scales']['y']);
        }
    }

    public function test_qualification_options_stack_by_default_and_unstack_only_hr_f(): void
    {
        $presenter = $this->presenter();

        foreach (['hr_e', 'hr_g', 'hr_h'] as $key) {
            $opts = $presenter->chartOptions($key);
            $this->assertTrue($opts['scales']['x']['stacked']);
            $this->assertTrue($opts['scales']['y']['stacked']);
        }

        $optsF = $presenter->chartOptions('hr_f');
        $this->assertFalse($optsF['scales']['x']['stacked']);
        $this->assertFalse($optsF['scales']['y']['stacked']);
    }

    public function test_unit_health_options_stack_by_default_hr_i_flips_axis_hr_m_unstacks_with_max(): void
    {
        $presenter = $this->presenter();

        foreach (['hr_j', 'hr_k', 'hr_l'] as $key) {
            $opts = $presenter->chartOptions($key);
            $this->assertTrue($opts['scales']['x']['stacked']);
            $this->assertTrue($opts['scales']['y']['stacked']);
            $this->assertArrayNotHasKey('indexAxis', $opts);
        }

        $optsI = $presenter->chartOptions('hr_i');
        $this->assertSame('y', $optsI['indexAxis']);
        $this->assertTrue($optsI['scales']['x']['beginAtZero']);

        $optsM = $presenter->chartOptions('hr_m');
        $this->assertFalse($optsM['scales']['x']['stacked']);
        $this->assertFalse($optsM['scales']['y']['stacked']);
        $this->assertTrue($optsM['scales']['x']['beginAtZero']);
        $this->assertSame(100, $optsM['scales']['x']['max']);
    }

    public function test_engagement_options_stack_hr_n_and_unstack_hr_o_p_q_keeping_begin_at_zero(): void
    {
        $presenter = $this->presenter();

        $optsN = $presenter->chartOptions('hr_n');
        $this->assertTrue($optsN['scales']['x']['stacked']);
        $this->assertTrue($optsN['scales']['x']['beginAtZero']);
        $this->assertTrue($optsN['scales']['y']['stacked']);

        foreach (['hr_o', 'hr_p', 'hr_q'] as $key) {
            $opts = $presenter->chartOptions($key);
            $this->assertFalse($opts['scales']['x']['stacked']);
            $this->assertFalse($opts['scales']['y']['stacked']);
            $this->assertTrue($opts['scales']['x']['beginAtZero']);
        }
    }

    public function test_chart_config_shapes_all_seventeen_module_keys(): void
    {
        $config = $this->presenter()->chartConfig();

        $this->assertSame(self::MODULE_KEYS, array_keys($config));

        foreach ($config as $key => $entry) {
            $this->assertSame(['type', 'options', 'label', 'description'], array_keys($entry));
            $this->assertSame($this->presenter()->chartType($key), $entry['type']);
            $this->assertIsArray($entry['options']);
            $this->assertIsString($entry['label']);
            $this->assertNotSame('', $entry['label']);
            $this->assertSame($this->presenter()->description($key), $entry['description']);
        }
    }
}
