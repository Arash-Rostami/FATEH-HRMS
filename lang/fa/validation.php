<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted'             => ':attribute باید پذیرفته شود.',
    'accepted_if'          => 'هنگامی که :other برابر :value است، :attribute باید پذیرفته شود.',
    'active_url'           => 'آدرس :attribute معتبر نیست.',
    'after'                => ':attribute باید تاریخی بعد از :date باشد.',
    'after_or_equal'       => ':attribute باید تاریخی بعد یا برابر :date باشد.',
    'alpha'                => ':attribute باید فقط شامل حروف الفبا باشد.',
    'alpha_dash'           => ':attribute باید فقط شامل حروف الفبا، اعداد، خط تیره و زیرخط باشد.',
    'alpha_num'            => ':attribute باید فقط شامل حروف الفبا و اعداد باشد.',
    'array'                => ':attribute باید یک آرایه باشد.',
    'ascii'                => ':attribute باید فقط شامل نویسه‌های استاندارد ASCII باشد.',
    'before'               => ':attribute باید تاریخی قبل از :date باشد.',
    'before_or_equal'      => ':attribute باید تاریخی قبل یا برابر :date باشد.',
    'between'              => [
        'array'   => ':attribute باید بین :min و :max مورد داشته باشد.',
        'file'    => ':attribute باید بین :min و :max کیلوبایت باشد.',
        'numeric' => ':attribute باید بین :min و :max باشد.',
        'string'  => ':attribute باید بین :min و :max نویسه باشد.',
    ],
    'boolean'              => 'فیلد :attribute باید true یا false باشد.',
    'can'                  => 'فیلد :attribute شامل مقداری غیرمجاز است.',
    'confirmed'            => ':attribute و تکرار آن یکسان نیستند.',
    'contains'             => 'یکی از مقادیر لازم برای :attribute وارد نشده است.',
    'current_password'     => 'رمز عبور اشتباه است.',
    'date'                 => ':attribute یک تاریخ معتبر نیست.',
    'date_equals'          => ':attribute باید تاریخی برابر با :date باشد.',
    'date_format'          => ':attribute با قالب :format مطابقت ندارد.',
    'decimal'              => ':attribute باید دارای :decimal رقم اعشار باشد.',
    'declined'             => ':attribute باید رد شود.',
    'declined_if'          => 'هنگامی که :other برابر :value است، :attribute باید رد شود.',
    'different'            => ':attribute و :other باید متفاوت باشند.',
    'digits'               => ':attribute باید :digits رقم باشد.',
    'digits_between'       => ':attribute باید بین :min و :max رقم باشد.',
    'dimensions'           => 'ابعاد تصویر :attribute نامعتبر است.',
    'distinct'             => 'فیلد :attribute دارای یک مقدار تکراری است.',
    'doesnt_end_with'      => ':attribute نباید با یکی از این موارد پایان یابد: :values.',
    'doesnt_start_with'    => ':attribute نباید با یکی از این موارد شروع شود: :values.',
    'email'                => ':attribute باید یک آدرس ایمیل معتبر باشد.',
    'ends_with'            => ':attribute باید با یکی از این موارد پایان یابد: :values.',
    'enum'                 => ':attribute انتخاب‌شده نامعتبر است.',
    'exists'               => ':attribute انتخاب‌شده نامعتبر است.',
    'extensions'           => 'فیلد :attribute باید یکی از پسوندهای زیر را داشته باشد: :values.',
    'file'                 => ':attribute باید یک فایل باشد.',
    'filled'               => 'فیلد :attribute باید مقدار داشته باشد.',
    'gt'                   => [
        'array'   => ':attribute باید بیشتر از :value مورد داشته باشد.',
        'file'    => ':attribute باید بزرگ‌تر از :value کیلوبایت باشد.',
        'numeric' => ':attribute باید بزرگ‌تر از :value باشد.',
        'string'  => ':attribute باید بیشتر از :value نویسه باشد.',
    ],
    'gte'                  => [
        'array'   => ':attribute باید :value مورد یا بیشتر داشته باشد.',
        'file'    => ':attribute باید بزرگ‌تر یا مساوی :value کیلوبایت باشد.',
        'numeric' => ':attribute باید بزرگ‌تر یا مساوی :value باشد.',
        'string'  => ':attribute باید بیشتر یا مساوی :value نویسه باشد.',
    ],
    'hex_color'            => 'فیلد :attribute باید یک رنگ هگزادسیمال معتبر باشد.',
    'image'                => ':attribute باید یک تصویر باشد.',
    'in'                   => ':attribute انتخاب‌شده نامعتبر است.',
    'in_array'             => 'فیلد :attribute در :other وجود ندارد.',
    'integer'              => ':attribute باید یک عدد صحیح باشد.',
    'ip'                   => ':attribute باید یک آدرس IP معتبر باشد.',
    'ipv4'                 => ':attribute باید یک آدرس IPv4 معتبر باشد.',
    'ipv6'                 => ':attribute باید یک آدرس IPv6 معتبر باشد.',
    'json'                 => ':attribute باید یک رشته JSON معتبر باشد.',
    'list'                 => 'فیلد :attribute باید یک لیست باشد.',
    'lowercase'            => ':attribute باید با حروف کوچک باشد.',
    'lt'                   => [
        'array'   => ':attribute باید کمتر از :value مورد داشته باشد.',
        'file'    => ':attribute باید کوچک‌تر از :value کیلوبایت باشد.',
        'numeric' => ':attribute باید کوچک‌تر از :value باشد.',
        'string'  => ':attribute باید کمتر از :value نویسه باشد.',
    ],
    'lte'                  => [
        'array'   => ':attribute نباید بیشتر از :value مورد داشته باشد.',
        'file'    => ':attribute باید کوچک‌تر یا مساوی :value کیلوبایت باشد.',
        'numeric' => ':attribute باید کوچک‌تر یا مساوی :value باشد.',
        'string'  => ':attribute باید کمتر یا مساوی :value نویسه باشد.',
    ],
    'mac_address'          => ':attribute باید یک مک آدرس معتبر باشد.',
    'max'                  => [
        'array'   => ':attribute نباید بیشتر از :max مورد داشته باشد.',
        'file'    => ':attribute نباید بزرگ‌تر از :max کیلوبایت باشد.',
        'numeric' => ':attribute نباید بزرگ‌تر از :max باشد.',
        'string'  => ':attribute نباید بیشتر از :max نویسه باشد.',
    ],
    'max_digits'           => ':attribute نباید بیشتر از :max رقم داشته باشد.',
    'mimes'                => ':attribute باید فایلی از نوع: :values باشد.',
    'mimetypes'            => ':attribute باید فایلی از نوع: :values باشد.',
    'min'                  => [
        'array'   => ':attribute باید حداقل :min مورد داشته باشد.',
        'file'    => ':attribute باید حداقل :min کیلوبایت باشد.',
        'numeric' => ':attribute باید حداقل :min باشد.',
        'string'  => ':attribute باید حداقل :min نویسه باشد.',
    ],
    'min_digits'           => ':attribute باید حداقل :min رقم داشته باشد.',
    'missing'              => 'فیلد :attribute نباید وجود داشته باشد.',
    'missing_if'           => 'هنگامی که :other برابر :value است، فیلد :attribute نباید وجود داشته باشد.',
    'missing_unless'       => 'فیلد :attribute نباید وجود داشته باشد مگر اینکه :other برابر :value باشد.',
    'missing_with'         => 'اگر یکی از :values در اطلاعات ارسالی وجود دارد، فیلد :attribute نباید ارسال شود.',
    'missing_with_all'     => 'اگر همهٔ :values در اطلاعات ارسالی وجود دارند، فیلد :attribute نباید ارسال شود.',
    'multiple_of'          => ':attribute باید مضربی از :value باشد.',
    'not_in'               => ':attribute انتخاب‌شده نامعتبر است.',
    'not_regex'            => 'قالب :attribute نامعتبر است.',
    'numeric'              => ':attribute باید یک عدد باشد.',
    'password'             => [
        'letters'       => ':attribute باید شامل حداقل یک حرف باشد.',
        'mixed'         => ':attribute باید شامل حداقل یک حرف بزرگ و یک حرف کوچک باشد.',
        'numbers'       => ':attribute باید شامل حداقل یک عدد باشد.',
        'symbols'       => ':attribute باید حداقل یک نشانه مانند ! یا @ داشته باشد.',
        'uncompromised' => ':attribute در اطلاعات افشاشده پیدا شده است. لطفاً :attribute دیگری انتخاب کنید.',
    ],
    'present'              => 'فیلد :attribute در اطلاعات ارسالی وجود ندارد.',
    'present_if'           => 'وقتی :other برابر :value است، فیلد :attribute باید در اطلاعات ارسالی وجود داشته باشد.',
    'present_unless'       => 'فیلد :attribute باید در اطلاعات ارسالی وجود داشته باشد، مگر اینکه :other برابر :value باشد.',
    'present_with'         => 'اگر یکی از :values وجود دارد، فیلد :attribute نیز باید در اطلاعات ارسالی وجود داشته باشد.',
    'present_with_all'     => 'اگر همهٔ :values وجود دارند، فیلد :attribute نیز باید در اطلاعات ارسالی وجود داشته باشد.',
    'prohibited'           => 'فیلد :attribute ممنوع است.',
    'prohibited_if'        => 'هنگامی که :other برابر :value است، فیلد :attribute ممنوع است.',
    'prohibited_unless'    => 'فیلد :attribute ممنوع است مگر اینکه :other در :values باشد.',
    'prohibits'            => 'فیلد :attribute حضور :other را ممنوع می‌کند.',
    'regex'                => 'قالب :attribute نامعتبر است.',
    'required'             => 'فیلد :attribute الزامی است.',
    'required_array_keys'  => 'فیلد :attribute باید شامل ورودی‌هایی برای: :values باشد.',
    'required_if'          => 'هنگامی که :other برابر :value است، فیلد :attribute الزامی است.',
    'required_if_accepted' => 'هنگامی که :other پذیرفته شده است، فیلد :attribute الزامی است.',
    'required_if_declined' => 'هنگامی که :other رد شده است، فیلد :attribute الزامی است.',
    'required_unless'      => 'فیلد :attribute الزامی است مگر اینکه :other در :values باشد.',
    'required_with'        => 'اگر یکی از :values تکمیل شده باشد، :attribute نیز الزامی است.',
    'required_with_all'    => 'اگر همهٔ :values تکمیل شده باشند، :attribute نیز الزامی است.',
    'required_without'     => 'اگر یکی از :values خالی باشد، :attribute الزامی است.',
    'required_without_all' => 'اگر همهٔ :values خالی باشند، :attribute الزامی است.',
    'same'                 => ':attribute و :other باید با هم مطابقت داشته باشند.',
    'size'                 => [
        'array'   => ':attribute باید شامل :size مورد باشد.',
        'file'    => ':attribute باید :size کیلوبایت باشد.',
        'numeric' => ':attribute باید :size باشد.',
        'string'  => ':attribute باید :size نویسه باشد.',
    ],
    'starts_with'          => ':attribute باید با یکی از این موارد شروع شود: :values.',
    'string'               => ':attribute باید یک رشته متنی باشد.',
    'timezone'             => ':attribute باید یک منطقه زمانی معتبر باشد.',
    'unique'               => ':attribute قبلاً ثبت شده است.',
    'uploaded'             => 'بارگذاری :attribute انجام نشد. دوباره تلاش کنید.',
    'uppercase'            => ':attribute باید با حروف بزرگ باشد.',
    'url'                  => ':attribute باید یک آدرس اینترنتی معتبر باشد.',
    'ulid'                 => ':attribute باید یک ULID معتبر باشد.',
    'uuid'                 => ':attribute باید یک UUID معتبر باشد.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
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
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'files.*' => 'فایل',
    ],

];
