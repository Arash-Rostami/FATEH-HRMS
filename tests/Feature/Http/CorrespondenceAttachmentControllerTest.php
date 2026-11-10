<?php

namespace Tests\Feature\Http;

use App\Models\Correspondence;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CorrespondenceAttachmentControllerTest extends TestCase
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

    private function user(): User
    {
        return User::factory()->create(['role' => 'user', 'status' => 'active']);
    }

    private function letterWith(User $owner, array $attachment, array $extra = []): Correspondence
    {
        $letter = Correspondence::forceCreate($extra + [
            'user_id' => $owner->id,
            'direction' => Correspondence::DIRECTION_OUT,
            'type' => 'letter',
            'status' => Correspondence::STATUS_SHARED,
            'classification' => Correspondence::CLASSIFICATION_NORMAL,
            'subject' => 'Attachment test',
            'spec' => ['blocks' => [['type' => 'text', 'text' => 'body']]],
            'parties' => [],
            'recipients' => [],
            'events' => [],
        ]);
        $letter->forceFill(['spec' => [...$letter->spec, 'attachments' => [$attachment]]])->saveQuietly();

        return $letter;
    }

    private function stored(User $owner, string $file = 'report.txt', string $name = 'report.txt', array $extra = []): Correspondence
    {
        $letter = $this->letterWith($owner, ['path' => 'pending', 'name' => $name], $extra);
        $path = "correspondence/{$letter->id}/{$file}";
        Storage::disk('local')->put($path, 'file');
        $letter->forceFill(['spec' => [...$letter->spec, 'attachments' => [['path' => $path, 'name' => $name]]]])->saveQuietly();

        return $letter;
    }

    private function url(Correspondence $letter, int $index = 0): string
    {
        return route('correspondence.attachment', ['id' => $letter->id, 'index' => $index]);
    }

    public function test_a_guest_is_sent_to_login(): void
    {
        $letter = $this->stored($this->user());

        $this->get($this->url($letter))->assertRedirect();
    }

    public function test_the_author_downloads_the_file_with_private_headers(): void
    {
        $owner = $this->user();
        $letter = $this->stored($owner);

        $response = $this->actingAs($owner)->get($this->url($letter))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_a_stored_name_with_fraction_slash_and_percent_still_downloads_as_an_attachment(): void
    {
        $owner = $this->user();
        $letter = $this->stored($owner, 'report.txt', 'نسبت ½ ⁄ 100%.txt');

        $response = $this->actingAs($owner)->get($this->url($letter))->assertOk();

        $this->assertStringStartsWith('attachment', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_a_recipient_of_a_shared_letter_downloads_the_file(): void
    {
        $owner = $this->user();
        $recipient = $this->user();
        $letter = $this->stored($owner, extra: ['recipients' => [$recipient->id]]);

        $this->actingAs($recipient)->get($this->url($letter))->assertOk();
    }

    public function test_a_user_who_cannot_see_the_letter_gets_404(): void
    {
        $letter = $this->stored($this->user());

        $this->actingAs($this->user())->get($this->url($letter))->assertNotFound();
    }

    public function test_a_recipient_cannot_download_from_a_draft(): void
    {
        $recipient = $this->user();
        $letter = $this->stored($this->user(), extra: ['status' => Correspondence::STATUS_DRAFT, 'recipients' => [$recipient->id]]);

        $this->actingAs($recipient)->get($this->url($letter))->assertNotFound();
    }

    public function test_a_soft_deleted_letter_is_not_downloadable_even_by_its_author(): void
    {
        $owner = $this->user();
        $letter = $this->stored($owner);
        $letter->forceFill(['deleted_at' => now()])->saveQuietly();

        $this->actingAs($owner)->get($this->url($letter))->assertNotFound();
    }

    public function test_a_stored_path_outside_the_letter_folder_is_refused(): void
    {
        $owner = $this->user();
        $letter = $this->stored($owner);
        Storage::disk('local')->put('secret/other.txt', 'secret');
        $letter->forceFill(['spec' => [...$letter->spec, 'attachments' => [['path' => 'secret/other.txt', 'name' => 'other.txt']]]])->saveQuietly();

        $this->actingAs($owner)->get($this->url($letter))->assertNotFound();
    }

    public function test_a_traversal_path_is_refused(): void
    {
        $owner = $this->user();
        $letter = $this->stored($owner);
        Storage::disk('local')->put('secret/other.txt', 'secret');
        $letter->forceFill(['spec' => [...$letter->spec, 'attachments' => [['path' => "correspondence/{$letter->id}/../../secret/other.txt", 'name' => 'other.txt']]]])->saveQuietly();

        $this->actingAs($owner)->get($this->url($letter))->assertNotFound();
    }

    public function test_a_missing_file_gets_404(): void
    {
        $owner = $this->user();
        $letter = $this->stored($owner);
        Storage::disk('local')->delete("correspondence/{$letter->id}/report.txt");

        $this->actingAs($owner)->get($this->url($letter))->assertNotFound();
    }

    public function test_an_index_out_of_range_gets_404(): void
    {
        $owner = $this->user();
        $letter = $this->stored($owner);

        $this->actingAs($owner)->get($this->url($letter, 5))->assertNotFound();
    }

    public function test_a_pdf_is_served_inline_under_its_name(): void
    {
        $owner = $this->user();
        $letter = $this->stored($owner, 'scan.pdf', 'scan.pdf');

        $disposition = (string) $this->actingAs($owner)->get($this->url($letter))->assertOk()->headers->get('Content-Disposition');

        $this->assertStringStartsWith('inline', $disposition);
        $this->assertStringContainsString('scan.pdf', $disposition);
    }

    public function test_a_stored_name_with_slashes_percent_or_persian_letters_still_downloads(): void
    {
        $owner = $this->user();
        $letter = $this->stored($owner, 'scan.pdf', "a/b\\c%d نامه\r\n.pdf");

        $disposition = (string) $this->actingAs($owner)->get($this->url($letter))->assertOk()->headers->get('Content-Disposition');

        $this->assertStringStartsWith('inline', $disposition);
        $this->assertStringNotContainsString("\n", $disposition);
    }

    public function test_other_file_types_are_served_as_an_attachment(): void
    {
        $owner = $this->user();
        $letter = $this->stored($owner);

        $disposition = (string) $this->actingAs($owner)->get($this->url($letter))->assertOk()->headers->get('Content-Disposition');

        $this->assertStringStartsWith('attachment', $disposition);
    }

    public function test_module_off_hides_the_endpoint(): void
    {
        $owner = $this->user();
        $letter = $this->stored($owner);
        config(['app.off' => ['correspondence']]);

        $this->actingAs($owner)->get($this->url($letter))->assertNotFound();
    }
}
