@php
    $tabs = [
        ['id' => 'roles', 'icon' => 'groups', 'label' => 'نقش‌ها'],
        ['id' => 'done', 'icon' => 'task_alt', 'label' => 'انجام‌شده', 'sub' => 'move'],
        ['id' => 'project', 'icon' => 'workspaces', 'label' => 'پروژه', 'sub' => 'board'],
        ['id' => 'filters', 'icon' => 'filter_alt', 'label' => 'فیلترها', 'sub' => 'basic'],
        ['id' => 'card', 'icon' => 'contact_page', 'label' => 'کارت'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات', 'sub' => 'talk'],
    ];

    $projectRows = [
        ['icon' => 'drag_indicator', 'color' => 'primary', 'label' => 'جابه‌جایی کارت‌ها', 'text' => 'برای تغییر وضعیت، کارت را به ستون مورد نظر بکشید. برای تغییر ترتیب در یک ستون، آن را میان کارت‌های دیگر قرار دهید.'],
        ['icon' => 'workspaces', 'color' => 'tertiary', 'label' => 'اتصال به پروژه', 'text' => 'نام پروژه روی کارت، فضای کاری همان پروژه را باز می‌کند. با اتصال وظیفه به پروژه، افراد مجاز آن پروژه هم وظیفه و گفتگوی آن را می‌بینند. نام نمایشی پروژه در «اطلاعات تکمیلی» فقط یک متن است و چنین اتصالی ایجاد نمی‌کند.'],
        ['icon' => 'sell', 'color' => 'secondary', 'label' => 'اولویت و مراحل کار', 'text' => 'با زدن نشان اولویت، آن را از کم تا فوری تغییر دهید؛ جای کارت هم متناسب با اولویت عوض می‌شود. در چک‌لیست، مراحل را بنویسید و انجام‌شده‌ها را تیک بزنید. افزودن یا حذف مرحله، سهم مراحل را برابر می‌کند؛ درصد هرکدام را هم می‌توانید تغییر دهید. تیک زدن همهٔ مراحل، کارت را به «انجام‌شده» نمی‌برد؛ این جابه‌جایی را خودتان انجام دهید.'],
        ['icon' => 'verified', 'color' => 'primary', 'label' => 'چه وقت تأیید لازم است؟', 'text' => 'اگر پروژه تأیید انجام کار را لازم کرده باشد، کارِ انجام‌شده توسط اعضا منتظر تأیید مدیر پروژه می‌ماند. در نبود مدیر فعال، مسئول پیگیری می‌تواند تأیید کند. کارِ انجام‌شده توسط خود مدیر، خودکار تأیید می‌شود. پس از تأیید، کاربران عادی نمی‌توانند وظیفه را تغییر دهند؛ گفتگو و آرشیو با دسترسی لازم همچنان ممکن است. مدیر با «بازگشایی وظیفه»، کار را به «در حال انجام» برمی‌گرداند و تأیید قبلی برداشته می‌شود. وظیفهٔ متصل به مرحلهٔ چرخه، با این دکمه باز نمی‌شود.'],
        ['icon' => 'event_busy', 'color' => 'tertiary', 'label' => 'مهلت کار در پروژه', 'text' => 'مهلت وظیفه نباید پس از مهلت نهایی پروژه باشد. برای وظیفهٔ تازه یا هنگام تغییر پروژه، مهلت خالی با توجه به زمان مجاز انجام کار در پروژه تعیین می‌شود. نشان «عبور از مهلت» یعنی مهلت یا مدت مجاز این کار گذشته است؛ دلیل دقیق را در توضیح نشان ببینید. اصلاح مهلت یا وضعیت، علت هشدار را برطرف می‌کند.'],
        ['icon' => 'tune', 'color' => 'secondary', 'label' => 'اطلاعات دلخواه وظیفه', 'text' => 'این زبانه برای اطلاعات اضافه، مثل شمارهٔ پیگیری است. اگر پروژه قسمت‌های مشخصی تعریف کرده، همان‌ها را پر کنید؛ وگرنه نام و متن دلخواه اضافه کنید. نام را با حروف کوچک انگلیسی، رقم و زیرخط (_) بنویسید. اگر نیازی ندارید، این بخش را خالی بگذارید.'],
        ['icon' => 'admin_panel_settings', 'color' => 'primary', 'label' => 'اختیارات مدیر پروژه', 'text' => 'مدیر پروژه می‌تواند وظایف پروژه را مدیریت کند: مسئول انجام را عوض کند، کار را تأیید یا دوباره باز کند و با رعایت محدودیت‌های تیکت و چرخه، آن را حذف کند. دسترسی اعضا محدودتر است؛ دکمه‌های مجاز هر کارت نمایش داده می‌شوند.'],
        ['icon' => 'conversion_path', 'color' => 'tertiary', 'label' => 'وظیفهٔ مربوط به چرخهٔ کاری', 'text' => 'وظیفهٔ مرحلهٔ فعلی یک چرخهٔ فعال را نمی‌توان حذف کرد یا به پروژهٔ دیگری برد. ابتدا مرحله را پیش ببرید یا چرخه را لغو کنید. نشان چرخه روی کارت، همان چرخه را باز می‌کند. تغییر مسئول انجام یا همکاران این وظیفه، افراد همان مرحله را هم تغییر می‌دهد.'],
        ['icon' => 'sell', 'color' => 'secondary', 'label' => 'فهرست برچسب پروژه', 'text' => 'اگر پروژه فهرست برچسبِ مجاز داشته باشد، فقط از همان‌ها می‌شود برچسب تازه گذاشت (برچسب‌های قدیمیِ وظیفه می‌مانند). فهرست خالی یعنی برچسب آزاد.'],
    ];

    $roleRows = [
        'creator' => [
            'note' => 'شما این وظیفه را ثبت کرده‌اید؛ انجام آن ممکن است با خودتان یا فرد دیگری باشد.',
            ['icon' => 'visibility', 'color' => 'primary', 'label' => 'کجا پیدایش کنید؟', 'text' => 'کارهایی که انجامشان با شماست، در «وظایف من» هستند؛ کارهایی که به دیگری سپرده‌اید در «محول شده» دیده می‌شوند. وظایفی که همکار آن‌ها هستید نیز در «وظایف من» می‌آیند.'],
            ['icon' => 'bolt', 'color' => 'tertiary', 'label' => 'چه کاری می‌توانید بکنید؟', 'text' => 'تا وقتی انجام کار با شماست، متن و وضعیت آن را تغییر دهید. پس از سپردن کار به دیگری، همچنان می‌توانید پیام بدهید و با داشتن دسترسی لازم واگذاری را لغو یا وظیفه را حذف کنید. مدیر پروژه و همکاران نیز اختیارات خود را دارند.'],
            ['icon' => 'notifications', 'color' => 'secondary', 'label' => 'اعلان', 'text' => 'تا وقتی وظیفه به دیگری واگذار نشده و در ستون «انجام نشده» است، نشانگر کارتابل شما روشن می‌ماند. پس از واگذاری، مسئول انجام نشانگر و اعلان تغییرات را دریافت می‌کند؛ شما با پاسخ تازهٔ او اعلان دریافت می‌کنید.'],
        ],
        'assignee' => [
            'note' => 'انجام این کار به شما سپرده شده است.',
            ['icon' => 'visibility', 'color' => 'primary', 'label' => 'می‌بینید', 'text' => 'وظیفه‌ای که به شما محول شده، در تب «وظایف من» شما دیده می‌شود؛ تب «محول شده» فقط برای کسی است که خودش وظیفه را به دیگری واگذار کرده.'],
            ['icon' => 'bolt', 'color' => 'tertiary', 'label' => 'اقدام شما', 'text' => 'وضعیت وظیفه (در حال انجام / انجام‌شده) را خودتان تغییر می‌دهید و می‌توانید در گفتگوی وظیفه با ایجادکننده پیام رد و بدل کنید.'],
            ['icon' => 'notifications', 'color' => 'secondary', 'label' => 'اعلان', 'text' => 'نشان کارتابل تا وقتی وظیفهٔ محول‌شده به شما در ستون «انجام نشده» باشد روشن می‌ماند؛ زنگوله با هر پیام تازهٔ ایجادکننده.'],
        ],
    ];

    $doneRows = [
        ['icon' => 'task_alt', 'color' => 'secondary', 'label' => 'پیش از ثبت انجام کار', 'text' => 'وظیفه را باز کنید و در «اقدام و پیگیری»، نتیجهٔ کار را انتخاب و ذخیره کنید: تمدید، توقف یا تکمیل. سپس کارت را به «انجام‌شده» ببرید. در انتقال گروهی هم نتیجهٔ کار لازم است. «توقف» هشدارهای تأخیر و فوریت را خاموش می‌کند.'],
        ['icon' => 'history', 'color' => 'primary', 'label' => 'دیدن کارهای قدیمی‌تر', 'text' => 'ستون «انجام‌شده» به‌طور معمول کارهای تغییرکرده در ۴۵ روز اخیر را نشان می‌دهد. برای دیدن همهٔ موارد قدیمی‌تر، دکمهٔ تاریخ بالای همان ستون را بزنید. پنهان شدن کارت به معنی حذف آن نیست.'],
        ['icon' => 'archive', 'color' => 'tertiary', 'label' => 'آرشیو با حذف فرق دارد', 'text' => 'آرشیو، کارت انجام‌شده را از فهرست فعال کنار می‌گذارد و اطلاعاتش را نگه می‌دارد. دکمهٔ آرشیو بالای ستون، این کارت‌ها را نشان می‌دهد؛ با «خروج از آرشیو» کارت را برگردانید. وظیفهٔ منتظر تأیید را نمی‌توان آرشیو کرد.'],
        ['icon' => 'schedule', 'color' => 'secondary', 'label' => 'آرشیو خودکار شبانه', 'text' => 'وظایف انجام‌شده و تأییدشده، پس از مدتی بدون تغییر، شبانه آرشیو می‌شوند. پیش‌فرض ۴۵ روز از تأیید یا آخرین تغییر است؛ هرکدام دیرتر باشد. پروژه می‌تواند این مدت را عوض کند. تعداد کارت‌های آرشیو کنار دکمهٔ آرشیو بالای ستون دیده می‌شود.'],
        ['icon' => 'delete', 'color' => 'error', 'label' => 'حذف وظیفه', 'text' => 'ایجادکننده یا مدیر پروژه، با داشتن دسترسی لازم، وظیفه را حذف می‌کند. برای بازیابی از پنل مدیریت اقدام کنید. وظیفهٔ بدون پروژه، پس از ۳۰ روز از حذف، برای همیشه پاک می‌شود. وظایف صرفاً به دلیل قدیمی یا انجام‌شده بودن پاک نمی‌شوند.'],
    ];

    $filtersRows = [
        ['icon' => 'search', 'color' => 'primary', 'label' => 'جستجو در وظایف قدیمی و بایگانی‌شده', 'text' => 'جستجو در عنوان و توضیحات انجام می‌شود و موارد بایگانی‌شده و انجام‌شده‌های قدیمی‌تر از ' . convertToPersian('45') . ' روز را نیز در بر می‌گیرد. هنگام جستجو، محدودیت پیش‌فرض تاریخ و فیلتر آرشیو موقتاً کنار گذاشته می‌شوند.'],
        ['icon' => 'tune', 'color' => 'secondary', 'label' => 'چند شرط هم‌زمان', 'text' => 'در پنل جستجو و فیلتر، چند شرط را با هم انتخاب کنید؛ مثلاً کارهای فوری یک پروژه با مهلت امروز. فقط کارهایی می‌مانند که همهٔ شرط‌ها را دارند.'],
        ['icon' => 'person_check', 'color' => 'tertiary', 'label' => 'پیدا کردن کارهای یک نفر', 'text' => 'در فهرست «محول شده»، با فیلتر مسئول انجام، کارهایی را که به یک نفر سپرده‌اید پیدا کنید. این فیلتر فهرست فعلی شما را محدود می‌کند و کارهای همهٔ سازمان را نشان نمی‌دهد.'],
        ['icon' => 'hub', 'color' => 'primary', 'label' => 'پیدا کردن منبع درخواست', 'text' => 'ابتدا دستهٔ منبع درخواست، مثلاً «جلسه»، را انتخاب کنید؛ سپس فهرست منبع درخواست به موارد همان دسته محدود می‌شود. عوض کردن دسته، انتخاب قبلی منبع را پاک می‌کند.'],
        ['icon' => 'sell', 'color' => 'secondary', 'label' => 'انتخاب چند برچسب', 'text' => 'اگر چند برچسب انتخاب کنید، فقط وظایفی نمایش داده می‌شوند که همهٔ آن برچسب‌ها را دارند. در فرم وظیفه، برچسب تازهٔ پروژه ممکن است به فهرست مجاز مدیر پروژه محدود باشد.'],
        ['icon' => 'label', 'color' => 'tertiary', 'label' => 'کلیک روی برچسب = فیلتر', 'text' => 'کلیک روی هر برچسبِ روی کارت، فهرست را بلافاصله بر همان برچسب فیلتر می‌کند؛ بدون بازکردن نوار فیلترها.'],
        ['icon' => 'assignment_turned_in', 'color' => 'tertiary', 'label' => 'گزارش تسک‌شیت و فایل Excel', 'text' => 'تسک‌شیت، گزارش عملکرد شما را برای بازهٔ دلخواه در صفحه‌ای تازه باز می‌کند؛ از همان‌جا می‌توانید گزارش را برای مدیر یا کاربر دیگری بفرستید. دکمهٔ خروجی روی برد، فایل Excel همهٔ وظایف مرتبط با شما را می‌گیرد و به جستجو یا فیلتر فعلی محدود نیست.'],
        ['icon' => 'unfold_less', 'color' => 'primary', 'label' => 'پیدا کردن ابزارها', 'text' => 'برای ساخت وظیفه، «+» بالای ستون «انجام‌نشده» را بزنید. ابزارهای نمایش، انتخاب گروهی و خروجی بالای برد هستند. جستجو و فیلترها از دکمهٔ شناور باز می‌شوند. اگر ابزارها پنهان‌اند، دکمهٔ روشن بالای برد را بزنید تا دوباره دیده شوند.'],
        ['icon' => 'swap_vert', 'color' => 'secondary', 'label' => 'ترتیب و ظاهر کارت‌ها', 'text' => 'مرتب‌سازی روی همهٔ ستون‌ها اعمال می‌شود. برای برگشت به ترتیب دستی کارت‌ها، «بازنشانی ترتیب» را بزنید. فیلتر تاریخ، زمان ثبت یا آخرین تغییر وظیفه را بررسی می‌کند. «بخش‌های کارت» فقط اطلاعات روی کارت را در همین مرورگر پنهان می‌کند؛ چیزی از وظیفه حذف نمی‌شود.'],
    ];

    $cardRows = [
        ['icon' => 'group', 'color' => 'tertiary', 'label' => 'همکاران روی کارت', 'text' => 'تصویر همکاران پایین کارت دیده می‌شود. همکار می‌تواند پیام بدهد و متن یا وضعیت کار را تغییر دهد؛ اختیار تغییر مسئول انجام، انتقال پروژه و حذف جداگانه بررسی می‌شود. وظیفهٔ تأییدشده ممکن است برای او قفل باشد.'],
        ['icon' => 'badge', 'color' => 'secondary', 'label' => 'مسئول پیگیری و بخش سازمانی', 'text' => 'این نشان‌ها نام مسئول پیگیری و بخش سازمانی را مشخص می‌کنند. مسئول پیگیری با مسئول انجام فرق دارد؛ او می‌تواند وظیفه و گفتگو را ببیند و نتیجه را دنبال کند. نام کامل و زیرواحدها در توضیح نشان دیده می‌شوند.'],
        ['icon' => 'push_pin', 'color' => 'primary', 'label' => 'کارت سنجاق‌شده', 'text' => 'کارت سنجاق‌شده خودکار به بالای همان ستون می‌رود تا همیشه در دید باشد.'],
        ['icon' => 'palette', 'color' => 'primary', 'label' => 'رنگ شخصی کارت', 'text' => 'از دکمهٔ رنگ پایین کارت استفاده کنید تا آن را راحت‌تر پیدا کنید. رنگ در همین مرورگر ذخیره می‌شود و وضعیت کار را عوض نمی‌کند. بعضی کارت‌های دارای هشدار یا آرشیوشده، رنگ وضعیت خود را نگه می‌دارند.'],
        ['icon' => 'bolt', 'color' => 'error', 'label' => 'نوار فوریت کناری', 'text' => 'اولویت بالا/فوری نوار فوریتِ کناری کارت را روشن می‌کند، حتی بدون ضرب‌الاجل؛ کارت تازه در جایگاه اولویت خودش می‌نشیند، نه لزوماً بالای ستون.'],
        ['icon' => 'forum', 'color' => 'secondary', 'label' => 'شمارندهٔ گفتگو و پیوست', 'text' => 'شمارندهٔ گفتگو و پیوست (کنار شمارهٔ وظیفه) تعداد را بدون بازکردن کارت نشان می‌دهد؛ نگه‌داشتن اشاره‌گر روی شمارندهٔ پیوست فهرستشان را باز می‌کند (تصویر در پنجرهٔ گالری و فایل در زبانهٔ جدید).'],
        ['icon' => 'donut_large', 'color' => 'tertiary', 'label' => 'پیش‌نمایش چک‌لیست', 'text' => 'نگه‌داشتن اشاره‌گر روی حلقهٔ پیشرفتِ چک‌لیست، فهرست موارد را فقط‌خواندنی نشان می‌دهد؛ ویرایش همچنان داخل کارت است.'],
        ['icon' => 'event', 'color' => 'secondary', 'label' => 'نمایش تاریخ ضرب‌الاجل', 'text' => 'اگر ضرب‌الاجل در هفتهٔ آینده باشد، روی کارت فقط روزِ هفته و تاریخ کوتاه دیده می‌شود («شنبه ۵ شهریور») تا بدون شمردن معلوم شود؛ مهلت‌های دورتر تاریخ کامل با سال می‌گیرند. همین قالب در گزارش و پنل مدیریت هم یکسان است.'],
        ['icon' => 'travel_explore', 'color' => 'secondary', 'label' => 'منبع درخواست روی کارت', 'text' => 'اگر منبع درخواست ثبت شده باشد، این نشان روی کارت دیده می‌شود. توضیح نشان را بخوانید تا بدانید کار از کدام جلسه، نامه یا درخواست آمده است.'],
        ['icon' => 'alarm', 'color' => 'primary', 'label' => 'یادآوری وظیفه', 'text' => 'در پایین کارت، کنارِ سنجاق و پالت، یک آیکون زنگ هم هست که فقط با نگه‌داشتن اشاره‌گر روی کارت نمایان می‌شود؛ اگر برای آن وظیفه یادآوری تنظیم کرده باشید، همیشه با رنگ اصلی برنامه نمایان می‌ماند و با کلیک، همان یادآوری موجود برای ویرایش باز می‌شود.'],
    ];

    $cardSubTabs = [
        ['id' => 'chips', 'icon' => 'sell', 'label' => 'نشان‌ها و شمارنده‌ها', 'rows' => [$cardRows[1], $cardRows[5], $cardRows[6], $cardRows[7], $cardRows[8]]],
        ['id' => 'signals', 'icon' => 'bolt', 'label' => 'همکاران و فوریت', 'rows' => [$cardRows[0], $cardRows[4]]],
        ['id' => 'personal', 'icon' => 'palette', 'label' => 'سنجاق و رنگ', 'rows' => [$cardRows[2], $cardRows[3], $cardRows[9]]],
    ];

    $notes = [
        'در «نظرات»، ایجادکننده، مسئول انجام، همکاران و مسئول پیگیری می‌توانند گفتگو کنند. در وظیفهٔ پروژه، افراد مجاز پروژه هم دسترسی دارند. با پیام تازه، افراد مرتبط اعلان می‌گیرند؛ برای پیام خودتان اعلان نمی‌آید. تغییر مهلت یا برچسب، اعلان گفتگو نمی‌فرستد.',
        'همین قابلیت گفتگوی دوطرفه در تیکتینگ هم وجود دارد؛ برای جزئیات، راهنمای بخش تیکتینگ را ببینید.',
        'کارت «از تیکت» برای پیگیری درخواست پشتیبانی ساخته شده است. برای ویرایش یا گفتگو، خود تیکت را باز کنید؛ اولویت کارت هم از همان تیکت می‌آید. کارت انجام‌شده را با داشتن دسترسی لازم می‌توانید آرشیو کنید یا برگردانید.',
        'وقتی وظیفه‌ای را به فرد دیگری محول می‌کنید، نمای شما خودکار به زبانهٔ «محول شده» می‌رود. واگردانیِ محول‌کردن فقط توسط ایجادکننده و فقط وقتی وظیفه انجام‌شده نیست و از تیکت نیست ممکن است.',
        '«ساخت کپی»، وظیفهٔ تازه‌ای با مسئولیت انجام شما می‌سازد. اطلاعات، پیوست‌ها و مراحل کپی می‌شوند، اما تیک مراحل پاک می‌شود. افراد و برچسب‌های کپی باید با قواعد فعلی پروژه سازگار باشند. وظیفهٔ تیکت یا آرشیوشده را نمی‌توان کپی کرد.',
        'تغییرهای مهم، مانند مسئول انجام، مهلت، نتیجهٔ کار و اطلاعات دلخواه، با نام فرد و زمان تغییر ثبت می‌شوند. برای وظیفهٔ پروژه، آن‌ها را در «فعالیت‌ها و نظرات» پروژه ببینید؛ برای وظیفهٔ بدون پروژه، زبانهٔ «تاریخچه» را باز کنید.',
        '«تمرکز» ستون را برجسته می‌کند تا کارت‌هایش را راحت‌تر ببینید؛ برای ستون‌های دیگر، صفحه را افقی بکشید. با زدن دوبارهٔ دکمه یا کلید Esc برگردید. این انتخاب در همین مرورگر نگه داشته می‌شود.',
    ];

    $chipClasses = fn($c) => match ($c) {
        'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
        'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
        'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
        'error' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
    };
@endphp

<div x-data="{ tab: 'roles', role: 'creator', cardSub: 'chips', sub: 'move' }">
    <div class="flex p-1 mb-5 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
        @foreach($tabs as $tab)
            <button
                type="button"
                @click="tab = '{{ $tab['id'] }}'@if(!empty($tab['sub'])) ; sub = '{{ $tab['sub'] }}'@endif"
                :class="tab === '{{ $tab['id'] }}'
                    ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                    : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                class="flex-1 flex flex-col items-center justify-center gap-0.5 px-1.5 py-2 rounded-xl text-[11px] font-bold transition-all duration-200"
            >
                <span class="material-symbols-rounded text-[18px]">{{ $tab['icon'] }}</span>
                <span class="leading-tight text-center">{{ $tab['label'] }}</span>
            </button>
        @endforeach
    </div>

    <div x-show="tab === 'roles'" x-cloak>
        <div class="flex p-1 mb-4 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
            @foreach([
                ['id' => 'creator', 'icon' => 'person', 'label' => 'ایجادکننده'],
                ['id' => 'assignee', 'icon' => 'assignment_ind', 'label' => 'مسئول انجام'],
            ] as $role)
                <button
                    type="button"
                    @click="role = '{{ $role['id'] }}'"
                    :class="role === '{{ $role['id'] }}'
                        ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                        : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                    class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-[13px] font-bold transition-all duration-200"
                >
                    <span class="material-symbols-rounded text-[17px]">{{ $role['icon'] }}</span>
                    {{ $role['label'] }}
                </button>
            @endforeach
        </div>

        @foreach($roleRows as $roleId => $sections)
            <div x-show="role === '{{ $roleId }}'" x-cloak class="space-y-3">
                <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1">{{ $sections['note'] }}</p>
                @foreach(array_slice($sections, 1) as $s)
                    <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipClasses($s['color']) }}">
                            <span class="material-symbols-rounded text-[16px]">{{ $s['icon'] }}</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">{{ $s['label'] }}</p>
                            <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $s['text'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

    @php
        $groupedTabs = [
            ['id' => 'done', 'subs' => [
                ['id' => 'move', 'icon' => 'swap_horiz', 'label' => 'ورود و پنجرهٔ زمانی', 'rows' => [$doneRows[0], $doneRows[1]]],
                ['id' => 'archive', 'icon' => 'archive', 'label' => 'آرشیو و حذف', 'rows' => [$doneRows[2], $doneRows[3], $doneRows[4]]],
            ]],
            ['id' => 'project', 'subs' => [
                ['id' => 'board', 'icon' => 'view_kanban', 'label' => 'برد و کارت', 'rows' => [$projectRows[0], $projectRows[1], $projectRows[2], $projectRows[7]]],
                ['id' => 'rules', 'icon' => 'tune', 'label' => 'تنظیمات و قواعد پروژه', 'rows' => [$projectRows[3], $projectRows[4], $projectRows[6], $projectRows[8], $projectRows[5]]],
            ]],
            ['id' => 'filters', 'subs' => [
                ['id' => 'basic', 'icon' => 'search', 'label' => 'جستجو و ترکیب', 'rows' => [$filtersRows[0], $filtersRows[1], $filtersRows[8], $filtersRows[6], $filtersRows[7]]],
                ['id' => 'owners', 'icon' => 'person_check', 'label' => 'مسئول و منبع اقدام', 'rows' => [$filtersRows[2], $filtersRows[3]]],
                ['id' => 'labels', 'icon' => 'sell', 'label' => 'برچسب‌ها', 'rows' => [$filtersRows[4], $filtersRows[5]]],
            ]],
        ];

        $notesGroups = [
            ['id' => 'talk', 'icon' => 'forum', 'label' => 'گفتگو', 'rows' => [$notes[0], $notes[1]]],
            ['id' => 'flows', 'icon' => 'content_copy', 'label' => 'محول‌کردن و کپی', 'rows' => [$notes[2], $notes[3], $notes[4]]],
            ['id' => 'views', 'icon' => 'visibility', 'label' => 'فعالیت و نما', 'rows' => [$notes[5], $notes[6]]],
        ];
    @endphp
    @foreach($groupedTabs as $gt)
        <div x-show="tab === '{{ $gt['id'] }}'" x-cloak>
            <div class="flex p-1 mb-4 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
                @foreach($gt['subs'] as $gs)
                    <button
                        type="button"
                        @click="sub = '{{ $gs['id'] }}'"
                        :class="sub === '{{ $gs['id'] }}'
                            ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                            : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                        class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-[13px] font-bold transition-all duration-200"
                    >
                        <span class="material-symbols-rounded text-[17px]">{{ $gs['icon'] }}</span>
                        {{ $gs['label'] }}
                    </button>
                @endforeach
            </div>

            @foreach($gt['subs'] as $gs)
                <div x-show="sub === '{{ $gs['id'] }}'" x-cloak class="space-y-3">
                    @foreach($gs['rows'] as $s)
                        <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipClasses($s['color']) }}">
                                <span class="material-symbols-rounded text-[16px]">{{ $s['icon'] }}</span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">{{ $s['label'] }}</p>
                                <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $s['text'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endforeach

    <div x-show="tab === 'card'" x-cloak>
        <div class="flex p-1 mb-4 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
            @foreach($cardSubTabs as $sub)
                <button
                    type="button"
                    @click="cardSub = '{{ $sub['id'] }}'"
                    :class="cardSub === '{{ $sub['id'] }}'
                        ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                        : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                    class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-[13px] font-bold transition-all duration-200"
                >
                    <span class="material-symbols-rounded text-[17px]">{{ $sub['icon'] }}</span>
                    {{ $sub['label'] }}
                </button>
            @endforeach
        </div>

        @foreach($cardSubTabs as $sub)
            <div x-show="cardSub === '{{ $sub['id'] }}'" x-cloak class="space-y-3">
                @foreach($sub['rows'] as $s)
                    <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipClasses($s['color']) }}">
                            <span class="material-symbols-rounded text-[16px]">{{ $s['icon'] }}</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">{{ $s['label'] }}</p>
                            <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $s['text'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

    <div x-show="tab === 'notes'" x-cloak>
        <div class="flex p-1 mb-4 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
            @foreach($notesGroups as $ng)
                <button
                    type="button"
                    @click="sub = '{{ $ng['id'] }}'"
                    :class="sub === '{{ $ng['id'] }}'
                        ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                        : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                    class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-[13px] font-bold transition-all duration-200"
                >
                    <span class="material-symbols-rounded text-[17px]">{{ $ng['icon'] }}</span>
                    {{ $ng['label'] }}
                </button>
            @endforeach
        </div>

        @foreach($notesGroups as $ng)
            <div x-show="sub === '{{ $ng['id'] }}'" x-cloak class="space-y-2">
                @foreach($ng['rows'] as $note)
                    <div class="flex items-start gap-2 px-1">
                        <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">info</span>
                        <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $note }}</p>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</div>
