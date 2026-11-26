<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Enums\UserType;
use App\Jobs\RefreshLaunchpadJob;
use App\Livewire\Dashboard\Launchpad\Actions\RefreshLaunchpadAction;
use App\Livewire\Dashboard\Launchpad\Forms\LaunchpadForm;
use App\Livewire\Dashboard\Launchpad\Main;
use App\Livewire\Dashboard\Launchpad\Presentation\LaunchpadPresenter;
use App\Livewire\Dashboard\Tab\Home;
use App\Livewire\Dashboard\Tab\Presentation\TabPresenter;
use App\Models\Department;
use App\Models\Launchpad;
use App\Models\Profile;
use App\Models\User;
use App\Services\Menu\Indicators\UnseenLaunchpads;
use App\Services\SsoTokenService;
use App\Support\LaunchpadAccessPolicy;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Morilog\Jalali\Jalalian;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

class LaunchpadTest extends TestCase
{
    private ?string $ssoPublic = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        Livewire::withoutLazyLoading();
        config(['launchpad.refresh.verify_tabs' => false]);
        $this->fakeSiblings();
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

    private function jalaliParts(int $daysAhead): array
    {
        $date = Jalalian::fromCarbon(now()->addDays($daysAhead));

        return [(string) $date->getYear(), (string) $date->getMonth(), (string) $date->getDay()];
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

    private function fakeSiblings(bool $sso = true): string
    {
        $pair = sodium_crypto_sign_keypair();
        $this->ssoPublic = base64_encode(sodium_crypto_sign_publickey($pair));
        config([
            'app.siblings' => [[
                'name' => 'lps',
                'title' => 'LP Sibling',
                'url' => 'https://lp-sibling.example.com/',
                'sso' => ['on' => $sso, 'receiver' => 'https://lp-sibling.example.com/sso/receive', 'aud' => 'lps-app'],
            ]],
            'launchpad.sso' => ['issuer' => 'fateh-test', 'secret_key' => base64_encode(sodium_crypto_sign_secretkey($pair))],
        ]);

        return 'lps';
    }

    private function addSibling(string $name): string
    {
        config(['app.siblings' => [...config('app.siblings'), [
            'name' => $name,
            'title' => 'LP Sibling ' . $name,
            'url' => "https://{$name}.example.com/",
            'sso' => ['on' => true, 'receiver' => "https://{$name}.example.com/sso/receive", 'aud' => "{$name}-app"],
        ]]]);

        return $name;
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
            'settings' => ['site' => 'lps', 'path' => 'reports/q1'],
        ], $overrides));
    }

    private function goAssertion($response): string
    {
        preg_match('/name="assertion" value="([^"]+)"/', (string) $response->getContent(), $match);

        return $match[1] ?? '';
    }

    private function ssoKeys(): array
    {
        $pair = sodium_crypto_sign_keypair();

        return [base64_encode(sodium_crypto_sign_publickey($pair)), base64_encode(sodium_crypto_sign_secretkey($pair))];
    }

    private function ssoClaims(array $overrides = []): array
    {
        return array_replace(['iss' => 'fateh-test', 'aud' => 'lps-app', 'sub' => '7', 'iat' => time(), 'exp' => time() + 60, 'jti' => 'jti-1', 'email' => 'a@example.com'], $overrides);
    }

    private function ssoEncode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private function ssoRawToken(string $header, string $payload, string $secret): string
    {
        $data = $this->ssoEncode($header) . '.' . $this->ssoEncode($payload);

        return $data . '.' . $this->ssoEncode(sodium_crypto_sign_detached($data, base64_decode($secret)));
    }

    private function ssoVerify(string $token, array $keys): ?array
    {
        return SsoTokenService::verify($token, $keys, 'fateh-test', 'lps-app');
    }

    private function atSecondStart(): int
    {
        $start = time();
        while (time() === $start) {
            usleep(5000);
        }

        return time();
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

    private function sourceRow(string $dept, User $owner, array $overrides = []): Launchpad
    {
        return $this->fileRow($dept, $owner, array_replace([
            'source' => ['url' => 'http://example.com/api', 'method' => 'GET', 'body' => null, 'auth' => 'none', 'env' => []],
            'refresh_due_at' => now()->subHour(),
        ], $overrides));
    }

    private function dataFile(Launchpad $row): string
    {
        return 'launchpad/files/' . $row->folder . '/' . Launchpad::storedName('data.json');
    }

    private function saveWithSource(Testable $component, string $title, array $source)
    {
        foreach ($source as $key => $value) {
            $component->set('form.' . $key, $value);
        }

        return $component->set('form.title', $title)->set('form.files', $this->validFiles())->call('save');
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
            'double-dot' => [UploadedFile::fake()->createWithContent('a..html', 'x'), 'form.files', 'نام فایل خیلی بلند است یا علامت غیرمجاز دارد؛ نام کوتاه‌تر و ساده‌تری انتخاب کنید.'],
            'htaccess' => [UploadedFile::fake()->createWithContent('.htaccess', 'x'), 'form.files', 'نام فایل نباید با نقطه شروع شود.'],
            'user-ini' => [UploadedFile::fake()->createWithContent('.user.ini', 'x'), 'form.files', 'نام فایل نباید با نقطه شروع شود.'],
            'php' => [UploadedFile::fake()->createWithContent('x.php', 'x'), 'form.files', 'این نوع فایل اجرایی پذیرفته نمی‌شود؛ فقط فایل‌های وب مجاز را انتخاب کنید.'],
            'phtml' => [UploadedFile::fake()->createWithContent('x.phtml', 'x'), 'form.files', 'این نوع فایل اجرایی پذیرفته نمی‌شود؛ فقط فایل‌های وب مجاز را انتخاب کنید.'],
            'phar' => [UploadedFile::fake()->createWithContent('x.phar', 'x'), 'form.files', 'این نوع فایل اجرایی پذیرفته نمی‌شود؛ فقط فایل‌های وب مجاز را انتخاب کنید.'],
            'php-png' => [UploadedFile::fake()->createWithContent('x.php.png', 'x'), 'form.files', 'این نوع فایل اجرایی پذیرفته نمی‌شود؛ فقط فایل‌های وب مجاز را انتخاب کنید.'],
            'markdown' => [UploadedFile::fake()->createWithContent('notes.md', 'x'), 'form.files', 'این نوع فایل پذیرفته نمی‌شود؛ فهرست فایل‌های مجاز را در راهنما ببینید.'],
            'long-name' => [UploadedFile::fake()->createWithContent(str_repeat('a', 116) . '.html', 'x'), 'form.files', 'نام فایل خیلی بلند است یا علامت غیرمجاز دارد؛ نام کوتاه‌تر و ساده‌تری انتخاب کنید.'],
            'php-content' => [UploadedFile::fake()->createWithContent('page.html', '<?php echo 1; ?>'), 'form.files', 'در ابتدای فایل کد PHP پیدا شد؛ نسخهٔ بدون کد PHP را از سازندهٔ برنامه بگیرید.'],
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
            ->assertSet('precheck.rejected.0', ['name' => 'A.HTML', 'reason' => 'فایلی با همین نام انتخاب شده است؛ نام‌ها باید متفاوت باشند، حتی اگر حروف بزرگ و کوچک فرق کنند.'])
            ->assertHasErrors('form.files');
        $this->assertDatabaseMissing('launchpads', ['title' => 'LP-reject-duplicate']);

        $this->saveLaunch($owner, 'LP-reject-unicode-duplicate', [], [$this->entryFile(), UploadedFile::fake()->createWithContent('É.png', 'x'), UploadedFile::fake()->createWithContent('é.png', 'x')])
            ->assertSet('precheck.rejected.0', ['name' => 'é.png', 'reason' => 'فایلی با همین نام انتخاب شده است؛ نام‌ها باید متفاوت باشند، حتی اگر حروف بزرگ و کوچک فرق کنند.'])
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

            $this->saveLaunch($owner, 'LP-cap-new')
                ->assertHasErrors('form.files')
                ->assertDispatched('toast', message: 'ظرفیت ثبت برنامه در این دپارتمان پر است. یکی از برنامه‌های قبلی را حذف کنید یا از مسئول برنامه بخواهید این کار را انجام دهد.', type: 'error');
            $this->assertDatabaseMissing('launchpads', ['title' => 'LP-cap-new']);

            Launchpad::query()->where('title', 'LP-cap-b')->firstOrFail()->delete();
            $this->saveLaunch($owner, 'LP-cap-new')->assertHasNoErrors();
            $this->assertDatabaseHas('launchpads', ['title' => 'LP-cap-new']);

            $component = $this->saveLaunch($owner, 'LP-cap-a', [], [
                UploadedFile::fake()->createWithContent('index.html', '<html>replaced</html>'),
                UploadedFile::fake()->createWithContent('other.css', 'a{}'),
            ]);
            $component->assertDispatched('open-confirmation')->call('confirmReplace')->assertHasNoErrors();

            $this->assertSame($kept->id, Launchpad::query()->where('title', 'LP-cap-a')->firstOrFail()->id);
            $this->assertSame(2, Launchpad::query()->where('department_id', $dept)->count());
        } finally {
            config(['launchpad.max_per_department' => $original]);
        }
    }

    public function test_developer_bypasses_the_department_cap_on_create(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $developer = $this->deptUser($dept, ['role' => 'developer']);

        $original = config('launchpad.max_per_department');
        try {
            config(['launchpad.max_per_department' => 2]);
            $this->fileRow($dept, $developer, ['title' => 'LP-dev-cap-a']);
            $this->fileRow($dept, $developer, ['title' => 'LP-dev-cap-b']);

            $this->saveLaunch($developer, 'LP-dev-cap-new')->assertHasNoErrors();
            $this->assertDatabaseHas('launchpads', ['title' => 'LP-dev-cap-new']);
            $this->assertSame(3, Launchpad::query()->where('department_id', $dept)->count());
        } finally {
            config(['launchpad.max_per_department' => $original]);
        }
    }

    public function test_developer_workspace_options_ignore_the_three_cap(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $developer = $this->deptUser($dept, ['role' => 'developer']);
        $rows = collect(range(1, 4))->map(fn (int $i) => $this->fileRow($dept, $developer, ['title' => "LP-dev-option-{$i}"]));

        $component = Livewire::actingAs($developer)->test(Main::class);
        $rows->each(fn (Launchpad $row) => $component->call('addOption', $row->id));

        $this->assertSame($rows->pluck('id')->all(), Launchpad::optionIds($developer->fresh()));
    }

    public function test_same_title_by_another_owner_is_refused(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $rowOwner = $this->deptUser($dept);
        $actor = $this->deptUser($dept);
        $otherRow = $this->fileRow($dept, $rowOwner, ['title' => 'LP-title-clash']);
        $folder = $otherRow->folder;

        $this->saveLaunch($actor, 'LP-title-clash')
            ->assertHasErrors('form.title')
            ->assertDispatched('toast', message: 'در این دپارتمان برنامه‌ای با همین نام وجود دارد. نام دیگری انتخاب کنید. برای جایگزینی، به اجازهٔ مدیریت برنامهٔ قبلی نیاز دارید.', type: 'error');
        $this->assertSame($folder, Launchpad::query()->whereKey($otherRow->id)->value('folder'));
        $this->assertSame(1, Launchpad::query()->where('title', 'LP-title-clash')->count());
    }

    public function test_owner_replace_swaps_the_folder_keeps_id_and_audience_and_resets_seen_by(): void
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
        $component->assertDispatched('open-confirmation')->call('confirmReplace')->assertHasNoErrors();

        $fresh = Launchpad::query()->whereKey($row->id)->firstOrFail();
        $this->assertSame($row->id, $fresh->id);
        $this->assertSame([$viewer->id], $fresh->audience);
        $this->assertSame([], $fresh->seen_by);
        $this->actingAs($viewer);
        $this->assertTrue(app(UnseenLaunchpads::class)->isActive());
        $this->actingAs($owner);
        $this->assertFalse(app(UnseenLaunchpads::class)->isActive());
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
            ->set('toYear', (string) jNow())->set('toMonth', '1')->set('toDay', '1')
            ->call('save')
            ->assertHasErrors('form.accessible_to');
        $this->assertDatabaseMissing('launchpads', ['title' => 'LP-date-ended']);

        [$fromYear, $fromMonth, $fromDay] = $this->jalaliParts(30);
        [$expiryYear, $expiryMonth, $expiryDay] = $this->jalaliParts(10);

        Livewire::actingAs($owner)->test(Main::class)
            ->set('form.title', 'LP-date-expiry')
            ->set('form.files', $this->validFiles())
            ->set('fromYear', $fromYear)->set('fromMonth', $fromMonth)->set('fromDay', $fromDay)
            ->set('expiryYear', $expiryYear)->set('expiryMonth', $expiryMonth)->set('expiryDay', $expiryDay)
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

    public function test_failed_save_points_to_the_first_invalid_field_with_an_error_toast(): void
    {
        $user = $this->deptUser($this->dept('LPN'));
        Livewire::withoutLazyLoading();

        Livewire::actingAs($user)->test(Main::class)
            ->call('save')
            ->assertDispatched('form-invalid', field: 'form.title')
            ->assertDispatched('toast', type: 'error');
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
            ->assertDispatched('open-confirmation')->call('confirmReplace')->assertHasNoErrors();

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
            ->assertSee('مهلت دسترسی به برنامه تمام شده است');
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

    public function test_cap_meter_counts_every_undeleted_row_and_matches_the_refusal_boundary(): void
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

            $rules = app(LaunchpadPresenter::class)->capRules();
            $this->assertSame(['ظرفیت', 'جایگزینی', 'نگهداری', 'مخاطبان', 'منبع داده'], array_keys($rules));
            $this->assertStringContainsString(convertToPersian('3'), $rules['ظرفیت']);

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

        [$expiryYear, $expiryMonth, $expiryDay] = $this->jalaliParts(20);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($owner)->test(Main::class)
            ->call('switchTab', 'create')
            ->set('form.title', 'LP-link-expiry')
            ->set('form.kind', 'link')
            ->set('form.site', $this->addSibling('lpx'))
            ->set('form.path', '')
            ->set('expiryYear', $expiryYear)->set('expiryMonth', $expiryMonth)->set('expiryDay', $expiryDay)
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
        $this->fakeSiblings(false);
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

        Launchpad::query()->where('title', 'LP-link-title')->firstOrFail()->delete();
        $replaceSite = $this->addSibling('lpx');

        $row = $this->linkRow($dept, $owner, ['title' => 'LP-link-replace', 'audience' => [$viewer->id], 'settings' => ['site' => $replaceSite, 'path' => 'reports/q1']]);
        $this->withoutVite()->actingAs($viewer)->get(route('launchpad.go', $row->id))->assertOk();
        $this->assertContains($viewer->id, Launchpad::query()->whereKey($row->id)->firstOrFail()->seen_by);

        $this->saveLink($owner, 'LP-link-replace', $replaceSite, 'new/path')
            ->assertDispatched('open-confirmation')->call('confirmReplace')->assertHasNoErrors();
        $fresh = Launchpad::query()->whereKey($row->id)->firstOrFail();
        $this->assertSame($row->id, $fresh->id);
        $this->assertSame([$viewer->id], $fresh->audience);
        $this->assertSame([], $fresh->seen_by);
        $this->assertEquals(['site' => $replaceSite, 'path' => 'new/path'], $fresh->settings);

        $this->saveLink($owner, 'LP-kind-clash', $site, 'd')
            ->assertDispatched('open-confirmation')->call('confirmReplace')->assertHasNoErrors();
        $flipped = Launchpad::query()->whereKey($static->id)->firstOrFail();
        $this->assertSame('link', $flipped->kind);
        $this->assertNull($flipped->folder);
        $this->assertSame([], Storage::disk('public')->files('launchpad/files/' . $static->folder));

        $this->saveLaunch($owner, 'LP-kind-clash')
            ->assertDispatched('open-confirmation')->call('confirmReplace')->assertHasNoErrors();
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
        [$fromYear, $fromMonth, $fromDay] = $this->jalaliParts(10);
        [$toYear, $toMonth, $toDay] = $this->jalaliParts(20);
        [$expiryYear, $expiryMonth, $expiryDay] = $this->jalaliParts(40);
        $expectedFrom = (new Jalalian((int) $fromYear, (int) $fromMonth, (int) $fromDay))->toCarbon()->toDateString();
        $expectedTo = (new Jalalian((int) $toYear, (int) $toMonth, (int) $toDay))->toCarbon()->toDateString();
        $expectedExpiry = (new Jalalian((int) $expiryYear, (int) $expiryMonth, (int) $expiryDay))->toCarbon()->toDateString();

        Livewire::withoutLazyLoading();
        Livewire::actingAs($owner)->test(Main::class)
            ->call('switchTab', 'create')
            ->set('form.title', 'LP-jalali-dates')
            ->set('form.audience', [])
            ->set('fromYear', $fromYear)->set('fromMonth', $fromMonth)->set('fromDay', $fromDay)
            ->set('toYear', $toYear)->set('toMonth', $toMonth)->set('toDay', $toDay)
            ->set('expiryYear', $expiryYear)->set('expiryMonth', $expiryMonth)->set('expiryDay', $expiryDay)
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

    public function test_plan_queues_one_delayed_job_per_due_live_static_row_oldest_first_within_the_limit(): void
    {
        Queue::fake();
        Storage::fake('public');
        Launchpad::query()->toBase()->update(['refresh_due_at' => null]);
        config(['launchpad.refresh.limit' => 2]);
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $inactive = $this->sourceRow($dept, $owner, ['active' => false, 'refresh_due_at' => now()->subDays(3)]);
        $oldest = $this->sourceRow($dept, $owner, ['refresh_due_at' => now()->subDays(2)]);
        $next = $this->sourceRow($dept, $owner, ['refresh_due_at' => now()->subDay()]);
        $overflow = $this->sourceRow($dept, $owner, ['refresh_due_at' => now()->subHour()]);
        $this->sourceRow($dept, $owner, ['refresh_due_at' => now()->addDay()]);

        $this->assertSame(2, (new RefreshLaunchpadAction)->plan());

        $pushed = Queue::pushed(RefreshLaunchpadJob::class)->values();
        $this->assertSame([$oldest->id, $next->id], $pushed->pluck('id')->all());
        $this->assertTrue($pushed[0]->delay < $pushed[1]->delay);
        $this->assertTrue($oldest->fresh()->refresh_due_at->isSameDay(now()->addDay()));
        $this->assertTrue($next->fresh()->refresh_due_at->isSameDay(now()->addDay()));
        $this->assertTrue($overflow->fresh()->refresh_due_at->lte(now()));
        $this->assertTrue($inactive->fresh()->refresh_due_at->lte(now()));
    }

    public function test_plan_queues_nothing_when_no_row_is_due(): void
    {
        Queue::fake();
        Storage::fake('public');
        Launchpad::query()->toBase()->update(['refresh_due_at' => null]);
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $this->sourceRow($dept, $owner, ['refresh_due_at' => now()->addDay()]);
        $this->fileRow($dept, $owner);

        $this->assertSame(0, (new RefreshLaunchpadAction)->plan());

        Queue::assertNothingPushed();
    }

    public function test_the_refresh_job_is_single_try_unique_per_row_and_never_throws(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $row = $this->sourceRow($dept, $owner);
        $job = new RefreshLaunchpadJob($row->id);

        $this->assertInstanceOf(ShouldBeUniqueUntilProcessing::class, $job);
        $this->assertSame(1, $job->tries);
        $this->assertSame(120, $job->timeout);
        $this->assertSame((string) $row->id, $job->uniqueId());

        $job->handle(new RefreshLaunchpadAction);
        (new RefreshLaunchpadJob(0))->handle(new RefreshLaunchpadAction);

        $this->assertSame('invalid', $row->fresh()->settings['refresh']['status']);
    }

    public function test_the_scheduler_plans_the_refresh_once_a_day_at_the_configured_time(): void
    {
        $event = collect(app(Schedule::class)->events())->first(fn ($e) => $e->description === 'launchpad:refresh-plan');

        $this->assertNotNull($event);
        [$hour, $minute] = array_map('intval', explode(':', (string) config('launchpad.refresh.at')));
        $this->assertSame("{$minute} {$hour} * * *", $event->expression);
        $this->assertTrue($event->withoutOverlapping);
    }

    public function test_a_failing_source_still_writes_a_zero_result_file_and_never_throws(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $row = $this->sourceRow($dept, $owner);
        $plain = $this->fileRow($dept, $owner);
        $disk = Storage::disk('public');
        $disk->put($this->dataFile($row), '{"ok":true,"data":[1]}');

        $action = new RefreshLaunchpadAction;
        $action->execute($row->id);
        $action->execute($plain->id);

        $payload = json_decode($disk->get($this->dataFile($row)), true);
        $this->assertFalse($payload['ok']);
        $this->assertNull($payload['data']);
        $this->assertArrayHasKey('updated_at', $payload);
        $refresh = $row->fresh()->settings['refresh'];
        $this->assertSame('invalid', $refresh['status']);
        $this->assertSame([], array_diff(array_keys($refresh), ['at', 'status', 'reason']));
        $this->assertNull($refresh['reason']);
        $this->assertFalse($disk->exists($this->dataFile($plain)));
    }

    public function test_the_ssrf_guard_rejects_unsafe_targets_before_any_network_call(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $login = ['url' => 'https://example.com/login', 'body' => 'x', 'token' => 'token'];
        $sources = [
            'plain http' => ['url' => 'http://example.com/a'],
            'ipv4 literal' => ['url' => 'https://93.184.216.34/a'],
            'loopback literal' => ['url' => 'https://127.0.0.1/a'],
            'private literal' => ['url' => 'https://10.0.0.5/a'],
            'ipv6 literal' => ['url' => 'https://[::1]/a'],
            'custom port' => ['url' => 'https://example.com:8443/a'],
            'userinfo' => ['url' => 'https://user:pass@example.com/a'],
            'trailing dot host' => ['url' => 'https://example.com./a'],
            'login to a trailing dot host' => ['url' => 'https://example.com/a', 'auth' => 'login', 'env' => [...$login, 'url' => 'https://example.com./login']],
            'unsupported method' => ['url' => 'https://example.com/a', 'method' => 'PUT'],
            'login over http' => ['url' => 'https://example.com/a', 'auth' => 'login', 'env' => [...$login, 'url' => 'http://example.com/login']],
            'login to a private literal' => ['url' => 'https://example.com/a', 'auth' => 'login', 'env' => [...$login, 'url' => 'https://192.168.1.10/login']],
            'forbidden header name' => ['url' => 'https://example.com/a', 'auth' => 'header', 'env' => ['kind' => 'custom', 'name' => 'Host', 'value' => 'x']],
            'custom header without a name' => ['url' => 'https://example.com/a', 'auth' => 'header', 'env' => ['kind' => 'custom', 'value' => 'x']],
            'header without a value' => ['url' => 'https://example.com/a', 'auth' => 'header', 'env' => ['kind' => 'bearer']],
        ];

        foreach ($sources as $label => $override) {
            $row = $this->sourceRow($dept, $owner, ['source' => array_replace(['url' => 'https://example.com/a', 'method' => 'GET', 'body' => null, 'auth' => 'none', 'env' => []], $override)]);

            (new RefreshLaunchpadAction)->execute($row->id);

            $this->assertSame('invalid', $row->fresh()->settings['refresh']['status'], $label);
            $this->assertFalse(json_decode(Storage::disk('public')->get($this->dataFile($row)), true)['ok'], $label);
        }
    }

    public function test_the_public_address_check_rejects_private_reserved_and_shared_ranges(): void
    {
        $check = new ReflectionMethod(RefreshLaunchpadAction::class, 'isPublic');
        $action = new RefreshLaunchpadAction;

        foreach (['10.1.2.3', '172.16.0.1', '192.168.1.1', '127.0.0.1', '169.254.169.254', '100.64.0.1', '0.0.0.0', '224.0.0.1', '198.18.0.1', '203.0.113.5'] as $ip) {
            $this->assertFalse($check->invoke($action, $ip), $ip);
        }

        foreach (['8.8.8.8', '93.184.216.34'] as $ip) {
            $this->assertTrue($check->invoke($action, $ip), $ip);
        }
    }

    public function test_source_never_leaves_the_model_json_the_database_or_the_list(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $secret = 'sk-live-' . Str::random(16);
        $row = $this->sourceRow($dept, $owner, [
            'audience' => [$viewer->id],
            'source' => ['url' => 'https://example.com/a', 'method' => 'GET', 'body' => null, 'auth' => 'header', 'env' => ['kind' => 'custom', 'name' => 'X-Key', 'value' => $secret]],
        ]);

        $fresh = Launchpad::query()->findOrFail($row->id)->load('user');
        $this->assertSame($secret, $fresh->source['env']['value']);
        $this->assertArrayNotHasKey('source', $fresh->toArray());
        $this->assertStringNotContainsString($secret, $fresh->toJson());
        $this->assertStringNotContainsString($secret, (string) DB::table('launchpads')->where('id', $row->id)->value('source'));

        $presenter = new LaunchpadPresenter;
        $ownerRow = $presenter->listRow($fresh, $owner);
        $this->assertNotNull($ownerRow['refresh']);
        $this->assertStringNotContainsString($secret, json_encode($ownerRow));
        $this->assertNull($presenter->listRow($fresh, $viewer)['refresh']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($owner)->test(Main::class)->assertSee($row->title)->assertDontSee($secret);
    }

    public function test_deleting_a_row_nulls_its_source_and_refresh_schedule(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $row = $this->sourceRow($dept, $owner);
        Livewire::withoutLazyLoading();

        Livewire::actingAs($owner)->test(Main::class)->call('delete', $row->id);

        $raw = DB::table('launchpads')->where('id', $row->id)->first(['source', 'refresh_due_at', 'deleted_at']);
        $this->assertNotNull($raw->deleted_at);
        $this->assertNull($raw->source);
        $this->assertNull($raw->refresh_due_at);
    }

    public function test_replace_without_source_fields_keeps_the_old_source_and_copies_data_json(): void
    {
        Queue::fake();
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $this->saveLaunch($owner, 'LP-keep-source')->assertHasNoErrors();
        $row = Launchpad::query()->where('title', 'LP-keep-source')->firstOrFail();
        $source = ['url' => 'http://example.com/api', 'method' => 'GET', 'body' => null, 'auth' => 'none', 'env' => []];
        $row->update(['source' => $source, 'refresh_due_at' => now()->addDay()]);
        $disk = Storage::disk('public');
        $disk->put($this->dataFile($row), '{"ok":true,"data":{"n":1}}');
        $oldFolder = $row->folder;

        $this->saveLaunch($owner, 'LP-keep-source', [], [$this->entryFile()])
            ->assertDispatched('open-confirmation')->call('confirmReplace')->assertHasNoErrors();

        $fresh = Launchpad::query()->whereKey($row->id)->firstOrFail();
        $this->assertSame($source, $fresh->source);
        $this->assertNotSame($oldFolder, $fresh->folder);
        $this->assertSame('{"ok":true,"data":{"n":1}}', $disk->get($this->dataFile($fresh)));
        $this->assertSame([], $disk->files('launchpad/files/' . $oldFolder));
        Queue::assertPushed(RefreshLaunchpadJob::class, fn ($job) => $job->id === $row->id);
    }

    public function test_replace_of_a_row_without_a_source_does_not_carry_the_old_uploaded_data_file(): void
    {
        Queue::fake();
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $this->saveLaunch($owner, 'LP-no-source')->assertHasNoErrors();
        $row = Launchpad::query()->where('title', 'LP-no-source')->firstOrFail();
        $disk = Storage::disk('public');
        $disk->put($this->dataFile($row), '{"uploaded":true}');

        $this->saveLaunch($owner, 'LP-no-source', [], [$this->entryFile()])
            ->assertDispatched('open-confirmation')->call('confirmReplace')->assertHasNoErrors();

        $fresh = Launchpad::query()->whereKey($row->id)->firstOrFail();
        $this->assertNotSame($row->folder, $fresh->folder);
        $this->assertFalse($disk->exists($this->dataFile($fresh)));
        Queue::assertNothingPushed();
    }

    public function test_refresh_now_is_refused_for_a_non_manager(): void
    {
        Queue::fake();
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $row = $this->sourceRow($dept, $owner, ['audience' => [$viewer->id]]);
        Livewire::withoutLazyLoading();

        Livewire::actingAs($viewer)->test(Main::class)->call('refreshNow', $row->id)->assertDispatched('toast');

        Queue::assertNothingPushed();
    }

    public function test_refresh_now_on_an_inactive_or_expired_row_returns_dead_and_dispatches_nothing(): void
    {
        Queue::fake();
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $inactive = $this->sourceRow($dept, $owner, ['active' => false]);
        $expired = $this->sourceRow($dept, $owner, ['expire_at' => today()->subDay()]);
        $live = $this->sourceRow($dept, $owner);
        RateLimiter::clear("launchpad-refresh:{$live->id}");
        $action = app(RefreshLaunchpadAction::class);

        $this->assertSame('dead', $action->refreshNow($inactive->id, $owner));
        $this->assertSame('dead', $action->refreshNow($expired->id, $owner));
        Queue::assertNothingPushed();

        Livewire::withoutLazyLoading();
        Livewire::actingAs($owner)->test(Main::class)->call('refreshNow', $inactive->id)
            ->assertDispatched('toast', fn ($event, $params) => ($params['type'] ?? null) === 'warning' && str_contains((string) ($params['message'] ?? ''), 'ابتدا آن را فعال کنید'));
        Queue::assertNothingPushed();

        $this->assertSame('queued', $action->refreshNow($live->id, $owner));
        Queue::assertPushed(RefreshLaunchpadJob::class, 1);
        RateLimiter::clear("launchpad-refresh:{$live->id}");
    }

    public function test_execute_on_a_dead_row_makes_no_outbound_call(): void
    {
        Storage::fake('public');
        Log::spy();
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $inactive = $this->sourceRow($dept, $owner, ['active' => false]);
        $expired = $this->sourceRow($dept, $owner, ['accessible_to' => today()->subDay()]);

        $action = new RefreshLaunchpadAction;
        $action->execute($inactive->id);
        $action->execute($expired->id);

        Log::shouldNotHaveReceived('warning');
        $this->assertFalse(Storage::disk('public')->exists($this->dataFile($inactive)));
        $this->assertFalse(Storage::disk('public')->exists($this->dataFile($expired)));
    }

    public function test_replace_of_a_dead_row_does_not_dispatch_a_refresh(): void
    {
        Queue::fake();
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $this->sourceRow($dept, $owner, ['title' => 'LP-dead-replace', 'active' => false]);

        $this->saveLaunch($owner, 'LP-dead-replace')->call('confirmReplace')->assertHasNoErrors();

        Queue::assertNothingPushed();
    }

    public function test_refresh_now_queues_once_then_is_rate_limited_for_a_manager(): void
    {
        Queue::fake();
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $row = $this->sourceRow($dept, $owner);
        RateLimiter::clear("launchpad-refresh:{$row->id}");
        Livewire::withoutLazyLoading();

        $component = Livewire::actingAs($owner)->test(Main::class)->call('refreshNow', $row->id);
        Queue::assertPushed(RefreshLaunchpadJob::class, 1);
        $component->assertDispatched('toast', fn ($event, $params) => ($params['type'] ?? null) === 'success');

        $component->call('refreshNow', $row->id)
            ->assertDispatched('toast', fn ($event, $params) => ($params['type'] ?? null) === 'warning');
        Queue::assertPushed(RefreshLaunchpadJob::class, 1);

        RateLimiter::clear("launchpad-refresh:{$row->id}");
    }

    public function test_source_rules_follow_the_option_matrix(): void
    {
        Queue::fake();
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($owner)->test(Main::class)->call('switchTab', 'create');

        $this->saveWithSource($component, 'LP-rules-login', ['sourceUrl' => 'https://example.com/a', 'sourceAuth' => 'login', 'sourceEnv' => []])
            ->assertHasErrors(['form.sourceEnv.url', 'form.sourceEnv.body'])
            ->assertHasNoErrors(['form.sourceEnv.token', 'form.sourceEnv.name', 'form.sourceEnv.value']);

        $this->saveWithSource($component, 'LP-rules-header', ['sourceUrl' => 'https://example.com/a', 'sourceAuth' => 'header', 'sourceEnv' => []])
            ->assertHasErrors(['form.sourceEnv.value'])
            ->assertHasNoErrors(['form.sourceEnv.name', 'form.sourceEnv.kind']);

        $this->saveWithSource($component, 'LP-rules-http', ['sourceUrl' => 'http://example.com/a', 'sourceAuth' => 'none', 'sourceEnv' => []])
            ->assertHasErrors(['form.sourceUrl']);

        $this->saveWithSource($component, 'LP-rules-no-url', ['sourceUrl' => '', 'sourceAuth' => 'login', 'sourceEnv' => []])
            ->assertHasErrors(['form.sourceUrl']);

        $this->saveWithSource($component, 'LP-rules-off', ['sourceUrl' => '', 'sourceAuth' => 'off', 'sourceEnv' => []])
            ->assertHasNoErrors();
    }

    public function test_source_option_none_needs_nothing_and_the_filled_options_save_with_defaults(): void
    {
        Queue::fake();
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($owner)->test(Main::class)->call('switchTab', 'create');

        $this->saveWithSource($component, 'LP-src-none', ['sourceUrl' => 'https://example.com/a', 'sourceAuth' => 'none', 'sourceEnv' => []])->assertHasNoErrors();
        $this->saveWithSource($component, 'LP-src-header', ['sourceUrl' => 'https://example.com/a', 'sourceAuth' => 'header', 'sourceEnv' => ['value' => ' abc ', 'name' => 'Ignored']])->assertHasNoErrors();

        $none = Launchpad::query()->where('title', 'LP-src-none')->firstOrFail();
        $header = Launchpad::query()->where('title', 'LP-src-header')->firstOrFail();
        $this->assertSame([], $none->source['env']);
        $this->assertSame(['kind' => 'bearer', 'value' => 'abc'], $header->source['env']);
        $this->assertNotNull($header->refresh_due_at);
        Queue::assertPushed(RefreshLaunchpadJob::class, 2);
    }

    public static function sourceRuleCases(): array
    {
        $url = ['sourceUrl' => 'https://example.com/a'];
        $header = fn (array $env) => [...$url, 'sourceAuth' => 'header', 'sourceEnv' => $env];
        $login = fn (array $env) => [...$url, 'sourceAuth' => 'login', 'sourceEnv' => ['url' => 'https://example.com/login', 'body' => '{"u":"a"}', ...$env]];

        return [
            'http url' => [['sourceUrl' => 'http://example.com/a'], ['form.sourceUrl']],
            'userinfo' => [['sourceUrl' => 'https://u:p@example.com/a'], ['form.sourceUrl']],
            'custom port' => [['sourceUrl' => 'https://example.com:8443/a'], ['form.sourceUrl']],
            'ip literal' => [['sourceUrl' => 'https://93.184.216.34/a'], ['form.sourceUrl']],
            'space in url' => [['sourceUrl' => 'https://example.com/a b'], ['form.sourceUrl']],
            'trailing dot host' => [['sourceUrl' => 'https://example.com./a'], ['form.sourceUrl']],
            'login url with a trailing dot host' => [['sourceUrl' => 'https://example.com/a', 'sourceAuth' => 'login', 'sourceEnv' => ['url' => 'https://example.com./login', 'body' => '{"u":"a"}']], ['form.sourceEnv.url']],
            'post body over the cap' => [['sourceUrl' => 'https://example.com/a', 'sourceMethod' => 'POST', 'sourceBody' => '{"a":"' . str_repeat('a', 2000) . '"}'], ['form.sourceBody']],
            'url too long' => [['sourceUrl' => 'https://example.com/' . str_repeat('a', 500)], ['form.sourceUrl']],
            'unknown method' => [[...$url, 'sourceMethod' => 'PUT'], ['form.sourceMethod']],
            'post body not json' => [[...$url, 'sourceMethod' => 'POST', 'sourceBody' => '{bad'], ['form.sourceBody']],
            'get ignores a bad body' => [[...$url, 'sourceMethod' => 'GET', 'sourceBody' => '{bad'], []],
            'post body of the off type is not validated' => [['sourceAuth' => 'off', 'sourceMethod' => 'POST', 'sourceBody' => '{bad'], []],
            'unknown auth' => [[...$url, 'sourceAuth' => 'oauth'], ['form.sourceAuth']],
            'unknown header kind' => [$header(['kind' => 'x', 'value' => 'v']), ['form.sourceEnv.kind']],
            'custom needs a name' => [$header(['kind' => 'custom', 'value' => 'v']), ['form.sourceEnv.name']],
            'custom forbidden name' => [$header(['kind' => 'custom', 'name' => 'Content-Length', 'value' => 'v']), ['form.sourceEnv.name']],
            'custom name with a space' => [$header(['kind' => 'custom', 'name' => 'X Key', 'value' => 'v']), ['form.sourceEnv.name']],
            'custom name with a trailing newline is cleaned' => [$header(['kind' => 'custom', 'name' => "X-Key\n", 'value' => 'v']), []],
            'custom name with an inner newline' => [$header(['kind' => 'custom', 'name' => "X\nKey", 'value' => 'v']), ['form.sourceEnv.name']],
            'kind default needs no choice' => [$header(['value' => 'v']), []],
            'whitespace-only optional token is skipped' => [$login(['token' => '   ']), []],
            'custom name ok' => [$header(['kind' => 'custom', 'name' => 'X-Custom-Key', 'value' => 'v']), []],
            'bearer needs no name' => [$header(['kind' => 'bearer', 'name' => 'Host', 'value' => 'v']), []],
            'value with a newline' => [$header(['kind' => 'key', 'value' => "a\nb"]), ['form.sourceEnv.value']],
            'value too long' => [$header(['kind' => 'key', 'value' => str_repeat('a', 2001)]), ['form.sourceEnv.value']],
            'login ok without a token path' => [$login([]), []],
            'login url over http' => [$login(['url' => 'http://example.com/login']), ['form.sourceEnv.url']],
            'login body not json' => [$login(['body' => '{bad']), ['form.sourceEnv.body']],
            'login body not an object' => [$login(['body' => '[1,2]']), ['form.sourceEnv.body']],
            'login token path with a space' => [$login(['token' => 'a b']), ['form.sourceEnv.token']],
            'login token path ok' => [$login(['token' => 'data.access_token']), []],
        ];
    }

    #[DataProvider('sourceRuleCases')]
    public function test_source_validation_rules(array $state, array $errors): void
    {
        Queue::fake();
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($owner)->test(Main::class)->call('switchTab', 'create');
        $result = $this->saveWithSource($component, 'LP-rule-case', ['sourceAuth' => 'none', ...$state]);

        $errors === [] ? $result->assertHasNoErrors() : $result->assertHasErrors($errors);
    }

    public function test_the_source_form_stores_only_the_chosen_options_fields(): void
    {
        $form = Livewire::actingAs($this->deptUser($this->dept('LPA')))->test(Main::class)->instance()->form;
        $form->sourceUrl = 'https://example.com/a';
        $form->sourceMethod = 'GET';
        $form->sourceBody = '{"a":1}';
        $form->sourceAuth = 'header';
        $form->sourceEnv = ['kind' => 'custom', 'name' => 'X-Key', 'value' => 'v', 'url' => 'https://x.test', 'token' => 't'];

        $this->assertSame(['kind' => 'custom', 'name' => 'X-Key', 'value' => 'v'], $form->source()['env']);
        $this->assertNull($form->source()['body']);

        $form->sourceMethod = 'POST';
        $form->sourceEnv['kind'] = 'key';
        $this->assertSame('{"a":1}', $form->source()['body']);
        $this->assertSame(['kind', 'value'], array_keys($form->source()['env']));

        $form->sourceAuth = 'login';
        $form->sourceEnv = ['url' => 'https://example.com/login', 'body' => '{"u":1}'];
        $this->assertSame(['url' => 'https://example.com/login', 'body' => '{"u":1}', 'token' => null], $form->source()['env']);
    }

    public function test_token_auto_detect_paths_are_ordered_and_the_header_pattern_is_shared(): void
    {
        $this->assertSame(['access_token', 'token', 'data.access_token', 'data.token', 'jwt', 'id_token'], config('launchpad.refresh.token_paths'));
        $this->assertSame(0, preg_match(LaunchpadForm::HEADER_PATTERN, 'Host'));
        $this->assertSame(1, preg_match(LaunchpadForm::HEADER_PATTERN, 'X-API-Key'));
    }

    public function test_source_errors_are_rendered_under_the_accordion_and_open_it(): void
    {
        Queue::fake();
        Storage::fake('public');
        $owner = $this->deptUser($this->dept('LPA'));
        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($owner)->test(Main::class)->call('switchTab', 'create');

        $this->saveWithSource($component, 'LP-open', ['sourceUrl' => 'http://example.com/a', 'sourceAuth' => 'none'])
            ->assertHasErrors(['form.sourceUrl'])
            ->assertSeeHtml('launchpad-source-open')
            ->assertDontSeeHtml('open || true');

        $this->saveWithSource($component, 'LP-open', ['sourceUrl' => '', 'sourceAuth' => 'off'])->assertDontSeeHtml('launchpad-source-open');
    }

    private const THROWAWAY_TEST_ONLY_RSA_PRIVATE_KEY = '-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCbDK4c53ErxkYr
9RWj09ksVRPfFlspEA92rN8MsedNTKU2qvPvbUH0X+EAlzEoXQ28j50SMgGW2eBy
JucAiJvozV+h32kpPA0zkzGaUe0SIOcUDgJ/s4y+zUkTJl4Wglu7NPAC6dDwK5/9
NakG1z54WcPdRk4CjjoyHSUx8dJPdu2TpoOPFW+Aj6nW485WUvDprejOIoQnutnp
MkN1doNF2szKf43AFSivm1DSPRVbr7L3Re+5/AywDkm0xVHs5QGHN7UyhQfnB8ML
JVun0GCJtjRUMIHo3WH81wzdbWHWF/3oEYnkw9E8XTj98UOmyu+7/6KcCXdZ3wGt
DGC4+4IBAgMBAAECggEABNX0aDpfsTnR3nUaY62rzFIq+H4+AZ3anZzup/q0eBi3
3mieiw0hTXngDqCoi9W7I/Jmt7aPjiDleynzcnTQSAZ/wRCyuvKAhudEWMfNWQ9l
pZ56PXlgHmNQsCQ2MwWFzuTrx2t7dMInj0IpL2lvLVm/XhtfO30sG3wFOPWN1aSX
cwYkv9LYcJtGKjC3twkrBm8PW5Vxz0vbvGVZAczwhxBrkjq+n9hUkSwF0H1KY11E
lLASIQlLTOWhm2Q6MPDsUuV+3pkfOMyhDVVjcy1LgdlM1s4J98nvSbXmefmWmPje
eYq+m1EylbKa28GqVQF5JwMOE4lYVlxBzO6bVsFUsQKBgQDZKsYnSsFgbaLyb+47
FaXOBWqYJhE3Ny/2YB6xzAMwPOk3JtgQ0AaZoHcucOyZboQo/2PvnM0+xAwhaX9K
Pj44DQnIPjugS8AkyHc4g+2KeGCt89OqKL+6ulB7pq56Kx/mWaFgge3kVCFu5g85
m4DgICyM3aQArYI5TG/yjmAyOwKBgQC2xlwhZ/LWB/BgjKwxl7xok9hh3BGggp6M
VVcGfx/ezrYjldZ5G0WdZjzPJ1AfqhUAdIt6tw4y2FqEWJH7Au1EPVVIHt74JhHI
GMsYjH9OaOd5VnvOgQE2lYoM+J3Ex5d48frqAStawoLb1S/uJ96UzuX8XsgsEHvS
v772w1I88wKBgEHmD/E2Zw3K1yp4OrNlM0YcMKdwzcKSlZROwKG1RbZqlV5uBBy9
83LE1cWa8MDyX1pNfVblnwaP2Rp+d+jgkGLT2gcQk2ll8y12Q2874WYmdzfyCGXX
La8NAIU1JDbgRTLSOf0eEEE8IlUTrMUPYgEg4kPwseaewCiBAGt7NsenAoGBAIXh
VHjv0HOwiwXd5P/KmqqrqTM9ZPgC1aoLlNxgX/LvzYDRb7F0JZD+44oFzO30wr97
nvN7hP3/doY87mlEIpH6ry5N1ptEH4BHkPThbRr9QpihnbyMyFu4O8/2iHIslSp1
bYrc4mnv0rS+Owai06qCxvoOX2fPhzO/Wl0cg2+7AoGACGCYHhTlOsW+8AxDNlUq
+52KukbvQfNJagqDh5xubnAdzN9oollHVk+X0R4abABmkh/LhxJrdeu/MnAkPmay
edCmpDRUkyw82xfslrPuXAd1YD0fY4jmYRyZAwIKHCX2GIdlvk2fwwr8yP4qeysn
B7n05Bmpzmfzd+I7+y+mec8=
-----END PRIVATE KEY-----
';

    private function serviceAccountJson(array $override = []): string
    {
        return json_encode(array_replace(['type' => 'service_account', 'client_email' => 'reader@demo-project.iam.gserviceaccount.com', 'private_key' => self::THROWAWAY_TEST_ONLY_RSA_PRIVATE_KEY, 'token_uri' => 'https://evil.test/token'], $override));
    }

    public function test_google_source_validation_rejects_bad_parts_and_needs_no_call_fields(): void
    {
        Queue::fake();
        Storage::fake('public');
        $owner = $this->deptUser($this->dept('LPA'));
        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($owner)->test(Main::class)->call('switchTab', 'create');
        $env = ['access' => 'private', 'sheet' => str_repeat('a', 30), 'ranges' => "'داشبورد پروژه'!A1:H17", 'key' => $this->serviceAccountJson()];
        $cases = [
            'unknown access' => [['access' => 'secret'], 'form.sourceEnv.access'],
            'private without a key' => [['key' => ''], 'form.sourceEnv.key'],
            'bad sheet id' => [['sheet' => 'short id!'], 'form.sourceEnv.sheet'],
            'eleven range lines' => [['ranges' => implode("\n", array_fill(0, 11, 'A1:B2'))], 'form.sourceEnv.ranges'],
            'range line too long' => [['ranges' => str_repeat('a', 101)], 'form.sourceEnv.ranges'],
            'range with a control character' => [['ranges' => "A1\x07"], 'form.sourceEnv.ranges'],
            'missing ranges' => [['ranges' => ''], 'form.sourceEnv.ranges'],
            'key not json' => [['key' => '{bad'], 'form.sourceEnv.key'],
            'key not a service account' => [['key' => $this->serviceAccountJson(['type' => 'authorized_user'])], 'form.sourceEnv.key'],
            'key with a foreign email' => [['key' => $this->serviceAccountJson(['client_email' => 'a@example.com'])], 'form.sourceEnv.key'],
            'key with an unparsable private key' => [['key' => $this->serviceAccountJson(['private_key' => 'not a pem'])], 'form.sourceEnv.key'],
            'key pointing at a server file' => [['key' => $this->serviceAccountJson(['private_key' => 'file://' . __FILE__])], 'form.sourceEnv.key'],
            'key too long' => [['key' => str_repeat('a', 10001)], 'form.sourceEnv.key'],
        ];

        foreach ($cases as $label => [$override, $error]) {
            $this->saveWithSource($component, 'LP-google', ['sourceAuth' => 'google', 'sourceEnv' => [...$env, ...$override]])->assertHasErrors([$error]);
        }

        $this->saveWithSource($component, 'LP-google', ['sourceAuth' => 'google', 'sourceEnv' => $env])
            ->assertHasNoErrors(['form.sourceUrl', 'form.sourceMethod', 'form.sourceBody']);
    }

    public function test_google_source_stores_only_the_email_and_private_key_and_hides_them(): void
    {
        Queue::fake();
        Storage::fake('public');
        $owner = $this->deptUser($this->dept('LPA'));
        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($owner)->test(Main::class)->call('switchTab', 'create');
        $json = $this->serviceAccountJson();

        $this->saveWithSource($component, 'LP-google-ok', ['sourceAuth' => 'google', 'sourceEnv' => ['access' => 'private', 'sheet' => str_repeat('b', 30), 'ranges' => "Tab!A1:B2\n\n Tab!C3:D4 ", 'key' => $json]])->assertHasNoErrors();

        $row = Launchpad::query()->where('title', 'LP-google-ok')->firstOrFail();
        $this->assertSame(['auth', 'env'], array_keys($row->source));
        $this->assertSame('google', $row->source['auth']);
        $this->assertSame(['access', 'sheet', 'ranges', 'email', 'key'], array_keys($row->source['env']));
        $this->assertSame('private', $row->source['env']['access']);
        $this->assertSame("Tab!A1:B2\nTab!C3:D4", $row->source['env']['ranges']);
        $this->assertSame('reader@demo-project.iam.gserviceaccount.com', $row->source['env']['email']);
        $this->assertSame(json_decode($json, true)['private_key'], $row->source['env']['key']);
        $this->assertStringNotContainsString('evil.test', json_encode($row->source));
        $this->assertNotNull($row->refresh_due_at);

        $fresh = Launchpad::query()->findOrFail($row->id)->load('user');
        $this->assertArrayNotHasKey('source', $fresh->toArray());
        $this->assertStringNotContainsString('PRIVATE KEY', $fresh->toJson());
        $this->assertStringNotContainsString('PRIVATE KEY', (string) DB::table('launchpads')->where('id', $row->id)->value('source'));
    }

    public function test_google_option_hides_the_call_fields_and_builds_the_source_from_the_matrix(): void
    {
        $form = Livewire::actingAs($this->deptUser($this->dept('LPA')))->test(Main::class)->instance()->form;
        $form->sourceAuth = 'google';

        $this->assertSame([], array_keys($form->callFields()));
        $this->assertFalse(isset($form->sourceRows()['call-1']));
        $this->assertTrue($form->hasSource());
        $this->assertSame(['access', 'sheet', 'ranges'], array_keys($form->sourceFields()));

        $form->sourceEnv['access'] = 'private';
        $this->assertSame(['access', 'sheet', 'ranges', 'key'], array_keys($form->sourceFields()));

        $form->sourceAuth = 'none';
        $this->assertSame(['url', 'method'], array_keys($form->callFields()));
        $this->assertTrue($form->hasSource());
    }

    public function test_the_type_select_is_first_defaults_to_off_and_off_stores_and_validates_nothing(): void
    {
        Queue::fake();
        Storage::fake('public');
        $owner = $this->deptUser($this->dept('LPA'));
        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($owner)->test(Main::class);
        $form = $component->instance()->form;

        $this->assertSame('off', $form->sourceAuth);
        $this->assertSame(['type'], array_keys($form->sourceRows()));
        $this->assertSame('form.sourceAuth', $form->sourceRows()['type'][0]['model']);
        $this->assertSame(['off', 'google', 'none', 'header', 'login'], array_keys($form->sourceRows()['type'][0]['options']));
        $this->assertFalse($form->hasSource());
        $this->assertNull($form->source());

        $component->set('form.sourceAuth', 'header');
        $this->assertSame('type', array_key_first($component->instance()->form->sourceRows()));

        $component->set('form.sourceAuth', 'off')->call('switchTab', 'create');
        $this->saveWithSource($component, 'LP-off', ['sourceUrl' => 'http://bad', 'sourceBody' => '{bad', 'sourceEnv' => ['value' => "a\nb"]])->assertHasNoErrors();

        $row = Launchpad::query()->where('title', 'LP-off')->firstOrFail();
        $this->assertNull($row->source);
        $this->assertNull($row->refresh_due_at);
    }

    public function test_sheets_url_is_built_from_constants_and_encoded_ranges(): void
    {
        $this->assertSame(
            'https://sheets.googleapis.com/v4/spreadsheets/ID123/values:batchGet?ranges=%27Sheet%201%27%21A1%3AB2&ranges=C%3AD&valueRenderOption=FORMATTED_VALUE',
            RefreshLaunchpadAction::sheetsUrl('ID123', "'Sheet 1'!A1:B2\n\n C:D ")
        );
    }

    public function test_a_google_source_with_a_broken_key_ends_as_a_zero_result_not_an_exception(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $row = $this->sourceRow($dept, $owner, ['source' => ['auth' => 'google', 'env' => ['access' => 'private', 'sheet' => str_repeat('a', 30), 'ranges' => 'A1:B2', 'email' => 'reader@demo-project.iam.gserviceaccount.com', 'key' => 'not a pem']]]);

        (new RefreshLaunchpadAction)->execute($row->id);

        $this->assertSame('invalid', $row->fresh()->settings['refresh']['status']);
        $this->assertFalse(json_decode(Storage::disk('public')->get($this->dataFile($row)), true)['ok']);
    }

    public function test_a_google_source_without_a_known_access_ends_as_a_zero_result_invalid(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $row = $this->sourceRow($dept, $owner, ['source' => ['auth' => 'google', 'env' => ['sheet' => str_repeat('a', 30), 'ranges' => 'Tab!A1:B2']]]);

        (new RefreshLaunchpadAction)->execute($row->id);

        $this->assertSame('invalid', $row->fresh()->settings['refresh']['status']);
    }

    public function test_public_sheet_validation_rejects_bad_parts_and_needs_no_key_or_call_fields(): void
    {
        Queue::fake();
        Storage::fake('public');
        $owner = $this->deptUser($this->dept('LPA'));
        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($owner)->test(Main::class)->call('switchTab', 'create');
        $env = ['access' => 'public', 'sheet' => str_repeat('a', 30), 'ranges' => "'داشبورد پروژه'!A1:H17"];
        $cases = [
            'bad sheet id' => [['sheet' => 'short id!'], 'form.sourceEnv.sheet'],
            'eleven range lines' => [['ranges' => implode("\n", array_fill(0, 11, 'Tab!A1:B2'))], 'form.sourceEnv.ranges'],
            'range without a bang' => [['ranges' => 'A1:B2'], 'form.sourceEnv.ranges'],
            'cell part with a dangling colon' => [['ranges' => 'Tab!A1:'], 'form.sourceEnv.ranges'],
            'cell part with four letters' => [['ranges' => 'Tab!ZZZZ1'], 'form.sourceEnv.ranges'],
            'control character' => [['ranges' => "Tab!A1\x07"], 'form.sourceEnv.ranges'],
            'missing ranges' => [['ranges' => ''], 'form.sourceEnv.ranges'],
        ];

        foreach ($cases as [$override, $error]) {
            $this->saveWithSource($component, 'LP-public', ['sourceAuth' => 'google', 'sourceEnv' => [...$env, ...$override]])->assertHasErrors([$error]);
        }

        $this->saveWithSource($component, 'LP-public', ['sourceAuth' => 'google', 'sourceEnv' => $env])
            ->assertHasNoErrors(['form.sourceUrl', 'form.sourceMethod', 'form.sourceBody', 'form.sourceEnv.sheet', 'form.sourceEnv.ranges', 'form.sourceEnv.key']);
    }

    public function test_public_sheet_stores_only_the_sheet_and_ranges_and_hides_the_call_fields(): void
    {
        Queue::fake();
        Storage::fake('public');
        $owner = $this->deptUser($this->dept('LPA'));
        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($owner)->test(Main::class)->call('switchTab', 'create');

        $json = $this->serviceAccountJson();

        $this->saveWithSource($component, 'LP-public-ok', ['sourceAuth' => 'google', 'sourceEnv' => ['access' => 'public', 'sheet' => str_repeat('b', 30), 'ranges' => "Tab!A1:B2\n\n 'x y'!C3 ", 'key' => 'ignored']])->assertHasNoErrors();

        $row = Launchpad::query()->where('title', 'LP-public-ok')->firstOrFail();
        $this->assertSame(['auth', 'env'], array_keys($row->source));
        $this->assertSame('google', $row->source['auth']);
        $this->assertSame(['access', 'sheet', 'ranges'], array_keys($row->source['env']));
        $this->assertSame("Tab!A1:B2\n'x y'!C3", $row->source['env']['ranges']);
        $this->assertNotNull($row->refresh_due_at);

        $form = Livewire::actingAs($owner)->test(Main::class)->instance()->form;
        $form->sourceAuth = 'google';
        $this->assertSame([], array_keys($form->callFields()));
        $this->assertTrue($form->hasSource());
        $this->assertSame(['access', 'sheet', 'ranges'], array_keys($form->sourceFields()));

        $this->saveWithSource($component, 'LP-switch-a', ['sourceAuth' => 'google', 'sourceEnv' => ['access' => 'private', 'sheet' => str_repeat('c', 30), 'ranges' => 'Tab!A1:B2', 'key' => $json]])->assertHasNoErrors();
        $this->assertArrayHasKey('key', Launchpad::query()->where('title', 'LP-switch-a')->firstOrFail()->source['env']);

        $this->saveWithSource($component, 'LP-switch-b', ['sourceAuth' => 'google', 'sourceEnv' => ['access' => 'public', 'sheet' => str_repeat('c', 30), 'ranges' => 'Tab!A1:B2', 'key' => $json]])->assertHasNoErrors();
        $this->assertSame(['access', 'sheet', 'ranges'], array_keys(Launchpad::query()->where('title', 'LP-switch-b')->firstOrFail()->source['env']));
    }

    public function test_range_parts_unquote_the_tab_and_validate_the_cells(): void
    {
        $this->assertSame(['داشبورد پروژه', 'A1:H17'], RefreshLaunchpadAction::rangeParts("'داشبورد پروژه'!A1:H17"));
        $this->assertSame(["it's", 'B2'], RefreshLaunchpadAction::rangeParts("'it''s'!B2"));
        $this->assertSame(['Sheet1', 'A1:C3'], RefreshLaunchpadAction::rangeParts('Sheet1!A1:C3'));
        $this->assertSame(['Sheet1', 'D4'], RefreshLaunchpadAction::rangeParts("'Sheet1'!D4"));
        $this->assertSame(['tab', 'A1:B2'], RefreshLaunchpadAction::rangeParts('tab!a1:b2'));
        $this->assertNull(RefreshLaunchpadAction::rangeParts('A1:B2'));
        $this->assertNull(RefreshLaunchpadAction::rangeParts('Tab!A1:'));
        $this->assertNull(RefreshLaunchpadAction::rangeParts('Tab!ZZZZ1'));
        $this->assertNull(RefreshLaunchpadAction::rangeParts("'a'b'!A1"));
        $this->assertNull(RefreshLaunchpadAction::rangeParts("Tab!A1\n"));
    }

    public function test_source_input_drops_direction_marks_but_keeps_the_zero_width_non_joiner(): void
    {
        Livewire::withoutLazyLoading();
        $owner = $this->deptUser($this->dept('LPA'));
        $form = Livewire::actingAs($owner)->test(Main::class)->instance()->form;
        $form->sourceUrl = "\u{200F}https://example.com/a\u{200E} ";
        $form->sourceBody = "\u{202B}{\"a\":1}\u{202C}";
        $form->sourceEnv = [
            'sheet' => "\u{200E}1OQ41gaUH5YWvpI35HlnYqIA1_-5J83SjsepNgTYa2ps\u{200F}",
            'ranges' => "'داشبورد پروژه'\u{200F}!\u{200E}A1:H30\r\n\u{202B}'پایش تیم\u{200C}ها'!A1:J160\u{202C}\r\n",
        ];
        $form->normalizeSource();

        $this->assertSame('https://example.com/a', $form->sourceUrl);
        $this->assertSame('{"a":1}', $form->sourceBody);
        $this->assertSame('1OQ41gaUH5YWvpI35HlnYqIA1_-5J83SjsepNgTYa2ps', $form->sourceEnv['sheet']);
        $this->assertSame("'داشبورد پروژه'!A1:H30\n'پایش تیم\u{200C}ها'!A1:J160\n", $form->sourceEnv['ranges']);
        $this->assertSame(['داشبورد پروژه', 'A1:H30'], RefreshLaunchpadAction::rangeParts(explode("\n", $form->sourceEnv['ranges'])[0]));
        $this->assertSame(["پایش تیم\u{200C}ها", 'A1:J160'], RefreshLaunchpadAction::rangeParts(explode("\n", $form->sourceEnv['ranges'])[1]));
    }

    public function test_range_parts_accept_the_reversed_order_a_right_to_left_display_produces(): void
    {
        $this->assertSame(['داشبورد پروژه', 'A1:H30'], RefreshLaunchpadAction::rangeParts("A1:H30!'داشبورد پروژه'"));
        $this->assertSame(["it's", 'B2'], RefreshLaunchpadAction::rangeParts("B2!'it''s'"));
        $this->assertSame(['Sheet1', 'A1:C3'], RefreshLaunchpadAction::rangeParts('a1:c3!Sheet1'));
        $this->assertSame(['A1', 'B2'], RefreshLaunchpadAction::rangeParts('A1!B2'));
        $this->assertNull(RefreshLaunchpadAction::rangeParts("A1:H30!"));
        $this->assertNull(RefreshLaunchpadAction::rangeParts("A0!'tab'"));
        $this->assertNull(RefreshLaunchpadAction::rangeParts("A1\n!'tab'"));
    }

    public function test_public_sheet_urls_are_built_from_constants_and_a_numeric_gid(): void
    {
        $this->assertSame('https://docs.google.com/spreadsheets/d/ID123/htmlview', RefreshLaunchpadAction::publicHtmlviewUrl('ID123'));
        $this->assertSame('https://docs.google.com/spreadsheets/d/ID123/export?format=csv&gid=2089146827', RefreshLaunchpadAction::publicExportUrl('ID123', '2089146827'));
        $this->assertSame('https://docs.google.com/spreadsheets/d/ID123/export?format=csv&gid=0', RefreshLaunchpadAction::publicExportUrl('ID123', '0'));

        foreach (['', '12a', '1&x=2', '-1', '1234567890123', "1\n"] as $gid) {
            $this->assertNull(RefreshLaunchpadAction::publicExportUrl('ID123', $gid), $gid);
        }
    }

    public function test_sheet_gids_reads_the_name_to_gid_map_from_the_htmlview_script(): void
    {
        $html = '<script>var x = {a: 1};'
            . "items.push({name: \"پایش تیم\u{200C}ها\", pageUrl: \"https:\\/\\/docs.google.com\\/spreadsheets\\/d\\/ID\\/edit#gid\\x3d11\", gid: \"11\",initialSheet: (true)});"
            . 'items.push({name: "داش", pageUrl: "x#gid=22", gid: "22",initialSheet: (false)});'
            . 'items.push({name: "Say \"hi\"", pageUrl: "x#gid=33", gid: "33",initialSheet: (false)});'
            . 'items.push({name: "no gid here"}); push({name: "x", gid: "9"}); </script>';

        $this->assertSame(["پایش تیم\u{200C}ها" => '11', 'داش' => '22', 'Say "hi"' => '33'], RefreshLaunchpadAction::sheetGids($html));
        $this->assertSame([], RefreshLaunchpadAction::sheetGids('<html></html>'));
    }

    public function test_sheet_gids_decodes_js_hex_escapes_and_keeps_escaped_backslashes(): void
    {
        $html = 'items.push({name: "it\x27s", gid: "1"});'
            . 'items.push({name: "a\x3db", gid: "2"});'
            . 'items.push({name: "lit\\\\x41", gid: "3"});'
            . 'items.push({name: "bad\xZZ", gid: "4"});';

        $this->assertSame(["it's" => '1', 'a=b' => '2', 'lit\x41' => '3'], RefreshLaunchpadAction::sheetGids($html));
    }

    public function test_row_zero_and_leading_zero_rows_are_rejected(): void
    {
        $this->assertNull(RefreshLaunchpadAction::rangeParts('Tab!A0'));
        $this->assertNull(RefreshLaunchpadAction::rangeParts('Tab!A0:B1'));
        $this->assertNull(RefreshLaunchpadAction::rangeParts('Tab!A1:B0'));
        $this->assertNull(RefreshLaunchpadAction::rangeParts('Tab!A01'));
        $this->assertNull(RefreshLaunchpadAction::rangeParts('Tab!A12345678'));
        $this->assertSame(['Tab', 'A1'], RefreshLaunchpadAction::rangeParts('Tab!A1'));
        $this->assertSame(['Tab', 'A10:Z9999999'], RefreshLaunchpadAction::rangeParts('Tab!A10:Z9999999'));

        $grid = [['a', 'b'], ['c', 'd']];
        $this->assertSame([], RefreshLaunchpadAction::sliceRange($grid, 'A0'));
        $this->assertSame([], RefreshLaunchpadAction::sliceRange($grid, 'A0:B1'));
        $this->assertSame([], RefreshLaunchpadAction::sliceRange($grid, 'A01'));
        $this->assertSame([['a']], RefreshLaunchpadAction::sliceRange($grid, 'A1'));
    }

    public function test_only_the_sheets_download_host_may_be_followed(): void
    {
        $this->assertTrue(RefreshLaunchpadAction::isSheetsDownloadHost('doc-04-ao-sheets.googleusercontent.com'));
        $this->assertTrue(RefreshLaunchpadAction::isSheetsDownloadHost('doc-0g-5k-sheets.googleusercontent.com'));

        foreach (['evil.googleusercontent.com', 'doc-04-ao-sheets.googleusercontent.com.evil.com', 'DOC-04-AO-SHEETS.GOOGLEUSERCONTENT.COM', 'Doc-04-ao-sheets.googleusercontent.com', 'docs.google.com', 'doc-04-ao-sheets.googleusercontent.com.', "doc-04-ao-sheets.googleusercontent.com\n", 'xdoc-04-ao-sheets.googleusercontent.com', ''] as $host) {
            $this->assertFalse(RefreshLaunchpadAction::isSheetsDownloadHost($host), $host);
        }
    }

    public function test_slice_range_cuts_rows_and_columns_trims_and_keeps_interior_gaps(): void
    {
        $grid = [['a', 'b', 'c', 'd'], ['', '', '', ''], ['e', '', 'f', ''], ['g'], ['', '', '', '']];

        $this->assertSame([['a', 'b'], [], ['e']], RefreshLaunchpadAction::sliceRange($grid, 'A1:B3'));
        $this->assertSame([['c', 'd'], [], ['f']], RefreshLaunchpadAction::sliceRange($grid, 'C1:D3'));
        $this->assertSame([['a', 'b', 'c', 'd'], [], ['e', '', 'f'], ['g']], RefreshLaunchpadAction::sliceRange($grid, 'A1:Z99'));
        $this->assertSame([['d']], RefreshLaunchpadAction::sliceRange($grid, 'D1:D3'));
        $this->assertSame([['f']],RefreshLaunchpadAction::sliceRange($grid, 'C3'));
        $this->assertSame([], RefreshLaunchpadAction::sliceRange($grid, 'B2'));
        $this->assertSame([], RefreshLaunchpadAction::sliceRange($grid, 'I1:K9'));
        $this->assertSame([], RefreshLaunchpadAction::sliceRange($grid, 'A50:B60'));
        $this->assertSame([['g']], RefreshLaunchpadAction::sliceRange($grid, 'a4:c9'));
        $this->assertSame([['a']], RefreshLaunchpadAction::sliceRange([['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j']], 'A1'));
        $this->assertSame([['i', 'j']], RefreshLaunchpadAction::sliceRange([['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j']], 'I1:K9'));
        $this->assertSame([], RefreshLaunchpadAction::sliceRange($grid, 'garbage'));
    }

    public function test_csv_grid_keeps_full_width_and_formatted_text(): void
    {
        $grid = new ReflectionMethod(RefreshLaunchpadAction::class, 'csvGrid');
        $csv = "\xEF\xBB\xBF\"نام\",\"18,350\",\"-184.6%\",\"19.1%\",\n,,,,\n\"a,b\",\"x\ny\",,,z\n";

        $this->assertSame([['نام', '18,350', '-184.6%', '19.1%', ''], ['', '', '', '', ''], ['a,b', "x\ny", '', '', 'z']], $grid->invoke(null, $csv));
        $this->assertSame([], $grid->invoke(null, ''));
        $this->assertSame([['نام', '18,350', '-184.6%', '19.1%'], [], ['a,b', "x\ny", '', '', 'z']], RefreshLaunchpadAction::csvRows($csv));
    }

    public function test_csv_rows_keep_strings_quotes_commas_and_persian_and_drop_trailing_empty_rows(): void
    {
        $csv = "\xEF\xBB\xBF\"نام\",\"a,b\",\"say \"\"hi\"\"\"\n\"۱\",,\"x\ny\"\n\n,,\n";

        $this->assertSame([['نام', 'a,b', 'say "hi"'], ['۱', '', "x\ny"]], RefreshLaunchpadAction::csvRows($csv));
        $this->assertSame([], RefreshLaunchpadAction::csvRows(''));
        $this->assertSame([], RefreshLaunchpadAction::csvRows("\n,\n"));
        $this->assertSame([['a'], [], ['b', '', 'c']], RefreshLaunchpadAction::csvRows("a,,\n\nb,,c\n,,\n"));
        $this->assertSame([['<5%', 'x']], RefreshLaunchpadAction::csvRows("<5%,x,\n"));
    }

    public function test_a_public_sheet_with_an_unusable_range_ends_as_a_zero_result_invalid(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $row = $this->sourceRow($dept, $owner, ['source' => ['auth' => 'google', 'env' => ['access' => 'public', 'sheet' => str_repeat('a', 30), 'ranges' => "Tab!A1:B2\nnot a range"]]]);

        (new RefreshLaunchpadAction)->execute($row->id);

        $this->assertSame('invalid', $row->fresh()->settings['refresh']['status']);
        $this->assertSame('range_invalid', $row->fresh()->settings['refresh']['reason']);
        $file = json_decode(Storage::disk('public')->get($this->dataFile($row)), true);
        $this->assertFalse($file['ok']);
        $this->assertNull($file['data']);
    }

    public function test_a_failed_public_run_stores_the_reason_and_keeps_other_settings_and_the_gid_cache(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $cache = ['sheetIdHash' => hash('sha256', 'x'), 'map' => ['Tab' => '1'], 'at' => now()->toIso8601String()];
        $row = $this->sourceRow($dept, $owner, ['source' => ['auth' => 'google', 'env' => ['access' => 'public', 'sheet' => str_repeat('a', 30), 'ranges' => 'not a range']]]);
        $row->update(['settings' => ['keep' => 'me', 'refresh' => ['at' => 'old', 'status' => 'ok', 'reason' => null, 'gids' => $cache]]]);

        (new RefreshLaunchpadAction)->execute($row->id);

        $settings = $row->fresh()->settings;
        $this->assertSame('me', $settings['keep']);
        $this->assertSame('range_invalid', $settings['refresh']['reason']);
        $this->assertEquals($cache, $settings['refresh']['gids']);
    }

    public function test_refresh_meta_clears_the_reason_on_success_and_drops_an_absent_cache(): void
    {
        $gids = ['sheetIdHash' => 'h', 'map' => ['Tab' => '1'], 'at' => 'now'];

        $this->assertSame(['at' => 'A', 'status' => 'ok', 'reason' => null, 'gids' => $gids], RefreshLaunchpadAction::refreshMeta('ok', 'map_failed', $gids, 'A'));
        $this->assertSame(['at' => 'A', 'status' => 'denied', 'reason' => 'private_sheet'], RefreshLaunchpadAction::refreshMeta('denied', 'private_sheet', null, 'A'));
    }

    public function test_gid_cache_decision_uses_skips_or_falls_back_by_hash_age_and_tabs(): void
    {
        $now = Carbon::parse('2026-11-17 12:00:00');
        $cache = fn (string $at, string $hash = 'h', array $map = ['A' => '1', 'B' => '2']) => ['sheetIdHash' => $hash, 'map' => $map, 'at' => $at];

        $this->assertSame('fresh', RefreshLaunchpadAction::gidSource($cache('2026-11-17 01:00:00'), 'h', ['A', 'B'], $now));
        $this->assertSame('fresh', RefreshLaunchpadAction::gidSource($cache('2026-11-17 00:01:00'), 'h', ['A'], $now));
        $this->assertSame('stale', RefreshLaunchpadAction::gidSource($cache('2026-11-16 23:59:00'), 'h', ['A'], $now));
        $this->assertSame('stale', RefreshLaunchpadAction::gidSource($cache('2026-11-17 01:00:00'), 'h', ['A', 'C'], $now));
        $this->assertSame('stale', RefreshLaunchpadAction::gidSource($cache('garbage'), 'h', ['A'], $now));
        $this->assertSame('none', RefreshLaunchpadAction::gidSource($cache('2026-11-17 01:00:00'), 'other', ['A'], $now));
        $this->assertSame('none', RefreshLaunchpadAction::gidSource(null, 'h', ['A'], $now));
        $this->assertSame('none', RefreshLaunchpadAction::gidSource(['sheetIdHash' => 'h', 'at' => 'x'], 'h', ['A'], $now));
    }

    public function test_tab_names_compare_without_zwnj_space_arabic_letters_or_case(): void
    {
        $real = "پایش تیم\u{200C}ها";
        $map = [$real => '1473068987', 'help' => '3614650', "گزارش هفتگی" => '61008427'];

        foreach (['پایش تیم ها', 'پایش تیمها', $real, 'پايش تیم‌ها '] as $typed) {
            $this->assertSame('1473068987', RefreshLaunchpadAction::tabGid($map, $typed), $typed);
        }

        $this->assertSame('3614650', RefreshLaunchpadAction::tabGid($map, 'HELP'));
        $this->assertNull(RefreshLaunchpadAction::tabGid($map, 'پایش سهام'));
        $this->assertSame('fresh', RefreshLaunchpadAction::gidSource(['sheetIdHash' => 'h', 'map' => $map, 'at' => '2026-11-17 01:00:00'], 'h', ['پایش تیم ها', 'help'], Carbon::parse('2026-11-17 12:00:00')));
        $this->assertSame('stale', RefreshLaunchpadAction::gidSource(['sheetIdHash' => 'h', 'map' => $map, 'at' => '2026-11-17 01:00:00'], 'h', ['پایش سهام'], Carbon::parse('2026-11-17 12:00:00')));
    }

    public function test_save_time_tab_check_accepts_a_half_space_or_space_variant_of_a_real_tab(): void
    {
        $this->fakeTabs(['داشبورد', "پایش تیم\u{200C}ها"]);
        $owner = $this->deptUser($this->dept('LPA'));

        $this->saveSheet($owner, 'LP-tab-fold', ['access' => 'public', 'sheet' => str_repeat('a', 30), 'ranges' => "A1:H30!'پایش تیم ها'"])->assertHasNoErrors();
        $this->assertDatabaseHas('launchpads', ['title' => 'LP-tab-fold']);
    }

    public function test_response_validation_picks_the_closed_reason(): void
    {
        $page = '<!DOCTYPE html><script>items.push({name: "A", gid: "1"});</script>';

        $this->assertNull(RefreshLaunchpadAction::verdict($page, 'text/html; charset=utf-8', true));
        $this->assertSame(['invalid', 'map_failed'], RefreshLaunchpadAction::verdict('<html></html>', 'text/html', true));
        $this->assertSame(['invalid', 'map_failed'], RefreshLaunchpadAction::verdict('items.push(', 'text/html', true));
        $this->assertNull(RefreshLaunchpadAction::verdict("a,b\n1,2", 'text/csv; charset=utf-8', false));
        $this->assertNull(RefreshLaunchpadAction::verdict("\xEF\xBB\xBFa,b", '', false));
        $this->assertSame(['denied', 'private_sheet'], RefreshLaunchpadAction::verdict("<!doctype html><html></html>", 'text/html', false));
        $this->assertNull(RefreshLaunchpadAction::verdict("\xEF\xBB\xBF", 'text/csv', false));
        $this->assertNull(RefreshLaunchpadAction::verdict('', 'text/csv', false));
        $this->assertSame(['invalid', 'bad_content'], RefreshLaunchpadAction::verdict('', 'application/json', false));
        $this->assertSame(['invalid', 'map_failed'], RefreshLaunchpadAction::verdict('', 'text/html', true));
        $this->assertSame([], RefreshLaunchpadAction::csvRows(''));
        $this->assertSame(['invalid', 'bad_content'], RefreshLaunchpadAction::verdict("\xFF\xFE", 'text/csv', false));
        $this->assertSame(['invalid', 'bad_content'], RefreshLaunchpadAction::verdict('a,b', 'application/json', false));
    }

    public function test_oversized_grids_and_wide_rows_are_bounded_and_google_sign_in_redirects_mean_private(): void
    {
        $this->assertFalse(RefreshLaunchpadAction::gridTooLarge(str_repeat("\n", 50000)));
        $this->assertTrue(RefreshLaunchpadAction::gridTooLarge(str_repeat("\n", 50001)));

        $grid = new ReflectionMethod(RefreshLaunchpadAction::class, 'csvGrid');
        $this->assertCount(200, $grid->invoke(null, implode(',', range(1, 500)))[0]);

        $this->assertSame('private_sheet', RefreshLaunchpadAction::redirectReason('accounts.google.com'));
        $this->assertSame('private_sheet', RefreshLaunchpadAction::redirectReason('google.com'));
        $this->assertSame('redirect_refused', RefreshLaunchpadAction::redirectReason('evil-google.com'));
        $this->assertSame('redirect_refused', RefreshLaunchpadAction::redirectReason('example.com'));
        $this->assertSame('redirect_refused', RefreshLaunchpadAction::redirectReason(''));
    }

    public function test_content_type_allow_list_and_backoff_schedule(): void
    {
        foreach (['text/csv', 'TEXT/CSV; charset=utf-8', 'text/plain', 'application/octet-stream', ''] as $type) {
            $this->assertTrue(RefreshLaunchpadAction::contentTypeOk($type), $type);
        }

        foreach (['text/html', 'application/json', 'image/png'] as $type) {
            $this->assertFalse(RefreshLaunchpadAction::contentTypeOk($type), $type);
        }

        $this->assertSame([1, 2, 2], array_map(RefreshLaunchpadAction::backoff(...), [1, 2, 3]));
    }

    private function fakeTabs(?array $tabs): RefreshLaunchpadAction
    {
        config(['launchpad.refresh.verify_tabs' => true]);
        $fake = new class($tabs) extends RefreshLaunchpadAction {
            public int $calls = 0;

            public function __construct(private ?array $tabs)
            {
            }

            public function sheetTabs(string $sheet): ?array
            {
                $this->calls++;

                return $this->tabs;
            }
        };
        $this->app->instance(RefreshLaunchpadAction::class, $fake);

        return $fake;
    }

    private function saveSheet(User $owner, string $title, array $env): Testable
    {
        Queue::fake();
        Storage::fake('public');
        Livewire::withoutLazyLoading();

        return $this->saveWithSource(Livewire::actingAs($owner)->test(Main::class)->call('switchTab', 'create'), $title, ['sourceAuth' => 'google', 'sourceEnv' => $env]);
    }

    public function test_a_public_sheet_with_a_missing_tab_is_refused_at_save_with_the_real_tab_names(): void
    {
        $fake = $this->fakeTabs(['داشبورد', "پایش تیم\u{200C}ها"]);
        $owner = $this->deptUser($this->dept('LPA'));

        $component = $this->saveSheet($owner, 'LP-tab-missing', ['access' => 'public', 'sheet' => str_repeat('a', 30), 'ranges' => "'پایش سهام'!A1:H17"]);

        $component->assertHasErrors('form.sourceEnv.ranges');
        $message = $component->errors()->first('form.sourceEnv.ranges');
        $this->assertStringContainsString('برگهٔ پایش سهام در فایل Google Sheets پیدا نشد. نام برگه‌های موجود: ', $message);
        $this->assertStringContainsString("داشبورد، پایش تیم\u{200C}ها", $message);
        $this->assertDatabaseMissing('launchpads', ['title' => 'LP-tab-missing']);
        $this->assertSame(1, $fake->calls);
    }

    public function test_a_public_sheet_with_every_tab_present_or_an_unfetched_map_still_saves(): void
    {
        $this->fakeTabs(['داشبورد', "پایش تیم\u{200C}ها"]);
        $owner = $this->deptUser($this->dept('LPA'));
        $this->saveSheet($owner, 'LP-tab-ok', ['access' => 'public', 'sheet' => str_repeat('a', 30), 'ranges' => "'داشبورد'!A1:B2\n'پایش تیم\u{200C}ها'!A1"])->assertHasNoErrors();
        $this->assertDatabaseHas('launchpads', ['title' => 'LP-tab-ok']);

        $this->fakeTabs(null);
        $this->saveSheet($owner, 'LP-tab-unknown', ['access' => 'public', 'sheet' => str_repeat('a', 30), 'ranges' => "'پایش سهام'!A1:H17"])->assertHasNoErrors();
        $this->assertDatabaseHas('launchpads', ['title' => 'LP-tab-unknown']);
    }

    public function test_the_private_access_path_never_asks_for_the_tab_map(): void
    {
        $fake = $this->fakeTabs(['داشبورد']);
        $owner = $this->deptUser($this->dept('LPA'));

        $this->saveSheet($owner, 'LP-tab-private', ['access' => 'private', 'sheet' => str_repeat('a', 30), 'ranges' => "'پایش سهام'!A1:H17", 'key' => $this->serviceAccountJson()])->assertHasNoErrors();

        $this->assertSame(0, $fake->calls);
        $this->assertDatabaseHas('launchpads', ['title' => 'LP-tab-private']);
    }

    public function test_a_user_who_may_not_create_in_the_department_is_refused_before_any_sheet_lookup(): void
    {
        $fake = $this->fakeTabs(['داشبورد']);
        Queue::fake();
        Storage::fake('public');
        $user = $this->deptUser($this->dept('LPA'));
        $foreign = $this->dept('LPB');
        Livewire::withoutLazyLoading();

        $component = Livewire::actingAs($user)->test(Main::class)->call('switchTab', 'create')->set('form.department_id', $foreign);
        $this->saveWithSource($component, 'LP-refused-first', ['sourceAuth' => 'google', 'sourceEnv' => ['access' => 'public', 'sheet' => str_repeat('a', 30), 'ranges' => "'داشبورد'!A1:B2"]])
            ->assertDispatched('toast', fn (string $name, array $params) => $params['message'] === 'شما اجازهٔ افزودن برنامه در دپارتمان انتخاب‌شده را ندارید.');

        $this->assertSame(0, $fake->calls);
        $this->assertDatabaseMissing('launchpads', ['title' => 'LP-refused-first']);
    }

    public function test_a_fetch_finishing_after_the_source_was_removed_writes_nothing(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $row = $this->sourceRow($dept, $owner, ['source' => ['auth' => 'google', 'env' => ['access' => 'public', 'sheet' => str_repeat('a', 30), 'ranges' => 'not a range']]]);
        Log::listen(fn () => Launchpad::query()->whereKey($row->id)->update(['source' => null, 'refresh_due_at' => null]));

        (new RefreshLaunchpadAction)->execute($row->id);

        $this->assertFalse(Storage::disk('public')->exists($this->dataFile($row)));
        $this->assertArrayNotHasKey('refresh', (array) $row->fresh()->settings);
        $this->assertNull($row->fresh()->source);
    }

    public function test_a_fetch_finishing_after_the_source_changed_writes_nothing(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $row = $this->sourceRow($dept, $owner, ['source' => ['auth' => 'google', 'env' => ['access' => 'public', 'sheet' => str_repeat('a', 30), 'ranges' => 'not a range']]]);
        $changed = ['auth' => 'google', 'env' => ['access' => 'public', 'sheet' => str_repeat('b', 30), 'ranges' => 'also not a range']];
        Log::listen(fn () => Launchpad::query()->findOrFail($row->id)->forceFill(['source' => $changed])->save());

        (new RefreshLaunchpadAction)->execute($row->id);

        $this->assertFalse(Storage::disk('public')->exists($this->dataFile($row)));
        $this->assertArrayNotHasKey('refresh', (array) $row->fresh()->settings);
        $this->assertSame($changed, $row->fresh()->source);
    }

    public function test_precheck_treats_composed_and_decomposed_unicode_names_as_duplicates(): void
    {
        Storage::fake('public');
        $owner = $this->deptUser($this->dept('LPA'));

        $this->saveLaunch($owner, 'LP-reject-nfc-nfd', [], [$this->entryFile(), UploadedFile::fake()->createWithContent("\u{00E9}.png", 'x'), UploadedFile::fake()->createWithContent("e\u{0301}.png", 'x')])
            ->assertSet('precheck.rejected.0.reason', 'فایلی با همین نام انتخاب شده است؛ نام‌ها باید متفاوت باشند، حتی اگر حروف بزرگ و کوچک فرق کنند.')
            ->assertHasErrors('form.files');
        $this->assertDatabaseMissing('launchpads', ['title' => 'LP-reject-nfc-nfd']);
    }

    public function test_the_source_url_rule_accepts_plain_https_hosts_and_refuses_every_trick(): void
    {
        foreach (['https://api.example.com/v1/report', 'https://API.Example.com/x'] as $url) {
            $this->assertTrue(LaunchpadForm::urlAllowed($url), $url);
        }

        foreach (['https://a%2eb.example.com/', 'https://api.example.com\@evil.test/', 'https://exa mple.com/', 'https://[::1]/', 'https://127.0.0.1/', 'https://api.example.com:8443/', 'http://api.example.com/', 'https://user:pw@api.example.com/', 'https://api.example.com./', 'https://_x.example.com/'] as $url) {
            $this->assertFalse(LaunchpadForm::urlAllowed($url), $url);
        }
    }

    public function test_replace_asks_for_confirmation_with_a_rows_payload_instead_of_a_property(): void
    {
        Storage::fake('public');
        $owner = $this->deptUser($this->dept('LPA'));
        $this->saveLaunch($owner, 'LP-dialog')->assertHasNoErrors();

        $this->saveLaunch($owner, 'LP-dialog')->assertDispatched('open-confirmation', fn (string $name, array $params) => $params['title'] === 'جایگزینی برنامهٔ قبلی'
            && $params['method'] === 'confirmReplace'
            && $params['tone'] === 'danger'
            && str_contains($params['message'], "نام برنامه: LP-dialog\n")
            && str_contains($params['message'], "\nدپارتمان: ")
            && str_contains($params['message'], "\nمالک: {$owner->name}\n"));
    }

    public function test_details_show_the_source_block_with_reason_and_tabs_to_managers_only(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $stranger = $this->deptUser($dept);
        $sheet = str_repeat('z', 30);
        $row = $this->sourceRow($dept, $owner, [
            'audience' => [$viewer->id],
            'source' => ['auth' => 'google', 'env' => ['access' => 'public', 'sheet' => $sheet, 'ranges' => "'پایش سهام'!A1:H17"]],
            'settings' => ['files_count' => 2, 'size' => 4096, 'refresh' => ['at' => now()->toIso8601String(), 'status' => 'invalid', 'reason' => 'tab_missing', 'gids' => ['sheetIdHash' => 'x', 'map' => ['داشبورد' => '0', "پایش تیم\u{200C}ها" => '12'], 'at' => now()->toIso8601String()]]],
        ]);
        Livewire::withoutLazyLoading();

        $manager = Livewire::actingAs($owner)->test(Main::class)->call('showDetails', $row->id)
            ->assertDispatched('open-modal')
            ->assertSee('این برگه پیدا نشد. نام آن را دقیقاً از فایل گوگل‌شیت کپی کنید.')
            ->assertSee("داشبورد، پایش تیم\u{200C}ها")
            ->assertSee('Google Sheets — عمومی')
            ->assertDontSee($sheet)
            ->assertDontSee('پایش سهام')
            ->assertSee('مشخصات')
            ->assertSee('دسترسی و داده')
            ->assertSeeHtml('wire:key="launchpad-details-info"')
            ->assertSeeHtml('wire:key="launchpad-details-access"');
        $this->assertNotNull($manager->get('details')['source']);
        $this->assertContains('نام برنامه', array_column($manager->get('details')['rows'], 0));
        $this->assertContains('حجم فایل‌ها', array_column($manager->get('details')['rows'], 0));
        $this->assertSame(['وضعیت', 'مخاطبان', 'شروع دسترسی', 'پایان دسترسی', 'آخرین روز نگهداری برنامه'], array_column($manager->get('details')['access'], 0));
        $this->assertNotContains('وضعیت', array_column($manager->get('details')['rows'], 0));

        $seen = Livewire::actingAs($viewer)->test(Main::class)->call('showDetails', $row->id)
            ->assertDontSee('این برگه پیدا نشد. نام آن را دقیقاً از فایل گوگل‌شیت کپی کنید.')
            ->assertDontSee('Google Sheets — عمومی');
        $this->assertNull($seen->get('details')['source']);
        $this->assertNotEmpty($seen->get('details')['rows']);

        Livewire::actingAs($stranger)->test(Main::class)->call('showDetails', $row->id)
            ->assertSet('details', [])
            ->assertDispatched('toast')
            ->assertNotDispatched('open-modal');
    }

    public function test_toggle_view_accepts_only_known_tabs_and_a_forged_tab_is_clamped(): void
    {
        $user = $this->deptUser($this->dept('LPA'));

        Livewire::actingAs($user)->test(Main::class)
            ->call('toggleView', 'compact')->assertSet('activeTab', 'compact')
            ->call('toggleView', 'forged')->assertSet('activeTab', 'compact')
            ->call('toggleView', 'create')->assertSet('activeTab', 'create')
            ->call('toggleView', 'list')->assertSet('activeTab', 'list')
            ->set('activeTab', 'forged')->assertSet('activeTab', 'list');
    }

    public function test_compact_view_renders_the_side_list_and_the_viewer_in_the_pane_without_the_takeover(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $row = $this->fileRow($dept, $owner, ['title' => 'LP-compact-pane']);

        Livewire::withQueryParams(['tab' => 'compact', 'a' => $row->id])->actingAs($owner)->test(Main::class)
            ->assertSet('activeTab', 'compact')
            ->assertSet('openId', $row->id)
            ->assertSeeHtml('wire:key="launchpad-compact-side"')
            ->assertSeeHtml('wire:key="launchpad-compact-viewer"')
            ->assertSeeHtml('sandbox="allow-scripts"')
            ->assertSee('LP-compact-pane')
            ->assertSeeHtml('aria-label="بازگشت به فهرست برنامه‌ها"')
            ->assertDontSeeHtml('wire:key="launchpad-viewer"')
            ->call('close')
            ->assertSet('openId', null)
            ->assertSeeHtml('wire:key="launchpad-compact-empty"')
            ->assertSeeHtml('wire:key="launchpad-compact-side"');
    }

    public function test_embed_is_still_a_takeover_even_on_the_compact_tab(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $row = $this->fileRow($dept, $owner, ['title' => 'LP-compact-embed']);

        Livewire::withQueryParams(['tab' => 'compact', 'embed' => 1, 'a' => $row->id])->actingAs($owner)->test(Main::class)
            ->assertSeeHtml('wire:key="launchpad-viewer"')
            ->assertDontSeeHtml('wire:key="launchpad-compact-side"');
    }

    public function test_a_miss_in_compact_is_the_empty_state_with_a_toast(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $stranger = $this->deptUser($dept);
        $row = $this->fileRow($dept, $owner, ['title' => 'LP-compact-miss']);

        Livewire::withQueryParams(['tab' => 'compact', 'a' => $row->id])->actingAs($stranger)->test(Main::class)
            ->assertSet('openId', null)
            ->assertSet('viewToken', null)
            ->assertDispatched('toast')
            ->assertSeeHtml('wire:key="launchpad-compact-empty"')
            ->assertDontSeeHtml('wire:key="launchpad-compact-viewer"');
    }

    public function test_date_fields_start_at_the_current_jalali_year_and_the_list_has_the_maximize_button(): void
    {
        $user = $this->deptUser($this->dept('LPA'));

        Livewire::actingAs($user)->test(Main::class)->call('switchTab', 'create')
            ->assertSeeHtml('<option value="' . jNow() . '">')
            ->assertSeeHtml('<option value="' . (jNow() + 5) . '">')
            ->assertDontSeeHtml('<option value="' . (jNow() - 1) . '">')
            ->assertDontSeeHtml('<option value="' . (jNow() + 6) . '">');

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Main::class)
            ->assertSee('open_in_full')
            ->assertSeeHtml('min-h-[300px]')
            ->assertSeeHtml('max-backdrop');
    }

    public function test_viewer_has_the_maximize_button_and_the_wrapper_while_the_iframe_stays_outside_the_toggled_classes(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $row = $this->fileRow($dept, $owner, ['title' => 'LP-viewer-max']);

        Livewire::withQueryParams(['a' => $row->id])->actingAs($owner)->test(Main::class)
            ->assertSeeHtml('wire:key="launchpad-viewer"')
            ->assertSee('open_in_full')
            ->assertSeeHtml('max-backdrop')
            ->assertSeeHtml("'max-widget': max || maxLeaving")
            ->assertSeeHtml('if (max) toggleMaximize()')
            ->assertSeeHtml('wire:click="close"')
            ->assertSeeHtml('aria-label="بازگشت به فهرست برنامه‌ها"')
            ->assertSeeHtml('<iframe sandbox="allow-scripts"')
            ->assertDontSeeHtml('<iframe :class');

        Livewire::withoutLazyLoading();
        Livewire::withQueryParams(['tab' => 'compact', 'a' => $row->id])->actingAs($owner)->test(Main::class)
            ->assertSeeHtml('wire:key="launchpad-compact-viewer"')
            ->assertSee('open_in_full')
            ->assertSeeHtml("'max-widget': max || maxLeaving");
    }

    public function test_embed_viewer_has_no_maximize_button_or_backdrop(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $row = $this->fileRow($dept, $owner, ['title' => 'LP-embed-no-max']);

        Livewire::withQueryParams(['embed' => 1, 'a' => $row->id])->actingAs($owner)->test(Main::class)
            ->assertSeeHtml('<iframe sandbox="allow-scripts"')
            ->assertDontSee('open_in_full')
            ->assertDontSeeHtml('max-backdrop');
    }

    public function test_compact_empty_state_has_the_arrow_label_and_the_create_form_below_with_no_button(): void
    {
        $user = $this->deptUser($this->dept('LPA'));

        Livewire::withQueryParams(['tab' => 'compact'])->actingAs($user)->test(Main::class)
            ->assertSeeHtml('wire:key="launchpad-compact-empty"')
            ->assertSeeHtml('arrow_downward')
            ->assertSee('افزودن برنامه')
            ->assertSeeHtml('wire:key="launchpad-compact-create"')
            ->assertSee('ذخیره برنامه')
            ->assertDontSeeHtml('wire:key="launchpad-compact-add"')
            ->assertDontSeeHtml('wire:click="startCreate"');
    }

    public function test_compact_side_counts_split_active_from_inactive_expired_and_scheduled(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $this->fileRow($dept, $owner, ['title' => 'LP-cnt-live']);
        $this->fileRow($dept, $owner, ['title' => 'LP-cnt-off', 'active' => false]);
        $this->fileRow($dept, $owner, ['title' => 'LP-cnt-expired', 'expire_at' => today()->subDay()]);
        $this->fileRow($dept, $owner, ['title' => 'LP-cnt-soon', 'accessible_from' => today()->addDays(3)]);

        $component = Livewire::withQueryParams(['tab' => 'compact'])->actingAs($owner)->test(Main::class);

        $this->assertSame(['active' => 1, 'inactive' => 3], $component->instance()->counts);
        $component->assertSee('LP-cnt-live')->assertDontSee('LP-cnt-off');
    }

    public function test_compact_side_selector_filters_the_list_and_a_forged_status_is_ignored(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $this->fileRow($dept, $owner, ['title' => 'LP-sel-live']);
        $this->fileRow($dept, $owner, ['title' => 'LP-sel-off', 'active' => false]);

        Livewire::withQueryParams(['tab' => 'compact'])->actingAs($owner)->test(Main::class)
            ->call('switchTab', 'inactive')
            ->assertSet('listStatus', 'inactive')
            ->assertSet('activeTab', 'compact')
            ->assertSee('LP-sel-off')
            ->assertDontSee('LP-sel-live')
            ->call('switchTab', 'forged')
            ->assertSet('listStatus', 'inactive')
            ->call('switchTab', 'active')
            ->assertSee('LP-sel-live')
            ->assertDontSee('LP-sel-off');
    }

    public function test_classic_list_shows_every_program_by_default_and_the_status_filter_narrows_it(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $this->fileRow($dept, $owner, ['title' => 'LP-cls-live']);
        $this->fileRow($dept, $owner, ['title' => 'LP-cls-off', 'active' => false]);

        Livewire::actingAs($owner)->test(Main::class)
            ->assertSet('statusFilter', 'all')
            ->assertDontSeeHtml('wire:key="launchpad-list-status-tabs"')
            ->assertSee('LP-cls-live')
            ->assertSee('LP-cls-off')
            ->call('setStatusFilter', 'inactive')
            ->assertSee('LP-cls-off')
            ->assertDontSee('LP-cls-live')
            ->call('setStatusFilter', 'active')
            ->assertSee('LP-cls-live')
            ->assertDontSee('LP-cls-off')
            ->call('setStatusFilter', 'forged')
            ->assertSet('statusFilter', 'active')
            ->call('setStatusFilter', 'all')
            ->assertSee('LP-cls-live')
            ->assertSee('LP-cls-off');
    }

    public function test_classic_list_status_group_sits_in_the_filter_and_the_selector_status_never_filters_it(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $this->fileRow($dept, $owner, ['title' => 'LP-cls-live']);
        $this->fileRow($dept, $owner, ['title' => 'LP-cls-off', 'active' => false]);

        Livewire::actingAs($owner)->test(Main::class)
            ->assertSeeHtml('wire:key="launchpad-status-filter-inactive"')
            ->assertSeeHtml("wire:click=\"setStatusFilter('inactive')\"")
            ->call('switchTab', 'inactive')
            ->assertSet('listStatus', 'inactive')
            ->assertSee('LP-cls-live')
            ->assertSee('LP-cls-off');
    }

    public function test_activate_sets_active_and_starts_a_scheduled_program_today_but_keeps_a_past_start(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $later = $this->fileRow($dept, $owner, ['title' => 'LP-act-later', 'active' => false, 'accessible_from' => today()->addDays(5)->toDateString()]);
        $past = $this->fileRow($dept, $owner, ['title' => 'LP-act-past', 'active' => false, 'accessible_from' => today()->subDays(5)->toDateString()]);
        $open = $this->fileRow($dept, $owner, ['title' => 'LP-act-open', 'active' => false]);

        Livewire::actingAs($owner)->test(Main::class)
            ->call('activate', $later->id)
            ->assertDispatched('toast', fn ($event, $params) => ($params['type'] ?? null) === 'success')
            ->call('activate', $past->id)
            ->call('activate', $open->id);

        $this->assertTrue($later->refresh()->active);
        $this->assertSame(today()->toDateString(), $later->accessible_from->toDateString());
        $this->assertTrue($past->refresh()->active);
        $this->assertSame(today()->subDays(5)->toDateString(), $past->accessible_from->toDateString());
        $this->assertSame(today()->toDateString(), $open->refresh()->accessible_from->toDateString());
        $this->assertSame('live', $later->status());
    }

    public function test_activate_refuses_an_expired_window_and_changes_nothing(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $ended = $this->fileRow($dept, $owner, ['title' => 'LP-act-ended', 'active' => false, 'accessible_to' => today()->subDay()->toDateString()]);
        $gone = $this->fileRow($dept, $owner, ['title' => 'LP-act-gone', 'active' => false, 'expire_at' => today()->subDay()->toDateString()]);

        Livewire::actingAs($owner)->test(Main::class)
            ->call('activate', $ended->id)
            ->assertDispatched('toast', fn ($event, $params) => ($params['type'] ?? null) === 'warning')
            ->call('activate', $gone->id);

        $this->assertFalse($ended->refresh()->active);
        $this->assertNull($ended->accessible_from);
        $this->assertFalse($gone->refresh()->active);
    }

    public function test_activate_queues_the_refresh_only_for_a_program_with_a_source(): void
    {
        Queue::fake();
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $withSource = $this->sourceRow($dept, $owner, ['active' => false]);
        $plain = $this->fileRow($dept, $owner, ['title' => 'LP-act-plain', 'active' => false]);

        Livewire::actingAs($owner)->test(Main::class)->call('activate', $plain->id);
        Queue::assertNothingPushed();

        Livewire::actingAs($owner)->test(Main::class)->call('activate', $withSource->id);
        Queue::assertPushed(RefreshLaunchpadJob::class, 1);
        $this->assertTrue($withSource->refresh()->active);
        $this->assertNotNull($withSource->refresh_due_at);
    }

    public function test_deactivate_sets_inactive_and_the_row_leaves_the_active_filter(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $row = $this->fileRow($dept, $owner, ['title' => 'LP-deact']);

        Livewire::actingAs($owner)->test(Main::class)
            ->call('setStatusFilter', 'active')
            ->assertSee('LP-deact')
            ->call('deactivate', $row->id)
            ->assertDispatched('toast', fn ($event, $params) => ($params['type'] ?? null) === 'success')
            ->assertDontSee('LP-deact');

        $this->assertFalse($row->refresh()->active);
    }

    public function test_activate_and_deactivate_are_refused_for_non_managers_and_forged_ids(): void
    {
        Queue::fake();
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $otherDept = $this->dept('LPB');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $stranger = $this->deptUser($otherDept);
        $live = $this->fileRow($dept, $owner, ['title' => 'LP-guard-live', 'audience' => [$viewer->id]]);
        $off = $this->fileRow($dept, $owner, ['title' => 'LP-guard-off', 'active' => false, 'audience' => [$viewer->id]]);

        foreach ([$viewer, $stranger] as $user) {
            Livewire::actingAs($user)->test(Main::class)
                ->call('deactivate', $live->id)
                ->assertDispatched('toast', fn ($event, $params) => ($params['type'] ?? null) === 'warning')
                ->call('activate', $off->id)
                ->call('activate', 999999999)
                ->call('deactivate', 999999999);
        }

        $this->assertTrue($live->refresh()->active);
        $this->assertFalse($off->refresh()->active);
        Queue::assertNothingPushed();
    }

    public function test_row_toggle_button_shows_only_to_managers_with_the_matching_action(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $this->fileRow($dept, $owner, ['title' => 'LP-tgl-live', 'audience' => [$viewer->id]]);
        Livewire::withoutLazyLoading();

        Livewire::actingAs($owner)->test(Main::class)
            ->assertSeeHtml('wire:key="launchpad-row-deactivate"')
            ->assertDontSeeHtml('wire:key="launchpad-row-activate"');
        Livewire::withoutLazyLoading();
        Livewire::actingAs($viewer)->test(Main::class)
            ->assertSee('LP-tgl-live')
            ->assertDontSeeHtml('wire:key="launchpad-row-deactivate"')
            ->assertDontSeeHtml('wire:key="launchpad-row-activate"');
    }

    public function test_audience_bulk_selection_selects_all_clears_and_merges_by_department(): void
    {
        Storage::fake('public');
        Livewire::withoutLazyLoading();
        $dept = $this->dept('LPA');
        $other = $this->dept('LPB');
        $owner = $this->deptUser($dept);
        $this->deptUser($dept);
        $this->deptUser($dept);
        $this->deptUser($other);
        $inactive = $this->deptUser($dept, ['status' => 'inactive']);
        $guest = $this->deptUser($dept, ['type' => UserType::Guest->value]);

        $component = Livewire::actingAs($owner)->test(Main::class);
        $all = array_column($component->instance()->candidates, 'id');
        $this->assertNotEmpty($all);
        $this->assertNotContains($owner->id, $all);
        $this->assertNotContains($inactive->id, $all);
        $this->assertNotContains($guest->id, $all);
        $deptIds = array_column(array_filter($component->instance()->candidates, fn ($c) => $c['dept'] === $dept), 'id');
        $this->assertCount(2, $deptIds);
        $this->assertNotContains($owner->id, $deptIds);

        $component->call('bulkSelectPeople', 'form.audience', 'all');
        $this->assertEqualsCanonicalizing($all, array_map('intval', $component->get('form.audience')));

        $component->call('bulkSelectPeople', 'form.audience', 'none');
        $this->assertSame([], $component->get('form.audience'));

        $component->set('form.audience', [(string) $all[0]])->call('bulkSelectPeople', 'form.audience', 'all');
        $selected = array_map('intval', $component->get('form.audience'));
        $this->assertEqualsCanonicalizing($all, $selected);
        $this->assertSame(count($all), count(array_unique($selected)), 'string ids from the wire must not survive as duplicates');

        $component->call('bulkSelectPeople', 'form.audience', 'none');
        $this->assertSame([], $component->get('form.audience'));

        $component->call('bulkSelectPeople', 'form.audience', 'department', $dept);
        $this->assertEqualsCanonicalizing($deptIds, array_map('intval', $component->get('form.audience')));

        $component->call('bulkSelectPeople', 'form.audience', 'department', $dept);
        $this->assertEqualsCanonicalizing($deptIds, array_map('intval', $component->get('form.audience')), 'a second department bulk run must not duplicate ids');

        $component->call('bulkSelectPeople', 'form.audience', 'department');
        $this->assertEqualsCanonicalizing($deptIds, array_map('intval', $component->get('form.audience')), 'a department run without a department value is a no-op');

        $component->set('form.title', 'untouched')->call('bulkSelectPeople', 'form.title', 'all');
        $this->assertSame('untouched', $component->get('form.title'), 'a forged target property is a no-op');
        $this->assertEqualsCanonicalizing($deptIds, array_map('intval', $component->get('form.audience')));

        $component->call('bulkSelectPeople', 'form.audience', 'none', $dept);
        $this->assertSame([], $component->get('form.audience'), 'a department clear removes that department');

        $component->call('bulkSelectPeople', 'form.audience', 'all')->call('bulkSelectPeople', 'form.audience', 'none', $dept);
        $this->assertEqualsCanonicalizing(array_values(array_diff($all, $deptIds)), array_map('intval', $component->get('form.audience')), 'a department clear keeps other departments selected');
        $component->call('bulkSelectPeople', 'form.audience', 'none')->call('bulkSelectPeople', 'form.audience', 'department', $dept);

        $component->call('bulkSelectPeople', 'form.audience', 'everything');
        $this->assertEqualsCanonicalizing($deptIds, array_map('intval', $component->get('form.audience')), 'a forged kind is a no-op');
    }

    public function test_compact_side_row_toggle_button_shows_to_the_manager(): void
    {
        Storage::fake('public');
        Livewire::withoutLazyLoading();
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $this->fileRow($dept, $owner, ['title' => 'LP-tgl-side']);

        Livewire::withQueryParams(['tab' => 'compact'])->actingAs($owner)->test(Main::class)
            ->assertSeeHtml('wire:key="launchpad-side-deactivate"');
    }

    public function test_classic_list_counts_follow_the_scope_filter(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $other = $this->deptUser($dept);
        $this->fileRow($dept, $owner, ['title' => 'LP-cls-mine']);
        $this->fileRow($dept, $other, ['title' => 'LP-cls-theirs', 'audience' => [$owner->id]]);
        $this->fileRow($dept, $owner, ['title' => 'LP-cls-mine-off', 'active' => false]);
        $this->fileRow($dept, $other, ['title' => 'LP-cls-theirs-off', 'active' => false, 'audience' => [$owner->id]]);

        $component = Livewire::actingAs($owner)->test(Main::class);
        $this->assertSame(['active' => 2, 'inactive' => 1], $component->instance()->counts);

        $component->call('setFilter', 'mine');
        $this->assertSame(['active' => 1, 'inactive' => 1], $component->instance()->counts);
        $component->call('setFilter', 'shared');
        $this->assertSame(['active' => 1, 'inactive' => 0], $component->instance()->counts);
    }

    public function test_compact_side_counts_leave_out_a_strangers_programs_and_follow_the_filter(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $stranger = $this->deptUser($dept);
        $this->fileRow($dept, $owner, ['title' => 'LP-vis-owner']);
        $this->fileRow($dept, $stranger, ['title' => 'LP-vis-stranger']);

        $component = Livewire::withQueryParams(['tab' => 'compact'])->actingAs($owner)->test(Main::class);
        $this->assertSame(['active' => 1, 'inactive' => 0], $component->instance()->counts);

        $component->call('setFilter', 'department');
        $this->assertSame(['active' => 1, 'inactive' => 0], $component->instance()->counts);
        $component->call('setFilter', 'shared');
        $this->assertSame(['active' => 0, 'inactive' => 0], $component->instance()->counts);
    }

    public function test_compact_embed_never_shows_the_create_form(): void
    {
        $user = $this->deptUser($this->dept('LPA'));

        Livewire::withQueryParams(['tab' => 'compact', 'embed' => 1])->actingAs($user)->test(Main::class)
            ->assertDontSeeHtml('wire:key="launchpad-compact-create"')
            ->assertDontSeeHtml('wire:key="launchpad-compact-add"');
    }

    public function test_compact_create_save_opens_the_new_program_and_resets_the_form(): void
    {
        Storage::fake('public');
        $owner = $this->deptUser($this->dept('LPA'));

        $component = Livewire::withQueryParams(['tab' => 'compact'])->actingAs($owner)->test(Main::class)
            ->set('form.title', 'LP-compact-save')
            ->set('form.files', $this->validFiles())
            ->call('save')
            ->assertSet('activeTab', 'compact')
            ->assertSet('form.title', '')
            ->assertSeeHtml('wire:key="launchpad-compact-viewer"');

        $row = Launchpad::query()->where('title', 'LP-compact-save')->firstOrFail();
        $component->assertSet('openId', $row->id)->assertNotSet('viewToken', null);
    }

    public function test_adding_to_workspace_registers_an_option_and_never_pins(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $row = $this->fileRow($dept, $owner, ['title' => 'LP-option-add']);

        Livewire::actingAs($owner)->test(Main::class)
            ->assertDontSeeHtml('data-src=')
            ->assertDontSeeHtml('add($el)')
            ->call('addOption', $row->id)
            ->assertDispatched('toast', message: 'برنامه به فضای کاری افزوده شد.', type: 'success')
            ->call('addOption', $row->id)
            ->assertDispatched('toast', message: 'این برنامه در فضای کاری وجود دارد.', type: 'warning');

        $this->assertSame([$row->id], Launchpad::optionIds($owner->fresh()));
    }

    public function test_a_sso_link_is_pinned_and_a_non_sso_link_gets_the_info_toast(): void
    {
        $dept = $this->dept('LPK');
        $owner = $this->deptUser($dept);
        $link = $this->linkRow($dept, $owner, ['title' => 'LP-option-link']);
        $presenter = app(LaunchpadPresenter::class);

        $this->assertTrue($presenter->listRow($link, $owner, Launchpad::ssoSites())['pinnable']);

        Livewire::actingAs($owner)->test(Main::class)
            ->call('addOption', $link->id)
            ->assertDispatched('toast', message: 'برنامه به فضای کاری افزوده شد.', type: 'success');

        $this->assertSame([$link->id], Launchpad::optionIds($owner->fresh()));

        $plain = $this->linkRow($dept, $owner, ['title' => 'LP-option-plain']);
        $this->fakeSiblings(false);

        $this->assertFalse($presenter->listRow($plain, $owner, Launchpad::ssoSites())['pinnable']);

        Livewire::actingAs($owner)->test(Main::class)
            ->call('addOption', $plain->id)
            ->assertDispatched('toast', message: 'این سامانه در فضای کاری باز نمی‌شود؛ آن را از فهرست برنامه‌ها در زبانهٔ جدید باز کنید.', type: 'info');

        $this->assertSame([$link->id], Launchpad::optionIds($owner->fresh()));
    }

    public function test_workspace_options_keep_only_the_newest_three(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $rows = collect(range(1, 4))->map(fn (int $i) => $this->fileRow($dept, $owner, ['title' => "LP-option-cap-{$i}"]));

        $component = Livewire::actingAs($owner)->test(Main::class);
        $rows->each(fn (Launchpad $row) => $component->call('addOption', $row->id));

        $this->assertSame(config('launchpad.workspace.options_max'), count(Launchpad::optionIds($owner->fresh())));
        $this->assertSame($rows->skip(1)->pluck('id')->all(), Launchpad::optionIds($owner->fresh()));
    }

    public function test_workspace_option_write_rereads_the_stored_list_so_two_tabs_keep_both_ids(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $first = $this->fileRow($dept, $owner, ['title' => 'LP-option-tab-a']);
        $second = $this->fileRow($dept, $owner, ['title' => 'LP-option-tab-b']);

        $tabA = Livewire::actingAs($owner)->test(Main::class);
        $tabB = Livewire::actingAs($owner)->test(Main::class);
        $tabA->call('addOption', $first->id);
        $tabB->call('addOption', $second->id);

        $this->assertSame([$first->id, $second->id], Launchpad::optionIds($owner->fresh()));
    }

    public function test_compact_add_icon_shows_only_with_a_program_open_and_close_returns_to_the_empty_state_and_form(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $row = $this->fileRow($dept, $owner, ['title' => 'LP-compact-add']);

        Livewire::withQueryParams(['tab' => 'compact', 'a' => $row->id])->actingAs($owner)->test(Main::class)
            ->assertSeeHtml('wire:key="launchpad-compact-add"')
            ->assertSeeHtml('wire:key="launchpad-compact-viewer"')
            ->assertDontSeeHtml('wire:key="launchpad-compact-create"')
            ->call('close')
            ->assertDontSeeHtml('wire:key="launchpad-compact-add"')
            ->assertSeeHtml('wire:key="launchpad-compact-empty"')
            ->assertSeeHtml('wire:key="launchpad-compact-create"');
    }

    public function test_option_registration_refuses_a_stranger_an_inactive_program_and_a_non_sso_link(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $stranger = $this->deptUser($dept);
        $private = $this->fileRow($dept, $owner, ['title' => 'LP-option-private', 'audience' => []]);
        $inactive = $this->fileRow($dept, $owner, ['title' => 'LP-option-off', 'active' => false]);
        $link = $this->linkRow($dept, $owner, ['title' => 'LP-option-link']);

        Livewire::actingAs($stranger)->test(Main::class)->call('addOption', $private->id)->assertDispatched('toast', type: 'warning');
        Livewire::actingAs($owner)->test(Main::class)->call('addOption', $inactive->id)->assertDispatched('toast', type: 'warning');
        $this->fakeSiblings(false);
        Livewire::actingAs($owner)->test(Main::class)->call('addOption', $link->id)->assertDispatched('toast', type: 'info');

        $this->assertSame([], Launchpad::optionIds($stranger->fresh()));
        $this->assertSame([], Launchpad::optionIds($owner->fresh()));
    }

    public function test_workspace_picker_lists_only_options_the_user_may_still_open(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $stranger = $this->deptUser($dept);
        $shared = $this->fileRow($dept, $owner, ['title' => 'LP-pick-shared', 'audience' => [$viewer->id]]);
        $expired = $this->fileRow($dept, $owner, ['title' => 'LP-pick-expired', 'audience' => [$viewer->id]]);
        $deleted = $this->fileRow($dept, $owner, ['title' => 'LP-pick-deleted', 'audience' => [$viewer->id]]);
        $ids = [$shared->id, $expired->id, $deleted->id];

        foreach ([$viewer, $stranger] as $user) {
            $user->setExtraValue('preferences.' . Launchpad::OPTIONS_PREFERENCE, $ids);
            $user->save();
        }

        $this->assertSame(route('launchpad', ['a' => $shared->id, 'embed' => 1]), $this->pickerSources($viewer)['LP-pick-shared']);
        $this->assertArrayHasKey('LP-pick-expired', $this->pickerSources($viewer));
        $this->assertArrayNotHasKey('LP-pick-shared', $this->pickerSources($stranger));

        $expired->update(['expire_at' => today()->subDay()->toDateString()]);
        $deleted->delete();
        $listed = $this->pickerSources($viewer->fresh());
        $this->assertArrayHasKey('LP-pick-shared', $listed);
        $this->assertArrayNotHasKey('LP-pick-expired', $listed);
        $this->assertArrayNotHasKey('LP-pick-deleted', $listed);

        $shared->update(['audience' => []]);
        $this->assertArrayNotHasKey('LP-pick-shared', $this->pickerSources($viewer->fresh()));
    }

    public function test_workspace_picker_groups_modules_programs_and_connected_systems_with_flags(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $site = $this->fakeSiblings();
        $program = $this->fileRow($dept, $owner, ['title' => 'LP-grp-program', 'audience' => [$viewer->id], 'expire_at' => today()->addDays(2)]);
        $public = $this->fileRow($dept, $owner, ['title' => 'LP-grp-public', 'settings' => ['public' => true]]);
        $link = $this->linkRow($dept, $owner, ['title' => 'LP-grp-link', 'audience' => [$viewer->id], 'settings' => ['site' => $site, 'path' => 'x']]);
        $plain = $this->linkRow($dept, $owner, ['title' => 'LP-grp-plain', 'audience' => [$viewer->id], 'settings' => ['site' => 'ghost', 'path' => 'x']]);

        $viewer->setExtraValue('preferences.' . Launchpad::OPTIONS_PREFERENCE, [$program->id, $public->id, $link->id, $plain->id]);
        $viewer->save();
        $this->actingAs($viewer->fresh());

        $items = collect((new TabPresenter())->heroGadgetCatalog())->keyBy('title');
        $groups = array_values(array_unique(array_column($items->values()->all(), 'group')));

        $this->assertSame(['module', 'program', 'sso'], $groups);
        $this->assertSame(array_keys(TabPresenter::GADGET_GROUPS), $groups);
        $this->assertArrayNotHasKey('LP-grp-plain', $items);

        $name = Department::getCachedOptions()->get($dept);
        $module = $items->firstWhere('group', 'module');

        $this->assertSame(['program', 'deployed_code', $name, $name], [$items['LP-grp-program']['group'], $items['LP-grp-program']['icon'], $items['LP-grp-program']['badge'], $items['LP-grp-program']['subtitle']]);
        $this->assertSame(['sso', 'hub', 'LP Sibling', 'lp-sibling.example.com'], [$items['LP-grp-link']['group'], $items['LP-grp-link']['icon'], $items['LP-grp-link']['badge'], $items['LP-grp-link']['subtitle']]);
        $this->assertSame([null, null, false, false], [$module['badge'], $module['subtitle'], $module['new'], $module['expiring']]);

        $this->assertTrue($items['LP-grp-program']['new']);
        $this->assertTrue($items['LP-grp-program']['expiring']);
        $this->assertTrue($items['LP-grp-link']['new']);
        $this->assertFalse($items['LP-grp-link']['expiring']);
        $this->assertFalse($items['LP-grp-public']['new']);
        $this->assertFalse($items['LP-grp-public']['expiring']);

        $program->markSeenBy($viewer->id);
        $this->assertFalse(collect((new TabPresenter())->heroGadgetCatalog())->firstWhere('title', 'LP-grp-program')['new']);
    }

    public function test_workspace_picker_view_renders_tabs_with_counts_and_open_hooks(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $site = $this->fakeSiblings();
        $program = $this->fileRow($dept, $owner, ['title' => 'LP-tab-program']);
        $link = $this->linkRow($dept, $owner, ['title' => 'LP-tab-link', 'settings' => ['site' => $site, 'path' => 'x']]);
        $owner->setExtraValue('preferences.' . Launchpad::OPTIONS_PREFERENCE, [$program->id, $link->id]);
        $owner->save();
        Livewire::withoutLazyLoading();

        Livewire::actingAs($owner->fresh())->test(Home::class)
            ->assertSeeHtml('hero-gadget-tabs')
            ->assertSeeHtml('tab = &#039;program&#039;')
            ->assertSeeHtml('tab = &#039;sso&#039;')
            ->assertSeeHtml('tab = &#039;module&#039;')
            ->assertSeeHtml('count(&#039;sso&#039;)')
            ->assertDontSeeHtml('tab = &#039;all&#039;')
            ->assertSee('LP-tab-program')
            ->assertSee('LP-tab-link')
            ->assertSee('در حال استفاده')
            ->assertSeeHtml('usedSrc(rows[');

        Livewire::actingAs($this->deptUser($dept))->test(Home::class)
            ->assertDontSeeHtml('tab = &#039;program&#039;')
            ->assertDontSeeHtml('tab = &#039;sso&#039;');
    }

    public function test_workspace_picker_groups_modules_before_programs_for_owner_and_audience(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $stranger = $this->deptUser($dept);
        $row = $this->fileRow($dept, $owner, ['title' => 'LP-group-row', 'audience' => [$viewer->id]]);

        foreach ([$owner, $viewer, $stranger] as $user) {
            $user->setExtraValue('preferences.' . Launchpad::OPTIONS_PREFERENCE, [$row->id]);
            $user->save();
        }

        foreach ([$owner, $viewer] as $user) {
            $this->actingAs($user->fresh());
            $catalog = (new TabPresenter())->heroGadgetCatalog();
            $groups = array_column($catalog, 'group');

            $last = $catalog[array_key_last($catalog)];

            $this->assertSame('program', $last['group']);
            $this->assertSame('LP-group-row', $last['title']);
            $this->assertSame(Department::getCachedOptions()->get($dept), $last['badge']);
            $this->assertSame(['module', 'program'], array_values(array_unique($groups)));
            $this->assertSame($groups, collect($groups)->sortBy(fn ($g) => array_search($g, ['module', 'program']))->values()->all());
        }

        $this->actingAs($stranger->fresh());
        $this->assertSame(['module'], array_values(array_unique(array_column((new TabPresenter())->heroGadgetCatalog(), 'group'))));
    }

    private function headerRow(string $dept, User $owner, array $overrides = []): Launchpad
    {
        return $this->fileRow($dept, $owner, array_replace([
            'source' => ['url' => 'https://api.example.com/report', 'method' => 'GET', 'body' => null, 'auth' => 'header', 'env' => ['kind' => 'bearer', 'name' => null, 'value' => 'SECRET-TOKEN-123']],
            'refresh_due_at' => now()->addDay(),
            'audience' => [],
        ], $overrides));
    }

    private function setEditDate(Testable $component, string $prefix, Carbon $date): Testable
    {
        $jalali = Jalalian::fromCarbon($date);

        return $component->set($prefix . 'Year', (string) $jalali->getYear())->set($prefix . 'Month', (string) $jalali->getMonth())->set($prefix . 'Day', (string) $jalali->getDay());
    }

    public function test_only_a_manager_sees_the_edit_button_in_the_details(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPE');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $stranger = $this->deptUser($dept);
        $row = $this->fileRow($dept, $owner, ['audience' => [$viewer->id]]);
        Livewire::withoutLazyLoading();

        $manager = Livewire::actingAs($owner)->test(Main::class)->call('showDetails', $row->id)->assertSeeHtml('wire:key="launchpad-details-edit"');
        $this->assertTrue($manager->get('details')['manage']);
        $this->assertSame((int) $row->id, $manager->get('details')['id']);

        $seen = Livewire::actingAs($viewer)->test(Main::class)->call('showDetails', $row->id)->assertDontSeeHtml('wire:key="launchpad-details-edit"');
        $this->assertFalse($seen->get('details')['manage']);

        Livewire::actingAs($stranger)->test(Main::class)->call('openEdit', $row->id)->assertSet('editId', null)->assertSet('editOpen', false)->assertDispatched('toast');
        Livewire::actingAs($viewer)->test(Main::class)->call('openEdit', $row->id)->assertSet('editId', null)->assertSet('editOpen', false);
    }

    public function test_open_edit_loads_only_the_safe_fields_and_never_a_secret(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPE');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $row = $this->headerRow($dept, $owner, ['title' => 'LP-edit-open', 'description' => 'old text', 'audience' => [$viewer->id]]);
        Livewire::withoutLazyLoading();

        $component = Livewire::actingAs($owner)->test(Main::class)->call('openEdit', $row->id)
            ->assertSet('editId', $row->id)
            ->assertSet('editOpen', true)
            ->assertSet('editForm.description', 'old text')
            ->assertSet('editForm.audience', [$viewer->id])
            ->assertSet('editForm.sourceAuth', 'header')
            ->assertSet('editForm.sourceUrl', 'https://api.example.com/report')
            ->assertSet('editForm.files', [])
            ->assertSet('editContext.title', 'LP-edit-open')
            ->assertSeeHtml('wire:key="launchpad-edit-source"')
            ->assertDontSee('SECRET-TOKEN-123');

        $this->assertArrayNotHasKey('value', $component->get('editForm.sourceEnv'));
        $this->assertStringNotContainsString('SECRET-TOKEN-123', json_encode($component->get('editForm')));
    }

    public function test_an_elevated_non_owner_edit_offers_the_admin_never_the_row_owner_and_can_clear(): void
    {
        Storage::fake('public');
        Queue::fake();
        config(['launchpad.max_audience' => 100000]);
        $dept = $this->dept('LPE');
        $owner = $this->deptUser($dept);
        $admin = $this->deptUser($dept, ['role' => 'admin']);
        $viewer = $this->deptUser($dept);
        $row = $this->fileRow($dept, $owner, ['audience' => [$admin->id, $viewer->id]]);
        Livewire::withoutLazyLoading();

        $component = Livewire::actingAs($admin)->test(Main::class)->call('openEdit', $row->id);

        $offered = array_column($component->instance()->editCandidates, 'id');
        $this->assertContains($admin->id, $offered);
        $this->assertContains($viewer->id, $offered);
        $this->assertNotContains($owner->id, $offered);
        $created = array_column($component->instance()->candidates, 'id');
        $this->assertContains($owner->id, $created);
        $this->assertNotContains($admin->id, $created);

        $component->call('bulkSelectPeople', 'editForm.audience', 'all');
        $selected = array_map('intval', $component->get('editForm.audience'));
        $this->assertContains($admin->id, $selected);
        $this->assertNotContains($owner->id, $selected);

        $component->set('editForm.audience', [$viewer->id])->call('saveEdit')->assertHasNoErrors();
        $this->assertSame([$viewer->id], $row->refresh()->audience);

        $component->call('openEdit', $row->id)->call('bulkSelectPeople', 'editForm.audience', 'none')->assertSet('editForm.audience', []);
        $component->call('saveEdit')->assertHasNoErrors();
        $this->assertSame([], $row->refresh()->audience);
    }

    public function test_save_edit_updates_description_audience_and_dates(): void
    {
        Storage::fake('public');
        Queue::fake();
        $dept = $this->dept('LPE');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $row = $this->fileRow($dept, $owner, ['title' => 'LP-edit-save', 'audience' => []]);
        $to = now()->addDays(10);
        $keep = now()->addDays(20);
        Livewire::withoutLazyLoading();

        $component = Livewire::actingAs($owner)->test(Main::class)->call('openEdit', $row->id)
            ->set('editForm.description', 'new text')
            ->set('editForm.audience', [$viewer->id]);
        $component = $this->setEditDate($component, 'editTo', $to);
        $component = $this->setEditDate($component, 'editExpiry', $keep);
        $component->call('saveEdit')->assertHasNoErrors()->assertSet('editId', null)->assertSet('editOpen', false)->assertDispatched('toast');

        $row->refresh();
        $this->assertSame('new text', $row->description);
        $this->assertSame([$viewer->id], $row->audience);
        $this->assertSame($to->toDateString(), $row->accessible_to->toDateString());
        $this->assertSame($keep->toDateString(), $row->expire_at->toDateString());
        $this->assertNull($row->accessible_from);
        $this->assertSame('LP-edit-save', $row->title);
    }

    public function test_save_edit_uses_the_create_validation_and_changes_nothing_on_refusal(): void
    {
        Storage::fake('public');
        Queue::fake();
        $dept = $this->dept('LPE');
        $owner = $this->deptUser($dept);
        $others = array_map(fn () => $this->deptUser($dept)->id, range(1, 3));
        $row = $this->fileRow($dept, $owner, ['title' => 'LP-edit-refuse', 'description' => 'keep me', 'audience' => []]);
        Livewire::withoutLazyLoading();

        Livewire::actingAs($owner)->test(Main::class)->call('openEdit', $row->id)
            ->set('editForm.description', 'changed')
            ->set('editToYear', (string) (jNow() - 1))->set('editToMonth', '1')->set('editToDay', '1')
            ->call('saveEdit')
            ->assertHasErrors(['editToYear'])
            ->assertSet('editOpen', true);

        config(['launchpad.max_audience' => 2]);
        Livewire::actingAs($owner)->test(Main::class)->call('openEdit', $row->id)
            ->set('editForm.audience', $others)
            ->call('saveEdit')
            ->assertHasErrors(['editForm.audience']);

        $this->fakeTabs(['داشبورد']);
        $sheet = Livewire::actingAs($owner)->test(Main::class)->call('openEdit', $row->id)
            ->set('editForm.sourceAuth', 'google')
            ->set('editForm.sourceEnv', ['access' => 'public', 'sheet' => str_repeat('a', 30), 'ranges' => "'پایش سهام'!A1:H17"])
            ->call('saveEdit')
            ->assertHasErrors(['editForm.sourceEnv.ranges']);
        $this->assertStringContainsString('داشبورد', collect($sheet->errors()->get('editForm.sourceEnv.ranges'))->first());

        $row->refresh();
        $this->assertSame('keep me', $row->description);
        $this->assertSame([], $row->audience);
        $this->assertNull($row->accessible_to);
        $this->assertNull($row->source);
        Queue::assertNothingPushed();
    }

    public function test_save_edit_ignores_a_forged_title_department_and_kind(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPE');
        $other = $this->dept('LPF');
        $owner = $this->deptUser($dept);
        $row = $this->fileRow($dept, $owner, ['title' => 'LP-edit-forge']);
        Livewire::withoutLazyLoading();

        Livewire::actingAs($owner)->test(Main::class)->call('openEdit', $row->id)
            ->set('editForm.title', 'LP-hacked')
            ->set('editForm.department_id', $other)
            ->set('editForm.kind', 'link')
            ->set('editForm.site', 'lps')
            ->set('editForm.description', 'ok')
            ->call('saveEdit')
            ->assertHasNoErrors();

        $row->refresh();
        $this->assertSame('LP-edit-forge', $row->title);
        $this->assertSame($dept, $row->department_id);
        $this->assertSame('static', $row->kind);
        $this->assertNotNull($row->folder);
        $this->assertSame('ok', $row->description);
    }

    public function test_edit_id_is_locked_and_a_save_without_an_open_edit_does_nothing(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPE');
        $owner = $this->deptUser($dept);
        $stranger = $this->deptUser($this->dept('LPF'));
        $row = $this->fileRow($dept, $owner, ['title' => 'LP-edit-locked', 'description' => 'untouched']);
        Livewire::withoutLazyLoading();

        Livewire::actingAs($stranger)->test(Main::class)->call('openEdit', $row->id)->set('editForm.description', 'hacked')->call('saveEdit');
        $this->assertSame('untouched', $row->refresh()->description);

        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::actingAs($stranger)->test(Main::class)->set('editId', $row->id);
    }

    public function test_an_unchanged_source_is_kept_without_secrets_in_state_and_queues_nothing(): void
    {
        Storage::fake('public');
        Queue::fake();
        $dept = $this->dept('LPE');
        $owner = $this->deptUser($dept);
        $row = $this->headerRow($dept, $owner, ['title' => 'LP-edit-keep']);
        $stored = $row->source;
        Livewire::withoutLazyLoading();

        $component = Livewire::actingAs($owner)->test(Main::class)->call('openEdit', $row->id)
            ->set('editForm.description', 'plain edit')
            ->call('saveEdit')
            ->assertHasNoErrors();

        $row->refresh();
        $this->assertSame($stored, $row->source);
        $this->assertSame('plain edit', $row->description);
        $this->assertStringNotContainsString('SECRET-TOKEN-123', json_encode($component->get('editForm')));
        Queue::assertNothingPushed();
    }

    public function test_a_changed_source_is_saved_and_queues_a_refresh_and_off_removes_it(): void
    {
        Storage::fake('public');
        Queue::fake();
        $dept = $this->dept('LPE');
        $owner = $this->deptUser($dept);
        $row = $this->headerRow($dept, $owner, ['title' => 'LP-edit-source']);
        Livewire::withoutLazyLoading();

        Livewire::actingAs($owner)->test(Main::class)->call('openEdit', $row->id)
            ->set('editForm.sourceUrl', 'https://api.example.com/other')
            ->call('saveEdit')
            ->assertHasNoErrors();
        $row->refresh();
        $this->assertSame('https://api.example.com/other', $row->source['url']);
        $this->assertSame('SECRET-TOKEN-123', $row->source['env']['value']);
        Queue::assertPushed(RefreshLaunchpadJob::class, 1);
        Cache::lock('laravel_unique_job:' . RefreshLaunchpadJob::class . ':' . $row->id)->forceRelease();

        Livewire::actingAs($owner)->test(Main::class)->call('openEdit', $row->id)
            ->set('editForm.sourceEnv.value', 'NEW-TOKEN-456')
            ->call('saveEdit')
            ->assertHasNoErrors();
        $this->assertSame('NEW-TOKEN-456', $row->refresh()->source['env']['value']);
        Queue::assertPushed(RefreshLaunchpadJob::class, 2);

        Livewire::actingAs($owner)->test(Main::class)->call('openEdit', $row->id)
            ->set('editForm.sourceAuth', 'off')
            ->call('saveEdit')
            ->assertHasNoErrors();
        $row->refresh();
        $this->assertNull($row->source);
        $this->assertNull($row->refresh_due_at);
        Queue::assertPushed(RefreshLaunchpadJob::class, 2);
    }

    public function test_switching_the_source_type_requires_the_secret_again(): void
    {
        Storage::fake('public');
        Queue::fake();
        $dept = $this->dept('LPE');
        $owner = $this->deptUser($dept);
        $row = $this->headerRow($dept, $owner, ['title' => 'LP-edit-switch']);
        Livewire::withoutLazyLoading();

        Livewire::actingAs($owner)->test(Main::class)->call('openEdit', $row->id)
            ->set('editForm.sourceAuth', 'login')
            ->set('editForm.sourceEnv', ['url' => 'https://api.example.com/login'])
            ->call('saveEdit')
            ->assertHasErrors(['editForm.sourceEnv.body']);

        $this->assertSame('header', $row->refresh()->source['auth']);
    }

    public function test_a_past_dated_program_stays_editable_while_its_dates_are_left_as_loaded(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPE');
        $owner = $this->deptUser($dept);
        $row = $this->fileRow($dept, $owner, [
            'title' => 'LP-edit-past',
            'accessible_from' => today()->subDays(30)->toDateString(),
            'accessible_to' => today()->subDays(3)->toDateString(),
            'expire_at' => today()->subDay()->toDateString(),
        ]);
        Livewire::withoutLazyLoading();

        Livewire::actingAs($owner)->test(Main::class)->call('openEdit', $row->id)
            ->set('editForm.description', 'still editable')
            ->call('saveEdit')
            ->assertHasNoErrors()
            ->assertSet('editOpen', false);

        $row->refresh();
        $this->assertSame('still editable', $row->description);
        $this->assertSame(today()->subDays(3)->toDateString(), $row->accessible_to->toDateString());
        $this->assertSame(today()->subDay()->toDateString(), $row->expire_at->toDateString());
    }

    public function test_switching_the_source_off_deletes_the_generated_data_file_but_keeps_an_uploaded_one(): void
    {
        Storage::fake('public');
        Queue::fake();
        $dept = $this->dept('LPE');
        $owner = $this->deptUser($dept);
        $generated = $this->headerRow($dept, $owner, ['title' => 'LP-edit-off', 'settings' => ['refresh' => ['status' => 'ok', 'at' => now()->toIso8601String()]]]);
        $uploaded = $this->headerRow($dept, $owner, ['title' => 'LP-edit-off-plain']);
        Storage::disk('public')->put($this->dataFile($generated), '{"ok":true}');
        Storage::disk('public')->put($this->dataFile($uploaded), '{"mine":true}');
        Livewire::withoutLazyLoading();

        foreach ([$generated, $uploaded] as $row) {
            Livewire::actingAs($owner)->test(Main::class)->call('openEdit', $row->id)
                ->set('editForm.sourceAuth', 'off')
                ->call('saveEdit')
                ->assertHasNoErrors();
        }

        Storage::disk('public')->assertMissing($this->dataFile($generated));
        Storage::disk('public')->assertExists($this->dataFile($uploaded));
        $this->assertArrayNotHasKey('refresh', (array) $generated->refresh()->settings);
    }

    public function test_activate_on_an_already_live_program_neither_rearms_nor_queues_a_refresh(): void
    {
        Storage::fake('public');
        Queue::fake();
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $row = $this->headerRow($dept, $owner, ['title' => 'LP-act-live']);
        $due = $row->fresh()->refresh_due_at->toDateTimeString();
        Livewire::withoutLazyLoading();

        Livewire::actingAs($owner)->test(Main::class)->call('activate', $row->id)->assertDispatched('toast', type: 'success');

        Queue::assertNothingPushed();
        $this->assertSame($due, $row->fresh()->refresh_due_at->toDateTimeString());
    }

    public function test_audience_bulk_selection_respects_the_configured_cap_and_toasts(): void
    {
        Storage::fake('public');
        Livewire::withoutLazyLoading();
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        foreach (range(1, 3) as $ignored) {
            $this->deptUser($dept);
        }
        config(['launchpad.max_audience' => 2]);

        $component = Livewire::actingAs($owner)->test(Main::class)->call('bulkSelectPeople', 'form.audience', 'all');

        $this->assertCount(2, $component->get('form.audience'));
        $component->assertDispatched('toast', type: 'info');
    }

    public function test_assertion_round_trips_and_rejects_tamper_expiry_wrong_audience_wrong_issuer_wrong_alg_and_overlong_lifetime(): void
    {
        $pair = sodium_crypto_sign_keypair();
        $public = base64_encode(sodium_crypto_sign_publickey($pair));
        $secret = base64_encode(sodium_crypto_sign_secretkey($pair));
        $other = base64_encode(sodium_crypto_sign_publickey(sodium_crypto_sign_keypair()));
        $b64 = fn (string $raw) => rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
        $claims = fn (array $over = []) => array_replace(['iss' => 'fateh-test', 'aud' => 'lps-app', 'sub' => '7', 'iat' => time(), 'exp' => time() + 60, 'jti' => 'jti-1', 'email' => 'a@example.com'], $over);
        $verify = fn (string $token, ?array $keys = null, string $iss = 'fateh-test', string $aud = 'lps-app') => SsoTokenService::verify($token, $keys ?? [$public], $iss, $aud);
        $signed = fn (array $header, array $payload) => ($data = $b64(json_encode($header)) . '.' . $b64(json_encode($payload))) . '.' . $b64(sodium_crypto_sign_detached($data, base64_decode($secret)));

        $fixed = $claims();
        $token = SsoTokenService::sign($fixed, $secret);
        $this->assertSame($fixed, $verify($token));
        $this->assertSame($fixed, $verify($token, [$other, $public]));

        [$head, $payload, $signature] = explode('.', $token);
        $this->assertNull($verify($head . '.' . $b64(json_encode($claims(['email' => 'evil@example.com']))) . '.' . $signature));
        $this->assertNull($verify($head . '.' . $payload . '.' . substr($signature, 0, -2) . (str_ends_with($signature, 'AA') ? 'BB' : 'AA')));
        $this->assertNull($verify($token, [$other]));
        $this->assertNull($verify($token, []));
        $this->assertNull($verify('not-a-token'));
        $this->assertNull($verify($head . '.' . $payload));

        $this->assertNull($verify($token, null, 'someone-else'));
        $this->assertNull($verify($token, null, 'fateh-test', 'other-app'));

        $this->assertNotNull($verify(SsoTokenService::sign($claims(['iat' => time() - 70, 'exp' => time() - 10]), $secret)));
        $this->assertNull($verify(SsoTokenService::sign($claims(['iat' => time() - 200, 'exp' => time() - 140]), $secret)));
        $this->assertNull($verify(SsoTokenService::sign($claims(['iat' => time() + 100, 'exp' => time() + 160]), $secret)));
        $this->assertNull($verify(SsoTokenService::sign($claims(['exp' => time() + 61]), $secret)));
        $this->assertNull($verify(SsoTokenService::sign($claims(['iat' => '1', 'exp' => '2']), $secret)));
        $this->assertNull($verify(SsoTokenService::sign($claims(['iat' => time() - 5, 'exp' => time() - 10]), $secret)));

        $this->assertNotNull($verify($signed(['alg' => 'EdDSA', 'typ' => 'JWT'], $claims())));
        $this->assertNull($verify($signed(['alg' => 'none', 'typ' => 'JWT'], $claims())));
        $this->assertNull($verify($signed(['alg' => 'HS256', 'typ' => 'JWT'], $claims())));
        $this->assertNull($verify($b64(json_encode(['alg' => 'none'])) . '.' . $b64(json_encode($claims())) . '.'));
    }

    public function test_go_renders_the_handoff_for_an_sso_sibling_with_headers_and_the_signed_assertion(): void
    {
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept, ['email' => ' Mixed.Case@Example.COM ']);
        $row = $this->linkRow($dept, $owner, ['title' => 'LP-go-sso', 'audience' => [$viewer->id]]);
        $root = $this->linkRow($dept, $owner, ['title' => 'LP-go-sso-root', 'audience' => [$viewer->id], 'settings' => ['site' => 'lps', 'path' => '']]);
        Log::spy();
        $this->withoutVite()->actingAs($viewer);

        $response = $this->get(route('launchpad.go', $row->id))->assertOk();
        $assertion = $this->goAssertion($response);
        $claims = SsoTokenService::verify($assertion, [$this->ssoPublic], 'fateh-test', 'lps-app');

        $this->assertNotSame('', $assertion);
        $this->assertNotNull($claims);
        $this->assertSame('mixed.case@example.com', $claims['email']);
        $this->assertSame('lps-app', $claims['aud']);
        $this->assertSame((string) $viewer->id, $claims['sub']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $claims['jti']);
        $this->assertSame(60, $claims['exp'] - $claims['iat']);
        $this->assertArrayNotHasKey('phone', $claims);

        $this->assertStringContainsString('action="https://lp-sibling.example.com/sso/receive"', $response->getContent());
        $this->assertStringContainsString('name="next" value="/reports/q1"', $response->getContent());
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertSame('strict-origin', $response->headers->get('Referrer-Policy'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertStringContainsString('form-action https://lp-sibling.example.com;', (string) $response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("frame-ancestors 'self'", (string) $response->headers->get('Content-Security-Policy'));
        $this->assertContains($viewer->id, Launchpad::query()->whereKey($row->id)->firstOrFail()->seen_by);

        Log::shouldHaveReceived('info')
            ->withArgs(fn ($message, $context = []) => $message === 'launchpad sso' && !str_contains(json_encode($context), $assertion))
            ->once();

        $rootResponse = $this->get(route('launchpad.go', $root->id))->assertOk();
        $this->assertStringNotContainsString('name="next"', $rootResponse->getContent());
        $this->assertNotSame($assertion, $this->goAssertion($rootResponse));
    }

    public function test_go_falls_back_to_the_plain_redirect_when_sso_is_off_globally_for_that_sibling_or_without_a_valid_key(): void
    {
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $row = $this->linkRow($dept, $owner, ['title' => 'LP-go-plain', 'audience' => [$viewer->id]]);
        $url = route('launchpad.go', $row->id);
        $expected = 'https://lp-sibling.example.com/reports/q1';
        $off = config('app.off');
        $this->withoutVite()->actingAs($viewer);

        $this->get($url)->assertOk();

        config(['app.off' => ['launchpad:sso']]);
        $this->get($url)->assertRedirect($expected);
        config(['app.off' => $off]);

        $this->get($url)->assertOk();

        $this->fakeSiblings(false);
        $this->get($url)->assertRedirect($expected);

        $this->fakeSiblings();
        config(['app.siblings.0.sso.receiver' => '']);
        $this->get($url)->assertRedirect($expected);

        foreach ([['launchpad.sso.issuer', ' '], ['launchpad.sso.secret_key', base64_encode('short')], ['launchpad.sso.secret_key', 'not base64 !!'], ['launchpad.sso.secret_key', null]] as [$key, $value]) {
            $this->fakeSiblings();
            config([$key => $value]);
            $this->get($url)->assertRedirect($expected);
        }
    }

    public function test_go_rechecks_access_and_returns_the_empty_404_for_revoked_inactive_or_unknown_site(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $row = $this->linkRow($dept, $owner, ['title' => 'LP-go-recheck', 'audience' => [$viewer->id]]);
        $ghost = $this->linkRow($dept, $owner, ['title' => 'LP-go-recheck-ghost', 'audience' => [$viewer->id], 'settings' => ['site' => 'ghost', 'path' => 'x']]);
        $badPath = $this->linkRow($dept, $owner, ['title' => 'LP-go-recheck-path', 'audience' => [$viewer->id], 'settings' => ['site' => 'lps', 'path' => '../secret']]);
        $static = $this->fileRow($dept, $owner, ['title' => 'LP-go-recheck-static', 'audience' => [$viewer->id]]);
        $this->withoutVite()->actingAs($viewer);

        $this->get(route('launchpad.go', $row->id))->assertOk();

        foreach ([$ghost, $badPath, $static] as $blocked) {
            $this->assertEmpty404($this->get(route('launchpad.go', $blocked->id)));
        }
        $this->assertEmpty404($this->get(route('launchpad.go', Launchpad::query()->max('id') + 1000)));

        $row->update(['active' => false]);
        $this->assertEmpty404($this->get(route('launchpad.go', $row->id)));
        $row->update(['active' => true]);
        $this->get(route('launchpad.go', $row->id))->assertOk();

        $row->update(['audience' => []]);
        $response = $this->get(route('launchpad.go', $row->id));
        $this->assertEmpty404($response);
        $this->assertStringNotContainsString('assertion', $response->getContent());
    }

    public function test_go_shows_the_notice_page_instead_of_an_assertion_for_an_unverified_email(): void
    {
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept, ['email_verified_at' => null]);
        $row = $this->linkRow($dept, $owner, ['title' => 'LP-go-unverified', 'audience' => [$viewer->id]]);
        $this->withoutVite()->actingAs($viewer);

        $response = $this->get(route('launchpad.go', $row->id))->assertOk();
        $html = (string) $response->getContent();

        $this->assertStringContainsString('ورود خودکار برای ایمیل شما فعال نیست؛ ایمیل شما هنوز توسط مدیر تأیید نشده است. می‌توانید سامانه را باز کنید و خودتان وارد شوید.', $html);
        $this->assertStringContainsString('href="https://lp-sibling.example.com/reports/q1"', $html);
        $this->assertStringContainsString('باز کردن سامانه', $html);
        $this->assertStringNotContainsString('<form', $html);
        $this->assertStringNotContainsString('assertion', $html);
        $this->assertStringNotContainsString('http-equiv="refresh"', $html);
        $this->assertSame("default-src 'none'; style-src 'self'; font-src 'self'; base-uri 'none'; frame-ancestors 'self'", $response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('strict-origin', $response->headers->get('Referrer-Policy'));

        config(['app.off' => ['launchpad:sso']]);
        $plain = $this->get(route('launchpad.go', $row->id))->assertRedirect('https://lp-sibling.example.com/reports/q1');
        $this->assertStringNotContainsString('assertion', (string) $plain->headers->get('Location'));
    }

    public function test_go_never_hands_off_to_a_receiver_that_is_not_https_on_the_sibling_host(): void
    {
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $row = $this->linkRow($dept, $owner, ['title' => 'LP-go-bad-receiver', 'audience' => [$viewer->id]]);
        $this->withoutVite()->actingAs($viewer);

        foreach (['not a url', 'http://lp-sibling.example.com/sso/receive', 'https://evil.example.com/sso/receive', 'https://lp-sibling.example.com:8443/sso/receive'] as $receiver) {
            config(['app.siblings.0.sso.receiver' => $receiver]);

            $response = $this->get(route('launchpad.go', $row->id))->assertStatus(302);

            $this->assertStringStartsWith('https://lp-sibling.example.com/', (string) $response->headers->get('Location'), $receiver);
            $this->assertSame([], Launchpad::ssoSites(), $receiver);
        }
    }

    public function test_a_profile_email_change_clears_verification_and_the_same_email_keeps_it(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        app(UpdateUserProfileInformation::class)->update($user, ['name' => 'Same Mail', 'email' => $user->email]);
        $this->assertNotNull($user->fresh()->email_verified_at);

        app(UpdateUserProfileInformation::class)->update($user, ['name' => 'New Mail', 'email' => 'lp-new-' . Str::lower(Str::random(8)) . '@example.com']);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_link_form_lists_and_accepts_only_sso_enabled_siblings(): void
    {
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $sso = ['on' => true, 'receiver' => 'https://lp-sibling.example.com/sso/receive', 'aud' => 'lps-app'];
        config(['app.siblings' => [
            ['name' => 'lps', 'title' => 'LP Sibling', 'url' => 'https://lp-sibling.example.com/', 'sso' => $sso],
            ['name' => 'plain', 'title' => 'Plain Sibling', 'url' => 'https://plain.example.com/'],
            ['name' => 'dark', 'title' => 'Dark Sibling', 'url' => 'https://dark.example.com/', 'sso' => ['on' => false] + $sso],
            ['name' => 'half', 'title' => 'Half Sibling', 'url' => 'https://half.example.com/', 'sso' => ['receiver' => '', 'aud' => 'half-app', 'on' => true]],
        ]]);

        Livewire::actingAs($owner)->test(Main::class)
            ->call('switchTab', 'create')
            ->assertSeeHtml('wire:key="launchpad-kind-switch"')
            ->set('form.kind', 'link')
            ->assertSeeHtml('<option value="lps">LP Sibling</option>')
            ->assertDontSeeHtml('value="plain"')
            ->assertDontSeeHtml('value="dark"')
            ->assertDontSeeHtml('value="half"');

        foreach (['plain', 'dark', 'half'] as $site) {
            $this->saveLink($owner, 'LP-site-' . $site, $site)->assertHasErrors('form.site');
            $this->assertDatabaseMissing('launchpads', ['title' => 'LP-site-' . $site]);
        }

        $this->saveLink($owner, 'LP-site-lps', 'lps')->assertHasNoErrors();
        $this->assertDatabaseHas('launchpads', ['title' => 'LP-site-lps', 'kind' => 'link']);

        $this->fakeSiblings(false);
        Livewire::actingAs($owner)->test(Main::class)
            ->call('switchTab', 'create')
            ->assertDontSeeHtml('launchpad-kind-switch');
    }

    public function test_embed_pane_of_a_link_loads_the_go_route(): void
    {
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $row = $this->linkRow($dept, $owner, ['title' => 'LP-embed-link', 'audience' => [$viewer->id]]);
        $params = ['embed' => 1, 'a' => $row->id];

        Livewire::withQueryParams($params)->actingAs($viewer)->test(Main::class)
            ->assertSet('openId', $row->id)
            ->assertSet('viewToken', null)
            ->assertSet('unavailable', null)
            ->assertSeeHtml('wire:key="launchpad-embed-link"')
            ->assertSeeHtml('src="' . route('launchpad.go', $row->id) . '"');

    }

    public function test_embed_pane_of_a_link_shows_the_empty_state_once_access_is_gone(): void
    {
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $row = $this->linkRow($dept, $owner, ['title' => 'LP-embed-link', 'audience' => []]);

        Livewire::withQueryParams(['embed' => 1, 'a' => $row->id])->actingAs($viewer)->test(Main::class)
            ->assertSet('openId', null)
            ->assertSet('unavailable', 'این برنامه در دسترس نیست.')
            ->assertSeeHtml('wire:key="launchpad-unavailable"')
            ->assertDontSeeHtml('launchpad-frame');
    }

    public function test_embed_pane_of_a_link_shows_the_empty_state_once_sso_is_gone(): void
    {
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $row = $this->linkRow($dept, $owner, ['title' => 'LP-embed-link', 'audience' => [$viewer->id]]);
        $params = ['embed' => 1, 'a' => $row->id];
        $this->fakeSiblings(false);
        Livewire::withQueryParams($params)->actingAs($viewer)->test(Main::class)
            ->assertSet('openId', null)
            ->assertSet('viewToken', null)
            ->assertSet('unavailable', 'این برنامه در دسترس نیست.')
            ->assertSeeHtml('wire:key="launchpad-unavailable"')
            ->assertDontSeeHtml('launchpad-frame');
    }

    public function test_workspace_options_keep_sso_links_and_drop_non_sso_links(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $static = $this->fileRow($dept, $owner, ['title' => 'LP-pick-static']);
        $link = $this->linkRow($dept, $owner, ['title' => 'LP-pick-sso']);
        $ghost = $this->linkRow($dept, $owner, ['title' => 'LP-pick-ghost', 'settings' => ['site' => 'ghost', 'path' => '']]);
        $owner->setExtraValue('preferences.' . Launchpad::OPTIONS_PREFERENCE, [$static->id, $link->id, $ghost->id]);
        $owner->save();

        $listed = $this->pickerSources($owner->fresh());
        $this->assertSame(route('launchpad', ['a' => $link->id, 'embed' => 1]), $listed['LP-pick-sso']);
        $this->assertArrayHasKey('LP-pick-static', $listed);
        $this->assertArrayNotHasKey('LP-pick-ghost', $listed);

        $this->fakeSiblings(false);
        $listed = $this->pickerSources($owner->fresh());
        $this->assertArrayHasKey('LP-pick-static', $listed);
        $this->assertArrayNotHasKey('LP-pick-sso', $listed);
    }

    public function test_assertion_rejects_malformed_tokens_and_forged_structures(): void
    {
        [$public, $secret] = $this->ssoKeys();
        $claims = json_encode($this->ssoClaims());
        $header = '{"alg":"EdDSA","typ":"JWT"}';
        $valid = SsoTokenService::sign($this->ssoClaims(), $secret);
        [$head, $payload] = explode('.', $valid);
        $data = $head . '.' . $payload;
        $signature = sodium_crypto_sign_detached($data, base64_decode($secret));

        $this->assertNotNull($this->ssoVerify($valid, [$public]));

        $cases = [
            'empty' => '',
            'one part' => $head,
            'two parts' => $data,
            'four parts' => $valid . '.extra',
            'dots only' => '..',
            'non-base64 segments' => '!!!.@@@.###',
            'non-json header' => $this->ssoRawToken('not json', $claims, $secret),
            'header is a string' => $this->ssoRawToken('"EdDSA"', $claims, $secret),
            'header is a list' => $this->ssoRawToken('["EdDSA"]', $claims, $secret),
            'lowercase alg' => $this->ssoRawToken('{"alg":"eddsa"}', $claims, $secret),
            'non-json payload' => $this->ssoRawToken($header, 'not json', $secret),
            'payload is a string' => $this->ssoRawToken($header, '"text"', $secret),
            'payload is a number' => $this->ssoRawToken($header, '123', $secret),
            'payload is null' => $this->ssoRawToken($header, 'null', $secret),
            'payload is an empty list' => $this->ssoRawToken($header, '[]', $secret),
            'signature of 63 bytes' => $data . '.' . $this->ssoEncode(substr($signature, 0, 63)),
            'signature of 65 bytes' => $data . '.' . $this->ssoEncode($signature . 'x'),
            'empty signature' => $data . '.',
        ];

        foreach ($cases as $label => $token) {
            $this->assertNull($this->ssoVerify($token, [$public]), $label);
        }
    }

    public function test_assertion_requires_integer_time_claims_and_string_identity_claims(): void
    {
        [$public, $secret] = $this->ssoKeys();
        $now = time();
        $header = '{"alg":"EdDSA","typ":"JWT"}';
        $forged = fn (array $claims) => $this->ssoRawToken($header, json_encode($claims), $secret);

        $this->assertNotNull($this->ssoVerify($forged($this->ssoClaims()), [$public]));

        $wrongTypes = [
            'float iat' => ['iat' => $now + 0.5],
            'float exp' => ['exp' => $now + 60.5],
            'null iat' => ['iat' => null],
            'bool exp' => ['exp' => true],
            'array iat' => ['iat' => [$now]],
            'numeric string exp' => ['exp' => (string) ($now + 60)],
            'integer iss' => ['iss' => 123],
            'array aud' => ['aud' => ['lps-app']],
        ];

        foreach ($wrongTypes as $label => $override) {
            $this->assertNull($this->ssoVerify($forged($this->ssoClaims($override)), [$public]), $label);
        }

        foreach (['iss', 'aud', 'iat', 'exp'] as $missing) {
            $this->assertNull($this->ssoVerify($forged(array_diff_key($this->ssoClaims(), [$missing => true])), [$public]), 'missing ' . $missing);
        }
    }

    public function test_assertion_clock_skew_and_lifetime_have_exact_thirty_and_sixty_second_edges(): void
    {
        [$public, $secret] = $this->ssoKeys();
        $now = $this->atSecondStart();

        $accepted = [
            'iat exactly 30 seconds ahead' => ['iat' => $now + 30, 'exp' => $now + 90],
            'exp exactly 30 seconds ago' => ['iat' => $now - 40, 'exp' => $now - 30],
            'lifetime exactly 60 seconds' => ['iat' => $now, 'exp' => $now + 60],
        ];
        $rejected = [
            'iat 31 seconds ahead' => ['iat' => $now + 31, 'exp' => $now + 91],
            'exp 31 seconds ago' => ['iat' => $now - 41, 'exp' => $now - 31],
            'lifetime 61 seconds' => ['iat' => $now, 'exp' => $now + 61],
            'exp before iat' => ['iat' => $now, 'exp' => $now - 1],
        ];

        foreach ($accepted as $label => $times) {
            $this->assertNotNull($this->ssoVerify(SsoTokenService::sign($this->ssoClaims($times), $secret), [$public]), $label);
        }

        foreach ($rejected as $label => $times) {
            $this->assertNull($this->ssoVerify(SsoTokenService::sign($this->ssoClaims($times), $secret), [$public]), $label);
        }
    }

    public function test_assertion_ignores_listed_keys_that_are_not_strings_or_not_thirty_two_bytes(): void
    {
        [$public, $secret] = $this->ssoKeys();
        [$other] = $this->ssoKeys();
        $token = SsoTokenService::sign($this->ssoClaims(), $secret);
        $raw = base64_decode($public);
        $junk = [null, 123, true, 1.5, '', '!!not base64!!', base64_encode('short'), base64_encode(substr($raw, 0, 31)), base64_encode($raw . 'x'), $secret];

        $this->assertNotNull($this->ssoVerify($token, [...$junk, $public]));
        $this->assertNotNull($this->ssoVerify($token, [$other, ...$junk, $public]));
        $this->assertNull($this->ssoVerify($token, $junk));
        $this->assertNull($this->ssoVerify($token, [$other, ...$junk]));
    }

    public function test_sso_sites_is_empty_unless_the_switch_the_issuer_and_a_valid_secret_key_are_present(): void
    {
        $off = config('app.off');
        $public = $this->ssoPublic;

        $this->assertSame(['lps'], array_keys(Launchpad::ssoSites()));

        config(['app.off' => ['launchpad:sso']]);
        $this->assertSame([], Launchpad::ssoSites());
        config(['app.off' => $off]);
        $this->assertSame(['lps'], array_keys(Launchpad::ssoSites()));

        $broken = [
            ['launchpad.sso.issuer', ''],
            ['launchpad.sso.issuer', null],
            ['launchpad.sso.issuer', '   '],
            ['launchpad.sso.secret_key', null],
            ['launchpad.sso.secret_key', ''],
            ['launchpad.sso.secret_key', 'not base64 !!'],
            ['launchpad.sso.secret_key', base64_encode('short')],
            ['launchpad.sso.secret_key', $public],
            ['launchpad.sso.secret_key', base64_encode(str_repeat('a', 65))],
        ];

        foreach ($broken as [$key, $value]) {
            $this->fakeSiblings();
            config([$key => $value]);
            $this->assertSame([], Launchpad::ssoSites(), $key . ' = ' . var_export($value, true));
        }
    }

    public function test_sso_sites_keeps_only_strictly_enabled_siblings_with_receiver_and_audience_keyed_by_name(): void
    {
        $sso = fn (string $host, array $overrides = []) => array_replace(['on' => true, 'receiver' => "https://{$host}/sso/receive", 'aud' => 'x-app'], $overrides);
        config(['app.siblings' => [
            ['name' => 'good', 'title' => 'Good', 'url' => 'https://good.example.com/', 'sso' => $sso('good.example.com')],
            ['name' => 'second', 'title' => 'Second', 'url' => 'https://second.example.com/', 'sso' => $sso('second.example.com', ['aud' => 'second-app'])],
            ['name' => 'ported', 'title' => 'Ported', 'url' => 'https://ported.example.com:8443/', 'sso' => $sso('ported.example.com:8443')],
            ['name' => 'nosso', 'title' => 'No SSO', 'url' => 'https://nosso.example.com/'],
            ['name' => 'off', 'title' => 'Off', 'url' => 'https://off.example.com/', 'sso' => $sso('off.example.com', ['on' => false])],
            ['name' => 'one', 'title' => 'One', 'url' => 'https://one.example.com/', 'sso' => $sso('one.example.com', ['on' => 1])],
            ['name' => 'text', 'title' => 'Text', 'url' => 'https://text.example.com/', 'sso' => $sso('text.example.com', ['on' => 'true'])],
            ['name' => 'noreceiver', 'title' => 'No receiver', 'url' => 'https://nr.example.com/', 'sso' => $sso('nr.example.com', ['receiver' => ''])],
            ['name' => 'nokey', 'title' => 'No receiver key', 'url' => 'https://nk.example.com/', 'sso' => ['on' => true, 'aud' => 'x-app']],
            ['name' => 'noaud', 'title' => 'No aud', 'url' => 'https://na.example.com/', 'sso' => $sso('na.example.com', ['aud' => ''])],
            ['name' => 'nullaud', 'title' => 'Null aud', 'url' => 'https://nn.example.com/', 'sso' => $sso('nn.example.com', ['aud' => null])],
            ['name' => 'plainhttp', 'title' => 'Plain http', 'url' => 'https://ph.example.com/', 'sso' => $sso('ph.example.com', ['receiver' => 'http://ph.example.com/sso/receive'])],
            ['name' => 'otherhost', 'title' => 'Other host', 'url' => 'https://oh.example.com/', 'sso' => $sso('evil.example.com')],
            ['name' => 'otherport', 'title' => 'Other port', 'url' => 'https://op.example.com/', 'sso' => $sso('op.example.com:8443')],
            ['name' => 'nohost', 'title' => 'No host', 'url' => 'https://nh.example.com/', 'sso' => $sso('nh.example.com', ['receiver' => 'not a url'])],
        ]]);

        $sites = Launchpad::ssoSites();

        $this->assertSame(['good', 'second', 'ported'], array_keys($sites));
        $this->assertSame('Good', $sites['good']['title']);
        $this->assertSame('second-app', $sites['second']['sso']['aud']);
        $this->assertSame('https://second.example.com/sso/receive', $sites['second']['sso']['receiver']);
        $this->assertSame('https://ported.example.com:8443/sso/receive', $sites['ported']['sso']['receiver']);
    }

    public function test_go_hands_off_to_the_audience_owner_and_elevated_roles_and_hides_from_strangers(): void
    {
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $stranger = $this->deptUser($dept);
        $admin = $this->deptUser($this->dept('LPB'), ['role' => 'admin']);
        $row = $this->linkRow($dept, $owner, ['title' => 'LP-go-matrix', 'audience' => [$viewer->id]]);
        $this->withoutVite();

        foreach ([$viewer, $owner, $admin] as $actor) {
            $response = $this->actingAs($actor)->get(route('launchpad.go', $row->id))->assertOk();
            $claims = SsoTokenService::verify($this->goAssertion($response), [$this->ssoPublic], 'fateh-test', 'lps-app');

            $this->assertNotNull($claims);
            $this->assertSame((string) $actor->id, $claims['sub']);
        }

        $response = $this->actingAs($stranger)->get(route('launchpad.go', $row->id));
        $this->assertEmpty404($response);
        $this->assertStringNotContainsString('assertion', (string) $response->getContent());
    }

    public function test_go_is_a_404_for_a_past_expiry_a_deleted_row_an_unknown_id_and_malformed_ids(): void
    {
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $expired = $this->linkRow($dept, $owner, ['title' => 'LP-go-expired', 'audience' => [$viewer->id], 'expire_at' => today()->subDay()->toDateString()]);
        $deleted = $this->linkRow($dept, $owner, ['title' => 'LP-go-deleted', 'audience' => [$viewer->id]]);
        $deleted->delete();
        $this->withoutVite();

        foreach ([$viewer, $owner] as $actor) {
            $this->actingAs($actor);
            $this->assertEmpty404($this->get(route('launchpad.go', $expired->id)));
            $this->assertEmpty404($this->get(route('launchpad.go', $deleted->id)));
            $this->assertEmpty404($this->get(route('launchpad.go', 9999999999)));
        }

        foreach (['abc', '0', '01', '1x', '-1', '12345678901'] as $malformed) {
            $this->get('/launchpad/' . $malformed . '/go')->assertNotFound();
        }

        $this->assertNull(Launchpad::query()->whereKey($expired->id)->value('seen_by'));
    }

    public function test_go_without_an_email_shows_the_notice_page_with_no_assertion(): void
    {
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept, ['email' => ' ']);
        $row = $this->linkRow($dept, $owner, ['title' => 'LP-go-no-email', 'audience' => [$viewer->id]]);
        $this->withoutVite()->actingAs($viewer);

        $response = $this->get(route('launchpad.go', $row->id))->assertOk();

        $this->assertStringContainsString('href="https://lp-sibling.example.com/reports/q1"', (string) $response->getContent());
        $this->assertStringNotContainsString('assertion', (string) $response->getContent());
    }

    public function test_go_mints_a_different_verifiable_assertion_on_every_request(): void
    {
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $row = $this->linkRow($dept, $owner, ['title' => 'LP-go-fresh', 'audience' => [$viewer->id]]);
        $this->withoutVite()->actingAs($viewer);

        $first = $this->goAssertion($this->get(route('launchpad.go', $row->id))->assertOk());
        $second = $this->goAssertion($this->get(route('launchpad.go', $row->id))->assertOk());
        $one = SsoTokenService::verify($first, [$this->ssoPublic], 'fateh-test', 'lps-app');
        $two = SsoTokenService::verify($second, [$this->ssoPublic], 'fateh-test', 'lps-app');

        $this->assertNotNull($one);
        $this->assertNotNull($two);
        $this->assertNotSame($first, $second);
        $this->assertNotSame($one['jti'], $two['jti']);
    }

    public function test_go_handoff_page_is_a_scriptless_post_form_with_a_refresh_and_a_visible_submit_button(): void
    {
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $row = $this->linkRow($dept, $owner, ['title' => 'LP-go-page', 'audience' => [$viewer->id]]);
        $this->withoutVite()->actingAs($viewer);

        $html = (string) $this->get(route('launchpad.go', $row->id))->assertOk()->getContent();

        $this->assertStringContainsString('<meta http-equiv="refresh" content="50">', $html);
        $this->assertSame(1, substr_count($html, '<form'));
        $this->assertStringContainsString('<form method="POST" action="https://lp-sibling.example.com/sso/receive"', $html);
        $this->assertStringContainsString('<input type="hidden" name="assertion" value="', $html);
        $this->assertStringContainsString('<button', $html);
        $this->assertStringContainsString('type="submit"', $html);
        $this->assertStringContainsString('ادامه و ورود به سامانه', $html);
        $this->assertStringNotContainsString('<script', $html);
    }

    public function test_go_form_action_csp_carries_the_receiver_origin_including_a_non_default_port(): void
    {
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $row = $this->linkRow($dept, $owner, ['title' => 'LP-go-port', 'audience' => [$viewer->id]]);
        config(['app.siblings.0.url' => 'https://lp-sibling.example.com:8443/', 'app.siblings.0.sso.receiver' => 'https://lp-sibling.example.com:8443/sso/receive']);
        $this->withoutVite()->actingAs($viewer);

        $response = $this->get(route('launchpad.go', $row->id))->assertOk();
        $csp = (string) $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('form-action https://lp-sibling.example.com:8443;', $csp);
        $this->assertStringNotContainsString('/sso/receive', $csp);
        $this->assertStringContainsString('action="https://lp-sibling.example.com:8443/sso/receive"', (string) $response->getContent());
    }

    public function test_go_keeps_query_and_hash_characters_of_the_path_in_next_and_in_the_plain_redirect(): void
    {
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $row = $this->linkRow($dept, $owner, ['title' => 'LP-go-query', 'audience' => [$viewer->id], 'settings' => ['site' => 'lps', 'path' => 'reports/q1?tab=2&x=y#top']]);
        $this->withoutVite()->actingAs($viewer);

        $html = (string) $this->get(route('launchpad.go', $row->id))->assertOk()->getContent();
        $this->assertStringContainsString('name="next" value="/reports/q1?tab=2&amp;x=y#top"', $html);

        $this->fakeSiblings(false);
        $this->get(route('launchpad.go', $row->id))->assertRedirect('https://lp-sibling.example.com/reports/q1?tab=2&x=y#top');
    }

    public function test_go_marks_the_row_seen_exactly_once_and_clears_the_unseen_dot(): void
    {
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $badge = app(UnseenLaunchpads::class);
        $row = $this->linkRow($dept, $owner, ['title' => 'LP-go-seen-once', 'audience' => [$viewer->id]]);
        $this->withoutVite()->actingAs($viewer);

        $this->assertTrue($badge->isActive());

        $this->get(route('launchpad.go', $row->id))->assertOk();
        $this->get(route('launchpad.go', $row->id))->assertOk();

        $this->assertSame([$viewer->id], Launchpad::query()->whereKey($row->id)->firstOrFail()->seen_by);
        $this->assertFalse($badge->isActive());

        $this->actingAs($owner)->get(route('launchpad.go', $row->id))->assertOk();
        $this->assertSame([$viewer->id], Launchpad::query()->whereKey($row->id)->firstOrFail()->seen_by);
    }

    public function test_go_logs_only_the_user_the_peer_and_the_jti(): void
    {
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $row = $this->linkRow($dept, $owner, ['title' => 'LP-go-log', 'audience' => [$viewer->id]]);
        Log::spy();
        $this->withoutVite()->actingAs($viewer);

        $claims = SsoTokenService::verify($this->goAssertion($this->get(route('launchpad.go', $row->id))->assertOk()), [$this->ssoPublic], 'fateh-test', 'lps-app');

        Log::shouldHaveReceived('info')
            ->withArgs(fn ($message, $context = []) => $message === 'launchpad sso' && $context === ['user' => $viewer->id, 'peer' => 'lps', 'jti' => $claims['jti']])
            ->once();
    }

    public function test_go_is_throttled_to_thirty_requests_a_minute(): void
    {
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $row = $this->linkRow($dept, $owner, ['title' => 'LP-go-throttle', 'audience' => [$viewer->id]]);
        $this->fakeSiblings(false);
        $this->actingAs($viewer);

        for ($i = 0; $i < 30; $i++) {
            $this->get(route('launchpad.go', $row->id))->assertRedirect();
        }

        $this->get(route('launchpad.go', $row->id))->assertStatus(429);
    }

    public function test_link_save_fails_on_the_site_when_sso_is_off_or_unconfigured(): void
    {
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $off = config('app.off');
        $states = [
            'tenant off' => fn () => config(['app.off' => ['launchpad:sso']]),
            'blank issuer' => fn () => config(['launchpad.sso.issuer' => '']),
            'bad secret key' => fn () => config(['launchpad.sso.secret_key' => base64_encode('short')]),
            'null secret key' => fn () => config(['launchpad.sso.secret_key' => null]),
        ];

        foreach ($states as $label => $break) {
            $this->fakeSiblings();
            config(['app.off' => $off]);
            $break();
            $title = 'LP-link-sso-off-' . Str::slug($label);

            $this->saveLink($owner, $title, 'lps', 'reports')->assertHasErrors('form.site');
            $this->assertDatabaseMissing('launchpads', ['title' => $title]);
        }

        $this->fakeSiblings();
        config(['app.off' => $off]);
        $this->saveLink($owner, 'LP-link-sso-back', 'lps', 'reports/q1?tab=2#top')->assertHasNoErrors();
        $this->assertEquals(['site' => 'lps', 'path' => 'reports/q1?tab=2#top'], Launchpad::query()->where('title', 'LP-link-sso-back')->firstOrFail()->settings);
    }

    public function test_the_department_cap_counts_link_rows_for_both_kinds_and_still_allows_a_link_replace(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPS');
        $owner = $this->deptUser($dept);
        config(['launchpad.max_per_department' => 3]);

        foreach ([1 => 'lps', 2 => $this->addSibling('lpx'), 3 => $this->addSibling('lpy')] as $i => $rowSite) {
            $this->linkRow($dept, $owner, ['title' => "LP-cap-link-{$i}", 'settings' => ['site' => $rowSite, 'path' => 'reports/q1']]);
        }

        $this->assertSame(3, app(LaunchpadPresenter::class)->capMeter($dept)['used']);

        $this->saveLink($owner, 'LP-cap-link-extra')->assertHasErrors('form.site');
        $this->saveLaunch($owner, 'LP-cap-static-extra')->assertHasErrors('form.files');
        $this->assertDatabaseMissing('launchpads', ['title' => 'LP-cap-link-extra']);
        $this->assertDatabaseMissing('launchpads', ['title' => 'LP-cap-static-extra']);

        $this->saveLink($owner, 'LP-cap-link-1', 'lps', 'new/path')
            ->assertDispatched('open-confirmation')->call('confirmReplace')->assertHasNoErrors();
        $this->assertSame(3, Launchpad::query()->where('department_id', $dept)->count());
    }

    public function test_a_second_link_for_the_same_site_is_refused_with_the_detailed_message_for_a_manager(): void
    {
        $deptA = $this->dept('LPA');
        $deptB = $this->dept('LPB');
        $owner = $this->deptUser($deptA);
        $admin = $this->deptUser($deptB, ['role' => 'admin']);
        $this->linkRow($deptA, $owner, ['title' => 'LP-site-first']);
        $detailed = 'برای این سامانه قبلاً میان‌بر «LP-site-first» در دپارتمان «' . Department::getCachedOptions()[$deptA] . '» ساخته شده است؛ آن را ویرایش یا جایگزین کنید.';

        $this->saveLink($owner, 'LP-site-second', 'lps')->assertDispatched('toast', message: $detailed, type: 'warning');
        $this->assertDatabaseMissing('launchpads', ['title' => 'LP-site-second']);

        $this->saveLink($admin, 'LP-site-third', 'lps')->assertDispatched('toast', message: $detailed, type: 'warning');
        $this->assertDatabaseMissing('launchpads', ['title' => 'LP-site-third']);
        $this->assertSame(1, Launchpad::query()->where('kind', 'link')->where('settings->site', 'lps')->count());
    }

    public function test_a_second_link_for_the_same_site_gets_the_generic_message_without_leaking_the_record(): void
    {
        $deptA = $this->dept('LPA');
        $deptB = $this->dept('LPB');
        $owner = $this->deptUser($deptA);
        $stranger = $this->deptUser($deptB);
        $this->linkRow($deptA, $owner, ['title' => 'LP-site-secret']);
        $generic = 'برای این سامانه قبلاً میان‌بر ساخته شده است. از مدیر بخواهید دسترسی شما را به آن اضافه کند.';

        $this->saveLink($stranger, 'LP-site-other-dept', 'lps')->assertDispatched('toast', message: $generic, type: 'warning');

        $colleague = $this->deptUser($deptA);
        $this->saveLink($colleague, 'LP-site-colleague', 'lps')->assertDispatched('toast', message: $generic, type: 'warning');

        $this->assertDatabaseMissing('launchpads', ['title' => 'LP-site-other-dept']);
        $this->assertDatabaseMissing('launchpads', ['title' => 'LP-site-colleague']);
        $this->assertStringNotContainsString('LP-site-secret', $generic);
        $this->assertSame(1, Launchpad::query()->where('kind', 'link')->where('settings->site', 'lps')->count());
    }

    public function test_the_single_site_record_can_still_be_replaced_and_keeps_one_row(): void
    {
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $admin = $this->deptUser($dept, ['role' => 'admin']);
        $row = $this->linkRow($dept, $owner, ['title' => 'LP-site-single']);

        $this->saveLink($owner, 'LP-site-single', 'lps', 'owner/path')
            ->assertDispatched('open-confirmation')->call('confirmReplace')->assertHasNoErrors();
        $this->saveLink($admin, 'LP-site-single', 'lps', 'admin/path')
            ->assertDispatched('open-confirmation')->call('confirmReplace')->assertHasNoErrors();

        $this->assertSame(1, Launchpad::query()->where('kind', 'link')->where('settings->site', 'lps')->count());
        $this->assertEquals(['site' => 'lps', 'path' => 'admin/path'], Launchpad::query()->whereKey($row->id)->firstOrFail()->settings);
    }

    public function test_a_soft_deleted_link_frees_the_site_and_another_site_is_unaffected(): void
    {
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $old = $this->linkRow($dept, $owner, ['title' => 'LP-site-old']);
        $other = $this->addSibling('lpx');

        $this->saveLink($owner, 'LP-site-other', $other)->assertHasNoErrors();
        $this->assertDatabaseHas('launchpads', ['title' => 'LP-site-other', 'kind' => 'link']);

        $old->delete();
        $this->saveLink($owner, 'LP-site-new', 'lps')->assertHasNoErrors();
        $this->assertDatabaseHas('launchpads', ['title' => 'LP-site-new', 'kind' => 'link']);
        $this->assertSame(1, Launchpad::query()->where('kind', 'link')->where('settings->site', 'lps')->count());
    }

    public function test_a_replace_that_moves_the_link_onto_an_occupied_site_is_refused(): void
    {
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $colleague = $this->deptUser($dept);
        $admin = $this->deptUser($dept, ['role' => 'admin']);
        $second = $this->addSibling('lpx');
        $third = $this->addSibling('lpy');
        $this->linkRow($dept, $owner, ['title' => 'LP-site-held']);
        $mine = $this->linkRow($dept, $colleague, ['title' => 'LP-site-mine', 'settings' => ['site' => $second, 'path' => 'a']]);
        $mineToo = $this->linkRow($dept, $colleague, ['title' => 'LP-site-mine-too', 'settings' => ['site' => $third, 'path' => 'b']]);
        $generic = 'برای این سامانه قبلاً میان‌بر ساخته شده است. از مدیر بخواهید دسترسی شما را به آن اضافه کند.';

        $this->saveLink($colleague, 'LP-site-mine', 'lps', 'a')
            ->assertDispatched('open-confirmation')->call('confirmReplace')
            ->assertDispatched('toast', message: $generic, type: 'warning');
        $this->assertEquals(['site' => $second, 'path' => 'a'], Launchpad::query()->whereKey($mine->id)->firstOrFail()->settings);

        $detailed = 'برای این سامانه قبلاً میان‌بر «LP-site-held» در دپارتمان «' . Department::getCachedOptions()[$dept] . '» ساخته شده است؛ آن را ویرایش یا جایگزین کنید.';
        $this->saveLink($admin, 'LP-site-mine-too', 'lps', 'b')
            ->assertDispatched('open-confirmation')->call('confirmReplace')
            ->assertDispatched('toast', message: $detailed, type: 'warning');
        $this->assertEquals(['site' => $third, 'path' => 'b'], Launchpad::query()->whereKey($mineToo->id)->firstOrFail()->settings);

        $this->saveLink($colleague, 'LP-site-mine', $second, 'c')
            ->assertDispatched('open-confirmation')->call('confirmReplace')->assertHasNoErrors();
        $this->assertEquals(['site' => $second, 'path' => 'c'], Launchpad::query()->whereKey($mine->id)->firstOrFail()->settings);
    }

    public function test_editing_a_link_whose_sibling_turned_sso_off_still_saves(): void
    {
        $dept = $this->dept('LPE');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $row = $this->linkRow($dept, $owner, ['title' => 'LP-edit-link']);
        $this->fakeSiblings(false);
        Livewire::withoutLazyLoading();

        Livewire::actingAs($owner)->test(Main::class)->call('openEdit', $row->id)
            ->assertSet('editOpen', true)
            ->set('editForm.description', 'link text')
            ->set('editForm.audience', [$viewer->id])
            ->call('saveEdit')
            ->assertHasNoErrors()
            ->assertSet('editOpen', false);

        $row->refresh();
        $this->assertSame('link', $row->kind);
        $this->assertSame('link text', $row->description);
        $this->assertSame([$viewer->id], $row->audience);
        $this->assertEquals(['site' => 'lps', 'path' => 'reports/q1'], $row->settings);
    }

    public function test_pinning_an_sso_link_twice_reports_that_it_already_exists(): void
    {
        $dept = $this->dept('LPK');
        $owner = $this->deptUser($dept);
        $link = $this->linkRow($dept, $owner, ['title' => 'LP-pin-twice']);

        Livewire::actingAs($owner)->test(Main::class)
            ->call('addOption', $link->id)
            ->assertDispatched('toast', message: 'برنامه به فضای کاری افزوده شد.', type: 'success')
            ->call('addOption', $link->id)
            ->assertDispatched('toast', message: 'این برنامه در فضای کاری وجود دارد.', type: 'warning');

        $this->assertSame([$link->id], Launchpad::optionIds($owner->fresh()));
    }

    public function test_the_option_cap_prunes_the_oldest_across_a_mix_of_static_and_link_rows(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPK');
        $owner = $this->deptUser($dept);
        config(['launchpad.workspace.options_max' => 3]);
        $rows = [
            $this->fileRow($dept, $owner, ['title' => 'LP-mix-static-1']),
            $this->linkRow($dept, $owner, ['title' => 'LP-mix-link-1']),
            $this->fileRow($dept, $owner, ['title' => 'LP-mix-static-2']),
            $this->linkRow($dept, $owner, ['title' => 'LP-mix-link-2']),
        ];

        $component = Livewire::actingAs($owner)->test(Main::class);
        foreach ($rows as $row) {
            $component->call('addOption', $row->id);
        }

        $this->assertSame([$rows[1]->id, $rows[2]->id, $rows[3]->id], Launchpad::optionIds($owner->fresh()));
    }

    public function test_pinning_an_unavailable_link_gets_the_generic_warning_and_pins_nothing(): void
    {
        $dept = $this->dept('LPK');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $stranger = $this->deptUser($dept);
        $inactive = $this->linkRow($dept, $owner, ['title' => 'LP-pin-inactive', 'active' => false, 'audience' => [$viewer->id]]);
        $expired = $this->linkRow($dept, $owner, ['title' => 'LP-pin-expired', 'audience' => [$viewer->id], 'expire_at' => today()->subDay()->toDateString()]);
        $hidden = $this->linkRow($dept, $owner, ['title' => 'LP-pin-hidden', 'audience' => []]);

        foreach ([[$viewer, $inactive], [$viewer, $expired], [$stranger, $hidden]] as [$actor, $row]) {
            Livewire::actingAs($actor)->test(Main::class)
                ->call('addOption', $row->id)
                ->assertDispatched('toast', message: 'این برنامه در دسترس نیست.', type: 'warning');
        }

        $this->fakeSiblings(false);
        Livewire::actingAs($stranger)->test(Main::class)
            ->call('addOption', $hidden->id)
            ->assertDispatched('toast', message: 'این برنامه در دسترس نیست.', type: 'warning');

        $this->assertSame([], Launchpad::optionIds($viewer->fresh()));
        $this->assertSame([], Launchpad::optionIds($stranger->fresh()));
    }

    public function test_list_row_is_pinnable_for_static_and_sso_links_and_not_for_other_links(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPK');
        $owner = $this->deptUser($dept);
        $static = $this->fileRow($dept, $owner, ['title' => 'LP-pinnable-static']);
        $link = $this->linkRow($dept, $owner, ['title' => 'LP-pinnable-link']);
        $ghost = $this->linkRow($dept, $owner, ['title' => 'LP-pinnable-ghost', 'settings' => ['site' => 'ghost', 'path' => '']]);
        $bare = $this->linkRow($dept, $owner, ['title' => 'LP-pinnable-bare', 'settings' => []]);
        $presenter = app(LaunchpadPresenter::class);
        $sites = Launchpad::ssoSites();

        $this->assertTrue($presenter->listRow($static, $owner, $sites)['pinnable']);
        $this->assertTrue($presenter->listRow($static, $owner)['pinnable']);
        $this->assertTrue($presenter->listRow($link, $owner, $sites)['pinnable']);
        $this->assertFalse($presenter->listRow($link, $owner)['pinnable']);
        $this->assertFalse($presenter->listRow($ghost, $owner, $sites)['pinnable']);
        $this->assertFalse($presenter->listRow($bare, $owner, $sites)['pinnable']);
    }

    public function test_the_list_renders_the_pin_button_for_an_sso_link_and_the_danger_button_for_another_link(): void
    {
        $dept = $this->dept('LPK');
        $owner = $this->deptUser($dept);
        $this->linkRow($dept, $owner, ['title' => 'LP-list-link']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($owner)->test(Main::class)
            ->assertSee('LP-list-link')
            ->assertSeeHtml('wire:key="launchpad-workspace-add"')
            ->assertDontSeeHtml('wire:key="launchpad-workspace-add-link"');

        $this->fakeSiblings(false);
        Livewire::withoutLazyLoading();
        Livewire::actingAs($owner)->test(Main::class)
            ->assertSee('LP-list-link')
            ->assertSeeHtml('wire:key="launchpad-workspace-add-link"')
            ->assertDontSeeHtml('wire:key="launchpad-workspace-add"');
    }

    public function test_embed_pane_of_an_sso_link_carries_one_go_iframe_and_the_two_icon_links(): void
    {
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $row = $this->linkRow($dept, $owner, ['title' => 'LP-embed-bar', 'audience' => [$viewer->id]]);
        $go = route('launchpad.go', $row->id);

        Livewire::withoutLazyLoading();
        $component = Livewire::withQueryParams(['embed' => 1, 'a' => $row->id])->actingAs($viewer)->test(Main::class)
            ->assertSeeHtml('wire:key="launchpad-embed-link"')
            ->assertDontSeeHtml('wire:key="launchpad-unavailable"')
            ->assertSeeHtml('<a href="' . $go . '" target="launchpad-frame" aria-label="ورود دوباره"')
            ->assertSeeHtml('<a href="' . $go . '" target="_blank" rel="noopener" aria-label="باز کردن در پنجره جدید"');

        $html = $component->html();
        $this->assertSame(1, substr_count($html, '<iframe'));
        $this->assertMatchesRegularExpression('/<iframe[^>]*\bname="launchpad-frame"[^>]*\bsrc="' . preg_quote($go, '/') . '"/', $html);
        $this->assertStringNotContainsString('lp-sibling.example.com', $html);
    }

    public function test_embed_pane_of_a_link_is_the_unavailable_state_for_an_inactive_deleted_or_unknown_row(): void
    {
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $inactive = $this->linkRow($dept, $owner, ['title' => 'LP-embed-inactive', 'active' => false, 'audience' => [$viewer->id]]);
        $deleted = $this->linkRow($dept, $owner, ['title' => 'LP-embed-deleted', 'audience' => [$viewer->id]]);
        $deleted->delete();

        foreach ([$inactive->id, $deleted->id, Launchpad::query()->max('id') + 1000] as $id) {
            Livewire::withoutLazyLoading();
            Livewire::withQueryParams(['embed' => 1, 'a' => $id])->actingAs($viewer)->test(Main::class)
                ->assertSet('openId', null)
                ->assertSet('viewToken', null)
                ->assertSet('unavailable', 'این برنامه در دسترس نیست.')
                ->assertNotDispatched('toast')
                ->assertSeeHtml('wire:key="launchpad-unavailable"')
                ->assertDontSeeHtml('wire:key="launchpad-embed-link"')
                ->assertDontSeeHtml('<iframe');
        }

        Livewire::withoutLazyLoading();
        Livewire::withQueryParams(['embed' => 1, 'a' => $inactive->id])->actingAs($owner)->test(Main::class)
            ->assertSet('openId', null)
            ->assertSet('unavailable', 'این برنامه در دسترس نیست.')
            ->assertSeeHtml('wire:key="launchpad-unavailable"')
            ->assertDontSeeHtml('<iframe');
    }

    public function test_open_outside_the_embed_refuses_a_link_row_while_a_static_row_still_opens(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $link = $this->linkRow($dept, $owner, ['title' => 'LP-open-link']);
        $static = $this->fileRow($dept, $owner, ['title' => 'LP-open-static']);
        Livewire::withoutLazyLoading();

        Livewire::actingAs($owner)->test(Main::class)
            ->call('open', $link->id)
            ->assertSet('openId', null)
            ->assertSet('viewToken', null)
            ->assertSet('unavailable', null)
            ->assertDispatched('toast', message: 'این برنامه در دسترس نیست.', type: 'warning')
            ->call('open', $static->id)
            ->assertSet('openId', $static->id)
            ->assertNotSet('viewToken', null);
    }

    public function test_a_failed_profile_update_leaves_the_email_and_its_verification_intact(): void
    {
        $taken = User::factory()->create();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $email = $user->email;
        $name = $user->name;

        foreach ([$taken->email, 'not-an-email', ''] as $input) {
            try {
                app(UpdateUserProfileInformation::class)->update($user, ['name' => 'Changed', 'email' => $input]);
                $this->fail('the update should have been refused for ' . var_export($input, true));
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('email', $e->errors());
            }

            $fresh = $user->fresh();
            $this->assertSame($email, $fresh->email);
            $this->assertSame($name, $fresh->name);
            $this->assertNotNull($fresh->email_verified_at);
        }
    }

    public function test_the_legend_renders_and_explains_the_admin_verified_email(): void
    {
        $html = view('livewire.dashboard.launchpad.legend')->render();

        $this->assertStringContainsString('ایمیل شما باید توسط مدیر', $html);
        $this->assertStringContainsString('ورود دوباره', $html);
    }

    public function test_the_admin_guide_overview_explains_the_sso_shortcut(): void
    {
        $html = view('filament.resources.launchpad.guide.overview')->render();

        $this->assertStringContainsString('ورود خودکار آن', $html);
        $this->assertStringContainsString('کاربر با نشانی ایمیل خود وارد', $html);
    }

    public function test_a_public_program_is_visible_to_any_active_user_through_every_door_while_a_private_one_is_not(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $stranger = $this->deptUser($this->dept('LPB'));
        $inactiveUser = $this->deptUser($dept, ['status' => 'inactive']);
        $private = $this->fileRow($dept, $owner, ['title' => 'LP-pub-private']);
        $public = $this->fileRow($dept, $owner, ['title' => 'LP-pub-open', 'settings' => ['public' => true]]);
        $link = $this->linkRow($dept, $owner, ['title' => 'LP-pub-link', 'settings' => ['site' => 'lps', 'path' => 'a', 'public' => true]]);

        $this->assertTrue($public->isPublic());
        $this->assertFalse($private->isPublic());
        $this->assertTrue(LaunchpadAccessPolicy::canView($public, $stranger));
        $this->assertFalse(LaunchpadAccessPolicy::canView($private, $stranger));
        $this->assertFalse(LaunchpadAccessPolicy::canView($public, $inactiveUser));
        $this->assertFalse(LaunchpadAccessPolicy::canView($public, null));
        $this->assertSame([$public->id, $link->id], Launchpad::query()->visibleTo($stranger)->orderBy('id')->pluck('id')->all());

        $this->assertSame(200, $this->actingAs($stranger)->get(route('launchpad.file', [$public->id, LaunchpadAccessPolicy::token($public, $stranger), 'index.html']))->getStatusCode());
        $this->assertSame(404, $this->get(route('launchpad.file', [$private->id, LaunchpadAccessPolicy::token($private, $stranger), 'index.html']))->getStatusCode());
        $this->withoutVite()->get(route('launchpad.go', $link->id))->assertOk();

        $component = Livewire::actingAs($stranger)->test(Main::class);
        $component->call('open', $public->id)->assertSet('openId', $public->id);
        $component->call('open', $private->id)->assertSet('openId', null);

        $stranger->setExtraValue('preferences.' . Launchpad::OPTIONS_PREFERENCE, [$public->id, $private->id]);
        $stranger->save();
        $listed = $this->pickerSources($stranger->fresh());
        $this->assertArrayHasKey('LP-pub-open', $listed);
        $this->assertArrayNotHasKey('LP-pub-private', $listed);
    }

    public function test_public_programs_stay_hidden_when_inactive_scheduled_or_expired_but_not_from_owner_or_admin(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $admin = $this->deptUser($this->dept('LPB'), ['role' => 'admin']);
        $stranger = $this->deptUser($this->dept('LPB'));
        $rows = [
            $this->fileRow($dept, $owner, ['title' => 'LP-pub-off', 'active' => false, 'settings' => ['public' => true]]),
            $this->fileRow($dept, $owner, ['title' => 'LP-pub-later', 'accessible_from' => today()->addDay()->toDateString(), 'settings' => ['public' => true]]),
            $this->fileRow($dept, $owner, ['title' => 'LP-pub-ended', 'accessible_to' => today()->subDay()->toDateString(), 'settings' => ['public' => true]]),
            $this->fileRow($dept, $owner, ['title' => 'LP-pub-gone', 'expire_at' => today()->subDay()->toDateString(), 'settings' => ['public' => true]]),
        ];

        foreach ($rows as $row) {
            $this->assertFalse(LaunchpadAccessPolicy::canView($row, $stranger), $row->title);
            $this->assertSame(404, $this->actingAs($stranger)->get(route('launchpad.file', [$row->id, LaunchpadAccessPolicy::token($row, $stranger), 'index.html']))->getStatusCode(), $row->title);
            $this->assertTrue(LaunchpadAccessPolicy::canView($row, $owner), $row->title);
            $this->assertTrue(LaunchpadAccessPolicy::canView($row, $admin), $row->title);
        }

        $this->assertSame([], Launchpad::query()->visibleTo($stranger)->pluck('id')->all());
    }

    public function test_a_public_program_never_lights_the_dot_or_grows_seen_by_while_a_private_one_does(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $badge = app(UnseenLaunchpads::class);
        $public = $this->fileRow($dept, $owner, ['title' => 'LP-pub-dot', 'audience' => [$viewer->id], 'settings' => ['public' => true]]);
        $this->actingAs($viewer);

        $this->assertFalse($badge->isActive());
        $this->assertSame([], Launchpad::query()->unseenBy($viewer->id)->pluck('id')->all());

        Livewire::actingAs($viewer)->test(Main::class)->call('open', $public->id)->assertSet('openId', $public->id);
        $this->assertNull(Launchpad::query()->whereKey($public->id)->value('seen_by'));

        $private = $this->fileRow($dept, $owner, ['title' => 'LP-pub-dot-private', 'audience' => [$viewer->id]]);
        $this->assertTrue($badge->isActive());
        Livewire::actingAs($viewer)->test(Main::class)->call('open', $private->id);
        $this->assertSame([$viewer->id], $private->refresh()->seen_by);
        $this->assertFalse($badge->isActive());
    }

    public function test_the_create_form_hides_the_picker_when_public_and_saves_an_empty_audience(): void
    {
        Storage::fake('public');
        Queue::fake();
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept, ['role' => 'admin']);
        $viewer = $this->deptUser($dept);
        Livewire::withoutLazyLoading();

        Livewire::actingAs($owner)->test(Main::class)
            ->call('switchTab', 'create')
            ->assertSeeHtml('wire:key="launchpad-public-switch"')
            ->assertSeeHtml('wire:key="launchpad-audience-block"')
            ->set('form.public', true)
            ->assertDontSeeHtml('wire:key="launchpad-audience-block"')
            ->set('form.title', 'LP-pub-create')
            ->set('form.audience', [$viewer->id])
            ->set('form.files', $this->validFiles())
            ->call('save')
            ->assertHasNoErrors();

        $row = Launchpad::query()->where('title', 'LP-pub-create')->firstOrFail();
        $this->assertTrue($row->isPublic());
        $this->assertSame([], $row->audience);
        $this->assertTrue(LaunchpadAccessPolicy::canView($row, $viewer));
    }

    public function test_the_edit_modal_toggles_public_on_and_off_and_keeps_other_settings(): void
    {
        Storage::fake('public');
        Queue::fake();
        $dept = $this->dept('LPE');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $row = $this->fileRow($dept, $owner, ['title' => 'LP-pub-edit', 'audience' => [$viewer->id], 'settings' => ['entry' => 'index.html', 'files_count' => 2]]);
        Livewire::withoutLazyLoading();

        $component = Livewire::actingAs($owner)->test(Main::class)->call('openEdit', $row->id)
            ->assertSet('editForm.public', false)
            ->assertSeeHtml('wire:key="launchpad-edit-audience-block"')
            ->set('editForm.public', true)
            ->assertDontSeeHtml('wire:key="launchpad-edit-audience-block"')
            ->call('saveEdit')->assertHasNoErrors();

        $row->refresh();
        $this->assertTrue($row->isPublic());
        $this->assertSame([], $row->audience);
        $this->assertSame(2, $row->settings['files_count']);

        $component->call('openEdit', $row->id)->assertSet('editForm.public', true)->set('editForm.public', false)->call('saveEdit')->assertHasNoErrors();

        $row->refresh();
        $this->assertFalse($row->isPublic());
        $this->assertArrayNotHasKey('public', $row->settings);
        $this->assertSame(2, $row->settings['files_count']);
    }

    public function test_the_list_and_details_label_a_public_program_for_all_users(): void
    {
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $public = $this->fileRow($dept, $owner, ['title' => 'LP-pub-label', 'settings' => ['public' => true]]);
        $presenter = new LaunchpadPresenter();

        $this->assertSame('همهٔ کاربران', $presenter->listRow($public, $owner)['audience']);
        $this->assertContains(['مخاطبان', 'همهٔ کاربران'], $presenter->details($public, true)['access']);
    }

    public function test_removing_from_workspace_works_even_for_a_blocked_program_and_warns_when_absent(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $row = $this->fileRow($dept, $owner, ['title' => 'LP-opt-remove']);

        $component = Livewire::actingAs($owner)->test(Main::class)
            ->assertSeeHtml('wire:click="addOption(' . $row->id . ')"')
            ->assertDontSeeHtml('wire:click="removeOption(')
            ->call('addOption', $row->id)
            ->assertSeeHtml('wire:click="removeOption(' . $row->id . ')"')
            ->assertDontSeeHtml('wire:click="addOption(' . $row->id . ')"');

        $row->update(['active' => false]);

        $component->call('removeOption', $row->id)
            ->assertDispatched('toast', message: 'از فضای کاری حذف شد.', type: 'success')
            ->assertSeeHtml('wire:click="addOption(' . $row->id . ')"')
            ->call('removeOption', $row->id)
            ->assertDispatched('toast', message: 'این برنامه در فضای کاری نیست.', type: 'warning');

        $this->assertSame([], Launchpad::optionIds($owner->fresh()));
    }

    public function test_the_type_filter_narrows_rows_and_shows_in_the_filter_summary(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $this->fileRow($dept, $owner, ['title' => 'LP-type-static']);
        $this->linkRow($dept, $owner, ['title' => 'LP-type-link']);

        Livewire::actingAs($owner)->test(Main::class)
            ->assertSet('typeFilter', 'all')
            ->assertSee('LP-type-static')
            ->assertSee('LP-type-link')
            ->assertSeeHtml("wire:click=\"setTypeFilter('link')\"")
            ->assertSee("this.\$wire.get('typeFilter') !== 'all'")
            ->call('setTypeFilter', 'link')
            ->assertSee('LP-type-link')
            ->assertDontSee('LP-type-static')
            ->call('setTypeFilter', 'static')
            ->assertSee('LP-type-static')
            ->assertDontSee('LP-type-link')
            ->call('setTypeFilter', 'forged')
            ->assertSet('typeFilter', 'static')
            ->call('setTypeFilter', 'all')
            ->assertSee('LP-type-link');
    }

    public function test_the_unseen_filter_lists_only_unopened_private_programs_shared_with_the_user(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $this->fileRow($dept, $owner, ['title' => 'LP-unseen-new', 'audience' => [$viewer->id]]);
        $this->fileRow($dept, $owner, ['title' => 'LP-unseen-opened', 'audience' => [$viewer->id], 'seen_by' => [$viewer->id]]);
        $this->fileRow($dept, $owner, ['title' => 'LP-unseen-public', 'settings' => ['public' => true]]);
        $this->fileRow($dept, $viewer, ['title' => 'LP-unseen-own', 'audience' => [$viewer->id]]);
        $this->fileRow($dept, $owner, ['title' => 'LP-unseen-other', 'audience' => [$owner->id]]);

        Livewire::actingAs($viewer)->test(Main::class)
            ->assertSet('unseenFilter', 'all')
            ->assertSee('LP-unseen-public')
            ->assertSeeHtml("wire:click=\"setUnseenFilter('unseen')\"")
            ->assertSee("this.\$wire.get('unseenFilter') !== 'all'")
            ->call('setUnseenFilter', 'unseen')
            ->assertSee('LP-unseen-new')
            ->assertDontSee('LP-unseen-opened')
            ->assertDontSee('LP-unseen-public')
            ->assertDontSee('LP-unseen-own')
            ->assertDontSee('LP-unseen-other')
            ->call('setUnseenFilter', 'forged')
            ->assertSet('unseenFilter', 'unseen')
            ->call('open', Launchpad::query()->where('title', 'LP-unseen-new')->value('id'))
            ->call('close')
            ->assertDontSee('LP-unseen-new')
            ->call('setUnseenFilter', 'all')
            ->assertSee('LP-unseen-opened');
    }

    public function test_the_freshness_chip_shows_for_viewers_and_reason_with_retry_only_for_managers(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $at = '2026-11-10 10:30:00';
        $ok = $this->fileRow($dept, $owner, ['title' => 'LP-fresh-ok', 'audience' => [$viewer->id], 'settings' => ['refresh' => ['at' => $at, 'status' => 'ok', 'reason' => null]]]);
        $bad = $this->fileRow($dept, $owner, ['title' => 'LP-fresh-bad', 'audience' => [$viewer->id], 'settings' => ['refresh' => ['at' => $at, 'status' => 'unreachable', 'reason' => 'timeout']]]);
        $plain = $this->fileRow($dept, $owner, ['title' => 'LP-fresh-none', 'audience' => [$viewer->id]]);
        $reason = config('launchpad.refresh.reasons.timeout');
        $failure = 'آخرین دریافت اطلاعات ناموفق بود؛ اطلاعات ممکن است قدیمی باشد';
        $stamp = 'آخرین به‌روزرسانی: ' . convertToPersian(toJalaliSmart($at));

        Livewire::actingAs($viewer)->test(Main::class)->call('open', $ok->id)
            ->assertSee($stamp)->assertDontSee($failure)->assertDontSeeHtml('refreshNow(');
        Livewire::actingAs($viewer)->test(Main::class)->call('open', $bad->id)
            ->assertSee($failure)->assertDontSee($reason)->assertDontSee('آخرین به‌روزرسانی:')->assertDontSeeHtml('refreshNow(');
        Livewire::actingAs($viewer)->test(Main::class)->call('open', $plain->id)
            ->assertDontSee('آخرین به‌روزرسانی:')->assertDontSee($failure);
        Livewire::actingAs($owner)->test(Main::class)->call('open', $bad->id)
            ->assertSee($failure)->assertSee($reason)->assertSeeHtml('wire:click="refreshNow(' . $bad->id . ')"');
        Livewire::actingAs($owner)->test(Main::class)->call('open', $bad->id)->call('close')
            ->assertSet('openFresh', []);
        Livewire::actingAs($owner)->test(Main::class)->set('activeTab', 'compact')->call('open', $ok->id)
            ->assertSee($stamp)->assertSeeHtml('wire:click="refreshNow(' . $ok->id . ')"');
    }

    public function test_the_embed_pane_shows_only_a_thin_strip_on_a_failed_fetch(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $at = '2026-11-10 10:30:00';
        $ok = $this->fileRow($dept, $owner, ['title' => 'LP-strip-ok', 'settings' => ['refresh' => ['at' => $at, 'status' => 'ok', 'reason' => null]]]);
        $bad = $this->fileRow($dept, $owner, ['title' => 'LP-strip-bad', 'settings' => ['refresh' => ['at' => $at, 'status' => 'denied', 'reason' => 'private_sheet']]]);

        Livewire::withQueryParams(['embed' => 1, 'a' => $ok->id])->actingAs($owner)->test(Main::class)
            ->assertDontSee('آخرین به‌روزرسانی:')
            ->assertDontSeeHtml('wire:key="launchpad-embed-stale"');
    }

    public function test_the_embed_pane_shows_the_strip_for_a_failed_fetch(): void
    {
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $at = '2026-11-10 10:30:00';
        $bad = $this->fileRow($dept, $owner, ['title' => 'LP-strip-bad', 'settings' => ['refresh' => ['at' => $at, 'status' => 'denied', 'reason' => 'private_sheet']]]);

        Livewire::withQueryParams(['embed' => 1, 'a' => $bad->id])->actingAs($owner)->test(Main::class)
            ->assertSeeHtml('wire:key="launchpad-embed-stale"')
            ->assertSee('آخرین دریافت اطلاعات ناموفق بود؛ اطلاعات ممکن است قدیمی باشد')
            ->assertDontSee(config('launchpad.refresh.reasons.private_sheet'))
            ->assertDontSeeHtml('refreshNow(');
    }

    public function test_the_viewer_retry_goes_through_refresh_now_results(): void
    {
        Queue::fake();
        Storage::fake('public');
        $dept = $this->dept('LPA');
        $owner = $this->deptUser($dept);
        $row = $this->sourceRow($dept, $owner, ['settings' => ['refresh' => ['at' => '2026-11-10 10:30:00', 'status' => 'invalid', 'reason' => 'bad_content']]]);
        RateLimiter::clear("launchpad-refresh:{$row->id}");

        Livewire::actingAs($owner)->test(Main::class)->call('open', $row->id)
            ->assertSee(config('launchpad.refresh.reasons.bad_content'))
            ->call('refreshNow', $row->id)
            ->assertDispatched('toast', fn ($event, $params) => ($params['type'] ?? null) === 'success')
            ->call('refreshNow', $row->id)
            ->assertDispatched('toast', fn ($event, $params) => ($params['type'] ?? null) === 'warning');
        Queue::assertPushed(RefreshLaunchpadJob::class, 1);

        RateLimiter::clear("launchpad-refresh:{$row->id}");
    }

    private function pickerSources(User $user): array
    {
        $this->actingAs($user);

        return collect((new TabPresenter())->heroGadgetCatalog())->pluck('src', 'title')->all();
    }
}
