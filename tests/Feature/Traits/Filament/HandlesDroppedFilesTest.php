<?php

namespace Tests\Feature\Traits\Filament;

use App\Models\Suggestion;
use App\Traits\Filament\HandlesDroppedFiles;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HandlesDroppedFilesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        Cache::flush();
        DB::beginTransaction();
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        Cache::flush();
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

    private function host(): object
    {
        return new class {
            use HandlesDroppedFiles {
                trackedFileColumns as public;
                trackedFilePaths as public;
                deleteDroppedFiles as public;
                updateDeletingDroppedFiles as public;
            }
        };
    }

    private function stagePublic(string ...$paths): void
    {
        foreach ($paths as $path) {
            Storage::disk('public')->put($path, 'x');
        }
    }

    public function test_tracked_file_columns_default_to_nothing_tracked(): void
    {
        $this->assertSame([], $this->host()->trackedFileColumns());
    }

    public function test_tracked_file_paths_collects_paths_from_tracked_columns(): void
    {
        $record = Suggestion::factory()->create(['attachment' => 'suggestions/a.pdf']);

        $this->assertSame(['suggestions/a.pdf'], $this->host()->trackedFilePaths($record, ['attachment' => []]));
    }

    public function test_tracked_file_paths_is_empty_when_nothing_is_tracked(): void
    {
        $record = Suggestion::factory()->create(['attachment' => 'suggestions/a.pdf']);

        $this->assertSame([], $this->host()->trackedFilePaths($record, []));
    }

    public function test_update_deletes_the_replaced_file_and_keeps_the_new_one(): void
    {
        $this->stagePublic('suggestions/old.pdf', 'suggestions/new.pdf');
        $record = Suggestion::factory()->create(['attachment' => 'suggestions/old.pdf']);

        $this->host()->updateDeletingDroppedFiles($record, ['attachment' => 'suggestions/new.pdf'], ['attachment' => []]);

        Storage::disk('public')->assertMissing('suggestions/old.pdf');
        Storage::disk('public')->assertExists('suggestions/new.pdf');
        $this->assertSame('suggestions/new.pdf', $record->fresh()->attachment);
    }

    public function test_update_keeps_an_unchanged_file(): void
    {
        $this->stagePublic('suggestions/keep.pdf');
        $record = Suggestion::factory()->create(['attachment' => 'suggestions/keep.pdf']);

        $this->host()->updateDeletingDroppedFiles($record, ['attachment' => 'suggestions/keep.pdf'], ['attachment' => []]);

        Storage::disk('public')->assertExists('suggestions/keep.pdf');
    }

    public function test_files_are_not_deleted_when_the_update_fails(): void
    {
        $this->stagePublic('suggestions/old.pdf');
        $record = Suggestion::factory()->create(['attachment' => 'suggestions/old.pdf']);

        try {
            $this->host()->updateDeletingDroppedFiles($record, ['no_such_column' => 'x'], ['attachment' => []]);
            $this->fail('The failing update did not throw.');
        } catch (\Throwable) {
            Storage::disk('public')->assertExists('suggestions/old.pdf');
        }
    }

    public function test_delete_dropped_files_with_empty_before_list_is_a_no_op(): void
    {
        $this->stagePublic('suggestions/other.pdf');
        $record = Suggestion::factory()->create(['attachment' => 'suggestions/other.pdf']);

        $this->host()->deleteDroppedFiles($record, [], ['attachment' => []]);

        Storage::disk('public')->assertExists('suggestions/other.pdf');
    }
}
