<?php

namespace Tests\Feature\Models;

use Closure;
use App\Models\AiReport;
use App\Models\Correspondence;
use App\Models\Letterhead;
use App\Models\User;
use App\Services\ContentSanitizerService;
use App\Services\Menu\StateService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\TestCase;

class CorrespondenceModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        config(['cache.default' => 'array', 'cache.limiter' => 'array']);
        Cache::setDefaultDriver('array');
        Cache::flush();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
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

    private function letter(array $overrides = []): Correspondence
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        return Correspondence::forceCreate(array_replace([
            'user_id' => $user->id,
            'direction' => Correspondence::DIRECTION_OUT,
            'type' => 'letter',
            'subject' => 'موضوع آزمایشی',
            'spec' => ['blocks' => [['type' => 'text', 'text' => 'متن آزمایشی']]],
        ], $overrides));
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

    public function test_sanitizer_preserves_legitimate_styled_tables_lists_and_persian_text(): void
    {
        $html = '<h2 dir="rtl" style="color:#123456;text-align:right">نامه فارسی</h2><ul><li>نخست</li><li>دوم</li></ul><table><tr><th colspan="2" rowspan="1" style="background-color:#eee">عنوان</th></tr><tr><td dir="rtl" style="font-size:14pt">متن</td><td>داده</td></tr></table>';

        $clean = Correspondence::sanitizeHtml($html);

        $this->assertStringContainsString('<h2', $clean);
        $this->assertStringContainsString('dir="rtl"', $clean);
        $this->assertStringContainsString('text-align: right', $clean);
        $this->assertStringContainsString('<ul', $clean);
        $this->assertStringContainsString('<table', $clean);
        $this->assertStringContainsString('colspan="2"', $clean);
        $this->assertStringContainsString('rowspan="1"', $clean);
        $this->assertStringContainsString('نامه فارسی', $clean);
    }

    public function test_sanitizer_removes_hostile_elements_attributes_and_url_schemes(): void
    {
        $clean = Correspondence::sanitizeHtml('<p onclick="alert(1)" style="color:red;background-image:url(javascript:alert(1));width:expression(1)">safe</p><script>alert(1)</script><img src="data:image/svg+xml,x"><iframe src="https://example.test"></iframe><svg><script>alert(2)</script></svg><math><mi>x</mi></math><a href="javascript:alert(3)">link</a>');

        foreach (['onclick', 'javascript:', 'data:image', 'url(', 'expression(', '<script', '<img', '<iframe', '<svg', '<math'] as $needle) {
            $this->assertStringNotContainsString($needle, strtolower($clean));
        }
        $this->assertStringContainsString('safe', $clean);

        foreach (['javascript:alert(3)', 'data:text/html,x', 'vbscript:alert(4)'] as $href) {
            $hostile = strtolower(Correspondence::sanitizeHtml('<a href="' . $href . '">link</a>'));
            $this->assertStringNotContainsString(explode(':', $href)[0] . ':', $hostile);
            $this->assertStringContainsString('link', $hostile);
        }

        foreach (['https://example.test/a', 'http://example.test/b', 'mailto:user@example.test'] as $href) {
            $safe = Correspondence::sanitizeHtml('<a href="' . $href . '" onclick="x()">link</a>');
            $this->assertStringContainsString('href="' . $href . '"', html_entity_decode($safe));
            $this->assertStringContainsString('rel="noopener"', $safe);
            $this->assertStringNotContainsString('onclick', $safe);
        }
    }

    public function test_sanitizer_rejects_hostile_nesting_floods_and_oversized_blocks_quickly(): void
    {
        $hostile = [
            str_repeat('<div>', 101),
            str_repeat('<h2><ul><li><b><span>', 25),
            str_repeat('<tr><th>', 101),
            str_repeat('<html></html>', 101),
            str_repeat('<p>', 6001),
            str_repeat('x', 400000),
        ];

        foreach ($hostile as $html) {
            $started = hrtime(true);
            $this->expectValidation(fn () => Correspondence::sanitizeHtml($html));
            $elapsed = (hrtime(true) - $started) / 1_000_000_000;
            $this->assertLessThan(2.0, $elapsed);
        }

        $started = hrtime(true);
        $clean = Correspondence::sanitizeHtml(str_repeat('<div title="</div>">', 101));
        $this->assertLessThan(2.0, (hrtime(true) - $started) / 1_000_000_000);
        $this->assertStringNotContainsString('<div', strtolower($clean));
    }

    public function test_sanitizer_does_not_allow_spliced_tags_to_reform(): void
    {
        $clean = Correspondence::sanitizeHtml('<sty<style></style>le>SECRET{x}</style><scr<script>ipt>alert(1)</scr</script>ipt>');

        $this->assertStringNotContainsString('<style', strtolower($clean));
        $this->assertStringNotContainsString('<script', strtolower($clean));
        $this->assertStringContainsString('&lt;style&gt;SECRET', $clean);
        $this->assertStringContainsString('&lt;script&gt;', $clean);
        $this->assertStringNotContainsString('alert(1)', $clean);
    }

    public function test_sanitizer_keeps_the_editor_nodes_and_forces_safe_link_attributes(): void
    {
        $clean = Correspondence::sanitizeHtml('<blockquote><p>نقل</p></blockquote><hr><p>x<sup>2</sup><sub>1</sub> <mark>مهم</mark></p><a href="https://example.test/a" target="_self" rel="author">پیوند</a>');

        foreach (['<blockquote', '<hr', '<sup>', '<sub>', '<mark>', 'href="https://example.test/a"', 'rel="noopener"', 'target="_blank"'] as $needle) {
            $this->assertStringContainsString($needle, $clean);
        }
        $this->assertStringNotContainsString('target="_self"', $clean);
        $this->assertStringNotContainsString('rel="author"', $clean);
    }

    public function test_sanitizer_strips_hostile_relative_obfuscated_and_oversized_hrefs(): void
    {
        foreach (['javascript:alert(1)', 'JaVaScRiPt:alert(1)', "java\tscript:alert(1)", 'java&#9;script:alert(1)', 'data:text/html,x', '/relative/path', 'relative/path', 'ftp://example.test/a', 'https://example.test/' . str_repeat('a', 2100)] as $href) {
            $clean = strtolower(Correspondence::sanitizeHtml('<a href="' . $href . '">link</a>'));

            $this->assertStringNotContainsString('href', $clean, $href);
            $this->assertStringNotContainsString('script:', $clean, $href);
            $this->assertStringContainsString('link', $clean);
        }

        $this->assertStringContainsString('href="mailto:user@example.test"', html_entity_decode(Correspondence::sanitizeHtml('<a href="mailto:user@example.test">mail</a>')));
    }

    public function test_sanitizer_allows_indent_page_break_and_highlight_styles_only_on_their_elements(): void
    {
        $clean = Correspondence::sanitizeHtml('<p style="margin-right:48pt">a</p><h2 style="margin-left:24pt">b</h2><p style="margin-right:30pt">c</p><ul><li style="margin-right:48pt">d</li></ul><hr style="page-break-after: always"><p style="page-break-after: always">e</p><p style="background-color:#ffee00">f</p><h2 style="background-color:#fff">g</h2>');

        $this->assertStringContainsString('<p style="margin-right: 48pt">a</p>', $clean);
        $this->assertStringContainsString('margin-left: 24pt', $clean);
        $this->assertStringNotContainsString('30pt', $clean);
        $this->assertStringContainsString('<li>d</li>', $clean);
        $this->assertStringContainsString('<hr style="page-break-after: always"', $clean);
        $this->assertStringContainsString('<p>e</p>', $clean);
        $this->assertStringContainsString('background-color: #ffee00', $clean);
        $this->assertStringContainsString('<h2>g</h2>', $clean);
    }

    public function test_sanitizer_accepts_only_hex_highlight_colors_and_keeps_zero_width_non_joiners(): void
    {
        $this->assertStringContainsString('<mark style="background-color: #fef3c7">x</mark>', Correspondence::sanitizeHtml('<mark style="background-color:#fef3c7">x</mark>'));
        $this->assertStringNotContainsString('background-color', Correspondence::sanitizeHtml('<mark style="background-color:red">x</mark>'));
        $this->assertStringNotContainsString('background-color', Correspondence::sanitizeHtml('<mark style="background-color:rgb(1,2,3)">x</mark>'));

        $text = "می\u{200C}خواهم";
        $this->assertStringContainsString($text, Correspondence::sanitizeHtml("<p>{$text}</p>"));
        $this->assertStringContainsString($text, (string) ContentSanitizerService::clean("<p>{$text}</p>"));
    }

    public function test_spec_is_whitelisted_and_body_caps_and_block_shape_are_enforced(): void
    {
        $letter = $this->letter(['spec' => [
            'unexpected' => 'discard me',
            'recipient' => ['organization' => 'سازمان', 'extra' => 'discard me'],
            'sender' => ['name' => 'فرستنده', 'extra' => 'discard me'],
            'blocks' => [
                ['type' => 'unknown', 'html' => '<p>discard me</p>'],
                ['type' => 'text', 'text' => 'متن', 'size' => 'giant', 'align' => 'diagonal', 'unknown' => true],
            ],
        ]]);
        $spec = $letter->fresh()->spec;

        $this->assertEqualsCanonicalizing(['blocks', 'recipient', 'sender'], array_keys($spec));
        $this->assertCount(1, $spec['blocks']);
        $this->assertEqualsCanonicalizing(['type', 'text'], array_keys($spec['blocks'][0]));
        $this->assertEquals(['organization' => 'سازمان', 'person' => '', 'title' => ''], $spec['recipient']);

        config(['ai.correspondence.max_blocks' => 1]);
        $this->expectValidation(fn () => $this->letter(['spec' => ['blocks' => [
            ['type' => 'text', 'text' => '۱'], ['type' => 'text', 'text' => '۲'],
        ]]]), 'spec');

        config(['ai.correspondence.max_blocks' => 60, 'ai.correspondence.body_bytes' => 5]);
        $this->expectValidation(fn () => $this->letter(['spec' => ['blocks' => [['type' => 'html', 'html' => '<p>متن بلند</p>']]]]), 'spec');

        config(['ai.correspondence.body_bytes' => 200000, 'ai.correspondence.spec_bytes' => 100]);
        $this->expectValidation(fn () => $this->letter(['spec' => ['blocks' => [['type' => 'text', 'text' => str_repeat('الف', 80)]]]]), 'spec');

        config(['ai.correspondence.spec_bytes' => 400000]);
        $this->expectValidation(fn () => $this->letter(['spec' => ['blocks' => [['type' => 'html', 'html' => '<p>' . str_repeat('x', Correspondence::HTML_BLOCK_BYTES + 1) . '</p>']]]]), 'spec');
    }

    public function test_supported_structural_blocks_and_size_alignment_values_survive_storage(): void
    {
        $types = ['heading', 'text', 'html', 'table', 'signature_row', 'attachments_note'];
        $blocks = array_map(fn (string $type) => [
            'type' => $type,
            'heading' => 'عنوان',
            'text' => 'متن',
            'html' => '<p>بدنه</p>',
            'size' => 'small',
            'align' => 'left',
        ], $types);
        $letter = $this->letter(['spec' => ['blocks' => $blocks]]);

        $saved = $letter->fresh()->spec['blocks'];
        $this->assertSame($types, array_column($saved, 'type'));
        foreach ($saved as $block) {
            $this->assertSame('small', $block['size']);
            $this->assertSame('left', $block['align']);
        }
    }

    public function test_spec_paths_are_kept_only_under_the_same_existing_letter(): void
    {
        $letter = $this->letter(['spec' => [
            'blocks' => [['type' => 'image', 'path' => 'correspondence/999/image.png']],
            'attachments' => [['path' => 'correspondence/999/file.pdf', 'name' => 'file.pdf']],
            'evidence_path' => 'correspondence/999/evidence.html',
        ]]);
        $this->assertSame([], $letter->fresh()->spec['blocks']);
        $this->assertSame([], $letter->fresh()->spec['attachments']);
        $this->assertArrayNotHasKey('evidence_path', $letter->fresh()->spec);

        $id = (int) $letter->id;
        $letter = Correspondence::findOrFail($id);
        $letter->forceFill(['spec' => [
            'blocks' => [['type' => 'image', 'path' => "correspondence/{$id}/image.png"]],
            'attachments' => [['path' => "correspondence/{$id}/file.pdf", 'name' => 'file.pdf']],
            'evidence_path' => "correspondence/{$id}/evidence.html",
        ]])->save();

        $this->assertSame("correspondence/{$id}/image.png", $letter->fresh()->spec['blocks'][0]['path']);
        $this->assertSame("correspondence/{$id}/file.pdf", $letter->fresh()->spec['attachments'][0]['path']);
        $this->assertSame("correspondence/{$id}/evidence.html", $letter->fresh()->spec['evidence_path']);
    }

    public function test_search_text_indexes_plain_content_is_bounded_and_is_null_for_restricted_classification(): void
    {
        $letter = $this->letter(['spec' => [
            'blocks' => [[
                'type' => 'html',
                'heading' => 'سرعنوان',
                'caption' => 'زیرنویس',
                'text' => 'متن ساده',
                'html' => '<p>متن <strong>HTML</strong></p>',
            ]],
            'recipient' => ['organization' => 'مخاطب'],
            'sender' => ['name' => 'فرستنده'],
        ]]);
        $search = $letter->fresh()->search_text;

        foreach (['سرعنوان', 'زیرنویس', 'متن ساده', 'HTML', 'مخاطب', 'فرستنده'] as $text) {
            $this->assertStringContainsString($text, $search);
        }

        config(['ai.correspondence.search_text_bytes' => 32]);
        $letter = Correspondence::findOrFail($letter->id);
        $letter->forceFill(['spec' => ['blocks' => [['type' => 'text', 'text' => str_repeat('الف', 100)]]]])->save();
        $this->assertLessThanOrEqual(32, strlen($letter->fresh()->search_text));

        config(['ai.correspondence.search_text_bytes' => 16384]);
        $letter = Correspondence::findOrFail($letter->id);
        $letter->forceFill(['spec' => ['blocks' => [['type' => 'text', 'text' => str_repeat('الف', 8000)]]]])->save();
        $this->assertLessThanOrEqual(16384, strlen($letter->fresh()->search_text));

        $letter = Correspondence::findOrFail($letter->id);
        $letter->forceFill(['classification' => Correspondence::CLASSIFICATION_CONFIDENTIAL])->save();
        $this->assertNull($letter->fresh()->search_text);
    }

    public function test_status_and_enum_values_and_jalali_date_ranges_are_validated(): void
    {
        foreach (array_keys(Correspondence::STATUS_COLORS) as $status) {
            $this->letter(['status' => $status]);
        }

        foreach (config('ai.correspondence.types') as $type => $settings) {
            if ($settings['enabled'] ?? false) {
                $this->letter(['type' => $type, 'direction' => $settings['direction']]);
            }
        }
        foreach ([Correspondence::MODE_SIMPLE, Correspondence::MODE_SMART] as $mode) {
            $this->letter(['mode' => $mode]);
        }
        foreach ([Correspondence::CLASSIFICATION_NORMAL, Correspondence::CLASSIFICATION_CONFIDENTIAL, Correspondence::CLASSIFICATION_SECRET] as $classification) {
            $this->letter(['classification' => $classification]);
        }
        foreach ([Correspondence::URGENCY_NORMAL, Correspondence::URGENCY_URGENT, Correspondence::URGENCY_IMMEDIATE] as $urgency) {
            $this->letter(['urgency' => $urgency]);
        }
        foreach ([Correspondence::SIGN_MODE_ELECTRONIC, Correspondence::SIGN_MODE_WET] as $signMode) {
            $this->letter(['sign_mode' => $signMode]);
        }
        foreach ([Correspondence::ROUTE_SEQUENTIAL, Correspondence::ROUTE_PARALLEL] as $routeMode) {
            $this->letter(['route_mode' => $routeMode]);
        }

        $incoming = $this->letter(['direction' => Correspondence::DIRECTION_IN, 'type' => 'incoming', 'their_date' => '1405/01/31']);
        $this->assertSame('1405/01/31', $incoming->fresh()->their_date);
        $this->letter(['direction' => Correspondence::DIRECTION_IN, 'type' => 'incoming', 'their_date' => '1405/12/30']);

        foreach ([
            ['urgency' => 'whenever', 'field' => 'urgency'],
            ['classification' => 'top-secret', 'field' => 'classification'],
            ['direction' => 'sideways', 'field' => 'direction'],
            ['mode' => 'turbo', 'field' => 'mode'],
            ['sign_mode' => 'voice', 'field' => 'sign_mode'],
            ['route_mode' => 'random', 'field' => 'route_mode'],
            ['sender_category' => 'unknown', 'field' => 'sender_category'],
            ['type' => 'unknown', 'field' => 'type'],
            ['type' => 'letter', 'direction' => Correspondence::DIRECTION_IN, 'field' => 'type'],
            ['direction' => Correspondence::DIRECTION_IN, 'type' => 'incoming', 'their_date' => '1405/13/01', 'field' => 'their_date'],
            ['direction' => Correspondence::DIRECTION_IN, 'type' => 'incoming', 'their_date' => '1405/07/31', 'field' => 'their_date'],
        ] as $case) {
            $field = $case['field'];
            unset($case['field']);
            $this->expectValidation(fn () => $this->letter($case), $field);
        }
    }

    public function test_plain_line_keeps_comparison_text_and_zwnj_but_strips_bidi_controls_and_tags(): void
    {
        $letter = $this->letter(['subject' => "a <50 b; 1<2 and 3>2؛ می‌نویسد \u{202E}مخفی"]);
        $subject = $letter->fresh()->subject;

        $this->assertStringContainsString('a <50 b', $subject);
        $this->assertStringContainsString('1<2 and 3>2', $subject);
        $this->assertStringContainsString('می‌نویسد', $subject);
        $this->assertStringNotContainsString("\u{202E}", $subject);

        $letter = Correspondence::findOrFail($letter->id);
        $letter->forceFill(['subject' => '<scr<script>ipt>alert(1)</scr</script>ipt>'])->save();
        $this->assertStringNotContainsString('<script', strtolower($letter->fresh()->subject));
    }

    public function test_tag_cleaning_preserves_the_string_zero(): void
    {
        $letter = $this->letter(['tags' => ['0', '  0  ', '', 2, '<b>kept</b>', '<script>x</script>']]);

        $this->assertSame(['0', 'kept', 'x'], $letter->fresh()->tags);
    }

    public function test_approved_rows_reject_frozen_columns_even_when_partially_loaded(): void
    {
        $head = Letterhead::forceCreate(['kind' => Letterhead::KIND_HEAD, 'name' => 'Frozen guard head', 'pattern' => '{SEQ}']);
        $registryHead = Letterhead::forceCreate(['kind' => Letterhead::KIND_REGISTRY, 'name' => 'Frozen guard registry', 'pattern' => '{SEQ}']);

        foreach ([
            'spec' => ['blocks' => [['type' => 'text', 'text' => 'changed']]],
            'subject' => 'changed',
            'number' => 'N-1',
            'number_seq' => 1,
            'letter_date' => '1405/01/01',
            'letterhead_id' => $head->id,
            'type' => 'certificate',
            'direction' => Correspondence::DIRECTION_IN,
            'sign_mode' => Correspondence::SIGN_MODE_WET,
            'content_hash' => str_repeat('a', 64),
            'approved_at' => now()->subDay(),
        ] as $column => $value) {
            $letter = $this->letter(['status' => Correspondence::STATUS_APPROVED]);
            $partial = Correspondence::query()->select(['id', 'status'])->findOrFail($letter->id);
            $changes = [$column => $value];
            if ($column === 'direction') {
                $changes['type'] = 'incoming';
                $changes['letterhead_id'] = $registryHead->id;
            }
            $partial->forceFill($changes);
            $this->expectValidation(fn () => $partial->save(), 'status');
        }

        foreach ([Correspondence::STATUS_SUPERSEDED, Correspondence::STATUS_VOID] as $status) {
            $letter = $this->letter(['status' => $status]);
            $letter->forceFill(['subject' => 'تغییر ممنوع']);
            $this->expectValidation(fn () => $letter->save(), 'status');
        }
    }

    public function test_approved_status_exits_and_shared_to_approved_finalization_are_allowed(): void
    {
        $approved = $this->letter(['status' => Correspondence::STATUS_APPROVED]);
        $approved->forceFill(['status' => Correspondence::STATUS_SUPERSEDED])->save();
        $this->assertSame(Correspondence::STATUS_SUPERSEDED, $approved->fresh()->status);

        $approvedToVoid = $this->letter(['status' => Correspondence::STATUS_APPROVED]);
        $approvedToVoid->forceFill(['status' => Correspondence::STATUS_VOID])->save();
        $this->assertSame(Correspondence::STATUS_VOID, $approvedToVoid->fresh()->status);

        $superseded = Correspondence::findOrFail($approved->id);
        $superseded->forceFill(['status' => Correspondence::STATUS_VOID])->save();
        $this->assertSame(Correspondence::STATUS_VOID, $superseded->fresh()->status);

        $shared = $this->letter(['status' => Correspondence::STATUS_SHARED]);
        $shared->forceFill([
            'status' => Correspondence::STATUS_APPROVED,
            'number' => 'L/1405/1',
            'number_seq' => 1,
            'letter_date' => '1405/01/01',
            'approved_at' => now(),
            'content_hash' => str_repeat('b', 64),
        ])->save();

        $this->assertSame(Correspondence::STATUS_APPROVED, $shared->fresh()->status);
        $this->assertSame('L/1405/1', $shared->fresh()->number);
        $this->assertNotNull($shared->fresh()->approved_at);
    }

    public function test_classification_only_change_and_approved_metadata_columns_remain_writable(): void
    {
        $letter = $this->letter(['status' => Correspondence::STATUS_APPROVED]);
        $letter->forceFill(['classification' => Correspondence::CLASSIFICATION_SECRET])->save();
        $this->assertSame(Correspondence::CLASSIFICATION_SECRET, $letter->fresh()->classification);
        $this->assertNull($letter->fresh()->search_text);

        $letter = Correspondence::findOrFail($letter->id);
        $letter->forceFill([
            'parties' => [['user_id' => 18, 'role' => 'approver', 'signed_at' => now()->toIso8601String()]],
            'events' => [['at' => now()->toIso8601String(), 'user_id' => 18, 'action' => 'reviewed']],
            'tags' => ['signed'],
            'meta' => ['snapshot' => ['version' => 1]],
            'is_template' => true,
            'pdf_path' => "correspondence/{$letter->id}/letter.pdf",
            'pdf_hash' => str_repeat('c', 64),
        ])->save();

        $saved = $letter->fresh();
        $this->assertSame(18, $saved->parties[0]['user_id']);
        $this->assertSame('reviewed', $saved->events[0]['action']);
        $this->assertSame(['signed'], $saved->tags);
        $this->assertTrue($saved->is_template);
        $this->assertSame("correspondence/{$letter->id}/letter.pdf", $saved->pdf_path);
        $this->assertSame(str_repeat('c', 64), $saved->pdf_hash);
    }

    public function test_shared_with_scope_excludes_hidden_and_deleted_rows_and_matches_recipients_or_parties(): void
    {
        $viewer = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $recipient = $this->letter(['status' => Correspondence::STATUS_SHARED, 'recipients' => [$viewer->id]]);
        $party = $this->letter(['status' => Correspondence::STATUS_APPROVED, 'parties' => [['user_id' => $viewer->id, 'role' => 'fyi']]]);

        $excluded = [];
        foreach ([...Correspondence::DRAFT_LIKE, Correspondence::STATUS_SAVED, Correspondence::STATUS_WITHDRAWN] as $status) {
            $excluded[] = $this->letter(['status' => $status, 'recipients' => [$viewer->id]]);
        }
        $void = $this->letter(['status' => Correspondence::STATUS_VOID, 'recipients' => [$viewer->id]]);
        $deleted = $this->letter(['status' => Correspondence::STATUS_APPROVED, 'recipients' => [$viewer->id]]);
        $deleted->delete();

        $ids = Correspondence::sharedWith((int) $viewer->id)->pluck('id')->all();

        $this->assertContains($recipient->id, $ids);
        $this->assertContains($party->id, $ids);
        $this->assertContains($void->id, $ids);
        foreach ($excluded as $row) {
            $this->assertNotContains($row->id, $ids);
        }
        $this->assertNotContains($deleted->id, $ids);
    }

    public function test_fillable_defaults_and_list_columns_protect_internal_state(): void
    {
        $letter = new Correspondence();
        $letter->fill([
            'status' => Correspondence::STATUS_APPROVED,
            'number' => 'should-not-set',
            'number_seq' => 9,
            'parties' => [['user_id' => 1]],
            'events' => [['action' => 'fake']],
            'search_text' => 'private',
            'pdf_path' => 'private.pdf',
            'approved_at' => now(),
        ]);

        $this->assertSame(Correspondence::STATUS_DRAFT, $letter->status);
        foreach (['number', 'number_seq', 'parties', 'events', 'search_text', 'pdf_path', 'approved_at'] as $protected) {
            $this->assertNull($letter->getAttribute($protected));
        }

        foreach (['spec', 'versions', 'events', 'search_text', 'parties', 'meta'] as $sensitive) {
            $this->assertNotContains($sensitive, Correspondence::LIST_COLUMNS);
        }
        $this->assertSame(Correspondence::CLASSIFICATION_NORMAL, (new Correspondence())->classification);
        $this->assertSame(Correspondence::DIRECTION_OUT, (new Correspondence())->direction);
        $this->assertSame(Correspondence::MODE_SIMPLE, (new Correspondence())->mode);
        $this->assertSame(Correspondence::SIGN_MODE_ELECTRONIC, (new Correspondence())->sign_mode);
    }

    public function test_shared_ai_concerns_keep_report_defaults_and_use_correspondence_configuration(): void
    {
        config([
            'ai.report.daily_cap' => 3,
            'ai.report.max_versions' => 4,
            'ai.correspondence.daily_cap' => 8,
            'ai.correspondence.max_versions' => 12,
        ]);

        $this->assertSame('ai-report:quota:17:2026-11-09', AiReport::quotaKey(17, '2026-11-09'));
        $this->assertSame('ai-correspondence:quota:17:2026-11-09', Correspondence::quotaKey(17, '2026-11-09'));
        $this->assertSame(3, AiReport::quotaCap());
        $this->assertSame(8, Correspondence::quotaCap());
        $this->assertSame(4, AiReport::maxVersions());
        $this->assertSame(12, Correspondence::maxVersions());

        $report = new AiReport();
        $report->id = 41;
        $letter = new Correspondence();
        $letter->id = 41;
        $this->assertSame('ai-report/tmp/41', $report->tempDirectory());
        $this->assertSame('ai-correspondence/tmp/41', $letter->tempDirectory());
    }

    public function test_menu_state_flush_is_deferred_and_skips_irrelevant_changes(): void
    {
        Cache::forever('menu_state:version', 123);
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $letter = $this->letter(['user_id' => $user->id]);
        $this->assertSame(123, Cache::get('menu_state:version'));

        $database = DB::getFacadeRoot();
        $callback = null;

        try {
            DB::shouldReceive('afterCommit')->once()->andReturnUsing(function (Closure $afterCommit) use (&$callback): void {
                $callback = $afterCommit;
            });

            $letter = Correspondence::findOrFail($letter->id);
            $letter->forceFill(['subject' => 'تغییر موضوع'])->save();
            $this->assertSame(123, Cache::get('menu_state:version'));

            $letter->forceFill([
                'status' => Correspondence::STATUS_SHARED,
                'recipients' => [$user->id],
                'parties' => [['user_id' => $user->id, 'role' => 'fyi']],
                'awaiting_user_id' => $user->id,
            ])->save();
            $this->assertSame(123, Cache::get('menu_state:version'));
            $this->assertInstanceOf(Closure::class, $callback);

            $callback();
            $this->assertNotSame(123, Cache::get('menu_state:version'));
        } finally {
            DB::swap($database);
        }
    }

    public function test_self_links_and_cycles_are_rejected(): void
    {
        $first = $this->letter();
        $first->forceFill(['in_reply_to_id' => $first->id]);
        $this->expectValidation(fn () => $first->save(), 'in_reply_to_id');

        $first = Correspondence::findOrFail($first->id);
        $second = $this->letter();
        $first->forceFill(['in_reply_to_id' => $second->id])->save();
        $second = Correspondence::findOrFail($second->id);
        $second->forceFill(['in_reply_to_id' => $first->id]);
        $this->expectValidation(fn () => $second->save(), 'in_reply_to_id');

        $first = Correspondence::findOrFail($first->id);
        $second = Correspondence::findOrFail($second->id);
        $first->forceFill(['supersedes_id' => $second->id])->save();
        $second->forceFill(['supersedes_id' => $first->id]);
        $this->expectValidation(fn () => $second->save(), 'supersedes_id');
    }

    public function test_seen_by_methods_throw_because_seen_state_lives_on_party_entries(): void
    {
        $this->expectException(LogicException::class);
        Correspondence::query()->unseenBy(1);
    }

    public function test_mark_seen_by_throws_because_seen_state_lives_on_party_entries(): void
    {
        $this->expectException(LogicException::class);
        (new Correspondence())->markSeenBy(1);
    }
}
