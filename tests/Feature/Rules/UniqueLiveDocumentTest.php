<?php

namespace Tests\Feature\Rules;

use App\Filament\Resources\DmsResource\Enums\DocumentStatus;
use App\Models\DMS;
use App\Rules\UniqueLiveDocument;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * (code, version) unique among LIVE docs only — non-live duplicates allowed, one live only.
 * The rule is field-agnostic: the whole triple is passed via the constructor, so it fires
 * identically whether attached to code, version, or status (changing any of the three
 * re-validates on submit). Self-contained: builds just the `dms` table because this
 * project's MySQL-authored migrations fail under the sqlite testing DB, so RefreshDatabase
 * is broken repo-wide (see FeedsTest).
 */
class UniqueLiveDocumentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('dms', function ($t) {
            $t->id();
            $t->string('file');
            $t->string('code');
            $t->string('version');
            $t->string('title');
            $t->string('status');
            $t->boolean('type')->default(false);
            $t->json('owners')->nullable();
            $t->json('users')->nullable();
            $t->text('revision')->nullable();
            $t->integer('combined_read_count')->default(0);
            $t->json('extra')->nullable();
            $t->json('tags')->nullable();
            $t->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('dms');
        parent::tearDown();
    }

    private function makeDoc(string $code, string $version, DocumentStatus $status): DMS
    {
        return DMS::create([
            'file' => $code . '.pdf',
            'code' => $code,
            'version' => $version,
            'title' => $code,
            'status' => $status->value,
        ]);
    }

    /** The rule ignores $value (the field it is on); the constructor triple is the source of truth. */
    private function fails(UniqueLiveDocument $rule): bool
    {
        $failed = false;
        $rule->validate('code', null, function () use (&$failed): void {
            $failed = true;
        });

        return $failed;
    }

    public function test_creating_a_live_doc_with_a_pair_already_live_fails(): void
    {
        $this->makeDoc('DOC-1', '1.0', DocumentStatus::Live);

        $this->assertTrue($this->fails(new UniqueLiveDocument('DOC-1', '1.0', 'live')));
    }

    public function test_creating_a_live_doc_with_a_unique_pair_passes(): void
    {
        $this->makeDoc('DOC-1', '1.0', DocumentStatus::Live);

        $this->assertFalse($this->fails(new UniqueLiveDocument('DOC-2', '1.0', 'live')));
    }

    public function test_non_live_statuses_do_not_collide_with_an_existing_live_pair(): void
    {
        $this->makeDoc('DOC-1', '1.0', DocumentStatus::Live);

        $this->assertFalse($this->fails(new UniqueLiveDocument('DOC-1', '1.0', 'under_review')));
        $this->assertFalse($this->fails(new UniqueLiveDocument('DOC-1', '1.0', 'obsolete')));
    }

    public function test_many_non_live_duplicates_allowed_but_only_one_live(): void
    {
        foreach (['under_review', 'obsolete', 'obsolete', 'under_review', 'obsolete'] as $status) {
            $this->makeDoc('DOC-1', '1.0', DocumentStatus::from($status));
        }

        // a sixth non-live still allowed; then add the one live, and a second live must block.
        $this->assertFalse($this->fails(new UniqueLiveDocument('DOC-1', '1.0', 'under_review')));
        $this->makeDoc('DOC-1', '1.0', DocumentStatus::Live);
        $this->assertTrue($this->fails(new UniqueLiveDocument('DOC-1', '1.0', 'live')));
    }

    public function test_editing_a_live_doc_keeping_its_own_pair_passes(): void
    {
        $live = $this->makeDoc('DOC-1', '1.0', DocumentStatus::Live);

        $this->assertFalse($this->fails(new UniqueLiveDocument('DOC-1', '1.0', 'live', $live->id)));
    }

    public function test_promoting_a_non_live_doc_to_live_collides_with_another_live_pair(): void
    {
        $underReview = $this->makeDoc('DOC-1', '1.0', DocumentStatus::UnderReview);
        $this->makeDoc('DOC-1', '1.0', DocumentStatus::Live); // another live already holds the pair

        $this->assertTrue($this->fails(new UniqueLiveDocument('DOC-1', '1.0', 'live', $underReview->id)));
    }

    public function test_two_live_docs_with_different_codes_pass(): void
    {
        $this->makeDoc('DOC-1', '1.0', DocumentStatus::Live);

        $this->assertFalse($this->fails(new UniqueLiveDocument('DOC-2', '1.0', 'live')));
    }

    public function test_empty_code_or_version_does_not_run_the_unique_check(): void
    {
        $this->makeDoc('DOC-1', '1.0', DocumentStatus::Live);

        $this->assertFalse($this->fails(new UniqueLiveDocument('', '1.0', 'live')));
        $this->assertFalse($this->fails(new UniqueLiveDocument('DOC-1', '', 'live')));
    }

    public function test_rule_fires_regardless_of_which_field_it_is_attached_to(): void
    {
        // The rule is field-agnostic: attaching it to code, version, or status must all fire,
        // so changing any of the three re-validates the triple on submit.
        $this->makeDoc('DOC-1', '1.0', DocumentStatus::Live);
        $rule = new UniqueLiveDocument('DOC-1', '1.0', 'live');

        foreach (['code', 'version', 'status'] as $attribute) {
            $failed = false;
            $rule->validate($attribute, null, function () use (&$failed): void {
                $failed = true;
            });

            $this->assertTrue($failed, "rule must fire when attached to the {$attribute} field");
        }
    }

    public function test_message_includes_code_and_version_placeholders(): void
    {
        // .env.testing forces APP_LOCALE=en (no lang/en → keys return raw); the real panel runs fa.
        $this->app->setLocale('fa');
        $this->makeDoc('DOC-1', '1.0', DocumentStatus::Live);

        $message = null;
        (new UniqueLiveDocument('DOC-1', '1.0', 'live'))
            ->validate('code', null, function (string $m) use (&$message): void {
                $message = $m;
            });

        $this->assertNotNull($message);
        $this->assertStringNotContainsString('resources/dms/strings', $message);
        $this->assertStringContainsString('DOC-1', $message);
        $this->assertStringContainsString('1.0', $message);
    }
}