<?php

namespace Tests\Feature\Traits;

use App\Services\ProjectTask\CreateTaskAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use Tests\TestCase;

class StoresAttachedFilesTest extends TestCase
{
    private function storeAttachment(mixed $file, string $directory, ?callable $fileName = null): array
    {
        $method = new ReflectionMethod(CreateTaskAction::class, 'storeAttachment');
        $method->setAccessible(true);

        return $method->invoke(null, $file, $directory, $fileName);
    }

    public function test_store_attachment_without_file_name_closure_uses_default_random_name(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->create('report.pdf', 10, 'application/pdf');

        $result = $this->storeAttachment($file, 'reports');

        Storage::disk('public')->assertExists($result['path']);
        $this->assertStringStartsWith('reports/', $result['path']);
    }

    public function test_store_attachment_with_file_name_closure_uses_custom_name(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->create('report.pdf', 10, 'application/pdf');

        $result = $this->storeAttachment($file, 'reports', fn($f) => 'custom-name.pdf');

        $this->assertSame('reports/custom-name.pdf', $result['path']);
        Storage::disk('public')->assertExists('reports/custom-name.pdf');
    }

    public function test_store_attachment_returns_canonical_shape_with_original_client_name(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->create('original-name.pdf', 10, 'application/pdf');

        $result = $this->storeAttachment($file, 'reports', fn($f) => 'renamed-on-disk.pdf');

        $this->assertSame(['path', 'name', 'mime', 'size'], array_keys($result));
        $this->assertSame('original-name.pdf', $result['name']);
        $this->assertSame('reports/renamed-on-disk.pdf', $result['path']);
    }

    public function test_store_attachment_returns_correct_mime_type(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->create('image.jpg', 10, 'image/jpeg');

        $result = $this->storeAttachment($file, 'reports');

        $this->assertSame('image/jpeg', $result['mime']);
    }

    public function test_store_attachment_returns_correct_size_in_kilobytes(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->create('report.pdf', 50, 'application/pdf');

        $result = $this->storeAttachment($file, 'reports');

        $this->assertSame($file->getSize(), $result['size']);
    }

    public function test_store_attachment_file_name_closure_receives_the_same_file_instance(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->create('report.pdf', 10, 'application/pdf');

        $result = $this->storeAttachment($file, 'reports', fn($f) => $f->getClientOriginalExtension() . '-suffix.' . $f->getClientOriginalExtension());

        $this->assertSame('reports/pdf-suffix.pdf', $result['path']);
    }
}
