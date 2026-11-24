<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Livewire\Dashboard\Correspondence\Actions\TemplateAction;
use App\Livewire\Dashboard\Correspondence\Actions\ApproveLetterAction;
use App\Livewire\Dashboard\Correspondence\Actions\CloseLetterAction;
use App\Livewire\Dashboard\Correspondence\Actions\CommentLetterAction;
use App\Livewire\Dashboard\Correspondence\Actions\CorrectLetterAction;
use App\Livewire\Dashboard\Correspondence\Actions\DeliverLinkAction;
use App\Livewire\Dashboard\Correspondence\Actions\DeliverManuallyAction;
use App\Livewire\Dashboard\Correspondence\Actions\LetterRules;
use App\Livewire\Dashboard\Correspondence\Actions\LetterWrite;
use App\Livewire\Dashboard\Correspondence\Actions\ReceiveLetterAction;
use App\Livewire\Dashboard\Correspondence\Actions\RejectLetterAction;
use App\Livewire\Dashboard\Correspondence\Actions\RerouteLetterAction;
use App\Livewire\Dashboard\Correspondence\Actions\RouteLetterAction;
use App\Livewire\Dashboard\Correspondence\Actions\SaveMailTextAction;
use App\Livewire\Dashboard\Correspondence\Actions\ShareLetterAction;
use App\Livewire\Dashboard\Correspondence\Actions\SupersedeLetterAction;
use App\Livewire\Dashboard\Correspondence\Actions\VoidLetterAction;
use App\Livewire\Dashboard\Correspondence\Actions\WetStepAction;
use App\Livewire\Dashboard\Correspondence\Actions\WithdrawLetterAction;
use App\Livewire\Dashboard\Correspondence\Forms\LetterForm;
use App\Livewire\Dashboard\Correspondence\Presentation\CorrespondencePresenter;
use App\Jobs\GenerateCorrespondenceJob;
use App\Jobs\SendCorrespondenceJob;
use App\Livewire\Dashboard\Correspondence\Main;
use App\Livewire\Dashboard\Correspondence\Workspace;
use App\Models\Correspondence;
use App\Models\Department;
use App\Models\Letterhead;
use App\Models\Profile;
use App\Models\User;
use App\Services\Menu\BadgeLegendCatalog;
use App\Services\Correspondence\CorrespondenceFonts;
use Illuminate\Cache\RateLimiter as CacheRateLimiter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Arr;
use Livewire\Livewire;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use LogicException;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

class CorrespondenceTest extends TestCase
{
    private bool $autoSign = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        config(['app.off' => [], 'cache.default' => 'array', 'cache.limiter' => 'array', 'ai.correspondence.cooldown' => 0]);
        Cache::setDefaultDriver('array');
        Cache::flush();
        $this->app->forgetInstance('cache.limiter');
        $this->app->singleton('cache.limiter', fn ($app) => new CacheRateLimiter($app['cache']->store('array')));
        Livewire::withoutLazyLoading();
        app()->setLocale('fa');
        Storage::fake('local');
        Storage::fake('public');
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        Auth::logout();
        Cache::flush();
        Carbon::setTestNow();
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
        return User::factory()->create(array_replace([
            'role' => 'user',
            'status' => 'active',
            'type' => 'employee',
        ], $attributes));
    }

    private function registryUser(array $attributes = []): User
    {
        return $this->user(array_replace([
            'extra' => ['admin' => ['correspondence_registry' => true]],
        ], $attributes));
    }

    private function makeHead(string $kind = Letterhead::KIND_HEAD, ?string $pattern = null): Letterhead
    {
        $prefix = 'C' . bin2hex(random_bytes(4));

        return Letterhead::forceCreate([
            'kind' => $kind,
            'name' => 'Correspondence test ' . $prefix,
            'pattern' => $pattern ?? ($kind === Letterhead::KIND_REGISTRY ? '{PREFIX}/{YYYY}/{SEQ:4}' : '{PREFIX}/{DEPT}/{TYPE}/{SEQ:4}'),
            'config' => ['prefix' => $prefix],
        ]);
    }

    private function letter(User $author, array $overrides = []): Correspondence
    {
        $direction = $overrides['direction'] ?? Correspondence::DIRECTION_OUT;
        $head = $overrides['letterhead_id'] ?? $this->makeHead($direction === Correspondence::DIRECTION_IN ? Letterhead::KIND_REGISTRY : Letterhead::KIND_HEAD)->id;

        return Correspondence::forceCreate(array_replace([
            'user_id' => $author->id,
            'direction' => $direction,
            'type' => $direction === Correspondence::DIRECTION_IN ? 'incoming' : 'letter',
            'status' => Correspondence::STATUS_DRAFT,
            'classification' => Correspondence::CLASSIFICATION_NORMAL,
            'sign_mode' => Correspondence::SIGN_MODE_ELECTRONIC,
            'route_mode' => Correspondence::ROUTE_SEQUENTIAL,
            'letterhead_id' => $head,
            'subject' => 'آزمون مکاتبه',
            'spec' => ['blocks' => [['type' => 'text', 'text' => 'متن نامه']]],
            'parties' => [],
            'recipients' => [],
            'events' => [],
        ], $overrides));
    }

    private function party(User $user, string $role = LetterForm::ROLE_APPROVER, array $extra = []): array
    {
        return array_replace([
            'user_id' => (int) $user->id,
            'role' => $role,
            'name' => (string) $user->name,
            'org' => '',
            'delivery' => 'link',
            'order' => 1,
        ], $extra);
    }

    private function shared(User $author, array $parties, array $overrides = []): Correspondence
    {
        $letter = $this->letter($author, array_replace(['parties' => $parties], $overrides));
        $this->act($author, $letter, 'share');

        return $letter->fresh();
    }

    private function act(User $actor, Correspondence $letter, string $transition, array $payload = []): void
    {
        if ($transition === 'share' && $this->autoSign) {
            foreach (Correspondence::findOrFail($letter->id)->parties ?? [] as $party) {
                if (($party['role'] ?? null) === LetterForm::ROLE_APPROVER && filled($party['user_id'] ?? null)) {
                    $this->signature(User::findOrFail($party['user_id']));
                }
            }
        }
        $this->actingAs($actor);
        $class = match ($transition) {
            'share' => ShareLetterAction::class,
            'approve' => ApproveLetterAction::class,
            'reject' => RejectLetterAction::class,
            'withdraw' => WithdrawLetterAction::class,
            'void' => VoidLetterAction::class,
            'supersede' => SupersedeLetterAction::class,
            'reroute' => RerouteLetterAction::class,
            'route' => RouteLetterAction::class,
            'receive' => ReceiveLetterAction::class,
            'close' => CloseLetterAction::class,
            'correct' => CorrectLetterAction::class,
            'comment' => CommentLetterAction::class,
            'printed', 'wet_signed' => WetStepAction::class,
            'delivered_manually' => DeliverManuallyAction::class,
            'mail_text' => SaveMailTextAction::class,
        };
        app($class)->execute((int) $letter->id, in_array($transition, ['printed', 'wet_signed'], true) ? ['step' => $transition, ...$payload] : $payload);
    }

    private function signature(User $user): string
    {
        $path = "users/signatures/sign-{$user->id}.png";
        Storage::disk('local')->put($path, 'signature');
        $user->setExtraValue('admin.signature', $path);
        $user->save();

        return $path;
    }

    private function profile(User $user, string $position = 'employee'): string
    {
        $department = Department::factory()->create();
        Profile::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->code,
            'position' => $position,
        ]);

        return $department->code;
    }

    private function expectValidation(callable $callback, ?string $field = null): ValidationException
    {
        try {
            $callback();
        } catch (ValidationException $exception) {
            if ($field !== null) {
                $this->assertArrayHasKey($field, $exception->errors());
            }

            return $exception;
        }

        $this->fail('Expected a ValidationException.');
    }

    private function expectDenied(callable $callback): RuntimeException
    {
        try {
            $callback();
        } catch (RuntimeException $exception) {
            return $exception;
        }

        $this->fail('Expected the action to deny access.');
    }

    public function test_share_requires_an_approver_and_blocks_unfilled_placeholders(): void
    {
        $author = $this->user();
        $approver = $this->user();
        $fyi = $this->user();

        $withoutParties = $this->letter($author);
        $this->expectValidation(fn () => $this->act($author, $withoutParties, 'share'), 'form');

        $fyiOnly = $this->letter($author, ['parties' => [$this->party($fyi, LetterForm::ROLE_FYI)]]);
        $this->expectValidation(fn () => $this->act($author, $fyiOnly, 'share'), 'form');

        $placeholder = $this->letter($author, [
            'parties' => [$this->party($approver)],
            'spec' => ['blocks' => [['type' => 'text', 'text' => 'تکمیل شود […]']]],
        ]);
        $exception = $this->expectValidation(fn () => $this->act($author, $placeholder, 'share'), 'form');
        $this->assertNotEmpty($exception->errors()['form']);
    }

    public function test_share_sets_route_state_and_rejects_a_non_author(): void
    {
        $author = $this->user();
        $approver = $this->user();
        $stranger = $this->user();
        $letter = $this->letter($author, ['parties' => [$this->party($approver)]]);

        $this->expectDenied(fn () => $this->act($stranger, $letter, 'share'));
        $this->act($author, $letter, 'share');

        $shared = $letter->fresh();
        $this->assertSame(Correspondence::STATUS_SHARED, $shared->status);
        $this->assertSame((int) $approver->id, (int) $shared->awaiting_user_id);
        $this->assertSame(1, $shared->step_no);
        $this->assertSame(1, $shared->steps_total);
        $this->assertSame('shared', $shared->events[array_key_last($shared->events)]['action']);
    }

    public function test_sequential_approval_numbers_freezes_and_snapshots_on_the_final_signature(): void
    {
        $author = $this->user();
        $dept = $this->profile($author);
        $first = $this->user();
        $second = $this->user();
        $this->signature($first);
        $this->signature($second);
        $head = $this->makeHead();
        $letter = $this->letter($author, [
            'letterhead_id' => $head->id,
            'parties' => [$this->party($first), $this->party($second)],
        ]);
        $this->act($author, $letter, 'share');

        $this->act($first, $letter, 'approve');
        $shared = $letter->fresh();
        $this->assertSame(Correspondence::STATUS_SHARED, $shared->status);
        $this->assertSame((int) $second->id, (int) $shared->awaiting_user_id);
        $this->assertNotEmpty($shared->parties[0]['signed_at']);
        $this->assertSame(2, $shared->step_no);

        $this->act($second, $letter, 'approve');
        $approved = $letter->fresh();
        $this->assertSame(Correspondence::STATUS_APPROVED, $approved->status);
        $this->assertNotEmpty($approved->number);
        $this->assertNotEmpty($approved->letter_date);
        $this->assertNotEmpty($approved->approved_at);
        $this->assertSame(64, strlen($approved->content_hash));
        $this->assertSame("correspondence/{$letter->id}/signature-{$first->id}.png", $approved->parties[0]['signature_path']);
        $this->assertSame("correspondence/{$letter->id}/signature-{$second->id}.png", $approved->parties[1]['signature_path']);
        $this->assertSame($head->config['prefix'] . '/' . strtolower($dept) . '/' . config('ai.correspondence.types.letter.code') . '/0001', $approved->number);
        $this->assertSame((int) $head->id, $approved->meta['snapshot']['letterhead_id']);
        $this->assertSame('approved', $approved->events[array_key_last($approved->events)]['action']);
        $counterKey = $head->config['prefix'] . '|' . strtolower($dept) . '|' . config('ai.correspondence.types.letter.code');
        $this->assertSame(1, $head->fresh()->counters[$counterKey]);
        $this->assertTrue(Storage::disk('local')->exists("correspondence/{$letter->id}/signature-{$first->id}.png"));
        $this->assertTrue(Storage::disk('local')->exists("correspondence/{$letter->id}/signature-{$second->id}.png"));

        $eventCount = count($approved->events);
        $this->act($second, $letter, 'approve');
        $this->assertSame($eventCount, count($letter->fresh()->events));
        $this->assertSame($approved->number, $letter->fresh()->number);
    }

    public function test_parallel_approvers_can_sign_in_any_order_and_only_the_last_one_finalizes(): void
    {
        $author = $this->user();
        $first = $this->user();
        $second = $this->user();
        $this->signature($first);
        $this->signature($second);
        $letter = $this->letter($author, [
            'route_mode' => Correspondence::ROUTE_PARALLEL,
            'parties' => [$this->party($first), $this->party($second)],
        ]);
        $this->act($author, $letter, 'share');

        $this->act($second, $letter, 'approve');
        $this->assertSame(Correspondence::STATUS_SHARED, $letter->fresh()->status);
        $this->assertNotEmpty($letter->fresh()->parties[1]['signed_at']);

        $this->act($first, $letter, 'approve');
        $this->assertSame(Correspondence::STATUS_APPROVED, $letter->fresh()->status);
    }

    public function test_wet_approval_numbers_and_snapshots_without_copying_electronic_signatures(): void
    {
        $author = $this->user();
        $approver = $this->user();
        $letter = $this->letter($author, [
            'sign_mode' => Correspondence::SIGN_MODE_WET,
            'parties' => [$this->party($approver)],
        ]);
        $this->act($author, $letter, 'share');
        $this->act($approver, $letter, 'approve');

        $approved = $letter->fresh();
        $this->assertSame(Correspondence::STATUS_APPROVED, $approved->status);
        $this->assertNotEmpty($approved->number);
        $this->assertArrayNotHasKey('signature_path', $approved->parties[0]);
        $this->assertArrayHasKey('snapshot', $approved->meta);
        $this->assertSame([], Storage::disk('local')->allFiles("correspondence/{$letter->id}"));
    }

    public function test_electronic_finalization_requires_each_approver_signature_file(): void
    {
        $author = $this->user();
        $approver = $this->user();
        $letter = $this->letter($author, ['parties' => [$this->party($approver)]]);
        $this->act($author, $letter, 'share');
        Storage::disk('local')->delete("users/signatures/sign-{$approver->id}.png");

        $error = $this->expectValidation(fn () => $this->act($approver, $letter, 'approve'), 'status');

        $this->assertStringContainsString((string) $approver->name, $error->errors()['status'][0]);
        $this->assertSame(Correspondence::STATUS_SHARED, $letter->fresh()->status);
        $this->assertNull($letter->fresh()->number);
    }

    public function test_proxy_approval_uses_the_secret_code_and_clears_only_the_pair_key(): void
    {
        $author = $this->user();
        $approver = $this->user();
        $proxy = $this->user();
        $other = $this->user();
        $approver->setExtraValue('admin.sign_code_hash', Hash::make('secret-123'));
        $approver->save();
        $letter = $this->letter($author, [
            'route_mode' => Correspondence::ROUTE_PARALLEL,
            'parties' => [$this->party($approver), $this->party($other), $this->party($proxy, LetterForm::ROLE_FYI)],
        ]);
        $this->act($author, $letter, 'share');

        $pairKey = "sign-code:pair:{$proxy->id}:{$approver->id}";
        $actorKey = "sign-code:actor:{$proxy->id}";
        RateLimiter::hit($pairKey, 900);
        RateLimiter::hit($actorKey, 900);
        $this->act($proxy, $letter, 'approve', ['target_id' => $approver->id, 'code' => 'secret-123']);

        $this->assertNotEmpty($letter->fresh()->parties[0]['signed_at']);
        $this->assertSame(0, RateLimiter::attempts($pairKey));
        $this->assertSame(1, RateLimiter::attempts($actorKey));
        $event = $letter->fresh()->events[array_key_last($letter->fresh()->events)];
        $this->assertSame('approved_instead', $event['action']);
        $this->assertSame((int) $approver->id, $event['target_id']);
        $this->assertSame((int) $proxy->id, $event['user_id']);
    }

    public function test_missing_wrong_and_inactive_target_codes_share_one_validation_error(): void
    {
        $author = $this->user();
        $approver = $this->user();
        $proxy = $this->user();
        $approver->setExtraValue('admin.sign_code_hash', Hash::make('secret-123'));
        $approver->save();
        $letter = $this->letter($author, [
            'route_mode' => Correspondence::ROUTE_PARALLEL,
            'parties' => [$this->party($approver), $this->party($proxy, LetterForm::ROLE_FYI)],
        ]);
        $this->act($author, $letter, 'share');

        $missing =$this->expectValidation(fn () => $this->act($proxy, $letter, 'approve', ['target_id' => $approver->id]), 'signCode');
        $wrong = $this->expectValidation(fn () => $this->act($proxy, $letter, 'approve', ['target_id' => $approver->id, 'code' => 'wrong-code']), 'signCode');
        $this->assertSame($missing->errors()['signCode'], $wrong->errors()['signCode']);

        $approver->update(['status' => 'inactive']);
        $inactive = $this->expectValidation(fn () => $this->act($proxy, $letter, 'approve', ['target_id' => $approver->id, 'code' => 'secret-123']), 'signCode');
        $this->assertSame($wrong->errors()['signCode'], $inactive->errors()['signCode']);
    }

    public function test_the_pair_cap_of_five_blocks_the_sixth_wrong_code_and_the_strikes_count_for_the_actor_too(): void
    {
        $author = $this->user();
        $approver = $this->user();
        $proxy = $this->user();
        $letter = $this->letter($author, [
            'route_mode' => Correspondence::ROUTE_PARALLEL,
            'parties' => [$this->party($approver), $this->party($proxy, LetterForm::ROLE_FYI)],
        ]);
        $this->act($author, $letter, 'share');

        $cap = (int) config('ai.correspondence.sign_code_pair_attempts');
        $this->assertSame(5, $cap);
        for ($attempt = 0; $attempt < $cap; $attempt++) {
            $wrong = $this->expectValidation(fn () => $this->act($proxy, $letter, 'approve', ['target_id' => $approver->id, 'code' => 'wrong']), 'signCode');
            $this->assertSame(__('resources/correspondence/strings.errors.wrong_code'), $wrong->errors()['signCode'][0]);
        }
        $throttled = $this->expectValidation(fn () => $this->act($proxy, $letter, 'approve', ['target_id' => $approver->id, 'code' => 'wrong']), 'signCode');

        $this->assertSame(__('resources/correspondence/strings.errors.too_many_attempts'), $throttled->errors()['signCode'][0]);
        $this->assertSame($cap, RateLimiter::attempts("sign-code:pair:{$proxy->id}:{$approver->id}"));
        $this->assertSame($cap, RateLimiter::attempts("sign-code:actor:{$proxy->id}"));
    }

    public function test_fyi_target_cannot_be_approved_for(): void
    {
        $author = $this->user();
        $fyi = $this->user();
        $approver = $this->user();
        $parallel = $this->letter($author, [
            'route_mode' => Correspondence::ROUTE_PARALLEL,
            'parties' => [$this->party($approver), $this->party($fyi, LetterForm::ROLE_FYI)],
        ]);
        $this->act($author, $parallel, 'share');
        $exception = $this->expectValidation(fn () => $this->act($fyi, $parallel, 'approve', ['target_id' => $fyi->id, 'code' => 'secret']), 'status');
        $this->assertNotEmpty($exception->errors()['status']);
        $this->assertSame(Correspondence::STATUS_SHARED, $parallel->fresh()->status);
    }

    public function test_reject_resume_preserves_other_signatures_only_when_content_hash_is_unchanged(): void
    {
        $author = $this->user();
        $first = $this->user();
        $second = $this->user();
        $this->signature($first);
        $this->signature($second);
        $letter = $this->letter($author, [
            'route_mode' => Correspondence::ROUTE_PARALLEL,
            'parties' => [$this->party($first), $this->party($second)],
        ]);
        $this->act($author, $letter, 'share');
        $this->act($first, $letter, 'approve');
        $this->act($second, $letter, 'reject', ['reason' => 'اصلاح شود', 'resume' => true]);

        $rejected = $letter->fresh();
        $this->assertSame(Correspondence::STATUS_REJECTED, $rejected->status);
        $this->assertNotEmpty($rejected->parties[0]['signed_at']);
        $this->assertArrayHasKey('resume', $rejected->meta);
        $this->act($author, $letter, 'share');
        $this->assertNotEmpty($letter->fresh()->parties[0]['signed_at']);

        $this->act($first, $letter, 'reject', ['reason' => 'برگشت', 'resume' => true]);
        $changed = Correspondence::findOrFail($letter->id);
        $changed->forceFill(['subject' => 'موضوع عوض شد'])->save();
        $this->act($author, $letter, 'share');
        foreach ($letter->fresh()->parties as $party) {
            $this->assertArrayNotHasKey('signed_at', $party);
        }
    }

    public function test_parallel_resuming_reject_clears_only_the_rejecting_approvers_signature(): void
    {
        $author = $this->user();
        $first = $this->user();
        $second = $this->user();
        $letter = $this->letter($author, [
            'status' => Correspondence::STATUS_SHARED,
            'route_mode' => Correspondence::ROUTE_PARALLEL,
            'parties' => [
                $this->party($first, LetterForm::ROLE_APPROVER, ['signed_at' => now()->toIso8601String()]),
                $this->party($second, LetterForm::ROLE_APPROVER, ['signed_at' => now()->toIso8601String()]),
            ],
        ]);

        $this->act($second, $letter, 'reject', ['reason' => 'بازبینی', 'resume' => true]);

        $parties = $letter->fresh()->parties;
        $this->assertNotEmpty($parties[0]['signed_at']);
        $this->assertArrayNotHasKey('signed_at', $parties[1]);
    }

    public function test_withdraw_resets_signatures_and_void_is_an_author_only_approved_exit(): void
    {
        $author = $this->user();
        $approver = $this->user();
        $letter = $this->letter($author, [
            'status' => Correspondence::STATUS_SHARED,
            'shared_at' => now(),
            'awaiting_user_id' => $approver->id,
            'parties' => [$this->party($approver, LetterForm::ROLE_APPROVER, ['signed_at' => now()->toIso8601String()])],
        ]);
        $this->act($author, $letter, 'withdraw');
        $withdrawn = $letter->fresh();
        $this->assertSame(Correspondence::STATUS_DRAFT, $withdrawn->status);
        $this->assertNull($withdrawn->awaiting_user_id);
        $this->assertNull($withdrawn->shared_at);
        $this->assertArrayNotHasKey('signed_at', $withdrawn->parties[0]);

        $approved = $this->letter($author, ['status' => Correspondence::STATUS_APPROVED]);
        $this->expectDenied(fn () => $this->act($approver, $approved, 'void'));
        $this->act($author, $approved, 'void');
        $this->assertSame(Correspondence::STATUS_VOID, $approved->fresh()->status);
    }

    public function test_supersede_requires_an_approved_outgoing_replacement_pointing_to_the_original(): void
    {
        $author = $this->user();
        $original = $this->letter($author, ['status' => Correspondence::STATUS_APPROVED]);
        $replacement = $this->letter($author, [
            'status' => Correspondence::STATUS_APPROVED,
            'supersedes_id' => $original->id,
            'number' => 'replacement-' . $original->id,
        ]);

        $this->act($author, $original, 'supersede', ['replacement_id' => $replacement->id]);
        $this->assertSame(Correspondence::STATUS_SUPERSEDED, $original->fresh()->status);
        $this->assertSame('superseded', $original->fresh()->events[array_key_last($original->fresh()->events)]['action']);
    }

    public function test_supersede_rejects_a_replacement_that_is_not_approved(): void
    {
        $author = $this->user();
        $original = $this->letter($author, ['status' => Correspondence::STATUS_APPROVED]);
        $replacement = $this->letter($author, ['supersedes_id' => $original->id, 'status' => Correspondence::STATUS_DRAFT]);

        $this->expectDenied(fn () => $this->act($author, $original, 'supersede', ['replacement_id' => $replacement->id]));
        $this->assertSame(Correspondence::STATUS_APPROVED, $original->fresh()->status);
    }

    public function test_reroute_retains_signed_approvers_and_requires_a_new_unsigned_approver(): void
    {
        $author = $this->user();
        $signed = $this->user();
        $oldUnsigned = $this->user();
        $newApprover = $this->user();
        $unsignedNew = $this->user();
        $letter = $this->letter($author, [
            'status' => Correspondence::STATUS_SHARED,
            'route_mode' => Correspondence::ROUTE_PARALLEL,
            'parties' => [
                $this->party($signed, LetterForm::ROLE_APPROVER, ['signed_at' => now()->toIso8601String()]),
                $this->party($oldUnsigned),
            ],
            'recipients' => [$signed->id, $oldUnsigned->id],
        ]);

        $this->expectValidation(fn () => $this->act($author, $letter, 'reroute', [
            'parties' => [$this->party($signed), $this->party($unsignedNew)],
        ]), 'status');
        $this->signature($newApprover);

        $this->act($author, $letter, 'reroute', [
            'parties' => [$this->party($signed), $this->party($newApprover)],
            'route_mode' => Correspondence::ROUTE_SEQUENTIAL,
        ]);
        $rerouted = $letter->fresh();
        $this->assertSame(Correspondence::ROUTE_SEQUENTIAL, $rerouted->route_mode);
        $this->assertSame((int) $signed->id, (int) $rerouted->parties[0]['user_id']);
        $this->assertNotEmpty($rerouted->parties[0]['signed_at']);
        $this->assertSame((int) $newApprover->id, (int) $rerouted->parties[1]['user_id']);
        $this->assertSame((int) $newApprover->id, (int) $rerouted->awaiting_user_id);

        $invalid = Correspondence::findOrFail($letter->id);
        $invalid->forceFill(['status' => Correspondence::STATUS_SHARED]);
        $this->expectValidation(fn () => $this->act($author, $invalid, 'reroute', ['parties' => [$this->party($signed)]]), 'form');
    }

    public function test_incoming_receive_and_route_use_registry_permission_and_append_party_events(): void
    {
        $registry = $this->registryUser();
        $recipient = $this->user();
        $department = $this->profile($registry);
        $ceo = $this->user();
        $this->profile($ceo, 'ceo');
        $head = $this->makeHead(Letterhead::KIND_REGISTRY);
        $incoming = $this->letter($registry, [
            'direction' => Correspondence::DIRECTION_IN,
            'type' => 'incoming',
            'letterhead_id' => $head->id,
            'sender_category' => Correspondence::CATEGORY_AUTHORITY,
            'their_number' => 'EX-12',
            'their_date' => '1405/02/03',
            'spec' => ['blocks' => [['type' => 'text', 'text' => 'متن وارده']], 'sender' => ['name' => 'مرجع']],
        ]);

        $this->expectDenied(fn () => $this->act($recipient, $incoming, 'receive'));
        $this->act($registry, $incoming, 'receive');
        $received = $incoming->fresh();
        $this->assertSame(Correspondence::STATUS_RECEIVED, $received->status);
        $this->assertSame('EX-12', $received->their_number);
        $this->assertNotEmpty($received->number);
        $this->assertNotEmpty($received->received_at);
        $this->assertTrue(User::query()
            ->whereIn('id', array_map(fn (array $party) => (int) ($party['user_id'] ?? 0), $received->parties))
            ->whereHas('profile', fn ($query) => $query->where('position', 'ceo'))
            ->exists());
        $this->assertNotSame('', $department);

        $this->expectDenied(fn () => $this->act($recipient, $incoming, 'route', [
            'parties' => [['user_id' => $recipient->id]],
            'instruction' => 'برای بررسی',
        ]));
        $this->act($registry, $incoming, 'route', [
            'parties' => [['user_id' => $recipient->id]],
            'instruction' => 'برای بررسی',
        ]);
        $routed = $incoming->fresh();
        $this->assertSame(Correspondence::STATUS_ROUTED, $routed->status);
        $this->assertContains((int) $recipient->id, array_map(fn (array $party) => (int) ($party['user_id'] ?? 0), $routed->parties));
        $routedEvent = $routed->events[array_key_last($routed->events)];
        $this->assertSame('routed', $routedEvent['action']);
        $this->assertSame((int) $recipient->id, $routedEvent['target_id']);
        $this->assertSame('برای بررسی', $routedEvent['text']);
    }

    public function test_comments_are_appended_in_the_frozen_event_shape_and_the_count_is_capped(): void
    {
        $author = $this->user();
        $letter = $this->letter($author, ['status' => Correspondence::STATUS_SHARED]);
        $this->act($author, $letter, 'comment', ['text' => 'یادداشت <b>مهم</b>']);

        $event = $letter->fresh()->events[0];
        $this->assertEqualsCanonicalizing(['at', 'user_id', 'action', 'text'], array_keys($event));
        $this->assertSame((int) $author->id, $event['user_id']);
        $this->assertSame('commented', $event['action']);
        $this->assertSame('یادداشت مهم', $event['text']);

        $capped = $this->letter($author, [
            'status' => Correspondence::STATUS_SHARED,
            'events' => array_fill(0, 500, ['action' => 'commented', 'user_id' => $author->id, 'at' => now()->toIso8601String()]),
        ]);
        $this->expectValidation(fn () => $this->act($author, $capped, 'comment', ['text' => 'دیگر']), 'commentText');

        $eventsFull = $this->letter($author, [
            'status' => Correspondence::STATUS_SHARED,
            'events' => array_fill(0, 1000, ['action' => 'existing', 'user_id' => $author->id, 'at' => now()->toIso8601String()]),
        ]);
        $this->expectValidation(fn () => $this->act($author, $eventsFull, 'comment', ['text' => 'دیگر']), 'form');
    }

    public function test_wet_signed_is_idempotent_and_follows_printed_in_the_event_order(): void
    {
        $author = $this->user();
        $wet = $this->letter($author, ['status' => Correspondence::STATUS_APPROVED, 'sign_mode' => Correspondence::SIGN_MODE_WET]);
        $this->act($author, $wet, 'printed');
        $this->act($author, $wet, 'wet_signed');
        $afterSign = count($wet->fresh()->events);
        $this->act($author, $wet, 'wet_signed');
        $this->assertSame($afterSign, count($wet->fresh()->events));
        $this->assertSame('printed', $wet->fresh()->events[0]['action']);
        $this->assertSame('wet_signed', $wet->fresh()->events[1]['action']);
    }

    public function test_printed_is_refused_on_an_electronic_letter(): void
    {
        $author = $this->user();
        $electronic = $this->letter($author, ['status' => Correspondence::STATUS_APPROVED]);
        $this->expectValidation(fn () => $this->act($author, $electronic, 'printed'), 'status');
    }

    public function test_manual_delivery_validates_external_party_and_records_delivery_event(): void
    {
        $author = $this->user();
        $letter = $this->letter($author, [
            'status' => Correspondence::STATUS_APPROVED,
            'parties' => [[
                'user_id' => null,
                'role' => LetterForm::ROLE_FYI,
                'name' => 'گیرنده بیرونی',
                'org' => 'شرکت نمونه',
                'email' => 'outside@example.org',
            ]],
        ]);

        $this->act($author, $letter, 'delivered_manually', ['party' => 0, 'text' => 'تحویل شد']);
        $delivered = $letter->fresh();
        $this->assertSame('تحویل شد', $delivered->parties[0]['manual_note']);
        $this->assertNotEmpty($delivered->parties[0]['sent_at']);
        $event = $delivered->events[array_key_last($delivered->events)];
        $this->assertSame('delivered_manually', $event['action']);
        $this->assertSame('گیرنده بیرونی', $event['target']);

        $internal = $this->letter($author, ['status' => Correspondence::STATUS_APPROVED, 'parties' => [$this->party($this->user())]]);
        $this->expectDenied(fn () => $this->act($author, $internal, 'delivered_manually', ['party' => 0, 'text' => 'تحویل شد']));
    }

    public function test_stale_hash_and_awaiting_pointers_use_conflict_code_and_missing_pointer_is_optional(): void
    {
        $author = $this->user();
        $approver = $this->user();
        $letter = $this->letter($author, [
            'status' => Correspondence::STATUS_SHARED,
            'awaiting_user_id' => $approver->id,
            'parties' => [$this->party($approver)],
        ]);

        foreach ([
            ['expected_hash' => 'stale'],
            ['expected_awaiting' => $approver->id + 1],
        ] as $payload) {
            try {
                $this->act($approver, $letter, 'reject', ['reason' => 'برگشت', ...$payload]);
                $this->fail('Expected a stale-state conflict.');
            } catch (RuntimeException $exception) {
                $this->assertSame(409, $exception->getCode());
            }
        }

        $this->act($approver, $letter, 'reject', ['reason' => 'برگشت']);
        $this->assertSame(Correspondence::STATUS_REJECTED, $letter->fresh()->status);
    }

    public function test_wrong_secret_code_is_a_validation_error_not_a_runtime_conflict(): void
    {
        $author = $this->user();
        $approver = $this->user();
        $proxy = $this->user();
        $letter = $this->letter($author, [
            'route_mode' => Correspondence::ROUTE_PARALLEL,
            'parties' => [$this->party($approver), $this->party($proxy, LetterForm::ROLE_FYI)],
        ]);
        $this->act($author, $letter, 'share');

        try {
            $this->act($proxy, $letter, 'approve', ['target_id' => $approver->id, 'code' => 'wrong']);
            $this->fail('Expected a validation error for the code.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('signCode', $exception->errors());
        } catch (RuntimeException $exception) {
            $this->fail('Wrong sign codes must not be reported as stale conflicts: ' . $exception->getMessage());
        }
    }

    public function test_failed_finalization_cleans_copied_files_and_rolls_back_the_number_counter(): void
    {
        $author = $this->user();
        $approver = $this->user();
        $this->signature($approver);
        $head = $this->makeHead();
        $letter = $this->letter($author, ['letterhead_id' => $head->id, 'parties' => [$this->party($approver)]]);
        $this->act($author, $letter, 'share');
        $armed = true;
        Correspondence::saved(function (Correspondence $saved) use (&$armed) {
            if ($armed && $saved->status === Correspondence::STATUS_APPROVED) {
                throw ValidationException::withMessages(['spec' => 'forced failure after finalization']);
            }
        });

        try {
            $this->expectValidation(fn () => $this->act($approver, $letter, 'approve'), 'spec');
        } finally {
            $armed = false;
        }

        $this->assertFalse(Storage::disk('local')->exists("correspondence/{$letter->id}/signature-{$approver->id}.png"));
        $this->assertNull($head->fresh()->counters);
        $this->assertSame(Correspondence::STATUS_SHARED, $letter->fresh()->status);
    }

    public function test_main_mounts_a_draft_and_opens_only_visible_letters(): void
    {
        $author = $this->user();
        $approver = $this->user();
        $shared = $this->shared($author, [$this->party($approver)]);
        $hidden = $this->letter($author, ['subject' => 'hidden from stranger']);
        $stranger = $this->user();

        Livewire::actingAs($author)->test(Main::class)->assertSuccessful()->assertSet('letterId', null);
        Livewire::actingAs($author)->test(Workspace::class)->assertSuccessful()->assertSet('letterId', null);

        Livewire::actingAs($approver)->test(Main::class)
            ->call('open', $shared->id)
            ->assertSet('letterId', $shared->id);
        Livewire::actingAs($approver)->test(Workspace::class, ['letterId' => $shared->id])
            ->assertSet('letterId', $shared->id)
            ->assertSet('readOnly', false);

        Livewire::actingAs($stranger)->test(Main::class)
            ->call('open', $hidden->id)
            ->assertSet('letterId', null);
        Livewire::actingAs($stranger)->test(Workspace::class, ['letterId' => $hidden->id])
            ->assertSet('letterId', null);
    }

    public function test_main_open_refuses_an_unauthorised_letter_and_sets_the_letter_id_for_a_viewer(): void
    {
        $author = $this->user();
        $approver = $this->user();
        $stranger = $this->user();
        $shared = $this->shared($author, [$this->party($approver)]);

        Livewire::actingAs($stranger)->test(Main::class)->call('open', $shared->id)->assertSet('letterId', null)->assertDispatched('toast');
        Livewire::actingAs($approver)->test(Main::class)->call('open', $shared->id)->assertSet('letterId', $shared->id)->assertDispatched('correspondence-open');
    }

    public function test_the_workspace_event_updates_the_parent_list_state_and_ignores_unreachable_ids(): void
    {
        $author = $this->user();
        $mine = $this->letter($author, ['subject' => 'mine']);
        $foreign = $this->letter($this->user(), ['subject' => 'foreign']);

        $component = Livewire::actingAs($author)->test(Main::class)
            ->dispatch('correspondence-letter-updated', id: $mine->id, watch: true)
            ->assertSet('letterId', $mine->id)
            ->assertSet('watchedId', $mine->id)
            ->dispatch('correspondence-letter-updated', id: $foreign->id)
            ->assertSet('letterId', null)
            ->dispatch('correspondence-letter-updated', id: $mine->id)
            ->dispatch('correspondence-letter-updated', id: null)
            ->assertSet('letterId', null);
        $component->dispatch('correspondence-letter-updated', id: $mine->id, watch: false)->assertSet('watchedId', null);
    }

    public function test_a_forged_watch_flag_never_sets_the_watched_id_for_an_unreachable_letter(): void
    {
        $author = $this->user();
        $foreign = $this->letter($this->user(), ['subject' => 'foreign']);

        Livewire::actingAs($author)->test(Main::class)
            ->dispatch('correspondence-letter-updated', id: $foreign->id, watch: true)
            ->assertSet('letterId', null)
            ->assertSet('watchedId', null);
    }

    public function test_workspace_mutations_announce_the_letter_to_the_parent(): void
    {
        $author = $this->user();
        $approver = $this->user();
        $draft = $this->letter($author);
        $shared = $this->shared($author, [$this->party($approver)]);
        $approved = $this->letter($author, ['status' => Correspondence::STATUS_APPROVED, 'number' => 'ANN-' . uniqid()]);

        Livewire::actingAs($author)->test(Workspace::class, ['letterId' => $draft->id])
            ->call('rename', 'موضوع تازه')
            ->assertDispatched('correspondence-letter-updated');
        Livewire::actingAs($approver)->test(Workspace::class, ['letterId' => $shared->id])
            ->set('commentText', 'نظر')
            ->call('commentLetter')
            ->assertDispatched('correspondence-letter-updated');
        Livewire::actingAs($author)->test(Workspace::class, ['letterId' => $approved->id])
            ->call('copyAsNew')
            ->assertDispatched('correspondence-letter-updated');
    }

    public function test_workspace_generate_announces_and_asks_the_parent_to_watch(): void
    {
        config(['app.off' => [], 'ai.gateway.key' => 'test-key', 'ai.gateway.url' => 'https://gateway.test/v1']);
        Queue::fake();

        Livewire::actingAs($this->user())->test(Workspace::class)
            ->set('form.prompt', 'Draft a short office letter about the approved request.')
            ->call('generate')
            ->assertDispatched('correspondence-letter-updated');
    }

    public function test_poll_status_announces_when_the_generating_letter_has_finished(): void
    {
        $author = $this->user();
        $letter = $this->letter($author, ['status' => Correspondence::STATUS_GENERATING]);
        $component = Livewire::actingAs($author)->test(Workspace::class, ['letterId' => $letter->id])
            ->assertSet('lastSeenStatus', Correspondence::STATUS_GENERATING)
            ->call('pollStatus')
            ->assertNotDispatched('correspondence-letter-updated');

        $letter->forceFill(['status' => Correspondence::STATUS_SAVED])->save();
        $component->call('pollStatus')
            ->assertSet('lastSeenStatus', Correspondence::STATUS_SAVED)
            ->assertDispatched('correspondence-letter-updated');
    }

    public function test_the_list_poller_runs_while_another_letter_generates_or_the_table_hides_the_workspace(): void
    {
        $author = $this->user();
        $other = $this->letter($author, ['subject' => 'other']);
        $generating = $this->letter($author, ['status' => Correspondence::STATUS_GENERATING]);

        $component = Livewire::actingAs($author)->test(Main::class);
        $this->assertStringContainsString('correspondence-list-polling', $component->html());

        $component->call('open', $other->id);
        $this->assertStringContainsString('correspondence-list-polling', $component->html());

        $component->call('open', $generating->id);
        $this->assertStringContainsString('correspondence-list-idle', $component->html());
        $this->assertStringNotContainsString('correspondence-list-polling', $component->html());

        $component->call('toggleView', 'table');
        $this->assertStringContainsString('correspondence-classic-poll', $component->html());
    }

    public function test_the_list_poll_tells_the_workspace_when_no_run_is_generating_any_more(): void
    {
        $author = $this->user();
        $letter = $this->letter($author, ['status' => Correspondence::STATUS_GENERATING]);
        $component = Livewire::actingAs($author)->test(Main::class);

        $component->call('pollList')->assertNotDispatched('correspondence-generation-ended');
        $letter->forceFill(['status' => Correspondence::STATUS_SAVED])->save();
        $component->call('pollList')->assertDispatched('correspondence-generation-ended');

        Livewire::actingAs($author)->test(Workspace::class)->dispatch('correspondence-generation-ended')->assertSuccessful();
    }

    public function test_the_workspace_component_name_is_covered_by_the_tenant_off_guard(): void
    {
        config(['app.off' => ['correspondence']]);
        $this->assertTrue(tenantOffName('dashboard.correspondence.main'));
        $this->assertTrue(tenantOffName('dashboard.correspondence.workspace'));

        config(['app.off' => []]);
        $this->assertFalse(tenantOffName('dashboard.correspondence.workspace'));
    }

    public function test_the_reply_event_resets_the_parent_to_a_new_letter_that_replies_to_the_parent(): void
    {
        $author = $this->user();
        $parent = $this->letter($author, ['subject' => 'نامه اصلی']);

        Livewire::actingAs($author)->test(Main::class)
            ->call('open', $parent->id)
            ->assertSet('letterId', $parent->id)
            ->dispatch('correspondence-reply', id: $parent->id)
            ->assertSet('letterId', null)
            ->assertSet('replyToId', $parent->id);

        Livewire::actingAs($author)->test(Workspace::class, ['replyToId' => $parent->id])
            ->assertSet('replyToId', $parent->id)
            ->assertSet('form.subject', 'پاسخ: نامه اصلی');
        Livewire::actingAs($this->user())->test(Workspace::class, ['replyToId' => $parent->id])->assertSet('replyToId', null);
    }

    public function test_a_reply_to_an_unviewable_parent_leaves_no_stale_reply_id_and_a_normal_save_succeeds(): void
    {
        $author = $this->user();
        $hiddenParent = $this->letter($this->user(), ['subject' => 'hidden parent']);
        $head = $this->makeHead();

        $component = Livewire::actingAs($author)->test(Workspace::class, ['replyToId' => $hiddenParent->id])
            ->assertSet('replyToId', null)
            ->set('form.letterheadId', $head->id)
            ->set('form.subject', 'نامه بدون والد')
            ->set('form.blocks', [['type' => 'text', 'text' => 'بدنه']])
            ->call('save');

        $this->assertNotNull($component->get('letterId'));
        $saved = Correspondence::query()->where('user_id', $author->id)->where('subject', 'نامه بدون والد')->first();
        $this->assertNotNull($saved);
        $this->assertNull($saved->in_reply_to_id);
    }

    public function test_the_first_save_announce_keeps_the_child_mounted_while_every_other_id_change_remounts(): void
    {
        $author = $this->user();
        $mine = $this->letter($author, ['subject' => 'mine']);
        $other = $this->letter($author, ['subject' => 'other']);

        $first = Livewire::actingAs($author)->test(Main::class);
        $epoch = $first->get('epoch');
        $first->dispatch('correspondence-letter-updated', id: $mine->id)->assertSet('letterId', $mine->id);
        $this->assertSame($epoch, $first->get('epoch'));

        $watched = Livewire::actingAs($author)->test(Main::class);
        $epoch = $watched->get('epoch');
        $watched->dispatch('correspondence-letter-updated', id: $mine->id, watch: true)->assertSet('letterId', $mine->id);
        $this->assertSame($epoch + 1, $watched->get('epoch'));

        $opened = Livewire::actingAs($author)->test(Main::class)->call('open', $mine->id);
        $epoch = $opened->get('epoch');
        $opened->call('open', $other->id)->assertSet('letterId', $other->id);
        $this->assertSame($epoch + 1, $opened->get('epoch'));

        $epoch = $opened->get('epoch');
        $opened->call('open', $other->id)->assertDispatched('correspondence-reload');
        $this->assertSame($epoch, $opened->get('epoch'));

        $opened->dispatch('correspondence-letter-updated', id: $mine->id);
        $this->assertSame($epoch + 1, $opened->get('epoch'));

        $epoch = $opened->get('epoch');
        $opened->call('close');
        $this->assertSame($epoch + 1, $opened->get('epoch'));
        $opened->call('blank');
        $this->assertSame($epoch + 2, $opened->get('epoch'));
    }

    public function test_the_view_switch_renders_the_scoped_skeleton_and_hides_the_view_roots_while_loading(): void
    {
        Livewire::withoutLazyLoading();

        $html = Livewire::actingAs($this->user())->test(Main::class)->html();

        $this->assertStringContainsString('wire:key="correspondence-switch-skeleton"', $html);
        $this->assertStringContainsString('wire:loading.remove', $html);
        $this->assertStringContainsString('toggleView,switchTab', $html);
    }

    public function test_the_two_layouts_key_the_workspace_differently_so_toggling_never_leaves_a_blank_stub(): void
    {
        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($this->user())->test(Main::class);

        $report = $component->set('activeTab', 'report')->html();
        $this->assertStringContainsString('correspondence-workspace-report-', $report);
        $this->assertStringNotContainsString('correspondence-workspace-compose-', $report);

        $compose = $component->set('activeTab', 'compose')->html();
        $this->assertStringContainsString('correspondence-workspace-compose-', $compose);
        $this->assertStringNotContainsString('correspondence-workspace-report-', $compose);
    }

    public function test_opening_a_shared_letter_stamps_the_party_and_event_once_but_not_for_the_author(): void
    {
        $author = $this->user();
        $approver = $this->user();
        $letter = $this->shared($author, [$this->party($approver)]);

        Livewire::actingAs($approver)->test(Workspace::class, ['letterId' => $letter->id])->call('reload');
        $opened = $letter->fresh();
        $this->assertNotEmpty($opened->parties[0]['opened_at']);
        $openedEvents = array_filter($opened->events, fn (array $event) => ($event['action'] ?? null) === 'opened');
        $this->assertCount(1, $openedEvents);

        $authorLetter = $this->shared($author, [$this->party($approver)], ['subject' => 'second letter']);
        Livewire::actingAs($author)->test(Workspace::class, ['letterId' => $authorLetter->id]);
        $this->assertArrayNotHasKey('opened_at', $authorLetter->fresh()->parties[0]);
    }

    public function test_per_letter_text_survives_reloading_the_same_letter(): void
    {
        $author = $this->user();
        $approver = $this->user();
        $first = $this->shared($author, [$this->party($approver)]);

        Livewire::actingAs($approver)->test(Workspace::class, ['letterId' => $first->id])
            ->set('commentText', 'draft note')
            ->set('rejectReason', 'why')
            ->set('resumeRoute', true)
            ->call('reload')
            ->assertSet('commentText', 'draft note');
    }

    public function test_opening_another_letter_remounts_the_workspace_with_clean_per_letter_text(): void
    {
        $author = $this->user();
        $approver = $this->user();
        $first = $this->shared($author, [$this->party($approver)]);
        $second = $this->shared($author, [$this->party($approver)], ['subject' => 'second']);

        Livewire::actingAs($approver)->test(Workspace::class, ['letterId' => $first->id])->set('commentText', 'draft note');
        Livewire::actingAs($approver)->test(Workspace::class, ['letterId' => $second->id])
            ->assertSet('commentText', '')
            ->assertSet('rejectReason', '')
            ->assertSet('resumeRoute', false);
    }

    public function test_main_close_clears_the_open_letter_and_remounts_the_workspace(): void
    {
        $author = $this->user();
        $approver = $this->user();
        $letter = $this->shared($author, [$this->party($approver)]);

        $component = Livewire::actingAs($approver)->test(Main::class)->call('open', $letter->id)->assertSet('letterId', $letter->id);
        $epoch = $component->get('epoch');
        $component->call('close')->assertSet('letterId', null)->assertSet('replyToId', null);
        $this->assertSame($epoch + 1, $component->get('epoch'));
        $component->call('open', $letter->id)->call('blank')->assertSet('letterId', null);
    }

    public function test_deleting_a_draft_clears_the_workspace_and_tells_the_parent(): void
    {
        $author = $this->user();
        $letter = $this->letter($author);

        Livewire::actingAs($author)->test(Workspace::class, ['letterId' => $letter->id])
            ->set('deliveryNote', 'note')
            ->call('deleteDraft')
            ->assertSet('letterId', null)
            ->assertSet('deliveryNote', '')
            ->assertDispatched('correspondence-letter-updated');
        $this->assertNull(Correspondence::query()->find($letter->id));
    }

    public function test_a_registry_open_leaves_the_table_tab_for_the_report(): void
    {
        $author = $this->user();
        $approver = $this->user();
        $first = $this->shared($author, [$this->party($approver)]);

        $clerk = $this->registryUser();
        Livewire::actingAs($clerk)->test(Main::class)
            ->set('activeTab', 'table')
            ->call('open', $first->id)
            ->assertSet('letterId', $first->id)
            ->assertSet('activeTab', 'report');
        Livewire::actingAs($clerk)->test(Workspace::class, ['letterId' => $first->id])
            ->assertSet('registryOnly', true);
    }

    public function test_the_viewer_state_lists_attachment_names_by_index_only_for_viewers(): void
    {
        $author = $this->user();
        $approver = $this->user();
        $stranger = $this->user();
        $letter = $this->shared($author, [$this->party($approver)]);
        $path = "correspondence/{$letter->id}/report.txt";
        Storage::disk('local')->put($path, 'file');
        $letter->forceFill(['spec' => [...$letter->spec, 'attachments' => [['path' => $path, 'name' => 'report.txt', 'mime' => 'text/plain', 'size' => 4]]]])->save();

        $this->actingAs($approver);
        $presenter = new CorrespondencePresenter();
        $this->assertSame([0 => 'report.txt'], $presenter->viewerState($letter->fresh(), (int) $approver->id)['files']);
        $this->actingAs($stranger);
        $this->assertSame([], $presenter->attachmentNames($letter->fresh()));
    }

    public function test_presenter_tab_items_carry_icons_and_paper_margins_are_floats(): void
    {
        $author = $this->user();
        $this->actingAs($author);
        $presenter = new CorrespondencePresenter();

        $this->assertSame(['table_rows', 'edit_note'], array_column(array_reverse($presenter->classicTabItems()), 'icon'));
        $this->assertSame('inbox', $presenter->listTabItems()[0]['icon']);
        $this->assertSame((int) config('ai.correspondence.poll_seconds'), $presenter->pollSeconds());
        $this->assertContainsOnlyFloat($presenter->paperData($this->letter($author), 'link')['margins']);
    }

    public function test_the_reply_chip_shows_the_parent_subject_when_the_viewer_can_see_the_parent(): void
    {
        $author = $this->user();
        $parent = $this->letter($author, ['subject' => 'نامه اصلی']);
        $child = $this->letter($author, ['in_reply_to_id' => $parent->id]);
        $this->actingAs($author);

        $reply = (new CorrespondencePresenter())->viewerState($child->fresh(), (int) $author->id)['reply'];

        $this->assertSame('نامه اصلی', $reply['label']);
        $this->assertSame(route('correspondence', ['r' => $parent->id]), $reply['url']);
    }

    public function test_the_reply_chip_is_hidden_when_the_viewer_cannot_see_the_parent(): void
    {
        $parentAuthor = $this->user();
        $replier = $this->user();
        $parent = $this->letter($parentAuthor, ['subject' => 'نامه خصوصی']);
        $child = $this->letter($replier, ['in_reply_to_id' => $parent->id]);
        $this->actingAs($replier);

        $this->assertNull((new CorrespondencePresenter())->viewerState($child->fresh(), (int) $replier->id)['reply']);
    }

    public function test_a_repeated_printed_step_adds_no_second_event(): void
    {
        $author = $this->user();
        $wet = $this->letter($author, ['status' => Correspondence::STATUS_APPROVED, 'sign_mode' => Correspondence::SIGN_MODE_WET]);
        $this->act($author, $wet, 'printed');
        $this->act($author, $wet, 'printed');

        $this->assertCount(1, $wet->fresh()->events);
    }

    public function test_manual_deliveries_are_refused_once_the_event_log_is_full(): void
    {
        $author = $this->user();
        $party = ['user_id' => null, 'role' => LetterForm::ROLE_FYI, 'name' => 'بیرونی', 'org' => '', 'email' => 'o@example.org'];
        $busy = $this->letter($author, [
            'status' => Correspondence::STATUS_APPROVED,
            'parties' => [$party],
            'events' => array_fill(0, 200, ['at' => now()->toIso8601String(), 'user_id' => $author->id, 'action' => 'delivered_manually', 'text' => 'x']),
        ]);
        $this->expectValidation(fn () => $this->act($author, $busy, 'delivered_manually', ['party' => 0, 'text' => 'again']), 'deliveryNote');
    }

    public function test_the_timeline_shows_the_latest_thirty_events_and_counts_the_hidden_ones(): void
    {
        $author = $this->user();
        $long = $this->letter($author, [
            'events' => array_fill(0, 35, ['at' => now()->toIso8601String(), 'user_id' => $author->id, 'action' => 'commented', 'text' => 'x']),
        ]);
        $this->actingAs($author);
        $state = (new CorrespondencePresenter())->viewerState($long->fresh(), (int) $author->id);
        $this->assertCount(30, $state['events']);
        $this->assertSame(5, $state['events_hidden']);
    }

    public function test_without_signatures_removes_opened_stamps_when_a_route_is_shared_again(): void
    {
        $author = $this->user();
        $approver = $this->user();
        $letter = $this->letter($author, ['parties' => [
            $this->party($approver, LetterForm::ROLE_APPROVER, ['opened_at' => now()->toIso8601String()]),
        ]]);

        $this->act($author, $letter, 'share');

        $this->assertArrayNotHasKey('opened_at', $letter->fresh()->parties[0]);
    }

    public function test_sign_code_wrong_attempts_are_scoped_to_the_actor_target_pair_and_recorded_after_rollback(): void
    {
        $author = $this->user();
        $approver = $this->user();
        $proxy = $this->user();
        $approver->setExtraValue('admin.sign_code_hash', Hash::make('secret-123'));
        $approver->save();
        $first = $this->letter($author, ['route_mode' => Correspondence::ROUTE_PARALLEL, 'parties' => [
            $this->party($approver), $this->party($proxy, LetterForm::ROLE_FYI),
        ]]);
        $second = $this->letter($author, ['route_mode' => Correspondence::ROUTE_PARALLEL, 'parties' => [
            $this->party($approver), $this->party($proxy, LetterForm::ROLE_FYI),
        ]]);
        $this->act($author, $first, 'share');
        $this->act($author, $second, 'share');

        $pairKey = "sign-code:pair:{$proxy->id}:{$approver->id}";
        $otherPair = "sign-code:pair:{$proxy->id}:" . ($approver->id + 1);
        $this->expectValidation(fn () => $this->act($proxy, $first, 'approve', ['target_id' => $approver->id, 'code' => 'wrong']), 'signCode');

        $this->assertSame(1, RateLimiter::attempts($pairKey));
        $this->assertSame(0, RateLimiter::attempts($otherPair));
        $this->assertSame(1, RateLimiter::attempts("sign-code:actor:{$proxy->id}"));
        $this->assertSame(Correspondence::STATUS_SHARED, $first->fresh()->status);
        $this->assertArrayNotHasKey('signed_at', $first->fresh()->parties[0]);

        $this->expectValidation(fn () => $this->act($proxy, $second, 'approve', ['target_id' => $approver->id, 'code' => 'wrong']), 'signCode');
        $this->assertSame(2, RateLimiter::attempts($pairKey));
    }

    public function test_content_hash_covers_type_direction_urgency_and_letterhead(): void
    {
        $author = $this->user();
        $letter = $this->letter($author);
        $original = LetterRules::contentHash($letter);

        foreach ([
            ['type' => 'certificate'],
            ['direction' => Correspondence::DIRECTION_IN],
            ['urgency' => Correspondence::URGENCY_URGENT],
            ['letterhead_id' => $this->makeHead()->id],
        ] as $change) {
            $changed = clone $letter;
            $changed->forceFill($change);
            $this->assertNotSame($original, LetterRules::contentHash($changed));
        }
    }

    public function test_main_table_search_finds_numbers_normalizes_digits_and_hides_confidential_subjects(): void
    {
        $owner = $this->user();
        $viewer = $this->user();
        $numbered = $this->letter($owner, [
            'status' => Correspondence::STATUS_APPROVED,
            'number' => 'L/1405/0012',
            'recipients' => [$viewer->id],
        ]);
        $secret = $this->letter($owner, [
            'status' => Correspondence::STATUS_APPROVED,
            'classification' => Correspondence::CLASSIFICATION_SECRET,
            'subject' => 'پروژه محرمانه unique-subject',
            'recipients' => [$viewer->id],
        ]);
        $secret->forceFill(['search_text' => 'private-search-token'])->save();

        $component = Livewire::actingAs($viewer)->test(Main::class, ['activeTab' => 'table', 'listTab' => 'all'])
            ->set('search', '۰۰۱۲');
        $ids = collect($component->instance()->letters)->pluck('id')->all();
        $this->assertContains($numbered->id, $ids);

        $component->set('search', 'unique-subject');
        $ids = collect($component->instance()->letters)->pluck('id')->all();
        $this->assertNotContains($secret->id, $ids);

        $component->set('search', 'private-search-token');
        $ids = collect($component->instance()->letters)->pluck('id')->all();
        $this->assertNotContains($secret->id, $ids);
    }

    public function test_main_search_is_capped_and_clear_filters_resets_search_filter_and_list_tab(): void
    {
        $user = $this->user();
        $component = Livewire::actingAs($user)->test(Main::class, ['activeTab' => 'table', 'filter' => 'approved', 'listTab' => 'mine'])
            ->set('search', str_repeat('x', 80));

        $this->assertSame(64, mb_strlen($component->get('search')));
        $component->assertSet('filter', 'approved')->assertSet('listTab', 'mine')->call('clearFilters')
            ->assertSet('filter', 'all')
            ->assertSet('search', '')
            ->assertSet('listTab', 'waiting');
    }

    public function test_waiting_tab_uses_a_lookahead_row_for_has_more(): void
    {
        $author = $this->user();
        $viewer = $this->user();
        foreach (range(1, 31) as $index) {
            $this->letter($author, [
                'status' => Correspondence::STATUS_SHARED,
                'route_mode' => Correspondence::ROUTE_SEQUENTIAL,
                'awaiting_user_id' => $viewer->id,
                'parties' => [$this->party($viewer)],
                'subject' => "waiting-{$index}",
            ]);
        }

        $component = Livewire::actingAs($viewer)->test(Main::class, ['activeTab' => 'table']);
        $this->assertCount(30, $component->instance()->letters);
        $this->assertTrue($component->instance()->hasMore);
        $component->call('loadMore');
        $this->assertCount(31, $component->instance()->letters);
        $this->assertFalse($component->instance()->hasMore);
    }

    public function test_registry_receive_persists_the_incoming_form_before_numbering_it(): void
    {
        $registry = $this->registryUser();
        $head = $this->makeHead(Letterhead::KIND_REGISTRY);

        $component = Livewire::actingAs($registry)->test(Workspace::class)
            ->set('form.type', 'incoming')
            ->set('form.letterheadId', $head->id)
            ->set('form.subject', 'نامه وارده آزمون')
            ->set('form.senderCategory', Correspondence::CATEGORY_AUTHORITY)
            ->set('form.theirNumber', 'IN-42')
            ->set('form.theirDate', '1405/02/03')
            ->set('form.sender', 'سازمان فرستنده')
            ->set('form.blocks', [['type' => 'text', 'text' => 'بدنه وارده']])
            ->call('receiveLetter');

        $saved = Correspondence::query()->where('user_id', $registry->id)->where('their_number', 'IN-42')->firstOrFail();
        $this->assertSame(Correspondence::STATUS_RECEIVED, $saved->status);
        $this->assertNotEmpty($saved->number);
        $this->assertSame($saved->id, $component->get('letterId'));
    }

    public function test_workspace_quota_exposes_the_user_counter_and_locked_route_state_cannot_be_changed(): void
    {
        $user = $this->user();
        Correspondence::consumeQuota($user->id);

        $component = Livewire::actingAs($user)->test(Workspace::class);
        $this->assertSame(['used' => 1, 'cap' => Correspondence::quotaCap()], $component->instance()->quota);

        $this->expectException(CannotUpdateLockedPropertyException::class);
        $component->set('letterId', 987);
    }

    public function test_main_letter_id_cannot_be_written_by_the_client(): void
    {
        $component = Livewire::actingAs($this->user())->test(Main::class);

        $this->expectException(CannotUpdateLockedPropertyException::class);
        $component->set('letterId', 987);
    }

    public function test_letter_form_normalizes_dates_builds_a_payload_and_drops_unrecognized_party_state(): void
    {
        $form = Livewire::actingAs($this->user())->test(Workspace::class)->instance()->form;
        $form->deadline = '۱۴۰۵-۰۲-۰۳';
        $form->theirDate = '1405-02-04';
        $form->recipientOrg = 'سازمان';
        $form->recipientPerson = 'گیرنده';
        $form->parties = [['user_id' => 12, 'role' => LetterForm::ROLE_APPROVER, 'name' => 'امضاکننده', 'signed_at' => 'forged']];
        $form->normalize();

        $payload = $form->payload();
        $this->assertSame('1405/02/03', $form->deadline);
        $this->assertSame('1405/02/04', $form->theirDate);
        $this->assertSame(['organization' => 'سازمان', 'person' => 'گیرنده', 'title' => ''], $payload['recipient']);
        $this->assertSame([array_replace(LetterForm::blankParty(), ['user_id' => 12, 'role' => LetterForm::ROLE_APPROVER, 'name' => 'امضاکننده'])], LetterForm::partyRows($form->parties));
        $this->assertSame(['user_id' => null, 'role' => LetterForm::ROLE_FYI, 'print_in_cc' => false, 'name' => '', 'org' => '', 'title' => '', 'email' => '', 'address' => '', 'delivery' => 'link', 'order' => 0], LetterForm::blankParty());
    }

    public function test_presenter_waiting_and_composer_state_reflect_route_quota_and_ai_gates(): void
    {
        $approver = $this->user();
        $letter = $this->letter($this->user(), [
            'status' => Correspondence::STATUS_SHARED,
            'route_mode' => Correspondence::ROUTE_PARALLEL,
            'parties' => [$this->party($approver)],
        ]);
        $presenter = new CorrespondencePresenter();
        $this->assertTrue(CorrespondencePresenter::waitingForMe($letter, (int) $approver->id));

        $state = $presenter->composerState(null, true, true, ['used' => 2, 'cap' => 6], false, true);
        $this->assertTrue($state['canSubmit']);
        $this->assertSame(4, $state['quotaLeft']);
        $this->assertSame(33, $state['quotaPct']);

        config(['app.off' => ['correspondence:ai']]);
        $hidden = $presenter->composerState(null, true, true, ['used' => 2, 'cap' => 6], false, true);
        $this->assertFalse($hidden['canSubmit']);
        $this->assertFalse($hidden['smartOn']);
    }

    public function test_comments_are_limited_to_commentable_statuses(): void
    {
        $author = $this->user();
        foreach (Correspondence::COMMENTABLE as $status) {
            $letter = $this->letter($author, ['status' => $status]);
            $this->act($author, $letter, 'comment', ['text' => 'یادداشت']);
            $this->assertSame('commented', $letter->fresh()->events[0]['action']);
        }

        $draft = $this->letter($author, ['status' => Correspondence::STATUS_DRAFT]);
        $this->expectValidation(fn () => $this->act($author, $draft, 'comment', ['text' => 'یادداشت']), 'status');
    }

    public function test_mail_text_is_plain_bounded_clearable_and_preserves_other_meta(): void
    {
        $author = $this->user();
        $external = ['user_id' => null, 'role' => LetterForm::ROLE_FYI, 'name' => 'گیرنده', 'email' => 'outside@example.test', 'delivery' => 'link'];
        $letter = $this->letter($author, [
            'status' => Correspondence::STATUS_APPROVED,
            'meta' => ['snapshot' => ['version' => 1]],
            'parties' => [$external],
        ]);

        $this->act($author, $letter, 'mail_text', ['subject' => "  موضوع\nبیش از حد ", 'body' => "سطر نخست\n\n\n<b>سطر دوم</b>"]);
        $mail = $letter->fresh()->meta['mail'];
        $this->assertLessThanOrEqual(LetterRules::MAIL_SUBJECT_MAX, mb_strlen($mail['subject']));
        $this->assertLessThanOrEqual(LetterRules::MAIL_BODY_MAX, mb_strlen($mail['body']));
        $this->assertStringNotContainsString('<b>', $mail['body']);
        $this->assertSame(['version' => 1], $letter->fresh()->meta['snapshot']);

        $this->act($author, $letter, 'mail_text', ['subject' => '', 'body' => '']);
        $this->assertArrayNotHasKey('mail', $letter->fresh()->meta);
        $this->assertSame(['version' => 1], $letter->fresh()->meta['snapshot']);
    }

    public function test_incoming_records_can_close_or_void_after_registration_and_correction_is_received_only(): void
    {
        $registry = $this->registryUser();
        $received = $this->letter($registry, [
            'direction' => Correspondence::DIRECTION_IN,
            'type' => 'incoming',
            'letterhead_id' => $this->makeHead(Letterhead::KIND_REGISTRY)->id,
            'status' => Correspondence::STATUS_RECEIVED,
            'subject' => 'نامه ثبت‌شده',
            'their_number' => 'IN-77',
            'their_date' => '1405/02/03',
            'spec' => ['blocks' => [['type' => 'text', 'text' => 'بدنه']], 'sender' => ['name' => 'مرجع']],
        ]);

        $this->act($registry, $received, 'correct', ['fields' => ['subject' => 'عنوان   اصلاح‌شده']]);
        $this->act($registry, $received, 'correct', ['fields' => ['subject' => 'عنوان اصلاح‌شده']]);
        $this->assertSame('عنوان اصلاح‌شده', $received->fresh()->subject);
        $this->act($registry, $received, 'close');
        $this->assertSame(Correspondence::STATUS_DONE, $received->fresh()->status);
        $this->expectValidation(fn () => $this->act($registry, $received, 'correct', ['fields' => ['subject' => 'too late']]), 'status');

        $routed = $this->letter($registry, ['direction' => Correspondence::DIRECTION_IN, 'type' => 'incoming', 'status' => Correspondence::STATUS_ROUTED]);
        $this->act($registry, $routed, 'void');
        $this->assertSame(Correspondence::STATUS_VOID, $routed->fresh()->status);
    }

    public function test_share_rejects_an_inactive_letterhead_and_a_past_deadline(): void
    {
        $author = $this->user();
        $approver = $this->user();
        $inactive = $this->makeHead();
        $letter = $this->letter($author, ['letterhead_id' => $inactive->id, 'parties' => [$this->party($approver)]]);
        $inactive->forceFill(['active' => false])->save();
        $this->expectValidation(fn () => $this->act($author, $letter, 'share'), 'letterhead_id');

        $past = $this->letter($author, [
            'deadline_at' => now()->subDay(),
            'parties' => [$this->party($approver)],
        ]);
        $this->expectValidation(fn () => $this->act($author, $past, 'share'), 'form');
    }

    public function test_ai_generation_actions_return_404_when_the_tenant_hides_the_ai_view(): void
    {
        config(['app.off' => ['correspondence:ai']]);
        $user = $this->user();

        Livewire::actingAs($user)->test(Workspace::class)->call('generate')->assertStatus(404);
        Livewire::actingAs($user)->test(Workspace::class)->call('refine', 0)->assertStatus(404);
        Livewire::actingAs($user)->test(Workspace::class)->call('retry')->assertStatus(404);
    }

    public function test_generate_action_starts_one_queued_run_and_records_its_settings(): void
    {
        config(['app.off' => [], 'ai.gateway.key' => 'test-key', 'ai.gateway.url' => 'https://gateway.test/v1']);
        Queue::fake();
        $user = $this->user();

        Livewire::actingAs($user)->test(Workspace::class)
            ->set('form.prompt', 'Draft a short office letter about the approved request.')
            ->set('form.audience', 'internal')
            ->set('form.tone', 'firm')
            ->call('generate')
            ->assertHasNoErrors();

        $letter = Correspondence::query()->where('user_id', $user->id)->where('status', Correspondence::STATUS_GENERATING)->firstOrFail();
        $run = $letter->lastVersion();
        $this->assertTrue($run['ai']);
        $this->assertSame('generate', $run['kind']);
        $this->assertSame('internal', $run['audience']);
        $this->assertSame('firm', $run['tone']);
        Queue::assertPushed(GenerateCorrespondenceJob::class, fn (GenerateCorrespondenceJob $job): bool => $job->letterId === (int) $letter->id && $job->run === $run['run']);
    }

    public function test_generate_inside_the_cooldown_is_refused_as_busy_without_queueing_and_works_after_it_clears(): void
    {
        $this->aiOn();
        config(['ai.correspondence.cooldown' => 3]);
        $user = $this->user();
        $prompt = 'Draft a short office letter about the approved request.';

        Livewire::actingAs($user)->test(Workspace::class)->set('form.prompt', $prompt)->call('generate')->assertHasNoErrors();
        $this->assertTrue(Cache::has("correspondence:ai:cooldown:{$user->id}"));
        Correspondence::query()->where('user_id', $user->id)->update(['status' => Correspondence::STATUS_FAILED]);

        Livewire::actingAs($user)->test(Workspace::class)->set('form.prompt', $prompt)->call('generate')->assertHasErrors(['form.prompt']);
        Queue::assertPushed(GenerateCorrespondenceJob::class, 1);
        $this->assertSame(1, Correspondence::quotaUsed($user->id));

        Cache::forget("correspondence:ai:cooldown:{$user->id}");
        Livewire::actingAs($user)->test(Workspace::class)->set('form.prompt', $prompt)->call('generate')->assertHasNoErrors();
        Queue::assertPushed(GenerateCorrespondenceJob::class, 2);
    }

    public function test_template_toggle_preserves_existing_meta_and_adds_its_scope_metadata(): void
    {
        $author = $this->user();
        $letter = $this->letter($author, ['meta' => ['mail' => ['subject' => 'keep'], 'custom' => 'preserve']]);

        app(TemplateAction::class)->toggleTemplate($author, (int) $letter->id, 'Reusable template', 'private');

        $fresh = $letter->fresh();
        $this->assertTrue($fresh->is_template);
        $this->assertSame('Reusable template', $fresh->meta['template_name']);
        $this->assertSame('private', $fresh->meta['template_scope']);
        $this->assertSame('keep', $fresh->meta['mail']['subject']);
        $this->assertSame('preserve', $fresh->meta['custom']);
    }

    public function test_external_link_send_remind_and_revoke_obey_email_expiry_and_sending_gates(): void
    {
        Queue::fake();
        $author = $this->user();
        $external = ['user_id' => null, 'role' => LetterForm::ROLE_FYI, 'name' => 'مخاطب', 'email' => 'not-an-email', 'delivery' => 'link'];
        $letter = $this->letter($author, ['status' => Correspondence::STATUS_APPROVED, 'parties' => [$external]]);
        $this->actingAs($author);

        $this->expectValidation(fn () => app(DeliverLinkAction::class)->execute((int) $letter->id, 0, 'send'), 'form');
        $external['email'] = 'outside@example.test';
        $letter->forceFill(['parties' => [$external]])->saveQuietly();

        $confidential = $this->letter($author, ['status' => Correspondence::STATUS_APPROVED, 'classification' => Correspondence::CLASSIFICATION_CONFIDENTIAL, 'parties' => [$external]]);
        $this->expectValidation(fn () => app(DeliverLinkAction::class)->execute((int) $confidential->id, 0, 'send'), 'status');
        $wet = $this->letter($author, ['status' => Correspondence::STATUS_APPROVED, 'sign_mode' => Correspondence::SIGN_MODE_WET, 'parties' => [$external]]);
        $this->expectValidation(fn () => app(DeliverLinkAction::class)->execute((int) $wet->id, 0, 'send'), 'status');

        app(DeliverLinkAction::class)->execute((int) $letter->id, 0, 'send');
        Queue::assertPushed(SendCorrespondenceJob::class, fn (SendCorrespondenceJob $job): bool => $job->letterId === (int) $letter->id && !$job->resend);

        $sent = [...$external, 'sent_at' => now()->subDays(2)->toIso8601String(), 'token_hash' => str_repeat('a', 64), 'expires_at' => now()->subDay()->toIso8601String()];
        $letter->forceFill(['parties' => [$sent]])->saveQuietly();
        app(DeliverLinkAction::class)->execute((int) $letter->id, 0, 'remind');
        Queue::assertPushed(SendCorrespondenceJob::class, fn (SendCorrespondenceJob $job): bool => $job->letterId === (int) $letter->id && $job->resend);

        $freshSending = [...$sent, 'delivery_error' => 'sending', 'sending_at' => now()->toIso8601String()];
        $letter->forceFill(['parties' => [$freshSending]])->saveQuietly();
        $this->expectValidation(fn () => app(DeliverLinkAction::class)->execute((int) $letter->id, 0, 'revoke'), 'status');

        $staleSending = [...$sent, 'delivery_error' => 'mail_failed', 'sending_at' => now()->subMinutes(5)->toIso8601String()];
        $letter->forceFill(['parties' => [$staleSending]])->saveQuietly();
        app(DeliverLinkAction::class)->execute((int) $letter->id, 0, 'revoke');
        $revoked = $letter->fresh()->parties[0];
        $this->assertNull($revoked['token_hash']);
        $this->assertNull($revoked['expires_at']);
    }

    private function withCode(User $user, string $code = 'secret-123'): User
    {
        $user->setExtraValue('admin.sign_code_hash', Hash::make($code));
        $user->save();

        return $user;
    }

    private function parallelShared(User $author, array $parties, array $overrides = []): Correspondence
    {
        $first = collect($parties)->first(fn (array $party) => $party['role'] === LetterForm::ROLE_APPROVER && blank($party['signed_at'] ?? null));

        return $this->letter($author, array_replace([
            'status' => Correspondence::STATUS_SHARED,
            'route_mode' => Correspondence::ROUTE_PARALLEL,
            'awaiting_user_id' => $first['user_id'] ?? null,
            'parties' => $parties,
        ], $overrides));
    }

    private function viewerOf(User $viewer, Correspondence $letter): array
    {
        $this->actingAs($viewer);

        return (new CorrespondencePresenter())->viewerState($letter->fresh(), (int) $viewer->id);
    }

    private function external(array $extra = []): array
    {
        return array_replace(['user_id' => null, 'role' => LetterForm::ROLE_FYI, 'name' => 'مخاطب', 'email' => 'outside@example.test', 'delivery' => 'link'], $extra);
    }

    private function diffed(User $author, User $approver, string $status = Correspondence::STATUS_SHARED): Correspondence
    {
        return $this->letter($author, [
            'status' => $status,
            'awaiting_user_id' => $status === Correspondence::STATUS_SHARED ? $approver->id : null,
            'parties' => [$this->party($approver)],
            'versions' => [['user_id' => $author->id, 'at' => now()->subHour()->toIso8601String(), 'spec' => ['blocks' => [['type' => 'text', 'text' => 'متن قدیمی']]]]],
        ]);
    }

    public function test_next_step_names_who_the_letter_waits_for(): void
    {
        $author = $this->user();
        $approver = $this->user();
        $letter = $this->parallelShared($author, [$this->party($approver)], ['route_mode' => Correspondence::ROUTE_SEQUENTIAL]);

        $this->assertSame('در انتظار امضای ' . $approver->name, $this->viewerOf($author, $letter)['next']);
    }

    public function test_next_step_lists_the_pending_approvers_of_a_parallel_route(): void
    {
        $author = $this->user();
        $first = $this->user();
        $second = $this->user();
        $letter = $this->parallelShared($author, [$this->party($first), $this->party($second, LetterForm::ROLE_APPROVER, ['order' => 2])]);

        $this->assertSame('در انتظار امضای ' . $first->name . '، ' . $second->name, $this->viewerOf($author, $letter)['next']);
    }

    public function test_next_step_for_approved_rejected_and_draft_letters(): void
    {
        $author = $this->user();

        $this->assertSame('تأیید شده', $this->viewerOf($author, $this->letter($author, ['status' => Correspondence::STATUS_APPROVED]))['next']);
        $this->assertSame('رد شده', $this->viewerOf($author, $this->letter($author, ['status' => Correspondence::STATUS_REJECTED]))['next']);
        $this->assertSame('', $this->viewerOf($author, $this->letter($author))['next']);
    }

    public function test_next_step_says_ready_to_send_for_the_author_with_an_unsent_external_party(): void
    {
        $author = $this->user();
        $letter = $this->letter($author, ['status' => Correspondence::STATUS_APPROVED, 'parties' => [$this->external()]]);

        $this->assertSame('آماده ارسال', $this->viewerOf($author, $letter)['next']);
    }

    public function test_next_step_offers_retry_only_where_the_retry_button_exists(): void
    {
        $author = $this->user();
        $letter = $this->letter($author, ['status' => Correspondence::STATUS_APPROVED, 'parties' => [$this->external(['delivery_error' => 'mail_failed'])]]);

        $this->assertSame('ارسال ناموفق — تلاش دوباره', $this->viewerOf($author, $letter)['next']);
        $this->assertSame('ارسال ناموفق', $this->viewerOf($this->user(), $letter)['next']);
    }

    public function test_deadline_note_counts_calendar_days_left(): void
    {
        $author = $this->user();
        $letter = $this->letter($author, ['status' => Correspondence::STATUS_SHARED, 'deadline_at' => now()->addDays(3)->endOfDay()]);

        $this->assertSame(convertToPersian(3) . ' روز مانده', $this->viewerOf($author, $letter)['deadline_note']);
    }

    public function test_deadline_note_counts_calendar_days_overdue(): void
    {
        $author = $this->user();
        $letter = $this->letter($author, ['status' => Correspondence::STATUS_SHARED, 'deadline_at' => now()->subDays(2)->endOfDay()]);

        $this->assertSame(convertToPersian(2) . ' روز گذشته', $this->viewerOf($author, $letter)['deadline_note']);
    }

    public function test_deadline_note_says_today_for_an_end_of_day_deadline_later_today(): void
    {
        $author = $this->user();
        $letter = $this->letter($author, ['status' => Correspondence::STATUS_SHARED, 'deadline_at' => now()->endOfDay()]);

        $this->assertSame('امروز', $this->viewerOf($author, $letter)['deadline_note']);
    }

    public function test_deadline_note_says_one_day_overdue_for_yesterdays_end_of_day(): void
    {
        $author = $this->user();
        $letter = $this->letter($author, ['status' => Correspondence::STATUS_SHARED, 'deadline_at' => now()->subDay()->endOfDay()]);

        $this->assertSame(convertToPersian(1) . ' روز گذشته', $this->viewerOf($author, $letter)['deadline_note']);
    }

    public function test_deadline_note_says_one_day_left_for_tomorrows_end_of_day(): void
    {
        $author = $this->user();
        $letter = $this->letter($author, ['status' => Correspondence::STATUS_SHARED, 'deadline_at' => now()->addDay()->endOfDay()]);

        $this->assertSame(convertToPersian(1) . ' روز مانده', $this->viewerOf($author, $letter)['deadline_note']);
    }

    public function test_deadline_note_is_empty_when_not_shared_or_without_a_deadline(): void
    {
        $author = $this->user();

        $this->assertSame('', $this->viewerOf($author, $this->letter($author, ['status' => Correspondence::STATUS_APPROVED, 'deadline_at' => now()->addDays(3)]))['deadline_note']);
        $this->assertSame('', $this->viewerOf($author, $this->letter($author, ['status' => Correspondence::STATUS_SHARED]))['deadline_note']);
    }

    public function test_the_diff_shows_beside_approve_for_an_approver_without_the_toggle(): void
    {
        $author = $this->user();
        $approver = $this->user();
        $letter = $this->diffed($author, $approver);

        Livewire::actingAs($approver)->test(Workspace::class, ['letterId' => $letter->id])
            ->assertSeeHtml('wire:key="correspondence-diff-beside"')
            ->assertDontSee('نمایش تغییرات');
    }

    public function test_the_diff_stays_behind_the_toggle_when_the_viewer_cannot_approve(): void
    {
        $author = $this->user();
        $letter = $this->diffed($author, $this->user(), Correspondence::STATUS_APPROVED);

        Livewire::actingAs($author)->test(Workspace::class, ['letterId' => $letter->id])
            ->assertDontSeeHtml('wire:key="correspondence-diff-beside"')
            ->assertSee('نمایش تغییرات');
    }

    public function test_the_opened_status_carries_the_scanner_tooltip(): void
    {
        $author = $this->user();
        $sent = $this->external(['sent_at' => now()->subHour()->toIso8601String(), 'opened_at' => now()->toIso8601String(), 'token_hash' => str_repeat('a', 64), 'expires_at' => now()->addDay()->toIso8601String()]);
        $letter = $this->letter($author, ['status' => Correspondence::STATUS_APPROVED, 'parties' => [$sent]]);

        Livewire::actingAs($author)->test(Workspace::class, ['letterId' => $letter->id])
            ->assertSeeHtml('<span title="' . e(__('resources/correspondence/strings.viewer.opened_note')) . '">باز شد:</span>');
    }

    public function test_the_global_actor_cap_survives_a_success_on_another_target_and_then_blocks_every_target(): void
    {
        $author = $this->user();
        $first = $this->withCode($this->user());
        $second = $this->withCode($this->user());
        $secretary = $this->user();
        $letter = $this->parallelShared($author, [$this->party($first), $this->party($second), $this->party($secretary, LetterForm::ROLE_FYI)]);
        $actorKey = "sign-code:actor:{$secretary->id}";
        $cap = (int) config('ai.correspondence.sign_code_actor_attempts');
        $this->assertSame(15, $cap);

        for ($i = 0; $i < $cap - 1; $i++) {
            RateLimiter::hit($actorKey, 900);
        }
        $this->act($secretary, $letter, 'approve', ['target_id' => $first->id, 'code' => 'secret-123']);
        $this->assertNotEmpty($letter->fresh()->parties[0]['signed_at']);
        $this->assertSame($cap - 1, RateLimiter::attempts($actorKey));

        $this->expectValidation(fn () => $this->act($secretary, $letter, 'approve', ['target_id' => $second->id, 'code' => 'wrong']), 'signCode');
        $this->assertSame($cap, RateLimiter::attempts($actorKey));

        $blocked = $this->expectValidation(fn () => $this->act($secretary, $letter, 'approve', ['target_id' => $second->id, 'code' => 'secret-123']), 'signCode');
        $this->assertSame(__('resources/correspondence/strings.errors.too_many_attempts'), $blocked->errors()['signCode'][0]);
        $this->assertArrayNotHasKey('signed_at', $letter->fresh()->parties[1]);
    }

    public function test_a_hostile_colleagues_failures_never_block_the_legitimate_signer(): void
    {
        $author = $this->user();
        $manager = $this->withCode($this->user());
        $other = $this->user();
        $secretary = $this->user();
        $colleague = $this->user();
        $letter = $this->parallelShared($author, [
            $this->party($manager), $this->party($other),
            $this->party($secretary, LetterForm::ROLE_FYI), $this->party($colleague, LetterForm::ROLE_FYI),
        ]);

        for ($i = 0; $i < (int) config('ai.correspondence.sign_code_pair_attempts'); $i++) {
            $this->expectValidation(fn () => $this->act($colleague, $letter, 'approve', ['target_id' => $manager->id, 'code' => 'guess']), 'signCode');
        }
        $locked = $this->expectValidation(fn () => $this->act($colleague, $letter, 'approve', ['target_id' => $manager->id, 'code' => 'secret-123']), 'signCode');
        $this->assertSame(__('resources/correspondence/strings.errors.too_many_attempts'), $locked->errors()['signCode'][0]);

        $this->act($secretary, $letter, 'approve', ['target_id' => $manager->id, 'code' => 'secret-123']);
        $this->assertNotEmpty($letter->fresh()->parties[0]['signed_at']);
        $this->assertSame(0, RateLimiter::attempts("sign-code:pair:{$secretary->id}:{$manager->id}"));
    }

    public function test_sequential_letters_ignore_target_id_and_always_sign_for_the_awaiting_approver(): void
    {
        $author = $this->user();
        $first = $this->withCode($this->user());
        $second = $this->user();
        $proxy = $this->user();
        $letter = $this->letter($author, ['parties' => [$this->party($first), $this->party($second), $this->party($proxy, LetterForm::ROLE_FYI)]]);
        $this->act($author, $letter, 'share');

        $this->act($proxy, $letter, 'approve', ['target_id' => $second->id, 'code' => 'secret-123']);

        $parties = $letter->fresh()->parties;
        $this->assertNotEmpty($parties[0]['signed_at']);
        $this->assertArrayNotHasKey('signed_at', $parties[1]);
        $event = $letter->fresh()->events[array_key_last($letter->fresh()->events)];
        $this->assertSame('approved_instead', $event['action']);
        $this->assertSame((int) $first->id, $event['target_id']);
    }

    public function test_a_parallel_target_must_be_an_unsigned_approver_other_than_a_non_approver_itself(): void
    {
        $author = $this->user();
        $open = $this->withCode($this->user());
        $done = $this->user();
        $reader = $this->user();
        $proxy = $this->user();
        $letter = $this->parallelShared($author, [
            $this->party($open),
            $this->party($done, LetterForm::ROLE_APPROVER, ['signed_at' => now()->toIso8601String()]),
            $this->party($reader, LetterForm::ROLE_FYI),
            $this->party($proxy, LetterForm::ROLE_FYI),
        ]);
        $expected = __('resources/correspondence/strings.errors.not_awaiting');

        foreach ([$reader->id, $done->id, $proxy->id, 987654321] as $target) {
            $error = $this->expectValidation(fn () => $this->act($proxy, $letter, 'approve', ['target_id' => $target, 'code' => 'secret-123']), 'status');
            $this->assertSame($expected, $error->errors()['status'][0]);
        }
        $this->assertArrayNotHasKey('signed_at', $letter->fresh()->parties[0]);
        $this->assertSame(0, RateLimiter::attempts("sign-code:actor:{$proxy->id}"));
    }

    public function test_a_non_approver_without_a_positive_target_is_refused_in_a_parallel_letter(): void
    {
        $author = $this->user();
        $approver = $this->withCode($this->user());
        $other = $this->user();
        $proxy = $this->user();
        $letter = $this->parallelShared($author, [$this->party($approver), $this->party($other), $this->party($proxy, LetterForm::ROLE_FYI)]);

        foreach ([0, -3] as $target) {
            $this->expectValidation(fn () => $this->act($proxy, $letter, 'approve', ['target_id' => $target, 'code' => 'wrong']), 'status');
        }
        $this->assertArrayNotHasKey('signed_at', $letter->fresh()->parties[0]);
        $this->assertSame(0, RateLimiter::attempts("sign-code:actor:{$proxy->id}"));
    }

    public function test_an_unsigned_parallel_approver_with_a_zero_negative_or_own_target_signs_for_herself(): void
    {
        $author = $this->user();
        $first = $this->user();
        $second = $this->user();
        $third = $this->user();
        $last = $this->user();
        $letter = $this->parallelShared($author, [$this->party($first), $this->party($second), $this->party($third), $this->party($last)]);

        $this->act($first, $letter, 'approve', ['target_id' => 0]);
        $this->act($second, $letter, 'approve', ['target_id' => -4]);
        $this->act($third, $letter, 'approve', ['target_id' => $third->id]);

        $actions = array_column($letter->fresh()->events, 'action');
        $this->assertSame(['approved', 'approved', 'approved'], array_slice($actions, -3));
        $this->assertSame(0, RateLimiter::attempts("sign-code:actor:{$first->id}"));
    }

    public function test_the_blank_code_no_op_of_a_signed_approver_applies_only_without_a_target(): void
    {
        $author = $this->user();
        $signed = $this->user();
        $pending = $this->withCode($this->user());
        $letter = $this->parallelShared($author, [
            $this->party($signed, LetterForm::ROLE_APPROVER, ['signed_at' => now()->toIso8601String()]),
            $this->party($pending),
            $this->party($this->user()),
        ]);
        $before = count($letter->events ?? []);

        $this->act($signed, $letter, 'approve');
        $this->act($signed, $letter, 'approve', ['target_id' => 0]);
        $this->assertSame($before, count($letter->fresh()->events ?? []));

        $this->expectValidation(fn () => $this->act($signed, $letter, 'approve', ['target_id' => $pending->id]), 'signCode');
        $this->assertArrayNotHasKey('signed_at', $letter->fresh()->parties[1]);

        $this->act($signed, $letter, 'approve', ['target_id' => $pending->id, 'code' => 'secret-123']);
        $this->assertNotEmpty($letter->fresh()->parties[1]['signed_at']);
    }

    public function test_workspace_sends_the_sign_target_only_for_parallel_letters_and_resets_the_sign_fields(): void
    {
        $author = $this->user();
        $manager = $this->withCode($this->user());
        $other = $this->user();
        $proxy = $this->user();
        $parallel = $this->parallelShared($author, [$this->party($manager), $this->party($other), $this->party($proxy, LetterForm::ROLE_FYI)]);

        Livewire::actingAs($proxy)->test(Workspace::class, ['letterId' => $parallel->id])
            ->set('signTarget', $manager->id)
            ->set('signCode', 'secret-123')
            ->call('approveLetter')
            ->assertSet('signCode', '')
            ->assertSet('signTarget', 0);
        $this->assertNotEmpty($parallel->fresh()->parties[0]['signed_at']);
        $this->assertArrayNotHasKey('signed_at', $parallel->fresh()->parties[1]);

        $sequential = $this->letter($author, ['parties' => [$this->party($manager), $this->party($other), $this->party($proxy, LetterForm::ROLE_FYI)]]);
        $this->act($author, $sequential, 'share');
        Livewire::withoutLazyLoading();
        Livewire::actingAs($proxy)->test(Workspace::class, ['letterId' => $sequential->id])
            ->set('signTarget', $other->id)
            ->set('signCode', 'secret-123')
            ->call('approveLetter')
            ->assertSet('signTarget', 0);
        $parties = $sequential->fresh()->parties;
        $this->assertNotEmpty($parties[0]['signed_at']);
        $this->assertArrayNotHasKey('signed_at', $parties[1]);
    }

    public function test_viewer_state_lists_parallel_sign_targets_with_the_right_label_and_final_flag(): void
    {
        $author = $this->user();
        $a = $this->user();
        $b = $this->user();
        $reader = $this->user();
        $letter = $this->parallelShared($author, [$this->party($a), $this->party($b), $this->party($reader, LetterForm::ROLE_FYI)]);
        $presenter = new CorrespondencePresenter();

        $viewer = $this->viewerOf($a, $letter);
        $this->assertSame([(int) $b->id => $presenter->name($b->id)], $viewer['targets']);
        $this->assertTrue($viewer['approve']);
        $this->assertTrue($viewer['waiting']);
        $this->assertSame('تأیید', $viewer['approve_label']);
        $this->assertFalse($viewer['final']);
        $this->assertSame(2, $viewer['progress']['remaining']);

        $outsider = $this->viewerOf($reader, $letter);
        $this->assertEqualsCanonicalizing([(int) $a->id, (int) $b->id], array_keys($outsider['targets']));
        $this->assertTrue($outsider['approve']);
        $this->assertFalse($outsider['waiting']);
        $this->assertSame('تأیید به‌جای دیگران', $outsider['approve_label']);

        $letter->forceFill([
            'parties' => [$this->party($a, LetterForm::ROLE_APPROVER, ['signed_at' => now()->toIso8601String()]), $this->party($b), $this->party($reader, LetterForm::ROLE_FYI)],
            'awaiting_user_id' => $b->id,
        ])->save();

        $last = $this->viewerOf($b, $letter);
        $this->assertSame([], $last['targets']);
        $this->assertTrue($last['approve']);
        $this->assertTrue($last['final']);
        $this->assertSame(1, $last['progress']['remaining']);

        $signed = $this->viewerOf($a, $letter);
        $this->assertSame([(int) $b->id => $presenter->name($b->id)], $signed['targets']);
        $this->assertTrue($signed['approve']);
        $this->assertFalse($signed['waiting']);
        $this->assertSame('تأیید به‌جای دیگران', $signed['approve_label']);

        $letter->forceFill(['status' => Correspondence::STATUS_APPROVED])->save();
        $ended = $this->viewerOf($reader, $letter);
        $this->assertSame([], $ended['targets']);
        $this->assertFalse($ended['approve']);
        $this->assertNull($ended['progress']);
    }

    public function test_viewer_state_keeps_the_named_sequential_label_and_never_lists_targets(): void
    {
        $author = $this->user();
        $first = $this->user();
        $second = $this->user();
        $letter = $this->letter($author, ['parties' => [$this->party($first), $this->party($second)]]);
        $this->act($author, $letter, 'share');
        $presenter = new CorrespondencePresenter();

        $waiting = $this->viewerOf($first, $letter);
        $this->assertSame('تأیید', $waiting['approve_label']);
        $this->assertFalse($waiting['final']);
        $this->assertSame([], $waiting['targets']);

        $instead = $this->viewerOf($second, $letter);
        $this->assertTrue($instead['instead']);
        $this->assertTrue($instead['approve']);
        $this->assertSame('تأیید به‌جای ' . $presenter->name($first->id), $instead['approve_label']);
        $this->assertSame([], $instead['targets']);
    }

    public function test_route_actions_need_steward_rights_so_a_clerk_cannot_act_on_another_clerks_confidential_letter(): void
    {
        $owner = $this->registryUser();
        $other = $this->registryUser();
        $recipient = $this->user();
        $head = $this->makeHead(Letterhead::KIND_REGISTRY);
        $make = fn (array $overrides = []) => $this->letter($owner, array_replace([
            'direction' => Correspondence::DIRECTION_IN,
            'type' => 'incoming',
            'letterhead_id' => $head->id,
            'status' => Correspondence::STATUS_RECEIVED,
            'classification' => Correspondence::CLASSIFICATION_CONFIDENTIAL,
        ], $overrides));
        $route = ['parties' => [['user_id' => $recipient->id]], 'instruction' => 'برای بررسی'];

        foreach (['route' => $route, 'close' => [], 'void' => [], 'correct' => ['fields' => ['subject' => 'تغییر']]] as $transition => $payload) {
            $secret = $make();
            $this->expectDenied(fn () => $this->act($other, $secret, $transition, $payload));
            $this->assertSame(Correspondence::STATUS_RECEIVED, $secret->fresh()->status);
            $this->assertSame('آزمون مکاتبه', $secret->fresh()->subject);
        }

        $secret = $make();
        $this->assertFalse($this->viewerOf($other, $secret)['route']);
        $this->assertFalse($this->viewerOf($other, $secret)['correct']);
        $this->assertTrue($this->viewerOf($owner, $secret)['route']);
        $this->assertTrue($this->viewerOf($owner, $secret)['correct']);

        $this->act($owner, $secret, 'route', $route);
        $this->assertSame(Correspondence::STATUS_ROUTED, $secret->fresh()->status);

        $normal = $make(['classification' => Correspondence::CLASSIFICATION_NORMAL]);
        $this->act($other, $normal, 'route', $route);
        $this->assertSame(Correspondence::STATUS_ROUTED, $normal->fresh()->status);
    }

    public function test_workspace_route_letter_is_a_no_op_in_the_registry_only_view(): void
    {
        $owner = $this->registryUser();
        $clerk = $this->registryUser();
        $recipient = $this->user();
        $normal = $this->letter($owner, [
            'direction' => Correspondence::DIRECTION_IN,
            'type' => 'incoming',
            'letterhead_id' => $this->makeHead(Letterhead::KIND_REGISTRY)->id,
            'status' => Correspondence::STATUS_RECEIVED,
        ]);

        Livewire::actingAs($clerk)->test(Workspace::class, ['letterId' => $normal->id])
            ->assertSet('registryOnly', true)
            ->assertSet('readOnly', true)
            ->set('routeParties', [['user_id' => $recipient->id, 'role' => LetterForm::ROLE_FYI]])
            ->call('routeLetter');

        $this->assertSame(Correspondence::STATUS_RECEIVED, $normal->fresh()->status);
    }

    public function test_inherit_drops_an_inactive_or_wrong_kind_letterhead_and_falls_back_from_a_disabled_type(): void
    {
        $inactive = $this->makeHead();
        $inactive->forceFill(['active' => false])->save();
        $active = $this->makeHead();
        $registry = $this->makeHead(Letterhead::KIND_REGISTRY);
        $firstOut = array_key_first(array_filter((array) config('ai.correspondence.types'), fn ($type) => $type['enabled'] === true && $type['direction'] === 'out'));
        $source = (new Correspondence())->forceFill(['direction' => Correspondence::DIRECTION_OUT, 'type' => 'memo', 'letterhead_id' => $inactive->id]);

        $target = new Correspondence();
        LetterWrite::inherit($target, $source);
        $this->assertNull($target->letterhead_id);
        $this->assertSame($firstOut, $target->type);

        $source->forceFill(['type' => 'contract', 'letterhead_id' => $active->id]);
        $target = new Correspondence();
        LetterWrite::inherit($target, $source);
        $this->assertSame((int) $active->id, (int) $target->letterhead_id);
        $this->assertSame('contract', $target->type);

        $source->forceFill(['letterhead_id' => $registry->id]);
        $target = new Correspondence();
        LetterWrite::inherit($target, $source);
        $this->assertNull($target->letterhead_id);

        $incoming = (new Correspondence())->forceFill(['direction' => Correspondence::DIRECTION_IN, 'type' => 'incoming', 'letterhead_id' => $registry->id]);
        $target = new Correspondence();
        LetterWrite::inherit($target, $incoming);
        $this->assertSame((int) $registry->id, (int) $target->letterhead_id);
        $this->assertSame('incoming', $target->type);
    }

    public function test_lifecycle_events_may_reach_one_thousand_while_spammable_ones_stop_at_nine_hundred(): void
    {
        $author = $this->user();
        $approver = $this->user();
        $events = fn (int $count) => array_fill(0, $count, ['action' => 'existing', 'user_id' => $author->id, 'at' => now()->toIso8601String()]);
        $letter = $this->letter($author, ['status' => Correspondence::STATUS_SHARED, 'awaiting_user_id' => $approver->id, 'parties' => [$this->party($approver)], 'events' => $events(950)]);

        $this->expectValidation(fn () => $this->act($author, $letter, 'comment', ['text' => 'یادداشت']), 'form');
        $this->act($author, $letter, 'withdraw');
        $this->assertCount(951, $letter->fresh()->events);
        $this->assertSame('withdrawn', $letter->fresh()->events[950]['action']);

        $full = $this->letter($author, ['status' => Correspondence::STATUS_SHARED, 'awaiting_user_id' => $approver->id, 'parties' => [$this->party($approver)], 'events' => $events(1000)]);
        $this->expectValidation(fn () => $this->act($author, $full, 'withdraw'), 'form');
        $this->assertSame(Correspondence::STATUS_SHARED, $full->fresh()->status);
    }

    public function test_wet_and_manual_delivery_need_steward_rights(): void
    {
        $author = $this->user();
        $clerk = $this->registryUser();
        $stranger = $this->user();
        $wet = $this->letter($author, ['status' => Correspondence::STATUS_APPROVED, 'sign_mode' => Correspondence::SIGN_MODE_WET]);

        $this->expectDenied(fn () => $this->act($stranger, $wet, 'printed'));
        $this->act($clerk, $wet, 'printed');
        $this->assertSame('printed', $wet->fresh()->events[0]['action']);

        $secret = $this->letter($author, ['status' => Correspondence::STATUS_APPROVED, 'sign_mode' => Correspondence::SIGN_MODE_WET, 'classification' => Correspondence::CLASSIFICATION_CONFIDENTIAL]);
        $this->expectDenied(fn () => $this->act($clerk, $secret, 'printed'));
        $this->expectDenied(fn () => $this->act($clerk, $secret, 'delivered_manually', ['text' => 'تحویل شد']));

        $normal = $this->letter($author, ['status' => Correspondence::STATUS_APPROVED]);
        $this->expectDenied(fn () => $this->act($stranger, $normal, 'delivered_manually', ['text' => 'تحویل شد']));
        $this->act($clerk, $normal, 'delivered_manually', ['text' => 'تحویل شد']);
        $this->assertSame('delivered_manually', $normal->fresh()->events[0]['action']);
    }

    public function test_share_names_the_approver_without_a_signature_file_and_rejects_an_empty_body(): void
    {
        $this->autoSign = false;
        $author = $this->user();
        $approver = $this->user();
        $letter = $this->letter($author, ['parties' => [$this->party($approver)]]);

        $error = $this->expectValidation(fn () => $this->act($author, $letter, 'share'), 'status');
        $this->assertStringContainsString((string) $approver->name, $error->errors()['status'][0]);

        $empty = $this->letter($author, ['parties' => [$this->party($approver)], 'spec' => ['blocks' => [['type' => 'text', 'text' => '   ']]]]);
        $error = $this->expectValidation(fn () => $this->act($author, $empty, 'share'), 'form');
        $this->assertSame(__('resources/correspondence/strings.errors.empty_body'), $error->errors()['form'][0]);
    }

    public function test_link_is_author_only_approved_outgoing_and_hits_the_limiter_before_any_check(): void
    {
        Queue::fake();
        $author = $this->user();
        $stranger = $this->user();
        $external = ['user_id' => null, 'role' => LetterForm::ROLE_FYI, 'name' => 'مخاطب', 'email' => 'outside@example.test', 'delivery' => 'link'];
        $approved = $this->letter($author, ['status' => Correspondence::STATUS_APPROVED, 'parties' => [$external]]);
        $flow = app(DeliverLinkAction::class);

        $this->actingAs($stranger);
        $this->expectDenied(fn () => $flow->execute((int) $approved->id, 0, 'send'));

        $this->actingAs($author);
        $draft = $this->letter($author, ['parties' => [$external]]);
        $this->expectValidation(fn () => $flow->execute((int) $draft->id, 0, 'send'), 'status');
        $incoming = $this->letter($author, ['direction' => Correspondence::DIRECTION_IN, 'status' => Correspondence::STATUS_APPROVED, 'parties' => [$external]]);
        $this->expectValidation(fn () => $flow->execute((int) $incoming->id, 0, 'send'), 'status');
        $internal = $this->letter($author, ['status' => Correspondence::STATUS_APPROVED, 'parties' => [$this->party($stranger, LetterForm::ROLE_FYI)]]);
        $this->expectDenied(fn () => $flow->execute((int) $internal->id, 0, 'send'));
        $this->expectDenied(fn () => $flow->execute((int) $approved->id, 9, 'send'));
        Queue::assertNothingPushed();

        $limited = $this->letter($author, ['parties' => [$external]]);
        for ($i = 0; $i < 10; $i++) {
            $this->expectValidation(fn () => $flow->execute((int) $limited->id, 0, 'send'), 'status');
        }
        $error = $this->expectValidation(fn () => $flow->execute((int) $limited->id, 0, 'send'), 'form');
        $this->assertSame(__('resources/correspondence/strings.errors.too_many_attempts'), $error->errors()['form'][0]);
    }

    public function test_link_send_remind_and_revoke_follow_the_sent_opened_and_lapsed_states(): void
    {
        Queue::fake();
        $author = $this->user();
        $base = ['user_id' => null, 'role' => LetterForm::ROLE_FYI, 'name' => 'مخاطب', 'email' => 'outside@example.test', 'delivery' => 'link'];
        $sent = [...$base, 'sent_at' => now()->subDays(2)->toIso8601String(), 'token_hash' => str_repeat('a', 64)];
        $flow = app(DeliverLinkAction::class);
        $this->actingAs($author);
        $run = function (array $party, string $action) use ($author, $flow): Correspondence {
            $letter = $this->letter($author, ['status' => Correspondence::STATUS_APPROVED, 'parties' => [$party]]);
            $flow->execute((int) $letter->id, 0, $action);

            return $letter;
        };

        $this->expectValidation(fn () => $run($sent, 'send'), 'status');
        $this->expectValidation(fn () => $run($base, 'remind'), 'status');
        $this->expectValidation(fn () => $run($base, 'revoke'), 'status');
        $this->expectValidation(fn () => $run([...$sent, 'expires_at' => now()->addDay()->toIso8601String(), 'opened_at' => now()->toIso8601String()], 'remind'), 'status');
        Queue::assertNothingPushed();

        $run([...$sent, 'expires_at' => now()->subDay()->toIso8601String(), 'opened_at' => now()->subDays(2)->toIso8601String()], 'remind');
        $run([...$sent, 'token_hash' => null], 'remind');
        $run([...$sent, 'expires_at' => now()->addDay()->toIso8601String()], 'remind');
        Queue::assertPushed(SendCorrespondenceJob::class, 3);
    }

    public function test_mail_text_is_author_only_needs_an_outside_recipient_and_is_capped(): void
    {
        $author = $this->user();
        $stranger = $this->user();
        $external = ['user_id' => null, 'role' => LetterForm::ROLE_FYI, 'name' => 'گیرنده', 'email' => 'outside@example.test', 'delivery' => 'link'];
        $letter = $this->letter($author, ['status' => Correspondence::STATUS_APPROVED, 'parties' => [$external]]);

        $this->expectDenied(fn () => $this->act($stranger, $letter, 'mail_text', ['subject' => 'x', 'body' => 'y']));
        $this->act($author, $letter, 'mail_text', ['subject' => str_repeat('س', 300), 'body' => str_repeat('ب', 3000)]);
        $mail = $letter->fresh()->meta['mail'];
        $this->assertSame(LetterRules::MAIL_SUBJECT_MAX, mb_strlen($mail['subject']));
        $this->assertSame(LetterRules::MAIL_BODY_MAX, mb_strlen($mail['body']));

        $internal = $this->letter($author, ['status' => Correspondence::STATUS_APPROVED, 'parties' => [$this->party($stranger, LetterForm::ROLE_FYI)]]);
        $this->expectValidation(fn () => $this->act($author, $internal, 'mail_text', ['subject' => 'x']), 'status');
        $draft = $this->letter($author, ['parties' => [$external]]);
        $this->expectValidation(fn () => $this->act($author, $draft, 'mail_text', ['subject' => 'x']), 'status');
    }

    public function test_incoming_close_and_void_need_the_registry_a_done_letter_stays_frozen_and_void_keeps_the_number(): void
    {
        $registry = $this->registryUser();
        $user = $this->user();
        $head = $this->makeHead(Letterhead::KIND_REGISTRY)->id;
        $incoming = fn (string $status) => $this->letter($registry, ['direction' => Correspondence::DIRECTION_IN, 'type' => 'incoming', 'letterhead_id' => $head, 'status' => $status, 'number' => 'IN/' . uniqid()]);

        $received = $incoming(Correspondence::STATUS_RECEIVED);
        $this->expectDenied(fn () => $this->act($user, $received, 'close'));
        $this->expectDenied(fn () => $this->act($user, $received, 'void'));

        $this->act($registry, $received, 'void');
        $this->assertSame(Correspondence::STATUS_VOID, $received->fresh()->status);
        $this->assertSame($received->number, $received->fresh()->number);
        $this->assertSame('voided', $received->fresh()->events[0]['action']);

        $done = $incoming(Correspondence::STATUS_DONE);
        $this->expectValidation(fn () => $this->act($registry, $done, 'close'), 'status');
        $this->expectValidation(fn () => $done->fresh()->forceFill(['subject' => 'تغییر'])->save(), 'status');
        $this->expectValidation(fn () => $done->fresh()->forceFill(['status' => Correspondence::STATUS_RECEIVED])->save(), 'status');
    }

    public function test_search_folds_arabic_letters_caps_the_token_count_and_matches_their_number_only_on_incoming(): void
    {
        $viewer = $this->registryUser();
        $head = $this->makeHead(Letterhead::KIND_REGISTRY)->id;
        $book = $this->letter($viewer, ['status' => Correspondence::STATUS_APPROVED, 'number' => 'S/' . uniqid(), 'subject' => 'کتاب راهنما']);
        $words = $this->letter($viewer, ['status' => Correspondence::STATUS_APPROVED, 'number' => 'S/' . uniqid(), 'subject' => 'alpha beta gamma delta epsilon']);
        $incoming = $this->letter($viewer, ['direction' => Correspondence::DIRECTION_IN, 'letterhead_id' => $head, 'status' => Correspondence::STATUS_RECEIVED, 'number' => 'S/' . uniqid(), 'their_number' => 'EXT-9917', 'subject' => 'وارده']);
        $outgoing = $this->letter($viewer, ['status' => Correspondence::STATUS_APPROVED, 'number' => 'S/' . uniqid(), 'their_number' => 'EXT-9917', 'subject' => 'صادره']);

        $component = Livewire::actingAs($viewer)->test(Main::class, ['activeTab' => 'table', 'listTab' => 'all']);
        $found = function (string $search) use ($component): array {
            $component->set('search', $search);

            return collect($component->instance()->letters)->pluck('id')->all();
        };

        $this->assertContains($book->id, $found('كتاب'));
        $this->assertContains($words->id, $found('alpha beta gamma delta epsilon zzzz'));
        $this->assertNotContains($words->id, $found('alpha zzzz'));
        $ids = $found('EXT-9917');
        $this->assertContains($incoming->id, $ids);
        $this->assertNotContains($outgoing->id, $ids);
    }

    public function test_load_more_stops_at_list_max_even_when_more_rows_exist(): void
    {
        config(['ai.correspondence.list_max' => 10]);
        $author = $this->user();
        foreach (range(1, 11) as $index) {
            $this->letter($author, ['subject' => "page-{$index}"]);
        }

        $component = Livewire::actingAs($author)->test(Main::class, ['listTab' => 'mine']);
        $this->assertCount(5, $component->instance()->letters);
        $this->assertTrue($component->instance()->hasMore);

        $component->call('loadMore');
        $this->assertCount(10, $component->instance()->letters);
        $this->assertFalse($component->instance()->hasMore);
    }

    public function test_pinned_ids_are_sanitized_and_order_the_table_first(): void
    {
        $author = $this->user();
        $oldest = $this->letter($author, ['subject' => 'pin-old']);
        $this->letter($author, ['subject' => 'pin-mid']);
        $newest = $this->letter($author, ['subject' => 'pin-new']);

        $component = Livewire::actingAs($author)->test(Main::class, ['activeTab' => 'table', 'listTab' => 'mine']);
        $this->assertSame($newest->id, collect($component->instance()->letters)->pluck('id')->first());

        $component->call('syncPinned', [$oldest->id, 'x', -3, $oldest->id])->assertSet('pinned', [$oldest->id]);
        $this->assertSame($oldest->id, collect($component->instance()->letters)->pluck('id')->first());
    }

    public function test_client_cannot_tamper_with_locked_state(): void
    {
        $user = $this->user();

        foreach ([
            Workspace::class => ['readOnly' => true, 'registryOnly' => true, 'contentHash' => 'x', 'awaitingId' => 5, 'correcting' => true, 'replyToId' => 5],
            Main::class => ['listTab' => 'all', 'filter' => 'draft', 'pinned' => [1], 'replyToId' => 5, 'epoch' => 3, 'watchedId' => 5],
        ] as $class => $locked) {
            foreach ($locked as $property => $value) {
                $this->assertLocked($class, $user, $property, $value);
            }
        }

        $this->addToAssertionCount(1);
    }

    private function assertLocked(string $class, User $user, string $property, mixed $value): void
    {
        Livewire::withoutLazyLoading();
        try {
            Livewire::actingAs($user)->test($class)->set($property, $value);
        } catch (CannotUpdateLockedPropertyException) {
            return;
        }

        $this->fail("{$class}::{$property} must be locked against client writes.");
    }

    public function test_a_read_only_viewer_cannot_write_the_form(): void
    {
        $author = $this->user();
        $reader = $this->user();
        $letter = $this->shared($author, [$this->party($this->user()), $this->party($reader, LetterForm::ROLE_FYI)]);

        Livewire::actingAs($reader)->test(Workspace::class, ['letterId' => $letter->id])
            ->assertSet('readOnly', true)
            ->set('form.subject', 'تغییر')
            ->assertStatus(403);
    }

    public function test_replacing_an_approved_letter_supersedes_its_original_only_from_an_approved_replacement(): void
    {
        $author = $this->user();
        $original = $this->letter($author, ['status' => Correspondence::STATUS_APPROVED, 'number' => 'ORIG-' . uniqid()]);
        $draft = $this->letter($author, ['supersedes_id' => $original->id]);

        Livewire::actingAs($author)->test(Workspace::class, ['letterId' => $draft->id])->call('supersedeOriginal');
        $this->assertSame(Correspondence::STATUS_APPROVED, $original->fresh()->status);

        $replacement = $this->letter($author, ['status' => Correspondence::STATUS_APPROVED, 'supersedes_id' => $original->id, 'number' => 'REP-' . uniqid()]);
        $this->assertTrue($this->viewerOf($author, $replacement)['replace']);
        $this->assertFalse($this->viewerOf($author, $draft)['replace']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($author)->test(Workspace::class, ['letterId' => $replacement->id])->call('supersedeOriginal');
        $this->assertSame(Correspondence::STATUS_SUPERSEDED, $original->fresh()->status);
        $this->assertFalse($this->viewerOf($author, $replacement)['replace']);
    }

    private function outside(array $extra = []): array
    {
        return array_replace([
            'user_id' => null,
            'role' => LetterForm::ROLE_FYI,
            'name' => 'مخاطب بیرونی',
            'email' => 'outside@example.test',
            'delivery' => 'link',
        ], $extra);
    }

    private function approvedWithOutsiders(User $author, array $outsiders): Correspondence
    {
        $approver = $this->user();

        return $this->letter($author, [
            'status' => Correspondence::STATUS_APPROVED,
            'number' => 'N-' . uniqid(),
            'letter_date' => '1405/01/01',
            'content_hash' => str_repeat('a', 64),
            'parties' => [$this->party($approver, LetterForm::ROLE_APPROVER, ['signed_at' => now()->toIso8601String()]), ...$outsiders],
        ]);
    }

    public function test_added_tags_are_trimmed_stripped_deduplicated_and_capped(): void
    {
        $component = Livewire::actingAs($this->user())->test(Workspace::class)->assertSet('form.tags', []);

        $component->call('addTag', '  فوری  ')->assertSet('form.tags', ['فوری']);
        $component->call('addTag', '<b>مالی</b>')->assertSet('form.tags', ['فوری', 'مالی']);
        $component->call('addTag', "\u{202E}فوری")->assertSet('form.tags', ['فوری', 'مالی']);
        $component->call('addTag', '   ')->assertSet('form.tags', ['فوری', 'مالی']);

        $component->call('addTag', str_repeat('ا', 40));
        $this->assertSame(32, mb_strlen($component->get('form.tags')[2]));

        foreach (range(1, 20) as $index) {
            $component->call('addTag', "t{$index}");
        }
        $this->assertCount(12, $component->get('form.tags'));
        $this->assertSame(array_values($component->get('form.tags')), $component->get('form.tags'));
    }

    public function test_a_tag_can_be_removed_by_position_and_a_bad_position_changes_nothing(): void
    {
        $component = Livewire::actingAs($this->user())->test(Workspace::class);
        foreach (['الف', 'ب', 'ج'] as $tag) {
            $component->call('addTag', $tag);
        }

        $component->call('removeTag', 1)->assertSet('form.tags', ['الف', 'ج']);
        $component->call('removeTag', 9)->assertSet('form.tags', ['الف', 'ج']);
        $component->call('removeTag', 0)->call('removeTag', 0)->assertSet('form.tags', []);
    }

    public function test_tags_cannot_be_changed_by_a_read_only_viewer_or_on_an_approved_letter(): void
    {
        $author = $this->user();
        $reader = $this->user();
        $shared = $this->shared($author, [$this->party($this->user()), $this->party($reader, LetterForm::ROLE_FYI)], ['tags' => ['الف']]);

        Livewire::actingAs($reader)->test(Workspace::class, ['letterId' => $shared->id])
            ->assertSet('readOnly', true)
            ->call('addTag', 'ب')->assertSet('form.tags', ['الف'])
            ->call('removeTag', 0)->assertSet('form.tags', ['الف']);

        $approved = $this->letter($author, ['status' => Correspondence::STATUS_APPROVED, 'tags' => ['الف'], 'number' => 'T-' . uniqid()]);
        Livewire::withoutLazyLoading();
        Livewire::actingAs($author)->test(Workspace::class, ['letterId' => $approved->id])
            ->call('addTag', 'ب')->assertSet('form.tags', ['الف'])
            ->call('removeTag', 0)->assertSet('form.tags', ['الف']);
    }

    public function test_the_tag_cleaner_is_public_static_and_applies_the_same_limits(): void
    {
        $this->assertSame(['x', 'y', '5', str_repeat('b', 32)], LetterRules::tags(['  x ', 'x', '<i>y</i>', '', 5, null, [], str_repeat('b', 40)]));
        $this->assertSame([], LetterRules::tags('not-an-array'));
        $this->assertSame([], LetterRules::tags(null));
        $this->assertCount(12, LetterRules::tags(array_map(fn (int $i) => "tag{$i}", range(1, 60))));
        $this->assertSame([], LetterRules::tags([...array_fill(0, 50, ''), 'late']));
    }

    public function test_link_and_mail_text_refuse_with_the_mail_off_message_when_the_mail_channel_is_off(): void
    {
        Queue::fake();
        $author = $this->user();
        $letter = $this->approvedWithOutsiders($author, [$this->outside()]);
        $this->actingAs($author);
        $message = __('resources/correspondence/strings.errors.mail_off');

        config(['app.off' => ['notify:mail']]);
        foreach (['send', 'remind', 'revoke'] as $action) {
            $exception = $this->expectValidation(fn () => app(DeliverLinkAction::class)->execute((int) $letter->id, 1, $action), 'form');
            $this->assertSame([$message], $exception->errors()['form']);
        }
        $exception = $this->expectValidation(fn () => $this->act($author, $letter, 'mail_text', ['subject' => 'عنوان', 'body' => 'متن']), 'form');
        $this->assertSame([$message], $exception->errors()['form']);

        Queue::assertNothingPushed();
        $this->assertArrayNotHasKey('mail', $letter->fresh()->meta ?? []);
        $this->assertSame([], array_filter($letter->fresh()->events ?? [], fn (array $event) => in_array($event['action'], ['sent', 'link_revoked'], true)));

        config(['app.off' => []]);
        app(DeliverLinkAction::class)->execute((int) $letter->id, 1, 'send');
        Queue::assertPushed(SendCorrespondenceJob::class, fn (SendCorrespondenceJob $job): bool => $job->letterId === (int) $letter->id);
    }

    public function test_viewer_state_and_delivery_rows_drop_every_mail_action_when_the_mail_channel_is_off(): void
    {
        $author = $this->user();
        $sent = $this->outside(['name' => 'ارسال‌شده', 'sent_at' => now()->subDay()->toIso8601String(), 'token_hash' => str_repeat('b', 64), 'expires_at' => now()->addDays(10)->toIso8601String()]);
        $letter = $this->approvedWithOutsiders($author, [$this->outside(), $sent]);

        $on = $this->viewerOf($author, $letter);
        $this->assertTrue($on['mail']);
        $this->assertTrue($on['delivery'][0]['can_send']);
        $this->assertTrue($on['delivery'][1]['can_revoke']);

        config(['app.off' => ['notify:mail']]);
        $off = $this->viewerOf($author, $letter);
        $this->assertFalse($off['mail']);
        $this->assertCount(2, $off['delivery']);
        foreach ($off['delivery'] as $row) {
            $this->assertFalse($row['can_send']);
            $this->assertFalse($row['can_remind']);
            $this->assertFalse($row['can_revoke']);
        }
    }

    public function test_the_viewer_hides_send_remind_revoke_and_the_mail_text_editor_and_explains_why_when_mail_is_off(): void
    {
        $author = $this->user();
        $sent = $this->outside(['sent_at' => now()->subDay()->toIso8601String(), 'token_hash' => str_repeat('b', 64), 'expires_at' => now()->addDays(10)->toIso8601String()]);
        $letter = $this->approvedWithOutsiders($author, [$this->outside(), $sent]);

        $html = Livewire::actingAs($author)->test(Workspace::class, ['letterId' => $letter->id])->html();
        $this->assertStringContainsString('correspondence-delivery-send-1', $html);
        $this->assertStringContainsString('correspondence-delivery-revoke-2', $html);
        $this->assertStringContainsString('correspondence-mail-text', $html);
        $this->assertStringNotContainsString('correspondence-mail-off', $html);

        config(['app.off' => ['notify:mail']]);
        Livewire::withoutLazyLoading();
        $html = Livewire::actingAs($author)->test(Workspace::class, ['letterId' => $letter->id])->html();
        $this->assertStringNotContainsString('correspondence-delivery-send-', $html);
        $this->assertStringNotContainsString('correspondence-delivery-revoke-', $html);
        $this->assertStringNotContainsString('correspondence-delivery-remind-', $html);
        $this->assertStringNotContainsString('correspondence-mail-text', $html);
        $this->assertStringContainsString('correspondence-mail-off', $html);
        $this->assertStringContainsString(__('resources/correspondence/strings.viewer.mail_off'), $html);
    }

    public function test_the_composer_hides_email_and_delivery_fields_of_outside_parties_when_mail_is_off(): void
    {
        $author = $this->user();
        $party = [...LetterForm::blankParty(), 'role' => LetterForm::ROLE_FYI];

        $html = Livewire::actingAs($author)->test(Workspace::class)->set('form.parties', [$party])->html();
        $this->assertStringContainsString('correspondence-form-party-external-0', $html);
        $this->assertStringContainsString('correspondence-form-party-email-0', $html);
        $this->assertStringContainsString('correspondence-form-party-delivery-0', $html);

        config(['app.off' => ['notify:mail']]);
        Livewire::withoutLazyLoading();
        $html = Livewire::actingAs($author)->test(Workspace::class)->set('form.parties', [$party])->html();
        $this->assertStringContainsString('correspondence-form-party-external-0', $html);
        $this->assertStringNotContainsString('correspondence-form-party-email-0', $html);
        $this->assertStringNotContainsString('correspondence-form-party-delivery-0', $html);
    }

    public function test_a_pdf_only_party_never_turns_not_opened_and_gets_no_remind(): void
    {
        $presenter = new CorrespondencePresenter();
        $days = LetterForm::limits()['not_opened_days'];

        $rows = $presenter->deliveryRows([
            $this->outside(['name' => 'پی‌دی‌اف', 'delivery' => 'pdf', 'sent_at' => now()->subDays($days + 1)->toIso8601String(), 'token_hash' => str_repeat('a', 64), 'expires_at' => now()->addDays(5)->toIso8601String()]),
        ]);

        $this->assertNotSame('not_opened', $rows[0]['state']);
        $this->assertFalse($rows[0]['can_remind']);
    }

    public function test_the_template_save_block_shows_to_the_author_only(): void
    {
        $author = $this->user();
        $approver = $this->user();
        $shared = $this->shared($author, [$this->party($approver)]);

        $draft = $this->letter($author, ['subject' => 'draft for template']);

        $authorHtml = Livewire::actingAs($author)->test(Workspace::class, ['letterId' => $draft->id])->html();
        $approverHtml = Livewire::actingAs($approver)->test(Workspace::class, ['letterId' => $shared->id])->html();

        $this->assertStringContainsString('correspondence-template-save', $authorHtml);
        $this->assertStringNotContainsString('correspondence-template-save', $approverHtml);
    }

    public function test_delivery_rows_report_state_flags_errors_and_expiry_per_outside_party(): void
    {
        $presenter = new CorrespondencePresenter();
        $days = LetterForm::limits()['not_opened_days'];
        $internal = $this->party($this->user());
        $rows = $presenter->deliveryRows([
            $internal,
            $this->outside(['name' => 'جدید']),
            $this->outside(['name' => 'ارسال‌شده', 'sent_at' => now()->subHour()->toIso8601String(), 'token_hash' => str_repeat('a', 64), 'expires_at' => now()->addDays(5)->toIso8601String()]),
            $this->outside(['name' => 'باز‌نشده', 'sent_at' => now()->subDays($days + 1)->toIso8601String(), 'token_hash' => str_repeat('a', 64), 'expires_at' => now()->addDays(5)->toIso8601String()]),
            $this->outside(['name' => 'بازشده', 'sent_at' => now()->subDays(2)->toIso8601String(), 'opened_at' => now()->subDay()->toIso8601String(), 'token_hash' => str_repeat('a', 64), 'expires_at' => now()->subHour()->toIso8601String()]),
            $this->outside(['name' => 'در حال ارسال', 'delivery_error' => 'sending', 'sending_at' => now()->toIso8601String()]),
            $this->outside(['name' => 'ناموفق', 'delivery_error' => 'mail_failed']),
            $this->outside(['name' => 'نامعلوم', 'delivery_error' => 'weird_code']),
            $this->outside(['name' => '', 'org' => 'فقط سازمان', 'email' => 'bad-email']),
        ]);

        $this->assertSame([1, 2, 3, 4, 5, 6, 7, 8], array_column($rows, 'index'));
        $this->assertSame(['index', 'name', 'delivery', 'state', 'label', 'sent_at', 'opened_at', 'expires', 'error', 'error_label', 'can_send', 'can_remind', 'can_revoke'], array_keys($rows[0]));
        $this->assertSame(['not_sent', 'sent', 'not_opened', 'opened', 'sending', 'not_sent', 'not_sent', 'not_sent'], array_column($rows, 'state'));

        $this->assertTrue($rows[0]['can_send']);
        $this->assertFalse($rows[0]['can_revoke']);
        $this->assertSame('', $rows[0]['sent_at']);

        $this->assertFalse($rows[1]['can_send']);
        $this->assertFalse($rows[1]['can_remind']);
        $this->assertTrue($rows[1]['can_revoke']);
        $this->assertNotSame('', $rows[1]['sent_at']);
        $this->assertNotSame('', $rows[1]['expires']);

        $this->assertTrue($rows[2]['can_remind']);
        $this->assertNotSame('', $rows[3]['opened_at']);
        $this->assertTrue($rows[3]['can_remind'], 'a lapsed link can be reminded even after it was opened');

        $this->assertSame(__('resources/correspondence/strings.delivery_errors.sending'), $rows[4]['label']);
        $this->assertSame('', $rows[4]['error']);
        $this->assertFalse($rows[4]['can_send']);

        $this->assertSame('mail_failed', $rows[5]['error']);
        $this->assertSame(__('resources/correspondence/strings.delivery_errors.mail_failed'), $rows[5]['error_label']);
        $this->assertSame(__('resources/correspondence/strings.delivery_errors.send_failed'), $rows[6]['error_label']);

        $this->assertSame('فقط سازمان', $rows[7]['name']);
        $this->assertFalse($rows[7]['can_send']);

        foreach ($presenter->deliveryRows([$this->outside()], false) as $row) {
            $this->assertFalse($row['can_send']);
        }
    }

    public function test_the_notification_legend_button_and_modal_list_the_two_correspondence_rows(): void
    {
        $attention = BadgeLegendCatalog::get('correspondence-controller');
        $bell = BadgeLegendCatalog::get('correspondence-controller:nudge');

        $this->assertNotNull($attention);
        $this->assertNotNull($bell);
        $this->assertSame('correspondence', $attention['subgroup']);
        $this->assertSame('correspondence', $bell['subgroup']);
        $this->assertArrayHasKey('surface', $attention);
        $this->assertArrayNotHasKey('surface', $bell);

        $html = Livewire::actingAs($this->user())->test(Main::class)->html();

        $this->assertStringContainsString('correspondence-badge-legend-button', $html);
        $this->assertStringContainsString("name: 'correspondence-badge-legend'", $html);
        $this->assertLessThan(strpos($html, "name: 'correspondence-legend'"), strpos($html, "name: 'correspondence-badge-legend'"));
        $this->assertStringContainsString($attention['label'], $html);
        $this->assertStringContainsString($bell['label'], $html);
    }

    public function test_the_legend_rows_go_away_with_the_module_for_a_tenant(): void
    {
        config(['app.off' => ['correspondence']]);

        $this->assertNull(BadgeLegendCatalog::get('correspondence-controller'));
        $this->assertNull(BadgeLegendCatalog::get('correspondence-controller:nudge'));
    }

    public function test_the_editor_toolbar_is_four_groups_of_four_part_buttons_with_the_new_commands(): void
    {
        $tools = (new CorrespondencePresenter())->editorTools([7 => 'علی', 'x' => 'skip']);
        $groups = $tools['buttons'];

        $this->assertCount(4, $groups);
        foreach ($groups as $group) {
            $this->assertNotEmpty($group);
            foreach ($group as $button) {
                $this->assertCount(4, $button);
                $this->assertContainsOnly('string', $button);
            }
        }

        $calls = array_column(array_merge(...$groups), 0);
        foreach (['bold()', 'superscript()', 'subscript()', 'quote()', 'outdent()', 'indent()', 'rule()', 'pageBreak()', 'halfSpace()', 'clearFormat()'] as $call) {
            $this->assertContains($call, $calls);
        }
        $this->assertSame($calls, array_values(array_unique($calls)));
        $this->assertSame([['value' => 7, 'label' => 'علی']], $tools['people']);
        $this->assertSame(LetterForm::MERGE_TOKENS, $tools['tokens']);
    }

    public function test_the_editor_font_menu_is_the_registry_and_every_key_fits_the_sanitizer_font_family_pattern(): void
    {
        $fonts = (new CorrespondencePresenter())->editorTools()['fonts'];

        $this->assertSame(CorrespondenceFonts::options(), $fonts);
        $this->assertNotEmpty($fonts);
        foreach (array_keys($fonts) as $key) {
            $this->assertMatchesRegularExpression('/^[\p{L}\p{N} ,\-]{1,100}$/u', (string) $key);
            $clean = Correspondence::sanitizeHtml('<span style="font-family: ' . $key . '">x</span>');
            $this->assertStringContainsString('font-family: ' . $key, $clean, "the sanitizer must keep the registry font [{$key}]");
        }
    }

    public function test_page_parts_split_only_at_depth_zero_and_drop_empty_edges(): void
    {
        $split = fn (string $html): array => (new ReflectionMethod(CorrespondencePresenter::class, 'pageParts'))->invoke(new CorrespondencePresenter(), $html);
        $hr = '<hr style="page-break-after: always">';

        $this->assertSame(['<p>الف</p>', '<p>ب</p>'], $split("<p>الف</p>{$hr}<p>ب</p>"));
        $this->assertSame(['<p>الف</p>'], $split("{$hr}<p>الف</p>{$hr}"));
        $this->assertSame(['<p>الف</p>', '<p>ب</p>'], $split("<p>الف</p>{$hr}{$hr}<p>ب</p>"));
        $this->assertSame([''], $split($hr));
        $this->assertSame(['<p>ساده</p>'], $split('<p>ساده</p>'));

        $nested = "<blockquote><p>الف</p>{$hr}<p>ب</p></blockquote>";
        $this->assertSame([$nested], $split($nested));
        $this->assertSame(["<blockquote><p>الف</p>{$hr}</blockquote>", '<p>ج</p>'], $split("<blockquote><p>الف</p>{$hr}</blockquote>{$hr}<p>ج</p>"));
    }

    private function paperLetter(string $html, array $snapshot = []): Correspondence
    {
        return (new Correspondence())->forceFill([
            'id' => 4343,
            'status' => Correspondence::STATUS_APPROVED,
            'type' => 'letter',
            'direction' => Correspondence::DIRECTION_OUT,
            'sign_mode' => Correspondence::SIGN_MODE_ELECTRONIC,
            'subject' => 'موضوع',
            'spec' => ['blocks' => [['type' => 'html', 'heading' => '', 'html' => $html]]],
            'meta' => ['snapshot' => $snapshot],
            'parties' => [],
            'letterhead_id' => null,
        ]);
    }

    public function test_pdf_mode_splits_html_blocks_at_page_breaks_and_other_modes_keep_one_part(): void
    {
        $presenter = new CorrespondencePresenter();
        $letter = $this->paperLetter('<p>الف</p><hr style="page-break-after: always"><p>ب</p>');

        $this->assertCount(2, $presenter->paperData($letter, 'pdf')['blocks'][0]['parts']);
        $this->assertCount(1, $presenter->paperData($letter, 'link')['blocks'][0]['parts']);

        $pdf = view('livewire.dashboard.correspondence.paper', ['letter' => $letter, 'mode' => 'pdf'])->render();
        $this->assertSame(1, substr_count($pdf, '<pagebreak />'));
        $this->assertSame(2, substr_count($pdf, 'class="paper-flow"'));

        $web = view('livewire.dashboard.correspondence.paper', ['letter' => $letter, 'mode' => 'link'])->render();
        $this->assertStringNotContainsString('<pagebreak', $web);
        $this->assertSame(1, substr_count($web, 'class="paper-flow"'));
    }

    public function test_paper_data_replaces_an_unknown_or_hostile_snapshot_font_with_a_registry_default(): void
    {
        $presenter = new CorrespondencePresenter();
        $letter = $this->paperLetter('<p>الف</p>', ['style' => ['font' => 'x;}</style><script>alert(1)</script>', 'latin_font' => 'parastoo']]);

        $style = $presenter->paperData($letter, 'link')['style'];

        $this->assertSame(CorrespondenceFonts::pick('x', 'fa'), $style['font']);
        $this->assertSame(CorrespondenceFonts::pick('parastoo', 'latin'), $style['latin_font']);

        $html = view('livewire.dashboard.correspondence.paper', ['letter' => $letter, 'mode' => 'link'])->render();
        $this->assertStringNotContainsString('x;}', $html);
        $this->assertStringNotContainsString('<script>alert(1)', $html);
    }

    public function test_the_paper_loads_font_faces_only_for_used_fonts_and_only_in_browser_modes(): void
    {
        $snapshot = ['style' => ['font' => 'parastoo', 'latin_font' => 'tinos']];
        $plain = $this->paperLetter('<p>الف</p>', $snapshot);
        $mixed = $this->paperLetter('<p style="font-family: Inter">b</p>', $snapshot);

        $web = view('livewire.dashboard.correspondence.paper', ['letter' => $plain, 'mode' => 'link'])->render();
        $this->assertStringContainsString('@font-face { font-family: "parastoo"', $web);
        $this->assertStringContainsString('@font-face { font-family: "tinos"', $web);
        $this->assertStringNotContainsString('@font-face { font-family: "inter"', $web);
        $this->assertStringNotContainsString('@font-face { font-family: "vazirmatn"', $web);

        $withInter = view('livewire.dashboard.correspondence.paper', ['letter' => $mixed, 'mode' => 'link'])->render();
        $this->assertStringContainsString('@font-face { font-family: "inter"', $withInter);

        $pdf = view('livewire.dashboard.correspondence.paper', ['letter' => $mixed, 'mode' => 'pdf'])->render();
        $this->assertStringNotContainsString('@font-face', $pdf);
    }

    public function test_the_quota_meter_renders_its_numbers_bar_warning_and_slot_with_aria_only_when_asked(): void
    {
        $tone = ['text' => 'tone-text', 'fill' => 'tone-fill'];

        $full = (string) $this->blade(
            '<x-ui.quota-meter label="سهمیه" :used="2" :cap="6" :pct="33" :tone="$tone" aria="سهمیه امروز" warn="رو به پایان" bar-key="quota-bar" warn-key="quota-warn" class="extra-class"><i id="quota-slot">x</i></x-ui.quota-meter>',
            ['tone' => $tone]
        );
        $this->assertStringContainsString('سهمیه: ۲ / ۶', $full);
        $this->assertStringContainsString('role="progressbar"', $full);
        $this->assertStringContainsString('aria-label="سهمیه امروز"', $full);
        $this->assertStringContainsString('aria-valuenow="2"', $full);
        $this->assertStringContainsString('aria-valuemin="0"', $full);
        $this->assertStringContainsString('aria-valuemax="6"', $full);
        $this->assertStringContainsString('width: 33%', $full);
        $this->assertStringContainsString('wire:key="quota-bar"', $full);
        $this->assertStringContainsString('wire:key="quota-warn"', $full);
        $this->assertStringContainsString('رو به پایان', $full);
        $this->assertStringContainsString('<i id="quota-slot">x</i>', $full);
        $this->assertStringContainsString('extra-class', $full);
        $this->assertStringContainsString('tone-fill', $full);

        $bare = (string) $this->blade('<x-ui.quota-meter label="سهمیه" :used="0" :cap="6" :pct="0" :tone="$tone"/>', ['tone' => $tone]);
        $this->assertStringNotContainsString('role="progressbar"', $bare);
        $this->assertStringNotContainsString('aria-valuenow', $bare);
        $this->assertStringNotContainsString('wire:key', $bare);
        $this->assertStringNotContainsString('<p', $bare);
        $this->assertStringContainsString('width: 0%', $bare);
    }

    public function test_the_form_button_renders_a_link_with_the_same_classes_and_aria_disabled_and_leaves_button_callers_alone(): void
    {
        $classes = fn (string $html): string => preg_match('/class="([^"]*)"/', $html, $match) === 1 ? preg_replace('/\s+/', ' ', trim($match[1])) : '';

        $button = (string) $this->blade('<x-ui.buttons.form variant="tonal" icon="send">برو</x-ui.buttons.form>');
        $link = (string) $this->blade('<x-ui.buttons.form href="/somewhere" variant="tonal" icon="send">برو</x-ui.buttons.form>');

        $this->assertStringContainsString('<button', $button);
        $this->assertStringContainsString('type="button"', $button);
        $this->assertDoesNotMatchRegularExpression('/<a\s/', $button);
        $this->assertMatchesRegularExpression('/<a\s/', $link);
        $this->assertStringContainsString('href="/somewhere"', $link);
        $this->assertStringNotContainsString('<button', $link);
        $this->assertStringNotContainsString('type="button"', $link);
        $this->assertStringNotContainsString('aria-disabled', $link);
        $this->assertNotSame('', $classes($button));
        $this->assertSame($classes($button), $classes($link));
        $this->assertStringContainsString('برو', $link);

        $disabledLink = (string) $this->blade('<x-ui.buttons.form href="/somewhere" disabled>برو</x-ui.buttons.form>');
        $this->assertStringContainsString('aria-disabled="true"', $disabledLink);
        $this->assertStringContainsString('tabindex="-1"', $disabledLink);
        $this->assertDoesNotMatchRegularExpression('/\sdisabled(=|\s|>)/', $disabledLink);

        $disabledButton = (string) $this->blade('<x-ui.buttons.form disabled>برو</x-ui.buttons.form>');
        $this->assertMatchesRegularExpression('/\sdisabled(=|\s|>)/', $disabledButton);
        $this->assertStringNotContainsString('aria-disabled', $disabledButton);
    }

    public function test_use_template_copies_the_body_into_a_new_draft_and_a_repeat_click_returns_the_same_copy(): void
    {
        $author = $this->user();
        $template = $this->letter($author, ['is_template' => true, 'subject' => 'قالب دعوت', 'meta' => ['template_name' => 'دعوت'], 'number' => null]);
        $actions = app(TemplateAction::class);

        $copy = $actions->useTemplate($author, (int) $template->id);

        $this->assertNotSame($template->id, $copy->id);
        $this->assertFalse((bool) $copy->is_template);
        $this->assertSame(Correspondence::STATUS_DRAFT, $copy->status);
        $this->assertSame($author->id, $copy->user_id);
        $this->assertSame('قالب دعوت', $copy->subject);
        $this->assertSame('متن نامه', $copy->spec['blocks'][0]['text']);
        $this->assertSame('created', $copy->events[0]['action']);

        $again = $actions->useTemplate($author, (int) $template->id);
        $this->assertSame($copy->id, $again->id);
        $this->assertSame(1, Correspondence::query()->where('user_id', $author->id)->where('is_template', false)->where('subject', 'قالب دعوت')->count());
    }

    public function test_the_recent_template_chips_refresh_after_a_correspondence_is_saved(): void
    {
        $author = $this->user();
        $this->letter($author, ['is_template' => true, 'subject' => 'قالب اول', 'meta' => ['template_name' => 'اول'], 'number' => null]);
        $this->assertCount(1, Livewire::actingAs($author)->test(Main::class)->instance()->recentTemplates);

        $this->letter($author, ['is_template' => true, 'subject' => 'قالب دوم', 'meta' => ['template_name' => 'دوم'], 'number' => null]);

        $this->assertCount(2, Livewire::actingAs($author)->test(Main::class)->instance()->recentTemplates);
    }

    public function test_use_template_fills_only_a_blank_draft_and_hides_other_peoples_private_templates(): void
    {
        $author = $this->user();
        $stranger = $this->user();
        $filled = $this->letter($author, ['is_template' => true, 'subject' => 'قالب پر']);
        $blank = $this->letter($author, ['subject' => '', 'spec' => ['blocks' => [LetterForm::BLANK_BLOCK]]]);
        $busy = $this->letter($author, ['subject' => 'نامه در حال نگارش']);
        $actions = app(TemplateAction::class);

        $this->assertTrue(TemplateAction::isBlank($blank, $author));
        $this->assertFalse(TemplateAction::isBlank($busy, $author));
        $this->assertFalse(TemplateAction::isBlank($blank, $stranger));

        $into = $actions->useTemplate($author, (int) $filled->id, (int) $blank->id);
        $this->assertSame($blank->id, $into->id);
        $this->assertSame('قالب پر', $blank->fresh()->subject);

        $other = $this->letter($author, ['is_template' => true, 'subject' => 'قالب دوم']);
        $this->expectDenied(fn () => $actions->useTemplate($author, (int) $other->id, (int) $busy->id));
        $this->assertSame('نامه در حال نگارش', $busy->fresh()->subject);

        $this->expectDenied(fn () => $actions->useTemplate($stranger, (int) $other->id));
    }

    public function test_main_use_template_into_the_open_blank_draft_asks_the_workspace_to_reload(): void
    {
        $author = $this->user();
        $template = $this->letter($author, ['is_template' => true, 'subject' => 'قالب پر']);
        $blank = $this->letter($author, ['subject' => '', 'spec' => ['blocks' => [LetterForm::BLANK_BLOCK]]]);

        Livewire::actingAs($author)->test(Main::class, ['letterId' => $blank->id])
            ->call('useTemplate', $template->id)
            ->assertSet('letterId', $blank->id)
            ->assertDispatched('correspondence-reload');
        $this->assertSame('قالب پر', $blank->fresh()->subject);
    }

    public function test_a_department_template_is_shared_with_colleagues_but_a_private_one_is_not(): void
    {
        $owner = $this->user();
        $colleague = $this->user();
        $outsider = $this->user();
        $department = $this->profile($owner);
        Profile::factory()->create(['user_id' => $colleague->id, 'department_id' => $department, 'position' => 'employee']);
        $this->profile($outsider);
        $shared = $this->letter($owner, ['is_template' => true, 'subject' => 'قالب واحد']);
        $private = $this->letter($owner, ['is_template' => true, 'subject' => 'قالب خصوصی']);
        $actions = app(TemplateAction::class);

        $actions->toggleTemplate($owner, (int) $shared->id, 'قالب واحد', 'department');
        $actions->toggleTemplate($owner, (int) $private->id, 'قالب خصوصی', 'private');

        $visible = fn (User $user): array => TemplateAction::templatesFor($user->fresh())->pluck('id')->all();
        $this->assertContains($shared->id, $visible($colleague));
        $this->assertNotContains($private->id, $visible($colleague));
        $this->assertNotContains($shared->id, $visible($outsider));
        $this->assertContains($private->id, $visible($owner));
    }

    public function test_duplicate_copies_a_viewable_letter_into_a_fresh_unnumbered_draft(): void
    {
        $author = $this->user();
        $stranger = $this->user();
        $source = $this->letter($author, ['status' => Correspondence::STATUS_APPROVED, 'number' => 'DUP-' . uniqid(), 'subject' => 'نامه تأییدشده', 'parties' => [$this->party($this->user())]]);
        $actions = app(TemplateAction::class);

        $copy = $actions->duplicate($author, (int) $source->id);

        $this->assertNotSame($source->id, $copy->id);
        $this->assertSame(Correspondence::STATUS_DRAFT, $copy->status);
        $this->assertNull($copy->number);
        $this->assertSame('نامه تأییدشده', $copy->subject);
        $this->assertSame([], $copy->parties);
        $this->assertSame(Correspondence::STATUS_APPROVED, $source->fresh()->status);

        $this->expectDenied(fn () => $actions->duplicate($stranger, (int) $source->id));
    }

    public function test_batch_creation_fills_the_merge_fields_per_line_and_enforces_the_line_cap(): void
    {
        $author = $this->user();
        $approver = $this->user();
        $template = $this->letter($author, [
            'is_template' => true,
            'subject' => 'دعوت از {گیرنده}',
            'spec' => ['blocks' => [['type' => 'text', 'text' => '{سازمان} | {آدرس} | {تاریخ}']]],
            'parties' => [$this->party($approver)],
        ]);
        $actions = app(TemplateAction::class);

        $ids = $actions->createFromTemplate($author, (int) $template->id, ['شرکت الف|آقای رضایی|تهران', 'شرکت ب', '', '   ']);

        $this->assertCount(2, $ids);
        [$first, $second] = [Correspondence::findOrFail($ids[0]), Correspondence::findOrFail($ids[1])];
        $this->assertSame('دعوت از آقای رضایی', $first->subject);
        $this->assertSame('دعوت از شرکت ب', $second->subject);
        $this->assertSame('شرکت الف | تهران | ' . toJalali(now('Asia/Tehran'), 'Y/m/d'), $first->spec['blocks'][0]['text']);
        $this->assertStringNotContainsString('{', $second->spec['blocks'][0]['text']);
        $this->assertSame('شرکت الف', $first->spec['recipient']['organization']);
        $this->assertSame('آقای رضایی', $first->spec['recipient']['person']);
        $this->assertSame('', $second->spec['recipient']['person']);
        foreach ([$first, $second] as $letter) {
            $this->assertSame(Correspondence::STATUS_DRAFT, $letter->status);
            $this->assertFalse((bool) $letter->is_template);
            $this->assertSame([$approver->id], array_map('intval', array_column($letter->parties, 'user_id')));
        }

        $tooMany = array_map(fn (int $i) => "سازمان {$i}", range(1, LetterForm::limits()['batch'] + 1));
        $before = Correspondence::query()->where('user_id', $author->id)->count();
        $this->expectValidation(fn () => $actions->createFromTemplate($author, (int) $template->id, $tooMany), 'batchLines');
        $this->expectValidation(fn () => $actions->createFromTemplate($author, (int) $template->id, ['', '  ']), 'batchLines');
        $this->assertSame($before, Correspondence::query()->where('user_id', $author->id)->count());

        $this->expectDenied(fn () => $actions->createFromTemplate($this->user(), (int) $template->id, ['شرکت']));
    }

    private function aiOn(): void
    {
        config(['app.off' => [], 'ai.gateway.key' => 'test-key', 'ai.gateway.url' => 'https://gateway.test/v1']);
        Queue::fake();
    }

    public function test_generate_is_refused_without_queueing_or_charging_when_the_daily_quota_is_spent(): void
    {
        $this->aiOn();
        $user = $this->user();
        foreach (range(1, Correspondence::quotaCap()) as $ignored) {
            Correspondence::consumeQuota($user->id);
        }

        Livewire::actingAs($user)->test(Workspace::class)
            ->set('form.prompt', 'Draft a short office letter about the approved request.')
            ->call('generate')
            ->assertHasErrors(['form.prompt']);

        Queue::assertNothingPushed();
        $this->assertSame(Correspondence::quotaCap(), Correspondence::quotaUsed($user->id));
        $this->assertFalse(Correspondence::query()->where('user_id', $user->id)->where('status', Correspondence::STATUS_GENERATING)->exists());
    }

    public function test_generate_warns_and_keeps_the_picked_files_instead_of_dropping_them(): void
    {
        $this->aiOn();
        $user = $this->user();

        $component = Livewire::actingAs($user)->test(Workspace::class)
            ->set('form.prompt', 'Draft a short office letter about the approved request.')
            ->set('form.files', [UploadedFile::fake()->create('note.pdf', 10, 'application/pdf')])
            ->call('generate')
            ->assertDispatched('toast')
            ->assertSet('form.prompt', 'Draft a short office letter about the approved request.');

        $this->assertCount(1, $component->instance()->form->files);
        Queue::assertNothingPushed();
        $this->assertSame(0, Correspondence::quotaUsed($user->id));
    }

    public function test_a_second_generation_is_refused_while_a_fresh_run_is_still_generating(): void
    {
        $this->aiOn();
        $user = $this->user();
        $this->letter($user, ['status' => Correspondence::STATUS_GENERATING, 'subject' => 'در حال تولید']);

        Livewire::actingAs($user)->test(Workspace::class)
            ->set('form.prompt', 'Draft a short office letter about the approved request.')
            ->call('generate')
            ->assertHasErrors(['form.prompt']);

        Queue::assertNothingPushed();
        $this->assertSame(0, Correspondence::quotaUsed($user->id));
    }

    public function test_a_held_per_user_lock_makes_generate_wait_out_and_refuse_without_charging(): void
    {
        $this->aiOn();
        config(['ai.correspondence.lock.wait' => 0]);
        $user = $this->user();
        $lock = Cache::lock("ai-correspondence:generate:{$user->id}", 30);
        $this->assertTrue($lock->get());

        Livewire::actingAs($user)->test(Workspace::class)
            ->set('form.prompt', 'Draft a short office letter about the approved request.')
            ->call('generate')
            ->assertHasErrors(['form.prompt']);

        Queue::assertNothingPushed();
        $this->assertSame(0, Correspondence::quotaUsed($user->id));

        $lock->release();
        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Workspace::class)
            ->set('form.prompt', 'Draft a short office letter about the approved request.')
            ->call('generate')
            ->assertHasNoErrors();

        Queue::assertPushed(GenerateCorrespondenceJob::class);
        $this->assertSame(1, Correspondence::quotaUsed($user->id));
    }

    public function test_the_waiting_tab_scans_four_chunks_and_a_wider_page_reaches_older_matches(): void
    {
        $author = $this->user();
        $viewer = $this->user();
        $real = $this->letter($author, [
            'status' => Correspondence::STATUS_SHARED,
            'route_mode' => Correspondence::ROUTE_SEQUENTIAL,
            'awaiting_user_id' => $viewer->id,
            'parties' => [$this->party($viewer)],
            'subject' => 'waiting-real',
        ]);
        $signed = $this->party($viewer, LetterForm::ROLE_APPROVER, ['signed_at' => now()->toIso8601String()]);
        foreach (range(1, 20) as $index) {
            $this->letter($author, [
                'status' => Correspondence::STATUS_SHARED,
                'route_mode' => Correspondence::ROUTE_PARALLEL,
                'shared_at' => now(),
                'parties' => [$signed],
                'subject' => "decoy-{$index}",
            ]);
        }

        $component = Livewire::actingAs($viewer)->test(Main::class);
        $this->assertCount(0, $component->instance()->letters);
        $this->assertTrue($component->instance()->hasMore);

        $component->call('loadMore');
        $this->assertSame([$real->id], collect($component->instance()->letters)->pluck('id')->all());
        $this->assertFalse($component->instance()->hasMore);
    }

    public function test_every_literal_correspondence_translation_key_used_by_code_and_views_exists(): void
    {
        $strings = require lang_path('fa/resources/correspondence/strings.php');
        $roots = [
            app_path('Livewire/Dashboard/Correspondence'),
            app_path('Filament/Resources/CorrespondenceResource'),
            app_path('Filament/Resources/LetterheadResource'),
            app_path('Filament/Resources/CorrespondenceResource.php'),
            app_path('Filament/Resources/LetterheadResource.php'),
            app_path('Filament/Resources/UserResource'),
            app_path('Livewire/Dashboard/Profile'),
            app_path('Http/Controllers/CorrespondenceLinkController.php'),
            app_path('Http/Controllers/CorrespondencePdfController.php'),
            app_path('Jobs/SendCorrespondenceJob.php'),
            app_path('Jobs/GenerateCorrespondenceJob.php'),
            app_path('Models/Correspondence.php'),
            app_path('Models/Letterhead.php'),
            app_path('Models/Concerns/HasLetterheadNumbering.php'),
            app_path('Models/Concerns/Hooks/HasCorrespondenceHooks.php'),
            app_path('Models/Concerns/Hooks/HasLetterheadHooks.php'),
            app_path('Support/CorrespondenceAccessPolicy.php'),
            app_path('Services/Correspondence/CorrespondencePdf.php'),
            app_path('Services/Menu/Notifications/CorrespondenceNudge.php'),
            app_path('Services/Menu/Indicators/CorrespondenceBadge.php'),
            app_path('Services/Search/Resources/CorrespondenceResource.php'),
            resource_path('views/livewire/dashboard/correspondence'),
            resource_path('views/filament/resources/correspondence'),
            resource_path('views/correspondence'),
            resource_path('views/livewire/dashboard/profile/credentials/sign-code.blade.php'),
        ];
        $files = [];
        foreach ($roots as $root) {
            if (File::isFile($root)) {
                $files[] = $root;
            } elseif (File::isDirectory($root)) {
                $phpFiles = array_filter(File::allFiles($root), fn ($file) => $file->getExtension() === 'php');
                $files = [...$files, ...array_map(fn ($file) => $file->getPathname(), $phpFiles)];
            }
        }

        $missing = [];
        foreach ($files as $path) {
            preg_match_all('/resources\/correspondence\/strings\.([A-Za-z0-9_.]+)/', File::get($path), $matches);
            foreach (array_unique($matches[1]) as $key) {
                $key = rtrim($key, '.');
                if ($key !== '' && !Arr::has($strings, $key)) {
                    $missing[] = $path . ':' . $key;
                }
            }
        }

        $this->assertSame([], $missing, 'Missing correspondence translation keys: ' . implode(', ', $missing));
    }
}
