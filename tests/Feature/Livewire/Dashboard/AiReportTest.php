<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Filament\Resources\ReportResource;
use App\Filament\Resources\AiReportResource;
use App\Filament\Resources\AiReportResource\Pages\ListAiReports;
use App\Filament\Resources\AiReportResource\Pages\ViewAiReport;
use App\Filament\Resources\AiReportResource\Schemas\AiReportTablePresenter;
use App\Jobs\GenerateReportJob;
use App\Jobs\ReconcileNudgeJob;
use App\Livewire\Dashboard\AiReport\Actions\DeleteReportAction;
use App\Livewire\Dashboard\AiReport\Actions\GenerateReportAction;
use App\Livewire\Dashboard\AiReport\Actions\ShareReportAction;
use App\Livewire\Dashboard\AiReport\Actions\UpdateReportAction;
use App\Livewire\Dashboard\AiReport\Forms\ComposerForm;
use App\Livewire\Dashboard\AiReport\Main;
use App\Livewire\Dashboard\AiReport\Presentation\AiReportPresenter;
use App\Models\AiReport;
use App\Models\Department;
use App\Models\Permission;
use App\Models\Profile;
use App\Models\User;
use App\Services\Ai\AiClient;
use App\Services\AiReport\BlockCatalog;
use App\Services\AiReport\FileExtractor;
use App\Services\AiReport\ReportAiService;
use App\Services\Menu\BadgeLegendCatalog;
use App\Services\Menu\Indicators\UnseenAiReports;
use App\Services\Menu\Notifications\AiReportNudge;
use App\Services\Menu\NudgeService;
use App\Services\Menu\StateService;
use App\Support\AiReportAccessPolicy;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpClientFactory;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Livewire;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;
use ZipArchive;

class AiReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        config(['cache.default' => 'array']);
        Cache::flush();
        Livewire::withoutLazyLoading();
        app()->setLocale('fa');
        Storage::fake('local');
        config(['ai.gateway.key' => 'test-key', 'ai.gateway.url' => 'https://gateway.test/v1', 'ai.report.daily_cap' => 10]);
    }

    protected function tearDown(): void
    {
        Auth::logout();
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

    private function user(array $attributes = []): User
    {
        return User::factory()->create(['status' => 'active', 'role' => 'user', 'type' => 'employee', ...$attributes]);
    }

    private function report(User $owner, array $attributes = []): AiReport
    {
        return AiReport::create([
            'user_id' => $owner->id,
            'status' => AiReport::STATUS_READY,
            'active' => true,
            'saved_at' => now(),
            'spec' => ['lang' => 'fa', 'title' => 'گزارش', 'pages' => []],
            'versions' => [['prompt' => 'p', 'files' => [], 'spec' => ['lang' => 'fa'], 'at' => now()->toIso8601String()]],
            ...$attributes,
        ]);
    }

    public function test_mark_failed_refunds_the_quota_exactly_once(): void
    {
        $owner = $this->user();
        $report = $this->report($owner, ['status' => AiReport::STATUS_GENERATING, 'saved_at' => null]);
        AiReport::consumeQuota($owner->id);
        AiReport::consumeQuota($owner->id);
        Storage::disk('local')->put($report->tempDirectory() . '/a.txt', 'x');

        $this->assertTrue($report->markFailed('timeout'));
        $this->assertFalse($report->markFailed('timeout'));
        $this->assertFalse($report->markFailed('exception'));

        $this->assertSame(1, AiReport::quotaUsed($owner->id));
        $fresh = $report->fresh();
        $this->assertSame(AiReport::STATUS_FAILED, $fresh->status);
        $this->assertSame('timeout', $fresh->fail_reason);
        Storage::disk('local')->assertMissing($report->tempDirectory() . '/a.txt');
    }

    public function test_mark_failed_is_a_no_op_for_a_report_that_is_not_generating(): void
    {
        $owner = $this->user();
        $report = $this->report($owner);
        AiReport::consumeQuota($owner->id);

        $this->assertFalse($report->markFailed('timeout'));
        $this->assertSame(1, AiReport::quotaUsed($owner->id));
        $this->assertSame(AiReport::STATUS_READY, $report->fresh()->status);
    }

    public function test_refund_never_drops_the_counter_below_zero(): void
    {
        $owner = $this->user();

        AiReport::refundQuota($owner->id);
        $this->assertSame(0, AiReport::quotaUsed($owner->id));

        $this->assertSame(1, AiReport::consumeQuota($owner->id));
        $this->assertSame(2, AiReport::consumeQuota($owner->id));
        $this->assertSame(2, AiReport::quotaUsed($owner->id));
        $this->assertSame(10, AiReport::quotaCap());
        $this->assertStringEndsWith(now()->format('Y-m-d'), AiReport::quotaKey($owner->id));
    }

    public function test_push_version_appends_and_trims_the_oldest_beyond_ten(): void
    {
        $report = $this->report($this->user(), ['versions' => null]);

        foreach (range(1, 12) as $i) {
            $report->pushVersion(['prompt' => "p{$i}", 'files' => [], 'at' => now()->toIso8601String()]);
        }

        $this->assertCount(AiReport::maxVersions(), $report->versions);
        $this->assertSame('p3', $report->versions[0]['prompt']);
        $this->assertSame('p12', $report->versions[9]['prompt']);
    }

    public function test_last_spec_version_skips_entries_without_a_spec(): void
    {
        $report = $this->report($this->user(), ['versions' => [
            ['prompt' => 'a', 'spec' => ['n' => 1]],
            ['prompt' => 'b', 'spec' => ['n' => 2]],
            ['prompt' => 'c'],
        ]]);

        $this->assertSame(2, $report->lastSpecVersion()['spec']['n']);
        $this->assertNull($this->report($this->user(), ['versions' => [['prompt' => 'x']]])->lastSpecVersion());
    }

    public function test_is_stale_only_for_an_old_generating_report(): void
    {
        $owner = $this->user();
        $report = $this->report($owner, ['status' => AiReport::STATUS_GENERATING]);
        $this->assertFalse($report->isStale());

        $report->updated_at = now()->subMinutes(AiReport::staleMinutes() + 1);
        $this->assertTrue($report->isStale());

        $report->status = AiReport::STATUS_READY;
        $this->assertFalse($report->isStale());
    }

    public function test_scope_visible_to_covers_owner_ready_saved_recipients_and_hides_inactive(): void
    {
        $owner = $this->user();
        $viewer = $this->user();
        $stranger = $this->user();

        $mine = $this->report($viewer, ['saved_at' => null]);
        $shared = $this->report($owner, ['recipients' => [$viewer->id]]);
        $sharedDraft = $this->report($owner, ['recipients' => [$viewer->id], 'saved_at' => null]);
        $sharedFailed = $this->report($owner, ['recipients' => [$viewer->id], 'status' => AiReport::STATUS_FAILED]);
        $sharedInactive = $this->report($owner, ['recipients' => [$viewer->id], 'active' => false]);
        $mineInactive = $this->report($viewer, ['active' => false]);
        $unrelated = $this->report($owner, ['recipients' => [$stranger->id]]);

        $ids = AiReport::visibleTo($viewer)->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$mine->id, $shared->id], $ids);
        $this->assertNotContains($sharedDraft->id, $ids);
        $this->assertNotContains($sharedFailed->id, $ids);
        $this->assertNotContains($sharedInactive->id, $ids);
        $this->assertNotContains($mineInactive->id, $ids);
        $this->assertNotContains($unrelated->id, $ids);
        $this->assertEqualsCanonicalizing([$shared->id, $sharedInactive->id], AiReport::sharedWith($viewer->id)->pluck('id')->all());
    }

    public function test_view_policy_table(): void
    {
        $owner = $this->user(['status' => 'inactive']);
        $recipient = $this->user();
        $stranger = $this->user();
        $admin = $this->user(['role' => 'admin']);
        $report = $this->report($owner, ['recipients' => [$recipient->id]]);
        $draft = $this->report($owner, ['recipients' => [$recipient->id], 'saved_at' => null]);
        $inactive = $this->report($owner, ['recipients' => [$recipient->id], 'active' => false]);

        $this->assertTrue(AiReportAccessPolicy::canView($report, $owner));
        $this->assertTrue(AiReportAccessPolicy::canView($report, $recipient));
        $this->assertTrue(AiReportAccessPolicy::canView($report, $admin));
        $this->assertFalse(AiReportAccessPolicy::canView($report, $stranger));
        $this->assertFalse(AiReportAccessPolicy::canView($report, null));
        $this->assertFalse(AiReportAccessPolicy::canView($draft, $recipient));
        $this->assertTrue(AiReportAccessPolicy::canView($draft, $owner));
        $this->assertTrue(AiReportAccessPolicy::canView($inactive, $owner));
        $this->assertFalse(AiReportAccessPolicy::canView($inactive, $admin));
        $this->assertFalse(AiReportAccessPolicy::canView($inactive, $recipient));
        $this->assertTrue(AiReportAccessPolicy::canToggleActive($inactive, $owner));
        $this->assertFalse(AiReportAccessPolicy::canToggleActive($inactive, $recipient));
        $this->assertTrue(AiReportAccessPolicy::canToggleActive($report, $owner));
        $this->assertFalse(AiReportAccessPolicy::canToggleActive($report, null));
    }

    public function test_generate_policy_table(): void
    {
        $this->assertTrue(AiReportAccessPolicy::canGenerate($this->user()));
        $this->assertFalse(AiReportAccessPolicy::canGenerate($this->user(['type' => 'guest'])));
        $this->assertFalse(AiReportAccessPolicy::canGenerate($this->user(['status' => 'inactive'])));
        $this->assertFalse(AiReportAccessPolicy::canGenerate(null));

        config(['ai.gateway.key' => '']);
        $this->assertFalse(AiReportAccessPolicy::canGenerate($this->user()));
    }

    public function test_owner_verbs_policy_table(): void
    {
        $owner = $this->user();
        $other = $this->user();
        $ready = $this->report($owner);
        $failed = $this->report($owner, ['status' => AiReport::STATUS_FAILED]);
        $generating = $this->report($owner, ['status' => AiReport::STATUS_GENERATING]);
        $full = $this->report($owner, ['versions' => array_fill(0, AiReport::maxVersions(), ['prompt' => 'p'])]);

        foreach (['canSave', 'canShare', 'canRevert'] as $verb) {
            $this->assertTrue(AiReportAccessPolicy::$verb($ready, $owner), $verb);
            $this->assertFalse(AiReportAccessPolicy::$verb($failed, $owner), $verb);
            $this->assertFalse(AiReportAccessPolicy::$verb($generating, $owner), $verb);
            $this->assertFalse(AiReportAccessPolicy::$verb($ready, $other), $verb);
            $this->assertFalse(AiReportAccessPolicy::$verb($ready, null), $verb);
        }

        $this->assertTrue(AiReportAccessPolicy::canRefine($ready, $owner));
        $this->assertTrue(AiReportAccessPolicy::canRefine($failed, $owner));
        $this->assertFalse(AiReportAccessPolicy::canRefine($generating, $owner));
        $this->assertTrue(AiReportAccessPolicy::canRefine($full, $owner));
        $this->assertFalse(AiReportAccessPolicy::canRefine($ready, $other));
        $this->assertFalse(AiReportAccessPolicy::canRefine($ready, null));

        $parked = $this->report($owner, ['active' => false]);
        $this->assertFalse(AiReportAccessPolicy::canRefine($parked, $owner));

        $this->assertFalse(AiReportAccessPolicy::canDelete($generating, $owner));
        $this->assertTrue(AiReportAccessPolicy::canDelete($ready, $owner));
        $this->assertTrue(AiReportAccessPolicy::canDelete($failed, $owner));
        $this->assertFalse(AiReportAccessPolicy::canDelete($ready, $other));
        $this->assertFalse(AiReportAccessPolicy::canDelete($ready, null));
    }

    public function test_routes_return_404_when_the_module_is_switched_off_and_resolve_otherwise(): void
    {
        $user = $this->user();
        $report = $this->report($user);
        $signed = URL::signedRoute('ai-report.shared', ['report' => $report->id]);

        config(['app.off' => ['ai-report']]);
        $this->actingAs($user)->get(route('ai-report'))->assertNotFound();
        $this->actingAs($user)->get($signed)->assertNotFound();

        config(['app.off' => []]);
        $this->actingAs($user)->get(route('ai-report'))->assertOk();
        $this->actingAs($user)->get($signed)->assertRedirect(route('ai-report', ['r' => $report->id]));
    }

    public function test_every_module_name_normalizes_to_the_ai_report_slot(): void
    {
        config(['app.off' => ['ai-report']]);

        $this->assertTrue(tenantOffName('ai-report'));
        $this->assertTrue(tenantOffName('ai-report.shared'));
        $this->assertTrue(tenantOffName('filament.admin.resources.ai-reports.index'));
        $this->assertTrue(tenantOff('ai-report'));
        $this->assertContains('ai-report', tenantOffKeys());
        $this->assertFalse(tenantOffName('reports'));
        $this->assertFalse(tenantOffName('filament.admin.resources.reports.index'));

        config(['app.off' => []]);

        $this->assertFalse(tenantOffName('ai-report'));
        $this->assertFalse(tenantOffName('filament.admin.resources.ai-reports.index'));
    }

    public function test_block_catalog_drops_unknown_keys_and_flags_only_real_mismatches(): void
    {
        $fixture = $this->spec();
        $fixture['pages'][0]['blocks'][1]['url'] = 'http://x';
        $fixture['pages'][0]['blocks'][1]['check'] = 'mismatch';
        $out = BlockCatalog::validate($fixture);

        $this->assertSame($fixture['title'], $out['title']);
        $this->assertArrayNotHasKey('url', $out['pages'][0]['blocks'][1]);
        $this->assertArrayNotHasKey('check', $out['pages'][0]['blocks'][1]);

        $tooMany = $fixture;
        $tooMany['pages'] = array_fill(0, 7, $fixture['pages'][0]);
        try {
            BlockCatalog::validate($tooMany);
            $this->fail('more than 6 pages was accepted');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('pages exceed 6', $e->getMessage());
        }

        $long = $fixture;
        $long['pages'][0]['blocks'][1]['rows'][0][0] = str_repeat('ا', 130);
        $this->assertSame(120, mb_strlen(BlockCatalog::validate($long)['pages'][0]['blocks'][1]['rows'][0][0]));

        $mismatch = $fixture;
        $mismatch['pages'][0]['blocks'][1]['total'][1] = '1';
        $this->assertSame('mismatch', BlockCatalog::validate($mismatch)['pages'][0]['blocks'][1]['check']);

        $within = $fixture;
        $within['pages'][0]['blocks'][1]['rows'][0][1] = '۱۰۰۱';
        $this->assertArrayNotHasKey('check', BlockCatalog::validate($within)['pages'][0]['blocks'][1]);

        $percent = $fixture;
        $percent['pages'][0]['blocks'][1]['rows'][0][2] = '50';
        $this->assertSame('mismatch', BlockCatalog::validate($percent)['pages'][0]['blocks'][1]['check']);

        $text = $fixture;
        $text['pages'][0]['blocks'][1]['total'][0] = '999';
        $this->assertArrayNotHasKey('check', BlockCatalog::validate($text)['pages'][0]['blocks'][1]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('block table: columns and rows are required');
        BlockCatalog::validate(['lang' => 'fa', 'title' => 'عنوان آزمایشی', 'pages' => [['blocks' => [['type' => 'table']]]]]);
    }

    public function test_report_ai_service_repairs_once(): void
    {
        Http::swap(new HttpClientFactory);
        config(['ai.gateway.model' => 'Gemini-3-Flash-Preview', 'ai.gateway.max_output' => 16384]);
        $spec = [
            'lang' => 'fa',
            'title' => 'عنوان آزمایشی',
            'pages' => [['blocks' => [['type' => 'title', 'heading' => 'عنوان آزمایشی']]]],
        ];
        Http::fake([
            '*' => Http::sequence()
                ->push(['choices' => [['message' => ['content' => '{'], 'finish_reason' => 'stop']], 'usage' => ['total_tokens' => 4]])
                ->push(['choices' => [['message' => ['content' => json_encode($spec)], 'finish_reason' => 'stop']], 'usage' => ['total_tokens' => 6]]),
        ]);

        $result = app(ReportAiService::class)->generate('یک درخواست آزمایشی', '', [['mime' => 'application/pdf', 'data' => base64_encode('%PDF')]]);

        $this->assertSame('عنوان آزمایشی', $result['spec']['title']);
        $this->assertSame(10, $result['tokens']);
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'apikey test-key')
            && $request['response_format']['type'] === 'json_schema'
            && $request['response_format']['json_schema']['name'] === 'report'
            && $request['response_format']['json_schema']['strict'] === true
            && $request['messages'][1]['content'][0]['type'] === 'file'
            && collect($request['messages'][1]['content'])->last()['type'] === 'text'
            && $request['max_tokens'] === 16384);
    }

    public function test_report_ai_service_does_not_repair_a_length_cutoff(): void
    {
        config(['ai.gateway.model' => 'Gemini-3-Flash-Preview']);
        Http::swap(new HttpClientFactory);
        Http::fake(['*' => Http::response(['choices' => [['finish_reason' => 'length', 'message' => ['content' => '{']]]])]);
        try {
            app(ReportAiService::class)->generate('یک درخواست آزمایشی');
            $this->fail('length was not too_large');
        } catch (\DomainException $e) {
            $this->assertSame('too_large', $e->getMessage());
        }
        Http::assertSentCount(1);
    }

    public function test_report_ai_service_does_not_repair_a_content_filter_block(): void
    {
        config(['ai.gateway.model' => 'Gemini-3-Flash-Preview']);
        Http::swap(new HttpClientFactory);
        Http::fake(['*' => Http::response(['choices' => [['finish_reason' => 'content_filter', 'message' => ['content' => '']]]])]);
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('blocked');
        app(ReportAiService::class)->generate('یک درخواست آزمایشی');
    }

    public function test_file_extractor_caps_text_ignores_a_fake_docx_and_hidden_sheets(): void
    {
        Storage::disk('local')->put('ai-report/tmp/9/note.txt', str_repeat('الف', 50000));
        Storage::disk('local')->put('ai-report/tmp/9/bad.docx', "PK\x03\x04not-a-zip");
        $text = app(FileExtractor::class)->extract('local', 'ai-report/tmp/9');
        $this->assertStringContainsString('بریده شد', $text['text']);
        $this->assertLessThan(50000, mb_strlen($text['text']));
        $this->assertContains(collect($text['skipped'])->firstWhere('name', 'bad.docx')['reason'], ['unsupported', 'corrupt']);

        Storage::disk('local')->makeDirectory('ai-report/tmp/8');
        $path = Storage::disk('local')->path('ai-report/tmp/8/book.xlsx');
        $writer = new Writer();
        $writer->openToFile($path);
        $writer->getCurrentSheet()->setName('hidden');
        $writer->getCurrentSheet()->setIsVisible(false);
        $writer->addRow(Row::fromValues(['SECRET']));
        $writer->addNewSheetAndMakeItCurrent()->setName('shown');
        $writer->addRow(Row::fromValues(['VISIBLE']));
        $writer->close();

        $sheet = app(FileExtractor::class)->extract('local', 'ai-report/tmp/8');
        $this->assertStringContainsString('VISIBLE', $sheet['text']);
        $this->assertStringNotContainsString('SECRET', $sheet['text']);
    }

    public function test_generate_forks_a_shared_parent_and_blocks_a_second_in_flight(): void
    {
        Bus::fake();
        $owner = $this->user();
        $recipient = $this->user();
        $parent = $this->report($owner, [
            'recipients' => [(int) $recipient->id],
            'spec' => $this->spec(),
        ]);
        $form = Livewire::actingAs($owner)->test(Main::class)->instance()->form;
        $form->prompt = 'این یک درخواست اصلاح گزارش است';

        $fork = app(GenerateReportAction::class)->execute($form, $owner, $parent);

        $this->assertNotSame($parent->id, $fork->id);
        $this->assertSame(AiReport::STATUS_READY, $parent->fresh()->status);
        $this->assertSame($parent->id, $fork->versions[0]['forked_from']);
        $this->assertSame('generating', $fork->status);
        Bus::assertDispatched(GenerateReportJob::class);

        $this->report($owner, ['status' => AiReport::STATUS_GENERATING, 'saved_at' => null]);
        try {
            app(GenerateReportAction::class)->execute($form, $owner);
            $this->fail('in-flight guard did not fire');
        } catch (ValidationException $e) {
            $this->assertSame('یک گزارش شما در حال ساخت است. پس از پایان آن، درخواست تازه بدهید.', $e->errors()['form.prompt'][0]);
        }
    }

    public function test_share_counts_only_new_integer_ids_and_keeps_one_bell_per_unseen_recipient(): void
    {
        $owner = $this->user();
        $already = $this->user();
        $fresh = $this->user();
        $report = $this->report($owner, ['title' => 'گزارش', 'recipients' => [(int) $already->id]]);

        $result = app(ShareReportAction::class)->execute($report, $owner, [(string) $already->id, (string) $fresh->id, (string) $owner->id]);

        $saved = $report->fresh()->recipients;
        $this->assertSame(1, $result['added']);
        $this->assertSame([(int) $already->id, (int) $fresh->id], $saved);
        $this->assertIsInt($saved[0]);
        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', $already->id)->count());
        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', $fresh->id)->count());
        $this->assertSame(0, DB::table('notifications')->where('notifiable_id', $owner->id)->count());
    }

    public function test_generate_job_refunds_quota_once_per_failure(): void
    {
        $owner = $this->user();
        $report = $this->report($owner, [
            'status' => AiReport::STATUS_GENERATING,
            'saved_at' => null,
            'versions' => [['prompt' => 'یک درخواست آزمایشی', 'files' => [], 'at' => now()->toIso8601String()]],
        ]);
        AiReport::consumeQuota($owner->id);
        $used = AiReport::quotaUsed($owner->id);
        Http::fake(['*' => Http::response([], 429, ['Retry-After' => '8'])]);

        $job = new GenerateReportJob($report->id);
        $job->handle(app(ReportAiService::class), app(FileExtractor::class));
        $job->handle(app(ReportAiService::class), app(FileExtractor::class));

        $this->assertSame('http_429', $report->fresh()->fail_reason);
        $this->assertSame($used - 1, AiReport::quotaUsed($owner->id));
    }

    public function test_a_failed_run_still_records_the_tokens_it_spent(): void
    {
        $owner = $this->user();
        $report = $this->report($owner, [
            'status' => AiReport::STATUS_GENERATING,
            'saved_at' => null,
            'tokens_total' => 0,
            'versions' => [['prompt' => 'یک درخواست آزمایشی', 'files' => [], 'at' => now()->toIso8601String()]],
        ]);
        Http::fake(['*' => Http::response([
            'choices' => [['message' => ['content' => 'not json at all'], 'finish_reason' => 'stop']],
            'usage' => ['total_tokens' => 7],
        ])]);

        (new GenerateReportJob($report->id))->handle(app(ReportAiService::class), app(FileExtractor::class));

        $failed = $report->fresh();
        $this->assertSame(AiReport::STATUS_FAILED, $failed->status);
        $this->assertGreaterThan(0, (int) $failed->tokens_total);
    }

    public function test_block_catalog_rejects_missing_fields_empty_headers_and_bidi(): void
    {
        try {
            BlockCatalog::validate(['lang' => 'fa', 'title' => 'عنوان آزمایشی', 'pages' => [['blocks' => [['type' => 'title']]]]]);
            $this->fail('a title without a heading was accepted');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('heading is required', $e->getMessage());
        }

        $aligned = $this->spec();
        $aligned['pages'][0]['blocks'][1]['columns'] = ['نام', '', 'سهم'];
        $out = BlockCatalog::validate($aligned);
        $this->assertSame(['نام', '', 'سهم'], $out['pages'][0]['blocks'][1]['columns']);
        $this->assertSame('۴۰', $out['pages'][0]['blocks'][1]['rows'][0][2]);

        $bidi = $this->spec();
        $bidi['title'] = "گزارش\u{202E}آزمایشی";
        $this->assertStringNotContainsString("\u{202E}", BlockCatalog::validate($bidi)['title']);

        config(['ai.report.spec.pages' => 1]);
        $tooMany = $this->spec();
        $tooMany['pages'][] = $tooMany['pages'][0];
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('pages exceed 1');
        BlockCatalog::validate($tooMany);
    }

    public function test_generate_refunds_a_failed_store_keeps_original_names_and_honors_the_lock(): void
    {
        Bus::fake();
        $owner = $this->user();
        $form = Livewire::actingAs($owner)->test(Main::class)->instance()->form;
        $form->prompt = 'این یک درخواست آزمایشی برای گزارش است';
        $path = tempnam(sys_get_temp_dir(), 'air');
        file_put_contents($path, 'hello report text');
        $form->files = [new class($path, 'report.txt', 'text/plain', UPLOAD_ERR_OK, true) extends UploadedFile {
            public static int $owner = 0;

            public static ?int $usedAtStore = null;

            public function storeAs($path, $name = null, $options = [])
            {
                self::$usedAtStore = AiReport::quotaUsed(self::$owner);

                return false;
            }
        }];
        $probe = $form->files[0]::class;
        $probe::$owner = $owner->id;

        try {
            app(GenerateReportAction::class)->execute($form, $owner);
            $this->fail('a failed store did not refund');
        } catch (\RuntimeException) {
            $this->assertSame(1, $probe::$usedAtStore);
            $failed = AiReport::query()->ownedBy($owner->id)->latest('id')->first();
            $this->assertSame(AiReport::STATUS_FAILED, $failed->status);
            $this->assertSame(0, AiReport::quotaUsed($owner->id));
            $this->assertSame(['report.txt'], $failed->versions[0]['files']);
        } finally {
            @unlink($path);
        }

        config(['ai.report.upload.total_kb' => 1024]);
        $big = tempnam(sys_get_temp_dir(), 'air');
        file_put_contents($big, str_repeat("hello report text line\n", 45600));
        $form->files = [new UploadedFile($big, 'big.txt', 'text/plain', UPLOAD_ERR_OK, true)];
        try {
            app(GenerateReportAction::class)->execute($form, $owner);
            $this->fail('the total cap did not fire');
        } catch (ValidationException $e) {
            $this->assertStringContainsString(convertToPersian(intdiv((int) config('ai.report.upload.total_kb'), 1024)), $e->errors()['form.files'][0]);
        } finally {
            @unlink($big);
        }

        config(['ai.report.upload.max_files' => 1, 'ai.report.upload.total_kb' => 25600]);
        $form->files = [
            UploadedFile::fake()->create('a.txt', 1, 'text/plain'),
            UploadedFile::fake()->create('b.txt', 1, 'text/plain'),
        ];
        try {
            app(GenerateReportAction::class)->execute($form, $owner);
            $this->fail('max_files was ignored');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('form.files', $e->errors());
        }

        $lock = Cache::lock('ai-report:generate:' . $owner->id, 10);
        $this->assertTrue($lock->get());
        $form->files = [];
        try {
            app(GenerateReportAction::class)->execute($form, $owner);
            $this->fail('the shared lock did not block');
        } catch (ValidationException $e) {
            $this->assertSame('یک گزارش شما در حال ساخت است. پس از پایان آن، درخواست تازه بدهید.', $e->errors()['form.prompt'][0]);
        } finally {
            $lock->release();
        }
    }

    public function test_extractor_reports_the_name_without_the_index_prefix(): void
    {
        Storage::disk('local')->put('ai-report/tmp/3/1-note.txt', 'hello');
        $text = app(FileExtractor::class)->extract('local', 'ai-report/tmp/3');
        $this->assertStringContainsString('<<<FILE note.txt>>>', $text['text']);
        $this->assertStringNotContainsString('1-note.txt', $text['text']);
    }

    public function test_update_and_delete_actions_honor_policy_and_refuse_an_empty_title(): void
    {
        $owner = $this->user();
        $other = $this->user();
        $report = $this->report($owner, [
            'saved_at' => null,
            'spec' => $this->spec(),
            'versions' => [[
                'prompt' => 'p',
                'files' => [],
                'spec' => $this->spec(),
                'at' => now()->toIso8601String(),
            ]],
        ]);

        app(UpdateReportAction::class)->execute($report, $owner, ['save' => true]);
        $this->assertNotNull($report->fresh()->saved_at);

        $report->spec = ['lang' => 'fa', 'title' => 'دیگر', 'pages' => [['blocks' => [['type' => 'title', 'heading' => 'دیگر']]]]];
        $report->save();
        app(UpdateReportAction::class)->execute($report->fresh(), $owner, ['revert_to' => 0]);
        $this->assertSame('گزارش آزمایشی', $report->fresh()->title);

        app(UpdateReportAction::class)->execute($report->fresh(), $owner, ['title' => 'عنوان تازه']);
        $renamed = $report->fresh();
        $this->assertSame('عنوان تازه', $renamed->title);
        $this->assertSame('عنوان تازه', $renamed->spec['pages'][0]['blocks'][0]['heading']);

        try {
            app(UpdateReportAction::class)->execute($renamed, $owner, ['title' => '   ']);
            $this->fail('an empty title was saved');
        } catch (ValidationException $e) {
            $this->assertSame('عنوان نمی‌تواند خالی باشد.', $e->errors()['titleDraft'][0]);
        }

        try {
            app(UpdateReportAction::class)->execute($renamed, $other, ['save' => true]);
            $this->fail('another user saved the report');
        } catch (\RuntimeException) {
            $this->assertNotSame($other->id, $renamed->fresh()->user_id);
        }

        Storage::disk('local')->put($renamed->tempDirectory() . '/a.txt', 'x');
        try {
            app(DeleteReportAction::class)->execute($renamed, $other);
            $this->fail('another user deleted the report');
        } catch (\RuntimeException) {
            $this->assertNotNull(AiReport::find($renamed->id));
        }
        app(DeleteReportAction::class)->execute($renamed, $owner);
        $this->assertNull(AiReport::find($renamed->id));
        Storage::disk('local')->assertMissing($renamed->tempDirectory() . '/a.txt');
    }

    public function test_share_truncated_flag_ignores_ineligible_extras(): void
    {
        config(['ai.report.max_recipients' => 1]);
        $owner = $this->user();
        $first = $this->user();
        $guest = $this->user(['type' => 'guest']);
        $report = $this->report($owner);

        $result = app(ShareReportAction::class)->execute($report, $owner, [(string) $first->id, (string) $first->id, (string) $owner->id, (string) $guest->id, '999999999']);

        $this->assertFalse($result['truncated']);
        $this->assertSame([(int) $first->id], $report->fresh()->recipients);
    }

    public function test_share_caps_recipients_and_the_job_writes_the_ready_row(): void
    {
        config(['ai.report.max_recipients' => 1]);
        $owner = $this->user();
        $first = $this->user();
        $second = $this->user();
        $report = $this->report($owner, ['title' => 'گزارش']);

        $capped = app(ShareReportAction::class)->execute($report, $owner, [(string) $first->id, (string) $second->id]);

        $this->assertSame(1, $capped['total']);
        $this->assertSame([(int) $first->id], $report->fresh()->recipients);
        $this->assertIsInt($report->fresh()->recipients[0]);

        $spec = [
            'lang' => 'fa',
            'title' => 'عنوان آزمایشی',
            'pages' => [['blocks' => [['type' => 'title', 'heading' => 'عنوان آزمایشی']]]],
        ];
        $generating = $this->report($owner, [
            'status' => AiReport::STATUS_GENERATING,
            'saved_at' => null,
            'versions' => [[
                'prompt' => 'یک درخواست آزمایشی',
                'files' => ['bad.docx'],
                'parent_spec' => ['lang' => 'fa'],
                'at' => now()->toIso8601String(),
            ]],
        ]);
        Storage::disk('local')->put($generating->tempDirectory() . '/1-bad.docx', "PK\x03\x04not-a-zip");
        config(['ai.gateway.model' => 'Gemini-3-Flash-Preview']);
        Http::fake(function () use ($generating, $spec) {
            $skipped = $generating->fresh()->versions[0]['skipped'][0]['name'] ?? null;
            if ($skipped !== 'bad.docx') {
                throw new \RuntimeException('skipped was not stored before the provider call');
            }

            return Http::response([
                'choices' => [['message' => ['content' => json_encode($spec)], 'finish_reason' => 'stop']],
                'usage' => ['total_tokens' => 9],
            ]);
        });

        (new GenerateReportJob($generating->id))->handle(app(ReportAiService::class), app(FileExtractor::class));

        $ready = $generating->fresh();
        $this->assertSame(AiReport::STATUS_READY, $ready->status);
        $this->assertSame('عنوان آزمایشی', $ready->title);
        $this->assertSame('Gemini-3-Flash-Preview', $ready->model);
        $this->assertSame(9, $ready->tokens_total);
        $this->assertArrayNotHasKey('parent_spec', $ready->versions[0]);
        $this->assertSame('bad.docx', $ready->versions[0]['skipped'][0]['name']);
    }

    private function plainSpec(string $title): array
    {
        return ['lang' => 'en', 'title' => $title, 'pages' => [['blocks' => [['type' => 'title', 'heading' => $title]]]]];
    }

    private function toastType(string $type): \Closure
    {
        return fn ($event, $params) => ($params['type'] ?? null) === $type;
    }

    public function test_main_mounts_with_and_without_a_report_and_scopes_access(): void
    {
        $owner = $this->user();
        $recipient = $this->user();
        $stranger = $this->user();
        $report = $this->report($owner, ['recipients' => [(int) $recipient->id]]);

        Livewire::actingAs($owner)->test(Main::class)->assertSet('reportId', null)->assertSet('readOnly', false);
        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])->assertSet('reportId', $report->id)->assertSet('readOnly', false);
        Livewire::actingAs($recipient)->test(Main::class, ['report' => $report])->assertSet('readOnly', true);
        Livewire::actingAs($stranger)->test(Main::class, ['report' => $report])
            ->assertOk()
            ->assertSet('reportId', null)
            ->assertDispatched('toast', $this->toastType('warning'));
    }

    public function test_open_is_scoped_to_owner_and_recipient(): void
    {
        $owner = $this->user();
        $recipient = $this->user();
        $stranger = $this->user();
        $report = $this->report($owner, ['recipients' => [(int) $recipient->id]]);

        Livewire::actingAs($owner)->test(Main::class)->call('open', $report->id)->assertSet('reportId', $report->id)->assertSet('readOnly', false);
        Livewire::actingAs($recipient)->test(Main::class)->call('open', $report->id)->assertSet('reportId', $report->id)->assertSet('readOnly', true);

        Livewire::actingAs($stranger)->test(Main::class)->call('open', $report->id)->assertSet('reportId', null)->assertDispatched('toast');
    }

    public function test_read_only_viewers_cannot_touch_guarded_properties(): void
    {
        $owner = $this->user();
        $recipient = $this->user();
        $report = $this->report($owner, ['recipients' => [(int) $recipient->id]]);

        foreach ([['form.prompt', 'a prompt long enough'], ['form.files', []], ['shareRecipients', []], ['titleDraft', 'x']] as [$property, $value]) {
            Livewire::actingAs($recipient)->test(Main::class, ['report' => $report])->set($property, $value)->assertStatus(403);
        }
    }

    public function test_close_resets_the_component_state(): void
    {
        $owner = $this->user();
        $report = $this->report($owner);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->call('close')
            ->assertSet('reportId', null)
            ->assertSet('readOnly', false)
            ->assertSet('lastSeenStatus', null)
            ->assertSet('titleDraft', null)
            ->assertSet('shareRecipients', []);
    }

    public function test_generate_dispatches_the_job_and_a_failure_shows_a_warning_toast(): void
    {
        Bus::fake();
        $owner = $this->user();

        $component = Livewire::actingAs($owner)->test(Main::class)
            ->set('form.prompt', 'a prompt that is long enough')
            ->call('generate')
            ->assertHasNoErrors()
            ->assertDispatched('toast', $this->toastType('success'));

        $created = AiReport::query()->ownedBy($owner->id)->latest('id')->first();
        $this->assertSame(AiReport::STATUS_GENERATING, $created->status);
        $component->assertSet('reportId', $created->id);
        Bus::assertDispatched(GenerateReportJob::class);

        $this->mock(GenerateReportAction::class, fn ($mock) => $mock->shouldReceive('execute')->andThrow(new \RuntimeException('boom')));
        $other = $this->user();
        Livewire::actingAs($other)->test(Main::class)
            ->set('form.prompt', 'a prompt that is long enough')
            ->call('generate')
            ->assertDispatched('toast', $this->toastType('warning'))
            ->assertSet('reportId', null);
    }

    public function test_refine_forks_a_shared_report_and_leaves_the_original_untouched(): void
    {
        Bus::fake();
        $owner = $this->user();
        $recipient = $this->user();
        $report = $this->report($owner, ['recipients' => [(int) $recipient->id], 'spec' => $this->plainSpec('Shared')]);

        $component = Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->set('form.prompt', 'make the report shorter please')
            ->call('refine');

        $original = $report->fresh();
        $this->assertSame(AiReport::STATUS_READY, $original->status);
        $this->assertSame([(int) $recipient->id], $original->recipients);
        $this->assertCount(1, $original->versions);
        $fork = AiReport::query()->ownedBy($owner->id)->where('id', '!=', $report->id)->latest('id')->first();
        $this->assertNotNull($fork);
        $this->assertSame($report->id, $fork->versions[0]['forked_from']);
        $component->assertSet('reportId', $fork->id);
    }

    public function test_retrying_a_failed_refine_sends_the_current_spec_as_the_parent(): void
    {
        Bus::fake();
        $owner = $this->user();
        $reverted = $this->plainSpec('Reverted');
        $report = $this->report($owner, [
            'status' => AiReport::STATUS_FAILED,
            'fail_reason' => 'timeout',
            'spec' => $reverted,
            'versions' => [['prompt' => 'p', 'files' => [], 'spec' => $this->plainSpec('Newer'), 'at' => now()->toIso8601String()]],
        ]);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->set('form.prompt', 'make the report shorter please')
            ->call('refine');

        $versions = $report->fresh()->versions;
        $this->assertEquals($reverted, end($versions)['parent_spec']);
    }

    public function test_retry_regenerates_a_failed_report_from_the_stored_prompt(): void
    {
        Bus::fake();
        $owner = $this->user();
        $report = $this->report($owner, [
            'status' => AiReport::STATUS_FAILED,
            'fail_reason' => 'timeout',
            'versions' => [['prompt' => 'the stored prompt that is long enough', 'files' => [], 'spec' => $this->plainSpec('Old'), 'at' => now()->toIso8601String()]],
        ]);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])->call('retry');

        $fresh = $report->fresh();
        $this->assertSame(AiReport::STATUS_GENERATING, $fresh->status);
        $this->assertSame('the stored prompt that is long enough', collect($fresh->versions)->last()['prompt']);
    }

    public function test_retry_is_refused_for_a_recipient_and_does_nothing_without_a_prompt(): void
    {
        Bus::fake();
        $owner = $this->user();
        $recipient = $this->user();
        $failed = $this->report($owner, [
            'status' => AiReport::STATUS_FAILED,
            'recipients' => [(int) $recipient->id],
            'versions' => [['prompt' => 'the stored prompt that is long enough', 'files' => [], 'at' => now()->toIso8601String()]],
        ]);

        Livewire::actingAs($recipient)->test(Main::class, ['report' => $failed])->call('retry');
        $this->assertSame(AiReport::STATUS_FAILED, $failed->fresh()->status);

        $blank = $this->report($owner, ['status' => AiReport::STATUS_FAILED, 'versions' => [['files' => [], 'at' => now()->toIso8601String()]]]);
        Livewire::actingAs($owner)->test(Main::class, ['report' => $blank])->call('retry')->assertNotDispatched('toast');
        $this->assertSame(AiReport::STATUS_FAILED, $blank->fresh()->status);
    }

    public function test_retry_with_attached_files_refills_the_composer_instead_of_sending(): void
    {
        Bus::fake();
        $owner = $this->user();
        $report = $this->report($owner, [
            'status' => AiReport::STATUS_FAILED,
            'fail_reason' => 'timeout',
            'versions' => [['prompt' => 'the stored prompt that is long enough', 'files' => ['a.pdf'], 'spec' => $this->plainSpec('Old'), 'at' => now()->toIso8601String()]],
        ]);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->call('retry')
            ->assertSet('form.prompt', 'the stored prompt that is long enough')
            ->assertDispatched('ai-report-open')
            ->assertDispatched('toast', $this->toastType('warning'));

        Bus::assertNotDispatched(GenerateReportJob::class);
        $this->assertSame(AiReport::STATUS_FAILED, $report->fresh()->status);
    }

    public function test_a_stale_block_editor_gets_a_specific_toast(): void
    {
        $owner = $this->user();
        $report = $this->blockRefineReport($owner);

        $component = Livewire::actingAs($owner)->test(Main::class, ['report' => $report])->call('openBlockEditor', '0:1');
        $fresh = $report->fresh();
        $changed = $fresh->spec;
        $changed['pages'][0]['blocks'][1]['heading'] = 'تب دیگر';
        $fresh->spec = $changed;
        $fresh->pushVersion(['prompt' => 'x', 'files' => [], 'spec' => $changed, 'at' => now()->toIso8601String()]);
        $fresh->save();

        $component->call('saveBlock')
            ->assertDispatched('toast', fn ($event, $params) => ($params['message'] ?? null) === 'گزارش در جای دیگری تغییر کرده؛ پنجره را ببندید و دوباره باز کنید.' && ($params['type'] ?? null) === 'warning')
            ->assertSet('blockEditorOpen', false);
    }

    public function test_section_refine_locks_type_and_length_and_a_shared_owner_sees_the_frozen_note(): void
    {
        $owner = $this->user();
        $recipient = $this->user();
        $private = $this->blockRefineReport($owner);
        $shared = $this->blockRefineReport($owner, ['recipients' => [(int) $recipient->id]]);

        $whole = Livewire::actingAs($owner)->test(Main::class, ['report' => $private])->html();
        $this->assertStringNotContainsString('ai-report-section-refine-hint', $whole);
        $this->assertStringNotContainsString('ai-report-shared-frozen-note', $whole);

        $section = Livewire::actingAs($owner)->test(Main::class, ['report' => $private])->call('editBlock', '0:1')->html();
        $this->assertStringContainsString('ai-report-section-refine-hint', $section);
        $this->assertSame(2, preg_match_all('/<select[^>]*name="form\.(type|length)"[^>]*disabled|<select[^>]*disabled[^>]*name="form\.(type|length)"/', $section));

        $sharedHtml = Livewire::actingAs($owner)->test(Main::class, ['report' => $shared])->html();
        $this->assertStringContainsString('برای تغییر عنوان، ویرایش دستی یا برگشت به نسخهٔ قبل', $sharedHtml);
    }

    public function test_the_current_version_is_the_newest_one_matching_the_spec(): void
    {
        $same = $this->plainSpec('Same');
        $report = $this->report($this->user(), [
            'spec' => $same,
            'versions' => [
                ['prompt' => 'p', 'spec' => $same, 'at' => now()->toIso8601String()],
                ['prompt' => 'p', 'spec' => $this->plainSpec('Other'), 'at' => now()->toIso8601String()],
                ['prompt' => 'p', 'spec' => $same, 'at' => now()->toIso8601String()],
                ['prompt' => 'p', 'at' => now()->toIso8601String()],
            ],
        ]);

        $this->assertSame([false, false, true, false], array_column((new AiReportPresenter())->versions($report), 'is_current'));
    }

    public function test_composer_state_decides_submit_rights_refine_target_and_quota_tone(): void
    {
        $owner = $this->user();
        $this->actingAs($owner);
        $presenter = new AiReportPresenter();
        $limits = $presenter->limits();
        $quota = fn (int $used, int $cap = 10) => ['used' => $used, 'cap' => $cap];

        $fresh = $presenter->composerState(null, true, $quota(2), false, null, $limits);
        $this->assertFalse($fresh['refining']);
        $this->assertFalse($fresh['busy']);
        $this->assertFalse($fresh['inactive']);
        $this->assertFalse($fresh['sectionRefine']);
        $this->assertTrue($fresh['canSubmit']);
        $this->assertSame(20, $fresh['quotaPct']);
        $this->assertSame(8, $fresh['quotaLeft']);
        $this->assertSame('text-[var(--md-sys-color-on-surface-variant)]', $fresh['quotaTone']['text']);

        $warn = $presenter->composerState(null, true, $quota(8), false, null, $limits);
        $this->assertSame('text-[var(--tool-gold-color)]', $warn['quotaTone']['text']);
        $this->assertTrue($warn['canSubmit']);

        $full = $presenter->composerState(null, true, $quota(10), false, null, $limits);
        $this->assertTrue($full['exhausted']);
        $this->assertFalse($full['canSubmit']);
        $this->assertSame('bg-[var(--md-sys-color-error)]', $full['quotaTone']['fill']);
        $this->assertSame(0, $full['quotaLeft']);

        $over = $presenter->composerState(null, true, $quota(12), false, null, $limits);
        $this->assertSame(100, $over['quotaPct']);
        $this->assertSame(0, $over['quotaLeft']);

        $zero = $presenter->composerState(null, true, $quota(0, 0), false, null, $limits);
        $this->assertSame(0, $zero['quotaPct']);
        $this->assertTrue($zero['exhausted']);

        $ready = $this->report($owner);
        $refine = $presenter->composerState($ready, true, $quota(1), false, null, $limits);
        $this->assertTrue($refine['refining']);
        $this->assertTrue($refine['canSubmit']);
        $this->assertFalse($presenter->composerState($ready, true, $quota(1), true, null, $limits)['canSubmit']);
        $this->assertFalse($presenter->composerState($ready, false, $quota(1), false, null, $limits)['refining']);
        $this->assertNull($presenter->composerState($ready, true, $quota(1), false, 'zz', $limits)['blockLabel']);
        $this->assertFalse($presenter->composerState($ready, true, $quota(1), false, 'zz', $limits)['sectionRefine']);

        $generating = $this->report($owner, ['status' => AiReport::STATUS_GENERATING]);
        $busy = $presenter->composerState($generating, true, $quota(1), false, null, $limits);
        $this->assertTrue($busy['busy']);
        $this->assertFalse($busy['refining']);
        $this->assertFalse($busy['canSubmit']);

        $parked = $this->report($owner, ['active' => false]);
        $inactive = $presenter->composerState($parked, true, $quota(1), false, null, $limits);
        $this->assertTrue($inactive['inactive']);
        $this->assertFalse($inactive['refining']);
        $this->assertFalse($presenter->composerState($parked, false, $quota(1), false, null, $limits)['inactive']);

        $rules = $fresh['rules'];
        $this->assertSame(['سهمیه', 'فایل‌ها', 'نوبت ساخت', 'اشتراک', 'بازبینی'], array_keys($rules));
        $this->assertStringContainsString(convertToPersian('10'), $rules['سهمیه']);
        $this->assertStringContainsString(convertToPersian((string) $limits['files']), $rules['فایل‌ها']);
    }

    public function test_table_chrome_helpers_decide_filter_state_and_load_more(): void
    {
        $presenter = new AiReportPresenter();

        $this->assertSame(['all', 'mine', 'shared', 'draft', 'saved', 'inactive'], array_keys($presenter->filterLabels()));
        $this->assertFalse($presenter->isFiltered('all', ''));
        $this->assertTrue($presenter->isFiltered('mine', ''));
        $this->assertTrue($presenter->isFiltered('all', 'x'));

        config(['ai.report.list_max' => 20]);
        $this->assertTrue($presenter->showLoadMore(10, 10));
        $this->assertFalse($presenter->showLoadMore(9, 10));
        $this->assertFalse($presenter->showLoadMore(20, 20));
    }

    public function test_block_editor_state_lists_only_editable_text_fields_and_locks_the_assumptions_heading(): void
    {
        $presenter = new AiReportPresenter();
        $faHeading = (string) config('ai.report.assumptions.heading.fa');
        $enHeading = (string) config('ai.report.assumptions.heading.en');
        $report = $this->report($this->user());
        $english = $this->report($this->user(), ['spec' => ['lang' => 'en', 'title' => 'R', 'pages' => []]]);

        $draft = [
            'type' => 'table',
            'check' => 'mismatch',
            'heading' => 'H',
            'columns' => ['A', 'B'],
            'rows' => [['1', '2']],
            'total' => ['3', '4'],
            'tone' => 'warn',
            'bullets' => ['x', 'y'],
            'pct' => 5,
            'meta' => ['unit' => 'kg', 'note' => 'n'],
        ];
        $state = $presenter->blockEditor($draft, $report);

        $this->assertSame(['heading', 'columns.0', 'columns.1', 'bullets.0', 'bullets.1', 'meta.note'], $state['fields']->keys()->all());
        $this->assertSame(2, $state['width']);
        $this->assertFalse($state['isAssumptions']);
        $this->assertSame('جمع', $state['labels']['total']);
        $this->assertContains('bullets', $state['long']);

        $this->assertTrue($presenter->blockEditor(['type' => 'section', 'heading' => $faHeading], $report)['isAssumptions']);
        $this->assertFalse($presenter->blockEditor(['type' => 'table', 'heading' => $faHeading], $report)['isAssumptions']);
        $this->assertFalse($presenter->blockEditor(['type' => 'section', 'heading' => 'other'], $report)['isAssumptions']);
        $this->assertTrue($presenter->blockEditor(['type' => 'section', 'heading' => $enHeading], $english)['isAssumptions']);
        $this->assertSame(0, $presenter->blockEditor(['type' => 'text'], null)['width']);

        $this->assertSame('نکته ' . convertToPersian(1), $presenter->blockFieldLabel('bullets.0'));
        $this->assertSame('ردیف ' . convertToPersian(2) . ' ' . convertToPersian(3), $presenter->blockFieldLabel('rows.1.2'));
        $this->assertSame('custom', $presenter->blockFieldLabel('custom'));
    }

    public function test_poll_status_toasts_exactly_once_per_transition(): void
    {
        $owner = $this->user();
        $ready = $this->report($owner, ['status' => AiReport::STATUS_GENERATING, 'saved_at' => null]);

        $component = Livewire::actingAs($owner)->test(Main::class, ['report' => $ready]);
        $ready->update(['status' => AiReport::STATUS_READY]);
        $component->call('pollStatus')->assertDispatched('toast', $this->toastType('success'));
        $component->call('pollStatus')->assertNotDispatched('toast');

        $failing = $this->report($owner, ['status' => AiReport::STATUS_GENERATING, 'saved_at' => null]);
        $component = Livewire::actingAs($owner)->test(Main::class, ['report' => $failing]);
        $failing->update(['status' => AiReport::STATUS_FAILED, 'fail_reason' => 'timeout']);
        $component->call('pollStatus')->assertDispatched('toast', $this->toastType('warning'));
        $component->call('pollStatus')->assertNotDispatched('toast');
    }

    public function test_an_unchanged_generating_tick_keeps_state_without_a_toast_and_a_changed_status_toasts(): void
    {
        $owner = $this->user();
        $report = $this->generatingReport($owner);

        $component = Livewire::actingAs($owner)->test(Main::class, ['report' => $report]);
        $component->call('pollStatus')
            ->assertNotDispatched('toast')
            ->assertSet('lastSeenStatus', AiReport::STATUS_GENERATING)
            ->assertSet('reportId', $report->id);

        $report->update(['status' => AiReport::STATUS_READY]);
        $component->call('pollStatus')->assertDispatched('toast', $this->toastType('success'));
        $component->call('pollStatus')->assertNotDispatched('toast')->assertSet('lastSeenStatus', AiReport::STATUS_READY);
    }

    public function test_an_unchanged_generating_tick_does_not_load_the_full_report_row(): void
    {
        $owner = $this->user();
        $report = $this->generatingReport($owner);
        $component = Livewire::actingAs($owner)->test(Main::class, ['report' => $report]);

        DB::enableQueryLog();
        $component->call('pollStatus');
        $skipped = collect(DB::getQueryLog())->pluck('query')->filter(fn (string $sql) => str_contains($sql, 'from `ai_reports`'));
        DB::flushQueryLog();

        $this->assertCount(1, $skipped);
        $this->assertStringContainsString('select exists', $skipped->first());

        $report->update(['status' => AiReport::STATUS_READY]);
        $component->call('pollStatus')->assertDispatched('toast', $this->toastType('success'));
    }

    public function test_the_list_poll_is_renderless_until_a_row_changes_and_resets_on_close(): void
    {
        $owner = $this->user();
        $open = $this->report($owner);
        $other = $this->generatingReport($owner);
        $component = Livewire::actingAs($owner)->test(Main::class, ['report' => $open]);
        $component->assertSeeHtml('wire:poll.visible.' . config('ai.report.poll_seconds') . 's="pollList"');

        $signature = $component->get('listSignature');
        $this->assertStringContainsString($other->id . ':' . AiReport::STATUS_GENERATING . ':', $signature);

        $component->call('pollList')->assertSet('listSignature', $signature);

        $other->update(['status' => AiReport::STATUS_READY]);
        $component->call('pollList');
        $this->assertNotSame($signature, $component->get('listSignature'));
        $this->assertStringContainsString($other->id . ':' . AiReport::STATUS_READY . ':', $component->get('listSignature'));

        $stale = $this->generatingReport($owner);
        $component->call('pollList');
        AiReport::query()->whereKey($stale->id)->update(['updated_at' => now()->subMinutes(AiReport::staleMinutes() + 1)]);
        $component->call('pollList');
        $this->assertStringContainsString($stale->id . ':' . AiReport::STATUS_FAILED . ':stale', $component->get('listSignature'));

        $component->call('close')->assertSet('reportId', null);
        $this->assertStringContainsString($stale->id . ':' . AiReport::STATUS_FAILED . ':stale', $component->get('listSignature'));
    }

    public function test_the_two_pollers_are_mutually_exclusive(): void
    {
        $owner = $this->user();
        $open = $this->generatingReport($owner);
        $seconds = config('ai.report.poll_seconds');

        $viewer = Livewire::actingAs($owner)->test(Main::class, ['report' => $open]);
        $viewer->assertSeeHtml('wire:poll.visible.' . $seconds . 's="pollStatus"')->assertDontSeeHtml('ai-report-list-polling');

        $ready = $this->report($owner);
        $aside = Livewire::actingAs($owner)->test(Main::class, ['report' => $ready]);
        $aside->assertSeeHtml('ai-report-list-polling')->assertDontSeeHtml('wire:poll.visible.' . $seconds . 's="pollStatus"');

        Livewire::actingAs($owner)->test(Main::class)->assertSeeHtml('ai-report-list-polling');
    }

    public function test_the_share_dialog_renders_only_for_an_owner_viewing_a_ready_report(): void
    {
        $owner = $this->user();
        $recipient = $this->user();
        $ready = $this->report($owner, ['recipients' => [(int) $recipient->id]]);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $ready])->assertSeeHtml('ai-report-share-picker');
        Livewire::actingAs($owner)->test(Main::class)->assertDontSeeHtml('ai-report-share-picker');
        Livewire::actingAs($owner)->test(Main::class, ['report' => $this->generatingReport($owner)])->assertDontSeeHtml('ai-report-share-picker');
        Livewire::actingAs($recipient)->test(Main::class, ['report' => $ready])->assertDontSeeHtml('ai-report-share-picker');
    }

    public function test_the_member_picker_is_built_only_after_open_share_and_resets_on_select_and_close(): void
    {
        $owner = $this->user();
        $recipient = $this->user();
        $ready = $this->report($owner);
        $other = $this->report($owner);

        $component = Livewire::actingAs($owner)->test(Main::class, ['report' => $ready])
            ->assertSet('shareOpen', false)
            ->assertSeeHtml('wire:click="openShare"')
            ->assertDontSeeHtml('ai-report-share-members');

        $component->call('openShare')->assertSet('shareOpen', true)->assertSeeHtml('ai-report-share-members');
        $component->call('open', $other->id)->assertSet('shareOpen', false)->assertDontSeeHtml('ai-report-share-members');
        $component->call('openShare')->assertSet('shareOpen', true);
        $component->call('close')->assertSet('shareOpen', false);

        Livewire::actingAs($recipient)->test(Main::class, ['report' => $this->report($owner, ['recipients' => [(int) $recipient->id]])])
            ->call('openShare')
            ->assertSet('shareOpen', false);
    }

    public function test_last_version_returns_the_newest_version_or_an_empty_array(): void
    {
        $owner = $this->user();

        $this->assertSame([], $this->report($owner, ['versions' => null])->lastVersion());
        $this->assertSame('b', $this->report($owner, ['versions' => [['prompt' => 'a'], ['prompt' => 'b']]])->lastVersion()['prompt']);
    }

    public function test_the_system_prompt_carries_the_configured_numbers(): void
    {
        config(['ai.report.spec.columns' => 3, 'ai.report.spec.kpi_items' => 5, 'ai.report.spec.kpi_min' => 1]);

        $method = new \ReflectionMethod(ReportAiService::class, 'systemPrompt');
        $prompt = $method->invoke(new ReportAiService());

        $this->assertStringContainsString('columns (at most 3)', $prompt);
        $this->assertStringContainsString('A table has at most 3 columns', $prompt);
        $this->assertStringContainsString('items (1 to 5 objects)', $prompt);
        $this->assertStringNotContainsString('{columns}', $prompt);
        $this->assertStringNotContainsString('{kpi', $prompt);
    }

    public function test_the_job_timeout_and_unique_window_follow_the_configured_padding(): void
    {
        config(['ai.report.job.padding' => 50, 'ai.report.job.retry_margin' => 0, 'ai.report.timeout' => 40, 'ai.report.connect_timeout' => 7, 'queue.connections.' . config('queue.default') . '.retry_after' => 100000]);
        $job = new GenerateReportJob(1);

        $this->assertSame(2 * ((int) AiClient::setting('report', 'timeout') + (int) AiClient::setting('report', 'connect_timeout')) + 50, $job->timeout);
        $this->assertSame(2 * (40 + 7) + 50, $job->timeout);
        $this->assertSame($job->timeout + 50, $job->uniqueFor);
    }

    public function test_a_rename_keeps_the_newest_version_current(): void
    {
        $owner = $this->user();
        $spec = $this->plainSpec('First');
        $report = $this->report($owner, [
            'spec' => $spec,
            'title' => 'First',
            'versions' => [
                ['prompt' => 'one', 'spec' => $this->plainSpec('Older'), 'at' => now()->toIso8601String()],
                ['prompt' => 'two', 'spec' => $spec, 'at' => now()->toIso8601String()],
            ],
        ]);

        app(UpdateReportAction::class)->execute($report, $owner, ['title' => 'Renamed']);

        $this->assertSame([false, true], array_column((new AiReportPresenter())->versions($report->fresh()), 'is_current'));
    }

    public function test_mark_failed_for_stale_requires_an_old_row_but_other_reasons_do_not(): void
    {
        $owner = $this->user();
        $fresh = $this->generatingReport($owner);

        $this->assertFalse($fresh->markFailed('stale'));
        $this->assertSame(AiReport::STATUS_GENERATING, $fresh->fresh()->status);

        AiReport::whereKey($fresh->id)->update(['updated_at' => now()->subMinutes(AiReport::staleMinutes() + 1)]);
        $this->assertTrue($fresh->markFailed('stale'));

        $other = $this->generatingReport($owner);
        $this->assertTrue($other->markFailed('timeout'));
    }

    public function test_share_slices_the_requested_ids_before_the_lookup(): void
    {
        config(['ai.report.max_recipients' => 1]);
        $owner = $this->user();
        $valid = $this->user();
        $report = $this->report($owner);

        $result = app(ShareReportAction::class)->execute($report, $owner, ['999999991', '999999992', (string) $valid->id]);

        $this->assertSame(0, $result['added']);
        $this->assertSame([], $report->fresh()->recipients);
    }

    public function test_quota_is_consumed_once_and_the_pre_check_rejects_at_the_cap(): void
    {
        Bus::fake();
        config(['ai.report.daily_cap' => 1]);
        $owner = $this->user();
        $form = Livewire::actingAs($owner)->test(Main::class)->instance()->form;
        $form->prompt = 'a prompt that is long enough';

        $report = app(GenerateReportAction::class)->execute($form, $owner);
        $this->assertSame(1, AiReport::quotaUsed($owner->id));

        $report->update(['status' => AiReport::STATUS_READY]);
        try {
            app(GenerateReportAction::class)->execute($form, $owner);
            $this->fail('the cap did not reject the second attempt');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('form.prompt', $e->errors());
        }
        $this->assertSame(1, AiReport::quotaUsed($owner->id));

        $filled = $this->user();
        AiReport::consumeQuota($filled->id);
        $form = Livewire::actingAs($filled)->test(Main::class)->instance()->form;
        $form->prompt = 'a prompt that is long enough';
        try {
            app(GenerateReportAction::class)->execute($form, $filled);
            $this->fail('the pre-filled counter did not reject');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('form.prompt', $e->errors());
        }
    }

    public function test_a_quota_race_past_the_cap_fails_the_row_with_quota_and_refunds_once(): void
    {
        Bus::fake();
        config(['ai.report.daily_cap' => 1]);
        $owner = $this->user();
        $form = Livewire::actingAs($owner)->test(Main::class)->instance()->form;
        $form->prompt = 'a prompt that is long enough';
        $raced = false;
        DB::listen(function ($query) use (&$raced, $owner) {
            if (!$raced && str_starts_with($query->sql, 'insert into `ai_reports`')) {
                $raced = true;
                AiReport::consumeQuota($owner->id);
            }
        });

        try {
            app(GenerateReportAction::class)->execute($form, $owner);
            $this->fail('the race past the cap was not rejected');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('form.prompt', $e->errors());
        }

        $row = AiReport::query()->ownedBy($owner->id)->latest('id')->first();
        $this->assertSame(AiReport::STATUS_FAILED, $row->status);
        $this->assertSame('quota', $row->fail_reason);
        $this->assertSame(1, AiReport::quotaUsed($owner->id));
    }

    public function test_the_livewire_temporary_upload_is_deleted_after_a_successful_generate(): void
    {
        Bus::fake();
        $owner = $this->user();
        $component = Livewire::actingAs($owner)->test(Main::class)
            ->set('form.prompt', 'a prompt that is long enough')
            ->set('form.files', [UploadedFile::fake()->createWithContent('note.txt', 'hello report text')]);
        $name = $component->instance()->form->files[0]->getFilename();
        $storage = FileUploadConfiguration::storage();
        $path = FileUploadConfiguration::path($name);
        $this->assertTrue($storage->exists($path));

        $component->call('generate');

        $this->assertFalse($storage->exists($path));
        $this->assertSame(1, AiReport::query()->ownedBy($owner->id)->count());
    }

    public function test_an_owner_can_remove_a_staged_upload_and_a_read_only_viewer_cannot_stage_or_remove(): void
    {
        $owner = $this->user();
        $recipient = $this->user();
        $component = Livewire::actingAs($owner)->test(Main::class)
            ->set('form.files', [UploadedFile::fake()->createWithContent('note.txt', 'hello report text')]);
        $name = $component->instance()->form->files[0]->getFilename();

        $component->call('_removeUpload', 'form.files', $name);
        $this->assertCount(0, $component->instance()->form->files);

        $shared = $this->report($owner, ['recipients' => [(int) $recipient->id]]);
        $viewer = Livewire::actingAs($recipient)->test(Main::class, ['report' => $shared]);
        $viewer->set('form.files', [UploadedFile::fake()->createWithContent('note.txt', 'hello report text')])->assertStatus(403);
        $fresh = Livewire::actingAs($recipient)->test(Main::class, ['report' => $shared]);
        $fresh->call('_removeUpload', 'form.files', $name);
        $this->assertCount(0, $fresh->instance()->form->files);
    }

    public function test_share_casts_ids_to_integers_and_keeps_one_bell_per_unseen_recipient(): void
    {
        $owner = $this->user();
        $already = $this->user();
        $fresh = $this->user();
        $report = $this->report($owner, ['title' => 'Shared', 'recipients' => [(int) $already->id]]);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->set('shareRecipients', [(string) $already->id, (string) $fresh->id])
            ->call('share')
            ->assertDispatched('toast', $this->toastType('success'));

        $this->assertSame([(int) $already->id, (int) $fresh->id], $report->fresh()->recipients);
        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', $already->id)->count());
        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', $fresh->id)->count());
    }

    public function test_delete_works_for_the_owner_and_is_refused_for_a_recipient(): void
    {
        $owner = $this->user();
        $recipient = $this->user();
        $report = $this->report($owner, ['recipients' => [(int) $recipient->id]]);

        Livewire::actingAs($recipient)->test(Main::class, ['report' => $report])->call('delete');
        $this->assertNotNull(AiReport::find($report->id));

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])->call('delete')->assertSet('reportId', null);
        $this->assertNull(AiReport::find($report->id));
    }

    public function test_rename_accepts_a_valid_title_and_refuses_empty_and_shared_reports(): void
    {
        $owner = $this->user();
        $report = $this->report($owner, ['title' => 'Old', 'spec' => $this->plainSpec('Old')]);

        $component = Livewire::actingAs($owner)->test(Main::class, ['report' => $report]);
        $component->set('titleDraft', 'New title')->call('rename');
        $this->assertSame('New title', $report->fresh()->title);
        $this->assertSame('New title', $report->fresh()->spec['title']);

        $component->set('titleDraft', '   ')->call('rename')->assertHasErrors(['titleDraft']);
        $this->assertSame('New title', $report->fresh()->title);

        $recipient = $this->user();
        $shared = $this->report($owner, ['title' => 'Locked', 'recipients' => [(int) $recipient->id], 'spec' => $this->plainSpec('Locked')]);
        Livewire::actingAs($owner)->test(Main::class, ['report' => $shared])
            ->set('titleDraft', 'Changed')
            ->call('rename')
            ->assertDispatched('toast', $this->toastType('warning'));
        $this->assertSame('Locked', $shared->fresh()->title);
    }

    public function test_rename_with_a_null_draft_falls_back_to_the_current_title(): void
    {
        $owner = $this->user();
        $report = $this->report($owner, ['title' => 'Kept', 'spec' => $this->plainSpec('Kept')]);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->set('titleDraft', null)
            ->call('rename')
            ->assertHasNoErrors();

        $this->assertSame('Kept', $report->fresh()->title);
    }

    public function test_revert_works_when_unshared_and_is_refused_when_shared(): void
    {
        $owner = $this->user();
        $recipient = $this->user();
        $versions = [
            ['prompt' => 'p', 'files' => [], 'spec' => $this->plainSpec('First'), 'at' => now()->toIso8601String()],
            ['prompt' => 'p', 'files' => [], 'spec' => $this->plainSpec('Second'), 'at' => now()->toIso8601String()],
        ];
        $free = $this->report($owner, ['spec' => $this->plainSpec('Second'), 'title' => 'Second', 'versions' => $versions]);
        $shared = $this->report($owner, ['spec' => $this->plainSpec('Second'), 'title' => 'Second', 'versions' => $versions, 'recipients' => [(int) $recipient->id]]);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $free])->call('revert', 0);
        $this->assertSame('First', $free->fresh()->title);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $shared])->call('revert', 0)->assertDispatched('toast', $this->toastType('warning'));
        $this->assertSame('Second', $shared->fresh()->title);
    }

    public function test_the_signed_route_renders_the_empty_state_for_a_stranger_and_opens_for_a_recipient(): void
    {
        $owner = $this->user();
        $recipient = $this->user();
        $stranger = $this->user();
        $report = $this->report($owner, ['recipients' => [(int) $recipient->id]]);
        $signed = URL::signedRoute('ai-report.shared', ['report' => $report->id]);

        $this->actingAs($stranger)->get($signed)->assertOk();
        $this->actingAs($recipient)->get($signed)->assertRedirect(route('ai-report', ['r' => $report->id]));
        $this->actingAs($recipient)->get(route('ai-report', ['r' => $report->id]))->assertOk();
        $this->actingAs($recipient)->get(preg_replace('/signature=[^&]+/', 'signature=bad', $signed))->assertRedirect(route('ai-report'));
    }

    public function test_a_stale_generating_report_is_failed_on_read_and_the_job_then_exits_early(): void
    {
        Http::fake();
        $owner = $this->user();
        $report = $this->report($owner, ['status' => AiReport::STATUS_GENERATING, 'saved_at' => null]);
        AiReport::whereKey($report->id)->update(['updated_at' => now()->subMinutes(AiReport::staleMinutes() + 1)]);

        $component = Livewire::actingAs($owner)->test(Main::class, ['report' => $report]);
        $component->call('pollStatus');

        $this->assertSame(AiReport::STATUS_FAILED, $report->fresh()->status);
        $this->assertSame('stale', $report->fresh()->fail_reason);

        Storage::disk('local')->put($report->tempDirectory() . '/leftover.txt', 'x');
        (new GenerateReportJob($report->id))->handle(app(ReportAiService::class), app(FileExtractor::class));

        Http::assertNothingSent();
        Storage::disk('local')->assertMissing($report->tempDirectory() . '/leftover.txt');
    }

    public function test_a_two_hundred_character_title_round_trips(): void
    {
        $owner = $this->user();
        $title = str_repeat('a', (int) config('ai.report.spec.short'));
        $report = $this->report($owner, ['title' => $title, 'spec' => $this->plainSpec('x')]);

        $this->assertSame($title, $report->fresh()->title);
        app(UpdateReportAction::class)->execute($report, $owner, ['title' => str_repeat('b', (int) config('ai.report.spec.short') + 50)]);
        $this->assertSame(str_repeat('b', (int) config('ai.report.spec.short')), $report->fresh()->title);
    }

    public function test_the_livewire_component_name_follows_the_ai_report_slot(): void
    {
        config(['app.off' => ['ai-report']]);
        $this->assertTrue(tenantOffName('dashboard.ai-report.main'));

        config(['app.off' => ['knowledge']]);
        $this->assertFalse(tenantOffName('dashboard.ai-report.main'));
    }

    public function test_shared_reports_cannot_be_renamed_or_reverted_and_refine_counts_only_spec_versions(): void
    {
        $owner = $this->user();
        $recipient = $this->user();
        $shared = $this->report($owner, ['recipients' => [(int) $recipient->id]]);
        $free = $this->report($owner);

        $this->assertFalse(AiReportAccessPolicy::canRename($shared, $owner));
        $this->assertFalse(AiReportAccessPolicy::canRevert($shared, $owner));
        $this->assertTrue(AiReportAccessPolicy::canRename($free, $owner));
        $this->assertTrue(AiReportAccessPolicy::canRevert($free, $owner));

        $padding = array_fill(0, AiReport::maxVersions() * 2, ['prompt' => 'p', 'files' => [], 'at' => now()->toIso8601String()]);
        $free->versions = [...$padding, ['prompt' => 'p', 'files' => [], 'spec' => $this->plainSpec('x'), 'at' => now()->toIso8601String()]];
        $this->assertTrue(AiReportAccessPolicy::canRefine($free, $owner));

        config(['ai.gateway.key' => null]);
        $this->assertFalse(AiReportAccessPolicy::canGenerate($owner));
    }

    public function test_block_catalog_flags_unknown_types_and_accepts_numeric_percent_columns(): void
    {
        try {
            BlockCatalog::validate(['lang' => 'en', 'title' => 'T', 'pages' => [['blocks' => [
                ['type' => 'title', 'heading' => 'T'],
                ['type' => 'gibberish'],
            ]]]]);
            $this->fail('an unknown block type was dropped silently');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('gibberish: unknown type', $e->getMessage());
        }

        $spec = BlockCatalog::validate(['lang' => 'en', 'title' => 'T', 'pages' => [['blocks' => [
            ['type' => 'table', 'columns' => ['A', 'B'], 'rows' => [['x', '60'], ['y', '40']], 'total' => ['sum', '100'], 'percent_column' => '1'],
        ]]]]);
        $this->assertSame(1, $spec['pages'][0]['blocks'][0]['percent_column']);
        $this->assertSame("a\u{200C}b", BlockCatalog::clean("a\u{200B}\u{200C}\u{2060}b", 10));
    }

    public function test_job_timeout_is_derived_from_the_provider_timeout_and_unreachable_maps_to_timeout(): void
    {
        config(['ai.report.timeout' => 60, 'ai.report.connect_timeout' => 10, 'ai.report.job.padding' => 30, 'ai.report.job.retry_margin' => 0, 'queue.connections.' . config('queue.default') . '.retry_after' => 100000]);
        $job = new GenerateReportJob(1);
        $this->assertSame(2 * (60 + 10) + 30, $job->timeout);
        $this->assertSame($job->timeout + 30, $job->uniqueFor);

        $owner = $this->user();
        $report = $this->report($owner, [
            'status' => AiReport::STATUS_GENERATING,
            'saved_at' => null,
            'versions' => [['prompt' => 'a prompt that is long enough', 'files' => [], 'at' => now()->toIso8601String()]],
        ]);
        Http::fake(fn () => throw new ConnectionException('cURL error 28 for https://gateway.test/v1/chat/completions'));

        (new GenerateReportJob($report->id))->handle(app(ReportAiService::class), app(FileExtractor::class));

        $this->assertSame(AiReport::STATUS_FAILED, $report->fresh()->status);
        $this->assertSame('timeout', $report->fresh()->fail_reason);
    }

    public function test_stage_rejects_an_inline_file_above_the_inline_cap(): void
    {
        Bus::fake();
        config(['ai.report.upload.inline_kb' => 1024]);
        $owner = $this->user();
        $form = Livewire::actingAs($owner)->test(Main::class)->instance()->form;
        $form->prompt = 'a prompt that is long enough';
        $path = tempnam(sys_get_temp_dir(), 'air');
        file_put_contents($path, "%PDF-1.4\n" . str_repeat('a', 1024 * 1024 + 16));
        $form->files = [new UploadedFile($path, 'big.pdf', 'application/pdf', UPLOAD_ERR_OK, true)];

        try {
            app(GenerateReportAction::class)->execute($form, $owner);
            $this->fail('the inline cap did not fire');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('form.files', $e->errors());
            $this->assertStringContainsString(convertToPersian(intdiv((int) config('ai.report.upload.inline_kb'), 1024)), $e->errors()['form.files'][0]);
        } finally {
            @unlink($path);
        }
    }

    private function toastContaining(string $needle): \Closure
    {
        return fn ($event, $params) => str_contains((string) ($params['message'] ?? ''), $needle);
    }

    private function generatingReport(User $owner, array $attributes = []): AiReport
    {
        return $this->report($owner, [
            'status' => AiReport::STATUS_GENERATING,
            'saved_at' => null,
            'versions' => [['prompt' => 'a prompt that is long enough', 'files' => [], 'at' => now()->toIso8601String()]],
            ...$attributes,
        ]);
    }

    public function test_the_list_loads_more_in_steps_up_to_the_configured_maximum_and_close_resets_it(): void
    {
        Bus::fake();
        config(['ai.report.list_max' => 7]);
        $owner = $this->user();
        $report = null;
        foreach (range(1, 8) as $i) {
            $report = $this->report($owner);
        }

        $component = Livewire::actingAs($owner)->test(Main::class)->assertSet('listLimit', 5);
        $this->assertCount(5, $component->instance()->reports);

        $component->call('loadMoreReports')->assertSet('listLimit', 7);
        $this->assertCount(7, $component->instance()->reports);

        $component->call('loadMoreReports')->assertSet('listLimit', 7);
        $component->call('close')->assertSet('listLimit', 5);

        $component->call('loadMoreReports')->assertSet('listLimit', 7);
        $component->call('open', $report->id)->assertSet('listLimit', 5);
        $component->call('close')->assertSet('listLimit', 5);

        $component->call('loadMoreReports')->assertSet('listLimit', 7);
        $component->set('form.prompt', str_repeat('ط', (int) config('ai.report.prompt.min')))
            ->call('generate');
        $this->assertSame(7, $component->instance()->listLimit);
        $this->assertNotSame('', $component->get('listSignature'));
    }

    public function test_the_quota_meter_leads_the_composer_and_warns_only_near_the_cap(): void
    {
        config(['ai.report.daily_cap' => 5]);
        $owner = $this->user();

        $html = Livewire::actingAs($owner)->test(Main::class)->html();
        $this->assertLessThan(strpos($html, 'ai-report-composer-title'), strpos($html, 'ai-report-quota-usage'));
        $this->assertStringNotContainsString('ai-report-quota-left', $html);

        foreach (range(1, 4) as $i) {
            AiReport::consumeQuota($owner->id);
        }
        $html = Livewire::actingAs($owner)->test(Main::class)->html();
        $this->assertStringContainsString('ai-report-quota-left', $html);
        $this->assertStringContainsString('aria-valuenow="4"', $html);
    }

    public function test_the_collapsed_rules_accordion_states_the_configured_cap_and_the_file_limits(): void
    {
        config(['ai.report.daily_cap' => 7]);
        $owner = $this->user();

        Livewire::actingAs($owner)->test(Main::class)
            ->assertSeeHtml('wire:key="ai-report-rules"')
            ->assertSeeHtml('روزانه ' . convertToPersian('7') . ' نوبت')
            ->assertSeeHtml('ai-report-rule-4');
    }

    private function mismatchReport(User $owner, array $attributes = []): AiReport
    {
        $spec = BlockCatalog::validate([
            'lang' => 'fa',
            'title' => 'گزارش هزینه',
            'pages' => [[
                'blocks' => [
                    ['type' => 'title', 'heading' => 'گزارش هزینه'],
                    [
                        'type' => 'table',
                        'heading' => 'هزینه‌ها',
                        'columns' => ['ماه', 'تعداد', 'هزینه'],
                        'rows' => [['تیر', '10', '100'], ['مرداد', '20', '200'], ['شهریور', '30', '300']],
                        'total' => ['جمع', '60', '999'],
                    ],
                ],
            ]],
        ]);

        return $this->report($owner, ['spec' => $spec, 'title' => 'گزارش هزینه', ...$attributes]);
    }

    public function test_the_mismatch_columns_helper_returns_indexes_and_check_table_output_is_unchanged(): void
    {
        $report = $this->mismatchReport($this->user());
        $table = $report->spec['pages'][0]['blocks'][1];

        $this->assertSame('mismatch', $table['check']);
        $this->assertSame([2], BlockCatalog::mismatchColumns($table));

        $table['total'] = ['جمع', '60', '600'];
        unset($table['check']);
        $this->assertSame([], BlockCatalog::mismatchColumns($table));
        $this->assertArrayNotHasKey('check', BlockCatalog::validate(['lang' => 'fa', 'title' => 't', 'pages' => [['blocks' => [$table]]]])['pages'][0]['blocks'][0]);
    }

    public function test_the_owner_taps_a_doubtful_number_and_gets_the_block_and_a_prompt_naming_the_table(): void
    {
        Bus::fake();
        $owner = $this->user();
        $report = $this->mismatchReport($owner);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->assertSeeHtml('wire:click="editBlock(\'0:1\', true)"')
            ->assertSeeHtml('aria-label="بررسی این رقم با هوش مصنوعی"')
            ->call('editBlock', '0:1', true)
            ->assertSet('form.block', '0:1')
            ->assertSet('form.prompt', 'مجموع ستون «هزینه» در جدول «هزینه‌ها» با ردیف‌ها نمی‌خواند؛ لطفاً ارقام را با فایل پیوست یا درخواست اصلی تطبیق بده و جدول را اصلاح کن.')
            ->assertDispatched('ai-report-open');
    }

    public function test_a_recipient_sees_no_tappable_marker_and_the_call_is_a_no_op(): void
    {
        $owner = $this->user();
        $recipient = $this->user();
        $report = $this->mismatchReport($owner, ['recipients' => [(int) $recipient->id]]);

        Livewire::actingAs($recipient)->test(Main::class, ['report' => $report])
            ->assertSee('ارقام با یکدیگر همخوانی ندارند')
            ->assertDontSeeHtml('aria-label="بررسی این رقم با هوش مصنوعی"')
            ->call('editBlock', '0:1', true)
            ->assertSet('form.block', null)
            ->assertSet('form.prompt', '');
    }

    public function test_a_typed_prompt_is_kept_when_a_doubtful_number_is_tapped(): void
    {
        $owner = $this->user();
        $report = $this->mismatchReport($owner);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->set('form.prompt', 'متن خودم')
            ->call('editBlock', '0:1', true)
            ->assertSet('form.block', '0:1')
            ->assertSet('form.prompt', 'متن خودم');
    }

    public function test_tapping_a_doubtful_number_dispatches_nothing_and_spends_no_quota_until_refine(): void
    {
        Bus::fake();
        $owner = $this->user();
        $report = $this->mismatchReport($owner);

        $component = Livewire::actingAs($owner)->test(Main::class, ['report' => $report])->call('editBlock', '0:1', true);

        Bus::assertNotDispatched(GenerateReportJob::class);
        $this->assertSame(0, AiReport::quotaUsed($owner->id));

        $component->call('refine');

        Bus::assertDispatched(GenerateReportJob::class);
        $this->assertSame(1, AiReport::quotaUsed($owner->id));
    }

    public function test_draft_saved_and_inactive_reports_wear_a_clear_state_badge_in_the_side_list_and_the_table(): void
    {
        $owner = $this->user();
        $this->report($owner, ['title' => 'گزارش پیش‌نویس', 'saved_at' => null]);
        $this->report($owner, ['title' => 'گزارش ذخیره', 'saved_at' => now()]);
        $this->report($owner, ['title' => 'گزارش غیرفعال', 'saved_at' => now(), 'active' => false]);

        $side = Livewire::actingAs($owner)->test(Main::class)->html();
        $this->assertStringContainsString('edit_note', $side);
        $this->assertStringContainsString('task_alt', $side);

        $inactiveSide = Livewire::actingAs($owner)->test(Main::class)->call('switchTab', 'inactive')->html();
        $this->assertStringContainsString('visibility_off', $inactiveSide);
        $this->assertStringContainsString('غیرفعال', $inactiveSide);

        $table = Livewire::actingAs($owner)->test(Main::class)->call('toggleView', 'table')->html();
        $this->assertStringContainsString('edit_note', $table);
        $this->assertStringContainsString('task_alt', $table);
    }

    public function test_a_reply_wrapped_in_markdown_fences_or_prose_is_still_parsed(): void
    {
        config(['ai.gateway.key' => 'test-key', 'ai.gateway.url' => 'https://gateway.test/v1']);
        $json = json_encode(['lang' => 'fa', 'title' => 'گزارش آزمایشی', 'pages' => [[
            'blocks' => [['type' => 'title', 'heading' => 'گزارش آزمایشی'], ['type' => 'summary', 'heading' => 'خلاصه', 'bullets' => ['الف', 'ب'], 'highlight' => 'نکته']],
        ]]], JSON_UNESCAPED_UNICODE);

        $reply = fn (string $content) => ['choices' => [['message' => ['content' => $content], 'finish_reason' => 'stop']], 'usage' => ['total_tokens' => 5]];
        Http::fake(['*' => Http::sequence()
            ->push($reply("```json\n{$json}\n```"))
            ->push($reply("در اینجا گزارش است:\n{$json}\nموفق باشید"))
            ->push($reply('not json at all'))
            ->push($reply('still not json'))]);

        foreach (range(1, 2) as $i) {
            $result = app(ReportAiService::class)->generate('گزارش آزمایشی بساز برای من');
            $this->assertSame('گزارش آزمایشی', $result['spec']['title']);
        }

        $this->expectException(\JsonException::class);
        app(ReportAiService::class)->generate('گزارش آزمایشی بساز برای من');
    }

    public function test_a_scalar_json_reply_triggers_the_repair_call(): void
    {
        config(['ai.gateway.key' => 'test-key', 'ai.gateway.url' => 'https://gateway.test/v1']);
        $json = json_encode(['lang' => 'fa', 'title' => 'گزارش آزمایشی', 'pages' => [[
            'blocks' => [['type' => 'title', 'heading' => 'گزارش آزمایشی'], ['type' => 'summary', 'heading' => 'خلاصه', 'bullets' => ['الف', 'ب'], 'highlight' => 'نکته']],
        ]]], JSON_UNESCAPED_UNICODE);
        $reply = fn (string $content) => ['choices' => [['message' => ['content' => $content], 'finish_reason' => 'stop']], 'usage' => ['total_tokens' => 5]];
        Http::fake(['*' => Http::sequence()->push($reply('5'))->push($reply($json))]);

        $result = app(ReportAiService::class)->generate('گزارش آزمایشی بساز برای من');

        $this->assertSame('گزارش آزمایشی', $result['spec']['title']);
        Http::assertSentCount(2);
    }

    public function test_form_rule_and_presenter_figures_follow_the_overridden_upload_config(): void
    {
        config(['ai.report.upload.inline_kb' => 2048, 'ai.report.upload.per_file_kb' => 3072, 'ai.report.upload.total_kb' => 5120, 'ai.report.upload.inline_total_kb' => 4096]);

        $form = Livewire::actingAs($this->user())->test(Main::class)->instance()->form;
        $limits = (new AiReportPresenter)->limits();

        $this->assertContains('max:3072', $form->rules()['files.*']);
        $this->assertSame(convertToPersian(3), $limits['per_file_mb']);
        $this->assertSame(convertToPersian(2), $limits['inline_mb']);
        $this->assertSame(convertToPersian(5), $limits['total_mb']);
        $this->assertSame(convertToPersian(4), $limits['inline_total_mb']);
    }

    public function test_opening_another_report_clears_staged_files_but_keeps_the_typed_prompt(): void
    {
        $owner = $this->user();
        $other = $this->report($owner);

        $component = Livewire::actingAs($owner)->test(Main::class)
            ->set('form.files', [UploadedFile::fake()->createWithContent('note.txt', 'hello report text')])
            ->set('form.prompt', 'متنی که پیش از باز کردن گزارش نوشتم')
            ->call('open', $other->id);

        $this->assertCount(0, $component->instance()->form->files);
        $this->assertSame('متنی که پیش از باز کردن گزارش نوشتم', $component->instance()->form->prompt);
    }

    public function test_the_list_poller_announces_a_finished_report_the_user_is_not_viewing(): void
    {
        $owner = $this->user();
        $ready = $this->generatingReport($owner);

        $component = Livewire::actingAs($owner)->test(Main::class)->call('pollList');
        $ready->update(['status' => AiReport::STATUS_READY]);
        $component->call('pollList')->assertDispatched('toast', $this->toastContaining('گزارش آماده شد.'));
        $component->call('pollList')->assertNotDispatched('toast');

        $failed = $this->generatingReport($owner);
        $component = Livewire::actingAs($owner)->test(Main::class)->call('pollList');
        $failed->update(['status' => AiReport::STATUS_FAILED, 'fail_reason' => 'timeout']);
        $component->call('pollList')->assertDispatched('toast', $this->toastType('warning'));

        $viewing = $this->generatingReport($owner);
        $component = Livewire::actingAs($owner)->test(Main::class, ['report' => $viewing])->call('pollList');
        $viewing->update(['status' => AiReport::STATUS_READY]);
        $component->call('pollList')->assertNotDispatched('toast');
    }

    public function test_an_inactive_report_cannot_be_renamed_reverted_or_hand_edited_but_can_be_reactivated(): void
    {
        $owner = $this->user();
        $report = $this->report($owner, ['active' => false, 'title' => 'Old', 'spec' => $this->plainSpec('Old')]);

        $this->assertFalse(AiReportAccessPolicy::canRename($report, $owner));
        $this->assertFalse(AiReportAccessPolicy::canRevert($report, $owner));

        $component = Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->set('titleDraft', 'New')
            ->call('rename')
            ->assertDispatched('toast', $this->toastType('warning'))
            ->call('openBlockEditor', '0:0')
            ->assertSet('blockRef', null);
        $this->assertSame('Old', $report->fresh()->title);

        $component->call('toggleActive')->assertDispatched('toast', $this->toastContaining('فعال شد'));
        $this->assertTrue($report->fresh()->active);
        $this->assertTrue(AiReportAccessPolicy::canRename($report->fresh(), $owner));
    }

    public function test_an_invalid_shared_link_redirects_with_a_notice_instead_of_a_bare_403(): void
    {
        $owner = $this->user();
        $recipient = $this->user();
        $report = $this->report($owner, ['recipients' => [(int) $recipient->id]]);
        $bad = preg_replace('/signature=[^&]+/', 'signature=bad', URL::signedRoute('ai-report.shared', ['report' => $report->id]));

        $this->actingAs($recipient)->get($bad)
            ->assertRedirect(route('ai-report'))
            ->assertSessionHas('ai-report-notice');

        session()->put('ai-report-notice', 'لینک منقضی شده؛ از فهرست «دریافتی» گزارش را باز کنید.');
        Livewire::actingAs($recipient)->test(Main::class)->assertDispatched('toast', $this->toastType('warning'));
    }

    public function test_a_report_that_turned_generating_cannot_be_deleted_from_a_stale_component(): void
    {
        $owner = $this->user();
        $report = $this->report($owner);
        $stale = AiReport::find($report->id);
        AiReport::whereKey($report->id)->update(['status' => AiReport::STATUS_GENERATING]);

        try {
            app(DeleteReportAction::class)->execute($stale, $owner);
            $this->fail('delete should be refused');
        } catch (\RuntimeException) {
        }

        $this->assertNotNull(AiReport::find($report->id));
    }

    public function test_retotal_leaves_a_total_cell_the_user_typed_by_hand_and_recomputes_the_rest(): void
    {
        $original = ['type' => 'table', 'columns' => ['a', 'b'], 'rows' => [['x', '10'], ['y', '20']], 'total' => ['جمع', '30']];
        $edited = $original;
        $edited['rows'][0][1] = '15';

        $this->assertSame('35', BlockCatalog::retotal($edited, $original)['total'][1]);

        $edited['total'][1] = '99';
        $this->assertSame('99', BlockCatalog::retotal($edited, $original)['total'][1]);
    }

    public function test_retotal_skips_a_column_whose_cells_carry_a_unit_word(): void
    {
        $original = ['type' => 'table', 'columns' => ['a', 'b', 'c'], 'rows' => [['x', '۱۲ میلیون', '10'], ['y', '۸ میلیون', '20']], 'total' => ['جمع', '۲۰ میلیون', '30']];
        $edited = $original;
        $edited['rows'][0][1] = '۱۵ میلیون';
        $edited['rows'][0][2] = '15';

        $total = BlockCatalog::retotal($edited, $original)['total'];

        $this->assertSame('۲۰ میلیون', $total[1]);
        $this->assertSame('35', $total[2]);
    }

    public function test_a_table_whose_total_cells_are_all_blank_or_dashes_drops_the_total(): void
    {
        $table = ['type' => 'table', 'columns' => ['a', 'b'], 'rows' => [['x', 'y']]];

        $this->assertArrayNotHasKey('total', BlockCatalog::normalizeBlock($table + ['total' => ['', '—']], 'fa'));
        $this->assertSame(['جمع', ''], BlockCatalog::normalizeBlock($table + ['total' => ['جمع', '']], 'fa')['total']);
    }

    public function test_the_table_row_enter_handler_ignores_inner_buttons_and_the_palette_toggle_is_accessible(): void
    {
        $owner = $this->user();
        $this->report($owner, ['title' => 'گزارش جدول']);

        $table = Livewire::actingAs($owner)->test(Main::class)->call('toggleView', 'table')->html();

        $this->assertStringContainsString('x-on:keydown.enter.self.prevent', $table);
        $this->assertStringNotContainsString('x-on:keydown.enter.prevent', $table);
        $this->assertStringContainsString(':aria-expanded="tagOpen"', $table);
    }

    public function test_the_block_editor_skips_nested_skip_listed_fields(): void
    {
        $owner = $this->user();
        $spec = ['lang' => 'fa', 'title' => 'گزارش', 'pages' => [['blocks' => [
            ['type' => 'kpi_row', 'items' => [['label' => 'درآمد', 'value' => '۱۰۰', 'tone' => 'up'], ['label' => 'هزینه', 'value' => '۵۰', 'tone' => 'down']]],
        ]]]];
        $report = $this->report($owner, ['spec' => $spec]);

        $html = Livewire::actingAs($owner)->test(Main::class, ['report' => $report])->call('openBlockEditor', '0:0')->html();

        $this->assertStringNotContainsString('blockDraft.items.0.tone', $html);
        $this->assertStringContainsString('blockDraft.items.0.value', $html);
    }

    public function test_the_system_prompt_block_limit_follows_config(): void
    {
        config(['ai.report.spec.blocks' => 7]);

        $system = (new \ReflectionMethod(ReportAiService::class, 'systemPrompt'))->invoke(app(ReportAiService::class), ['type' => 'monthly']);

        $this->assertStringContainsString('at most 7 blocks', $system);
        $this->assertStringNotContainsString('8 blocks', $system);
    }

    public function test_the_view_toggle_guards_match_modes_and_labels_every_button(): void
    {
        $render = fn (array $modes): string => html_entity_decode(Blade::render(
            '<x-ui.buttons.view-toggle state="view" action="toggleView" :modes="$modes"/>',
            ['modes' => $modes]
        ));

        $html = $render([
            ['value' => 'card', 'icon' => 'grid_view', 'title' => 'کارتی'],
            ['value' => 'report', 'match' => ['report', 'table'], 'icon' => 'table_rows', 'title' => 'کلاسیک'],
        ]);

        $this->assertStringContainsString("@click=\"view = 'card';\$wire.toggleView('card')\"", $html);
        $this->assertStringContainsString("@click=\"if (!(view === 'report' || view === 'table')) { view = 'report';\$wire.toggleView('report') }\"", $html);
        $this->assertStringContainsString('aria-label="کارتی"', $html);
        $this->assertStringContainsString('aria-label="کلاسیک"', $html);

        $owner = $this->user();
        $this->report($owner);
        $main = html_entity_decode(Livewire::actingAs($owner)->test(Main::class)->call('toggleView', 'table')->html());
        $this->assertStringContainsString("if (!(\$wire.activeTab === 'report' || \$wire.activeTab === 'table'))", $main);
    }

    public function test_a_successful_share_closes_the_dialog_and_a_denied_share_leaves_it_open(): void
    {
        $owner = $this->user();
        $report = $this->report($owner, ['title' => 'Shared']);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->set('shareRecipients', [])
            ->call('share')
            ->assertDispatched('close-modal', name: 'ai-report-share');

        $inactive = $this->report($owner, ['active' => false]);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $inactive])
            ->call('share')
            ->assertDispatched('toast', $this->toastType('warning'))
            ->assertNotDispatched('close-modal');
    }

    public function test_a_table_with_more_columns_than_the_cap_is_rejected_naming_the_cap(): void
    {
        $cap = (int) config('ai.report.spec.columns');
        $columns = array_map('strval', range(1, $cap + 1));

        $message = $this->b2Rejection(['type' => 'table', 'columns' => $columns, 'rows' => [array_fill(0, $cap + 1, 'x')]]);

        $this->assertStringContainsString('block table: too many columns', $message);
        $this->assertStringContainsString("at most {$cap}", $message);
        $this->assertSame($cap, count($this->b2Block(['type' => 'table', 'columns' => array_slice($columns, 0, $cap), 'rows' => [array_fill(0, $cap + 3, 'x')]])['rows'][0]));
    }

    public function test_the_aggregate_inline_cap_refuses_before_any_quota_is_spent_and_a_larger_cap_accepts(): void
    {
        Bus::fake();
        config(['ai.report.upload.inline_total_kb' => 2048, 'ai.report.upload.inline_kb' => 4096]);
        $owner = $this->user();
        $form = Livewire::actingAs($owner)->test(Main::class)->instance()->form;
        $form->prompt = 'a prompt that is long enough';
        $paths = $files = [];
        foreach (['a.pdf', 'b.pdf'] as $name) {
            $path = tempnam(sys_get_temp_dir(), 'air');
            file_put_contents($path, "%PDF-1.4\n" . str_repeat('a', 1536 * 1024));
            $paths[] = $path;
            $files[] = new UploadedFile($path, $name, 'application/pdf', UPLOAD_ERR_OK, true);
        }
        $form->files = $files;
        $before = AiReport::query()->count();

        try {
            app(GenerateReportAction::class)->execute($form, $owner);
            $this->fail('the aggregate inline cap did not fire');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('PDF', $e->errors()['form.files'][0]);
            $this->assertStringContainsString(convertToPersian(2), $e->errors()['form.files'][0]);
            $this->assertSame($before, AiReport::query()->count());
            $this->assertSame(0, AiReport::quotaUsed($owner->id));
        }

        config(['ai.report.upload.inline_total_kb' => 8192]);
        app(GenerateReportAction::class)->execute($form, $owner);
        $this->assertSame($before + 1, AiReport::query()->count());
        $this->assertSame(1, AiReport::quotaUsed($owner->id));
        array_map('unlink', $paths);
    }

    public function test_a_share_racing_a_deactivation_is_refused_and_recipients_stay_unchanged(): void
    {
        $owner = $this->user();
        $recipient = $this->user();
        $report = $this->report($owner, ['title' => 'Shared']);

        $component = Livewire::actingAs($owner)->test(Main::class, ['report' => $report]);
        AiReport::query()->whereKey($report->id)->update(['active' => false]);

        $component->set('shareRecipients', [(int) $recipient->id])
            ->call('share')
            ->assertNotDispatched('close-modal');

        $this->assertEmpty($report->fresh()->recipients ?? []);
    }

    public function test_the_viewer_keys_a_block_wrapper_with_its_resolved_view(): void
    {
        $owner = $this->user();
        $report = $this->report($owner, ['spec' => BlockCatalog::validate($this->spec()), 'title' => 'گزارش آزمایشی']);

        $html = Livewire::actingAs($owner)->test(Main::class, ['report' => $report])->html();

        $this->assertStringContainsString('wire:key="ai-report-block-0-1-table"', $html);
    }

    public function test_a_title_subtitle_carries_a_static_wire_key(): void
    {
        $this->assertStringContainsString('wire:key="ai-report-title-subtitle"', $this->renderReport(BlockCatalog::validate($this->spec())));
    }

    public function test_retotal_keeps_a_total_cell_that_carries_a_unit_word(): void
    {
        $original = ['type' => 'table', 'columns' => ['a', 'b'], 'rows' => [['x', '۲۰'], ['y', '۴۰']], 'total' => ['جمع', '۶۰ نفر']];
        $edited = $original;
        $edited['rows'][0][1] = '۳۰';

        $this->assertSame('۶۰ نفر', BlockCatalog::retotal($edited, $original)['total'][1]);
    }

    public function test_share_sends_no_direct_bell_and_dispatches_the_nudge_reconcile_for_the_report(): void
    {
        Bus::fake();
        $owner = $this->user();
        $fresh = $this->user();
        $report = $this->report($owner, ['title' => 'گزارش']);

        app(ShareReportAction::class)->execute($report, $owner, [$fresh->id]);

        $this->assertSame(0, DB::table('notifications')->where('notifiable_id', $fresh->id)->count());
        Bus::assertDispatched(ReconcileNudgeJob::class, fn (ReconcileNudgeJob $job) => $job->ruleKey === 'ai-report-controller:nudge'
            && $job->subjectClass === AiReport::class
            && (int) $job->subjectId === (int) $report->id);
    }

    public function test_the_side_list_header_shows_the_total_of_the_open_list_tab(): void
    {
        $owner = $this->user();
        foreach (range(1, 7) as $i) {
            $this->report($owner);
        }
        $this->report($owner, ['active' => false]);

        $component = Livewire::actingAs($owner)->test(Main::class);
        $this->assertSame(7, $component->instance()->total);
        $component->assertSeeHtml('ai-report-list-total')->assertSeeHtml(convertToPersian('7'));

        $component->call('switchTab', 'inactive');
        $this->assertSame(1, $component->instance()->total);
    }

    private function letterSpec(?array $middle = null, string $lang = 'fa'): array
    {
        $fa = $lang === 'fa';
        $middle ??= [
            ['type' => 'kpi_row', 'items' => [['label' => 'هزینه کل', 'value' => 'نه میلیارد'], ['label' => 'درآمد', 'value' => 'ده میلیارد']]],
            ['type' => 'callout', 'variant' => 'decision', 'heading' => 'تصمیم پیشنهادی', 'paragraphs' => ['بودجه تصویب شود.', 'بند دوم']],
        ];

        return BlockCatalog::validate($this->b2Spec([
            ['type' => 'title', 'heading' => $fa ? 'گزارش هزینه' : 'Cost report'],
            ...$middle,
            ['type' => 'section', 'heading' => $fa ? 'مفروضات و محدودیت‌ها' : 'Assumptions and limitations', 'paragraphs' => [$fa ? 'داده‌ها تا پایان مهر است.' : 'Data runs to October.', 'second']],
        ], $lang));
    }

    public function test_a_ready_report_shows_the_letter_card_with_title_figure_decision_and_assumption(): void
    {
        $owner = $this->user();
        $report = $this->report($owner, ['spec' => $this->letterSpec()]);
        $text = "گزارش هزینه\nهزینه کل: نه میلیارد\nتصمیم پیشنهادی: بودجه تصویب شود.\nمفروضات و محدودیت‌ها: داده‌ها تا پایان مهر است.";

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->assertSeeHtml('wire:key="ai-report-letter"')
            ->assertSeeHtml('wire:key="ai-report-letter-figure"')
            ->assertSeeHtml('wire:key="ai-report-letter-decision"')
            ->assertSeeHtml('wire:key="ai-report-letter-assumption"')
            ->assertSeeHtml('wire:key="ai-report-letter-copy"')
            ->assertSeeHtml('<bdi>نه میلیارد</bdi>')
            ->assertSeeHtml('data-text="' . e($text) . '"');
    }

    public function test_the_letter_card_falls_back_to_the_first_fact_without_a_kpi_row(): void
    {
        $owner = $this->user();
        $spec = $this->letterSpec([['type' => 'facts', 'items' => [['label' => 'محل', 'value' => 'تهران'], ['label' => 'تاریخ', 'value' => 'مهر']]]]);
        $report = $this->report($owner, ['spec' => $spec]);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->assertSeeHtml('wire:key="ai-report-letter-figure"')
            ->assertSeeHtml('data-text="' . e("گزارش هزینه\nمحل: تهران\nمفروضات و محدودیت‌ها: داده‌ها تا پایان مهر است.") . '"');
    }

    public function test_the_letter_card_skips_the_decision_part_when_there_is_no_decision_callout(): void
    {
        $owner = $this->user();
        $spec = $this->letterSpec([
            ['type' => 'kpi_row', 'items' => [['label' => 'هزینه کل', 'value' => 'نه میلیارد'], ['label' => 'درآمد', 'value' => 'ده میلیارد']]],
            ['type' => 'callout', 'variant' => 'info', 'heading' => 'یادداشت', 'paragraphs' => ['متن یادداشت']],
        ]);
        $report = $this->report($owner, ['spec' => $spec]);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->assertSeeHtml('wire:key="ai-report-letter"')
            ->assertSeeHtml('wire:key="ai-report-letter-figure"')
            ->assertDontSeeHtml('wire:key="ai-report-letter-decision"');
    }

    public function test_a_recipient_sees_the_letter_card_of_a_shared_ready_report(): void
    {
        $owner = $this->user();
        $viewer = $this->user();
        $report = $this->report($owner, ['spec' => $this->letterSpec(), 'recipients' => [$viewer->id]]);

        Livewire::actingAs($viewer)->test(Main::class, ['report' => $report])
            ->assertSeeHtml('wire:key="ai-report-letter"')
            ->assertSeeHtml('wire:key="ai-report-letter-copy"');
    }

    public function test_an_english_report_uses_english_letter_labels_and_ltr(): void
    {
        $owner = $this->user();
        $spec = $this->letterSpec([
            ['type' => 'kpi_row', 'items' => [['label' => 'Total cost', 'value' => 'Nine billion'], ['label' => 'Revenue', 'value' => 'Ten billion']]],
            ['type' => 'callout', 'variant' => 'decision', 'heading' => 'Approve budget', 'paragraphs' => ['Pass it now.']],
        ], 'en');
        $report = $this->report($owner, ['spec' => $spec]);
        $text = "Cost report\nTotal cost: Nine billion\nApprove budget: Pass it now.\nAssumptions and limitations: Data runs to October.";

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->assertSeeHtml('wire:key="ai-report-letter" dir="ltr"')
            ->assertSeeHtml('Total cost')
            ->assertSeeHtml('Decision')
            ->assertSeeHtml('Assumptions and limitations')
            ->assertSeeHtml('data-text="' . e($text) . '"');
    }

    public function test_a_generating_or_failed_report_renders_no_letter_card(): void
    {
        $owner = $this->user();

        $generating = $this->generatingReport($owner, ['spec' => $this->letterSpec()]);
        Livewire::actingAs($owner)->test(Main::class, ['report' => $generating])
            ->assertDontSeeHtml('wire:key="ai-report-letter"');

        $failed = $this->report($owner, ['status' => AiReport::STATUS_FAILED, 'spec' => $this->letterSpec()]);
        Livewire::actingAs($owner)->test(Main::class, ['report' => $failed])
            ->assertDontSeeHtml('wire:key="ai-report-letter"');
    }

    public function test_the_header_links_to_the_organizational_reports_tab(): void
    {
        $owner = $this->user();

        Livewire::actingAs($owner)->test(Main::class)
            ->assertSeeHtml('wire:key="ai-report-reports-link"')
            ->assertSeeHtml('/knowledge')
            ->assertSeeHtml('گزارشات سازمانی');
    }

    public function test_the_side_list_pages_by_five_and_the_table_view_by_thirty_and_a_view_switch_resets_the_step(): void
    {
        $owner = $this->user();

        Livewire::actingAs($owner)->test(Main::class)
            ->assertSet('listLimit', 5)
            ->call('loadMoreReports')->assertSet('listLimit', 10)
            ->call('toggleView', 'table')->assertSet('listLimit', 30)
            ->call('loadMoreReports')->assertSet('listLimit', 60)
            ->call('toggleView', 'compose')->assertSet('listLimit', 5);
    }

    public function test_the_list_is_ordered_by_id_descending_and_not_reshuffled_by_touches(): void
    {
        $owner = $this->user();
        $first = $this->report($owner);
        $second = $this->report($owner);
        $third = $this->report($owner);
        AiReport::whereKey($first->id)->update(['updated_at' => now()->addHour()]);

        $ids = Livewire::actingAs($owner)->test(Main::class)->instance()->reports->pluck('id')->all();

        $this->assertSame([$third->id, $second->id, $first->id], $ids);
    }

    public function test_loading_the_list_fails_a_stale_generating_row_and_refunds_once(): void
    {
        $owner = $this->user();
        $stale = $this->generatingReport($owner);
        AiReport::consumeQuota($owner->id);
        AiReport::whereKey($stale->id)->update(['updated_at' => now()->subMinutes(AiReport::staleMinutes() + 1)]);

        $row = Livewire::actingAs($owner)->test(Main::class)->instance()->reports->firstWhere('id', $stale->id);

        $this->assertSame(AiReport::STATUS_FAILED, $row->status);
        $this->assertSame('stale', $row->fail_reason);
        $this->assertSame(AiReport::STATUS_FAILED, $stale->fresh()->status);
        $this->assertSame(0, AiReport::quotaUsed($owner->id));
    }

    public function test_generating_flags_follow_the_loaded_list(): void
    {
        $owner = $this->user();
        $other = $this->user();

        $idle = Livewire::actingAs($owner)->test(Main::class)->instance();
        $this->assertFalse($idle->hasGenerating);

        $this->generatingReport($owner);
        $busy = Livewire::actingAs($owner)->test(Main::class)->instance();
        $this->assertTrue($busy->hasGenerating);

        $unrelated = Livewire::actingAs($other)->test(Main::class)->instance();
        $this->assertFalse($unrelated->hasGenerating);
    }

    public function test_a_stale_generating_row_does_not_count_as_generating(): void
    {
        $owner = $this->user();
        $stale = $this->generatingReport($owner);
        AiReport::whereKey($stale->id)->update(['updated_at' => now()->subMinutes(AiReport::staleMinutes() + 1)]);

        $component = Livewire::actingAs($owner)->test(Main::class)->instance();

        $this->assertFalse($component->hasGenerating);
    }

    public function test_a_valid_r_parameter_opens_the_report_and_an_invalid_one_resets_with_a_warning(): void
    {
        $owner = $this->user();
        $stranger = $this->user();
        $report = $this->report($owner);

        Livewire::withQueryParams(['r' => $report->id]);
        Livewire::actingAs($owner)->test(Main::class)
            ->assertSet('reportId', $report->id)
            ->assertSet('readOnly', false);

        Livewire::actingAs($stranger)->test(Main::class)
            ->assertOk()
            ->assertSet('reportId', null)
            ->assertDispatched('toast', $this->toastType('warning'));

        Livewire::withQueryParams(['r' => 999999999]);
        Livewire::actingAs($owner)->test(Main::class)
            ->assertOk()
            ->assertSet('reportId', null)
            ->assertDispatched('toast', $this->toastType('warning'));
    }

    public function test_actions_on_a_report_that_became_unavailable_toast_and_close(): void
    {
        $owner = $this->user();

        foreach (['save', 'pollStatus', 'delete', 'rename', 'share', 'refine'] as $method) {
            $report = $this->report($owner);
            $component = Livewire::actingAs($owner)->test(Main::class, ['report' => $report]);
            $report->delete();

            $component->call($method)
                ->assertDispatched('toast', $this->toastType('warning'))
                ->assertSet('reportId', null);
        }

        Livewire::actingAs($owner)->test(Main::class)->call('save')->assertNotDispatched('toast');
    }

    public function test_a_route_bound_report_that_is_no_longer_viewable_renders_the_page_with_a_toast(): void
    {
        $owner = $this->user();
        $recipient = $this->user();
        $report = $this->report($owner, ['recipients' => [(int) $recipient->id], 'active' => false]);

        Livewire::actingAs($recipient)->test(Main::class, ['report' => $report])
            ->assertOk()
            ->assertSet('reportId', null)
            ->assertDispatched('toast', $this->toastType('warning'));
    }

    public function test_the_mine_filter_excludes_received_rows_and_the_shared_filter_excludes_own_rows(): void
    {
        $owner = $this->user();
        $viewer = $this->user();
        $mine = $this->report($viewer, ['title' => 'Mine']);
        $received = $this->report($owner, ['title' => 'Received', 'recipients' => [(int) $viewer->id]]);

        $component = Livewire::actingAs($viewer)->test(Main::class)->call('toggleView', 'table');
        $this->assertEqualsCanonicalizing([$mine->id, $received->id], $component->instance()->reports->pluck('id')->all());

        $component->call('setFilter', 'mine');
        $this->assertSame([$mine->id], $component->instance()->reports->pluck('id')->all());

        $component->call('setFilter', 'shared');
        $this->assertSame([$received->id], $component->instance()->reports->pluck('id')->all());

        $component->call('setFilter', 'all');
        $this->assertEqualsCanonicalizing([$mine->id, $received->id], $component->instance()->reports->pluck('id')->all());
    }

    public function test_the_inactive_filter_returns_only_the_owners_inactive_rows_and_never_a_recipients(): void
    {
        $owner = $this->user();
        $viewer = $this->user();
        $mineOff = $this->report($viewer, ['active' => false]);
        $receivedOff = $this->report($owner, ['recipients' => [(int) $viewer->id], 'active' => false]);
        $mineOn = $this->report($viewer);

        $component = Livewire::actingAs($viewer)->test(Main::class)->call('toggleView', 'table')->call('setFilter', 'inactive');
        $this->assertSame([$mineOff->id], $component->instance()->reports->pluck('id')->all());

        $component->call('setFilter', 'all');
        $ids = $component->instance()->reports->pluck('id')->all();
        $this->assertNotContains($mineOff->id, $ids);
        $this->assertNotContains($receivedOff->id, $ids);
        $this->assertContains($mineOn->id, $ids);
    }

    public function test_the_draft_and_saved_filters_split_on_saved_at(): void
    {
        $owner = $this->user();
        $draft = $this->report($owner, ['saved_at' => null]);
        $saved = $this->report($owner);

        $component = Livewire::actingAs($owner)->test(Main::class)->call('toggleView', 'table')->call('setFilter', 'draft');
        $this->assertSame([$draft->id], $component->instance()->reports->pluck('id')->all());

        $component->call('setFilter', 'saved');
        $this->assertSame([$saved->id], $component->instance()->reports->pluck('id')->all());
    }

    public function test_title_search_narrows_the_table_treats_wildcards_literally_and_resets_the_limit(): void
    {
        $owner = $this->user();
        $alpha = $this->report($owner, ['title' => 'Alpha budget']);
        $beta = $this->report($owner, ['title' => 'Beta']);
        $wild = $this->report($owner, ['title' => '50%_plan']);

        $component = Livewire::actingAs($owner)->test(Main::class)->call('toggleView', 'table')->call('loadMoreReports');

        $component->set('search', 'alpha');
        $this->assertSame([$alpha->id], $component->instance()->reports->pluck('id')->all());
        $component->assertSet('listLimit', 30);

        $component->set('search', 'ALPHA');
        $this->assertSame([$alpha->id], $component->instance()->reports->pluck('id')->all());

        $component->set('search', '%_');
        $this->assertSame([$wild->id], $component->instance()->reports->pluck('id')->all());

        $component->set('search', 'zz nothing zz');
        $this->assertSame([], $component->instance()->reports->pluck('id')->all());

        $component->set('search', str_repeat('x', 150));
        $component->assertSet('search', str_repeat('x', 100));
    }

    public function test_the_table_tab_renders_the_table_branch_and_filters_are_ignored_in_compose(): void
    {
        $owner = $this->user();
        $viewer = $this->user();
        $received = $this->report($owner, ['recipients' => [(int) $viewer->id]]);

        Livewire::withQueryParams(['tab' => 'table', 'f' => 'mine']);
        $component = Livewire::actingAs($viewer)->test(Main::class)
            ->assertSet('activeTab', 'table')
            ->assertSet('filter', 'mine')
            ->assertSeeHtml('wire:key="ai-report-view-table"')
            ->assertDontSeeHtml('wire:key="ai-report-view-compose"')
            ->assertSeeHtml('wire:key="ai-report-filter-mine"');

        $component->call('toggleView', 'compose')
            ->assertSeeHtml('wire:key="ai-report-view-compose"')
            ->assertDontSeeHtml('wire:key="ai-report-view-table"');
        $this->assertContains($received->id, $component->instance()->reports->pluck('id')->all());

        Livewire::withQueryParams(['tab' => 'bogus', 'f' => 'bogus']);
        Livewire::actingAs($viewer)->test(Main::class)
            ->assertSet('activeTab', 'compose')
            ->assertSet('filter', 'all');
    }

    public function test_composer_rejects_unknown_presets_and_stores_valid_ones_on_the_version_and_the_row(): void
    {
        Bus::fake();
        $owner = $this->user();

        $component = Livewire::actingAs($owner)->test(Main::class)
            ->set('form.prompt', 'a prompt that is long enough')
            ->set('form.type', 'bogus')
            ->call('generate')
            ->assertHasErrors(['form.type']);
        $this->assertSame(0, AiReport::query()->ownedBy($owner->id)->count());

        $component->set('form.type', 'budget')->set('form.length', 'one_page')->set('form.audience', 'technical')
            ->call('generate')
            ->assertHasNoErrors();

        $created = AiReport::query()->ownedBy($owner->id)->latest('id')->firstOrFail();
        $version = $created->lastVersion();
        $this->assertSame(['budget', 'one_page', 'technical'], [$version['type'], $version['length'], $version['audience']]);
        $this->assertSame(['budget', 'one_page', 'technical'], [$created->preset_type, $created->preset_length, $created->preset_audience]);
    }

    public function test_refine_inherits_the_presets_and_a_fork_writes_its_own_row_columns(): void
    {
        Bus::fake();
        $owner = $this->user();
        $recipient = $this->user();
        $presets = ['type' => 'budget', 'length' => 'detailed', 'audience' => 'technical'];
        $version = ['prompt' => 'p', 'files' => [], ...$presets, 'spec' => $this->plainSpec('Base'), 'at' => now()->toIso8601String()];
        $columns = ['preset_type' => 'budget', 'preset_length' => 'detailed', 'preset_audience' => 'technical'];
        $plain = $this->report($owner, ['spec' => $this->plainSpec('Base'), 'versions' => [$version], ...$columns]);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $plain])
            ->assertSet('form.type', 'budget')
            ->assertSet('form.length', 'detailed')
            ->assertSet('form.audience', 'technical')
            ->set('form.type', 'monthly')
            ->set('form.length', 'one_page')
            ->set('form.audience', 'mixed')
            ->set('form.prompt', 'make the report shorter please')
            ->call('refine');

        $fresh = $plain->fresh();
        $this->assertCount(2, $fresh->versions);
        $last = $fresh->lastVersion();
        $this->assertSame(['monthly', 'one_page', 'mixed'], [$last['type'], $last['length'], $last['audience']]);
        $this->assertSame(array_values($columns), [$fresh->preset_type, $fresh->preset_length, $fresh->preset_audience]);

        config(['ai.gateway.model' => 'Gemini-3-Flash-Preview', 'ai.gateway.max_output' => 16384]);
        Http::fake(['*' => Http::response([
            'choices' => [['message' => ['content' => json_encode(['lang' => 'fa', 'title' => 'عنوان آزمایشی', 'pages' => [['blocks' => [['type' => 'title', 'heading' => 'عنوان آزمایشی']]]]])], 'finish_reason' => 'stop']],
            'usage' => ['total_tokens' => 3],
        ])]);
        (new GenerateReportJob($plain->id))->handle(app(ReportAiService::class), app(FileExtractor::class));
        $done = $plain->fresh();
        $this->assertSame(AiReport::STATUS_READY, $done->status);
        $this->assertSame(['monthly', 'one_page', 'mixed'], [$done->preset_type, $done->preset_length, $done->preset_audience]);

        $failing = $this->report($owner, ['spec' => $this->plainSpec('Base'), 'versions' => [$version], ...$columns]);
        Livewire::actingAs($owner)->test(Main::class, ['report' => $failing])
            ->set('form.type', 'meeting')
            ->set('form.prompt', 'make the report shorter please')
            ->call('refine');
        $this->assertSame('meeting', $failing->fresh()->lastVersion()['type']);
        $this->assertTrue($failing->fresh()->markFailed('timeout'));
        $failed = $failing->fresh();
        $this->assertSame(array_values($columns), [$failed->preset_type, $failed->preset_length, $failed->preset_audience]);

        $other = $this->user();
        $shared = $this->report($other, ['spec' => $this->plainSpec('Base'), 'versions' => [$version], 'recipients' => [(int) $recipient->id], ...$columns]);
        Livewire::actingAs($other)->test(Main::class, ['report' => $shared])
            ->set('form.length', 'one_page')
            ->set('form.prompt', 'make the report shorter please')
            ->call('refine');

        $fork = AiReport::query()->ownedBy($other->id)->where('id', '!=', $shared->id)->firstOrFail();
        $this->assertSame(['budget', 'one_page', 'technical'], [$fork->preset_type, $fork->preset_length, $fork->preset_audience]);
        $this->assertSame('one_page', $fork->lastVersion()['length']);
        $this->assertSame('detailed', $shared->fresh()->preset_length);
    }

    public function test_the_job_forwards_presets_into_the_system_prompt_and_free_adds_no_type_line(): void
    {
        config(['ai.gateway.model' => 'Gemini-3-Flash-Preview', 'ai.gateway.max_output' => 16384]);
        $owner = $this->user();
        $spec = ['lang' => 'fa', 'title' => 'عنوان آزمایشی', 'pages' => [['blocks' => [['type' => 'title', 'heading' => 'عنوان آزمایشی']]]]];
        Http::fake(['*' => Http::response([
            'choices' => [['message' => ['content' => json_encode($spec)], 'finish_reason' => 'stop']],
            'usage' => ['total_tokens' => 3],
        ])]);
        $plant = fn (string $type) => $this->report($owner, [
            'status' => AiReport::STATUS_GENERATING,
            'saved_at' => null,
            'spec' => null,
            'versions' => [['prompt' => 'یک درخواست آزمایشی', 'files' => [], 'type' => $type, 'length' => 'detailed', 'audience' => 'mixed', 'at' => now()->toIso8601String()]],
        ]);

        (new GenerateReportJob($plant('incident')->id))->handle(app(ReportAiService::class), app(FileExtractor::class));
        (new GenerateReportJob($plant('free')->id))->handle(app(ReportAiService::class), app(FileExtractor::class));

        Http::assertSentCount(2);
        $tokens = ['{blocks}' => (int) config('ai.report.spec.blocks'), '{one_page_blocks}' => (int) config('ai.report.spec.one_page_blocks')];
        Http::assertSent(function ($request) use ($tokens) {
            $system = $request['messages'][0]['content'];

            return str_contains($system, 'REPORT TYPE: required block order for pages[0]')
                && str_contains($system, strtr(config('ai.report.types.incident.rules'), $tokens))
                && str_contains($system, 'LENGTH: ' . strtr(config('ai.report.lengths.detailed.rules'), $tokens))
                && str_contains($system, 'AUDIENCE: ' . strtr(config('ai.report.audiences.mixed.rules'), $tokens));
        });
        Http::assertSent(function ($request) use ($tokens) {
            $system = $request['messages'][0]['content'];

            return !str_contains($system, 'REPORT TYPE:')
                && str_contains($system, 'LENGTH: ' . strtr(config('ai.report.lengths.detailed.rules'), $tokens))
                && str_contains($system, 'AUDIENCE: ' . strtr(config('ai.report.audiences.mixed.rules'), $tokens));
        });
    }

    public function test_select_prefills_the_last_versions_presets_for_the_owner_only_and_titles_the_version(): void
    {
        $owner = $this->user();
        $viewer = $this->user();
        $versions = [
            ['prompt' => 'p', 'files' => [], 'spec' => ['lang' => 'fa'], 'at' => now()->toIso8601String()],
            ['prompt' => 'p', 'files' => [], 'type' => 'meeting', 'length' => 'one_page', 'audience' => 'technical', 'spec' => ['lang' => 'fa'], 'at' => now()->toIso8601String()],
        ];
        $shared = $this->report($owner, ['recipients' => [(int) $viewer->id], 'versions' => $versions]);

        Livewire::actingAs($owner)->withQueryParams(['r' => $shared->id])->test(Main::class)
            ->assertSet('form.type', 'meeting')
            ->assertSet('form.length', 'one_page')
            ->assertSet('form.audience', 'technical');
        Livewire::actingAs($viewer)->withQueryParams(['r' => $shared->id])->test(Main::class)
            ->assertSet('readOnly', true)
            ->assertSet('form.type', 'free')
            ->assertSet('form.length', 'standard')
            ->assertSet('form.audience', 'management');

        $ghost = $this->report($owner, ['versions' => [['prompt' => 'p', 'files' => [], 'type' => 'ghost', 'length' => 'ghost', 'audience' => 'ghost', 'spec' => ['lang' => 'fa'], 'at' => now()->toIso8601String()]]]);
        Livewire::actingAs($owner)->withQueryParams(['r' => $ghost->id])->test(Main::class)
            ->assertSet('form.type', 'free')
            ->assertSet('form.length', 'standard')
            ->assertSet('form.audience', 'management');

        $rail = (new AiReportPresenter())->versions($shared);
        $this->assertNull($rail[0]['type']);
        $this->assertSame(config('ai.report.types.meeting.label'), $rail[1]['type']);
        $this->assertSame('صورت‌جلسه', $rail[1]['type']);
    }

    public function test_reverting_to_a_pre_preset_version_clears_the_row_presets_and_reverting_forward_restores_them(): void
    {
        $owner = $this->user();
        $versions = [
            ['prompt' => 'p', 'files' => [], 'spec' => $this->plainSpec('First'), 'at' => now()->toIso8601String()],
            ['prompt' => 'p', 'files' => [], 'type' => 'budget', 'length' => 'detailed', 'audience' => 'technical', 'spec' => $this->plainSpec('Second'), 'at' => now()->toIso8601String()],
        ];
        $report = $this->report($owner, [
            'spec' => $this->plainSpec('Second'),
            'title' => 'Second',
            'versions' => $versions,
            'preset_type' => 'budget',
            'preset_length' => 'detailed',
            'preset_audience' => 'technical',
        ]);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])->call('revert', 0);
        $cleared = $report->fresh();
        $this->assertSame('First', $cleared->title);
        $this->assertSame([null, null, null], [$cleared->preset_type, $cleared->preset_length, $cleared->preset_audience]);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $cleared])->call('revert', 1);
        $restored = $report->fresh();
        $this->assertSame('Second', $restored->title);
        $this->assertSame(['budget', 'detailed', 'technical'], [$restored->preset_type, $restored->preset_length, $restored->preset_audience]);
    }

    public function test_the_table_shows_preset_labels_and_dashes_and_compose_mode_never_selects_them(): void
    {
        $owner = $this->user();
        $old = $this->report($owner);
        $new = $this->report($owner, ['preset_type' => 'budget', 'preset_length' => 'detailed', 'preset_audience' => 'technical']);

        $table = Livewire::actingAs($owner)->withQueryParams(['tab' => 'table'])->test(Main::class)
            ->assertSeeHtml('<bdi>گزارش بودجه و هزینه</bdi>')
            ->assertSeeHtml('<bdi>مفصل (حدود ۲ تا ۴ صفحه)</bdi>')
            ->assertSeeHtml('<bdi>کارشناسان</bdi>')
            ->assertDontSeeHtml('<bdi>آزاد (طبق درخواست شما)</bdi>');
        $html = $table->html();
        $this->assertSame(3, substr_count($html, '<bdi>—</bdi>'));
        foreach (['type', 'length', 'audience'] as $col) {
            $this->assertSame(3, substr_count($html, 'data-col="' . $col . '"'), $col);
        }

        $rows = $table->instance()->reports;
        $this->assertCount(2, $rows);
        $presenter = new AiReportPresenter();
        $this->assertSame(['گزارش بودجه و هزینه', 'مفصل (حدود ۲ تا ۴ صفحه)', 'کارشناسان'], array_values($presenter->presets($rows->firstWhere('id', $new->id))));
        $this->assertSame(['—', '—', '—'], array_values($presenter->presets($rows->firstWhere('id', $old->id))));

        Livewire::withQueryParams([]);
        $composeComponent = Livewire::actingAs($owner)->test(Main::class)->assertSet('activeTab', 'compose');
        $compose = $composeComponent->instance()->reports;
        $this->assertCount(2, $compose);
        $this->assertNull($compose->firstWhere('id', $new->id)->getAttribute('preset_type'));
    }

    public function test_the_view_toggle_reads_the_livewire_state_and_the_table_wrapper_stays_stable_across_polling(): void
    {
        $owner = $this->user();
        $generating = $this->generatingReport($owner);

        Livewire::actingAs($owner)->test(Main::class)
            ->assertSeeHtml('$wire.activeTab ===')
            ->assertDontSeeHtml(':class="activeTab ===')
            ->assertDontSeeHtml('ai-report-table-poll-anchor');

        $component = Livewire::actingAs($owner)->test(Main::class)->call('toggleView', 'table');
        $component
            ->assertSeeHtml('wire:key="ai-report-view-table"')
            ->assertSeeHtml('wire:key="ai-report-table-poll-anchor"')
            ->assertSeeHtml('wire:poll.visible.' . config('ai.report.poll_seconds') . 's="pollList"');

        AiReport::whereKey($generating->id)->update(['status' => AiReport::STATUS_READY, 'saved_at' => now()]);
        $component->call('pollList')
            ->assertSeeHtml('wire:key="ai-report-view-table"')
            ->assertDontSeeHtml('ai-report-table-poll-anchor');
    }

    public function test_opening_a_report_leaves_the_table_for_the_report_tab_and_never_leaves_the_side_list_style(): void
    {
        $owner = $this->user();
        $report = $this->report($owner);

        Livewire::withQueryParams(['tab' => 'table', 'r' => $report->id]);
        Livewire::actingAs($owner)->test(Main::class)
            ->assertSet('activeTab', 'report')
            ->assertSet('reportId', $report->id);

        Livewire::withQueryParams([]);
        Livewire::actingAs($owner)->test(Main::class)
            ->call('toggleView', 'table')
            ->call('open', $report->id)
            ->assertSet('activeTab', 'report')
            ->assertSet('reportId', $report->id);

        Livewire::actingAs($owner)->test(Main::class)
            ->call('open', $report->id)
            ->assertSet('activeTab', 'compose');
    }

    public function test_the_tabbed_style_has_a_report_tab_without_a_side_list_and_a_table_tab_and_both_stay_reachable(): void
    {
        $owner = $this->user();
        $report = $this->report($owner);

        $component = Livewire::actingAs($owner)->test(Main::class)
            ->call('toggleView', 'report')
            ->assertSet('activeTab', 'report')
            ->assertSet('listLimit', 0)
            ->assertSeeHtml('wire:key="ai-report-view-report"')
            ->assertSeeHtml('wire:click="switchTab(\'table\')"')
            ->assertSeeHtml('wire:key="ai-report-composer"')
            ->assertDontSeeHtml('wire:key="ai-report-view-compose"')
            ->assertDontSeeHtml('wire:key="ai-report-list-idle"')
            ->assertDontSeeHtml('wire:key="ai-report-list-tabs"');
        $this->assertCount(0, $component->instance()->reports);

        $component->call('switchTab', 'table')
            ->assertSet('activeTab', 'table')
            ->assertSet('listLimit', 30)
            ->assertSeeHtml('wire:key="ai-report-view-table"')
            ->assertSeeHtml('wire:click="switchTab(\'report\')"')
            ->assertDontSeeHtml('wire:key="ai-report-view-report"');
        $this->assertSame([$report->id], $component->instance()->reports->pluck('id')->all());

        $component->call('switchTab', 'compose')
            ->assertSet('activeTab', 'compose')
            ->assertSet('listLimit', 5)
            ->assertSeeHtml('wire:key="ai-report-list-idle"')
            ->assertDontSeeHtml('wire:key="ai-report-view-tabs"');
        $component->call('switchTab', 'bogus')->assertSet('activeTab', 'compose');
    }

    public function test_the_view_toggle_highlights_the_tabbed_style_for_both_of_its_tabs(): void
    {
        $owner = $this->user();

        Livewire::actingAs($owner)->test(Main::class)
            ->assertSeeHtml(':class="$wire.activeTab === &#039;compose&#039; ?')
            ->assertSeeHtml(':class="$wire.activeTab === &#039;report&#039; || $wire.activeTab === &#039;table&#039; ?');
    }

    public function test_the_report_tab_keeps_the_generating_lock_refreshing_without_a_second_poller_on_the_generating_report(): void
    {
        $owner = $this->user();
        $ready = $this->report($owner);
        $generating = $this->generatingReport($owner);

        $component = Livewire::actingAs($owner)->test(Main::class)
            ->call('toggleView', 'report')
            ->assertSeeHtml('wire:key="ai-report-table-poll-anchor"')
            ->assertSeeHtml('wire:poll.visible.' . config('ai.report.poll_seconds') . 's="pollList"')
            ->assertDontSeeHtml('wire:key="ai-report-viewer-generating"');

        $component->call('open', $generating->id)
            ->assertSet('activeTab', 'report')
            ->assertSeeHtml('wire:key="ai-report-viewer-generating"')
            ->assertDontSeeHtml('wire:key="ai-report-table-poll-anchor"');

        $component->call('open', $ready->id)->assertSeeHtml('wire:key="ai-report-table-poll-anchor"');

        AiReport::whereKey($generating->id)->update(['status' => AiReport::STATUS_READY, 'saved_at' => now()]);
        $component->call('pollList')->assertDontSeeHtml('wire:key="ai-report-table-poll-anchor"');
        $this->assertFalse($component->instance()->hasGenerating);
    }

    public function test_pinned_rows_lead_the_table_and_client_ids_are_cleaned_capped_and_cannot_reveal_hidden_reports(): void
    {
        $owner = $this->user();
        $stranger = $this->user();
        $first = $this->report($owner, ['title' => 'First']);
        $second = $this->report($owner, ['title' => 'Second']);
        $third = $this->report($owner, ['title' => 'Third']);
        $hidden = $this->report($stranger, ['title' => 'Hidden']);

        $component = Livewire::actingAs($owner)->test(Main::class)->call('toggleView', 'table');
        $this->assertSame([$third->id, $second->id, $first->id], $component->instance()->reports->pluck('id')->all());

        $component->call('syncPinned', [$first->id, (string) $hidden->id, 'x', -3, 0, $first->id, [9]]);
        $this->assertSame([$first->id, $hidden->id], $component->instance()->pinned);
        $this->assertSame([$first->id, $third->id, $second->id], $component->instance()->reports->pluck('id')->all());

        $component->call('toggleView', 'compose')->call('toggleView', 'table');
        $this->assertSame([$first->id, $third->id, $second->id], $component->instance()->reports->pluck('id')->all());

        $component->call('syncPinned', range(1, 80));
        $this->assertCount(50, $component->instance()->pinned);

        $component->call('syncPinned', []);
        $this->assertSame([$third->id, $second->id, $first->id], $component->instance()->reports->pluck('id')->all());
    }

    public function test_sort_by_title_toggles_direction_and_is_whitelisted(): void
    {
        $owner = $this->user();
        $first = $this->report($owner, ['title' => 'Bbb first']);
        $second = $this->report($owner, ['title' => 'Aaa second']);

        $component = Livewire::actingAs($owner)->test(Main::class)->call('toggleView', 'table');

        $component->call('sortBy', 'title')->assertSet('sort', 'title')->assertSet('sortDir', 'asc');
        $this->assertSame([$second->id, $first->id], $component->instance()->reports->pluck('id')->all());

        $component->call('sortBy', 'title')->assertSet('sortDir', 'desc');
        $this->assertSame([$first->id, $second->id], $component->instance()->reports->pluck('id')->all());

        $component->call('sortBy', 'nope')->assertSet('sort', 'title')->assertSet('sortDir', 'desc');

        $component->call('toggleView', 'compose')->assertSet('activeTab', 'compose');
        $this->assertSame([$second->id, $first->id], $component->instance()->reports->pluck('id')->all());
    }

    public function test_the_classic_list_defaults_to_the_active_tab_and_the_inactive_tab_shows_only_the_owners_inactive_reports(): void
    {
        $owner = $this->user();
        $other = $this->user();
        $activeOne = $this->report($owner, ['title' => 'Active one']);
        $activeTwo = $this->report($owner, ['title' => 'Active two']);
        $inactive = $this->report($owner, ['title' => 'Inactive mine', 'active' => false]);
        $this->report($other, ['title' => 'Inactive received', 'recipients' => [(int) $owner->id], 'active' => false]);

        $component = Livewire::actingAs($owner)->test(Main::class)
            ->assertSet('listTab', 'active')
            ->assertSeeHtml('wire:key="ai-report-list-tabs"')
            ->assertSeeHtml('wire:click="switchTab(\'inactive\')"')
            ->assertSeeHtml('wire:key="ai-report-list-' . $activeOne->id . '"')
            ->assertDontSeeHtml('wire:key="ai-report-list-' . $inactive->id . '"');
        $this->assertSame([$activeTwo->id, $activeOne->id], $component->instance()->reports->pluck('id')->all());

        $component->call('switchTab', 'inactive')
            ->assertSet('listTab', 'inactive')
            ->assertSeeHtml('wire:key="ai-report-list-' . $inactive->id . '"')
            ->assertDontSeeHtml('wire:key="ai-report-list-' . $activeOne->id . '"')
            ->assertDontSeeHtml('ai-report-empty-list-inactive');
        $this->assertSame([$inactive->id], $component->instance()->reports->pluck('id')->all());

        $component->call('switchTab', 'bogus')->assertSet('listTab', 'inactive');
        $component->call('switchTab', 'active')->assertSet('listTab', 'active');
        $this->assertSame([$activeTwo->id, $activeOne->id], $component->instance()->reports->pluck('id')->all());

        Livewire::actingAs($this->user())->test(Main::class)
            ->call('switchTab', 'inactive')
            ->assertSeeHtml('wire:key="ai-report-empty-list-inactive"')
            ->assertSee('گزارش غیرفعالی ندارید')
            ->assertDontSeeHtml('wire:key="ai-report-empty-list-active"')
            ->call('switchTab', 'active')
            ->assertSeeHtml('wire:key="ai-report-empty-list-active"')
            ->assertDontSeeHtml('wire:key="ai-report-empty-list-inactive"');
    }

    public function test_switching_the_list_tab_resets_the_limit_and_changes_the_list_signature(): void
    {
        config(['ai.report.list_max' => 7]);
        $owner = $this->user();
        foreach (range(1, 6) as $i) {
            $this->report($owner);
        }
        $this->report($owner, ['active' => false]);

        $component = Livewire::actingAs($owner)->test(Main::class)->call('loadMoreReports')->assertSet('listLimit', 7);
        $before = $component->instance()->listSignature;
        $this->assertStringContainsString('|active|', $before);

        $component->call('switchTab', 'inactive')->assertSet('listLimit', 5);
        $this->assertCount(1, $component->instance()->reports);
        $this->assertStringContainsString('|inactive|', $component->instance()->listSignature);
        $this->assertNotSame($before, $component->instance()->listSignature);
    }

    public function test_a_generating_inactive_report_still_locks_the_composer_from_either_list_tab(): void
    {
        $owner = $this->user();
        $other = $this->user();
        $generating = $this->generatingReport($owner, ['active' => false]);
        $this->generatingReport($other);

        $component = Livewire::actingAs($owner)->test(Main::class);
        $this->assertNotContains($generating->id, $component->instance()->reports->pluck('id')->all());
        $this->assertTrue($component->instance()->hasGenerating);
        $component->assertSeeHtml('wire:key="ai-report-list-polling"');

        $component->call('switchTab', 'inactive');
        $this->assertSame([$generating->id], $component->instance()->reports->pluck('id')->all());
        $this->assertTrue($component->instance()->hasGenerating);

        AiReport::whereKey($generating->id)->update(['updated_at' => now()->subMinutes(AiReport::staleMinutes() + 1)]);
        $component->call('pollList');
        $this->assertFalse($component->instance()->hasGenerating);
        $this->assertTrue(Livewire::actingAs($other)->test(Main::class)->instance()->hasGenerating);
        $this->assertFalse(Livewire::actingAs($this->user())->test(Main::class)->instance()->hasGenerating);
    }

    public function test_the_poll_stops_and_the_lock_clears_when_a_generating_report_outside_the_visible_rows_finishes(): void
    {
        $owner = $this->user();
        $hidden = $this->generatingReport($owner, ['active' => false]);

        $compose = Livewire::actingAs($owner)->test(Main::class)
            ->assertSeeHtml('wire:key="ai-report-list-polling"');
        $this->assertNotContains($hidden->id, $compose->instance()->reports->pluck('id')->all());

        AiReport::whereKey($hidden->id)->update(['status' => AiReport::STATUS_READY]);
        $compose->call('pollList')
            ->assertDontSeeHtml('wire:key="ai-report-list-polling"')
            ->assertSeeHtml('wire:key="ai-report-list-idle"');
        $this->assertFalse($compose->instance()->hasGenerating);

        $draft = $this->generatingReport($owner);
        $table = Livewire::actingAs($owner)->test(Main::class)
            ->call('toggleView', 'table')
            ->call('setFilter', 'saved')
            ->assertSeeHtml('wire:key="ai-report-table-poll-anchor"');
        $this->assertNotContains($draft->id, $table->instance()->reports->pluck('id')->all());

        AiReport::whereKey($draft->id)->update(['status' => AiReport::STATUS_READY]);
        $table->call('pollList')->assertDontSeeHtml('wire:key="ai-report-table-poll-anchor"');
    }

    public function test_the_table_view_renders_the_tools_toolbar_and_data_col_markers(): void
    {
        $owner = $this->user();
        foreach (range(1, 3) as $i) {
            $this->report($owner);
        }

        $html = Livewire::actingAs($owner)->test(Main::class)
            ->call('toggleView', 'table')
            ->assertSeeHtml('wire:key="ai-report-table-tools"')
            ->assertSeeHtml('wire:key="ai-report-table-sort"')
            ->assertSeeHtml('x-data="aiReportTable"')
            ->assertSeeHtml('ai-report-table ')
            ->html();

        foreach (['status', 'owner', 'recipients', 'updated', 'state', 'active'] as $col) {
            $this->assertSame(4, substr_count($html, 'data-col="' . $col . '"'), $col);
        }
        $this->assertSame(0, substr_count($html, 'data-col="title"'));

        $empty = Livewire::actingAs($this->user())->test(Main::class)
            ->call('toggleView', 'table')
            ->assertSeeHtml('wire:key="ai-report-table-empty"')
            ->assertSeeHtml('wire:key="ai-report-table-tools"')
            ->html();
        $this->assertSame(1, substr_count($empty, 'data-col="status"'));
    }

    public function test_reset_sort_restores_the_default_order_and_clears_the_sort_marker(): void
    {
        $owner = $this->user();
        $first = $this->report($owner, ['title' => 'Aaa first']);
        $second = $this->report($owner, ['title' => 'Bbb second']);
        $third = $this->report($owner, ['title' => 'Ccc third']);
        $fourth = $this->report($owner, ['title' => 'Ddd fourth']);

        $component = Livewire::actingAs($owner)->test(Main::class)->call('toggleView', 'table')
            ->assertDontSeeHtml('table-sort-dot')
            ->call('sortBy', 'title')
            ->assertSet('sort', 'title')
            ->assertSeeHtml('table-sort-dot');
        $this->assertSame([$first->id, $second->id, $third->id, $fourth->id], $component->instance()->reports->pluck('id')->all());

        $component->call('loadMoreReports')->assertSet('listLimit', 60);

        $component->call('resetSort')
            ->assertSet('sort', 'id')
            ->assertSet('sortDir', 'desc')
            ->assertSet('listLimit', 30)
            ->assertDontSeeHtml('table-sort-dot');
        $this->assertSame([$fourth->id, $third->id, $second->id, $first->id], $component->instance()->reports->pluck('id')->all());
    }

    public function test_owner_can_toggle_active_and_recipients_lose_and_regain_access(): void
    {
        $owner = $this->user();
        $recipient = $this->user();
        $report = $this->report($owner, ['recipients' => [(int) $recipient->id]]);

        $component = Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->assertSeeHtml('wire:key="ai-report-composer-refine"')
            ->call('toggleActive')
            ->assertDispatched('toast', $this->toastContaining('غیرفعال شد'))
            ->assertSet('reportId', $report->id)
            ->assertSeeHtml('wire:key="ai-report-inactive-notice"')
            ->assertDontSeeHtml('wire:key="ai-report-composer-refine"');

        $this->assertFalse(AiReportAccessPolicy::canRefine($report->fresh(), $owner));
        $this->assertFalse($report->fresh()->active);
        $this->assertEmpty(AiReport::visibleTo($recipient)->pluck('id')->all());

        Livewire::actingAs($recipient)->test(Main::class, ['report' => $report->fresh()])
            ->assertSet('reportId', null)
            ->assertDispatched('toast', $this->toastType('warning'));

        Livewire::actingAs($owner);
        $component->call('toggleActive')
            ->assertDispatched('toast', $this->toastContaining('فعال شد'))
            ->assertDontSeeHtml('wire:key="ai-report-inactive-notice"')
            ->assertSeeHtml('wire:key="ai-report-composer-refine"');

        $this->assertTrue($report->fresh()->active);
        $this->assertContains($report->id, AiReport::visibleTo($recipient)->pluck('id')->all());
    }

    public function test_toggle_active_is_refused_while_generating_and_for_a_recipient(): void
    {
        $owner = $this->user();
        $recipient = $this->user();
        $generating = $this->generatingReport($owner);
        $shared = $this->report($owner, ['recipients' => [(int) $recipient->id]]);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $generating])
            ->call('toggleActive')
            ->assertDispatched('toast', $this->toastType('warning'));
        $this->assertTrue($generating->fresh()->active);

        Livewire::actingAs($recipient)->test(Main::class, ['report' => $shared])
            ->call('toggleActive')
            ->assertNotDispatched('toast');
        $this->assertTrue($shared->fresh()->active);

        $this->expectException(\RuntimeException::class);
        app(UpdateReportAction::class)->execute($shared, $recipient, ['active' => false]);
    }

    public function test_the_unseen_badge_and_open_follow_the_active_flag(): void
    {
        $owner = $this->user();
        $recipient = $this->user();
        $report = $this->report($owner, ['recipients' => [(int) $recipient->id]]);

        $this->assertTrue(AiReport::unseenBy($recipient->id)->exists());

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])->call('toggleActive');
        $this->assertFalse(AiReport::unseenBy($recipient->id)->exists());
        $this->assertFalse(AiReportAccessPolicy::canView($report->fresh(), $recipient));

        Livewire::actingAs($owner)->test(Main::class)->call('open', $report->id)->assertSet('reportId', $report->id);
        Livewire::actingAs($recipient)->test(Main::class)->call('open', $report->id)
            ->assertSet('reportId', null)
            ->assertDispatched('toast', $this->toastType('warning'));

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])->call('toggleActive');
        $this->assertTrue(AiReport::unseenBy($recipient->id)->exists());
    }

    public function test_share_replaces_the_recipient_list_and_reports_each_case(): void
    {
        $owner = $this->user();
        $first = $this->user();
        $second = $this->user();
        $inactive = $this->user(['status' => 'inactive']);
        $report = $this->report($owner, ['title' => 'Shared', 'recipients' => [(int) $first->id, (int) $second->id]]);

        $component = Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->assertSet('shareRecipients', [(string) $first->id, (string) $second->id]);

        $component->set('shareRecipients', [(string) $first->id])->call('share')
            ->assertDispatched('toast', $this->toastType('success'));
        $this->assertSame([(int) $first->id], $report->fresh()->recipients);

        $component->set('shareRecipients', [(string) $first->id, (string) $second->id])->call('share')
            ->assertDispatched('toast', $this->toastContaining(convertToPersian(1)));
        $this->assertSame([(int) $first->id, (int) $second->id], $report->fresh()->recipients);

        $component->set('shareRecipients', [])->call('share')->assertDispatched('toast', $this->toastType('success'));
        $this->assertEmpty($report->fresh()->recipients);

        $component->set('shareRecipients', [(string) $inactive->id])->call('share')->assertDispatched('toast', $this->toastType('warning'));
        $this->assertEmpty($report->fresh()->recipients);
    }

    public function test_resharing_the_identical_recipient_list_succeeds_both_times(): void
    {
        $owner = $this->user();
        $first = $this->user();
        $report = $this->report($owner, ['title' => 'Shared']);

        $component = Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->set('shareRecipients', [(string) $first->id])->call('share')
            ->assertDispatched('toast', $this->toastType('success'));

        $component->call('share')
            ->assertDispatched('toast', fn ($event, $params) => ($params['message'] ?? null) === 'فهرست گیرندگان تغییری نکرد.')
            ->assertNotDispatched('toast', fn ($event, $params) => ($params['message'] ?? null) === 'فقط مالک می‌تواند گزارش آماده و فعال خود را به اشتراک بگذارد.');
        $this->assertSame([(int) $first->id], $report->fresh()->recipients);
    }

    public function test_share_names_the_cap_when_the_request_is_truncated(): void
    {
        config(['ai.report.max_recipients' => 2]);
        $owner = $this->user();
        $people = [$this->user(), $this->user(), $this->user()];
        $report = $this->report($owner, ['title' => 'Shared']);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->set('shareRecipients', array_map(fn ($p) => (string) $p->id, $people))
            ->call('share')
            ->assertDispatched('toast', fn ($event, $params) => ($params['type'] ?? null) === 'warning'
                && str_contains((string) $params['message'], convertToPersian(config('ai.report.max_recipients'))));

        $this->assertCount(2, $report->fresh()->recipients);
    }

    public function test_a_recipient_who_is_no_longer_active_cannot_view_a_shared_report(): void
    {
        $owner = $this->user();
        $recipient = $this->user(['status' => 'inactive']);
        $report = $this->report($owner, ['recipients' => [(int) $recipient->id]]);

        $this->assertFalse(AiReportAccessPolicy::canView($report, $recipient));
    }

    public function test_every_main_action_turns_an_unexpected_exception_into_a_warning_toast(): void
    {
        $owner = $this->user();
        $report = $this->report($owner);
        $boom = new QueryException('mysql', 'update x', [], new \Exception('db down'));
        $this->mock(UpdateReportAction::class, fn ($mock) => $mock->shouldReceive('execute')->andThrow($boom));
        $this->mock(ShareReportAction::class, fn ($mock) => $mock->shouldReceive('execute')->andThrow($boom));
        $this->mock(DeleteReportAction::class, fn ($mock) => $mock->shouldReceive('execute')->andThrow($boom));

        foreach (['save', 'rename', 'share', 'delete'] as $method) {
            Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
                ->set('titleDraft', 'Another title')
                ->call($method)
                ->assertDispatched('toast', $this->toastType('warning'))
                ->assertSet('reportId', $report->id);
        }
    }

    public function test_a_runtime_exception_keeps_the_specific_not_allowed_toast(): void
    {
        $owner = $this->user();
        $report = $this->report($owner);
        $this->mock(DeleteReportAction::class, fn ($mock) => $mock->shouldReceive('execute')->andThrow(new \RuntimeException('no')));

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->call('delete')
            ->assertDispatched('toast', $this->toastType('warning'))
            ->assertSet('reportId', $report->id);
    }

    public function test_save_sets_saved_at_once_and_an_unsaved_report_is_invisible_to_recipients(): void
    {
        $owner = $this->user();
        $recipient = $this->user();
        $draft = $this->report($owner, [
            'saved_at' => null,
            'spec' => $this->plainSpec('Draft'),
            'versions' => [['prompt' => 'p', 'files' => [], 'spec' => $this->plainSpec('Draft'), 'at' => now()->toIso8601String()]],
        ]);
        $this->assertNull($draft->recipients);
        AiReport::whereKey($draft->id)->update(['recipients' => json_encode([(int) $recipient->id])]);
        $this->assertNotContains($draft->id, AiReport::visibleTo($recipient)->pluck('id')->all());

        $component = Livewire::actingAs($owner)->test(Main::class, ['report' => $draft->fresh()]);
        $component->call('save');
        $first = $draft->fresh()->saved_at;
        $this->assertNotNull($first);

        $this->travel(5)->minutes();
        $component->call('save');

        $this->assertEquals($first, $draft->fresh()->saved_at);
    }

    public function test_refine_has_no_version_count_limit_and_push_version_trims(): void
    {
        $owner = $this->user();
        $report = $this->report($owner, ['versions' => array_fill(0, AiReport::maxVersions(), ['prompt' => 'p', 'spec' => $this->plainSpec('x')])]);

        $this->assertTrue(AiReportAccessPolicy::canRefine($report, $owner));

        $report->pushVersion(['prompt' => 'newest', 'files' => [], 'at' => now()->toIso8601String()]);

        $this->assertCount(AiReport::maxVersions(), $report->versions);
        $this->assertSame('newest', $report->versions[AiReport::maxVersions() - 1]['prompt']);
    }

    public function test_user_caused_failures_keep_the_quota_and_infrastructure_failures_refund_it(): void
    {
        foreach (['too_large', 'blocked'] as $code) {
            $owner = $this->user();
            $report = $this->generatingReport($owner);
            AiReport::consumeQuota($owner->id);

            $this->assertTrue($report->markFailed($code));
            $this->assertSame(1, AiReport::quotaUsed($owner->id), $code);
        }

        foreach (['timeout', 'http_5xx', 'exception', 'stale'] as $code) {
            $owner = $this->user();
            $report = $this->generatingReport($owner);
            AiReport::consumeQuota($owner->id);
            AiReport::query()->whereKey($report->id)->update(['updated_at' => now()->subMinutes(AiReport::staleMinutes() + 1)]);
            $report->refresh();

            $this->assertTrue($report->markFailed($code));
            $this->assertSame(0,AiReport::quotaUsed($owner->id), $code);
        }
    }

    public function test_the_job_restarts_the_stale_clock_when_work_starts(): void
    {
        config(['ai.gateway.model' => 'Gemini-3-Flash-Preview']);
        $owner = $this->user();
        $report = $this->generatingReport($owner);
        AiReport::whereKey($report->id)->update(['updated_at' => now()->subMinutes(AiReport::staleMinutes() + 1)]);
        $this->assertTrue($report->fresh()->isStale());
        $staleDuring = null;
        Http::fake(function () use ($report, &$staleDuring) {
            $staleDuring = AiReport::find($report->id)->isStale();

            return Http::response([
                'choices' => [['message' => ['content' => json_encode($this->plainSpec('Title here'))], 'finish_reason' => 'stop']],
                'usage' => ['total_tokens' => 3],
            ]);
        });

        (new GenerateReportJob($report->id))->handle(app(ReportAiService::class), app(FileExtractor::class));

        $this->assertFalse($staleDuring);
        $this->assertSame(AiReport::STATUS_READY, $report->fresh()->status);
    }

    public function test_the_job_stores_the_extractor_notes_on_the_version_and_the_presenter_returns_them(): void
    {
        config(['ai.gateway.model' => 'Gemini-3-Flash-Preview']);
        $owner = $this->user();
        $report = $this->generatingReport($owner);
        Storage::disk('local')->put($report->tempDirectory() . '/1-bad.docx', "PK\x03\x04not-a-zip");
        Http::fake(['*' => Http::response([
            'choices' => [['message' => ['content' => json_encode($this->plainSpec('Title here'))], 'finish_reason' => 'stop']],
            'usage' => ['total_tokens' => 3],
        ])]);

        (new GenerateReportJob($report->id))->handle(app(ReportAiService::class), app(FileExtractor::class));

        $ready = $report->fresh();
        $notes = $ready->versions[0]['notes'] ?? [];
        $this->assertNotEmpty($notes);
        $this->assertContainsOnly('string', $notes);
        $this->assertSame($notes, (new AiReportPresenter)->notes($ready));
        $this->assertContains("\u{0647}\u{06CC}\u{0686}\u{200C}\u{06CC}\u{06A9} \u{0627}\u{0632} \u{0641}\u{0627}\u{06CC}\u{0644}\u{200C}\u{0647}\u{0627}\u{06CC} \u{067E}\u{06CC}\u{0648}\u{0633}\u{062A} \u{0642}\u{0627}\u{0628}\u{0644} \u{062E}\u{0648}\u{0627}\u{0646}\u{062F}\u{0646} \u{0646}\u{0628}\u{0648}\u{062F}.", $notes);
    }

    public function test_the_job_persists_a_truncation_note_naming_the_cut_file(): void
    {
        config(['ai.gateway.model' => 'Gemini-3-Flash-Preview', 'ai.report.extract.text_per_file' => 50]);
        $owner = $this->user();
        $report = $this->generatingReport($owner);
        Storage::disk('local')->put($report->tempDirectory() . '/1-long.txt', str_repeat('abcdefghij ', 40));
        Http::fake(['*' => Http::response([
            'choices' => [['message' => ['content' => json_encode($this->plainSpec('Title here'))], 'finish_reason' => 'stop']],
            'usage' => ['total_tokens' => 3],
        ])]);

        (new GenerateReportJob($report->id))->handle(app(ReportAiService::class), app(FileExtractor::class));

        $notes = $report->fresh()->versions[0]['notes'] ?? [];
        $this->assertCount(1, $notes);
        $this->assertStringContainsString('long.txt', $notes[0]);
        $this->assertSame($notes, (new AiReportPresenter)->notes($report->fresh()));
    }

    public function test_an_ole2_container_named_docx_or_xlsx_is_skipped_as_encrypted(): void
    {
        Storage::disk('local')->put('ai-report/tmp/8/1-secret.xlsx', "\xD0\xCF\x11\xE0" . str_repeat("\0", 600));
        Storage::disk('local')->put('ai-report/tmp/8/2-other.docx', "\xD0\xCF\x11\xE0" . str_repeat("\0", 600));
        Storage::disk('local')->put('ai-report/tmp/8/3-plain.bin', "\xD0\xCF\x11\xE0" . str_repeat("\0", 600));

        $out = app(FileExtractor::class)->extract('local', 'ai-report/tmp/8');

        $reasons = array_column($out['skipped'], 'reason', 'name');
        $this->assertSame('encrypted', $reasons['secret.xlsx']);
        $this->assertSame('encrypted', $reasons['other.docx']);
        $this->assertNotSame('encrypted', $reasons['plain.bin']);
    }

    public function test_an_uppercase_finish_reason_is_accepted(): void
    {
        config(['ai.gateway.model' => 'Gemini-3-Flash-Preview']);
        $report = $this->generatingReport($this->user());
        Http::fake(['*' => Http::response([
            'choices' => [['message' => ['content' => json_encode($this->plainSpec('Title here'))], 'finish_reason' => 'STOP']],
            'usage' => ['total_tokens' => 3],
        ])]);

        (new GenerateReportJob($report->id))->handle(app(ReportAiService::class), app(FileExtractor::class));

        $this->assertSame(AiReport::STATUS_READY, $report->fresh()->status);
    }

    public function test_clean_strips_the_arabic_letter_mark_but_keeps_rlm(): void
    {
        $this->assertSame("ab\u{200F}c", BlockCatalog::clean("a\u{061C}b\u{200F}c", 50));
    }

    public function test_print_css_breaks_before_every_following_page_and_not_after_the_last(): void
    {
        $css = file_get_contents(resource_path('css/core/dashboard.css'));

        $this->assertStringContainsString('.ai-report-page + .ai-report-page { break-before: page; }', $css);
        $this->assertStringNotContainsString('.ai-report-page:not(:last-of-type)', $css);
        $this->assertStringNotContainsString('@page', $css);

        $view = file_get_contents(resource_path('views/components/dashboard/ai-report.blade.php'));
        $this->assertStringContainsString('<style media="print">@@page { size: A4; margin: 14mm;', $view);
        $this->assertStringContainsString('@@bottom-center', $view);
        $this->assertStringContainsString('counter(pages)', $view);
    }

    public function test_the_reports_switch_drops_a_tenant_disabled_resource(): void
    {
        $this->actingAs($this->filamentDeveloper());
        config(['app.off' => ['ai-report']]);

        $names = array_map(fn ($action) => $action->getName(), ReportResource::switchActions(ReportResource::class));

        $this->assertNotContains('switch_ai_report_resource', $names);
        $this->assertContains('switch_report_resource', $names);
        $this->assertContains('switch_media_resource', $names);
        $this->assertContains('switch_knowledge_progress_resource', $names);
        $this->assertCount(3, $names);
    }

    public function test_a_refund_after_midnight_hits_the_day_the_slot_was_taken(): void
    {
        $owner = $this->user();
        $report = $this->generatingReport($owner, ['versions' => [['prompt' => 'p long enough here', 'files' => [], 'at' => now()->toIso8601String(), 'quota_day' => '2026-03-01']]]);
        Cache::put(AiReport::quotaKey($owner->id, '2026-03-01'), 1);
        Cache::put(AiReport::quotaKey($owner->id, '2026-03-02'), 1);
        Carbon::setTestNow('2026-03-02 00:30:00');

        try {
            $this->assertTrue($report->markFailed('exception'));
            $this->assertSame(0, (int) Cache::get(AiReport::quotaKey($owner->id, '2026-03-01')));
            $this->assertSame(1, (int) Cache::get(AiReport::quotaKey($owner->id, '2026-03-02')));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_a_swept_row_loaded_without_versions_refunds_the_day_it_was_charged(): void
    {
        $owner = $this->user();
        $yesterday = now()->subDay()->format('Y-m-d');
        $report = $this->generatingReport($owner, ['versions' => [['prompt' => 'p long enough here', 'files' => [], 'at' => now()->toIso8601String(), 'quota_day' => $yesterday]]]);
        Cache::put(AiReport::quotaKey($owner->id, $yesterday), 1);
        Cache::put(AiReport::quotaKey($owner->id), 1);
        AiReport::whereKey($report->id)->update(['updated_at' => now()->subMinutes(AiReport::staleMinutes() + 1)]);

        $listed = AiReport::query()->select(AiReport::LIST_COLUMNS)->find($report->id);

        $this->assertTrue($listed->markFailed('stale'));
        $this->assertSame(0, (int) Cache::get(AiReport::quotaKey($owner->id, $yesterday)));
        $this->assertSame(1, (int) Cache::get(AiReport::quotaKey($owner->id)));
    }

    public function test_a_missing_quota_day_falls_back_to_today(): void
    {
        $owner = $this->user();
        $report = $this->generatingReport($owner);
        AiReport::consumeQuota($owner->id);

        $this->assertTrue($report->markFailed('exception'));
        $this->assertSame(0, AiReport::quotaUsed($owner->id));
    }

    public function test_the_share_notification_escapes_title_and_user_name(): void
    {
        $owner = $this->user(['name' => 'Owner <b>x</b>']);
        $fresh = $this->user();
        $report = $this->report($owner, ['title' => '<a href="http://evil.test">click</a>']);

        app(ShareReportAction::class)->execute($report, $owner, [(string) $fresh->id]);

        $data = (string) DB::table('notifications')->where('notifiable_id', $fresh->id)->value('data');
        $this->assertStringNotContainsString('<a href', $data);
        $this->assertStringNotContainsString('<b>', $data);
        $this->assertStringContainsString('&lt;a href=', $data);
    }

    public function test_rename_and_revert_after_the_report_flipped_to_generating_are_refused(): void
    {
        $owner = $this->user();
        $report = $this->report($owner, [
            'saved_at' => null,
            'spec' => $this->spec(),
            'title' => 'Original',
            'versions' => [['prompt' => 'p', 'files' => [], 'spec' => $this->plainSpec('Older'), 'at' => now()->toIso8601String()]],
        ]);
        $stale = AiReport::find($report->id);
        AiReport::whereKey($report->id)->update(['status' => AiReport::STATUS_GENERATING]);
        $before = AiReport::find($report->id);

        foreach ([['title' => 'Changed'], ['revert_to' => 0]] as $changes) {
            try {
                app(UpdateReportAction::class)->execute($stale, $owner, $changes);
                $this->fail('a write raced a generating report');
            } catch (\RuntimeException) {
            }
        }

        $after = AiReport::find($report->id);
        $this->assertSame($before->title, $after->title);
        $this->assertSame($before->spec, $after->spec);
    }

    public function test_deleting_a_generating_report_is_refused_and_a_failed_one_is_deleted(): void
    {
        $owner = $this->user();
        $generating = $this->generatingReport($owner);
        $failed = $this->report($owner, ['status' => AiReport::STATUS_FAILED]);

        try {
            app(DeleteReportAction::class)->execute($generating, $owner);
            $this->fail('a generating report was deleted');
        } catch (\RuntimeException) {
        }
        $this->assertNotNull(AiReport::find($generating->id));

        app(DeleteReportAction::class)->execute($failed, $owner);
        $this->assertNull(AiReport::find($failed->id));
    }

    public function test_a_failed_provider_call_never_logs_the_raw_response_body(): void
    {
        Log::spy();
        $owner = $this->user();
        $report = $this->generatingReport($owner);
        Http::fake(['*' => Http::response('RAW-BODY-LEAK-MARKER', 500)]);

        (new GenerateReportJob($report->id))->handle(app(ReportAiService::class), app(FileExtractor::class));

        $this->assertSame('http_5xx', $report->fresh()->fail_reason);
        Log::shouldHaveReceived('warning')->withArgs(function ($message, $context = []) use ($report) {
            return !array_key_exists('body', $context)
                && !array_key_exists('message', $context)
                && !str_contains(json_encode($context), 'RAW-BODY-LEAK-MARKER')
                && ($context['report_id'] ?? null) === $report->id;
        })->once();
    }

    public function test_csv_is_extracted_as_text_and_unsupported_formats_list_the_supported_extensions(): void
    {
        Storage::disk('local')->put('ai-report/tmp/7/1-data.csv', "name,amount\nalpha,10\nbeta,20");
        $out = app(FileExtractor::class)->extract('local', 'ai-report/tmp/7');
        $this->assertStringContainsString('<<<FILE data.csv>>>', $out['text']);
        $this->assertStringContainsString('alpha,10', $out['text']);
        $this->assertSame([], $out['skipped']);

        Bus::fake();
        $owner = $this->user();
        $form = Livewire::actingAs($owner)->test(Main::class)->instance()->form;
        $form->prompt = 'a prompt that is long enough';
        $path = tempnam(sys_get_temp_dir(), 'air');
        file_put_contents($path, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1" . str_repeat("\0", 512));
        $form->files = [new UploadedFile($path, 'legacy.xls', 'application/vnd.ms-excel', UPLOAD_ERR_OK, true)];

        try {
            app(GenerateReportAction::class)->execute($form, $owner);
            $this->fail('a legacy xls file was accepted');
        } catch (ValidationException $e) {
            $message = $e->errors()[array_key_first($e->errors())][0];
            foreach (explode(',', (string) config('ai.report.upload.mimes')) as $extension) {
                $this->assertStringContainsString($extension, $message);
            }
        } finally {
            @unlink($path);
        }
    }

    public function test_every_validated_extension_is_offered_to_the_mobile_file_picker(): void
    {
        $accept = (string) config('ai.report.upload.accept');

        foreach (explode(',', (string) config('ai.report.upload.mimes')) as $extension) {
            $this->assertStringContainsString('.' . $extension, $accept);
        }
    }

    public function test_the_job_timeout_never_reaches_the_queue_retry_after(): void
    {
        config([
            'queue.default' => 'redis',
            'queue.connections.redis.retry_after' => 180,
            'ai.report.timeout' => 1000,
            'ai.report.connect_timeout' => 10,
            'ai.report.job.padding' => 30,
            'ai.report.job.retry_margin' => 10,
        ]);

        $job = new GenerateReportJob(1);

        $this->assertGreaterThan(170, 2 * (1000 + 10) + 30);
        $this->assertSame(170, $job->timeout);
        $this->assertSame($job->timeout + 30, $job->uniqueFor);
    }

    public function test_a_feature_setting_overrides_the_gateway_and_a_null_one_falls_back_to_it(): void
    {
        foreach (['timeout', 'connect_timeout', 'max_output'] as $key) {
            config(["ai.gateway.{$key}" => 111, "ai.report.{$key}" => 222]);
            $this->assertSame(222, AiClient::setting('report', $key));

            config(["ai.report.{$key}" => null]);
            $this->assertSame(111, AiClient::setting('report', $key));
        }
    }

    public function test_a_missing_no_refund_config_refunds_instead_of_crashing(): void
    {
        config(['ai.report.no_refund' => null]);
        $owner = $this->user();
        $report = $this->generatingReport($owner);
        AiReport::consumeQuota($owner->id);

        $this->assertTrue($report->markFailed('too_large'));
        $this->assertSame(0, AiReport::quotaUsed($owner->id));
    }

    public function test_a_spreadsheet_date_cell_is_followed_by_its_jalali_form(): void
    {
        Storage::disk('local')->makeDirectory('ai-report/tmp/6');
        $writer = new Writer();
        $writer->openToFile(Storage::disk('local')->path('ai-report/tmp/6/dates.xlsx'));
        $moment = new \DateTimeImmutable('2026-03-21 14:30:00');
        $writer->addRow(new Row([Cell::fromValue('when'), Cell::fromValue($moment, (new Style())->setFormat('yyyy-mm-dd hh:mm'))]));
        $writer->close();

        $text = app(FileExtractor::class)->extract('local', 'ai-report/tmp/6')['text'];

        $this->assertStringContainsString('2026-03-21 14:30', $text);
        $jalali = toJalali(\Carbon\Carbon::parse('2026-03-21 14:30:00'), 'j F Y');
        $this->assertNotSame('', $jalali);
        $this->assertStringContainsString('(' . $jalali . ')', $text);
    }

    public function test_a_fork_records_its_parent_and_the_list_row_marks_it(): void
    {
        Bus::fake();
        $owner = $this->user();
        $recipient = $this->user();
        $parent = $this->report($owner, ['recipients' => [(int) $recipient->id], 'spec' => $this->spec()]);
        $form = Livewire::actingAs($owner)->test(Main::class)->instance()->form;
        $form->prompt = 'make this report shorter please';

        $fork = app(GenerateReportAction::class)->execute($form, $owner, $parent);

        $this->assertSame($parent->id, (int) $fork->fresh()->forked_from);
        $this->assertTrue((new AiReportPresenter)->listRow($fork->fresh(), $owner->id)['is_fork']);
        $this->assertFalse((new AiReportPresenter)->listRow($parent->fresh(), $owner->id)['is_fork']);
    }

    private function spec(): array
    {
        return [
            'lang' => 'fa',
            'title' => 'گزارش آزمایشی',
            'pages' => [[
                'blocks' => [
                    ['type' => 'title', 'heading' => 'گزارش آزمایشی', 'subtitle' => 'مهر'],
                    [
                        'type' => 'table',
                        'heading' => 'جدول',
                        'columns' => ['نام', 'مبلغ', 'سهم'],
                        'rows' => [['یک', '۱۰۰۰', '40'], ['دو', '۱۰۰۰', '60']],
                        'total' => ['جمع', '۲۰۰۰', '100'],
                        'percent_column' => 2,
                    ],
                ],
            ]],
        ];
    }

    private function fixtureSpec(): array
    {
        return json_decode(<<<'JSON'
{"lang":"fa","title":"گزارش هزینه‌های هوش مصنوعی","pages":[
 {"blocks":[
  {"type":"title","heading":"گزارش هزینه‌های هوش مصنوعی","subtitle":"دورهٔ گزارش: مهر ۱۴۰۵"},
  {"type":"kpi_row","items":[
   {"label":"هزینهٔ کل ماه","value":"۴۸٬۵۰۰٬۰۰۰ تومان","hint":"۱۲٪ بیش از شهریور","tone":"warning"},
   {"label":"تعداد درخواست‌ها","value":"۱۲۶٬۴۰۰","hint":"میانگین روزانه ۴٬۲۰۰","tone":"info"},
   {"label":"هزینهٔ هر درخواست","value":"۳۸۴ تومان","hint":"۵٪ کمتر از ماه قبل","tone":"success"},
   {"label":"سهم مدل سریع","value":"۷۱٪","hint":"از کل درخواست‌ها","tone":"neutral"}]},
  {"type":"summary","heading":"خلاصهٔ مدیریتی","bullets":["رشد هزینه ناشی از افزایش حجم درخواست‌های واحد فروش است، نه افزایش قیمت.","هزینهٔ هر درخواست برای سومین ماه پیاپی کاهش یافته است.","۸۰٪ هزینه در سه سرویس متمرکز است."],"highlight":"تصمیم لازم: تعیین سقف بودجهٔ آبان پیش از پایان مهر."},
  {"type":"table","heading":"تفکیک بودجه بر اساس سرویس","columns":["سرویس","هزینه (تومان)","سهم"],"rows":[["دستیار هوشمند","۲۲٬۰۰۰٬۰۰۰","45.4"],["تولید گزارش","۱۱٬۵۰۰٬۰۰۰","23.7"],["جست‌وجوی اسناد","۸٬۰۰۰٬۰۰۰","16.5"],["سایر","۷٬۰۰۰٬۰۰۰","14.4"]],"total":["جمع","۴۸٬۵۰۰٬۰۰۰","100"],"percent_column":2,"footnote":"ارقام بر اساس صورت‌حساب رسمی ارائه‌دهنده و نرخ تبدیل پایان ماه."}]},
 {"blocks":[
  {"type":"table","heading":"روند ماهانه","columns":["ماه","هزینه (تومان)","درخواست‌ها"],"rows":[["تیر","۳۹٬۲۰۰٬۰۰۰","۹۸٬۱۰۰"],["مرداد","۴۱٬۸۰۰٬۰۰۰","۱۰۷٬۳۰۰"],["شهریور","۴۳٬۳۰۰٬۰۰۰","۱۱۵٬۹۰۰"],["مهر","۴۸٬۵۰۰٬۰۰۰","۱۲۶٬۴۰۰"]],"total":["جمع چهار ماه","۱۷۲٬۸۰۰٬۰۰۰","۴۴۷٬۷۰۰"]},
  {"type":"section","heading":"نکات و وضعیت","paragraphs":["قرارداد فعلی تا پایان آذر معتبر است و تمدید آن نیازمند تأیید مالی است."],"bullets":["گزارش مصرف واحدها از آبان به‌صورت هفتگی ارسال می‌شود.","سیاست ذخیره‌سازی پاسخ‌های تکراری در حال آزمایش است."]},
  {"type":"callout","variant":"decision","heading":"گزینه‌های پیش رو","paragraphs":["برای کنترل هزینهٔ آبان یکی از گزینه‌های زیر باید تا ۲۸ مهر تصویب شود."],"options":[{"label":"الف","text":"حفظ روند فعلی با سقف ۵۵ میلیون تومان."},{"label":"ب","text":"انتقال ۳۰٪ درخواست‌ها به مدل سریع و سقف ۵۰ میلیون تومان."},{"label":"ج","text":"محدودسازی دسترسی واحدهای غیرعملیاتی و سقف ۴۵ میلیون تومان."}],"risk":"ریسک: محدودسازی ناگهانی می‌تواند کیفیت پاسخ‌گویی واحد فروش را در فصل فروش کاهش دهد."}]}
]}
JSON, true);
    }

    private function renderReport(array $spec, string $dir = 'rtl'): string
    {
        return view('components.dashboard.ai-report', ['spec' => $spec, 'dir' => $dir])->render();
    }

    public function test_renders_two_pages_four_kpi_tiles_summary_options_and_persian_footer(): void
    {
        $html = $this->renderReport($this->fixtureSpec());

        $this->assertSame(2, substr_count($html, 'class="ai-report-page'));
        foreach (['هزینهٔ کل ماه', 'تعداد درخواست‌ها', 'هزینهٔ هر درخواست', 'سهم مدل سریع'] as $label) {
            $this->assertStringContainsString($label, $html);
        }
        $this->assertStringContainsString('دورهٔ گزارش: مهر ۱۴۰۵', $html);
        $this->assertStringContainsString('تصمیم لازم: تعیین سقف بودجهٔ آبان پیش از پایان مهر.', $html);
        foreach (['حفظ روند فعلی با سقف ۵۵ میلیون تومان.', 'انتقال ۳۰٪ درخواست‌ها به مدل سریع و سقف ۵۰ میلیون تومان.', 'محدودسازی دسترسی واحدهای غیرعملیاتی و سقف ۴۵ میلیون تومان.'] as $option) {
            $this->assertStringContainsString($option, $html);
        }
        foreach (['الف', 'ب', 'ج'] as $letter) {
            $this->assertStringContainsString('<bdi>' . $letter . '</bdi>', $html);
        }
        $this->assertStringContainsString('این گزارش با کمک هوش مصنوعی تولید شده است؛ ارقام را پیش از استفاده بررسی کنید.', $html);
        $this->assertStringNotContainsString('This report was generated with AI assistance', $html);
    }

    public function test_escapes_script_in_table_cell_and_renders_single_percent_sign_per_cell(): void
    {
        $spec = $this->fixtureSpec();
        $spec['pages'][0]['blocks'][3]['check'] = 'mismatch';
        $spec['pages'][0]['blocks'][3]['rows'][0][1] = '<script>alert(1)</script>';
        $spec['pages'][0]['blocks'][3]['rows'][1][2] = '۵۳٫۷٪';
        $spec['pages'][0]['blocks'][3]['rows'][2][2] = '—';
        $spec['pages'][0]['blocks'][] = ['type' => 'gibberish', 'heading' => 'این بلوک نباید رندر شود'];

        $html = $this->renderReport($spec);

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringContainsString('ارقام با یکدیگر همخوانی ندارند', $html);
        $this->assertMatchesRegularExpression('/<span[^>]*class="(?![^"]*print:hidden)[^"]*"[^>]*>\s*<span[^>]*>warning<\/span>\s*ارقام با یکدیگر همخوانی ندارند/u', $html);
        $this->assertStringContainsString('۵۳٫۷٪', $html);
        $this->assertStringNotContainsString('۵۳٫۷٪%', $html);
        $this->assertStringNotContainsString('٪%', $html);
        $this->assertStringNotContainsString('%%', $html);
        $this->assertStringContainsString('45.4%', $html);
        $this->assertStringContainsString('100٪', $html);
        $this->assertStringNotContainsString('—%', $html);
        $this->assertStringNotContainsString('این بلوک نباید رندر شود', $html);
    }

    public function test_limits_come_from_config_and_legend_renders_them(): void
    {
        $presenter = new AiReportPresenter;
        $limits = $presenter->limits();

        $this->assertSame(convertToPersian(config('ai.report.upload.max_files')), $limits['files']);
        $this->assertSame(convertToPersian((int) (config('ai.report.upload.per_file_kb') / 1024)), $limits['per_file_mb']);
        $this->assertSame(convertToPersian((int) (config('ai.report.upload.inline_kb') / 1024)), $limits['inline_mb']);
        $this->assertSame(convertToPersian((int) (config('ai.report.upload.total_kb') / 1024)), $limits['total_mb']);
        $this->assertSame(convertToPersian((int) (config('ai.report.upload.inline_total_kb') / 1024)), $limits['inline_total_mb']);
        $this->assertSame(convertToPersian(config('ai.report.prompt.min')), $limits['prompt_min']);
        $this->assertSame(convertToPersian(config('ai.report.prompt.max')), $limits['prompt_max']);
        $this->assertSame(convertToPersian(config('ai.report.max_recipients')), $limits['recipients']);
        $this->assertSame(convertToPersian(config('ai.report.max_versions')), $limits['versions']);
        $this->assertSame(convertToPersian(config('ai.report.share_link_days')), $limits['link_days']);
        $this->assertSame(convertToPersian(config('ai.report.stale_minutes')), $limits['stale_minutes']);
        $this->assertSame(convertToPersian(config('ai.report.stale_draft_days')), $limits['stale_draft_days']);
        [$etaMin, $etaMax] = config('ai.report.eta_seconds');
        $this->assertSame(convertToPersian($etaMin) . ' تا ' . convertToPersian($etaMax), $limits['eta']);

        $html = view('livewire.dashboard.ai-report.legend', ['presenter' => $presenter])->render();
        $this->assertStringContainsString($limits['total_mb'], $html);
        $this->assertStringContainsString('تا ' . $limits['files'] . ' فایل', $html);
        $this->assertStringContainsString('حداقل ' . $limits['prompt_min'] . ' حرف', $html);
        $this->assertStringContainsString('هر PDF یا تصویر تا ' . $limits['inline_mb'] . ' مگابایت', $html);
        $this->assertStringContainsString('هر فایل دیگر تا ' . $limits['per_file_mb'] . ' مگابایت', $html);
        $this->assertStringContainsString($limits['versions'] . ' نسخهٔ آخر نگه داشته می‌شود', $html);
        $this->assertStringContainsString('پیوند اعلان تا ' . $limits['link_days'] . ' روز معتبر است', $html);
        $this->assertStringContainsString('تیک او را بردارید و دوباره «ارسال» را بزنید', $html);
        $this->assertStringContainsString('این کار گزارش را هم ذخیره می‌کند', $html);
        $this->assertStringContainsString('معمولاً ' . $limits['eta'] . ' ثانیه', $html);
    }

    public function test_renders_english_footer_in_ltr(): void
    {
        $spec = $this->fixtureSpec();
        $spec['lang'] = 'en';

        $html = $this->renderReport($spec, 'ltr');

        $this->assertStringContainsString('dir="ltr"', $html);
        $spec['pages'][0]['blocks'][3]['check'] = 'mismatch';
        $this->assertStringContainsString('Figures do not add up', $this->renderReport($spec, 'ltr'));
        $this->assertStringContainsString('This report was generated with AI assistance; verify all figures before use.', $html);
        $this->assertStringNotContainsString('این گزارش با کمک هوش مصنوعی تولید شده است', $html);
    }

    private function filamentDeveloper(): User
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'developer']);
        Permission::create(['user_id' => $user->id, 'is_super_admin' => true]);

        return $user;
    }

    private function filamentReport(array $attrs = []): AiReport
    {
        return AiReport::create(array_merge([
            'user_id' => User::factory()->create(['status' => 'active', 'role' => 'user'])->id,
            'title' => 'گزارش هزینه‌های هوش مصنوعی',
            'status' => AiReport::STATUS_READY,
            'saved_at' => now(),
            'active' => true,
            'spec' => ['pages' => [['blocks' => [['type' => 'title', 'heading' => 'گزارش هزینه‌های هوش مصنوعی']]]]],
        ], $attrs));
    }

    public function test_list_mounts_for_developer_and_shows_report_rows(): void
    {
        $developer = $this->filamentDeveloper();
        $ready = $this->filamentReport();
        $failed = $this->filamentReport(['title' => 'گزارش ناموفق', 'status' => AiReport::STATUS_FAILED, 'saved_at' => null]);

        Livewire::actingAs($developer)->test(ListAiReports::class)
            ->assertCanSeeTableRecords([$ready, $failed]);
    }

    public function test_each_status_tab_filters_the_list(): void
    {
        $developer = $this->filamentDeveloper();
        $ready = $this->filamentReport();
        $failed = $this->filamentReport(['title' => 'گزارش ناموفق', 'status' => AiReport::STATUS_FAILED, 'saved_at' => null]);
        $draft = $this->filamentReport(['title' => 'گزارش پیش‌نویس', 'saved_at' => null]);
        $generating = $this->filamentReport(['title' => 'در حال ساخت', 'status' => AiReport::STATUS_GENERATING, 'saved_at' => null]);

        $list = Livewire::actingAs($developer)->test(ListAiReports::class);

        $list->set('activeTab', 'ready')
            ->assertCanSeeTableRecords([$ready, $draft])
            ->assertCanNotSeeTableRecords([$failed, $generating]);

        $list->set('activeTab', 'failed')
            ->assertCanSeeTableRecords([$failed])
            ->assertCanNotSeeTableRecords([$ready]);

        $list->set('activeTab', 'generating')
            ->assertCanSeeTableRecords([$generating])
            ->assertCanNotSeeTableRecords([$ready, $draft]);

        $list->set('activeTab', 'drafts')
            ->assertCanSeeTableRecords([$failed, $draft])
            ->assertCanNotSeeTableRecords([$ready, $generating]);
    }

    public function test_status_and_draft_filters_narrow_the_list(): void
    {
        $developer = $this->filamentDeveloper();
        $ready = $this->filamentReport();
        $failed = $this->filamentReport(['title' => 'گزارش ناموفق', 'status' => AiReport::STATUS_FAILED, 'saved_at' => null]);
        $draft = $this->filamentReport(['title' => 'گزارش پیش‌نویس', 'saved_at' => null]);

        $list = Livewire::actingAs($developer)->test(ListAiReports::class);

        $list->filterTable('status', AiReport::STATUS_READY)
            ->assertCanSeeTableRecords([$ready, $draft])
            ->assertCanNotSeeTableRecords([$failed]);

        $list->resetTableFilters()->filterTable('saved_at', true)
            ->assertCanSeeTableRecords([$ready])
            ->assertCanNotSeeTableRecords([$draft]);

        $list->resetTableFilters()->filterTable('saved_at', false)
            ->assertCanSeeTableRecords([$failed, $draft])
            ->assertCanNotSeeTableRecords([$ready]);
    }

    public function test_grouping_by_status_and_owner_succeeds(): void
    {
        $developer = $this->filamentDeveloper();
        $this->filamentReport();
        $this->filamentReport(['title' => 'گزارش ناموفق', 'status' => AiReport::STATUS_FAILED, 'saved_at' => null]);

        $component = Livewire::actingAs($developer)->test(ListAiReports::class);

        foreach (['status', 'user.name'] as $group) {
            $component->set('tableGrouping', $group)->assertSuccessful();
        }
    }

    public function test_view_page_renders_preview_and_has_no_edit_or_create_page(): void
    {
        $developer = $this->filamentDeveloper();
        $report = $this->filamentReport();

        $this->assertArrayNotHasKey('edit', AiReportResource::getPages());
        $this->assertArrayNotHasKey('create', AiReportResource::getPages());
        $this->assertFalse(AiReportResource::canCreate());

        Livewire::actingAs($developer)->test(ViewAiReport::class, ['record' => $report->getKey()])
            ->assertSee($report->title)
            ->assertSuccessful();
    }

    public function test_view_page_shows_last_prompt_and_skipped_files(): void
    {
        $developer = $this->filamentDeveloper();
        $report = $this->filamentReport(['versions' => [[
            'prompt' => 'هزینه‌های واحد فروش را جمع‌بندی کن',
            'files' => [],
            'skipped' => [['name' => 'financial.xlsx', 'reason' => 'encrypted']],
            'at' => now()->toIso8601String(),
        ]]]);

        Livewire::actingAs($developer)->test(ViewAiReport::class, ['record' => $report->getKey()])
            ->assertSee('هزینه‌های واحد فروش را جمع‌بندی کن')
            ->assertSee('financial.xlsx')
            ->assertSee('فایل رمز دارد؛ نسخهٔ بدون رمز را پیوست کنید')
            ->assertSuccessful();
    }

    public function test_active_toggle_hides_report_from_user_panel_scope(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $report = $this->filamentReport(['user_id' => $owner->id]);

        $this->assertTrue(AiReport::query()->visibleTo($owner)->whereKey($report->id)->exists());

        $report->update(['active' => false]);

        $this->assertFalse(AiReport::query()->visibleTo($owner)->whereKey($report->id)->exists());
        $this->assertDatabaseHas('ai_reports', ['id' => $report->id]);
    }

    public function test_delete_removes_report_row(): void
    {
        $developer = $this->filamentDeveloper();
        $report = $this->filamentReport();

        Livewire::actingAs($developer)->test(ListAiReports::class)
            ->callTableAction(DeleteAction::class, $report);

        $this->assertDatabaseMissing('ai_reports', ['id' => $report->id]);
    }

    public function test_bulk_delete_keeps_a_report_that_is_generating(): void
    {
        $developer = $this->filamentDeveloper();
        $report = $this->generatingReport($this->user());

        Livewire::actingAs($developer)->test(ListAiReports::class)
            ->callTableBulkAction(DeleteBulkAction::class, [$report]);

        $this->assertDatabaseHas('ai_reports', ['id' => $report->id]);
    }

    public function test_deleting_a_report_through_the_model_removes_its_staged_upload_folder(): void
    {
        $report = $this->filamentReport();
        $file = $report->tempDirectory() . '/a.txt';
        Storage::disk('local')->put($file, 'x');

        $report->delete();

        Storage::disk('local')->assertMissing($file);
    }

    public function test_admin_guides_render_config_limits(): void
    {
        $limits = (new AiReportPresenter)->limits();

        $user = view('filament.resources.ai-report.guide.user')->render();
        $this->assertStringContainsString('معمولاً ' . $limits['eta'] . ' ثانیه', $user);
        $this->assertStringContainsString('تا ' . $limits['versions'] . ' نسخهٔ آخر نگه داشته می‌شود', $user);
        $this->assertStringContainsString('پایان اعتبار پیوند اعلان پس از ' . $limits['link_days'] . ' روز', $user);

        $overview = view('filament.resources.ai-report.guide.overview')->render();
        $this->assertStringContainsString('هر PDF یا تصویر تا ' . $limits['inline_mb'] . ' مگابایت', $overview);
        $this->assertStringContainsString('بیش از ' . $limits['stale_minutes'] . ' دقیقه', $overview);
        $this->assertStringContainsString('قدیمی‌تر از ' . $limits['stale_draft_days'] . ' روز', $overview);
        $this->assertStringContainsString('آخرین درخواست و نام فایل‌های خوانده‌نشده', $overview);
        $this->assertSame('پیش‌نویس‌های قدیمی‌تر از ' . $limits['stale_draft_days'] . ' روز', AiReportTablePresenter::staleDraftFilter()->getLabel());
    }

    public function test_unseen_badge_follows_the_recipient_until_the_report_is_opened(): void
    {
        $owner = $this->user();
        $recipient = $this->user();
        $stranger = $this->user();
        $report = $this->report($owner, ['recipients' => [$recipient->id]]);
        $indicator = new UnseenAiReports;

        $this->actingAs($recipient);
        $this->assertTrue($indicator->isActive());
        $this->actingAs($owner);
        $this->assertFalse($indicator->isActive());
        $this->actingAs($stranger);
        $this->assertFalse($indicator->isActive());

        Livewire::actingAs($recipient)->test(Main::class)->call('open', $report->id)->assertSet('readOnly', true);

        $this->assertSame([$recipient->id], $report->fresh()->seen_by);
        $this->actingAs($recipient);
        $this->assertFalse($indicator->isActive());
    }

    public function test_deleting_or_deactivating_a_shared_report_refreshes_the_menu_state(): void
    {
        $owner = $this->user();
        $recipient = $this->user();
        $shared = $this->report($owner, ['recipients' => [$recipient->id]]);
        $private = $this->report($owner);

        $version = StateService::version();
        $private->update(['active' => false]);
        $private->delete();
        $this->assertSame($version, StateService::version());

        usleep(2000);
        $shared->update(['active' => false]);
        $afterToggle = StateService::version();
        $this->assertNotSame($version, $afterToggle);

        usleep(2000);
        $shared->delete();
        $this->assertNotSame($afterToggle, StateService::version());
    }

    public function test_the_badge_key_goes_off_with_the_module_for_a_tenant(): void
    {
        $this->assertFalse(tenantOffName('ai-report-controller'));

        config(['app.off' => ['ai-report']]);

        $this->assertTrue(tenantOffName('ai-report-controller'));
    }

    public function test_unseen_badge_ignores_drafts_inactive_and_not_ready_reports(): void
    {
        $owner = $this->user();
        $recipient = $this->user();
        $this->report($owner, ['recipients' => [$recipient->id], 'saved_at' => null]);
        $this->report($owner, ['recipients' => [$recipient->id], 'active' => false]);
        $this->report($owner, ['recipients' => [$recipient->id], 'status' => AiReport::STATUS_FAILED]);

        $this->actingAs($recipient);
        $this->assertFalse((new UnseenAiReports)->isActive());
    }

    public function test_mark_seen_is_idempotent_and_the_owner_opening_does_not_mark(): void
    {
        $owner = $this->user();
        $recipient = $this->user();
        $report = $this->report($owner, ['recipients' => [$recipient->id]]);

        $report->markSeenBy($recipient->id);
        $report->markSeenBy($recipient->id);
        $this->assertSame([$recipient->id], $report->fresh()->seen_by);

        $fresh = $this->report($owner, ['recipients' => [$recipient->id]]);
        Livewire::actingAs($owner)->test(Main::class)->call('open', $fresh->id);
        $this->assertNull($fresh->fresh()->seen_by);
    }

    public function test_share_drops_removed_recipients_from_seen_so_a_reshare_lights_the_badge_again(): void
    {
        $owner = $this->user();
        $recipient = $this->user();
        $report = $this->report($owner, ['recipients' => [$recipient->id], 'seen_by' => [$recipient->id]]);

        app(ShareReportAction::class)->execute($report, $owner, []);
        $this->assertSame([], $report->fresh()->seen_by);

        app(ShareReportAction::class)->execute($report->fresh(), $owner, [$recipient->id]);
        $this->actingAs($recipient);
        $this->assertTrue((new UnseenAiReports)->isActive());
    }

    public function test_the_new_chip_shows_on_an_unseen_received_row_and_not_after_opening_or_for_the_owner(): void
    {
        $owner = $this->user();
        $recipient = $this->user();
        $report = $this->report($owner, ['recipients' => [$recipient->id]]);

        $row = (new AiReportPresenter())->listRow(AiReport::query()->select([...AiReport::LIST_COLUMNS, 'seen_by'])->find($report->id), (int) $owner->id);
        $this->assertFalse($row['is_new']);

        $component = Livewire::actingAs($recipient)->test(Main::class)->assertSeeHtml('ai-report-chip-new');
        $component->call('open', $report->id)->assertDontSeeHtml('ai-report-chip-new');
        $this->assertSame([$recipient->id], $report->fresh()->seen_by);

        Livewire::actingAs($owner)->test(Main::class)->assertDontSeeHtml('ai-report-chip-new')->assertDontSeeHtml('ai-report-chip-received');
    }

    public function test_an_encrypted_office_file_gets_the_password_message_not_the_generic_one(): void
    {
        $owner = $this->user();
        $magic = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1" . str_repeat("\0", 600);
        $this->assertSame('application/CDFV2', (new \finfo(FILEINFO_MIME_TYPE))->buffer($magic));

        $form = Livewire::actingAs($owner)->test(Main::class)->instance()->form;
        $form->prompt = 'a prompt that is long enough';
        $form->files = [UploadedFile::fake()->createWithContent('secret.docx', $magic)];

        try {
            $form->validated();
            $this->fail('the encrypted file passed validation');
        } catch (ValidationException $e) {
            $this->assertSame([ComposerForm::encryptedFileMessage()], collect($e->errors())->flatten()->all());
        }

        $stage = new \ReflectionMethod(GenerateReportAction::class, 'stage');
        try {
            $stage->invoke(app(GenerateReportAction::class), $form);
            $this->fail('stage accepted the encrypted file');
        } catch (ValidationException $e) {
            $this->assertSame([ComposerForm::encryptedFileMessage()], $e->errors()['form.files']);
        }

        $form->files = [UploadedFile::fake()->createWithContent('legacy.doc', $magic)];
        try {
            $stage->invoke(app(GenerateReportAction::class), $form);
            $this->fail('stage accepted the legacy file');
        } catch (ValidationException $e) {
            $this->assertSame([ComposerForm::unsupportedFileMessage()], $e->errors()['form.files']);
        }
    }

    public function test_reuse_is_unavailable_for_a_fork_and_for_a_trimmed_history(): void
    {
        $owner = $this->user();
        $origin = $this->report($owner);
        $fork = $this->report($owner, [
            'forked_from' => $origin->id,
            'versions' => [['prompt' => 'make the chart red', 'files' => [], 'spec' => ['lang' => 'fa'], 'at' => now()->toIso8601String()]],
        ]);
        $trimmed = $this->report($owner, [
            'versions' => [['prompt' => 'mid history tweak', 'trimmed' => true, 'files' => [], 'spec' => ['lang' => 'fa'], 'at' => now()->toIso8601String()]],
        ]);

        foreach ([$fork, $trimmed] as $report) {
            Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
                ->assertDontSeeHtml('ai-report-reuse')
                ->call('reuse')
                ->assertSet('form.prompt', '')
                ->assertSet('reportId', $report->id);
        }
    }

    public function test_list_signature_changes_when_seen_by_changes(): void
    {
        $owner = $this->user();
        $recipient = $this->user();
        $report = $this->report($owner, ['recipients' => [$recipient->id]]);

        $component = Livewire::actingAs($recipient)->test(Main::class);
        $component->call('pollList');
        $before = $component->get('listSignature');

        $report->markSeenBy($recipient->id);
        $component->call('pollList');

        $this->assertNotSame($before, $component->get('listSignature'));
    }

    public function test_reuse_keeps_the_reused_reports_presets(): void
    {
        $owner = $this->user();
        $report = $this->report($owner, [
            'versions' => [
                ['prompt' => 'the very first request text', 'files' => [], 'type' => 'budget', 'length' => 'detailed', 'audience' => 'technical', 'spec' => ['lang' => 'fa'], 'at' => now()->toIso8601String()],
                ['prompt' => 'a later refinement text', 'files' => [], 'type' => 'monthly', 'length' => 'one_page', 'audience' => 'mixed', 'spec' => ['lang' => 'fa'], 'at' => now()->toIso8601String()],
            ],
        ]);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->assertSet('form.type', 'monthly')
            ->call('reuse')
            ->assertSet('reportId', null)
            ->assertSet('form.prompt', 'the very first request text')
            ->assertSet('form.type', 'budget')
            ->assertSet('form.length', 'detailed')
            ->assertSet('form.audience', 'technical');
    }

    public function test_reuse_prefills_the_first_prompt_closes_the_report_and_is_refused_for_a_recipient(): void
    {
        $owner = $this->user();
        $recipient = $this->user();
        $report = $this->report($owner, [
            'recipients' => [$recipient->id],
            'versions' => [
                ['prompt' => 'the very first request text', 'files' => [], 'spec' => ['lang' => 'fa'], 'at' => now()->toIso8601String()],
                ['prompt' => 'a later refinement text', 'files' => [], 'spec' => ['lang' => 'fa'], 'at' => now()->toIso8601String()],
            ],
        ]);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->assertSeeHtml('ai-report-reuse')
            ->call('reuse')
            ->assertSet('reportId', null)
            ->assertSet('form.prompt', 'the very first request text')
            ->assertDispatched('ai-report-open')
            ->assertDontSeeHtml('ai-report-reuse');

        Livewire::actingAs($recipient)->test(Main::class, ['report' => $report])
            ->assertDontSeeHtml('ai-report-reuse')
            ->call('reuse')
            ->assertSet('reportId', $report->id)
            ->assertSet('form.prompt', '')
            ->assertNotDispatched('ai-report-open');
    }

    public function test_the_print_header_carries_the_author_and_the_date_in_the_spec_language(): void
    {
        $head = function (string $html): string {
            $this->assertSame(1, preg_match('/<p class="ai-report-print-head[^>]*>.*?<\/p>/s', $html, $m), 'print head element missing');

            return $m[0];
        };
        $fa = view('components.dashboard.ai-report', ['spec' => $this->fixtureSpec(), 'dir' => 'rtl', 'author' => 'علی رضایی'])->render();
        $faHead = $head($fa);

        $this->assertStringContainsString('علی رضایی', $faHead);
        $this->assertStringContainsString(toJalali(now(), 'j F Y'), $faHead);
        $this->assertStringContainsString('aria-hidden="true"', $faHead);

        $en = view('components.dashboard.ai-report', ['spec' => $this->plainSpec('Hello World'), 'dir' => 'ltr', 'author' => 'Ann'])->render();
        $enHead = $head($en);

        $this->assertStringContainsString('Hello World', $en);
        $this->assertStringContainsString(now()->format('j F Y'), $enHead);
        $this->assertStringContainsString('Ann', $enHead);

        $owner = $this->user();
        $viewer = Livewire::actingAs($owner)->test(Main::class, ['report' => $this->report($owner, ['spec' => $this->spec()])])
            ->assertSeeHtml('ai-report-print-head');
        $this->assertStringContainsString($owner->name, $head($viewer->html()));
    }

    public function test_the_single_print_button_covers_pdf_and_appears_for_ready_reports_only(): void
    {
        $owner = $this->user();
        $ready = $this->report($owner);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $ready])
            ->assertSeeHtml('wire:key="ai-report-print-button"')
            ->assertSeeHtml('ذخیره به‌صورت PDF')
            ->assertDontSeeHtml('wire:key="ai-report-pdf-button"')
            ->assertDontSeeHtml('picture_as_pdf')
            ->call('toggleView', 'table')
            ->assertDontSeeHtml('wire:key="ai-report-print-button"')
            ->assertDontSeeHtml('wire:key="ai-report-copy-button"');

        $generating = $this->generatingReport($owner);
        Livewire::actingAs($owner)->test(Main::class, ['report' => $generating])
            ->assertDontSeeHtml('wire:key="ai-report-print-button"');

        $failed = $this->report($owner, ['status' => AiReport::STATUS_FAILED]);
        Livewire::actingAs($owner)->test(Main::class, ['report' => $failed])
            ->assertDontSeeHtml('wire:key="ai-report-print-button"');
    }

    private function b2Spec(array $blocks, string $lang = 'fa'): array
    {
        return ['lang' => $lang, 'title' => 'T', 'pages' => [['blocks' => $blocks]]];
    }

    private function b2Block(array $block, string $lang = 'fa'): array
    {
        return BlockCatalog::validate($this->b2Spec([$block], $lang))['pages'][0]['blocks'][0];
    }

    private function b2Rejection(array $block): string
    {
        try {
            BlockCatalog::validate($this->b2Spec([$block]));
        } catch (\InvalidArgumentException $e) {
            return $e->getMessage();
        }

        $this->fail('the block was accepted: ' . json_encode($block, JSON_UNESCAPED_UNICODE));
    }

    public function test_facts_reuse_the_tile_shape_with_their_own_cap_minimum_and_heading(): void
    {
        config(['ai.report.spec.facts_items' => 3]);
        $items = array_map(fn (int $i): array => ['label' => "L{$i}", 'value' => "V{$i}", 'hint' => null, 'tone' => null], range(1, 5));

        $block = $this->b2Block(['type' => 'facts', 'heading' => '  زمینه  ', 'items' => $items]);
        $this->assertSame('facts', $block['type']);
        $this->assertSame('زمینه', $block['heading']);
        $this->assertSame([['label' => 'L1', 'value' => 'V1'], ['label' => 'L2', 'value' => 'V2'], ['label' => 'L3', 'value' => 'V3']], $block['items']);

        $single = $this->b2Block(['type' => 'facts', 'items' => [$items[0]]]);
        $this->assertCount(1, $single['items']);
        $this->assertArrayNotHasKey('heading', $single);

        $this->assertSame('block facts: items are required', $this->b2Rejection(['type' => 'facts', 'items' => [['label' => '', 'value' => 'x'], ['label' => 'y', 'value' => '']]]));
        $this->assertSame('block facts: items are required', $this->b2Rejection(['type' => 'facts', 'items' => null]));
        $this->assertSame('block kpi_row: items are required', $this->b2Rejection(['type' => 'kpi_row', 'items' => [$items[0]]]));

        $kpi = $this->b2Block(['type' => 'kpi_row', 'heading' => 'ignored', 'items' => [$items[0], $items[1]]]);
        $this->assertSame('kpi_row', $kpi['type']);
        $this->assertArrayNotHasKey('heading', $kpi);
    }

    public function test_action_items_become_a_four_cell_table_with_a_status_column(): void
    {
        $block = $this->b2Block([
            'type' => 'action_items',
            'heading' => 'اقدام‌ها',
            'rows' => [
                ['علی', 'بازبینی قرارداد', '۱۴۰۵/۰۸/۳۰', 'در حال انجام'],
                ['رضا', '', '۱۴۰۵/۰۹/۰۱', 'late'],
                ['سارا', 'ارسال گزارش'],
                ['x', 'y', 'z', 'done', 'extra', 'more'],
            ],
            'total' => ['a', 'b', 'c', 'd'],
            'percent_column' => 1,
        ]);

        $this->assertSame(['type', 'columns', 'rows', 'status_column', 'heading'], array_keys($block));
        $this->assertSame(['مسئول', 'اقدام', 'مهلت', 'وضعیت'], $block['columns']);
        $this->assertSame(3, $block['status_column']);
        $this->assertSame([
            ['علی', 'بازبینی قرارداد', '۱۴۰۵/۰۸/۳۰', 'در حال انجام'],
            ['سارا', 'ارسال گزارش', '', ''],
            ['x', 'y', 'z', 'done'],
        ], $block['rows']);

        $en = $this->b2Block(['type' => 'action_items', 'rows' => [['Ann', 'Ship', 'Mon', 'done']]], 'en');
        $this->assertSame(['Owner', 'Action', 'Due', 'Status'], $en['columns']);

        $this->assertSame('block action_items: rows are required', $this->b2Rejection(['type' => 'action_items', 'rows' => [['a', '', 'c', 'd'], ['b']]]));
        $this->assertSame('block action_items: rows are required', $this->b2Rejection(['type' => 'action_items', 'rows' => null]));
    }

    public function test_progress_computes_the_percent_on_the_server_and_never_trusts_the_model(): void
    {
        $block = $this->b2Block([
            'type' => 'progress',
            'rows' => [
                ['فروش', '200', '150', '99'],
                ['هزینه', '۱۰۰', '۱۲۰'],
                ['ریسک', '0', '5'],
                ['کیفیت', '—', '3'],
                ['', '10', '5'],
                ['دوسوم', '3', '2'],
                ['یک‌سوم', '3', '1'],
                ['منفی', '100', '-50'],
            ],
            'percent_column' => 0,
            'status_column' => 1,
        ]);

        $this->assertSame(['type', 'columns', 'rows', 'percent_column'], array_keys($block));
        $this->assertSame(['شاخص', 'هدف', 'تحقق', 'پیشرفت'], $block['columns']);
        $this->assertSame(3, $block['percent_column']);
        $this->assertSame([
            ['فروش', '۲۰۰', '۱۵۰', '۷۵'],
            ['هزینه', '۱۰۰', '۱۲۰', '۱۲۰'],
            ['ریسک', '۰', '۵', ''],
            ['کیفیت', '—', '۳', ''],
            ['دوسوم', '۳', '۲', '۶۷'],
            ['یک‌سوم', '۳', '۱', '۳۳'],
            ['منفی', '۱۰۰', '-۵۰', '-۵۰'],
        ], $block['rows']);

        $en = $this->b2Block(['type' => 'progress', 'rows' => [['Sales', '10', '5']]], 'en');
        $this->assertSame(['Item', 'Target', 'Actual', 'Progress'], $en['columns']);
        $this->assertSame('50', $en['rows'][0][3]);

        $this->assertSame('block progress: rows are required', $this->b2Rejection(['type' => 'progress', 'rows' => [['', '10', '5']]]));
        $this->assertSame('block progress: rows are required', $this->b2Rejection(['type' => 'progress', 'rows' => []]));
    }

    public function test_persian_reports_get_persian_digits_in_value_cells_only(): void
    {
        $table = ['type' => 'table', 'heading' => 'جدول 2025', 'columns' => ['سرویس 1', 'مبلغ'], 'rows' => [['Q3 2025', '1,200'], ['5 نفر', '3']], 'total' => ['جمع', '1,203'], 'footnote' => 'تا 2025'];

        $fa = $this->b2Block($table);
        $this->assertSame([['Q3 2025', '۱,۲۰۰'], ['۵ نفر', '۳']], $fa['rows']);
        $this->assertSame(['جمع', '۱,۲۰۳'], $fa['total']);
        $this->assertSame(['سرویس 1', 'مبلغ'], $fa['columns']);
        $this->assertSame('جدول 2025', $fa['heading']);
        $this->assertSame('تا 2025', $fa['footnote']);
        $this->assertArrayNotHasKey('check', $fa);

        $mismatch = $this->b2Block(['total' => ['جمع', '9']] + $table);
        $this->assertSame('mismatch', $mismatch['check']);
        $this->assertSame(['جمع', '۹'], $mismatch['total']);

        $en = $this->b2Block($table, 'en');
        $this->assertSame([['Q3 2025', '1,200'], ['5 نفر', '3']], $en['rows']);
        $this->assertSame(['جمع', '1,203'], $en['total']);
        $this->assertArrayNotHasKey('check', $en);

        $items = [['label' => 'Revenue 1', 'value' => '120', 'hint' => 'up 5', 'tone' => null], ['label' => 'L2', 'value' => '3 ماه', 'hint' => null, 'tone' => null]];
        $expected = [['label' => 'Revenue 1', 'value' => '۱۲۰', 'hint' => 'up 5'], ['label' => 'L2', 'value' => '۳ ماه']];
        $this->assertSame($expected, $this->b2Block(['type' => 'kpi_row', 'items' => $items])['items']);
        $this->assertSame($expected, $this->b2Block(['type' => 'facts', 'items' => $items])['items']);
        $this->assertSame('120', $this->b2Block(['type' => 'facts', 'items' => $items], 'en')['items'][0]['value']);

        $progress = $this->b2Block(['type' => 'progress', 'rows' => [['فروش', '200', '150']]]);
        $this->assertSame([['فروش', '۲۰۰', '۱۵۰', '۷۵']], $progress['rows']);
    }

    public function test_tone_matches_status_words_exactly_after_folding(): void
    {
        $cases = [
            'late' => 'danger', 'Late' => 'danger', 'AT RISK' => 'danger', 'at_risk' => 'danger', 'عقب‌افتاده' => 'danger', "با\u{200C}تأخیر" => 'danger', 'با تأخیر' => 'danger', 'high' => 'danger',
            'In Progress' => 'warning', 'in-progress' => 'warning', 'در حال انجام' => 'warning', "در\u{200C}حال\u{200C}انجام" => 'warning', 'medium' => 'warning',
            'done' => 'success', '  Done  ' => 'success', 'انجام شده' => 'success', 'انجام‌شده' => 'success', 'تكميل' => 'success', 'on track' => 'success', 'low' => 'success',
            'done soon' => null, 'نامعلوم' => null, '' => null, '   ' => null, '—' => null, '75' => null,
        ];

        foreach ($cases as $word => $tone) {
            $this->assertSame($tone, BlockCatalog::tone((string) $word), json_encode($word, JSON_UNESCAPED_UNICODE));
        }

        config(['ai.report.status_values' => ['success' => ['shipped']]]);
        $this->assertSame('success', BlockCatalog::tone('Shipped'));
        $this->assertNull(BlockCatalog::tone('late'));
    }

    private function assumptionsPage(array $blocks, string $lang = 'fa'): array
    {
        return BlockCatalog::validate($this->b2Spec($blocks, $lang))['pages'][0]['blocks'];
    }

    public function test_validation_inserts_the_assumptions_section_before_the_final_callout(): void
    {
        $callout = ['type' => 'callout', 'variant' => 'decision', 'heading' => 'تصمیم', 'paragraphs' => ['یک'], 'options' => [['label' => 'الف', 'text' => 'گزینه']]];
        $title = ['type' => 'title', 'heading' => 'عنوان'];
        $heading = config('ai.report.assumptions.heading.fa');

        $blocks = $this->assumptionsPage([$title, $callout]);
        $this->assertSame(['title', 'section', 'callout'], array_column($blocks, 'type'));
        $this->assertSame(['type' => 'section', 'heading' => $heading, 'paragraphs' => [BlockCatalog::clean(config('ai.report.assumptions.fallback.fa'), 1200)]], $blocks[1]);

        $appended = $this->assumptionsPage([$callout, $title]);
        $this->assertSame(['callout', 'title', 'section'], array_column($appended, 'type'));
        $this->assertSame($heading, $appended[2]['heading']);

        $none = $this->assumptionsPage([$title]);
        $this->assertSame(['title', 'section'], array_column($none, 'type'));

        $en = $this->assumptionsPage([['type' => 'title', 'heading' => 'T'], $callout], 'en');
        $this->assertSame(['title', 'section', 'callout'], array_column($en, 'type'));
        $this->assertSame('Assumptions and limitations', $en[1]['heading']);
        $this->assertSame([config('ai.report.assumptions.fallback.en')], $en[1]['paragraphs']);
    }

    public function test_a_model_written_assumptions_section_is_kept_and_nothing_is_added(): void
    {
        $callout = ['type' => 'callout', 'variant' => 'decision', 'heading' => 'تصمیم', 'paragraphs' => ['یک'], 'options' => [['label' => 'الف', 'text' => 'گزینه']]];

        $fa = $this->assumptionsPage([['type' => 'section', 'heading' => "مفروضات و محدودیت\u{200C}ها", 'paragraphs' => ['داده ارائه نشده']], $callout]);
        $this->assertSame(['section', 'callout'], array_column($fa, 'type'));
        $this->assertSame(['داده ارائه نشده'], $fa[0]['paragraphs']);

        $en = $this->assumptionsPage([['type' => 'section', 'heading' => 'assumptions AND limitations', 'bullets' => ['not provided']], $callout], 'en');
        $this->assertSame(['section', 'callout'], array_column($en, 'type'));
        $this->assertSame(['not provided'], $en[0]['bullets']);

        $other = $this->assumptionsPage([['type' => 'section', 'heading' => 'مفروضات', 'paragraphs' => ['x']], $callout]);
        $this->assertSame(['section', 'section', 'callout'], array_column($other, 'type'));
    }

    public function test_assumptions_validation_is_idempotent_and_moves_to_a_new_page_when_the_last_is_full(): void
    {
        $spec = BlockCatalog::validate($this->b2Spec([['type' => 'title', 'heading' => 'عنوان']]));
        $this->assertSame($spec, BlockCatalog::validate($spec));
        $this->assertCount(2, $spec['pages'][0]['blocks']);

        config(['ai.report.spec.blocks' => 2]);
        $full = BlockCatalog::validate($this->b2Spec([['type' => 'title', 'heading' => 'عنوان'], ['type' => 'section', 'heading' => 'بخش', 'paragraphs' => ['x']]]));
        $this->assertCount(2, $full['pages']);
        $this->assertSame(['title', 'section'], array_column($full['pages'][0]['blocks'], 'type'));
        $this->assertSame(['section'], array_column($full['pages'][1]['blocks'], 'type'));
        $this->assertSame(config('ai.report.assumptions.heading.fa'), $full['pages'][1]['blocks'][0]['heading']);
        $this->assertSame($full, BlockCatalog::validate($full));

        $html = $this->renderReport($full);
        $this->assertSame(2, substr_count($html, 'class="ai-report-page'));
        $this->assertGreaterThan(strpos($html, 'بخش'), strpos($html, config('ai.report.assumptions.heading.fa')));

        config(['ai.report.spec.pages' => 1]);
        $capped = BlockCatalog::validate($this->b2Spec([['type' => 'title', 'heading' => 'عنوان'], ['type' => 'section', 'heading' => 'بخش', 'paragraphs' => ['x']]]));
        $this->assertCount(1, $capped['pages']);
        $this->assertSame(['title', 'section'], array_column($capped['pages'][0]['blocks'], 'type'));
        $this->assertSame($capped, BlockCatalog::validate($capped));
    }

    public function test_the_system_prompt_pins_the_assumptions_rule_in_both_languages(): void
    {
        $prompt = (new \ReflectionMethod(ReportAiService::class, 'systemPrompt'))->invoke(new ReportAiService());

        $this->assertStringContainsString('10. Every report contains a `section` block whose heading is exactly «مفروضات و محدودیت‌ها»', $prompt);
        $this->assertStringContainsString('"Assumptions and limitations" in an English report', $prompt);
        $this->assertStringContainsString('placed immediately before the final callout', $prompt);
        $this->assertStringContainsString('the assumptions section (rule 10), then a final callout', $prompt);
        $this->assertStringNotContainsString('{assume_', $prompt);

        $typed = (new \ReflectionMethod(ReportAiService::class, 'systemPrompt'))->invoke(new ReportAiService(), ['type' => 'incident']);
        $this->assertStringContainsString('Always keep the assumptions section from rule 10 immediately before the final callout.', $typed);
    }

    public function test_tone_covers_hamza_less_and_everyday_persian_status_words(): void
    {
        $cases = ['تاخیر' => 'danger', 'با تاخیر' => 'danger', "با\u{200C}تاخیر" => 'danger', 'متوقف شده' => 'danger', "نیمه\u{200C}کاره" => 'warning', 'نیمه کاره' => 'warning', 'لغو' => null];

        foreach ($cases as $word => $tone) {
            $this->assertSame($tone, BlockCatalog::tone((string) $word), $word);
        }
    }

    public function test_number_parses_multi_word_units_and_rejects_non_numbers_fast(): void
    {
        $this->assertSame(150.0, BlockCatalog::number('۱۵۰ میلیون تومان'));
        $this->assertSame(150.0, BlockCatalog::number('150 million toman'));
        $this->assertNull(BlockCatalog::number(str_repeat('ab cd ', 800) . '!'));
        $this->assertNull(BlockCatalog::number('12' . str_repeat('ab ', 1700) . '!'));
    }

    public function test_table_status_column_is_bounds_checked_and_refused_on_the_percent_column(): void
    {
        $table = fn (mixed $status, array $extra = []): array => $this->b2Block(['type' => 'table', 'columns' => ['A', 'B', 'C'], 'rows' => [['x', '1', '100']], 'status_column' => $status] + $extra);

        $this->assertSame(2, $table(2)['status_column']);
        $this->assertSame(1, $table('1')['status_column']);
        $this->assertSame(0, $table(0)['status_column']);
        foreach ([3, -1, '1.5', 'abc', null, true] as $bad) {
            $this->assertArrayNotHasKey('status_column', $table($bad), json_encode($bad));
        }

        $both = $table(2, ['percent_column' => 2]);
        $this->assertSame(2, $both['percent_column']);
        $this->assertArrayNotHasKey('status_column', $both);

        $apart = $table(1, ['percent_column' => 2]);
        $this->assertSame([2, 1], [$apart['percent_column'], $apart['status_column']]);
    }

    public function test_the_schema_gains_status_column_as_its_only_new_key_and_the_new_types(): void
    {
        $def = BlockCatalog::schema()['$defs']['block'];

        $this->assertSame(['title', 'kpi_row', 'facts', 'summary', 'table', 'action_items', 'progress', 'chart', 'callout', 'section'], $def['properties']['type']['enum']);
        $this->assertSame(['title', 'kpi_row', 'facts', 'summary', 'table', 'action_items', 'progress', 'chart', 'callout', 'section'], BlockCatalog::TYPES);
        $this->assertSame(['integer', 'null'], $def['properties']['status_column']['type']);
        $this->assertEqualsCanonicalizing(array_keys($def['properties']), $def['required']);
        $this->assertEqualsCanonicalizing(
            ['type', 'heading', 'subtitle', 'items', 'bullets', 'highlight', 'columns', 'rows', 'total', 'percent_column', 'status_column', 'footnote', 'variant', 'paragraphs', 'options', 'risk'],
            array_keys($def['properties'])
        );
        $this->assertSame(max((int) config('ai.report.spec.kpi_items'), (int) config('ai.report.spec.facts_items')), $def['properties']['items']['maxItems']);
        $this->assertSame(max((int) config('ai.report.spec.columns'), (int) config('ai.report.spec.chart_labels')), $def['properties']['columns']['maxItems']);
    }

    public function test_the_system_prompt_names_the_new_blocks_and_the_facts_cap(): void
    {
        config(['ai.report.spec.facts_items' => 5]);

        $prompt = (new \ReflectionMethod(ReportAiService::class, 'systemPrompt'))->invoke(new ReportAiService());

        $this->assertStringContainsString('(title, kpi_row, facts, summary, table, action_items, progress, chart, section, callout)', $prompt);
        $this->assertStringContainsString('facts: heading, items (1 to 5 label/value pairs', $prompt);
        $this->assertStringContainsString('action_items: heading, rows (each row exactly 4 strings', $prompt);
        $this->assertStringContainsString('progress: heading, rows (each row exactly 3 strings', $prompt);
        $this->assertStringContainsString('Set status_column to the 0-based index of a status or priority column', $prompt);
        $this->assertStringNotContainsString('{facts}', $prompt);
    }

    public function test_facts_render_as_kpi_tiles_and_action_items_render_as_a_table_with_status_chips(): void
    {
        $spec = BlockCatalog::validate($this->b2Spec([
            ['type' => 'facts', 'heading' => 'اطلاعات زمینه', 'items' => [['label' => 'دوره', 'value' => 'مهر ۱۴۰۵'], ['label' => 'منبع', 'value' => 'گزارش مالی']]],
            ['type' => 'action_items', 'heading' => 'اقدام‌ها', 'rows' => [
                ['علی', 'الف', '۱۴۰۵/۰۸/۰۱', 'انجام‌شده'],
                ['رضا', 'ب', '۱۴۰۵/۰۸/۰۲', 'در حال انجام'],
                ['سارا', 'ج', '۱۴۰۵/۰۸/۰۳', 'با تأخیر'],
                ['نیما', 'د', '۱۴۰۵/۰۸/۰۴', 'نامعلوم'],
            ]],
        ]));

        $html = $this->renderReport($spec);

        $this->assertSame(1, substr_count($html, 'grid grid-cols-2 lg:grid-cols-4 print:grid-cols-4'));
        $this->assertSame(2, substr_count($html, 'text-[11px] font-medium'));
        $this->assertStringContainsString('<bdi>مهر ۱۴۰۵</bdi>', $html);
        $this->assertSame(3, preg_match_all('/<h2[^>]*text-sm font-bold/', $html));
        $this->assertStringContainsString('اطلاعات زمینه', $html);

        foreach (['مسئول', 'اقدام', 'مهلت', 'وضعیت'] as $header) {
            $this->assertStringContainsString('>' . $header . '</th>', $html);
        }
        $this->assertSame(3, substr_count($html, 'ring-1 ring-inset'));
        $this->assertSame(1, substr_count($html, 'bg-green-50 '));
        $this->assertSame(1, substr_count($html, 'bg-yellow-50 '));
        $this->assertSame(1, substr_count($html, 'bg-[var(--md-sys-color-error-container)]'));
        $this->assertSame(3, substr_count($html, 'px-3 py-1.5 align-middle text-start whitespace-nowrap'));
        $this->assertSame(1, substr_count($html, 'px-3 py-1.5 align-middle text-start break-words max-w-[12rem] text-[var(--md-sys-color-on-surface)]'));
        $this->assertMatchesRegularExpression('/<bdi[^>]*>نامعلوم<\/bdi>/u', $html);
        $this->assertDoesNotMatchRegularExpression('/ring-inset[^>]*>\s*<bdi>نامعلوم/u', $html);
        $this->assertMatchesRegularExpression('/ring-inset[^>]*>\s*<bdi>با تأخیر<\/bdi>/u', $html);

        $en = $this->renderReport(BlockCatalog::validate($this->b2Spec([['type' => 'action_items', 'rows' => [['Ann', 'Ship', 'Mon', 'Done']]]], 'en')), 'ltr');
        $this->assertStringContainsString('>Owner</th>', $en);
        $this->assertStringContainsString('>Status</th>', $en);
        $this->assertSame(1, substr_count($en, 'ring-1 ring-inset'));
    }

    public function test_progress_renders_the_server_percent_bar_and_old_specs_render_unchanged(): void
    {
        $spec = BlockCatalog::validate($this->b2Spec([['type' => 'progress', 'rows' => [
            ['فروش', '200', '150'],
            ['هزینه', '100', '120'],
            ['کیفیت', '—', '3'],
        ]]]));

        $html = $this->renderReport($spec);

        $this->assertSame(3, substr_count($html, 'inline-block w-14 h-1.5'));
        $this->assertSame(2, substr_count($html, 'style="width:'));
        $this->assertStringContainsString('style="width: 75%"', $html);
        $this->assertStringContainsString('style="width: 100%"', $html);
        $this->assertStringContainsString('<bdi>۷۵٪</bdi>', $html);
        $this->assertStringContainsString('<bdi>۱۲۰٪</bdi>', $html);
        $this->assertStringNotContainsString('<bdi>75%</bdi>', $html);
        $this->assertStringNotContainsString('ring-inset', $html);

        $en = $this->renderReport(BlockCatalog::validate($this->b2Spec([['type' => 'progress', 'rows' => [['Sales', '200', '150']]]], 'en')), 'ltr');
        $this->assertStringContainsString('style="width: 75%"', $en);
        $this->assertStringContainsString('<bdi>75%</bdi>', $en);

        $old = $this->renderReport($this->fixtureSpec());
        $this->assertSame(4, substr_count($old, 'text-[11px] font-medium'));
        $this->assertStringNotContainsString('ring-inset', $old);
        $this->assertStringNotContainsString('<h2 class="text-sm font-bold text-[var(--md-sys-color-on-surface)]" dir="auto"></h2>', $old);
    }

    private function chartBlock(string $variant, array $columns, array $rows, array $extra = []): array
    {
        return ['type' => 'chart', 'variant' => $variant, 'columns' => $columns, 'rows' => $rows] + $extra;
    }

    public function test_block_catalog_normalizes_bar_and_pie_charts_and_folds_extra_pie_slices_into_other(): void
    {
        $bar = $this->b2Block($this->chartBlock('bar', ['فروردین', 'اردیبهشت'], [['فروش', '۱۰', '20'], ['هزینه', '5', '6 میلیون']], ['heading' => 'فروش', 'footnote' => 'میلیون ریال']));
        $this->assertSame('chart', $bar['type']);
        $this->assertSame('bar', $bar['kind']);
        $this->assertCount(2, $bar['labels']);
        $this->assertCount(2, $bar['series']);
        $this->assertSame([10.0, 20.0], $bar['series'][0]['values']);
        $this->assertSame(6.0, $bar['series'][1]['values'][1]);
        $this->assertSame('میلیون ریال', $bar['unit']);
        $this->assertSame('فروش', $bar['heading']);

        $letters = ['الف', 'ب', 'پ', 'ت', 'ث', 'ج', 'چ', 'ح'];
        $pie = $this->b2Block($this->chartBlock('pie', $letters, [['سهم', '10', '20', '30', '40', '50', '60', '70', '80']]));
        $this->assertCount(6, $pie['labels']);
        $this->assertSame('سایر', $pie['labels'][5]);
        $this->assertSame([10.0, 20.0, 30.0, 40.0, 50.0, 210.0], $pie['series'][0]['values']);
        $this->assertSame(360.0, array_sum($pie['series'][0]['values']));

        $en = $this->b2Block($this->chartBlock('pie', ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'], [['Share', '1', '1', '1', '1', '1', '1', '1', '1']]), 'en');
        $this->assertSame('Other', $en['labels'][5]);
        $this->assertSame(3.0, $en['series'][0]['values'][5]);

        $twoRows = $this->b2Block($this->chartBlock('pie', ['A', 'B'], [['s1', '3', '4'], ['s2', 'abc', 'x']]));
        $this->assertCount(1, $twoRows['series']);
        $this->assertSame([3.0, 4.0], $twoRows['series'][0]['values']);
    }

    public function test_block_catalog_rejects_unusable_charts_with_precise_errors(): void
    {
        $bar = fn (array $rows, array $columns = ['A', 'B']): array => $this->chartBlock('bar', $columns, $rows);
        $cases = [
            'variant must be bar or pie' => $this->chartBlock('line', ['A', 'B'], [['s', '1', '2']]),
            'one value per category' => $bar([['s', '1']]),
            'plain numbers' => $bar([['s', 'abc', '2']]),
            'plain numbers ' => $bar([['s', '1e999', '2']]),
            'plain numbers  ' => $bar([['s', '', '2']]),
            'out of range' => $bar([['s', str_repeat('9', 30), '2']]),
            'out of range ' => $bar([['s', '-' . str_repeat('9', 30), '2']]),
            'too many categories or series' => $bar([['s', ...array_fill(0, 13, '1')]], array_map('strval', range(1, 13))),
            'too many categories or series ' => $bar([['a', '1', '2'], ['b', '1', '2'], ['c', '1', '2'], ['d', '1', '2']]),
            'columns and rows are required' => $bar([['s', '1', '2']], ['', '']),
            'columns and rows are required ' => $bar([]),
            'must not be negative' => $this->chartBlock('pie', ['A', 'B'], [['s', '-1', '5']]),
            'sum to zero' => $this->chartBlock('pie', ['A', 'B'], [['s', '0', '0']]),
        ];

        foreach ($cases as $expected => $block) {
            $this->assertStringContainsString('block chart: ', $this->b2Rejection($block));
            $this->assertStringContainsString(trim($expected), $this->b2Rejection($block), $expected);
        }

        config(['ai.report.spec.chart_max_abs' => 100]);
        $this->assertStringContainsString('out of range', $this->b2Rejection($bar([['s', '101', '2']])));
        $this->assertSame([-100.0, 100.0], $this->b2Block($bar([['s', '-100', '100']]))['series'][0]['values']);

        $this->assertStringContainsString('block callout: variant is required', $this->b2Rejection(['type' => 'callout', 'variant' => 'bar', 'paragraphs' => ['x']]));
    }

    public function test_chart_values_labels_and_series_are_cleaned_rounded_and_idempotent(): void
    {
        $block = $this->b2Block($this->chartBlock('bar', ['', 'A', 'A', str_repeat('x', 100)], [
            ["  س\u{202E}ری  ", '-5', '۱٬۲۳۴', '۱٫۵', '1.123456'],
        ], ['heading' => ' عنوان ']));

        $this->assertSame(['—', 'A', 'A', str_repeat('x', 40)], $block['labels']);
        $this->assertSame('سری', $block['series'][0]['name']);
        $this->assertSame([-5.0, 1234.0, 1.5, 1.1235], $block['series'][0]['values']);
        $this->assertSame('عنوان', $block['heading']);

        $again = fn (array $stored, string $lang = 'fa'): array => BlockCatalog::validate($this->b2Spec([$stored], $lang))['pages'][0]['blocks'][0];
        $this->assertSame($block, $again($block));

        $pie = $this->b2Block($this->chartBlock('pie', ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'], [['s', '1.00005', '2', '3', '4', '5', '6', '7', '8.33333']], ['footnote' => 'واحد']), 'en');
        $this->assertSame($pie, $again($pie, 'en'));
        $this->assertSame('واحد', $again($pie, 'en')['unit']);
    }

    public function test_every_preset_block_name_exists_in_the_block_catalog(): void
    {
        $this->assertNotEmpty(config('ai.report.types'));
        foreach (config('ai.report.types') as $key => $type) {
            $this->assertSame([], array_diff($type['blocks'], BlockCatalog::TYPES), $key);
        }
    }

    public function test_the_chart_schema_prompt_and_caps_come_from_config(): void
    {
        config(['ai.report.spec.chart_labels' => 9, 'ai.report.spec.chart_series' => 2, 'ai.report.spec.pie_slices' => 4, 'ai.report.spec.columns' => 3]);

        $props = BlockCatalog::schema()['$defs']['block']['properties'];
        $this->assertSame(['info', 'warning', 'decision', 'bar', 'pie', null], $props['variant']['enum']);
        $this->assertSame(9, $props['columns']['maxItems']);
        $this->assertStringContainsString('Tables: at most 3 columns', $props['columns']['description']);
        $this->assertStringContainsString('Charts: category labels, at most 9.', $props['columns']['description']);

        $prompt = (new \ReflectionMethod(ReportAiService::class, 'systemPrompt'))->invoke(new ReportAiService());
        $this->assertStringContainsString('chart: variant (bar or pie), heading, columns (category labels, at most 9)', $prompt);
        $this->assertStringContainsString('rows (one series per row, at most 2: first cell the series name', $prompt);
        $this->assertStringContainsString('pie: exactly one row, at most 4 categories, no negatives', $prompt);
        $this->assertStringContainsString('Use a chart only for figures that also appear in a table or kpi_row.', $prompt);
        $this->assertStringNotContainsString('{chart_labels}', $prompt);
        $this->assertStringNotContainsString('{pie_slices}', $prompt);
    }

    public function test_a_chart_block_renders_a_safe_data_attribute_and_a_complete_fallback_table(): void
    {
        $label = "O'Neil \"Q1\" & <b>";
        $spec = BlockCatalog::validate($this->b2Spec([$this->chartBlock('bar', [$label, 'دوم', 'سوم'], [['فروش', '20', '1234.5', '0']], ['heading' => 'نمودار فروش', 'footnote' => 'میلیون ریال'])]));

        $html = $this->renderReport($spec);

        $this->assertStringContainsString('x-data="aiReportChart"', $html);
        $this->assertStringContainsString('<canvas', $html);
        $this->assertStringContainsString('ai-report-keep ai-report-chart', $html);
        $this->assertStringContainsString('h-64', $html);
        $this->assertStringContainsString('x-cloak x-show="drawn"', $html);
        $this->assertStringContainsString(':class="drawn ? \'sr-only\' : \'\'"', $html);
        $this->assertSame(1, preg_match('/data-chart="([^"]*)"/', $html, $m));
        $decoded = json_decode(html_entity_decode($m[1], ENT_QUOTES), true);
        $stored = $spec['pages'][0]['blocks'][0];
        $this->assertEquals($stored, $decoded);
        $this->assertSame($stored['labels'], $decoded['labels']);
        $this->assertSame($stored['kind'], $decoded['kind']);
        $this->assertSame(array_column($stored['series'], 'name'), array_column($decoded['series'], 'name'));
        $this->assertSame($label, $decoded['labels'][0]);
        $this->assertStringNotContainsString('<b>', $html);
        $this->assertSame(4, substr_count($html, '<tr>'));
        $this->assertStringContainsString('<bdi>۲۰</bdi>', $html);
        $this->assertStringContainsString('<bdi>۱٬۲۳۴٫۵</bdi>', $html);
        $this->assertStringContainsString('<bdi>۰</bdi>', $html);
        $this->assertStringContainsString('>دسته</th>', $html);
        $this->assertStringContainsString('میلیون ریال', $html);

        $en = $this->renderReport(BlockCatalog::validate($this->b2Spec([$this->chartBlock('bar', ['A'], [['S', '1234.5']])], 'en')), 'ltr');
        $this->assertStringContainsString('<bdi>1,234.5</bdi>', $en);
        $this->assertStringContainsString('>Category</th>', $en);

        $frozen = Blade::render('<x-dashboard.ai-report :spec="$spec" :dir="$dir" :live="false"/>', ['spec' => $spec, 'dir' => 'rtl']);
        $this->assertStringContainsString('x-data="{ drawn: false }"', $frozen);
        $this->assertStringNotContainsString('aiReportChart', $frozen);
        $this->assertSame(4, substr_count($frozen, '<tr>'));
        preg_match_all('/x-show="([^"]*)"/', $frozen, $shows);
        $this->assertEqualsCanonicalizing(['drawn'], $shows[1]);
        $this->assertStringContainsString(":class=\"drawn ? 'sr-only' : ''\"", $frozen);

        $other = BlockCatalog::validate($this->b2Spec([$this->chartBlock('bar', [$label, 'دوم', 'سوم'], [['فروش', '21', '1234.5', '0']])]));
        preg_match('/wire:key="(ai-report-chart-[0-9a-f]{8})"/', $html, $k1);
        preg_match('/wire:key="(ai-report-chart-[0-9a-f]{8})"/', $this->renderReport($other), $k2);
        $this->assertNotSame($k1[1], $k2[1]);
    }

    public function test_the_viewer_renders_the_chart_through_the_component(): void
    {
        $owner = $this->user();
        $r = $this->report($owner, ['spec' => BlockCatalog::validate($this->b2Spec([$this->chartBlock('pie', ['الف', 'ب'], [['سهم', '3', '4']])]))]);

        Livewire::actingAs($owner)->withQueryParams(['r' => $r->id])->test(Main::class)
            ->assertSeeHtml('x-data="aiReportChart"')
            ->assertSeeHtml('<canvas')
            ->assertSee('دسته');
    }

    public function test_malformed_chart_blocks_never_break_the_page(): void
    {
        $spec = $this->b2Spec([
            ['type' => 'chart'],
            ['type' => 'chart', 'kind' => 'bar', 'labels' => ['a', ['nested'], 3], 'series' => [
                ['name' => 'x', 'values' => ['abc', NAN, null]],
                'junk',
                ['values' => 'str'],
            ], 'heading' => ['bad'], 'unit' => 5],
            ['type' => 'chart', 'kind' => 'pie', 'labels' => 'nope', 'series' => 'nope'],
            ['type' => 'bogus', 'labels' => ['z']],
        ]);

        $html = $this->renderReport($spec);

        $this->assertSame(3, substr_count($html, 'ai-report-keep ai-report-chart'));
        $this->assertSame(3, substr_count($html, 'data-chart="'));
        $this->assertSame(6, substr_count($html, '<bdi>—</bdi>'));
        $this->assertStringNotContainsString('bogus', $html);
    }

    private function blockRefineReport(User $owner, array $attributes = []): AiReport
    {
        $spec = BlockCatalog::validate([
            'lang' => 'fa',
            'title' => 'گزارش اصلی',
            'pages' => [[
                'blocks' => [
                    ['type' => 'title', 'heading' => 'گزارش اصلی'],
                    ['type' => 'summary', 'heading' => 'خلاصه', 'bullets' => ['اول', 'دوم'], 'highlight' => 'نکته'],
                    ['type' => 'section', 'heading' => config('ai.report.assumptions.heading.fa'), 'paragraphs' => ['مفروض اول']],
                ],
            ]],
        ]);

        return $this->report($owner, ['spec' => $spec, 'title' => 'گزارش اصلی', ...$attributes]);
    }

    private function aiResponse(array $spec): \GuzzleHttp\Promise\PromiseInterface
    {
        return Http::response([
            'choices' => [['message' => ['content' => json_encode($spec, JSON_UNESCAPED_UNICODE)], 'finish_reason' => 'stop']],
            'usage' => ['total_tokens' => 5],
        ]);
    }

    public function test_a_utf16_csv_is_transcoded_instead_of_skipped_as_corrupt(): void
    {
        Storage::disk('local')->put('ai-report/tmp/8/1-sales.csv', "\xFF\xFE" . mb_convert_encoding("name,amount\nalpha,10", 'UTF-16LE', 'UTF-8'));

        $out = app(FileExtractor::class)->extract('local', 'ai-report/tmp/8');

        $this->assertStringContainsString('alpha,10', $out['text']);
        $this->assertSame([], $out['skipped']);
    }

    public function test_a_windows_1256_csv_is_transcoded_instead_of_skipped_as_corrupt(): void
    {
        Storage::disk('local')->put('ai-report/tmp/9/1-sales.csv', iconv('UTF-8', 'CP1256', "نام,مبلغ\nسلام,10"));

        $out = app(FileExtractor::class)->extract('local', 'ai-report/tmp/9');

        $this->assertStringContainsString('سلام,10', $out['text']);
        $this->assertSame([], $out['skipped']);
    }

    public function test_a_block_refine_whose_block_vanished_in_another_tab_is_refused_not_widened(): void
    {
        Bus::fake();
        $owner = $this->user();
        $report = $this->blockRefineReport($owner);

        $component = Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->call('editBlock', '0:1');

        $spec = $report->fresh()->spec;
        array_splice($spec['pages'][0]['blocks'], 1, 2);
        AiReport::whereKey($report->id)->update(['spec' => json_encode($spec)]);

        $component
            ->set('form.prompt', 'خلاصه را بسیار کوتاه‌تر کن')
            ->call('refine')
            ->assertHasErrors(['form.prompt']);

        Bus::assertNothingDispatched();
    }

    public function test_a_block_refine_sends_only_that_block_and_merges_it_in_place(): void
    {
        Bus::fake();
        config(['ai.gateway.model' => 'Gemini-3-Flash-Preview']);
        $owner = $this->user();
        $report = $this->blockRefineReport($owner);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->call('editBlock', '0:1')
            ->assertSet('form.block', '0:1')
            ->assertDispatched('ai-report-open')
            ->set('form.prompt', 'خلاصه را بسیار کوتاه‌تر کن')
            ->call('refine');

        $parent = $report->fresh();
        $this->assertSame('0:1', $parent->lastVersion()['block']);
        $this->assertSame(1, AiReport::quotaUsed($owner->id));

        $revised = ['type' => 'summary', 'heading' => 'خلاصهٔ کوتاه', 'bullets' => ['فقط یک نکته'], 'highlight' => 'نکته'];
        Http::fake(['*' => $this->aiResponse(['lang' => 'fa', 'title' => 'irrelevant', 'pages' => [['blocks' => [$revised]]]])]);
        (new GenerateReportJob($parent->id))->handle(app(ReportAiService::class), app(FileExtractor::class));

        $userParts = Http::recorded()[0][0]['messages'][1]['content'];
        $userBlock = end($userParts)['text'];
        $this->assertSame(1, count(Http::recorded()));
        $this->assertStringContainsString('CURRENT_BLOCK', $userBlock);
        $this->assertStringContainsString('"type":"summary"', $userBlock);
        $this->assertStringContainsString('BLOCK MODE: pages[0].blocks holds EXACTLY ONE block', Http::recorded()[0][0]['messages'][0]['content']);
        $this->assertStringNotContainsString('CURRENT_SPEC', $userBlock);

        $ready = $report->fresh();
        $this->assertSame(AiReport::STATUS_READY, $ready->status);
        $blocks = $ready->spec['pages'][0]['blocks'];
        $this->assertSame(['title', 'summary', 'section'], array_column($blocks, 'type'));
        $this->assertSame('خلاصهٔ کوتاه', $blocks[1]['heading']);
        $this->assertSame(['فقط یک نکته'], $blocks[1]['bullets']);
        $this->assertSame('گزارش اصلی', $blocks[0]['heading']);
        $this->assertSame(config('ai.report.assumptions.heading.fa'), $blocks[2]['heading']);
        $this->assertSame(['مفروض اول'], $blocks[2]['paragraphs']);
        $this->assertCount(3, $blocks);
    }

    public function test_a_title_block_refine_updates_the_spec_title_and_quota_stays_at_one(): void
    {
        Bus::fake();
        config(['ai.gateway.model' => 'Gemini-3-Flash-Preview']);
        $owner = $this->user();
        $report = $this->blockRefineReport($owner);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->call('editBlock', '0:0')
            ->set('form.prompt', 'عنوان را رسمی‌تر بازنویسی کن')
            ->call('refine');

        Http::fake(['*' => $this->aiResponse(['lang' => 'fa', 'title' => 'ignored', 'pages' => [['blocks' => [['type' => 'title', 'heading' => 'گزارش رسمی']]]]])]);
        (new GenerateReportJob($report->fresh()->id))->handle(app(ReportAiService::class), app(FileExtractor::class));

        $ready = $report->fresh();
        $this->assertSame('گزارش رسمی', $ready->spec['title']);
        $this->assertSame('گزارش رسمی', $ready->title);
        $this->assertSame('گزارش رسمی', $ready->spec['pages'][0]['blocks'][0]['heading']);
        $this->assertSame(1, AiReport::quotaUsed($owner->id));
    }

    public function test_a_block_refine_of_the_assumptions_section_keeps_its_exact_heading(): void
    {
        Bus::fake();
        config(['ai.gateway.model' => 'Gemini-3-Flash-Preview']);
        $owner = $this->user();
        $report = $this->blockRefineReport($owner);
        $heading = config('ai.report.assumptions.heading.fa');

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->call('editBlock', '0:2')
            ->set('form.prompt', 'مفروضات را دقیق‌تر توضیح بده')
            ->call('refine');

        Http::fake(['*' => $this->aiResponse(['lang' => 'fa', 'title' => 'x', 'pages' => [[
            'blocks' => [['type' => 'section', 'heading' => 'مفروضات و محدودیتها', 'bullets' => ['مفروض دقیق']],
        ]]]])]);
        (new GenerateReportJob($report->fresh()->id))->handle(app(ReportAiService::class), app(FileExtractor::class));

        $blocks = $report->fresh()->spec['pages'][0]['blocks'];
        $this->assertSame(['title', 'summary', 'section'], array_column($blocks, 'type'));
        $this->assertSame($heading, $blocks[2]['heading']);
        $this->assertSame(['مفروض دقیق'], $blocks[2]['bullets']);
    }

    public function test_a_block_refine_whose_type_changes_fails_with_invalid_spec(): void
    {
        Bus::fake();
        config(['ai.gateway.model' => 'Gemini-3-Flash-Preview']);
        $owner = $this->user();
        $report = $this->blockRefineReport($owner);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->call('editBlock', '0:1')
            ->set('form.prompt', 'خلاصه را بسیار کوتاه‌تر کن')
            ->call('refine');

        Http::fake(['*' => $this->aiResponse(['lang' => 'fa', 'title' => 'x', 'pages' => [[
            'blocks' => [['type' => 'section', 'heading' => 'نوع اشتباه', 'paragraphs' => ['x']]],
        ]]])]);
        (new GenerateReportJob($report->fresh()->id))->handle(app(ReportAiService::class), app(FileExtractor::class));

        $failed = $report->fresh();
        $this->assertSame(AiReport::STATUS_FAILED, $failed->status);
        $this->assertSame('invalid_spec', $failed->fail_reason);
        $this->assertSame(['title', 'summary', 'section'], array_column($failed->spec['pages'][0]['blocks'], 'type'));
        $this->assertSame(0, AiReport::quotaUsed($owner->id));
    }

    public function test_a_block_refine_of_a_shared_report_forks_with_the_block_on_the_version(): void
    {
        Bus::fake();
        $owner = $this->user();
        $recipient = $this->user();
        $shared = $this->blockRefineReport($owner, ['recipients' => [(int) $recipient->id]]);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $shared])
            ->call('editBlock', '0:1')
            ->set('form.prompt', 'خلاصه را بسیار کوتاه‌تر کن')
            ->call('refine');

        $fork = AiReport::query()->ownedBy($owner->id)->where('id', '!=', $shared->id)->firstOrFail();
        $this->assertSame(AiReport::STATUS_GENERATING, $fork->status);
        $this->assertSame($shared->id, $fork->forked_from);
        $this->assertSame('0:1', $fork->lastVersion()['block']);
        $this->assertSame($shared->id, $fork->lastVersion()['forked_from']);
        $this->assertSame(AiReport::STATUS_READY, $shared->fresh()->status);
    }

    public function test_edit_block_ignores_bad_references_and_close_clears_the_target(): void
    {
        Bus::fake();
        $owner = $this->user();
        $recipient = $this->user();
        $report = $this->blockRefineReport($owner, ['recipients' => [(int) $recipient->id]]);

        $component = Livewire::actingAs($owner)->test(Main::class, ['report' => $report]);
        $component->call('editBlock', '0:9')->assertNotSet('form.block', '0:9');
        $component->call('editBlock', 'x:y')->assertNotSet('form.block', 'x:y');
        $component->call('editBlock', '99:99')->assertNotSet('form.block', '99:99');

        Livewire::actingAs($recipient)->test(Main::class, ['report' => $report])->call('editBlock', '0:1')->assertSet('form.block', null);
        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->call('editBlock', '0:1')
            ->assertSet('form.block', '0:1')
            ->call('clearBlock')
            ->assertSet('form.block', null);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->call('editBlock', '0:1')
            ->assertSet('form.block', '0:1')
            ->call('close')
            ->assertSet('form.block', null);

        Livewire::actingAs($owner)->test(Main::class)
            ->set('form.prompt', 'یک درخواست آزمایشی')
            ->call('open', $report->id)
            ->assertSet('form.block', null);
        $this->assertSame(0, AiReport::quotaUsed($owner->id));
        $this->assertSame(AiReport::STATUS_READY, $report->fresh()->status);
    }

    public function test_ai_actions_wear_the_ai_variant_and_the_sparkle_while_manual_edit_stays_neutral(): void
    {
        $owner = $this->user();
        $button = fn (string $html, string $needle) => preg_match('/<button[^>]*' . preg_quote($needle, '/') . '.*?<\/button>/s', $html, $m) === 1 ? $m[0] : '';

        $fresh = Livewire::actingAs($owner)->test(Main::class)->html();
        $generate = $button($fresh, 'wire:click="generate"');
        $this->assertStringContainsString('bg-[var(--ai-bg)]', $generate);
        $this->assertStringContainsString('auto_awesome', $generate);
        $this->assertStringNotContainsString('md3-btn', $generate);

        $report = $this->blockRefineReport($owner);
        $open = Livewire::actingAs($owner)->test(Main::class, ['report' => $report])->html();

        $refine = $button($open, 'wire:click="refine"');
        $this->assertStringContainsString('bg-[var(--ai-bg)]', $refine);
        $this->assertStringContainsString('auto_awesome', $refine);
        $this->assertStringNotContainsString('autorenew', $refine);

        $blockRefine = $button($open, 'wire:click="editBlock(\'');
        $this->assertStringContainsString('text-[var(--ai-color)]', $blockRefine);
        $this->assertStringContainsString('auto_awesome', $blockRefine);
        $this->assertStringNotContainsString('autorenew', $blockRefine);

        $manual = $button($open, 'wire:click="openBlockEditor(\'');
        $this->assertStringContainsString('>edit<', $manual);
        $this->assertStringNotContainsString('--ai-', $manual);
        $this->assertStringNotContainsString('auto_awesome', $manual);
    }

    public function test_the_owner_sees_per_block_buttons_and_read_only_viewers_do_not(): void
    {
        $owner = $this->user();
        $recipient = $this->user();
        $private = $this->blockRefineReport($owner);
        $shared = $this->blockRefineReport($owner, ['recipients' => [(int) $recipient->id]]);

        $privateHtml = Livewire::actingAs($owner)->test(Main::class, ['report' => $private])->html();
        $this->assertSame(3, substr_count($privateHtml, 'wire:click="editBlock('));
        $this->assertSame(3, substr_count($privateHtml, 'wire:click="openBlockEditor('));

        $chipped = Livewire::actingAs($owner)->test(Main::class, ['report' => $private])->call('editBlock', '0:1')->html();
        $this->assertStringContainsString('فقط «خلاصه» اصلاح می‌شود', $chipped);
        $this->assertStringContainsString('wire:click="clearBlock"', $chipped);

        $sharedHtml = Livewire::actingAs($owner)->test(Main::class, ['report' => $shared])->html();
        $this->assertSame(3, substr_count($sharedHtml, 'wire:click="editBlock('));
        $this->assertSame(0, substr_count($sharedHtml, 'wire:click="openBlockEditor('));

        $recipientHtml = Livewire::actingAs($recipient)->test(Main::class, ['report' => $shared])->html();
        $this->assertSame(0, substr_count($recipientHtml, 'wire:click="editBlock('));
        $this->assertSame(0, substr_count($recipientHtml, 'wire:click="openBlockEditor('));
    }

    public function test_closing_the_block_editor_modal_discards_the_draft_and_a_forged_open_stays_shut(): void
    {
        $owner = $this->user();
        $report = $this->report($owner, ['spec' => BlockCatalog::validate($this->spec())]);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->call('openBlockEditor', '0:1')
            ->assertSet('blockEditorOpen', true)
            ->set('blockEditorOpen', false)
            ->assertSet('blockRef', null)
            ->assertSet('blockDraft', [])
            ->set('blockEditorOpen', true)
            ->assertSet('blockEditorOpen', false);
    }

    public function test_a_manual_text_edit_persists_adds_a_manual_version_and_keeps_the_quota(): void
    {
        $owner = $this->user();
        $report = $this->report($owner, ['spec' => BlockCatalog::validate($this->spec()), 'title' => 'گزارش آزمایشی']);
        $versionsBefore = count($report->versions);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->call('openBlockEditor', '0:1')
            ->assertSet('blockRef', '0:1')
            ->assertSet('blockDraft.type', 'table')
            ->assertSet('blockEditorOpen', true)
            ->set('blockDraft.heading', 'جدول تازه')
            ->call('saveBlock')
            ->assertDispatched('toast', fn ($event, $params) => ($params['message'] ?? null) === 'بخش ویرایش شد و به‌عنوان نسخهٔ تازه ذخیره شد.' && ($params['type'] ?? null) === 'success')
            ->assertSet('blockEditorOpen', false)
            ->assertSet('blockRef', null)
            ->assertSet('blockDraft', []);

        $report = $report->fresh();
        $this->assertSame('جدول تازه', $report->spec['pages'][0]['blocks'][1]['heading']);
        $this->assertSame(config('ai.report.assumptions.heading.fa'), $report->spec['pages'][0]['blocks'][2]['heading']);
        $this->assertSame($versionsBefore + 1, count($report->versions));
        $last = $report->lastVersion();
        $this->assertTrue($last['manual'] ?? false);
        $this->assertSame('ویرایش دستی', $last['prompt']);
        $this->assertSame(0, AiReport::quotaUsed($owner->id));

        $cards = (new AiReportPresenter())->versions($report);
        $card = end($cards);
        $this->assertStringContainsString(' · دستی', $card['label']);
        $this->assertTrue($card['is_current']);
    }

    public function test_a_manual_number_edit_rewrites_the_total_and_flags_a_percent_mismatch(): void
    {
        $owner = $this->user();
        $report = $this->report($owner, ['spec' => BlockCatalog::validate($this->spec()), 'title' => 'گزارش آزمایشی']);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->call('openBlockEditor', '0:1')
            ->set('blockDraft.rows.0.1', '۳۰۰۰')
            ->set('blockDraft.rows.0.2', '50')
            ->call('saveBlock')
            ->assertDispatched('toast', fn ($event, $params) => ($params['message'] ?? null) === 'بخش ویرایش شد و به‌عنوان نسخهٔ تازه ذخیره شد.' && ($params['type'] ?? null) === 'success');

        $block = $report->fresh()->spec['pages'][0]['blocks'][1];
        $this->assertSame('۴۰۰۰', $block['total'][1]);
        $this->assertSame('۱۱۰', $block['total'][2]);
        $this->assertSame('mismatch', $block['check']);
        $this->assertSame('۵۰', $block['rows'][0][2]);
        $this->assertSame(2, $block['percent_column']);
    }

    public function test_manual_edit_is_refused_for_a_shared_report_a_recipient_and_a_blank_heading(): void
    {
        $owner = $this->user();
        $recipient = $this->user();
        $shared = $this->blockRefineReport($owner, ['recipients' => [(int) $recipient->id]]);
        $private = $this->blockRefineReport($owner);

        $sharedOwner = Livewire::actingAs($owner)->test(Main::class, ['report' => $shared]);
        $sharedOwner->call('openBlockEditor', '0:1')->assertSet('blockRef', null);
        $sharedHtml = $sharedOwner->html();
        $this->assertStringNotContainsString('wire:key="ai-report-block-manual-0-0"', $sharedHtml);
        $this->assertStringContainsString('wire:key="ai-report-block-refine-0-0"', $sharedHtml);

        $recipientView = Livewire::actingAs($recipient)->test(Main::class, ['report' => $shared]);
        $recipientView->call('openBlockEditor', '0:1')->assertSet('blockRef', null);
        $recipientView->call('saveBlock')->assertNotDispatched('toast');

        foreach ([[$shared, $owner], [$shared, $recipient]] as [$denied, $who]) {
            try {
                app(UpdateReportAction::class)->execute($denied, $who, ['block' => '0:1', 'data' => $denied->spec['pages'][0]['blocks'][1]]);
                $this->fail('expected a denied manual block save');
            } catch (\RuntimeException) {
                $this->addToAssertionCount(1);
            }
        }
        $this->assertSame(1, count($shared->fresh()->versions));
        $this->assertSame(0, AiReport::quotaUsed($owner->id));

        $before = count($private->versions);
        Livewire::actingAs($owner)->test(Main::class, ['report' => $private])
            ->call('openBlockEditor', '0:0')
            ->assertSet('blockRef', '0:0')
            ->set('blockDraft.heading', '')
            ->call('saveBlock')
            ->assertHasErrors(['blockDraft'])
            ->assertNotDispatched('toast')
            ->assertSet('blockRef', '0:0');
        $this->assertSame($before, count($private->fresh()->versions));
        $this->assertSame('گزارش اصلی', $private->fresh()->spec['pages'][0]['blocks'][0]['heading']);
    }

    public function test_manual_edit_keeps_the_stored_type_and_ignores_a_missing_reference(): void
    {
        $owner = $this->user();
        $report = $this->report($owner, ['spec' => BlockCatalog::validate($this->spec()), 'title' => 'گزارش آزمایشی']);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->call('openBlockEditor', '0:1')
            ->set('blockDraft.type', 'section')
            ->set('blockDraft.heading', 'جدول تغییرنما')
            ->call('saveBlock')
            ->assertDispatched('toast', fn ($event, $params) => ($params['message'] ?? null) === 'بخش ویرایش شد و به‌عنوان نسخهٔ تازه ذخیره شد.' && ($params['type'] ?? null) === 'success');

        $block = $report->fresh()->spec['pages'][0]['blocks'][1];
        $this->assertSame('table', $block['type']);
        $this->assertSame('جدول تغییرنما', $block['heading']);
        $this->assertSame(2, count($report->fresh()->spec['pages'][0]['blocks'][1]['rows']));

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->call('openBlockEditor', '7:7')
            ->assertSet('blockRef', null)
            ->call('saveBlock')
            ->assertNotDispatched('toast');
        $this->assertSame(AiReport::STATUS_READY, $report->fresh()->status);
        $this->assertSame(0, AiReport::quotaUsed($owner->id));
    }

    public function test_manual_edit_of_the_assumptions_section_keeps_its_exact_heading(): void
    {
        $owner = $this->user();
        $report = $this->blockRefineReport($owner);
        $heading = (string) config('ai.report.assumptions.heading.fa');

        $editor = Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->call('openBlockEditor', '0:2')
            ->assertSet('blockRef', '0:2');
        $html = $editor->html();
        $this->assertStringContainsString('disabled', $html);
        $this->assertStringContainsString($heading, $html);

        $editor->set('blockDraft.paragraphs.0', 'مفروض بازنویسی‌شده')
            ->call('saveBlock')
            ->assertDispatched('toast', fn ($event, $params) => ($params['message'] ?? null) === 'بخش ویرایش شد و به‌عنوان نسخهٔ تازه ذخیره شد.' && ($params['type'] ?? null) === 'success');

        $block = $report->fresh()->spec['pages'][0]['blocks'][2];
        $this->assertSame($heading, $block['heading']);
        $this->assertSame(['مفروض بازنویسی‌شده'], $block['paragraphs']);
        $this->assertSame(3, count($report->fresh()->spec['pages'][0]['blocks']));
    }

    public function test_manual_edit_renormalizes_every_block_type_idempotently(): void
    {
        $owner = $this->user();
        $blocks = [
            ['type' => 'title', 'heading' => 'گزارش', 'subtitle' => 'آبان'],
            ['type' => 'summary', 'heading' => 'خلاصه', 'bullets' => ['اول'], 'highlight' => 'نکته'],
            ['type' => 'kpi_row', 'items' => [['label' => 'نفر', 'value' => '۵'], ['label' => 'ریال', 'value' => '۱۰۰']]],
            ['type' => 'section', 'heading' => 'بخش', 'paragraphs' => ['متن بخش']],
            ['type' => 'callout', 'variant' => 'info', 'heading' => 'یادداشت', 'paragraphs' => ['متن']],
            ['type' => 'table', 'heading' => 'جدول', 'columns' => ['نام', 'مبلغ'], 'rows' => [['یک', '۱۰۰۰'], ['دو', '۲۰۰۰']], 'total' => ['جمع', '۳۰۰۰']],
            ['type' => 'chart', 'kind' => 'bar', 'heading' => 'نمودار', 'labels' => ['الف', 'ب'], 'series' => [['name' => 'سری', 'values' => [3, 5]]]],
        ];
        $report = $this->report($owner, ['spec' => BlockCatalog::validate(['lang' => 'fa', 'title' => 'گزارش', 'pages' => [['blocks' => $blocks]]]), 'title' => 'گزارش']);
        $this->assertSame(count($blocks) + 1, count($report->spec['pages'][0]['blocks']));

        foreach (array_keys($blocks) as $b) {
            Livewire::actingAs($owner)->test(Main::class, ['report' => $report->fresh()])
                ->call('openBlockEditor', '0:' . $b)
                ->assertSet('blockRef', '0:' . $b)
                ->call('saveBlock')
                ->assertDispatched('toast', fn ($event, $params) => ($params['message'] ?? null) === 'بخش ویرایش شد و به‌عنوان نسخهٔ تازه ذخیره شد.' && ($params['type'] ?? null) === 'success');
        }

        $final = $report->fresh();
        $this->assertSame(count($blocks) + 1, count($final->spec['pages'][0]['blocks']));
        $this->assertSame(array_column($blocks, 'type'), array_slice(array_column($final->spec['pages'][0]['blocks'], 'type'), 0, count($blocks)));
        $this->assertSame(count($blocks) + count($report->versions), count($final->versions));

        $more = [
            ['type' => 'facts', 'items' => [['label' => 'منبع', 'value' => 'پیوست']]],
            ['type' => 'action_items', 'heading' => 'اقدام‌ها', 'rows' => [['نظافت داده‌ها', 'مسئول', 'هفته', 'در حال انجام']]],
            ['type' => 'progress', 'heading' => 'پیشرفت', 'rows' => [['کار', '۱۰۰', '۶۰']]],
        ];
        $second = $this->report($owner, ['spec' => BlockCatalog::validate(['lang' => 'fa', 'title' => 'گزارش دوم', 'pages' => [['blocks' => $more]]]), 'title' => 'گزارش دوم']);
        $this->assertSame(count($more) + 1, count($second->spec['pages'][0]['blocks']));

        foreach (array_keys($more) as $b) {
            Livewire::actingAs($owner)->test(Main::class, ['report' => $second->fresh()])
                ->call('openBlockEditor', '0:' . $b)
                ->assertSet('blockRef', '0:' . $b)
                ->call('saveBlock')
                ->assertDispatched('toast', fn ($event, $params) => ($params['message'] ?? null) === 'بخش ویرایش شد و به‌عنوان نسخهٔ تازه ذخیره شد.' && ($params['type'] ?? null) === 'success');
        }

        $secondFinal = $second->fresh();
        $this->assertSame(array_column($more, 'type'), array_slice(array_column($secondFinal->spec['pages'][0]['blocks'], 'type'), 0, count($more)));
        $this->assertSame(count($more) + count($second->versions), count($secondFinal->versions));
    }

    public function test_a_manual_title_block_edit_updates_the_heading_spec_title_and_row_title_together(): void
    {
        $owner = $this->user();
        $report = $this->report($owner, ['spec' => BlockCatalog::validate($this->spec()), 'title' => 'گزارش آزمایشی']);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->call('openBlockEditor', '0:0')
            ->assertSet('blockRef', '0:0')
            ->set('blockDraft.heading', 'عنوان تازهٔ رسمی')
            ->call('saveBlock')
            ->assertDispatched('toast', fn ($event, $params) => ($params['message'] ?? null) === 'بخش ویرایش شد و به‌عنوان نسخهٔ تازه ذخیره شد.' && ($params['type'] ?? null) === 'success');

        $fresh = $report->fresh();
        $this->assertSame('عنوان تازهٔ رسمی', $fresh->spec['pages'][0]['blocks'][0]['heading']);
        $this->assertSame('عنوان تازهٔ رسمی', $fresh->spec['title']);
        $this->assertSame('عنوان تازهٔ رسمی', $fresh->title);
        $this->assertSame(0, AiReport::quotaUsed($owner->id));
    }

    public function test_a_manual_edit_never_rewrites_a_total_that_was_not_a_sum(): void
    {
        $owner = $this->user();
        $spec = BlockCatalog::validate([
            'lang' => 'fa',
            'title' => 'گزارش نرخ‌ها',
            'pages' => [[
                'blocks' => [
                    ['type' => 'title', 'heading' => 'گزارش نرخ‌ها'],
                    [
                        'type' => 'table',
                        'heading' => 'نرخ‌ها',
                        'columns' => ['ماده', 'میانگین قیمت', 'درصد'],
                        'rows' => [['الف', '۱۵', '۴۰'], ['ب', '۲۵', '۶۰']],
                        'total' => ['میانگین', '۲۰', '۱۰۰'],
                        'percent_column' => 2,
                    ],
                ],
            ]],
        ]);
        $report = $this->report($owner, ['spec' => $spec, 'title' => 'گزارش نرخ‌ها']);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->call('openBlockEditor', '0:1')
            ->set('blockDraft.rows.0.0', 'الف ویزه')
            ->set('blockDraft.rows.0.1', '۱۷')
            ->call('saveBlock')
            ->assertDispatched('toast', fn ($event, $params) => ($params['message'] ?? null) === 'بخش ویرایش شد و به‌عنوان نسخهٔ تازه ذخیره شد.' && ($params['type'] ?? null) === 'success');

        $block = $report->fresh()->spec['pages'][0]['blocks'][1];
        $this->assertSame('میانگین', $block['total'][0]);
        $this->assertSame('۲۰', $block['total'][1]);
        $this->assertSame('۱۰۰', $block['total'][2]);
        $this->assertSame('۱۷', $block['rows'][0][1]);
    }

    public function test_a_block_refine_of_the_final_callout_merges_the_matching_reply_block(): void
    {
        Bus::fake();
        config(['ai.gateway.model' => 'Gemini-3-Flash-Preview']);
        $owner = $this->user();
        $spec = BlockCatalog::validate([
            'lang' => 'fa',
            'title' => 'گزارش تصمیم',
            'pages' => [[
                'blocks' => [
                    ['type' => 'title', 'heading' => 'گزارش تصمیم'],
                    ['type' => 'callout', 'variant' => 'decision', 'heading' => 'تصمیم', 'paragraphs' => ['متن تصمیم']],
                ],
            ]],
        ]);
        $report = $this->report($owner, ['spec' => $spec, 'title' => 'گزارش تصمیم']);
        $callout = $report->spec['pages'][0]['blocks'][2];

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->call('editBlock', '0:2')
            ->set('form.prompt', 'تصمیم را قاطع‌تر بازنویسی کن')
            ->call('refine');
        Bus::assertDispatched(GenerateReportJob::class);

        $reply = ['lang' => 'fa', 'title' => 'گزارش تصمیم', 'pages' => [[
            'blocks' => [
                ['type' => 'section', 'heading' => 'مفروضات و محدودیتها', 'paragraphs' => ['مفروض']],
                ['type' => 'callout', 'variant' => 'decision', 'heading' => 'تصمیم', 'paragraphs' => ['متن تصمیم قاطع']],
            ],
        ]]];
        Http::fake(['*' => $this->aiResponse($reply)]);
        (new GenerateReportJob($report->fresh()->id))->handle(app(ReportAiService::class), app(FileExtractor::class));

        $fresh = $report->fresh();
        $this->assertSame(AiReport::STATUS_READY, $fresh->status);
        $this->assertSame('متن تصمیم قاطع', $fresh->spec['pages'][0]['blocks'][2]['paragraphs'][0]);
        $this->assertSame(3, count($fresh->spec['pages'][0]['blocks']));
        $this->assertSame($callout['variant'], $fresh->spec['pages'][0]['blocks'][2]['variant']);
    }

    public function test_a_block_refine_sends_no_report_type_or_length_rule_but_keeps_audience(): void
    {
        Bus::fake();
        config(['ai.gateway.model' => 'Gemini-3-Flash-Preview']);
        $owner = $this->user();
        $report = $this->blockRefineReport($owner);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->call('editBlock', '0:1')
            ->set('form.prompt', 'خلاصه را بسیار کوتاه‌تر کن')
            ->set('form.type', 'monthly')
            ->set('form.length', 'detailed')
            ->set('form.audience', 'technical')
            ->call('refine');

        Http::fake(['*' => $this->aiResponse(['lang' => 'fa', 'title' => 'گزارش اصلی', 'pages' => [[
            'blocks' => [['type' => 'summary', 'heading' => 'خلاصه', 'bullets' => ['کوتاه'], 'highlight' => 'نکته']],
        ]]])]);
        (new GenerateReportJob($report->fresh()->id))->handle(app(ReportAiService::class), app(FileExtractor::class));

        $system = Http::recorded()[0][0]['messages'][0]['content'];
        $this->assertStringNotContainsString('REPORT TYPE:', $system);
        $this->assertStringNotContainsString('LENGTH:', $system);
        $this->assertStringContainsString('AUDIENCE:', $system);
        $this->assertStringContainsString('BLOCK MODE:', $system);
        $this->assertSame(AiReport::STATUS_READY, $report->fresh()->status);
    }

    public function test_a_block_refine_sends_only_the_title_and_headings_of_the_other_sections_as_context(): void
    {
        Bus::fake();
        config(['ai.gateway.model' => 'Gemini-3-Flash-Preview']);
        $owner = $this->user();
        $report = $this->blockRefineReport($owner);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->call('editBlock', '0:1')
            ->set('form.prompt', 'خلاصه را بسیار کوتاه‌تر کن')
            ->call('refine');

        Http::fake(['*' => $this->aiResponse(['lang' => 'fa', 'title' => 'گزارش اصلی', 'pages' => [[
            'blocks' => [['type' => 'summary', 'heading' => 'خلاصه', 'bullets' => ['کوتاه'], 'highlight' => 'نکته']],
        ]]])]);
        (new GenerateReportJob($report->fresh()->id))->handle(app(ReportAiService::class), app(FileExtractor::class));

        $user = json_encode(Http::recorded()[0][0]['messages'][1]['content'], JSON_UNESCAPED_UNICODE);
        $this->assertStringContainsString('REPORT CONTEXT', $user);
        $this->assertStringContainsString('section headings in order', $user);
        $this->assertStringContainsString('FINAL REMINDER', $user);
        $this->assertStringNotContainsString('CURRENT_SPEC', $user);
    }

    public function test_a_retry_of_a_failed_block_refine_retargets_the_same_block(): void
    {
        Bus::fake();
        $owner = $this->user();
        $report = $this->blockRefineReport($owner);
        $versions = $report->versions;
        $versions[0]['block'] = '0:1';
        $versions[0]['prompt'] = 'درخواست کافی برای اعتبارسنجی';
        $report->update(['status' => AiReport::STATUS_FAILED, 'fail_reason' => 'invalid_spec', 'versions' => $versions]);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report->fresh()])
            ->call('editBlock', '0:2')
            ->assertSet('form.block', '0:2')
            ->call('retry');

        $this->assertSame('0:1', $report->fresh()->lastVersion()['block']);
        Bus::assertDispatched(GenerateReportJob::class);
    }

    public function test_emptying_a_list_line_is_rejected_instead_of_dropping_it(): void
    {
        $owner = $this->user();
        $report = $this->blockRefineReport($owner);

        $before = count($report->versions);
        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->call('openBlockEditor', '0:1')
            ->set('blockDraft.bullets.0', '')
            ->call('saveBlock')
            ->assertHasErrors(['blockDraft'])
            ->assertNotDispatched('toast')
            ->assertSet('blockRef', '0:1');
        $this->assertSame($before, count($report->fresh()->versions));
        $this->assertSame(['اول', 'دوم'], $report->fresh()->spec['pages'][0]['blocks'][1]['bullets']);
    }

    public function test_a_stale_editor_save_is_refused_when_another_tab_added_a_version(): void
    {
        $owner = $this->user();
        $report = $this->report($owner, ['spec' => BlockCatalog::validate($this->spec()), 'title' => 'گزارش آزمایشی']);

        $component = Livewire::actingAs($owner)->test(Main::class, ['report' => $report]);
        $component->call('openBlockEditor', '0:1')->assertSet('blockRef', '0:1');

        $fresh = $report->fresh();
        $changed = $fresh->spec;
        $changed['pages'][0]['blocks'][1]['heading'] = 'تب دیگر';
        $fresh->spec = $changed;
        $fresh->pushVersion(['prompt' => 'تب دیگر', 'files' => [], 'spec' => $changed, 'at' => now()->toIso8601String(), 'quota_day' => now()->format('Y-m-d')]);
        $fresh->save();

        $component->set('blockDraft.heading', 'جدول دیرهنگام')
            ->call('saveBlock')
            ->assertNotDispatched('toast', $this->toastType('success'))
            ->assertDispatched('toast', $this->toastType('warning'))
            ->assertSet('blockEditorOpen', false)
            ->assertSet('blockRef', null);
        $this->assertSame('تب دیگر', $report->fresh()->spec['pages'][0]['blocks'][1]['heading']);

        $component->call('openBlockEditor', '0:1')->assertSet('blockRef', '0:1');
        $versions = count($report->fresh()->versions);
        $fresh = $report->fresh();
        $reverted = $fresh->spec;
        $reverted['pages'][0]['blocks'][1]['heading'] = 'بازگشت';
        $fresh->spec = $reverted;
        $fresh->save();

        $component->set('blockDraft.heading', 'جدول دیرهنگام')
            ->call('saveBlock')
            ->assertNotDispatched('toast', $this->toastType('success'));
        $this->assertSame('بازگشت', $report->fresh()->spec['pages'][0]['blocks'][1]['heading']);
        $this->assertSame($versions, count($report->fresh()->versions));
    }

    public function test_a_no_refund_failure_hides_retry_and_refines_only_on_a_new_request(): void
    {
        Bus::fake();
        $owner = $this->user();
        $report = $this->blockRefineReport($owner);
        $report->update(['status' => AiReport::STATUS_FAILED, 'fail_reason' => 'too_large']);

        $component = Livewire::actingAs($owner)->test(Main::class, ['report' => $report->fresh()]);
        $html = $component->html();
        $this->assertStringNotContainsString('wire:click="retry"', $html);
        $this->assertStringContainsString('کوتاه‌تر یا روشن‌تر بنویسید', $html);
        $this->assertStringContainsString('wire:key="ai-report-retry-no-refund"', $html);
        $this->assertStringNotContainsString('wire:key="ai-report-retry-quota-note"', $html);
        $this->assertStringNotContainsString('یک سهمیه مصرف می‌کند', $html);

        $component->call('retry');
        Bus::assertNotDispatched(GenerateReportJob::class);
        $this->assertSame(0, AiReport::quotaUsed($owner->id));

        $refundable = $this->blockRefineReport($owner);
        $refundable->update(['status' => AiReport::STATUS_FAILED, 'fail_reason' => 'timeout']);
        $html = Livewire::actingAs($owner)->test(Main::class, ['report' => $refundable->fresh()])->html();
        $this->assertStringContainsString('wire:click="retry"', $html);
        $this->assertStringContainsString('یک سهمیه مصرف می‌کند', $html);
        $this->assertStringNotContainsString('wire:key="ai-report-retry-no-refund"', $html);
    }

    public function test_an_inactive_report_cannot_be_shared_and_hides_the_share_button(): void
    {
        $owner = $this->user();
        $report = $this->report($owner, ['active' => false]);

        $component = Livewire::actingAs($owner)->test(Main::class, ['report' => $report]);
        $html = $component->html();
        $this->assertStringNotContainsString('wire:key="ai-report-share-button"', $html);
        $this->assertStringNotContainsString('wire:key="ai-report-share-dialog"', $html);

        $component->call('share')->assertDispatched('toast', $this->toastType('warning'));
        $this->assertEmpty($report->fresh()->recipients);
    }

    public function test_an_inactive_owned_report_shows_the_activation_notice_instead_of_the_composer_form(): void
    {
        $owner = $this->user();
        $report = $this->report($owner, ['active' => false]);

        $component = Livewire::actingAs($owner)->test(Main::class, ['report' => $report]);
        $html = $component->html();
        $this->assertStringContainsString('این گزارش غیرفعال است', $html);
        $this->assertStringContainsString('فعال‌سازی دوباره', $html);
        $this->assertStringNotContainsString('ai-report-composer-form', $html);
        $this->assertStringNotContainsString('wire:click="refine"', $html);
        $this->assertStringNotContainsString('wire:click="generate"', $html);

        $component->call('toggleActive')->assertDispatched('toast', $this->toastType('success'));
        $this->assertTrue($report->fresh()->active);

        $html = $component->html();
        $this->assertStringNotContainsString('این گزارش غیرفعال است', $html);
        $this->assertStringContainsString('ai-report-composer-form', $html);
    }

    public function test_the_failed_card_shows_the_hint_for_the_failure_code(): void
    {
        $owner = $this->user();
        $presenter = new AiReportPresenter();

        $this->assertStringContainsString('به سهمیهٔ روز درخواست برگشت', $presenter->failHint('timeout'));
        $this->assertStringContainsString('به سهمیه برنمی‌گردد', $presenter->failHint('too_large'));
        $this->assertStringContainsString('به سهمیهٔ روز درخواست برگشت', $presenter->failHint('bogus'));

        $report = $this->blockRefineReport($owner);
        $report->update(['status' => AiReport::STATUS_FAILED, 'fail_reason' => 'invalid_spec']);
        $html = Livewire::actingAs($owner)->test(Main::class, ['report' => $report->fresh()])->html();
        $this->assertStringContainsString('نوبت مصرف‌شده برای این درخواست به سهمیهٔ همان روز برگشت', $html);
    }

    public function test_a_recipient_sees_the_read_only_banner_naming_the_sender(): void
    {
        $owner = $this->user(['name' => 'آرش فرستنده']);
        $recipient = $this->user();
        $report = $this->report($owner, ['recipients' => [(int) $recipient->id]]);
        $report->update(['seen_by' => []]);

        $html = Livewire::actingAs($recipient)->test(Main::class, ['report' => $report->fresh()])->html();
        $this->assertStringContainsString('امکان ویرایش آن را ندارید', $html);
        $this->assertStringContainsString('آرش فرستنده', $html);

        $html = Livewire::actingAs($owner)->test(Main::class, ['report' => $report->fresh()])->html();
        $this->assertStringNotContainsString('امکان ویرایش آن را ندارید', $html);
    }

    public function test_a_generating_report_renders_the_keyed_skeleton_with_eta_and_poll(): void
    {
        $owner = $this->user();
        $open = $this->generatingReport($owner);
        $seconds = config('ai.report.poll_seconds');

        Livewire::actingAs($owner)->test(Main::class, ['report' => $open])
            ->assertSeeHtml('wire:key="ai-report-generating-skeleton"')
            ->assertSeeHtml('aria-busy="true"')
            ->assertSee('معمولاً')
            ->assertSeeHtml('wire:poll.visible.' . $seconds . 's="pollStatus"');
    }

    public function test_the_header_carries_the_notification_legend_button_before_the_help_button_and_its_modal(): void
    {
        $html = Livewire::actingAs($this->user())->test(Main::class)->html();

        // Dialog modals render their name inside x-data (single-quoted JS), never as a name="..." attribute.
        $badgeDialog = strpos($html, "name: 'ai-report-badge-legend' }");
        $legendDialog = strpos($html, "name: 'ai-report-legend' }");

        $this->assertNotFalse($badgeDialog);
        $this->assertNotFalse($legendDialog);
        $this->assertLessThan(
            $legendDialog,
            strpos($html, "name: 'ai-report-badge-legend' })") // the header dispatch, before both dialogs
        );
    }

    public function test_the_notification_legend_shows_the_received_report_row_with_its_dot_surface(): void
    {
        $row = BadgeLegendCatalog::get('ai-report-controller');

        $this->assertNotNull($row);
        $this->assertSame('ai-report', $row['subgroup']);
        $this->assertSame('sapphire', $row['tone']);
        $this->assertArrayHasKey('surface', $row);

        $bell = BadgeLegendCatalog::get('ai-report-controller:nudge');

        $this->assertNotNull($bell);
        $this->assertSame('ai-report', $bell['subgroup']);
        $this->assertSame('sage', $bell['tone']);
        $this->assertArrayNotHasKey('surface', $bell);
        $this->assertStringContainsString(convertToPersian(config('ai.report.share_link_days')), $bell['clears']);

        $html = Livewire::actingAs($this->user())->test(Main::class)->html();

        $this->assertStringContainsString($row['label'], $html);
        $this->assertStringContainsString($bell['label'], $html);
    }

    public function test_the_legend_rows_go_away_with_the_module_for_a_tenant(): void
    {
        config(['app.off' => ['ai-report']]);

        $this->assertNull(BadgeLegendCatalog::get('ai-report-controller'));
        $this->assertNull(BadgeLegendCatalog::get('ai-report-controller:nudge'));
    }

    public function test_the_nudge_follows_the_share_through_open_unshare_deactivate_delete_and_failure(): void
    {
        $owner = $this->user();
        $recipient = $this->user();
        $report = $this->report($owner);
        $bells = fn () => DB::table('notifications')->where('notifiable_id', $recipient->id)->where('data->menu_key', 'ai-report-controller:nudge')->count();
        $reconcile = fn () => NudgeService::reconcile('ai-report-controller:nudge', AiReport::class, $report->id);

        app(ShareReportAction::class)->execute($report, $owner, [$recipient->id]);
        $this->assertSame(1, $bells());

        Livewire::actingAs($recipient)->test(Main::class)->call('open', $report->id);
        $reconcile();
        $this->assertSame(0, $bells());

        app(ShareReportAction::class)->execute($report->fresh(), $owner, []);
        app(ShareReportAction::class)->execute($report->fresh(), $owner, [$recipient->id]);
        $this->assertSame(1, $bells());

        app(ShareReportAction::class)->execute($report->fresh(), $owner, []);
        $this->assertSame(0, $bells());

        app(ShareReportAction::class)->execute($report->fresh(), $owner, [$recipient->id]);
        $report->fresh()->update(['active' => false]);
        $reconcile();
        $this->assertSame(0, $bells());

        $report->fresh()->update(['active' => true]);
        $reconcile();
        $this->assertSame(1, $bells());

        $report->fresh()->update(['status' => AiReport::STATUS_FAILED]);
        $reconcile();
        $this->assertSame(0, $bells());

        $report->fresh()->update(['status' => AiReport::STATUS_READY]);
        $reconcile();
        $this->assertSame(1, $bells());

        $report->fresh()->delete();
        $reconcile();
        $this->assertSame(0, $bells());
    }

    public function test_the_nudge_trigger_reconciles_only_for_watched_columns_and_deletes(): void
    {
        $owner = $this->user();
        $report = $this->report($owner, ['recipients' => [$this->user()->id]]);
        $resolve = (new AiReportNudge())->triggers()[0]['subject'];

        $unrelated = $report->fresh();
        $unrelated->title = 'عنوان دیگر';
        $unrelated->save();
        $this->assertNull($resolve($unrelated));

        $watched = $report->fresh();
        $watched->seen_by = [999];
        $watched->save();
        $this->assertSame((int) $report->id, (int) $resolve($watched)->id);

        $gone = $report->fresh();
        $gone->delete();
        $this->assertSame((int) $report->id, (int) $resolve($gone)->id);
    }

    public function test_sharing_a_report_sends_one_bell_notification_per_new_recipient_with_a_signed_view_link(): void
    {
        $owner = $this->user();
        $recipient = $this->user();
        $report = $this->report($owner);

        app(ShareReportAction::class)->execute($report, $owner, [$recipient->id]);

        $notification = $recipient->notifications()->latest()->first();
        $this->assertNotNull($notification);
        $this->assertSame('مشاهده', $notification->data['actions'][0]['label']);
        $this->assertStringContainsString('signature=', $notification->data['actions'][0]['url']);
        $this->assertCount(0, $owner->notifications()->get());

        app(ShareReportAction::class)->execute($report->fresh(), $owner, [$recipient->id]);
        $this->assertCount(1, $recipient->notifications()->get());
    }

    public function test_restore_brings_back_the_previous_version_after_a_failed_refine_for_the_owner_only(): void
    {
        $owner = $this->user();
        $previous = $this->plainSpec('Previous');
        $report = $this->report($owner, [
            'status' => AiReport::STATUS_FAILED,
            'fail_reason' => 'timeout',
            'spec' => $previous,
            'versions' => [
                ['prompt' => 'first', 'files' => [], 'spec' => $previous, 'at' => now()->toIso8601String()],
                ['prompt' => 'second that failed', 'files' => [], 'parent_spec' => $previous, 'at' => now()->toIso8601String()],
            ],
        ]);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])->assertSeeHtml('ai-report-restore');

        $intruder = $this->user();
        $this->assertFalse(AiReportAccessPolicy::canRestore($report, $intruder));
        $this->assertTrue(AiReportAccessPolicy::canRestore($report, $owner));

        Livewire::actingAs($owner)->test(Main::class, ['report' => $report])
            ->call('restore')
            ->assertDispatched('toast', $this->toastType('success'))
            ->assertDontSeeHtml('ai-report-restore');

        $fresh = $report->fresh();
        $this->assertSame(AiReport::STATUS_READY, $fresh->status);
        $this->assertNull($fresh->fail_reason);
        $this->assertEquals($previous, $fresh->spec);
        $this->assertCount(1, $fresh->versions);
        $this->assertSame('first', $fresh->versions[0]['prompt']);
    }

    public function test_restore_is_refused_without_a_previous_spec_or_for_a_ready_report(): void
    {
        $owner = $this->user();
        $firstRun = $this->report($owner, [
            'status' => AiReport::STATUS_FAILED,
            'fail_reason' => 'timeout',
            'spec' => null,
            'versions' => [['prompt' => 'never finished', 'files' => [], 'at' => now()->toIso8601String()]],
        ]);
        $ready = $this->report($owner);

        Livewire::actingAs($owner)->test(Main::class, ['report' => $firstRun])->assertDontSeeHtml('ai-report-restore');

        foreach ([$firstRun, $ready] as $row) {
            $before = $row->fresh()->only(['status', 'fail_reason']);
            Livewire::actingAs($owner)->test(Main::class, ['report' => $row])
                ->call('restore')
                ->assertDispatched('toast', $this->toastType('warning'));
            $this->assertSame($before, $row->fresh()->only(['status', 'fail_reason']));
        }
    }

    public function test_a_docx_detected_as_zip_passes_validation_but_a_plain_zip_does_not(): void
    {
        $owner = $this->user();
        $path = tempnam(sys_get_temp_dir(), 'arz');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('docProps/app.xml', '<Properties/>');
        $zip->addFromString('word/document.xml', '<w:p><w:t>hello</w:t></w:p>');
        $zip->close();
        $bytes = (string) file_get_contents($path);
        unlink($path);
        $this->assertSame('application/zip', (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes));

        $form = Livewire::actingAs($owner)->test(Main::class)->instance()->form;
        $form->prompt = 'a prompt that is long enough';

        foreach (['report.docx', 'sheet.xlsx'] as $name) {
            $form->files = [UploadedFile::fake()->createWithContent($name, $bytes)];
            $this->assertArrayHasKey('prompt', $form->validated());
        }

        $form->files = [UploadedFile::fake()->createWithContent('archive.zip', $bytes)];
        try {
            $form->validated();
            $this->fail('a plain zip passed validation');
        } catch (ValidationException $e) {
            $this->assertSame([ComposerForm::unsupportedFileMessage()], collect($e->errors())->flatten()->all());
        }
    }

    public function test_a_superseded_job_leaves_the_newer_run_untouched_and_the_current_run_proceeds(): void
    {
        $owner = $this->user();
        $report = $this->report($owner, [
            'status' => AiReport::STATUS_GENERATING,
            'saved_at' => null,
            'versions' => [['prompt' => 'a request that is long enough', 'files' => [], 'run' => 'new-run', 'at' => now()->toIso8601String()]],
        ]);
        Storage::disk('local')->put($report->tempDirectory() . '/1-note.txt', 'hello');
        Http::fake(['*' => Http::response([], 429)]);

        (new GenerateReportJob($report->id, 'old-run'))->handle(app(ReportAiService::class), app(FileExtractor::class));

        Http::assertNothingSent();
        $this->assertSame(AiReport::STATUS_GENERATING, $report->fresh()->status);
        Storage::disk('local')->assertExists($report->tempDirectory() . '/1-note.txt');

        (new GenerateReportJob($report->id, 'new-run'))->handle(app(ReportAiService::class), app(FileExtractor::class));

        $this->assertSame('http_429', $report->fresh()->fail_reason);
        $this->assertSame('1:y', (new GenerateReportJob(1, 'y'))->uniqueId());
        $this->assertSame('1', (new GenerateReportJob(1))->uniqueId());
    }

    public function test_a_job_queued_before_the_run_token_existed_still_reads_a_null_run(): void
    {
        $legacy = (new \ReflectionClass(GenerateReportJob::class))->newInstanceWithoutConstructor();

        $this->assertNull($legacy->run);
    }

    public function test_generate_stamps_the_run_token_on_the_version_and_the_dispatched_job(): void
    {
        Bus::fake();
        $owner = $this->user();

        Livewire::actingAs($owner)->test(Main::class)
            ->set('form.prompt', 'a prompt that is long enough')
            ->call('generate');

        $created = AiReport::query()->ownedBy($owner->id)->latest('id')->first();
        $run = $created->lastVersion()['run'] ?? null;
        $this->assertNotEmpty($run);
        Bus::assertDispatched(GenerateReportJob::class, fn (GenerateReportJob $job) => $job->reportId === $created->id && $job->run === $run);
    }

    public function test_share_bulk_selection_pool_matches_recipient_candidates_and_clears(): void
    {
        config(['ai.report.max_recipients' => 100000]);
        $owner = User::factory()->create(['status' => 'active']);

        $component = Livewire::actingAs($owner)->test(Main::class);
        $component->call('bulkSelectPeople', 'shareRecipients', 'all');
        $expected = array_map('intval', array_column($component->instance()->recipientCandidates, 'id'));
        $this->assertEqualsCanonicalizing($expected, array_map('intval', $component->get('shareRecipients')));

        $component->call('bulkSelectPeople', 'shareRecipients', 'none');
        $this->assertSame([], $component->get('shareRecipients'));

        $component->set('form.prompt', 'untouched')->call('bulkSelectPeople', 'form.prompt', 'all');
        $this->assertSame('untouched', $component->get('form.prompt'), 'a forged target property is a no-op');
        $this->assertSame([], $component->get('shareRecipients'));
    }

    public function test_share_bulk_selection_is_capped_at_the_report_recipient_limit(): void
    {
        config(['ai.report.max_recipients' => 2]);
        $owner = $this->user();
        $this->user();
        $this->user();
        $this->user();

        Livewire::actingAs($owner)->test(Main::class)
            ->call('bulkSelectPeople', 'shareRecipients', 'all')
            ->assertCount('shareRecipients', 2)
            ->assertDispatched('toast', $this->toastType('info'));
    }

    public function test_share_bulk_selection_department_kind_filters_the_pool(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $colleague = User::factory()->create(['status' => 'active']);
        Department::firstOrCreate(['code' => 'BLK'], ['name' => 'BLK', 'description' => 'BLK']);
        Profile::factory()->create(['user_id' => $colleague->id, 'department_id' => 'BLK']);

        $component = Livewire::actingAs($owner)->test(Main::class)->call('bulkSelectPeople', 'shareRecipients', 'department', 'BLK');

        $this->assertSame([$colleague->id], array_map('intval', $component->get('shareRecipients')));
    }

    public function test_share_bulk_selection_is_refused_while_a_report_is_read_only(): void
    {
        $owner = $this->user();
        $recipient = $this->user();
        $report = $this->report($owner, ['recipients' => [(int) $recipient->id]]);

        $component = Livewire::actingAs($recipient)->test(Main::class)->call('open', $report->id)->assertSet('readOnly', true);
        $component->call('bulkSelectPeople', 'shareRecipients', 'all');

        $this->assertSame([], $component->get('shareRecipients'));
    }
}
