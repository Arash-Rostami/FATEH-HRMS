<?php

namespace Tests\Feature\Models;

use App\Models\DMS;
use App\Models\Read;
use App\Models\User;
use App\Services\Menu\StateService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReadModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        Cache::forget('menu_state:version');
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        Cache::forget('menu_state:version');
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

    public function test_table_name_is_reads(): void
    {
        $this->assertSame('reads', (new Read())->getTable());
    }

    public function test_fillable_lists_contract_fields(): void
    {
        $this->assertSame(
            ['document_id', 'user_id', 'read', 'read_count', 'combined_read_count'],
            (new Read())->getFillable()
        );
    }

    public function test_dms_relation_is_belongs_to_dms_via_document_id(): void
    {
        $read = new Read();

        $relation = $read->dms();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertSame(DMS::class, $relation->getRelated()::class);
        $this->assertSame('document_id', $relation->getForeignKeyName());
    }

    public function test_user_relation_is_belongs_to_user_via_user_id(): void
    {
        $read = new Read();

        $relation = $read->user();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertSame(User::class, $relation->getRelated()::class);
        $this->assertSame('user_id', $relation->getForeignKeyName());
    }

    public function test_get_unread_documents_count_counts_read_true_and_read_count_zero(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        $dms = DMS::factory()->create();

        Read::factory()->create([
            'document_id' => $dms->id,
            'user_id' => $user->id,
            'read' => true,
            'read_count' => 0,
        ]);

        $this->actingAs($user);
        $baseline = Read::where('user_id', $user->id)->where('read', true)->where('read_count', 0)->count();

        $this->assertSame($baseline, Read::getUnreadDocumentsCount());
        $this->assertGreaterThan(0, Read::getUnreadDocumentsCount());

        $this->actingAs($other);
        $this->assertSame(0, Read::getUnreadDocumentsCount());
    }

    public function test_get_unread_documents_count_excludes_read_false_rows(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $dms = DMS::factory()->create();

        Read::factory()->create([
            'document_id' => $dms->id,
            'user_id' => $user->id,
            'read' => false,
            'read_count' => 0,
        ]);

        $this->actingAs($user);

        $this->assertSame(0, Read::getUnreadDocumentsCount());
    }

    public function test_get_unread_documents_count_excludes_read_count_positive_rows(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $dms = DMS::factory()->create();

        Read::factory()->create([
            'document_id' => $dms->id,
            'user_id' => $user->id,
            'read' => true,
            'read_count' => 5,
        ]);

        $this->actingAs($user);

        $this->assertSame(0, Read::getUnreadDocumentsCount());
    }

    public function test_get_unread_documents_count_excludes_other_users_rows(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        $dms = DMS::factory()->create();

        Read::factory()->create([
            'document_id' => $dms->id,
            'user_id' => $other->id,
            'read' => true,
            'read_count' => 0,
        ]);

        $this->actingAs($user);

        $this->assertSame(0, Read::getUnreadDocumentsCount());
    }

    public function test_factory_create_round_trips_fillable_fields_through_db(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $dms = DMS::factory()->create();

        $read = Read::factory()->create([
            'document_id' => $dms->id,
            'user_id' => $user->id,
            'read' => true,
            'read_count' => 3,
            'combined_read_count' => 7,
        ]);

        $fresh = $read->fresh();

        $this->assertSame($dms->id, $fresh->document_id);
        $this->assertSame($user->id, $fresh->user_id);
        $this->assertSame(1, (int) $fresh->read);
        $this->assertSame(3, $fresh->read_count);
        $this->assertSame(7, $fresh->combined_read_count);
    }

    public function test_user_names_tooltip_accessor_returns_null_when_users_empty(): void
    {
        $read = new Read();

        $this->assertNull($read->getUserNamesTooltipAttribute());
    }

    public function test_users_count_accessor_returns_zero_when_users_empty(): void
    {
        $read = new Read();

        $this->assertSame(0, $read->getUsersCountAttribute());
    }
}