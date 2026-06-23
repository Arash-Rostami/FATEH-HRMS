<?php

return [
    'accepted'             => 'تایید «:attribute» الزامی است.',
    'active_url'           => '«:attribute» یک آدرس اینترنتی معتبر نیست.',
    'after'                => '«:attribute» باید تاریخی بعد از :date باشد.',
    'after_or_equal'       => '«:attribute» باید تاریخی بعد یا مساوی با :date باشد.',
    'alpha'                => '«:attribute» فقط باید شامل حروف باشد.',
    'alpha_dash'           => '«:attribute» فقط باید شامل حروف، اعداد، خط تیره و زیرخط باشد.',
    'alpha_num'            => '«:attribute» فقط باید شامل حروف و اعداد باشد.',
    'array'                => '«:attribute» باید یک آرایه باشد.',
    'before'               => '«:attribute» باید تاریخی قبل از :date باشد.',
    'before_or_equal'      => '«:attribute» باید تاریخی قبل یا مساوی با :date باشد.',
    'between'              => [
        'numeric' => '«:attribute» باید بین :min و :max باشد.',
        'file'    => '«:attribute» باید بین :min و :max کیلوبایت باشد.',
        'string'  => '«:attribute» باید بین :min و :max کاراکتر باشد.',
        'array'   => '«:attribute» باید بین :min و :max آیتم داشته باشد.',
    ],
    'boolean'              => '«:attribute» باید true یا false باشد.',
    'confirmed'            => 'تکرار «:attribute» مطابقت ندارد.',
    'current_password'     => 'رمز عبور فعلی نامعتبر است.',
    'date'                 => 'تاریخ «:attribute» نامعتبر است.',
    'date_equals'          => '«:attribute» باید تاریخی برابر با :date باشد.',
    'date_format'          => '«:attribute» با فرمت :format مطابقت ندارد.',
    'different'            => '«:attribute» و :other باید متفاوت باشند.',
    'digits'               => '«:attribute» باید :digits رقم باشد.',
    'digits_between'       => '«:attribute» باید بین :min و :max رقم باشد.',
    'dimensions'           => 'ابعاد تصویر «:attribute» نامعتبر است.',
    'distinct'             => 'فیلد «:attribute» دارای مقدار تکراری است.',
    'email'                => 'فرمت ایمیل «:attribute» نامعتبر است.',
    'ends_with'            => '«:attribute» باید با یکی از این مقادیر پایان یابد: :values.',
    'exists'               => '«:attribute» انتخاب شده نامعتبر است.',
    'file'                 => '«:attribute» باید یک فایل باشد.',
    'filled'               => 'فیلد «:attribute» باید مقدار داشته باشد.',
    'gt'                   => [
        'numeric' => '«:attribute» باید بزرگتر از :value باشد.',
        'file'    => '«:attribute» باید بزرگتر از :value کیلوبایت باشد.',
        'string'  => '«:attribute» باید بزرگتر از :value کاراکتر باشد.',
        'array'   => '«:attribute» باید بیشتر از :value آیتم داشته باشد.',
    ],
    'gte'                  => [
        'numeric' => '«:attribute» باید بزرگتر یا مساوی با :value باشد.',
        'file'    => '«:attribute» باید بزرگتر یا مساوی با :value کیلوبایت باشد.',
        'string'  => '«:attribute» باید بزرگتر یا مساوی با :value کاراکتر باشد.',
        'array'   => '«:attribute» باید :value آیتم یا بیشتر داشته باشد.',
    ],
    'image'                => '«:attribute» باید یک تصویر باشد.',
    'in'                   => '«:attribute» انتخاب شده نامعتبر است.',
    'in_array'             => '«:attribute» در :other وجود ندارد.',
    'integer'              => '«:attribute» باید یک عدد صحیح باشد.',
    'ip'                   => '«:attribute» باید یک آدرس IP معتبر باشد.',
    'ipv4'                 => '«:attribute» باید یک آدرس IPv4 معتبر باشد.',
    'ipv6'                 => '«:attribute» باید یک آدرس IPv6 معتبر باشد.',
    'json'                 => '«:attribute» باید یک رشته JSON معتبر باشد.',
    'lt'                   => [
        'numeric' => '«:attribute» باید کوچکتر از :value باشد.',
        'file'    => '«:attribute» باید کوچکتر از :value کیلوبایت باشد.',
        'string'  => '«:attribute» باید کوچکتر از :value کاراکتر باشد.',
        'array'   => '«:attribute» باید کمتر از :value آیتم داشته باشد.',
    ],
    'lte'                  => [
        'numeric' => '«:attribute» باید کوچکتر یا مساوی با :value باشد.',
        'file'    => '«:attribute» باید کوچکتر یا مساوی با :value کیلوبایت باشد.',
        'string'  => '«:attribute» باید کوچکتر یا مساوی با :value کاراکتر باشد.',
        'array'   => '«:attribute» نباید بیشتر از :value آیتم داشته باشد.',
    ],
    'mac_address'          => '«:attribute» باید یک آدرس MAC معتبر باشد.',
    'max'                  => [
        'numeric' => '«:attribute» نباید بزرگتر از :max باشد.',
        'file'    => 'حجم «:attribute» نباید بیشتر از :max کیلوبایت باشد.',
        'string'  => 'طول «:attribute» نباید بیشتر از :max کاراکتر باشد.',
        'array'   => '«:attribute» نباید بیشتر از :max آیتم داشته باشد.',
    ],
    'mimes'                => 'فرمت فایل «:attribute» نامعتبر است.',
    'mimetypes'            => 'فرمت فایل «:attribute» نامعتبر است.',
    'min'                  => [
        'numeric' => '«:attribute» نباید کوچکتر از :min باشد.',
        'file'    => 'حجم «:attribute» نباید کمتر از :min کیلوبایت باشد.',
        'string'  => 'طول «:attribute» نباید کمتر از :min کاراکتر باشد.',
        'array'   => '«:attribute» نباید کمتر از :min آیتم داشته باشد.',
    ],
    'multiple_of'          => '«:attribute» باید مضربی از :value باشد.',
    'not_in'               => '«:attribute» انتخاب شده نامعتبر است.',
    'not_regex'            => 'فرمت «:attribute» نامعتبر است.',
    'numeric'              => '«:attribute» باید یک عدد باشد.',
    'password'             => 'رمز عبور نامعتبر است.',
    'present'              => 'فیلد «:attribute» باید وجود داشته باشد.',
    'prohibited'           => 'فیلد «:attribute» ممنوع است.',
    'prohibited_if'        => 'هنگامی که :other برابر :value است، فیلد «:attribute» ممنوع است.',
    'prohibited_unless'    => 'فیلد «:attribute» ممنوع است مگر اینکه :other در :values باشد.',
    'prohibits'            => 'فیلد «:attribute» از حضور :other جلوگیری می‌کند.',
    'regex'                => 'فرمت «:attribute» نامعتبر است.',
    'required'             => 'وارد کردن «:attribute» الزامی است.',
    'required_array_keys'  => '«:attribute» باید شامل این کلیدها باشد: :values.',
    'required_if'          => 'هنگامی که :other برابر :value است، فیلد «:attribute» الزامی است.',
    'required_unless'      => 'فیلد «:attribute» الزامی است مگر اینکه :other در :values باشد.',
    'required_with'        => 'هنگامی که :values حضور دارد، فیلد «:attribute» الزامی است.',
    'required_with_all'    => 'هنگامی که :values حضور دارند، فیلد «:attribute» الزامی است.',
    'required_without'     => 'هنگامی که :values حضور ندارد، فیلد «:attribute» الزامی است.',
    'required_without_all' => 'هنگامی که هیچ یک از :values حضور ندارند، فیلد «:attribute» الزامی است.',
    'same'                 => '«:attribute» و :other باید مطابقت داشته باشند.',
    'size'                 => [
        'numeric' => '«:attribute» باید :size باشد.',
        'file'    => '«:attribute» باید :size کیلوبایت باشد.',
        'string'  => '«:attribute» باید :size کاراکتر باشد.',
        'array'   => '«:attribute» باید شامل :size آیتم باشد.',
    ],
    'starts_with'          => '«:attribute» باید با یکی از این مقادیر شروع شود: :values.',
    'string'               => '«:attribute» باید یک رشته متنی باشد.',
    'timezone'             => '«:attribute» باید یک منطقه زمانی معتبر باشد.',
    'unique'               => 'این «:attribute» قبلاً ثبت شده است.',
    'uploaded'             => 'آپلود «:attribute» با شکست مواجه شد.',
    'url'                  => 'فرمت لینک «:attribute» نامعتبر است.',
    'uuid'                 => '«:attribute» باید یک UUID معتبر باشد.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    */

    'attributes' => [],

];
