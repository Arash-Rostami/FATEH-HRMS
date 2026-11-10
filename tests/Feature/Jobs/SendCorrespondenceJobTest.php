<?php

namespace Tests\Feature\Jobs;

use App\Jobs\SendCorrespondenceJob;
use App\Mail\NotifyMail;
use App\Models\Correspondence;
use App\Models\User;
use App\Services\Correspondence\CorrespondencePdf;
use Illuminate\Mail\PendingMail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use JsonException;
use Mockery;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

class SendCorrespondenceJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        config(['app.off' => []]);
        Mail::fake();
        Queue::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
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

    private function letter(array $parties, array $meta = [], array $extra = []): Correspondence
    {
        $owner = User::factory()->create(['role' => 'user', 'status' => 'active']);

        return Correspondence::forceCreate($extra + [
            'user_id' => $owner->id,
            'direction' => Correspondence::DIRECTION_OUT,
            'type' => 'letter',
            'status' => Correspondence::STATUS_APPROVED,
            'classification' => Correspondence::CLASSIFICATION_NORMAL,
            'sign_mode' => Correspondence::SIGN_MODE_ELECTRONIC,
            'subject' => 'نامه آزمون',
            'number' => 'L/' . uniqid(),
            'letter_date' => '1405/01/01',
            'content_hash' => str_repeat('a', 64),
            'spec' => ['blocks' => [['type' => 'text', 'text' => 'بدنه']]],
            'parties' => $parties,
            'events' => [],
            'meta' => $meta,
        ]);
    }

    private function outside(string $email, string $mode = 'link'): array
    {
        return ['name' => 'مخاطب بیرونی', 'email' => $email, 'role' => 'fyi', 'delivery' => $mode, 'user_id' => null];
    }

    public function test_first_send_claims_a_token_and_mails_the_secure_link(): void
    {
        $letter = $this->letter([$this->outside('recipient@example.test')], ['mail' => ['subject' => 'موضوع جایگزین', 'body' => 'متن plain']]);

        (new SendCorrespondenceJob((int) $letter->id))->handle();

        Mail::assertSent(NotifyMail::class, fn (NotifyMail $mail): bool => $mail->message->subject === 'موضوع جایگزین'
            && $mail->message->lines === ['متن plain']
            && $mail->message->ruleName === 'correspondence'
            && str_contains((string) $mail->message->url, '/c/' . $letter->id . '-')
            && $mail->message->attachments === []);

        $party = $letter->fresh()->parties[0];
        $this->assertNotEmpty($party['sent_at']);
        $this->assertSame(64, strlen($party['token_hash']));
        $this->assertSame('sent', $letter->fresh()->events[0]['action']);
    }

    public function test_send_batches_five_parties_and_dispatches_the_remainder(): void
    {
        $parties = array_map(fn (int $i): array => $this->outside("recipient{$i}@example.test"), range(1, 6));
        $letter = $this->letter($parties);

        (new SendCorrespondenceJob((int) $letter->id))->handle();

        Mail::assertSent(NotifyMail::class, 5);
        Queue::assertPushed(SendCorrespondenceJob::class, fn (SendCorrespondenceJob $job): bool => $job->letterId === (int) $letter->id
            && $job->partyIndexes === [5]);
        $this->assertNotEmpty($letter->fresh()->parties[4]['sent_at']);
        $this->assertEmpty($letter->fresh()->parties[5]['sent_at'] ?? null);
    }

    public function test_tenant_off_is_a_no_op_and_revoke_clears_both_link_hashes(): void
    {
        $letter = $this->letter([$this->outside('recipient@example.test')]);
        config(['app.off' => ['correspondence']]);

        (new SendCorrespondenceJob((int) $letter->id))->handle();
        Mail::assertNothingSent();
        $this->assertEquals($letter->parties, $letter->fresh()->parties);

        config(['app.off' => []]);
        $letter->parties = [[...$this->outside('recipient@example.test'), 'token_hash' => 'old', 'expires_at' => now()->addDay()->toIso8601String(), 'token_hash_next' => 'next', 'expires_at_next' => now()->addDays(2)->toIso8601String()]];
        $letter->saveQuietly();

        SendCorrespondenceJob::revoke($letter, 0);

        $party = $letter->fresh()->parties[0];
        $this->assertNull($party['token_hash']);
        $this->assertNull($party['expires_at']);
        $this->assertArrayNotHasKey('token_hash_next', $party);
        $this->assertArrayNotHasKey('expires_at_next', $party);
    }

    public function test_a_tenant_without_mail_delivers_nothing_and_leaves_the_parties_untouched(): void
    {
        $letter = $this->letter([$this->outside('recipient@example.test')]);

        foreach ([['notify'], ['notify:mail']] as $off) {
            config(['app.off' => $off]);

            (new SendCorrespondenceJob((int) $letter->id))->handle();
            (new SendCorrespondenceJob((int) $letter->id, [0], true))->handle();

            Mail::assertNothingSent();
            Queue::assertNothingPushed();
            $this->assertEquals($letter->parties, $letter->fresh()->parties);
            $this->assertSame([], $letter->fresh()->events);
        }
    }

    private function mailFails(): void
    {
        $pending = Mockery::mock(PendingMail::class);
        $pending->shouldReceive('send')->andThrow(new RuntimeException('smtp down'));
        Mail::shouldReceive('to')->andReturn($pending);
    }

    private function cachePdf(Correspondence $letter, int $bytes = 8): string
    {
        Storage::fake('local');
        $fresh = Correspondence::query()->with('letterhead')->whereKey($letter->id)->first(CorrespondencePdf::renderColumns());
        $hash = (new ReflectionMethod(CorrespondencePdf::class, 'hashOf'))->invoke(null, $fresh);
        $relative = 'correspondence/' . $letter->id . '/letter-' . $hash . '.pdf';
        Storage::disk('local')->put($relative, str_repeat('p', $bytes));
        DB::table('correspondences')->where('id', $letter->id)->update(['pdf_path' => $relative, 'pdf_hash' => $hash]);

        return $hash;
    }

    private function parties(Correspondence $letter): array
    {
        return $letter->fresh()->parties;
    }

    private function sent(string $email, array $extra = []): array
    {
        return [...$this->outside($email), 'sent_at' => now()->subDay()->toIso8601String(), 'token_hash' => 'old', 'expires_at' => now()->addDays(5)->toIso8601String(), 'open_count' => 3, 'opened_at' => now()->subHour()->toIso8601String(), ...$extra];
    }

    public function test_the_sent_event_records_the_content_hash_or_the_cached_pdf_hash(): void
    {
        $plain = $this->letter([$this->outside('recipient@example.test')]);
        (new SendCorrespondenceJob((int) $plain->id))->handle();
        $this->assertSame(str_repeat('a', 64), $plain->fresh()->events[0]['hash']);

        $withPdf = $this->letter([$this->outside('recipient@example.test', 'pdf')]);
        $hash = $this->cachePdf($withPdf);
        (new SendCorrespondenceJob((int) $withPdf->id))->handle();
        $this->assertSame($hash, $withPdf->fresh()->events[0]['hash']);
    }

    public function test_a_resend_keeps_the_old_link_valid_until_the_new_mail_succeeds_then_promotes_it(): void
    {
        $letter = $this->letter([$this->sent('recipient@example.test')]);
        $seen = null;
        $pending = Mockery::mock(PendingMail::class);
        $pending->shouldReceive('send')->once()->andReturnUsing(function () use (&$seen, $letter): void {
            $seen = json_decode((string) DB::table('correspondences')->where('id', $letter->id)->value('parties'), true)[0];
        });
        Mail::shouldReceive('to')->andReturn($pending);

        (new SendCorrespondenceJob((int) $letter->id, [0], true, 77))->handle();

        $this->assertSame('old', $seen['token_hash']);
        $this->assertSame(64, strlen($seen['token_hash_next']));
        $this->assertSame('sending', $seen['delivery_error']);

        $party = $this->parties($letter)[0];
        $this->assertSame($seen['token_hash_next'], $party['token_hash']);
        $this->assertArrayNotHasKey('token_hash_next', $party);
        $this->assertArrayNotHasKey('expires_at_next', $party);
        $this->assertArrayNotHasKey('sending_at', $party);
        $this->assertNull($party['delivery_error']);
        $this->assertNull($party['opened_at']);
        $this->assertSame(0, $party['open_count']);
        $this->assertSame(77, $letter->fresh()->events[0]['user_id']);
    }

    public function test_a_failed_resend_mail_keeps_the_old_link_and_drops_the_pending_one(): void
    {
        $letter = $this->letter([$this->sent('recipient@example.test')]);
        $this->mailFails();

        (new SendCorrespondenceJob((int) $letter->id, [0], true))->handle();

        $party = $this->parties($letter)[0];
        $this->assertSame('old', $party['token_hash']);
        $this->assertNotEmpty($party['sent_at']);
        $this->assertArrayNotHasKey('token_hash_next', $party);
        $this->assertArrayNotHasKey('expires_at_next', $party);
        $this->assertSame('mail_failed', $party['delivery_error']);
        $this->assertSame([], $letter->fresh()->events);
    }

    public function test_a_failed_first_send_rolls_the_claim_back(): void
    {
        $letter = $this->letter([$this->outside('recipient@example.test')]);
        $this->mailFails();

        (new SendCorrespondenceJob((int) $letter->id))->handle();

        $party = $this->parties($letter)[0];
        $this->assertNull($party['sent_at']);
        $this->assertNull($party['token_hash']);
        $this->assertNull($party['expires_at']);
        $this->assertSame('mail_failed', $party['delivery_error']);
        $this->assertSame([], $letter->fresh()->events);
    }

    public function test_a_stale_sending_marker_is_claimed_again_but_a_fresh_one_is_left_alone(): void
    {
        $stale = [...$this->outside('stale@example.test'), 'delivery_error' => 'sending', 'sending_at' => now()->subMinutes(10)->toIso8601String()];
        $fresh = [...$this->outside('fresh@example.test'), 'delivery_error' => 'sending', 'sending_at' => now()->toIso8601String()];
        $letter = $this->letter([$stale, $fresh]);

        (new SendCorrespondenceJob((int) $letter->id))->handle();

        Mail::assertSent(NotifyMail::class, 1);
        $parties = $this->parties($letter);
        $this->assertNotEmpty($parties[0]['sent_at']);
        $this->assertSame('sending', $parties[1]['delivery_error']);
        $this->assertEmpty($parties[1]['sent_at'] ?? null);
    }

    public function test_stamp_error_on_a_first_send_clears_the_link_and_on_a_resend_keeps_it(): void
    {
        $first = [...$this->outside('first@example.test'), 'delivery_error' => 'sending', 'sending_at' => now()->toIso8601String(), 'token_hash' => 'hash-1', 'expires_at' => now()->addDay()->toIso8601String()];
        $resend = $this->sent('resend@example.test', ['delivery_error' => 'sending', 'sending_at' => now()->toIso8601String()]);
        $settled = [...$this->outside('settled@example.test'), 'delivery_error' => 'bad_email'];
        $letter = $this->letter([$first, $resend, $settled]);

        (new SendCorrespondenceJob((int) $letter->id))->failed(new RuntimeException('timeout'));

        $parties = $this->parties($letter);
        $this->assertSame('send_failed', $parties[0]['delivery_error']);
        $this->assertNull($parties[0]['token_hash']);
        $this->assertNull($parties[0]['expires_at']);
        $this->assertArrayNotHasKey('sending_at', $parties[0]);
        $this->assertSame('mail_failed', $parties[1]['delivery_error']);
        $this->assertSame('old', $parties[1]['token_hash']);
        $this->assertSame('bad_email', $parties[2]['delivery_error']);
    }

    public function test_a_confidential_letter_is_refused_and_its_links_are_cleared(): void
    {
        $party = [...$this->outside('recipient@example.test'), 'token_hash' => 'old', 'expires_at' => now()->addDay()->toIso8601String(), 'token_hash_next' => 'next', 'expires_at_next' => now()->addDay()->toIso8601String()];
        $letter = $this->letter([$party], [], ['classification' => Correspondence::CLASSIFICATION_CONFIDENTIAL]);

        (new SendCorrespondenceJob((int) $letter->id))->handle();

        Mail::assertNothingSent();
        $stored = $this->parties($letter)[0];
        $this->assertSame('refused', $stored['delivery_error']);
        $this->assertNull($stored['token_hash']);
        $this->assertArrayNotHasKey('token_hash_next', $stored);
    }

    public function test_a_wet_letter_waits_for_the_wet_signature_before_any_mail(): void
    {
        $pending = $this->letter([$this->outside('recipient@example.test')], [], ['sign_mode' => Correspondence::SIGN_MODE_WET]);
        (new SendCorrespondenceJob((int) $pending->id))->handle();
        Mail::assertNothingSent();
        $this->assertSame('wet_pending', $this->parties($pending)[0]['delivery_error']);

        $signed = $this->letter([$this->outside('recipient@example.test')], [], ['sign_mode' => Correspondence::SIGN_MODE_WET, 'events' => [['action' => 'wet_signed', 'at' => now()->toIso8601String(), 'user_id' => null]]]);
        (new SendCorrespondenceJob((int) $signed->id))->handle();
        Mail::assertSent(NotifyMail::class, 1);
    }

    public function test_a_letter_that_is_not_approved_is_never_mailed(): void
    {
        $letter = $this->letter([$this->outside('recipient@example.test')], [], ['status' => Correspondence::STATUS_VOID]);

        (new SendCorrespondenceJob((int) $letter->id))->handle();

        Mail::assertNothingSent();
        $this->assertEmpty($this->parties($letter)[0]['sent_at'] ?? null);
    }

    public function test_missing_and_malformed_emails_are_marked_and_unknown_modes_are_skipped(): void
    {
        $letter = $this->letter([
            [...$this->outside(''), 'email' => ''],
            $this->outside('not an email'),
            $this->outside(str_repeat('a', 250) . '@example.test'),
            $this->outside('mode@example.test', 'carrier-pigeon'),
            [...$this->outside('internal@example.test'), 'user_id' => 99999999],
        ]);

        (new SendCorrespondenceJob((int) $letter->id))->handle();

        Mail::assertNothingSent();
        $parties = $this->parties($letter);
        $this->assertSame('no_email', $parties[0]['delivery_error']);
        $this->assertSame('bad_email', $parties[1]['delivery_error']);
        $this->assertSame('bad_email', $parties[2]['delivery_error']);
        $this->assertArrayNotHasKey('delivery_error', $parties[3]);
        $this->assertArrayNotHasKey('delivery_error', $parties[4]);
    }

    public function test_the_recipient_cap_marks_new_outside_parties_as_too_many(): void
    {
        config(['ai.correspondence.max_recipients' => 1]);
        $letter = $this->letter([$this->sent('done@example.test'), $this->outside('extra@example.test')]);

        (new SendCorrespondenceJob((int) $letter->id))->handle();

        Mail::assertNothingSent();
        $parties = $this->parties($letter);
        $this->assertSame('too_many', $parties[1]['delivery_error']);
        $this->assertSame('old', $parties[0]['token_hash']);
    }

    public function test_an_already_sent_party_is_skipped_unless_resending_and_chosen_indexes_limit_the_run(): void
    {
        $letter = $this->letter([$this->sent('done@example.test'), $this->outside('one@example.test'), $this->outside('two@example.test')]);

        (new SendCorrespondenceJob((int) $letter->id, [2]))->handle();

        Mail::assertSent(NotifyMail::class, 1);
        $parties = $this->parties($letter);
        $this->assertSame('old', $parties[0]['token_hash']);
        $this->assertEmpty($parties[1]['sent_at'] ?? null);
        $this->assertNotEmpty($parties[2]['sent_at']);
    }

    public function test_the_default_subject_names_the_app_on_one_capped_line_with_a_fallback(): void
    {
        config(['app.name' => "Fa\r\nteh"]);
        $letter = $this->letter([$this->outside('recipient@example.test')]);
        (new SendCorrespondenceJob((int) $letter->id))->handle();
        Mail::assertSent(NotifyMail::class, fn (NotifyMail $mail): bool => $mail->message->subject === 'نامه از Fa teh');

        Mail::fake();
        config(['app.name' => str_repeat('x', 80)]);
        $long = $this->letter([$this->outside('recipient@example.test')]);
        (new SendCorrespondenceJob((int) $long->id))->handle();
        Mail::assertSent(NotifyMail::class, fn (NotifyMail $mail): bool => $mail->message->subject === 'نامه از ' . str_repeat('x', 60));

        Mail::fake();
        config(['app.name' => " \r\n "]);
        $blank = $this->letter([$this->outside('recipient@example.test')]);
        (new SendCorrespondenceJob((int) $blank->id))->handle();
        Mail::assertSent(NotifyMail::class, fn (NotifyMail $mail): bool => $mail->message->subject === 'نامه');
    }

    public function test_the_custom_mail_text_replaces_the_wording_but_keeps_the_link(): void
    {
        $mail = ['subject' => "  عنوان\r\nدوم  ", 'body' => "خط اول\n\nخط دوم"];
        $letter = $this->letter([$this->outside('recipient@example.test')], ['mail' => $mail]);

        (new SendCorrespondenceJob((int) $letter->id))->handle();

        Mail::assertSent(NotifyMail::class, fn (NotifyMail $sent): bool => $sent->message->subject === 'عنوان دوم'
            && $sent->message->lines === ['خط اول', 'خط دوم']
            && str_contains((string) $sent->message->url, '/c/' . $letter->id . '-'));
    }

    public function test_the_custom_subject_is_capped_and_a_blank_body_falls_back_to_the_default_line(): void
    {
        $letter = $this->letter([$this->outside('recipient@example.test')], ['mail' => ['subject' => str_repeat('ب', 200), 'body' => "  \n  "]]);

        (new SendCorrespondenceJob((int) $letter->id))->handle();

        Mail::assertSent(NotifyMail::class, fn (NotifyMail $sent): bool => mb_strlen($sent->message->subject) === 150
            && count($sent->message->lines) === 1
            && str_contains($sent->message->lines[0], 'یک نامه برای شما ارسال شده است'));
    }

    public function test_the_custom_mail_text_applies_to_a_resend_too(): void
    {
        $letter = $this->letter([$this->sent('recipient@example.test')], ['mail' => ['subject' => 'یادآوری', 'body' => 'متن تازه']]);

        (new SendCorrespondenceJob((int) $letter->id, [0], true))->handle();

        Mail::assertSent(NotifyMail::class, fn (NotifyMail $sent): bool => $sent->message->subject === 'یادآوری' && $sent->message->lines === ['متن تازه']);
    }

    public function test_the_link_lifetime_falls_back_to_thirty_days_for_unusable_values_and_null_never_expires(): void
    {
        foreach ([0, -4, 'abc'] as $days) {
            config(['ai.correspondence.link_ttl_days' => $days]);
            $letter = $this->letter([$this->outside('recipient@example.test')]);
            (new SendCorrespondenceJob((int) $letter->id))->handle();
            $expires = $this->parties($letter)[0]['expires_at'];
            $this->assertEqualsWithDelta(30 * 86400, Carbon::parse($expires)->getTimestamp() - time(), 120);
        }

        config(['ai.correspondence.link_ttl_days' => 7]);
        $week = $this->letter([$this->outside('recipient@example.test')]);
        (new SendCorrespondenceJob((int) $week->id))->handle();
        $this->assertEqualsWithDelta(7 * 86400, Carbon::parse($this->parties($week)[0]['expires_at'])->getTimestamp() - time(), 120);

        config(['ai.correspondence.link_ttl_days' => null]);
        $never = $this->letter([$this->outside('recipient@example.test')]);
        (new SendCorrespondenceJob((int) $never->id))->handle();
        $party = $this->parties($never)[0];
        $this->assertNull($party['expires_at']);
        $this->assertNotEmpty($party['sent_at']);
    }

    public function test_pdf_delivery_attaches_the_cached_file_and_both_mode_adds_the_link(): void
    {
        $letter = $this->letter([$this->outside('pdf@example.test', 'pdf'), $this->outside('both@example.test', 'both')]);
        $this->cachePdf($letter);

        (new SendCorrespondenceJob((int) $letter->id))->handle();

        Mail::assertSent(NotifyMail::class, fn (NotifyMail $sent): bool => $sent->message->url === null
            && count($sent->message->attachments) === 1
            && str_ends_with($sent->message->attachments[0], '.pdf'));
        Mail::assertSent(NotifyMail::class, fn (NotifyMail $sent): bool => str_contains((string) $sent->message->url, '/c/' . $letter->id . '-')
            && !str_ends_with((string) $sent->message->url, '/pdf')
            && count($sent->message->attachments) === 1);
    }

    public function test_an_oversize_pdf_is_sent_as_a_download_link_instead_of_an_attachment(): void
    {
        $letter = $this->letter([$this->outside('pdf@example.test', 'pdf')]);
        $this->cachePdf($letter, 15728641);

        (new SendCorrespondenceJob((int) $letter->id))->handle();

        Mail::assertSent(NotifyMail::class, fn (NotifyMail $sent): bool => $sent->message->attachments === []
            && str_ends_with((string) $sent->message->url, '/pdf'));
    }

    public function test_a_pdf_that_cannot_be_built_blocks_pdf_mode_but_not_the_link_of_both_mode(): void
    {
        $letter = $this->letter([$this->outside('pdf@example.test', 'pdf'), $this->outside('both@example.test', 'both')]);
        $block = ['type' => 'text', 'text' => str_repeat('&', 20000)];
        DB::table('correspondences')->where('id', $letter->id)->update(['spec' => json_encode(['blocks' => array_fill(0, 30, $block)], JSON_THROW_ON_ERROR)]);

        (new SendCorrespondenceJob((int) $letter->id))->handle();

        Mail::assertSent(NotifyMail::class, 1);
        $parties = $this->parties($letter);
        $this->assertSame('pdf_failed', $parties[0]['delivery_error']);
        $this->assertEmpty($parties[0]['sent_at'] ?? null);
        $this->assertSame('pdf_failed', $parties[1]['delivery_error']);
        $this->assertNotEmpty($parties[1]['sent_at']);
    }

    public function test_revoke_skips_internal_parties_and_keeps_the_link_revoked_event_within_the_cap(): void
    {
        $internal = [...$this->outside('internal@example.test'), 'user_id' => 99999999, 'token_hash' => 'keep'];
        $letter = $this->letter([$internal, $this->sent('outside@example.test', ['name' => "نام\n  دراز"])]);

        SendCorrespondenceJob::revoke($letter, 0, 5);
        $this->assertSame('keep', $this->parties($letter)[0]['token_hash']);
        $this->assertSame([], $letter->fresh()->events);

        SendCorrespondenceJob::revoke($letter, 1, 5);
        $event = $letter->fresh()->events[0];
        $this->assertSame('link_revoked', $event['action']);
        $this->assertSame('نام دراز', $event['target']);
        $this->assertSame(5, $event['user_id']);
        $this->assertNull($this->parties($letter)[1]['token_hash']);
    }

    public function test_revoke_still_clears_the_links_when_the_event_log_is_at_the_soft_cap(): void
    {
        $events = array_fill(0, 900, ['action' => 'commented', 'at' => now()->toIso8601String(), 'user_id' => null]);
        $letter = $this->letter([$this->sent('outside@example.test')], [], ['events' => $events]);

        SendCorrespondenceJob::revoke($letter, 0);

        $this->assertNull($this->parties($letter)[0]['token_hash']);
        $this->assertCount(900, $letter->fresh()->events);
    }

    public function test_write_quiet_throws_on_unencodable_json_and_never_touches_updated_at(): void
    {
        $letter = $this->letter([$this->outside('recipient@example.test')]);
        $before = DB::table('correspondences')->where('id', $letter->id)->value('updated_at');
        Carbon::setTestNow(now()->addHours(3));

        SendCorrespondenceJob::writeQuiet($letter, ['events' => [['action' => 'opened']]]);
        $this->assertSame($before, DB::table('correspondences')->where('id', $letter->id)->value('updated_at'));
        $this->assertSame('opened', $letter->fresh()->events[0]['action']);

        $this->expectException(JsonException::class);
        SendCorrespondenceJob::writeQuiet($letter, ['events' => ["\xB1\x31"]]);
    }

    public function test_label_prefers_name_then_org_then_email_on_one_collapsed_capped_line(): void
    {
        $this->assertSame('علی رضا', SendCorrespondenceJob::label(['name' => "علی\n  رضا", 'org' => 'سازمان'], 'a@example.test'));
        $this->assertSame('سازمان', SendCorrespondenceJob::label(['name' => ' ', 'org' => 'سازمان'], 'a@example.test'));
        $this->assertSame('a@example.test', SendCorrespondenceJob::label([], 'a@example.test'));
        $this->assertSame(120, mb_strlen(SendCorrespondenceJob::label(['name' => str_repeat('م', 400)])));
    }

    public function test_sending_fresh_only_holds_inside_the_claim_window(): void
    {
        $this->assertTrue(SendCorrespondenceJob::sendingFresh(['delivery_error' => 'sending', 'sending_at' => now()->subSeconds(60)->toIso8601String()]));
        $this->assertFalse(SendCorrespondenceJob::sendingFresh(['delivery_error' => 'sending', 'sending_at' => now()->subSeconds(200)->toIso8601String()]));
        $this->assertFalse(SendCorrespondenceJob::sendingFresh(['delivery_error' => 'sending']));
        $this->assertFalse(SendCorrespondenceJob::sendingFresh(['delivery_error' => 'mail_failed', 'sending_at' => now()->toIso8601String()]));
        $this->assertFalse(SendCorrespondenceJob::sendingFresh(['delivery_error' => 'sending', 'sending_at' => str_repeat('9', 60)]));
    }

    public function test_the_job_is_a_single_try_150_second_after_commit_job(): void
    {
        $job = new SendCorrespondenceJob(1);

        $this->assertSame(1, $job->tries);
        $this->assertSame(150, $job->timeout);
        $this->assertTrue($job->failOnTimeout);
        $this->assertTrue($job->afterCommit);
    }
}
