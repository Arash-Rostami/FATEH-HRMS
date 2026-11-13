<?php

namespace Tests\Feature\Http;

use App\Models\Correspondence;
use App\Models\User;
use App\Services\Correspondence\CorrespondencePdf;
use App\Support\FileNamer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use Tests\TestCase;

class CorrespondencePdfControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        config(['app.off' => []]);
        Storage::fake('local');
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

    private function cachePdf(Correspondence $letter): void
    {
        $hash = (new ReflectionMethod(CorrespondencePdf::class, 'hashOf'))->invoke(null, $letter->fresh());
        $path = "correspondence/{$letter->id}/letter-{$hash}.pdf";
        Storage::disk('local')->put($path, '%PDF-1.4 cached');
        $letter->forceFill(['pdf_path' => $path, 'pdf_hash' => $hash])->saveQuietly();
    }

    private function approvedLetter(User $owner, array $extra = []): Correspondence
    {
        return Correspondence::forceCreate($extra + [
            'user_id' => $owner->id,
            'direction' => Correspondence::DIRECTION_OUT,
            'type' => 'letter',
            'status' => Correspondence::STATUS_APPROVED,
            'classification' => Correspondence::CLASSIFICATION_NORMAL,
            'subject' => 'PDF test',
            'number' => 'L/' . uniqid(),
            'letter_date' => '1405/01/01',
            'content_hash' => str_repeat('c', 64),
            'spec' => ['blocks' => [['type' => 'text', 'text' => 'body']]],
            'parties' => [],
            'recipients' => [],
            'events' => [],
            'meta' => ['snapshot' => ['taken_at' => now()->toIso8601String()]],
        ]);
    }

    public function test_download_is_private_to_visible_users_and_returns_the_cached_pdf_with_safe_headers(): void
    {
        $owner = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $stranger = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $letter = $this->approvedLetter($owner);

        $this->actingAs($stranger)->get(route('correspondence.pdf', ['id' => $letter->id]))->assertNotFound();

        $hashMethod = new ReflectionMethod(CorrespondencePdf::class, 'hashOf');
        $hash = $hashMethod->invoke(null, $letter->fresh());
        $path = "correspondence/{$letter->id}/letter-{$hash}.pdf";
        Storage::disk('local')->put($path, '%PDF-1.4 cached');
        $letter->forceFill(['pdf_path' => $path, 'pdf_hash' => $hash])->saveQuietly();

        $response = $this->actingAs($owner)->get(route('correspondence.pdf', ['id' => $letter->id]))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->assertStringContainsString('.pdf', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_module_off_hides_the_authenticated_pdf_endpoint(): void
    {
        $owner = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $letter = $this->approvedLetter($owner);
        config(['app.off' => ['correspondence']]);

        $this->actingAs($owner)->get(route('correspondence.pdf', ['id' => $letter->id]))->assertNotFound();
    }

    public function test_a_letter_that_is_not_approved_is_not_downloadable_even_by_its_author(): void
    {
        $owner = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $letter = $this->approvedLetter($owner, ['status' => Correspondence::STATUS_SHARED]);

        $this->actingAs($owner)->get(route('correspondence.pdf', ['id' => $letter->id]))->assertNotFound();
    }

    public function test_a_registry_clerk_who_cannot_view_the_letter_cannot_download_it(): void
    {
        $owner = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $clerk = User::factory()->create(['role' => 'user', 'status' => 'active', 'extra' => ['admin' => ['correspondence_registry' => true]]]);
        $letter = $this->approvedLetter($owner);
        $this->cachePdf($letter);

        $this->actingAs($clerk)->get(route('correspondence.pdf', ['id' => $letter->id]))->assertNotFound();
    }

    public function test_a_recipient_can_download_and_the_file_is_named_from_the_letter_number(): void
    {
        $owner = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $recipient = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $letter = $this->approvedLetter($owner, ['number' => 'L-1405-77', 'recipients' => [$recipient->id]]);
        $this->cachePdf($letter);

        $response = $this->actingAs($recipient)->get(route('correspondence.pdf', ['id' => $letter->id]))->assertOk();

        $this->assertStringContainsString(FileNamer::download('pdf', 'L-1405-77'), (string) $response->headers->get('Content-Disposition'));
    }

    public function test_a_failing_pdf_builder_redirects_back_with_a_toast_flash_and_a_missing_letter_is_a_404(): void
    {
        $owner = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $letter = $this->approvedLetter($owner);
        Correspondence::query()->whereKey($letter->id)->toBase()->update(['spec' => '[]']);

        $this->actingAs($owner)->get(route('correspondence.pdf', ['id' => $letter->id]))
            ->assertRedirect(route('correspondence', ['open' => $letter->id]))
            ->assertSessionHas('correspondence_error');
        $this->actingAs($owner)->get(route('correspondence.pdf', ['id' => $letter->id + 900000]))->assertNotFound();
    }

    public function test_a_guest_is_sent_to_login(): void
    {
        $owner = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $letter = $this->approvedLetter($owner);

        $this->get(route('correspondence.pdf', ['id' => $letter->id]))->assertRedirect();
    }
}
