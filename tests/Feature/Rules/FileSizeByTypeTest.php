<?php

namespace Tests\Feature\Rules;

use App\Rules\FileSizeByType;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FileSizeByTypeTest extends TestCase
{
    private function fails(FileSizeByType $rule, UploadedFile $file): bool
    {
        $failed = false;
        $rule->validate('path', $file, function () use (&$failed): void {
            $failed = true;
        });

        return $failed;
    }

    public function test_image_passes_under_the_image_tier_and_fails_over_it(): void
    {
        $rule = new FileSizeByType();

        $this->assertFalse($this->fails($rule, UploadedFile::fake()->create('a.jpg', 5000, 'image/jpeg')));
        $this->assertTrue($this->fails($rule, UploadedFile::fake()->create('a.jpg', 5121, 'image/jpeg')));
    }

    public function test_video_passes_under_the_video_tier_and_fails_over_it(): void
    {
        $rule = new FileSizeByType();

        $this->assertFalse($this->fails($rule, UploadedFile::fake()->create('a.mp4', 90000, 'video/mp4')));
        $this->assertTrue($this->fails($rule, UploadedFile::fake()->create('a.mp4', 97281, 'video/mp4')));
    }

    public function test_document_passes_under_the_document_tier_and_fails_over_it(): void
    {
        $rule = new FileSizeByType();

        $this->assertFalse($this->fails($rule, UploadedFile::fake()->create('a.pdf', 10000, 'application/pdf')));
        $this->assertTrue($this->fails($rule, UploadedFile::fake()->create('a.pdf', 10241, 'application/pdf')));
    }
}
