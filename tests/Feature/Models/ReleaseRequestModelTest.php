<?php

namespace Tests\Feature\Models;

use App\Enums\ReleaseRequestStatus;
use App\Enums\ReleaseRequestType;
use App\Models\ReleaseRequest;
use App\Models\User;
use App\Services\ContentSanitizerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ReflectionProperty;
use Tests\TestCase;

class ReleaseRequestModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        $this->resetDbCharsetCache();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        $this->resetDbCharsetCache();
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

    private function resetDbCharsetCache(): void
    {
        $prop = new ReflectionProperty(ContentSanitizerService::class, 'dbIsUtf8mb4');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    public function test_scope_for_type_filters_by_enum_value(): void
    {
        $support = ReleaseRequest::factory()->create(['type' => ReleaseRequestType::Support->value]);
        $bug = ReleaseRequest::factory()->create(['type' => ReleaseRequestType::Bug->value]);

        $ids = ReleaseRequest::forType(ReleaseRequestType::Support)->pluck('id')->all();

        $this->assertContains($support->id, $ids);
        $this->assertNotContains($bug->id, $ids);
    }

    public function test_scope_for_status_filters_by_enum_value(): void
    {
        $open = ReleaseRequest::factory()->create(['status' => ReleaseRequestStatus::Open->value]);
        $resolved = ReleaseRequest::factory()->create(['status' => ReleaseRequestStatus::Resolved->value]);

        $ids = ReleaseRequest::forStatus(ReleaseRequestStatus::Open)->pluck('id')->all();

        $this->assertContains($open->id, $ids);
        $this->assertNotContains($resolved->id, $ids);
    }

    public function test_scope_pending_includes_open_and_in_review_only(): void
    {
        $open = ReleaseRequest::factory()->create(['status' => ReleaseRequestStatus::Open->value]);
        $inReview = ReleaseRequest::factory()->create(['status' => ReleaseRequestStatus::InReview->value]);
        $resolved = ReleaseRequest::factory()->create(['status' => ReleaseRequestStatus::Resolved->value]);

        $ids = ReleaseRequest::pending()->pluck('id')->all();

        $this->assertContains($open->id, $ids);
        $this->assertContains($inReview->id, $ids);
        $this->assertNotContains($resolved->id, $ids);
    }

    public function test_unscoped_query_returns_all_rows(): void
    {
        $baseline = ReleaseRequest::count();
        ReleaseRequest::factory()->create(['status' => ReleaseRequestStatus::Open->value]);
        ReleaseRequest::factory()->create(['status' => ReleaseRequestStatus::Resolved->value]);

        $this->assertSame($baseline + 2, ReleaseRequest::count());
    }

    public function test_title_set_mutator_strips_tags_and_trims(): void
    {
        $rr = new ReleaseRequest();
        $rr->title = '  <p>  Hello  </p>  ';

        $this->assertSame('Hello', $rr->getAttributes()['title']);
    }

    public function test_title_set_mutator_passes_null_through_unchanged(): void
    {
        $rr = new ReleaseRequest();
        $rr->title = null;

        $this->assertNull($rr->getAttributes()['title']);
    }

    public function test_body_set_mutator_strips_tags_and_sanitizes(): void
    {
        $rr = new ReleaseRequest();
        $rr->body = "<p>hello\x00world</p>";

        $this->assertSame('helloworld', $rr->getAttributes()['body']);
    }

    public function test_body_set_mutator_passes_null_through_unchanged(): void
    {
        $rr = new ReleaseRequest();
        $rr->body = null;

        $this->assertNull($rr->getAttributes()['body']);
    }

    public function test_body_set_mutator_normalizes_blank_lines(): void
    {
        $rr = new ReleaseRequest();
        $rr->body = "<p>line1</p>\n\n\n\n<p>line2</p>";

        $this->assertSame("line1\n\nline2", $rr->getAttributes()['body']);
    }

    public function test_title_and_body_round_trip_through_db(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $rr = ReleaseRequest::factory()->create([
            'user_id' => $user->id,
            'title' => '  <p>Report</p>  ',
            'body' => '<p>please fix</p>',
        ]);

        $fresh = $rr->fresh();
        $this->assertSame('Report', $fresh->title);
        $this->assertSame('please fix', $fresh->body);
    }

    public function test_user_relation_returns_owning_user(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $rr = ReleaseRequest::factory()->create(['user_id' => $user->id]);

        $this->assertSame($user->id, $rr->user->id);
    }

    public function test_attachments_cast_to_array_and_round_trip_through_db(): void
    {
        $attachments = [
            ['path' => 'release_request/attachments/a.jpg', 'name' => 'a.jpg', 'mime' => 'image/jpeg', 'size' => 123],
        ];

        $rr = ReleaseRequest::factory()->create(['attachments' => $attachments]);
        $fresh = $rr->fresh();

        $this->assertIsArray($fresh->attachments);
        $this->assertEquals($attachments, $fresh->attachments);
    }

    public function test_attachments_defaults_to_null_when_not_set(): void
    {
        $rr = ReleaseRequest::factory()->create();

        $this->assertNull($rr->fresh()->attachments);
    }

    public function test_deleting_removes_attachment_files_from_storage(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('release_request/attachments/orphan.jpg', 'content');

        $rr = ReleaseRequest::factory()->create([
            'attachments' => [
                ['path' => 'release_request/attachments/orphan.jpg', 'name' => 'orphan.jpg', 'mime' => 'image/jpeg', 'size' => 7],
            ],
        ]);

        $rr->delete();

        Storage::disk('public')->assertMissing('release_request/attachments/orphan.jpg');
    }

    public function test_deleting_with_no_attachments_does_not_error(): void
    {
        Storage::fake('public');
        $rr = ReleaseRequest::factory()->create(['attachments' => null]);

        $rr->delete();

        $this->assertDatabaseMissing('release_requests', ['id' => $rr->id]);
    }
}