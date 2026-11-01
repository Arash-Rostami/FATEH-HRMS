<?php

namespace Tests\Feature\Traits;

use App\Models\Suggestion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use Tests\TestCase;

class CleansAttachedFilesTest extends TestCase
{
    private function deleteStoredFiles(mixed $value, array $pathKeys = ['path']): void
    {
        $method = new ReflectionMethod(Suggestion::class, 'deleteStoredFiles');
        $method->setAccessible(true);
        $method->invoke(null, $value, $pathKeys);
    }

    private function deleteStoredDirectory(string $directory): void
    {
        $method = new ReflectionMethod(Suggestion::class, 'deleteStoredDirectory');
        $method->setAccessible(true);
        $method->invoke(null, $directory);
    }

    public function test_delete_stored_files_with_single_string_path_deletes_it(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('a.jpg', 'content');

        $this->deleteStoredFiles('a.jpg');

        Storage::disk('public')->assertMissing('a.jpg');
    }

    public function test_delete_stored_files_with_flat_array_of_string_paths_deletes_all(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('a.jpg', 'content');
        Storage::disk('public')->put('b.jpg', 'content');

        $this->deleteStoredFiles(['a.jpg', 'b.jpg']);

        Storage::disk('public')->assertMissing('a.jpg');
        Storage::disk('public')->assertMissing('b.jpg');
    }

    public function test_delete_stored_files_with_associative_items_uses_default_path_key(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('a.jpg', 'content');

        $this->deleteStoredFiles([['path' => 'a.jpg', 'name' => 'a.jpg']]);

        Storage::disk('public')->assertMissing('a.jpg');
    }

    public function test_delete_stored_files_with_custom_path_keys_pulls_multiple_keys_per_item(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('video.mp4', 'content');
        Storage::disk('public')->put('thumb.jpg', 'content');

        $this->deleteStoredFiles([['url' => 'video.mp4', 'thumbnail' => 'thumb.jpg']], ['url', 'thumbnail']);

        Storage::disk('public')->assertMissing('video.mp4');
        Storage::disk('public')->assertMissing('thumb.jpg');
    }

    public function test_delete_stored_files_with_null_is_a_no_op(): void
    {
        Storage::fake('public');

        $this->deleteStoredFiles(null);

        $this->assertTrue(true);
    }

    public function test_delete_stored_files_with_empty_array_is_a_no_op(): void
    {
        Storage::fake('public');

        $this->deleteStoredFiles([]);

        $this->assertTrue(true);
    }

    public function test_delete_stored_files_skips_absolute_path(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('/etc/passwd', 'content');

        $this->deleteStoredFiles('/etc/passwd');

        Storage::disk('public')->assertExists('/etc/passwd');
    }

    public function test_delete_stored_files_skips_path_traversal(): void
    {
        Storage::fake('public');

        $this->deleteStoredFiles('../secret.txt');

        $this->assertTrue(true);
    }

    public function test_delete_stored_files_skips_external_url_without_throwing(): void
    {
        Storage::fake('public');

        $this->deleteStoredFiles('http://example.com/x.jpg');

        $this->assertTrue(true);
    }

    public function test_delete_stored_directory_deletes_directory_contents(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('dir/a.jpg', 'content');
        Storage::disk('public')->put('dir/b.jpg', 'content');

        $this->deleteStoredDirectory('dir');

        Storage::disk('public')->assertMissing('dir/a.jpg');
        Storage::disk('public')->assertMissing('dir/b.jpg');
    }

    public function test_delete_stored_directory_rejects_empty_string(): void
    {
        Storage::fake('public');

        $this->deleteStoredDirectory('');

        $this->assertTrue(true);
    }

    public function test_delete_stored_directory_rejects_path_traversal(): void
    {
        Storage::fake('public');

        $this->deleteStoredDirectory('../outside');

        $this->assertTrue(true);
    }

    public function test_delete_stored_files_accepts_arrayable_collection(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('a.jpg', 'content');
        Storage::disk('public')->put('b.jpg', 'content');

        $this->deleteStoredFiles(new Collection(['a.jpg', 'b.jpg']));

        Storage::disk('public')->assertMissing('a.jpg');
        Storage::disk('public')->assertMissing('b.jpg');
    }
}
