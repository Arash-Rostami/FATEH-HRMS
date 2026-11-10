<?php

namespace Tests\Feature\Services\Correspondence;

use App\Models\Correspondence;
use App\Models\Letterhead;
use App\Models\User;
use App\Services\Correspondence\CorrespondenceFonts;
use App\Services\Correspondence\CorrespondencePdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

class CorrespondencePdfTest extends TestCase
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

    public function test_render_columns_include_only_the_data_needed_to_build_the_letter_pdf(): void
    {
        $this->assertSame([
            'id', 'status', 'classification', 'sign_mode', 'type', 'subject', 'number', 'letter_date',
            'letterhead_id', 'content_hash', 'pdf_path', 'pdf_hash', 'spec', 'meta', 'parties',
        ], CorrespondencePdf::renderColumns());
    }

    public function test_absolute_accepts_only_an_existing_file_inside_the_local_disk(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('correspondence/1/test.pdf', '%PDF-1.4');

        $this->assertNotNull(CorrespondencePdf::absolute('correspondence/1/test.pdf'));
        $this->assertNull(CorrespondencePdf::absolute('../outside.pdf'));
        $this->assertNull(CorrespondencePdf::absolute('correspondence/1/missing.pdf'));
    }

    public function test_path_refuses_a_letter_that_has_not_been_approved(): void
    {
        $letter = (new Correspondence())->forceFill([
            'status' => Correspondence::STATUS_DRAFT,
            'spec' => ['blocks' => [['type' => 'text', 'text' => 'draft']]],
            'meta' => [],
            'content_hash' => str_repeat('a', 64),
            'letterhead_id' => null,
            'pdf_path' => null,
            'pdf_hash' => null,
        ]);

        try {
            CorrespondencePdf::path($letter);
        } catch (RuntimeException $exception) {
            $this->assertSame('not_approved', $exception->getMessage());

            return;
        }

        $this->fail('A draft must not be rendered as a final PDF.');
    }

    private function invoke(string $method, mixed ...$args): mixed
    {
        return (new ReflectionMethod(CorrespondencePdf::class, $method))->invoke(null, ...$args);
    }

    private function approved(array $overrides = []): Correspondence
    {
        $letter = (new Correspondence())->forceFill(array_replace([
            'id' => 4242,
            'status' => Correspondence::STATUS_APPROVED,
            'spec' => ['blocks' => [['type' => 'text', 'text' => 'body']]],
            'meta' => [],
            'content_hash' => str_repeat('a', 64),
            'letterhead_id' => null,
            'pdf_path' => null,
            'pdf_hash' => null,
        ], $overrides));
        $letter->setRelation('letterhead', null);

        return $letter;
    }

    public function test_the_cache_hash_follows_the_content_hash_the_frozen_snapshot_and_ignores_taken_at(): void
    {
        $base = $this->invoke('hashOf', $this->approved());

        $this->assertSame($base, $this->invoke('hashOf', $this->approved()));
        $this->assertNotSame($base, $this->invoke('hashOf', $this->approved(['content_hash' => str_repeat('b', 64)])));

        $snap = $this->invoke('hashOf', $this->approved(['meta' => ['snapshot' => ['name' => 'A', 'taken_at' => '2026-01-01']]]));
        $this->assertNotSame($base, $snap);
        $this->assertSame($snap, $this->invoke('hashOf', $this->approved(['meta' => ['snapshot' => ['name' => 'A', 'taken_at' => '2030-05-05']]])));
        $this->assertNotSame($snap, $this->invoke('hashOf', $this->approved(['meta' => ['snapshot' => ['name' => 'B', 'taken_at' => '2026-01-01']]])));
    }

    public function test_the_cache_hash_without_a_snapshot_follows_the_live_letterhead_page_options(): void
    {
        $plain = $this->approved();
        $wide = $this->approved();
        $wide->setRelation('letterhead', (new Letterhead())->forceFill(['id' => 9, 'size' => 'a4', 'margins' => ['top' => 6]]));
        $tall = $this->approved();
        $tall->setRelation('letterhead', (new Letterhead())->forceFill(['id' => 9, 'size' => 'a4', 'margins' => ['top' => 7]]));

        $this->assertNotSame($this->invoke('hashOf', $plain), $this->invoke('hashOf', $wide));
        $this->assertNotSame($this->invoke('hashOf', $wide), $this->invoke('hashOf', $tall));
    }

    public function test_the_cache_hits_only_for_the_matching_hash_an_owned_pdf_path_and_an_existing_file(): void
    {
        Storage::fake('local');
        $letter = $this->approved();
        $hash = $this->invoke('hashOf', $letter);
        $relative = 'correspondence/4242/letter-' . $hash . '.pdf';
        $letter->forceFill(['pdf_hash' => $hash, 'pdf_path' => $relative]);

        $this->assertFalse($this->invoke('cached', $letter, $hash, $relative));

        Storage::disk('local')->put($relative, '%PDF');
        $this->assertTrue($this->invoke('cached', $letter, $hash, $relative));
        $this->assertSame($relative, CorrespondencePdf::path($letter));

        $this->assertFalse($this->invoke('cached', $letter->forceFill(['pdf_hash' => str_repeat('0', 64)]), $hash, $relative));
        $foreign = 'correspondence/1/letter-' . $hash . '.pdf';
        Storage::disk('local')->put($foreign, '%PDF');
        $this->assertFalse($this->invoke('cached', $letter->forceFill(['pdf_hash' => $hash, 'pdf_path' => $foreign]), $hash, $foreign));
        $this->assertFalse($this->invoke('cached', $letter->forceFill(['pdf_path' => $relative . '.txt']), $hash, $relative . '.txt'));
    }

    public function test_a_letter_with_an_empty_spec_cannot_be_rendered(): void
    {
        $this->expectExceptionObject(new RuntimeException('pdf_failed'));

        CorrespondencePdf::path($this->approved(['spec' => []]));
    }

    public function test_directory_creates_a_missing_folder_and_fails_when_it_cannot(): void
    {
        $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'corr-pdf-' . uniqid();
        $made = $this->invoke('directory', $root . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'b');
        $this->assertDirectoryExists($made);
        $this->assertSame($made, $this->invoke('directory', $made));

        $file = $root . DIRECTORY_SEPARATOR . 'plain.txt';
        file_put_contents($file, 'x');

        try {
            $this->invoke('directory', $file . DIRECTORY_SEPARATOR . 'sub');
            $this->fail('A folder under a regular file must not be creatable.');
        } catch (RuntimeException $exception) {
            $this->assertSame('pdf_failed', $exception->getMessage());
        } finally {
            unlink($file);
            rmdir($made);
            rmdir(dirname($made));
            rmdir($root);
        }
    }

    public function test_pull_div_takes_the_block_even_with_extra_attributes_and_pull_tag_takes_the_style(): void
    {
        $html = '<style>p{color:red}</style><p>a</p><div class="paper-foot" wire:key="correspondence-paper-foot"><span>f</span></div><div class="paper-head"><b>h</b></div>';

        [$rest, $foot] = $this->invoke('pullDiv', $html, 'paper-foot');
        $this->assertSame('<div class="paper-foot" wire:key="correspondence-paper-foot"><span>f</span></div>', $foot);
        $this->assertStringNotContainsString('paper-foot', $rest);
        $this->assertStringContainsString('paper-head', $rest);

        [$rest, $head] = $this->invoke('pullDiv', $html, 'paper-head');
        $this->assertSame('<div class="paper-head"><b>h</b></div>', $head);

        [$rest, $css] = $this->invoke('pullTag', $html, 'style');
        $this->assertSame('p{color:red}', $css);
        $this->assertStringNotContainsString('<style>', $rest);

        $this->assertSame([$html, ''], $this->invoke('pullDiv', $html, 'paper-missing'));
        $this->assertSame(['<p>x</p>', ''], $this->invoke('pullTag', '<p>x</p>', 'style'));
    }

    public function test_paint_drops_remote_and_missing_background_images_and_keeps_local_ones_resized(): void
    {
        $this->assertSame('', $this->invoke('paint', ''));
        $this->assertSame('<div style="background-image:none">', $this->invoke('paint', '<div style="background-image:url(\'https://evil.test/a.png\')">'));
        $this->assertSame('<div style="background-image:none">', $this->invoke('paint', '<div style="background-image:url(/nope/missing.png)">'));

        $dir = storage_path('app/public');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $file = $dir . '/zz-paint-' . uniqid() . '.png';
        file_put_contents($file, 'png');

        try {
            $painted = $this->invoke('paint', '<div style="background-image:url(\'' . $file . '\')">');
            $this->assertStringContainsString('background-image-resize:4', $painted);
            $this->assertStringNotContainsString('background-image:none', $painted);
        } finally {
            unlink($file);
        }
    }

    public function test_local_html_strips_scripts_remote_css_and_non_local_images_and_caps_the_size(): void
    {
        $html = $this->invoke('localHtml', '<script>alert(1)</script><style>@import url(http://x.test/a.css);div{background:url(https://x.test/b.png)}</style><p style="background:url(http://x.test/c.png)">t</p><img src="http://x.test/d.png"><img src="../../etc/passwd">');

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('@import', $html);
        $this->assertStringNotContainsString('x.test', $html);
        $this->assertStringNotContainsString('passwd', $html);
        $this->assertStringContainsString('<p', $html);

        $this->expectExceptionObject(new RuntimeException('too_large'));
        $this->invoke('localHtml', str_repeat('a', 2000001));
    }

    public function test_only_files_inside_the_storage_roots_up_to_the_size_cap_count_as_allowed_images(): void
    {
        $this->assertFalse($this->invoke('allowedImage', ''));
        $this->assertFalse($this->invoke('allowedImage', 'https://x.test/a.png'));
        $this->assertFalse($this->invoke('allowedImage', 'storage/../.env'));
        $this->assertFalse($this->invoke('allowedImage', base_path('composer.json')));
    }

    private function letterRow(string $status = Correspondence::STATUS_DRAFT): Correspondence
    {
        $owner = User::factory()->create(['role' => 'user', 'status' => 'active']);

        return Correspondence::forceCreate([
            'user_id' => $owner->id,
            'direction' => Correspondence::DIRECTION_OUT,
            'type' => 'letter',
            'status' => $status,
            'subject' => 'نامه آزمون',
            'spec' => ['blocks' => [['type' => 'text', 'text' => 'بدنه']]],
            'meta' => ['k' => 1],
            'content_hash' => str_repeat('c', 64),
        ]);
    }

    public function test_a_partially_loaded_letter_is_reloaded_with_every_column_the_render_needs(): void
    {
        $row = $this->letterRow();
        $partial = Correspondence::query()->select(['id', 'status'])->findOrFail($row->id);
        $this->assertArrayNotHasKey('spec', $partial->getAttributes());

        $full = $this->invoke('complete', $partial);

        $this->assertNotSame($partial, $full);
        $this->assertEquals($row->spec, $full->spec);
        $this->assertSame(['k' => 1], $full->meta);
        $this->assertSame(str_repeat('c', 64), $full->content_hash);
        $this->assertArrayHasKey('pdf_hash', $full->getAttributes());

        $loaded = Correspondence::query()->whereKey($row->id)->first(CorrespondencePdf::renderColumns());
        $this->assertSame($loaded, $this->invoke('complete', $loaded));
    }

    public function test_the_reload_decides_the_status_so_a_stale_partial_cannot_claim_an_approved_letter(): void
    {
        $row = $this->letterRow();
        $claim = (new Correspondence())->forceFill(['id' => $row->id, 'status' => Correspondence::STATUS_APPROVED]);

        $this->expectExceptionObject(new RuntimeException('not_approved'));

        CorrespondencePdf::path($claim);
    }

    public function test_a_partial_letter_whose_row_is_gone_fails_instead_of_rendering(): void
    {
        Storage::fake('local');
        $row = $this->letterRow();
        $partial = Correspondence::query()->select(['id'])->findOrFail($row->id);
        $row->forceDelete();

        $this->expectExceptionObject(new RuntimeException('pdf_failed'));

        $this->invoke('complete', $partial);
    }

    public function test_the_cache_hash_follows_the_letterhead_and_snapshot_fonts(): void
    {
        $regular = $this->approved();
        $regular->setRelation('letterhead', (new Letterhead())->forceFill(['id' => 9, 'size' => 'a4', 'style' => ['font' => 'parastoo']]));
        $other = $this->approved();
        $other->setRelation('letterhead', (new Letterhead())->forceFill(['id' => 9, 'size' => 'a4', 'style' => ['font' => 'vazirmatn']]));

        $this->assertNotSame($this->invoke('hashOf', $regular), $this->invoke('hashOf', $other));

        $frozenA = $this->invoke('hashOf', $this->approved(['meta' => ['snapshot' => ['style' => ['font' => 'parastoo']]]]));
        $frozenB = $this->invoke('hashOf', $this->approved(['meta' => ['snapshot' => ['style' => ['font' => 'vazirmatn']]]]));
        $this->assertNotSame($frozenA, $frozenB);
    }

    public function test_font_options_show_english_labels_for_latin_fonts_and_persian_labels_for_persian_ones(): void
    {
        $fa = CorrespondenceFonts::options('fa');
        $latin = CorrespondenceFonts::options('latin');
        $both = CorrespondenceFonts::options();

        $this->assertSame(config('ai.correspondence.fonts.parastoo.label_fa'), $fa['parastoo']);
        $this->assertSame('Tinos', $latin['tinos']);
        $this->assertNotSame(config('ai.correspondence.fonts.tinos.label_fa'), $latin['tinos']);
        $this->assertArrayNotHasKey('tinos', $fa);
        $this->assertArrayNotHasKey('parastoo', $latin);
        $this->assertSame($fa + $latin, $both);
        $this->assertSame(array_keys($both), CorrespondenceFonts::keys());
        $this->assertSame(array_keys($fa), CorrespondenceFonts::keys('fa'));
        $this->assertSame(array_keys($latin), CorrespondenceFonts::keys('latin'));
    }

    public function test_optional_fonts_are_offered_only_while_their_regular_file_exists(): void
    {
        config(['ai.correspondence.fonts.bnazanin.files' => ['R' => ['ttf' => 'zz-absent/BNazanin.ttf']]]);
        $this->assertNotContains('bnazanin', CorrespondenceFonts::keys('fa'));
        $this->assertArrayNotHasKey('bnazanin', CorrespondenceFonts::pdfData());
        $this->assertSame('parastoo', CorrespondenceFonts::pick('bnazanin', 'fa'));
        $this->assertContains('bnazanin', CorrespondenceFonts::known('fa'));
        $this->assertNotContains('tinos', CorrespondenceFonts::known('fa'));

        config(['ai.correspondence.fonts.bnazanin.files' => [
            'R' => ['ttf' => 'Yekan.ttf'],
            'B' => ['ttf' => 'zz-absent/BNazaninBold.ttf'],
        ]]);
        $this->assertContains('bnazanin', CorrespondenceFonts::keys('fa'));
        $this->assertSame(['R'], array_keys(CorrespondenceFonts::all()['bnazanin']['files']));
        $this->assertSame('bnazanin', CorrespondenceFonts::pick('bnazanin', 'fa'));
        $this->assertSame(['R' => 'Yekan.ttf', 'useOTL' => 0], CorrespondenceFonts::pdfData()['bnazanin']);

        config(['ai.correspondence.fonts.btitr.files' => ['R' => ['ttf' => 'zz-absent/BTitr.ttf']]]);
        $this->assertNotContains('btitr', CorrespondenceFonts::keys('fa'));
        $this->assertSame('parastoo', CorrespondenceFonts::pick('btitr', 'fa'));
    }

    public function test_pick_falls_back_to_the_letterhead_default_for_unknown_wrong_script_or_non_string_values(): void
    {
        $default = Letterhead::defaultStyle();
        $faDefault = in_array($default['font'], CorrespondenceFonts::keys('fa'), true) ? $default['font'] : 'parastoo';

        foreach (['unknown', 'x;}</style>', 'tinos', '', null, 5, ['parastoo']] as $value) {
            $this->assertSame($faDefault, CorrespondenceFonts::pick($value, 'fa'));
        }
        foreach (['unknown', 'x;}</style>', 'parastoo', null, false] as $value) {
            $this->assertSame($default['latin_font'], CorrespondenceFonts::pick($value, 'latin'));
        }

        $this->assertSame('vazirmatn', CorrespondenceFonts::pick('vazirmatn', 'fa'));
        $this->assertSame('inter', CorrespondenceFonts::pick('inter', 'latin'));
        $this->assertSame('Times New Roman', CorrespondenceFonts::pick('Times New Roman', 'latin'));
    }

    public function test_used_fonts_are_the_letterhead_pair_plus_families_found_in_the_body(): void
    {
        $style = ['font' => 'parastoo', 'latin_font' => 'tinos'];

        $this->assertSame(['parastoo', 'tinos'], CorrespondenceFonts::used($style, [['type' => 'html', 'html' => '<p>ساده</p>']]));

        $used = CorrespondenceFonts::used($style, [['type' => 'html', 'html' => '<p style="font-family: Vazirmatn, serif">الف</p><span style="font-family: Inter">b</span>']]);
        $this->assertSame(['parastoo', 'tinos'], array_slice($used, 0, 2));
        $this->assertContains('vazirmatn', $used);
        $this->assertContains('inter', $used);
        $this->assertNotContains('iranyekan', $used);
        $this->assertSame($used, array_values(array_unique($used)));

        $this->assertNotContains('inter', CorrespondenceFonts::used($style, [['type' => 'html', 'html' => '<p>Interesting font-family notes</p>']]));
    }

    public function test_font_face_css_is_emitted_per_registered_face_with_ordered_sources(): void
    {
        $tinos = CorrespondenceFonts::fontFace(['tinos']);

        $this->assertSame(4, substr_count($tinos, '@font-face'));
        $this->assertStringContainsString('font-family: "tinos"', $tinos);
        $this->assertStringContainsString('font-style: italic; font-weight: 700', $tinos);
        $this->assertStringContainsString(asset('build/assets/fonts/tinos/Tinos-Regular.ttf'), $tinos);
        $this->assertStringNotContainsString('woff2', $tinos);
        $this->assertStringContainsString('font-display: swap', $tinos);

        $parastoo = explode("\n", CorrespondenceFonts::fontFace(['parastoo']))[0];
        $this->assertLessThan(strpos($parastoo, 'format("truetype")'), strpos($parastoo, 'format("woff2")'));

        $this->assertSame('', CorrespondenceFonts::fontFace(['Times New Roman']));
        $this->assertSame('', CorrespondenceFonts::fontFace(['not-a-font']));
        $this->assertSame('', CorrespondenceFonts::fontFace([]));
    }

    public function test_pdf_data_registers_every_font_that_has_a_ttf_with_its_shaping_flags(): void
    {
        $data = CorrespondenceFonts::pdfData();

        $this->assertSame(0, $data['nazanin']['useOTL']);
        $this->assertSame(0, $data['notonaskh']['useOTL']);
        $this->assertSame(0xFF, $data['parastoo']['useOTL']);
        $this->assertSame(75, $data['parastoo']['useKashida']);
        $this->assertArrayNotHasKey('useOTL', $data['tinos']);
        $this->assertSame(['R', 'B', 'I', 'BI'], array_keys($data['inter']));
        $this->assertArrayNotHasKey('timesnewroman', $data);

        foreach ($data as $key => $entry) {
            foreach (array_diff_key($entry, array_flip(['useOTL', 'useKashida'])) as $face => $file) {
                $this->assertFileExists(resource_path('assets/fonts/' . $file), "{$key} {$face}");
            }
        }
    }

    public function test_owned_only_matches_the_letters_own_pdf_files(): void
    {
        $letter = $this->approved();

        $this->assertTrue($this->invoke('owned', $letter, 'correspondence/4242/letter-x.pdf'));
        $this->assertFalse($this->invoke('owned', $letter, 'correspondence/4243/letter-x.pdf'));
        $this->assertFalse($this->invoke('owned', $letter, 'correspondence/4242/letter-x.png'));
        $this->assertFalse($this->invoke('owned', $letter, 'correspondence/4242/../x.pdf'));
        $this->assertFalse($this->invoke('owned', $letter, null));
    }
}
