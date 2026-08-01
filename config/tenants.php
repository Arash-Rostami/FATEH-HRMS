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
 * @var string|null APP_INSTAGRAM
 * @var string|null APP_LINKEDIN
 */

return [

    'fateh' => [
        'name' => 'اینتــرا',
        'name_en' => 'Interra',

        'company_name' => 'اینتـرا، خـانه دیجیتـال مـا',
        'organization_name' => 'شرکت توسعه معادن و صنایع معدنی فاتح',
        'organization_name_en' => 'Fateh',

        'slogan' => 'خانه دیجیتال سازمان ما',
        'slogan_en' => 'Office Tools Services',

        'company_logo' => tenantAsset('fateh', 'img', 'logo', 'build/assets/img/logo.svg'),
        'app_logo_light' => tenantAsset('fateh', 'img', 'light', 'build/assets/img/light.png'),
        'app_logo_dark' => tenantAsset('fateh', 'img', 'dark', 'build/assets/img/dark.png'),

        'favicon' => tenantAsset('fateh', 'img', 'favicon', 'build/assets/img/favicon.ico'),
        'background_image' => tenantAsset('fateh', 'img', 'user-background', 'build/assets/img/user-background.jpg'),
        'admin_background_image' => tenantAsset('fateh', 'img', 'admin-background', 'build/assets/img/admin-background.jpg'),
        'videos' => tenantVideos('fateh'),

        'instagram' => 'https://www.instagram.com/fatehsanat/',
        'linkedin' => 'https://www.linkedin.com/company/fatehgroup',

        'admin_reverse_logo' => false,
        'user_reverse_logo' => false,
        'admin_use_company_logo' => false,
        'user_use_company_logo' => true,
    ],

    'persol' => [
        'name' => 'وینتــرا',
        'name_en' => 'WinterrA',

        'company_name' => 'وینتــرا، خـانه دیجیتـال مـا',
        'organization_name' => 'شرکـت هلدیـنگ پرسـال',
        'organization_name_en' => 'Persol Holding Corporation',

        'slogan' => 'خانه دیجیتال سازمان ما',
        'slogan_en' => 'Office Tools Services',

        'company_logo' => tenantAsset('persol', 'img', 'logo', 'build/assets/img/logo.svg'),
        'app_logo_light' => tenantAsset('persol', 'img', 'light', 'build/assets/img/light.png'),
        'app_logo_dark' => tenantAsset('persol', 'img', 'dark', 'build/assets/img/dark.png'),

        'favicon' => tenantAsset('persol', 'img', 'favicon', 'build/assets/img/favicon.ico'),
        'background_image' => tenantAsset('persol', 'img', 'user-background', 'build/assets/img/user-background.jpg'),
        'admin_background_image' => tenantAsset('persol', 'img', 'admin-background', 'build/assets/img/admin-background.jpg'),
        'videos' => tenantVideos('persol'),

        'instagram' => 'https://www.instagram.com/persol_co/',
        'linkedin' => 'https://www.linkedin.com/company/persol/',

        'admin_reverse_logo' => true,
        'user_reverse_logo' => false,
        'admin_use_company_logo' => false,
        'user_use_company_logo' => false,
    ],

];
