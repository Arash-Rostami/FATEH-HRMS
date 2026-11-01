<?php

namespace Tests\Feature\Config;

use Tests\TestCase;

class AppConfigTest extends TestCase
{
    private function loadAppConfig(array $env): array
    {
        $previous = [];

        foreach ($env as $key => $value) {
            $previous[$key] = getenv($key);
            $this->setEnv($key, $value);
        }

        try {
            return require config_path('app.php');
        } finally {
            foreach ($previous as $key => $value) {
                $this->setEnv($key, $value === false ? null : $value);
            }
        }
    }

    private function setEnv(string $key, ?string $value): void
    {
        if ($value === null) {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);

            return;
        }

        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    public function test_env_override_wins_over_tenant_default(): void
    {
        $config = $this->loadAppConfig([
            'APP_TENANT' => 'fateh',
            'APP_SLOGAN' => 'Custom Override Slogan',
        ]);

        $this->assertSame('Custom Override Slogan', $config['slogan']);
    }

    public function test_falls_back_to_active_tenant_value_when_env_unset(): void
    {
        $config = $this->loadAppConfig([
            'APP_TENANT' => 'persol',
            'APP_NAME' => null,
            'APP_NAME_EN' => null,
            'APP_SLOGAN' => null,
        ]);

        $persol = config('tenants.persol');

        $this->assertSame($persol['app']['name'], $config['name']);
        $this->assertSame($persol['app']['name_en'], $config['name_en']);
        $this->assertSame($persol['app']['slogan'], $config['slogan']);
        $this->assertTrue($config['admin_reverse_logo']);
        $this->assertTrue($config['user_reverse_logo']);
    }

    public function test_behavioral_flags_are_cast_to_bool(): void
    {
        $config = $this->loadAppConfig(['APP_TENANT' => 'fateh']);

        foreach (['admin_reverse_logo', 'user_reverse_logo', 'admin_auth_reverse_logo', 'user_auth_reverse_logo', 'admin_use_company_logo', 'user_use_company_logo', 'debug'] as $key) {
            $this->assertIsBool($config[$key], "{$key} should be cast to bool");
        }
    }

    public function test_version_defaults_to_the_latest_release_entry_when_env_unset(): void
    {
        $config = $this->loadAppConfig([
            'APP_TENANT' => 'fateh',
            'APP_VERSION' => null,
        ]);

        $releases = require config_path('releases.php');

        $this->assertSame(end($releases)['version'], $config['version']);
    }

    public function test_version_env_override_wins_over_the_latest_release_entry(): void
    {
        $config = $this->loadAppConfig([
            'APP_TENANT' => 'fateh',
            'APP_VERSION' => 'β.custom',
        ]);

        $this->assertSame('β.custom', $config['version']);
    }

    public function test_previous_keys_filters_empty_segments_and_reindexes(): void
    {
        $config = $this->loadAppConfig([
            'APP_TENANT' => 'fateh',
            'APP_PREVIOUS_KEYS' => 'key-one,,key-two,',
        ]);

        $this->assertSame(['key-one', 'key-two'], $config['previous_keys']);
    }

    public function test_previous_keys_defaults_to_empty_array_when_unset(): void
    {
        $config = $this->loadAppConfig([
            'APP_TENANT' => 'fateh',
            'APP_PREVIOUS_KEYS' => null,
        ]);

        $this->assertSame([], $config['previous_keys']);
    }

    public function test_ai_assistant_access_env_override_accepts_lock_string(): void
    {
        $config = $this->loadAppConfig([
            'APP_TENANT' => 'fateh',
            'AI_ASSISTANT_ACCESS' => 'lock',
        ]);

        $this->assertSame('lock', $config['ai_assistant_access']);
    }

    public function test_ai_assistant_lang_env_override_wins_over_tenant_default(): void
    {
        $config = $this->loadAppConfig([
            'APP_TENANT' => 'fateh',
            'AI_ASSISTANT_LANG' => 'en',
        ]);

        $this->assertSame('en', $config['ai_assistant_lang']);
    }

    public function test_trusted_proxies_resolves_from_env(): void
    {
        $config = $this->loadAppConfig([
            'APP_TENANT' => 'fateh',
            'TRUSTED_PROXIES' => '10.0.0.1,10.0.0.2',
        ]);

        $this->assertSame('10.0.0.1,10.0.0.2', $config['trusted_proxies']);
    }

    public function test_trusted_proxies_defaults_to_null_when_unset(): void
    {
        $config = $this->loadAppConfig([
            'APP_TENANT' => 'fateh',
            'TRUSTED_PROXIES' => null,
        ]);

        $this->assertNull($config['trusted_proxies']);
    }
}
