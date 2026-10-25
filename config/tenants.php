<?php


/**
 * Env vars for the tenant/branding system — see config/tenants.php (single source of truth
 * per tenant) and config/tenantPattern.md (full mechanism writeup).
 *
 * @var string      APP_TENANT           Active tenant slug (default: fateh). Selects the config/tenants.php entry that drives every key below.
 *
 * Optional per-deployment overrides — set ONLY to override the active tenant's own value; leave unset to use the tenant's value as-is:
 * @var string|null APP_NAME
 * @var string|null APP_NAME_EN
 * @var string|null APP_NAME_ALT
 * @var string|null APP_ORGANIZATION
 * @var string|null APP_ORGANIZATION_EN
 * @var string|null APP_SLOGAN
 * @var string|null APP_SLOGAN_EN
 * @var string|null COMPANY_LOGO
 * @var string|null APP_LOGO_LIGHT
 * @var string|null APP_LOGO_DARK
 * @var string|null APP_FAVICON
 * @var string|null APP_BACKGROUND_IMAGE
 * @var string|null APP_OFFICE_PHONE
 * @var string|null APP_INSTAGRAM
 * @var string|null APP_LINKEDIN
 * @var string|null      AI_ASSISTANT_URL     Overrides the active tenant's own 'ai.assistant.url' key.
 * @var bool|string|null AI_ASSISTANT_ACCESS  Overrides the active tenant's own 'ai.assistant.access' key (true|false|'lock').
 * @var bool|null        AI_DOCUMENT_ACCESS   Overrides the active tenant's own 'ai.document.access' key.
 * @var bool|null        AI_DOCUMENT_AI       Overrides the active tenant's own 'ai.document.ai' key.
 * @var bool|null        AI_DOCUMENT_CHAT     Overrides the active tenant's own 'ai.document.chat' key.
 * @var string|null      APP_OFF              Comma-separated module ids and 'module:view' keys to hide; when set it replaces the active tenant's own 'modules' map.
 *
 * Each tenant may carry an optional 'modules' map: 'modules' => ['reservation' => false, 'status:chart' => false].
 * Keys are module ids from config/modules.php or 'module:view' keys; false hides it, true (or an unlisted key)
 * leaves it on, so a missing key never needs a tenant edit and a sub-tenant can set true to un-hide.
 * Small tools have no registry entry: 'radio' => false, 'calculator' => false, 'stopwatch' => false hide the tool's
 * menu item and floating panel.
 *
 * Per-tenant plan-tier flags live in each tenant's 'ai' group below (not env vars by default). 'ai' nests one
 * array per AI feature:
 * 'ai' => ['assistant' => [...], 'document' => [...]].
 * - 'assistant' => ['access' => true|false|'lock', 'lang' => ...] is the chat assistant widget. A tenant that omits
 *   'access' behaves as false — the widget renders nothing. true shows the full working widget. 'lock' shows the
 *   header icon with a lock badge and an "upgrade your plan" toast on click, but never opens the actual chat panel.
 * - 'document' => ['access' => bool, 'modules' => [...], 'ai' => bool, 'chat' => bool] is the Document AI engine bridge: 'access'
 *   switches the whole bridge (and Smart search, no AI), 'modules' lists the module keys sent to the engine
 *   (e.g. ['dms']), 'ai' switches AI find (token cost) independently, 'chat' switches the document chat (a separate later module, token cost; nothing reads it yet). Missing keys behave as off/empty.
 *
 * Each tenant nests related settings into eight groups rather than ~24 flat sibling keys — see
 * config/tenantPattern.md's "Nested tenant setting groups" section for what belongs in each and why:
 * 'app' (the SaaS product's own identity: name/slogan/logo/favicon), 'company' (the actual tenant
 * organization's identity — distinct from 'app', see that section for why they were never the same
 * thing despite the old flat 'company_name' key actually meaning "app name, alternate form" —
 * 'company' also nests 'social' (instagram/linkedin), since social links belong to the real
 * organization, not the product), 'background', 'videos', 'backdrop', 'logo_display'
 * (reverse/use-company-logo behavior flags), 'ai' (nests 'assistant' and 'document'). config/app.php is the sole reader of
 * this nested shape — every other file in the app keeps reading the same flat config('app.*') keys
 * it always has.
 */

$tenants = [

    '_base' => [
        'company' => [
            'support' => ['phone' => '', 'email' => ''],
        ],
        'theme' => [
            'primary' => '#4e5f66',
            'header_bg' => '#08191e',
            'admin_primary' => 'amber',
        ],
        'ai' => [
            'assistant' => ['url' => null],
        ],
    ],

    'fateh' => [
        'app' => [
            'name' => 'اینتــرا',
            'name_en' => 'Interra',
            'alt_name' => 'اینتـرا، خـانه دیجیتـال مـا',
            'slogan' => 'خانه دیجیتال سازمان ما',
            'slogan_en' => 'Office Tools Services',
            'logo_light' => tenantAsset('fateh', 'img', 'light', 'build/assets/img/light.png'),
            'logo_dark' => tenantAsset('fateh', 'img', 'dark', 'build/assets/img/dark.png'),
            'favicon' => tenantAsset('fateh', 'img', 'favicon', 'build/assets/img/favicon.ico'),
        ],

        'company' => [
            'name' => 'شرکت توسعه معادن و صنایع معدنی فاتح',
            'name_en' => 'Fateh',
            'logo' => tenantAsset('fateh', 'img', 'logo', 'build/assets/img/logo.svg'),
            'phone' => '',
            'support' => ['phone' => '+9122398772', 'email' => 'arashrostami@time-gr.com'],
            'social' => [
                'instagram' => 'https://www.instagram.com/fatehsanat/',
                'linkedin' => 'https://www.linkedin.com/company/fatehgroup',
            ],
        ],

        'background' => [
            'user' => tenantAsset('fateh', 'img', 'user-background', 'build/assets/img/user-background.jpg'),
            'admin' => tenantAsset('fateh', 'img', 'admin-background', 'build/assets/img/admin-background.jpg'),
        ],

        'videos' => tenantVideos('fateh'),
        'backdrop' => [
            'images' => tenantBackdrops('fateh'),
            'mode' => 'tabs',
            'opacity' => 0.85,
            'blur' => 0,
            'brightness' => 1,
            'contrast' => 1,
            'grayscale' => 0,
            'width' => '85%',
        ],

        'logo_display' => [
            'admin_reverse' => false,
            'user_reverse' => false,
            'admin_auth_reverse' => false,
            'user_auth_reverse' => false,
            'admin_use_company' => false,
            'user_use_company' => true,
        ],

        'ai' => [
            'assistant' => [
                'access' => false,
                'lang' => 'fa',
            ],
            'document' => [
                'access' => false,
                'modules' => [],
                'ai' => false,
                'chat' => false,
            ],
        ],

         'modules' => [                       // unlisted = on; list only what you want off. true = on again (a sub-tenant can re-enable)
        //     'reservation' => false,          // whole module: pages, URL (404), admin, menus, search
        //     'reservation:avantgarde' => false, // only one view of a module (see 'views' in config/modules.php)
        //     'status:chart' => false,
        //     'faq:card' => false,
        //     'calendar:day' => false,
        //     'taskboard' => false,            // also hides projects + tasksheet + ths (its 'with' list in config/modules.php)
         ],                                   // see everything hidden: php artisan tenant:hidden   |  all keys: php artisan tenant:hidden --all
    ],

    'persol' => [
        'app' => [
            'name' => 'وینتــرا',
            'name_en' => 'WinterrA',
            'alt_name' => 'وینتــرا، خـانه دیجیتـال مـا',
            'slogan' => 'خانه دیجیتال سازمان ما',
            'slogan_en' => 'Office Tools Services',
            'logo_light' => tenantAsset('persol', 'img', 'light', 'build/assets/img/light.png'),
            'logo_dark' => tenantAsset('persol', 'img', 'dark', 'build/assets/img/dark.png'),
            'favicon' => tenantAsset('persol', 'img', 'favicon', 'build/assets/img/favicon.ico'),
        ],

        'company' => [
            'name' => 'گروه شرکـت های پرسـال',
            'name_en' => 'Persol Holding Corporation',
            'logo' => tenantAsset('persol', 'img', 'logo', 'build/assets/img/logo.png'),
            'phone' => '021-26208352',
            'support' => ['phone' => '+9122398772', 'email' => 'arashrostami@time-gr.com'],
            'social' => [
                'instagram' => 'https://www.instagram.com/persol_co/',
                'linkedin' => 'https://www.linkedin.com/company/persol/',
            ],
        ],

        'background' => [
            'user' => tenantAsset('persol', 'img', 'user-background', 'build/assets/img/user-background.jpg'),
            'admin' => tenantAsset('persol', 'img', 'admin-background', 'build/assets/img/admin-background.jpg'),
        ],

        'videos' => tenantVideos('persol'),
        'backdrop' => [
            'images' => tenantBackdrops('persol'),
            'mode' => 'time',
            'opacity' => 0.01,
            'blur' => 0,
            'brightness' => 1,
            'contrast' => 1,
            'grayscale' => 0,
            'width' => '100%',
            'fit' => 'top',
        ],

        'theme' => [
            'primary' => '#375E90',
        ],

        'logo_display' => [
            'admin_reverse' => true,
            'user_reverse' => true,
            'admin_auth_reverse' => false,
            'user_auth_reverse' => false,
            'admin_use_company' => false,
            'user_use_company' => false,
        ],

        'ai' => [
            'assistant' => [
                'access' => true,
                'lang' => 'fa',
            ],
            'document' => [
                'access' => false,
                'modules' => [],
                'ai' => false,
                'chat' => false,
            ],
        ],

        'modules' => [
            'reservation:avantgarde' => false,
            'status:chart' => false,
            'radio' => false,
            'dms' => false,
            'gallery' => false

        ],
    ],

];

$base = $tenants['_base'];
unset($tenants['_base']);

return array_map(fn($tenant) => array_replace_recursive($base, $tenant), $tenants);
