<?php

$tenant = (require __DIR__ . '/tenants.php')[env('APP_TENANT', 'fateh')] ?? [];
$releases = require __DIR__ . '/releases.php';
$latestVersion = end($releases)['version'] ?? 'β';

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'tenant' => env('APP_TENANT', 'fateh'),

    'name' => env('APP_NAME', $tenant['app']['name'] ),
    'name_en' => env('APP_NAME_EN', $tenant['app']['name_en'] ),
    'company_name' => env('APP_NAME_ALT', $tenant['app']['alt_name'] ),
    'organization_name' => env('APP_ORGANIZATION', $tenant['company']['name'] ),
    'organization_name_en' => env('APP_ORGANIZATION_EN', $tenant['company']['name_en'] ),
    'slogan' => env('APP_SLOGAN', $tenant['app']['slogan'] ),
    'slogan_en' => env('APP_SLOGAN_EN', $tenant['app']['slogan_en']),
    'company_logo' => env('COMPANY_LOGO', $tenant['company']['logo'] ),
    'app_logo_light' => env('APP_LOGO_LIGHT', $tenant['app']['logo_light'] ),
    'app_logo_dark' => env('APP_LOGO_DARK', $tenant['app']['logo_dark'] ),
    'admin_reverse_logo' => (bool)($tenant['logo_display']['admin_reverse'] ?? false),
    'user_reverse_logo' => (bool)($tenant['logo_display']['user_reverse'] ?? false),
    'admin_auth_reverse_logo' => (bool)($tenant['logo_display']['admin_auth_reverse'] ?? false),
    'user_auth_reverse_logo' => (bool)($tenant['logo_display']['user_auth_reverse'] ?? false),
    'admin_use_company_logo' => (bool)($tenant['logo_display']['admin_use_company'] ?? false),
    'user_use_company_logo' => (bool)($tenant['logo_display']['user_use_company'] ?? false),
    'ai_assistant_access' => env('AI_ASSISTANT_ACCESS', $tenant['ai_assistant']['access'] ?? false),
    'ai_assistant_lang' => env('AI_ASSISTANT_LANG', $tenant['ai_assistant']['lang'] ?? 'fa'),
    'favicon' => env('APP_FAVICON', $tenant['app']['favicon']),
    'background_image' => env('APP_BACKGROUND_IMAGE', $tenant['background']['user'] ),
    'admin_background_image' => $tenant['background']['admin'],
    'videos' => $tenant['videos'] ?? [],
    'backdrop' => $tenant['backdrop'] ?? [
        'images' => [], 'mode' => 'time', 'opacity' => 0.85,
        'blur' => 0, 'brightness' => 1, 'contrast' => 1, 'grayscale' => 0, 'width' => '100%',
    ],
    'instagram' => env('APP_INSTAGRAM', $tenant['company']['social']['instagram'] ?? null),
    'linkedin' => env('APP_LINKEDIN', $tenant['company']['social']['linkedin'] ?? null),
    'version' => env('APP_VERSION', $latestVersion),
    'developer' => env('DEVELOPER', 'Arash R.'),
    'owner' => env('OWNER', 'PBS'),
    'last_update' => env('APP_UPDATE', now()),

    'trusted_proxies' => env('TRUSTED_PROXIES'),
    'cache_ttl' => (int) env('APP_CACHE_TTL', 300),

    'support' => [
        'phone' => env('SUPPORT_PHONE', '+9122398772'),
        'email' => env('SUPPORT_EMAIL', 'arashrostami@time-gr.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool)env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'Asia/Tehran'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'fa'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string)env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" statusSwitcher. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
