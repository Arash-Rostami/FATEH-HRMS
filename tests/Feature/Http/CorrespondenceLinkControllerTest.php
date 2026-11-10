<?php

namespace Tests\Feature\Http;

use App\Http\Middleware\SecureLetterLink;
use App\Livewire\Dashboard\Correspondence\Presentation\CorrespondencePresenter;
use App\Models\Correspondence;
use App\Models\User;
use App\Services\Correspondence\CorrespondencePdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use Tests\TestCase;

class CorrespondenceLinkControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
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

    private function approvedLetter(string $token, array $partyOverrides = [], array $extra = []): Correspondence
    {
        $owner = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $party = array_replace([
            'role' => 'fyi',
            'name' => 'External recipient',
            'email' => 'recipient@example.test',
            'user_id' => null,
            'delivery' => 'link',
            'token_hash' => hash('sha256', $token),
            'expires_at' => null,
            'open_count' => 0,
        ], $partyOverrides);

        return Correspondence::forceCreate($extra + [
            'user_id' => $owner->id,
            'direction' => Correspondence::DIRECTION_OUT,
            'type' => 'letter',
            'status' => Correspondence::STATUS_APPROVED,
            'classification' => Correspondence::CLASSIFICATION_NORMAL,
            'subject' => 'Shared subject',
            'number' => 'L/' . uniqid(),
            'letter_date' => '1405/01/01',
            'content_hash' => str_repeat('a', 64),
            'spec' => ['blocks' => [['type' => 'text', 'text' => 'body']]],
            'parties' => [$party],
            'recipients' => [],
            'events' => [],
            'meta' => [],
        ]);
    }

    public function test_head_preview_does_not_stamp_and_get_stamps_once_with_private_headers(): void
    {
        $token = str_repeat('a', 43);
        $letter = $this->approvedLetter($token);
        $url = route('correspondence.link', ['id' => $letter->id, 'token' => $token]);

        $this->call('HEAD', $url)->assertOk();
        $this->assertSame(0, $letter->fresh()->parties[0]['open_count']);
        $this->assertArrayNotHasKey('opened_at', $letter->fresh()->parties[0]);

        $response = $this->get($url)->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Content-Security-Policy', "frame-ancestors 'none'");

        $this->assertFalse($response->headers->has('Set-Cookie'));
        $this->get($url)->assertOk();
        $fresh = $letter->fresh();
        $this->assertSame(2, $fresh->parties[0]['open_count']);
        $this->assertCount(1, array_filter($fresh->events, fn (array $event): bool => ($event['action'] ?? null) === 'opened'));
    }

    public function test_invalid_or_expired_links_are_empty_404_responses(): void
    {
        $token = str_repeat('b', 43);
        $letter = $this->approvedLetter($token, ['expires_at' => now()->subMinute()->toIso8601String()]);

        $this->get(route('correspondence.link', ['id' => $letter->id, 'token' => $token]))
            ->assertNotFound()
            ->assertContent('')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertHeader('Cache-Control', 'no-store, private');

        $this->get('/c/0-invalid')->assertNotFound()->assertContent('')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    private function url(Correspondence $letter, string $token, bool $pdf = false): string
    {
        return route($pdf ? 'correspondence.link.pdf' : 'correspondence.link', ['id' => $letter->id, 'token' => $token]);
    }

    private function cachePdf(Correspondence $letter): void
    {
        Storage::fake('local');
        $fresh = Correspondence::query()->with('letterhead')->whereKey($letter->id)->first(CorrespondencePdf::renderColumns());
        $hash = (new ReflectionMethod(CorrespondencePdf::class, 'hashOf'))->invoke(null, $fresh);
        $relative = 'correspondence/' . $letter->id . '/letter-' . $hash . '.pdf';
        Storage::disk('local')->put($relative, '%PDF-1.4');
        DB::table('correspondences')->where('id', $letter->id)->update(['pdf_path' => $relative, 'pdf_hash' => $hash]);
    }

    public function test_the_token_matches_either_hash_slot_and_each_slot_has_its_own_expiry(): void
    {
        $old = str_repeat('c', 43);
        $next = str_repeat('d', 43);
        $letter = $this->approvedLetter($old, [
            'expires_at' => now()->subMinute()->toIso8601String(),
            'token_hash_next' => hash('sha256', $next),
            'expires_at_next' => now()->addDay()->toIso8601String(),
        ]);

        $this->get($this->url($letter, $next))->assertOk();
        $this->get($this->url($letter, $old))->assertNotFound()->assertContent('');

        $swapped = $this->approvedLetter($old, [
            'expires_at' => now()->addDay()->toIso8601String(),
            'token_hash_next' => hash('sha256', $next),
            'expires_at_next' => now()->subMinute()->toIso8601String(),
        ]);

        $this->get($this->url($swapped, $old))->assertOk();
        $this->get($this->url($swapped, $next))->assertNotFound()->assertContent('');
    }

    public function test_a_link_without_an_expiry_never_expires(): void
    {
        $token = str_repeat('e', 43);
        $letter = $this->approvedLetter($token, ['expires_at' => null]);

        $this->get($this->url($letter, $token))->assertOk();
    }

    public function test_every_dead_link_gets_the_identical_empty_404(): void
    {
        $token = str_repeat('f', 43);
        $ok = $this->approvedLetter($token);
        $revoked = $this->approvedLetter($token, ['token_hash' => null]);
        $draft = $this->approvedLetter($token, [], ['status' => Correspondence::STATUS_SHARED]);
        $secret = $this->approvedLetter($token, [], ['classification' => Correspondence::CLASSIFICATION_CONFIDENTIAL]);

        $urls = [
            $this->url($revoked, $token),
            $this->url($draft, $token),
            $this->url($secret, $token),
            $this->url($ok, str_repeat('z', 43)),
            '/c/' . ($ok->id + 900000) . '-' . $token,
            '/c/' . $ok->id . '-short',
        ];

        foreach ($urls as $url) {
            $response = $this->get($url);
            $response->assertNotFound()->assertContent('');
            foreach (SecureLetterLink::HEADERS as $name => $value) {
                $response->assertHeader($name, $name === 'Cache-Control' ? 'no-store, private' : $value);
            }
            $this->assertFalse($response->headers->has('Set-Cookie'));
        }
    }

    public function test_a_dead_link_never_stamps_an_open(): void
    {
        $token = str_repeat('g', 43);
        $letter = $this->approvedLetter($token, ['expires_at' => now()->subMinute()->toIso8601String()]);

        $this->get($this->url($letter, $token))->assertNotFound();

        $fresh = $letter->fresh();
        $this->assertSame(0, $fresh->parties[0]['open_count']);
        $this->assertSame([], $fresh->events);
    }

    public function test_get_stamps_the_open_columns_once_and_the_first_open_time_stays(): void
    {
        $token = str_repeat('h', 43);
        $letter = $this->approvedLetter($token);

        $this->get($this->url($letter, $token))->assertOk();
        $first = $letter->fresh()->parties[0];
        $this->assertNotEmpty($first['opened_at']);
        $this->assertSame($first['opened_at'], $first['last_opened_at']);
        $this->assertSame(1, $first['open_count']);

        $this->travel(5)->minutes();
        $this->get($this->url($letter, $token))->assertOk();
        $second = $letter->fresh()->parties[0];
        $this->assertSame($first['opened_at'], $second['opened_at']);
        $this->assertNotSame($first['last_opened_at'], $second['last_opened_at']);
        $this->assertSame(2, $second['open_count']);
        $this->assertSame('External recipient', $letter->fresh()->events[0]['target']);
    }

    public function test_a_void_letter_shows_the_neutral_page_without_stamping(): void
    {
        $token = str_repeat('i', 43);
        $letter = $this->approvedLetter($token, [], ['status' => Correspondence::STATUS_VOID]);

        $this->get($this->url($letter, $token))
            ->assertOk()
            ->assertSee('این نامه باطل شده است')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $this->assertSame(0, $letter->fresh()->parties[0]['open_count']);
    }

    public function test_a_superseded_letter_names_the_approved_normal_replacement_number(): void
    {
        $token = str_repeat('j', 43);
        $original = $this->approvedLetter($token, [], ['status' => Correspondence::STATUS_SUPERSEDED]);
        $this->approvedLetter(str_repeat('k', 43), [], ['supersedes_id' => $original->id, 'number' => 'R-4471']);

        $this->get($this->url($original, $token))
            ->assertOk()
            ->assertSee('جایگزین شده است')
            ->assertSee(convertToPersian('R-4471'));
    }

    public function test_a_superseded_letter_stays_neutral_when_the_replacement_is_not_approved_and_normal(): void
    {
        $token = str_repeat('l', 43);
        $original = $this->approvedLetter($token, [], ['status' => Correspondence::STATUS_SUPERSEDED]);
        $this->approvedLetter(str_repeat('m', 43), [], ['supersedes_id' => $original->id, 'number' => 'R-5512', 'status' => Correspondence::STATUS_SHARED]);
        $this->approvedLetter(str_repeat('n', 43), [], ['supersedes_id' => $original->id, 'number' => 'R-5513', 'classification' => Correspondence::CLASSIFICATION_CONFIDENTIAL]);

        $this->get($this->url($original, $token))
            ->assertOk()
            ->assertSee('جایگزین شده است')
            ->assertDontSee(convertToPersian('R-5512'))
            ->assertDontSee(convertToPersian('R-5513'));
    }

    public function test_the_pdf_route_returns_the_cached_file_stamps_on_get_and_not_on_head(): void
    {
        $token = str_repeat('o', 43);
        $letter = $this->approvedLetter($token);
        $this->cachePdf($letter);

        $this->call('HEAD', $this->url($letter, $token, true))->assertOk();
        $this->assertSame(0, $letter->fresh()->parties[0]['open_count']);

        $response = $this->get($this->url($letter, $token, true))->assertOk()->assertHeader('X-Frame-Options', 'DENY');
        $this->assertStringContainsString('.pdf', (string) $response->headers->get('content-disposition'));
        $this->assertSame(1, $letter->fresh()->parties[0]['open_count']);
    }

    public function test_the_pdf_route_has_the_same_empty_404s_and_a_missing_pdf_is_a_404(): void
    {
        $token = str_repeat('p', 43);
        $letter = $this->approvedLetter($token);
        Correspondence::query()->whereKey($letter->id)->toBase()->update(['spec' => '[]']);
        $draft = $this->approvedLetter($token, [], ['status' => Correspondence::STATUS_SHARED]);

        $this->get($this->url($letter, $token, true))->assertNotFound()->assertContent('');
        $this->get($this->url($draft, $token, true))->assertNotFound()->assertContent('');
        $this->get($this->url($letter, str_repeat('q', 43), true))->assertNotFound()->assertContent('');
        $this->assertSame(0, $letter->fresh()->parties[0]['open_count']);
    }

    public function test_head_on_the_pdf_route_answers_200_without_rendering_the_pdf_or_stamping_the_open(): void
    {
        Storage::fake('local');
        $token = str_repeat('h', 43);
        $letter = $this->approvedLetter($token);

        $response = $this->call('HEAD', $this->url($letter, $token, true))->assertOk();

        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('Content-Type'));
        $this->assertSame('', $response->getContent());
        $this->assertSame([], Storage::disk('local')->allFiles("correspondence/{$letter->id}"));
        $fresh = $letter->fresh();
        $this->assertSame(0, $fresh->parties[0]['open_count']);
        $this->assertArrayNotHasKey('opened_at', $fresh->parties[0]);
    }

    public function test_the_pdf_route_of_a_void_letter_shows_the_neutral_page(): void
    {
        $token = str_repeat('r', 43);
        $letter = $this->approvedLetter($token, [], ['status' => Correspondence::STATUS_VOID]);

        $this->get($this->url($letter, $token, true))->assertOk()->assertSee('این نامه باطل شده است');
    }

    public function test_letter_images_get_one_signed_pass_only_inside_the_letters_own_folder(): void
    {
        $token = str_repeat('s', 43);
        $letter = $this->approvedLetter($token);
        $own = "correspondence/{$letter->id}/signature-5.png";
        $letter->forceFill(['parties' => [...$letter->parties, ['role' => 'approver', 'name' => 'Signer', 'signature_path' => $own]]])->save();
        $presenter = new CorrespondencePresenter();
        $signer = fn (string $path, string $mode = 'link'): string => $presenter->paperData(tap(clone $letter)->forceFill(['parties' => [['role' => 'approver', 'name' => 'Signer', 'signature_path' => $path]]]), $mode)['signer']['src'];

        $this->get($this->url($letter, $token))->assertOk()->assertSee('signature=', false);
        $this->assertStringContainsString('signature=', $signer($own));
        $this->assertSame('', $signer("correspondence/{$letter->id}/../signature-5.png"));
        $this->assertSame('', $signer('correspondence/999999/signature-5.png'));
        $this->assertSame('', $signer('users/signatures/signature-5.png'));

        $this->actingAs($letter->user);
        $this->assertStringContainsString('signature=', $signer($own, 'screen'));
        $this->assertSame($signer($own, 'screen'), $signer($own, 'screen'));
        $this->assertSame(Storage::disk('local')->path($own), $signer($own, 'pdf'));
    }
}
