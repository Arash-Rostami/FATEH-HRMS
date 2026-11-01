<?php

namespace Tests\Feature\Config;

use Tests\TestCase;

class FileUploadLimitsConfigTest extends TestCase
{
    public function test_all_four_tiers_are_present_and_positive(): void
    {
        foreach (['icon', 'image', 'document', 'video'] as $tier) {
            $this->assertIsInt(config("file_upload_limits.{$tier}"));
            $this->assertGreaterThan(0, config("file_upload_limits.{$tier}"));
        }
    }

    public function test_tiers_are_strictly_ascending(): void
    {
        $icon = config('file_upload_limits.icon');
        $image = config('file_upload_limits.image');
        $document = config('file_upload_limits.document');
        $video = config('file_upload_limits.video');

        $this->assertLessThan($image, $icon);
        $this->assertLessThan($document, $image);
        $this->assertLessThan($video, $document);
    }

    public function test_video_tier_stays_under_livewires_global_upload_ceiling(): void
    {
        $globalCeilingKb = (int) (string) str(config('livewire.temporary_file_upload.rules')[0])->after('max:');

        $this->assertLessThan($globalCeilingKb, config('file_upload_limits.video'));
    }
}
