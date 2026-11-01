<?php

namespace Tests\Feature\Config;

use Tests\TestCase;

class FilesystemsConfigTest extends TestCase
{
    private function loadFilesystemsConfig(string $appUrl): array
    {
        $previous = getenv('APP_URL');

        putenv("APP_URL={$appUrl}");
        $_ENV['APP_URL'] = $appUrl;
        $_SERVER['APP_URL'] = $appUrl;

        try {
            return require config_path('filesystems.php');
        } finally {
            if ($previous === false) {
                putenv('APP_URL');
                unset($_ENV['APP_URL'], $_SERVER['APP_URL']);
            } else {
                putenv("APP_URL={$previous}");
                $_ENV['APP_URL'] = $previous;
                $_SERVER['APP_URL'] = $previous;
            }
        }
    }

    public function test_public_disk_url_strips_trailing_slash_from_app_url(): void
    {
        $config = $this->loadFilesystemsConfig('https://example.test/');

        $this->assertSame('https://example.test/storage', $config['disks']['public']['url']);
    }

    public function test_public_disk_url_is_unaffected_when_app_url_has_no_trailing_slash(): void
    {
        $config = $this->loadFilesystemsConfig('https://example.test');

        $this->assertSame('https://example.test/storage', $config['disks']['public']['url']);
    }
}
