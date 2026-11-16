<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Livewire\Dashboard\Launchpad\Forms\LaunchpadForm;
use App\Livewire\Dashboard\Launchpad\Main;
use App\Livewire\Dashboard\Launchpad\Presentation\LaunchpadPresenter;
use App\Models\Department;
use App\Models\Launchpad;
use App\Models\Profile;
use App\Models\User;
use App\Services\Menu\Indicators\UnseenLaunchpads;
use App\Support\LaunchpadAccessPolicy;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Morilog\Jalali\Jalalian;
use Tests\TestCase;

class LaunchpadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
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

    private function dept(string $code): string
    {
        Department::firstOrCreate(['code' => $code], ['name' => $code, 'description' => $code]);

        return $code;
    }

    private function deptUser(string $dept, array $overrides = []): User
    {
        $user = User::factory()->create(array_replace(['role' => 'user', 'status' => 'active'], $overrides));
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => $dept]);

        return $user;
    }

    private function fileRow(string $dept, User $owner, array $overrides = []): Launchpad
    {
        $row = Launchpad::create(array_replace([
            'user_id' => $owner->id,
            'department_id' => $dept,
            'kind' => 'static',
            'active' => true,
            'title' => 'LP-' . Str::random(8),
            'folder' => (string) Str::ulid(),
        ], $overrides));
        Storage::disk('public')->put('launchpad/files/' . $row->folder . '/' . Launchpad::storedName('index.html'), '<html>ok</html>');
        Storage::disk('public')->put('launchpad/files/' . $row->folder . '/' . Launchpad::storedName('style.css'), 'body{color:red}');

        return $row;
    }

    private function validFiles(): array
    {
        return [
            UploadedFile::fake()->createWithContent('index.html', '<html><body>ok</body></html>'),
            UploadedFile::fake()->createWithContent('style.css', 'body{color:red}'),
            UploadedFile::fake()->createWithContent('app.js', 'console.log(1)'),
        ];
    }

    private function entryFile(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('index.html', '<html>ok</html>');
    }

    private function saveLaunch(User $user, string $title, array $audience = [], array $files = null)
    {
        Livewire::withoutLazyLoading();

        return Livewire::actingAs($user)->test(Main::class)
            ->call('switchTab', 'create')
            ->set('form.title', $title)
            ->set('form.audience', $audience)
            ->set('form.files', $files ?? $this->validFiles())
            ->call('save');
    }

    private function fakeSiblings(): string
    {
        config(['app.siblings' => [
            ['name' => 'lps', 'title' => 'LP Sibling', 'url' => 'https://lp-sibling.example.com/'],
        ]]);

        return 'lps';
    }

    private function linkRow(string $dept, User $owner, array $overrides = []): Launchpad
    {
        return Launchpad::create(array_replace([
            'user_id' => $owner->id,
            'department_id' => $dept,
            'kind' => 'link',
            'active' => true,
            'title' => 'LP-' . Str::random(8),
            'audience' => [],
            'settings' => ['site' => $this->fakeSiblings(), 'path' => 'reports/q1'],
        ], $overrides));
    }

    private function saveLink(User $user, string $title, string $site = 'lps', string $path = '', array $audience = [])
    {
        Livewire::withoutLazyLoading();

        return Livewire::actingAs($user)->test(Main::class)
            ->call('switchTab', 'create')
            ->set('form.title', $title)
            ->set('form.kind', 'link')
            ->set('form.site', $site)
            ->set('form.path', $path)
            ->set('form.audience', $audience)
            ->call('save');
    }

    private function body($response): string
    {
        return (string) file_get_contents($response->baseResponse->getFile()->getPathname());
    }

    private function assertEmpty404($response): void
    {
        $this->assertSame(404, $response->status());
        $this->assertSame('', $response->getContent());
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_access_matrix_agrees_across_policy_scope_and_file_route(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $admin = $this->deptUser($this->dept('LPB'), ['role' => 'admin']);
        $viewer = $this->deptUser($dept);
        $stranger = $this->deptUser($dept);

        $active = $this->fileRow($dept, $owner, ['title' => 'LP-active', 'audience' => [$viewer->id]]);
        $inactive = $this->fileRow($dept, $owner, ['title' => 'LP-inactive', 'active' => false, 'audience' => [$viewer->id]]);
        $scheduled = $this->fileRow($dept, $owner, ['title' => 'LP-scheduled', 'accessible_from' => today()->addDay()->toDateString(), 'audience' => [$viewer->id]]);
        $ended = $this->fileRow($dept, $owner, ['title' => 'LP-ended', 'accessible_to' => today()->subDay()->toDateString(), 'audience' => [$viewer->id]]);

        $expired = $this->fileRow($dept, $owner, ['title' => 'LP-expired', 'expire_at' => today()->subDay()->toDateString(), 'audience' => [$viewer->id]]);

        $cases = [
            [$owner, $expired, true],
            [$admin, $expired, true],
            [$viewer, $expired, false],
            [$owner, $active, true],
            [$owner, $inactive, true],
            [$owner, $scheduled, true],
            [$owner, $ended, true],
            [$admin, $active, true],
            [$admin, $inactive, true],
            [$admin, $scheduled, true],
            [$admin, $ended, true],
            [$viewer, $active, true],
            [$viewer, $inactive, false],
            [$viewer, $scheduled, false],
            [$viewer, $ended, false],
            [$stranger, $active, false],
        ];
        foreach ($cases as [$actor, $row, $allowed]) {
            $this->assertSame($allowed, LaunchpadAccessPolicy::canView($row, $actor), $row->title . ' policy');
            $this->assertSame($allowed ? 200 : 404, $this->get(route('launchpad.file', [$row->id, LaunchpadAccessPolicy::token($row, $actor), 'index.html']))->getStatusCode(), $row->title . ' route');
            $this->assertSame($allowed, Launchpad::query()->visibleTo($actor)->pluck('id')->contains($row->id), $row->title . ' scope');
        }

        $this->assertFalse(LaunchpadAccessPolicy::canView($active, null));

        $deactivated = $this->deptUser($dept, ['status' => 'inactive']);
        $theirRow = $this->fileRow($dept, $owner, ['title' => 'LP-offviewer', 'audience' => [$deactivated->id]]);
        $this->assertFalse(LaunchpadAccessPolicy::canView($theirRow, $deactivated));
        $this->assertSame(404, $this->get(route('launchpad.file', [$theirRow->id, LaunchpadAccessPolicy::token($theirRow, $deactivated), 'index.html']))->getStatusCode());

        $active->delete();
        $this->assertFalse(LaunchpadAccessPolicy::canView($active, $owner));
        $this->assertEmpty404($this->get(route('launchpad.file', [$active->id, LaunchpadAccessPolicy::token($active, $owner), 'index.html'])));
    }

    public function test_upload_matrix_rejects_bad_names_types_and_content(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);

        $cases = [
            'double-dot' => [UploadedFile::fake()->createWithContent('a..html', 'x'), 'form.files', 'نام نامعتبر'],
            'htaccess' => [UploadedFile::fake()->createWithContent('.htaccess', 'x'), 'form.files', 'فایل پنهان'],
            'user-ini' => [UploadedFile::fake()->createWithContent('.user.ini', 'x'), 'form.files', 'فایل پنهان'],
            'php' => [UploadedFile::fake()->createWithContent('x.php', 'x'), 'form.files', 'فایل اجرایی مجاز نیست'],
            'phtml' => [UploadedFile::fake()->createWithContent('x.phtml', 'x'), 'form.files', 'فایل اجرایی مجاز نیست'],
            'phar' => [UploadedFile::fake()->createWithContent('x.phar', 'x'), 'form.files', 'فایل اجرایی مجاز نیست'],
            'php-png' => [UploadedFile::fake()->createWithContent('x.php.png', 'x'), 'form.files', 'فایل اجرایی مجاز نیست'],
            'markdown' => [UploadedFile::fake()->createWithContent('notes.md', 'x'), 'form.files', 'پسوند مجاز نیست'],
            'long-name' => [UploadedFile::fake()->createWithContent(str_repeat('a', 116) . '.html', 'x'), 'form.files', 'نام نامعتبر'],
            'php-content' => [UploadedFile::fake()->createWithContent('page.html', '<?php echo 1; ?>'), 'form.files', 'محتوای غیرمجاز'],
        ];
        foreach ($cases as $key => [$badFile, $errorKey, $reason]) {
            $title = 'LP-reject-' . $key;
            $this->saveLaunch($owner, $title, [], [$this->entryFile(), $badFile])
                ->assertSet('precheck.rejected.0.reason', $reason)
                ->assertHasErrors($errorKey);
            $this->assertDatabaseMissing('launchpads', ['title' => $title]);
            $this->assertSame([], Storage::disk('public')->directories('launchpad/files'), $key);
        }

        // duplicate pair: same lowercased name twice in one selection
        $this->saveLaunch($owner, 'LP-reject-duplicate', [], [$this->entryFile(), UploadedFile::fake()->createWithContent('a.html', 'x'), UploadedFile::fake()->createWithContent('A.HTML', 'x')])
            ->assertSet('precheck.rejected.0', ['name' => 'A.HTML', 'reason' => 'نام تکراری'])
            ->assertHasErrors('form.files');
        $this->assertDatabaseMissing('launchpads', ['title' => 'LP-reject-duplicate']);

        $this->saveLaunch($owner, 'LP-reject-unicode-duplicate', [], [$this->entryFile(), UploadedFile::fake()->createWithContent('É.png', 'x'), UploadedFile::fake()->createWithContent('é.png', 'x')])
            ->assertSet('precheck.rejected.0', ['name' => 'é.png', 'reason' => 'نام تکراری'])
            ->assertHasErrors('form.files');
        $this->assertDatabaseMissing('launchpads', ['title' => 'LP-reject-unicode-duplicate']);

        $accepted = $this->saveLaunch($owner, 'LP-accept-dotted-names', [], [
            $this->entryFile(),
            UploadedFile::fake()->createWithContent('app.shared.js', 'x'),
            UploadedFile::fake()->createWithContent('style.sheet.css', 'x'),
            UploadedFile::fake()->createWithContent('x.aspect.css', 'x'),
            UploadedFile::fake()->createWithContent('bundle.jsplumb.js', 'x'),
        ]);
        $accepted->assertHasNoErrors();
        $row = Launchpad::query()->where('title', 'LP-accept-dotted-names')->firstOrFail();
        $this->assertSame(5, $row->settings['files_count']);
        $this->assertCount(5, Storage::disk('public')->files('launchpad/files/' . $row->folder));
        $row->delete();

        // set-level: no entry at all
        $this->saveLaunch($owner, 'LP-reject-no-entry', [], [UploadedFile::fake()->createWithContent('a.html', 'x'), UploadedFile::fake()->createWithContent('b.css', 'x')])
            ->assertSet('precheck.errors', ['no_entry'])
            ->assertHasErrors('form.files');
        // the entry match is case-sensitive: Index.html alone is no entry
        $this->saveLaunch($owner, 'LP-reject-upper-entry', [], [UploadedFile::fake()->createWithContent('Index.html', 'x')])
            ->assertSet('precheck.errors', ['no_entry'])
            ->assertHasErrors('form.files');

        // empty selection never reaches the precheck: the required rule fires first
        Livewire::withoutLazyLoading();
        Livewire::actingAs($owner)->test(Main::class)->call('switchTab', 'create')->set('form.title', 'LP-reject-empty')->call('save')
            ->assertHasErrors('form.files');
        $this->assertDatabaseMissing('launchpads', ['title' => 'LP-reject-empty']);

        $originals = ['launchpad.max_files' => config('launchpad.max_files'), 'file_upload_limits.launchpad' => config('file_upload_limits.launchpad'), 'file_upload_limits.launchpad_total' => config('file_upload_limits.launchpad_total')];
        try {
            config(['launchpad.max_files' => 3]);
            $this->saveLaunch($owner, 'LP-reject-too-many', [], [$this->entryFile(),
                UploadedFile::fake()->createWithContent('a.css', 'x'),
                UploadedFile::fake()->createWithContent('b.js', 'x'),
                UploadedFile::fake()->createWithContent('c.json', 'x'),
            ])->assertSet('precheck.errors', ['too_many'])->assertHasErrors('form.files');
            $this->assertDatabaseMissing('launchpads', ['title' => 'LP-reject-too-many']);

            config(['file_upload_limits.launchpad' => 1]);
            $this->saveLaunch($owner, 'LP-reject-per-file', [], [UploadedFile::fake()->createWithContent('index.html', str_repeat('a', 2048))])
                ->assertHasErrors('form.files.0');
            $this->assertDatabaseMissing('launchpads', ['title' => 'LP-reject-per-file']);

            config(['file_upload_limits.launchpad_total' => 1]);
            $this->saveLaunch($owner, 'LP-reject-total', [], [
                UploadedFile::fake()->createWithContent('index.html', str_repeat('a', 700)),
                UploadedFile::fake()->createWithContent('style.css', str_repeat('b', 700)),
            ])->assertSet('precheck.errors', ['too_big'])->assertHasErrors('form.files');
            $this->assertDatabaseMissing('launchpads', ['title' => 'LP-reject-total']);
        } finally {
            config($originals);
        }
        $this->assertSame([], Storage::disk('public')->directories('launchpad/files'));
    }

    public function test_valid_files_are_stored_under_hashed_names(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);

        $contents = [
            'index.html' => '<html><body>ok</body></html>',
            'style.css' => 'body{color:red}',
            'app.js' => 'console.log(1)',
            'data.json' => '{"a":1}',
            'logo.svg' => '<svg xmlns="http://www.w3.org/2000/svg"></svg>',
            'font.woff2' => 'woff2-bytes',
            'صفحهٔ من.html' => '<b>فارسی</b>',
        ];
        $files = [];
        foreach ($contents as $name => $content) {
            $files[] = UploadedFile::fake()->createWithContent($name, $content);
        }

        $this->saveLaunch($owner, 'LP-valid-files', [], $files)->assertHasNoErrors();
        $row = Launchpad::query()->where('title', 'LP-valid-files')->firstOrFail();

        $this->assertMatchesRegularExpression('/^[0-9A-Za-z]{26}$/', (string) $row->folder);
        $this->assertSame(count($contents), $row->settings['files_count']);
        $this->assertSame(array_sum(array_map('strlen', $contents)), $row->settings['size']);

        $stored = [];
        foreach (array_keys($contents) as $name) {
            $storedName = Launchpad::storedName($name);
            $this->assertSame(sha1(mb_strtolower($name)) . '.' . strtolower(pathinfo($name, PATHINFO_EXTENSION)), $storedName);
            $this->assertTrue(Storage::disk('public')->exists('launchpad/files/' . $row->folder . '/' . $storedName), $name);
            $stored[] = $storedName;
        }
        $onDisk = array_map('basename', Storage::disk('public')->files('launchpad/files/' . $row->folder));
        sort($stored);
        sort($onDisk);
        $this->assertSame($stored, $onDisk);
    }

    public function test_cap_blocks_create_but_not_replace(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);

        $original = config('launchpad.max_per_department');
        try {
            config(['launchpad.max_per_department' => 2]);
            $kept = $this->fileRow($dept, $owner, ['title' => 'LP-cap-a']);
            $this->fileRow($dept, $owner, ['title' => 'LP-cap-b', 'active' => false]);

            $this->saveLaunch($owner, 'LP-cap-new')->assertHasErrors('form.files');
            $this->assertDatabaseMissing('launchpads', ['title' => 'LP-cap-new']);

            Launchpad::query()->where('title', 'LP-cap-b')->firstOrFail()->delete();
            $this->saveLaunch($owner, 'LP-cap-new')->assertHasNoErrors();
            $this->assertDatabaseHas('launchpads', ['title' => 'LP-cap-new']);

            $component = $this->saveLaunch($owner, 'LP-cap-a', [], [
                UploadedFile::fake()->createWithContent('index.html', '<html>replaced</html>'),
                UploadedFile::fake()->createWithContent('other.css', 'a{}'),
            ]);
            $component->assertSet('replaceOpen', true)->call('confirmReplace')->assertHasNoErrors();

            $this->assertSame($kept->id, Launchpad::query()->where('title', 'LP-cap-a')->firstOrFail()->id);
            $this->assertSame(2, Launchpad::query()->where('department_id', $dept)->count());
        } finally {
            config(['launchpad.max_per_department' => $original]);
        }
    }

    public function test_same_title_by_another_owner_is_refused(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $rowOwner = $this->deptUser($dept);
        $actor = $this->deptUser($dept);
        $otherRow = $this->fileRow($dept, $rowOwner, ['title' => 'LP-title-clash']);
        $folder = $otherRow->folder;

        $this->saveLaunch($actor, 'LP-title-clash')->assertHasErrors('form.title');
        $this->assertSame($folder, Launchpad::query()->whereKey($otherRow->id)->value('folder'));
        $this->assertSame(1, Launchpad::query()->where('title', 'LP-title-clash')->count());
    }

    public function test_owner_replace_swaps_the_folder_and_keeps_id_audience_seen_by(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);

        $this->saveLaunch($owner, 'LP-replace', [$viewer->id])->assertHasNoErrors();
        $row = Launchpad::query()->where('title', 'LP-replace')->firstOrFail();
        $oldFolder = $row->folder;

        Livewire::withoutLazyLoading();
        Livewire::actingAs($viewer)->test(Main::class)->call('open', $row->id);
        $this->assertContains($viewer->id, Launchpad::query()->whereKey($row->id)->firstOrFail()->seen_by);

        $component = $this->saveLaunch($owner, 'LP-replace', [], [
            UploadedFile::fake()->createWithContent('index.html', '<html>two</html>'),
            UploadedFile::fake()->createWithContent('second.css', 'b{}'),
        ]);
        $component->assertSet('replaceOpen', true)->call('confirmReplace')->assertHasNoErrors();

        $fresh = Launchpad::query()->whereKey($row->id)->firstOrFail();
        $this->assertSame($row->id, $fresh->id);
        $this->assertSame([$viewer->id], $fresh->audience);
        $this->assertContains($viewer->id, $fresh->seen_by);
        $this->assertNotSame($oldFolder, $fresh->folder);
        $this->assertSame([], Storage::disk('public')->files('launchpad/files/' . $oldFolder));
        $this->assertTrue(Storage::disk('public')->exists('launchpad/files/' . $fresh->folder . '/' . Launchpad::storedName('index.html')));
        $this->assertTrue(Storage::disk('public')->exists('launchpad/files/' . $fresh->folder . '/' . Launchpad::storedName('second.css')));
    }

    public function test_title_is_unique_among_live_rows_only(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);

        $this->saveLaunch($owner, 'LP-title-cycle')->assertHasNoErrors();
        $first = Launchpad::query()->where('title', 'LP-title-cycle')->firstOrFail();
        $this->assertNotSame([], Storage::disk('public')->files('launchpad/files/' . $first->folder));
        $first->delete();
        $this->assertSame([], Storage::disk('public')->files('launchpad/files/' . $first->folder));
        $this->assertSame([], Storage::disk('public')->directories('launchpad/files/' . $first->folder));

        $this->saveLaunch($owner, 'LP-title-cycle')->assertHasNoErrors();
        $second = Launchpad::query()->where('title', 'LP-title-cycle')->firstOrFail();
        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(1, Launchpad::query()->where('title', 'LP-title-cycle')->count());

        $otherDept = $this->dept('LPB');
        $otherOwner = $this->deptUser($otherDept);
        $this->saveLaunch($otherOwner, 'LP-title-cycle')->assertHasNoErrors();
        $this->assertSame(2, Launchpad::query()->where('title', 'LP-title-cycle')->count());
        $this->assertSame([$dept, $otherDept], Launchpad::query()->where('title', 'LP-title-cycle')->orderBy('id')->pluck('department_id')->all());
    }

    public function test_file_response_carries_sandbox_csp_and_no_cookie(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $row = $this->fileRow($dept, $owner, ['title' => 'LP-serve', 'audience' => [$viewer->id]]);
        $token = LaunchpadAccessPolicy::token($row, $viewer);

        $response = $this->get(route('launchpad.file', [$row->id, $token, 'index.html']));
        $response->assertOk();
        $this->assertSame(config('launchpad.types.html'), $response->headers->get('Content-Type'));
        $this->assertSame('ok', strip_tags($this->body($response)));
        $csp = (string) $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString('sandbox allow-scripts', $csp);
        $this->assertStringNotContainsString("'self'", $csp);
        $this->assertStringContainsString(rtrim(url('/'), '/') . '/launchpad/' . $row->id . '/' . $token . '/', $csp);
        $this->assertStringContainsString('form-action \'none\'', $csp);
        $response->assertHeaderMissing('Set-Cookie');
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('no-referrer', $response->headers->get('Referrer-Policy'));
        $cache = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('private', $cache);
        $this->assertStringContainsString('no-cache', $cache);
        $this->assertStringNotContainsString('public', $cache);
        $this->assertSame('*', $response->headers->get('Access-Control-Allow-Origin'));

        $etag = (string) $response->headers->get('ETag');
        $this->assertStringNotContainsString($row->folder, $etag);
        $this->get(route('launchpad.file', [$row->id, $token, 'index.html']), ['If-None-Match' => $etag])->assertStatus(304);

        $css = $this->get(route('launchpad.file', [$row->id, $token, 'style.css']));
        $css->assertOk();
        $this->assertSame(config('launchpad.types.css'), $css->headers->get('Content-Type'));

        // storedName() hashes the lowercased name, so the same file answers to any letter case
        $upper = $this->get(route('launchpad.file', [$row->id, $token, 'INDEX.HTML']));
        $upper->assertOk();
        $this->assertSame($this->body($response), $this->body($upper));
    }

    public function test_every_file_failure_is_the_same_empty_404(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $row = $this->fileRow($dept, $owner, ['title' => 'LP-404', 'audience' => [$viewer->id]]);
        $other = $this->fileRow($dept, $owner, ['title' => 'LP-404-other', 'audience' => [$viewer->id]]);
        $token = LaunchpadAccessPolicy::token($row, $viewer);

        $this->assertEmpty404($this->get(route('launchpad.file', [$row->id, $token, 'index.html']), ['Sec-Fetch-Dest' => 'document']));

        $tampered = substr($token, 0, -1) . (str_ends_with($token, 'A') ? 'B' : 'A');
        $this->assertEmpty404($this->get(route('launchpad.file', [$row->id, $tampered, 'index.html'])));

        $this->travel(2 * (int) config('launchpad.token_ttl_minutes') + 1)->minutes();
        $this->assertEmpty404($this->get(route('launchpad.file', [$row->id, $token, 'index.html'])));
        $this->travelBack();

        $this->assertEmpty404($this->get(route('launchpad.file', [$row->id, LaunchpadAccessPolicy::token($other, $viewer), 'index.html'])));
        $this->assertEmpty404($this->get(route('launchpad.file', [$row->id, $token, 'missing.css'])));
        $this->assertEmpty404($this->get(route('launchpad.file', [$row->id, $token, '..'])));

        $link = $this->fileRow($dept, $owner, ['title' => 'LP-404-link', 'kind' => 'link', 'audience' => [$viewer->id]]);
        $this->assertEmpty404($this->get(route('launchpad.file', [$link->id, LaunchpadAccessPolicy::token($link, $viewer), 'index.html'])));

        $other->delete();
        $this->assertEmpty404($this->get(route('launchpad.file', [$other->id, LaunchpadAccessPolicy::token($other, $viewer), 'index.html'])));
    }

    public function test_token_is_stable_inside_a_window_and_ends_within_two_windows(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $row = $this->fileRow($dept, $owner, ['title' => 'LP-token-window', 'audience' => [$viewer->id]]);
        $window = (int) config('launchpad.token_ttl_minutes') * 60;

        $this->travelTo(Carbon::createFromTimestamp(intdiv(now()->timestamp, $window) * $window + 100));
        $first = LaunchpadAccessPolicy::token($row, $viewer);
        $this->travel(5)->seconds();
        $this->assertSame($first, LaunchpadAccessPolicy::token($row, $viewer));
        $this->assertSame($viewer->id, LaunchpadAccessPolicy::tokenUser($row->id, $first));

        $this->travelTo(Carbon::createFromTimestamp(intdiv(now()->timestamp, $window) * $window + $window - 1));
        $this->assertSame($viewer->id, LaunchpadAccessPolicy::tokenUser($row->id, $first));

        $this->travelTo(Carbon::createFromTimestamp((intdiv(now()->timestamp, $window) + 1) * $window + $window + 1));
        $this->assertNull(LaunchpadAccessPolicy::tokenUser($row->id, $first));
        $this->travelBack();
    }

    public function test_date_rules_reject_an_ended_window_and_an_expiry_before_the_start(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        Livewire::withoutLazyLoading();

        Livewire::actingAs($owner)->test(Main::class)
            ->set('form.title', 'LP-date-ended')
            ->set('form.files', $this->validFiles())
            ->set('toYear', '1403')->set('toMonth', '1')->set('toDay', '1')
            ->call('save')
            ->assertHasErrors('form.accessible_to');
        $this->assertDatabaseMissing('launchpads', ['title' => 'LP-date-ended']);

        Livewire::actingAs($owner)->test(Main::class)
            ->set('form.title', 'LP-date-expiry')
            ->set('form.files', $this->validFiles())
            ->set('fromYear', '1405')->set('fromMonth', '12')->set('fromDay', '1')
            ->set('expiryYear', '1405')->set('expiryMonth', '9')->set('expiryDay', '1')
            ->call('save')
            ->assertHasErrors('form.expire_at');
        $this->assertDatabaseMissing('launchpads', ['title' => 'LP-date-expiry']);
    }

    public function test_title_is_normalised_before_validation(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);

        $this->saveLaunch($owner, 'a  ')->assertHasErrors('form.title');
        $this->assertDatabaseMissing('launchpads', ['title' => 'a']);

        $this->saveLaunch($owner, '  LP   two   spaces ')->assertHasNoErrors();
        $this->assertDatabaseHas('launchpads', ['title' => 'LP two spaces']);
    }

    public function test_delete_by_a_non_manager_is_refused_with_a_toast_and_keeps_the_row(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $row = $this->fileRow($dept, $owner, ['title' => 'LP-delete-refused', 'audience' => [$viewer->id]]);
        Livewire::withoutLazyLoading();

        Livewire::actingAs($viewer)->test(Main::class)->call('delete', $row->id)->assertDispatched('toast');

        $this->assertNotNull(Launchpad::query()->find($row->id));
        $this->assertNotSame([], Storage::disk('public')->files('launchpad/files/' . $row->folder));
    }

    public function test_create_in_a_foreign_department_is_refused_for_a_regular_user(): void
    {
        Storage::fake('public');
        $own = $this->dept('LPA');
        $foreign = $this->dept('LPB');
        $user = $this->deptUser($own);
        $admin = $this->deptUser($own, ['role' => 'admin']);
        Livewire::withoutLazyLoading();

        Livewire::actingAs($user)->test(Main::class)
            ->set('form.department_id', $foreign)
            ->set('form.title', 'LP-foreign-dept')
            ->set('form.files', $this->validFiles())
            ->call('save')
            ->assertDispatched('toast');
        $this->assertDatabaseMissing('launchpads', ['title' => 'LP-foreign-dept']);
        $this->assertSame([], Storage::disk('public')->directories('launchpad/files'));

        Livewire::actingAs($admin)->test(Main::class)
            ->set('form.department_id', $foreign)
            ->set('form.title', 'LP-foreign-dept')
            ->set('form.files', $this->validFiles())
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame($foreign, Launchpad::query()->where('title', 'LP-foreign-dept')->value('department_id'));
    }

    public function test_embed_open_of_an_unavailable_app_shows_the_message_not_a_toast(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $stranger = $this->deptUser($dept);
        $row = $this->fileRow($dept, $owner, ['title' => 'LP-embed-hidden']);
        Livewire::withoutLazyLoading();

        Livewire::withQueryParams(['embed' => 1, 'a' => $row->id])->actingAs($stranger)->test(Main::class)
            ->assertSet('unavailable', 'این برنامه در دسترس نیست.')
            ->assertSet('openId', null)
            ->assertNotDispatched('toast')
            ->assertSee('برنامه در دسترس نیست');
    }

    public function test_replace_by_an_elevated_non_owner_keeps_id_and_audience(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $admin = $this->deptUser($dept, ['role' => 'admin']);

        $this->saveLaunch($owner, 'LP-admin-replace', [$viewer->id])->assertHasNoErrors();
        $row = Launchpad::query()->where('title', 'LP-admin-replace')->firstOrFail();
        $oldFolder = $row->folder;

        $this->saveLaunch($admin, 'LP-admin-replace', [], [$this->entryFile(), UploadedFile::fake()->createWithContent('extra.css', 'a{}')])
            ->assertSet('replaceOpen', true)->call('confirmReplace')->assertHasNoErrors();

        $fresh = Launchpad::query()->whereKey($row->id)->firstOrFail();
        $this->assertSame($owner->id, (int) $fresh->user_id);
        $this->assertSame([$viewer->id], $fresh->audience);
        $this->assertNotSame($oldFolder, $fresh->folder);
        $this->assertSame([], Storage::disk('public')->files('launchpad/files/' . $oldFolder));
        $this->assertSame(1, Launchpad::query()->where('title', 'LP-admin-replace')->count());
    }

    public function test_access_is_rechecked_on_every_file_request(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $row = $this->fileRow($dept, $owner, ['title' => 'LP-recheck', 'audience' => [$viewer->id]]);
        $url = route('launchpad.file', [$row->id, LaunchpadAccessPolicy::token($row, $viewer), 'index.html']);

        $this->get($url)->assertOk();

        $row->update(['audience' => []]);
        $this->get($url)->assertStatus(404);

        $row->update(['audience' => [$viewer->id]]);
        $this->get($url)->assertOk();

        $row->update(['active' => false]);
        $this->get($url)->assertStatus(404);

        $row->update(['active' => true]);
        $this->get($url)->assertOk();

        $viewer->update(['status' => 'inactive']);
        $this->get($url)->assertStatus(404);
    }

    public function test_badge_lights_and_clears_only_for_unopened_shared_rows(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $other = $this->deptUser($dept);
        $badge = app(UnseenLaunchpads::class);

        $this->actingAs($viewer);
        $this->assertFalse($badge->isActive());

        Cache::forever('menu_state:version', 'lp-sentinel');
        $this->saveLaunch($owner, 'LP-badge-shared', [$viewer->id, $other->id])->assertHasNoErrors();
        $this->assertNotSame('lp-sentinel', (string) Cache::get('menu_state:version'));

        $this->actingAs($viewer);
        $this->assertTrue($badge->isActive());
        $this->actingAs($owner);
        $this->assertFalse($badge->isActive());
        $this->actingAs($other);
        $this->assertTrue($badge->isActive());

        Cache::forever('menu_state:version', 'lp-sentinel-2');
        $this->saveLaunch($owner, 'LP-badge-solo')->assertHasNoErrors();
        $this->assertSame('lp-sentinel-2', (string) Cache::get('menu_state:version'));

        $row = Launchpad::query()->where('title', 'LP-badge-shared')->firstOrFail();
        $this->actingAs($viewer);
        Livewire::withoutLazyLoading();
        Livewire::actingAs($viewer)->test(Main::class)->call('open', $row->id);
        $this->assertFalse($badge->isActive());
        $this->actingAs($other);
        $this->assertTrue($badge->isActive());

        $row->update(['active' => false]);
        $this->assertFalse($badge->isActive());
        $row->update(['active' => true]);
        $this->assertTrue($badge->isActive());
    }

    public function test_workspace_button_is_disabled_with_a_reason(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $stranger = $this->deptUser($dept);
        $presenter = app(LaunchpadPresenter::class);

        $live = $this->fileRow($dept, $owner, ['title' => 'LP-workspace-live']);
        $inactive = $this->fileRow($dept, $owner, ['title' => 'LP-workspace-off', 'active' => false]);
        $scheduled = $this->fileRow($dept, $owner, ['title' => 'LP-workspace-later', 'accessible_from' => today()->addDay()->toDateString()]);
        $ended = $this->fileRow($dept, $owner, ['title' => 'LP-workspace-ended', 'accessible_to' => today()->subDay()->toDateString()]);

        $liveRow = $presenter->listRow($live, $owner);
        $this->assertNull($liveRow['block']);
        $this->assertSame('افزودن به فضای کاری', $liveRow['workspace_title']);
        $this->assertSame('inactive', $presenter->listRow($inactive, $owner)['block']);
        $this->assertSame('scheduled', $presenter->listRow($scheduled, $owner)['block']);
        $this->assertSame('expired', $presenter->listRow($ended, $owner)['block']);
        $this->assertSame('denied', $presenter->listRow($inactive, $stranger)['block']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($owner)->test(Main::class)
            ->assertSee('LP-workspace-live')
            ->assertSee('برنامه غیرفعال است')
            ->assertSee('برنامه منقضی شده است');
    }

    public function test_tenant_off_hides_page_file_route_admin_slug_and_badge(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $row = $this->fileRow($dept, $owner, ['title' => 'LP-tenant-off', 'audience' => [$viewer->id]]);
        $url = route('launchpad.file', [$row->id, LaunchpadAccessPolicy::token($row, $viewer), 'index.html']);

        $this->get($url)->assertOk();
        $this->actingAs($viewer)->get(route('launchpad'))->assertOk();

        config(['app.off' => ['launchpad']]);

        $this->get($url)->assertNotFound();
        $this->actingAs($viewer)->get(route('launchpad'))->assertNotFound();

        foreach (['launchpad', 'launchpad.file', 'launchpad.go', 'dashboard.launchpad.main', 'filament.admin.resources.launchpads.index', 'launchpad-controller'] as $name) {
            $this->assertTrue(tenantOffName($name), $name);
        }
    }

    public function test_prune_deletes_expired_rows_and_their_folders(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);

        $this->saveLaunch($owner, 'LP-prune-expired')->assertHasNoErrors();
        $this->saveLaunch($owner, 'LP-prune-kept')->assertHasNoErrors();
        $expiring = Launchpad::query()->where('title', 'LP-prune-expired')->firstOrFail();
        $kept = Launchpad::query()->where('title', 'LP-prune-kept')->firstOrFail();
        $folder = (string) $expiring->folder;
        $this->assertTrue(Storage::disk('public')->exists('launchpad/files/' . $folder . '/' . Launchpad::storedName('index.html')));

        $expiring->update(['expire_at' => today()->subDay()]);

        $total = (new Launchpad())->pruneAll();
        $this->assertGreaterThanOrEqual(1, $total);
        $this->assertNull(Launchpad::withTrashed()->find($expiring->id));
        $this->assertNotNull(Launchpad::query()->find($kept->id));
        $this->assertSame([], Storage::disk('public')->files('launchpad/files/' . $folder));
        $this->assertSame([], Storage::disk('public')->directories('launchpad/files/' . $folder));
    }

    public function test_list_filters_and_search_narrow_the_visible_rows(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $otherDept = $this->dept('LPB');
        $viewer = $this->deptUser($dept);
        $stranger = $this->deptUser($otherDept);
        $this->fileRow($dept, $viewer, ['title' => 'LP-alpha']);
        $this->fileRow($dept, $viewer, ['title' => 'LP-beta']);
        $this->fileRow($otherDept, $stranger, ['title' => 'LP-gamma', 'audience' => [$viewer->id]]);
        $this->fileRow($otherDept, $stranger, ['title' => 'LP-omega']);
        $this->fileRow($dept, $this->deptUser($dept), ['title' => 'LP-delta', 'audience' => [$viewer->id]]);

        Livewire::actingAs($viewer)->test(Main::class)
            ->assertSee('LP-alpha')->assertSee('LP-beta')->assertSee('LP-gamma')->assertSee('LP-delta')->assertDontSee('LP-omega');

        Livewire::actingAs($viewer)->test(Main::class)->call('setFilter', 'mine')
            ->assertSee('LP-alpha')->assertSee('LP-beta')->assertDontSee('LP-gamma')->assertDontSee('LP-delta');

        Livewire::actingAs($viewer)->test(Main::class)->call('setFilter', 'shared')
            ->assertSee('LP-gamma')->assertSee('LP-delta')->assertDontSee('LP-alpha')->assertDontSee('LP-omega');

        Livewire::actingAs($viewer)->test(Main::class)->call('setFilter', 'department')
            ->assertSee('LP-alpha')->assertSee('LP-beta')->assertSee('LP-delta')->assertDontSee('LP-gamma');

        Livewire::actingAs($viewer)->test(Main::class)->set('search', 'amm')
            ->assertSee('LP-gamma')->assertDontSee('LP-alpha')->assertDontSee('LP-omega');
    }

    public function test_list_sort_orders_by_title_toggles_direction_ignores_unknown_columns_and_resets(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPS');
        $viewer = $this->deptUser($dept);
        $this->fileRow($dept, $viewer, ['title' => 'LP-b']);
        $this->fileRow($dept, $viewer, ['title' => 'LP-c']);
        $this->fileRow($dept, $viewer, ['title' => 'LP-a']);

        $titles = fn ($component) => $component->instance()->rows->pluck('title')->all();

        $component = Livewire::actingAs($viewer)->test(Main::class);
        $this->assertSame(['LP-a', 'LP-c', 'LP-b'], $titles($component));

        $component->call('sortBy', 'title');
        $this->assertSame(['LP-a', 'LP-b', 'LP-c'], $titles($component));

        $component->call('sortBy', 'title');
        $this->assertSame(['LP-c', 'LP-b', 'LP-a'], $titles($component));

        $component->call('sortBy', 'bogus');
        $this->assertSame(['LP-c', 'LP-b', 'LP-a'], $titles($component));

        $component->call('resetSort');
        $this->assertSame(['LP-a', 'LP-c', 'LP-b'], $titles($component));
    }

    public function test_cap_meter_counts_live_rows_and_matches_the_refusal_boundary(): void
    {
        Storage::fake('public');
        $orig = (int) config('launchpad.max_per_department');
        $dept = $this->dept('LPC');
        $owner = $this->deptUser($dept);

        try {
            config(['launchpad.max_per_department' => 3]);
            $this->fileRow($dept, $owner, ['title' => 'LP-cap-1']);
            $this->fileRow($dept, $owner, ['title' => 'LP-cap-2']);
            $this->fileRow($dept, $owner, ['title' => 'LP-cap-3', 'active' => false]);
            $trashed = $this->fileRow($dept, $owner, ['title' => 'LP-cap-gone']);
            $trashed->delete();

            $meter = app(LaunchpadPresenter::class)->capMeter($dept);
            $this->assertSame(3, $meter['used']);
            $this->assertSame(3, $meter['cap']);
            $this->assertSame(100, $meter['pct']);
            $this->assertSame('text-[var(--md-sys-color-error)]', $meter['tone']['text']);

            $this->saveLaunch($owner, 'LP-cap-refused')->assertHasErrors('form.files');
            $this->assertNull(Launchpad::query()->where('title', 'LP-cap-refused')->first());
        } finally {
            config(['launchpad.max_per_department' => $orig]);
        }
    }

    public function test_link_kind_creates_without_files_and_stores_site_and_path(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $site = $this->fakeSiblings();

        $this->saveLink($owner, 'LP-link-basic', $site, 'reports/q1', [$viewer->id])->assertHasNoErrors();
        $row = Launchpad::query()->where('title', 'LP-link-basic')->firstOrFail();

        $this->assertSame('link', $row->kind);
        $this->assertNull($row->folder);
        $this->assertEquals(['site' => $site, 'path' => 'reports/q1'], $row->settings);
        $this->assertSame([$viewer->id], $row->audience);
        $this->assertNull($row->accessible_from);
        $this->assertNull($row->accessible_to);
        $this->assertNull($row->expire_at);
        $this->assertSame([], Storage::disk('public')->directories('launchpad/files'));

        Livewire::withoutLazyLoading();
        Livewire::actingAs($owner)->test(Main::class)
            ->call('switchTab', 'create')
            ->set('form.title', 'LP-link-expiry')
            ->set('form.kind', 'link')
            ->set('form.site', $site)
            ->set('form.path', '')
            ->set('expiryYear', '1406')->set('expiryMonth', '1')->set('expiryDay', '1')
            ->call('save')
            ->assertHasNoErrors();
        $dated = Launchpad::query()->where('title', 'LP-link-expiry')->firstOrFail();
        $this->assertSame('link', $dated->kind);
        $this->assertNotNull($dated->expire_at);
        $this->assertTrue($dated->expire_at->isFuture());
        $this->assertSame([], Storage::disk('public')->directories('launchpad/files'));
    }

    public function test_link_path_and_site_validation_rejects_schemes_traversal_and_unknown_sites(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $site = $this->fakeSiblings();

        $cases = [
            'scheme' => [$site, 'http://x/y', 'form.path'],
            'traversal' => [$site, '../secret', 'form.path'],
            'leading-slash' => [$site, '/abs', 'form.path'],
            'too-long' => [$site, str_repeat('a', 201), 'form.path'],
            'empty-site' => ['', 'reports/q1', 'form.site'],
            'unknown-site' => ['ghost', 'reports/q1', 'form.site'],
        ];
        foreach ($cases as $key => [$caseSite, $path, $errorKey]) {
            $title = 'LP-link-reject-' . $key;
            $this->saveLink($owner, $title, $caseSite, $path)->assertHasErrors($errorKey);
            $this->assertDatabaseMissing('launchpads', ['title' => $title]);
        }
    }

    public function test_link_go_redirects_audience_members_and_marks_them_seen(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $this->fakeSiblings();
        $badge = app(UnseenLaunchpads::class);
        $row = $this->linkRow($dept, $owner, ['title' => 'LP-go-link', 'audience' => [$viewer->id]]);

        $this->actingAs($viewer);
        $this->assertTrue($badge->isActive());

        $response = $this->get(route('launchpad.go', $row->id));
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('https://lp-sibling.example.com/reports/q1', $response->headers->get('Location'));

        $this->assertFalse($badge->isActive());
        $this->assertContains($viewer->id, Launchpad::query()->whereKey($row->id)->firstOrFail()->seen_by);

        $this->actingAs($owner);
        $this->assertFalse($badge->isActive());

        $rootRow = $this->linkRow($dept, $owner, ['title' => 'LP-go-root', 'audience' => [$viewer->id], 'settings' => ['site' => 'lps', 'path' => '']]);
        $this->actingAs($viewer);
        $rootResponse = $this->get(route('launchpad.go', $rootRow->id));
        $this->assertSame('https://lp-sibling.example.com/', $rootResponse->headers->get('Location'));
    }

    public function test_link_go_denies_strangers_expired_scheduled_and_inactive_rows(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $stranger = $this->deptUser($dept);
        $this->fakeSiblings();

        $live = $this->linkRow($dept, $owner, ['title' => 'LP-deny-live', 'audience' => [$viewer->id]]);
        $expired = $this->linkRow($dept, $owner, ['title' => 'LP-deny-expired', 'audience' => [$viewer->id], 'accessible_to' => today()->subDay()->toDateString()]);
        $scheduled = $this->linkRow($dept, $owner, ['title' => 'LP-deny-scheduled', 'audience' => [$viewer->id], 'accessible_from' => today()->addDay()->toDateString()]);
        $inactive = $this->linkRow($dept, $owner, ['title' => 'LP-deny-inactive', 'active' => false, 'audience' => [$viewer->id]]);
        $ghostSite = $this->linkRow($dept, $owner, ['title' => 'LP-deny-ghost', 'audience' => [$viewer->id], 'settings' => ['site' => 'ghost', 'path' => 'x']]);

        $this->get(route('launchpad.go', $live->id))->assertRedirect(route('login'));

        $this->actingAs($stranger);
        $this->assertEmpty404($this->get(route('launchpad.go', $live->id)));

        $this->actingAs($viewer);
        $this->assertEmpty404($this->get(route('launchpad.go', $expired->id)));
        $this->assertEmpty404($this->get(route('launchpad.go', $scheduled->id)));
        $this->assertEmpty404($this->get(route('launchpad.go', $inactive->id)));
        $this->assertEmpty404($this->get(route('launchpad.go', $ghostSite->id)));
        $this->assertNull(Launchpad::query()->whereKey($ghostSite->id)->value('seen_by'));

        $this->actingAs($owner);
        $this->assertEmpty404($this->get(route('launchpad.go', $expired->id)));
    }

    public function test_link_rows_share_title_cap_and_replace_rules_with_static(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $other = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $site = $this->fakeSiblings();

        $this->saveLink($owner, 'LP-link-title', $site, 'a')->assertHasNoErrors();
        $this->saveLink($other, 'LP-link-title', $site, 'b')->assertHasErrors('form.title');
        $this->assertSame(1, Launchpad::query()->where('title', 'LP-link-title')->count());

        $static = $this->fileRow($dept, $owner, ['title' => 'LP-kind-clash']);
        $this->saveLink($other, 'LP-kind-clash', $site, 'c')->assertHasErrors('form.title');
        $this->assertSame(1, Launchpad::query()->where('title', 'LP-kind-clash')->count());

        $original = config('launchpad.max_per_department');
        try {
            config(['launchpad.max_per_department' => 2]);
            $this->saveLink($owner, 'LP-link-capped', $site)->assertHasErrors('form.site');
            $this->assertDatabaseMissing('launchpads', ['title' => 'LP-link-capped']);
        } finally {
            config(['launchpad.max_per_department' => $original]);
        }

        $row = $this->linkRow($dept, $owner, ['title' => 'LP-link-replace', 'audience' => [$viewer->id]]);
        $this->actingAs($viewer)->get(route('launchpad.go', $row->id))->assertStatus(302);
        $this->assertContains($viewer->id, Launchpad::query()->whereKey($row->id)->firstOrFail()->seen_by);

        $this->saveLink($owner, 'LP-link-replace', $site, 'new/path')
            ->assertSet('replaceOpen', true)->call('confirmReplace')->assertHasNoErrors();
        $fresh = Launchpad::query()->whereKey($row->id)->firstOrFail();
        $this->assertSame($row->id, $fresh->id);
        $this->assertSame([$viewer->id], $fresh->audience);
        $this->assertContains($viewer->id, $fresh->seen_by);
        $this->assertEquals(['site' => $site, 'path' => 'new/path'], $fresh->settings);

        $this->saveLink($owner, 'LP-kind-clash', $site, 'd')
            ->assertSet('replaceOpen', true)->call('confirmReplace')->assertHasNoErrors();
        $flipped = Launchpad::query()->whereKey($static->id)->firstOrFail();
        $this->assertSame('link', $flipped->kind);
        $this->assertNull($flipped->folder);
        $this->assertSame([], Storage::disk('public')->files('launchpad/files/' . $static->folder));

        $this->saveLaunch($owner, 'LP-kind-clash')
            ->assertSet('replaceOpen', true)->call('confirmReplace')->assertHasNoErrors();
        $back = Launchpad::query()->whereKey($static->id)->firstOrFail();
        $this->assertSame('static', $back->kind);
        $this->assertNotNull($back->folder);
        $this->assertTrue(Storage::disk('public')->exists('launchpad/files/' . $back->folder . '/' . Launchpad::storedName('index.html')));
        $this->assertSame('index.html', $back->settings['entry']);
    }

    public function test_jalali_date_fields_are_converted_to_gregorian_before_the_action_runs(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $expectedFrom = (new Jalalian(1405, 9, 1))->toCarbon()->toDateString();
        $expectedTo = (new Jalalian(1405, 9, 10))->toCarbon()->toDateString();
        $expectedExpiry = (new Jalalian(1406, 1, 15))->toCarbon()->toDateString();

        Livewire::withoutLazyLoading();
        Livewire::actingAs($owner)->test(Main::class)
            ->call('switchTab', 'create')
            ->set('form.title', 'LP-jalali-dates')
            ->set('form.audience', [])
            ->set('fromYear', '1405')->set('fromMonth', '9')->set('fromDay', '1')
            ->set('toYear', '1405')->set('toMonth', '9')->set('toDay', '10')
            ->set('expiryYear', '1406')->set('expiryMonth', '1')->set('expiryDay', '15')
            ->set('form.files', $this->validFiles())
            ->call('save')
            ->assertHasNoErrors();

        $row = Launchpad::query()->where('title', 'LP-jalali-dates')->firstOrFail();
        $this->assertSame($expectedFrom, $row->accessible_from->toDateString());
        $this->assertSame($expectedTo, $row->accessible_to->toDateString());
        $this->assertSame($expectedExpiry, $row->expire_at->toDateString());
    }

    public function test_max_files_clamps_the_configured_cap_against_the_ini_limit(): void
    {
        $original = (int) config('launchpad.max_files');
        $ini = (int) (ini_get('max_file_uploads') ?: 20);

        try {
            config(['launchpad.max_files' => 3]);
            $this->assertSame(3, LaunchpadForm::maxFiles());
            config(['launchpad.max_files' => 100]);
            $this->assertSame($ini, LaunchpadForm::maxFiles());
        } finally {
            config(['launchpad.max_files' => $original]);
        }
    }

    public function test_load_more_reveals_rows_beyond_the_first_page(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        for ($i = 1; $i <= 31; $i++) {
            $this->fileRow($dept, $owner, ['title' => 'LP-page-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT)]);
        }

        Livewire::actingAs($owner)->test(Main::class)
            ->assertSee('LP-page-31')->assertDontSee('LP-page-01')
            ->call('loadMore')
            ->assertSee('LP-page-01');
    }
}
