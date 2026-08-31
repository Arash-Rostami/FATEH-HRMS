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
        ['icon' => 'drag_indicator', 'color' => 'primary', 'label' => 'جابه‌جایی و ترتیب', 'text' => 'کارت‌ها را با کشیدن جابه‌جا کنید؛ ترتیب با یک رتبهٔ کسری بین دو همسایه ذخیره می‌شود، نه با شماره‌گذاری مجدد کل ستون. برای همین جابه‌جایی یک کارت هیچ کارت دیگری را در دیتابیس تغییر نمی‌دهد.'],
        ['icon' => 'workspaces', 'color' => 'tertiary', 'label' => 'اتصال به پروژه', 'text' => 'وظیفه‌ای که به پروژه متصل است، برچسب کوچک پروژه را روی کارت نشان می‌دهد؛ با زدن آن، بدون بارگذاری مجدد صفحه به فضای کاری همان پروژه می‌روید. فعالیت‌ها و نظرات، چت زندهٔ تیم و تقویم آن پروژه در همان‌جا کنار هم هستند — جدا از «گفتگوی وظیفه» خودِ این کارت که همچنان خصوصی می‌ماند.'],
        ['icon' => 'sell', 'color' => 'secondary', 'label' => 'برچسب، اولویت و زیروظیفه‌ها', 'text' => 'برچسب‌ها و سطح اولویت روی خود کارت دیده می‌شوند و در فیلترها قابل جست‌وجو هستند. با کلیک روی چیپِ اولویت، بدون بازکردن وظیفه، اولویت در چرخهٔ کم←متوسط←بالا←فوری←کم عوض می‌شود و جای کارت هم متناسب با آن در همان ستون به‌روز می‌شود. هر زیروظیفهٔ چک‌لیست یک سهم (٪) از پیشرفت کل دارد — با افزودن/حذف مورد به‌طور خودکار مساوی می‌شود. برای تغییر سهم، مستقیماً روی خودِ ردیف بکشید (مثل یک خط‌کش) یا عدد را دستی ویرایش کنید؛ برای تغییر متن، دکمهٔ مداد را بزنید. تیک‌زدن موارد هرگز خودکار وضعیت وظیفه را عوض نمی‌کند؛ فقط وقتی خودتان وظیفه را «انجام‌شده» می‌کنید، درصد روی کارت ۱۰۰٪ نشان داده می‌شود.'],
        ['icon' => 'verified', 'color' => 'primary', 'label' => 'تأیید انجام توسط مدیر پروژه', 'text' => 'اگر در تنظیمات پروژه «نیازمند تأیید مدیر» فعال باشد، وظیفه‌ای که به ستون «انجام‌شده» می‌رود، نهایی نمی‌شود بلکه حالت «منتظر تأیید» می‌گیرد و فقط مدیرِ همان پروژه می‌تواند با دکمهٔ تأیید روی کارت آن را نهایی کند (اگر خودِ مدیر پروژه وظیفه را انجام دهد، خودکار تأیید می‌شود). تا پیش از تأیید، زنگولهٔ مدیر با گذشت ۲۴ و ۴۸ ساعت یادآوری و سپس فوری می‌شود. در پروژه‌های بدون این تنظیم، رفتار همیشگی است و تأییدی در کار نیست. روی کارتِ منتظر تأیید، زیرنویسِ «آخرین تغییر» نام آخرین نویسندهٔ گفتگو و زمان آن را نشان می‌دهد تا بدانید آخرین بار چه کسی و کِی روی وظیفه کار کرده — جدا از مسئول انجامِ خودِ وظیفه.'],
        ['icon' => 'event_busy', 'color' => 'tertiary', 'label' => 'سقف مهلت و SLA پروژه', 'text' => 'مهلتی که در تنظیمات پروژه تعیین می‌شود، سقفِ ضرب‌الاجل همهٔ وظایف آن پروژه است: ثبت یا ویرایش وظیفه با مهلتی فراتر از آن رد می‌شود، لبهٔ کارت قرمز می‌شود و چیپِ «فراتر از مهلت پروژه» روی کارت می‌نشیند. اگر SLA (به ساعت) تنظیم شده باشد، وظیفه‌ای که بیش از آن مدت باز مانده باشد چیپِ «نقض SLA» و لبهٔ فوریت می‌گیرد — هر دو مثل چیپِ «رکود» دیده می‌شوند و با تغییر وضعیت یا رفع نقض، از کارت حذف می‌شوند. هر دو فقط هشدار و محدودیتِ همان پروژه‌اند و روی وظایف بدون پروژه اثری ندارند.'],
        ['icon' => 'tune', 'color' => 'secondary', 'label' => 'دیتای سفارشی وظیفه', 'text' => 'تب «دیتای سفارشی» در مودال وظیفه (و ستون دیتای سفارشی در گزارش): اگر پروژه‌ای فیلدهایی با برچسب تعریف کرده باشد، همان فیلدها با برچسب فارسی ظاهر می‌شوند؛ در غیر آن صورت — روی هر وظیفه‌ای، با پروژه یا بدون آن — کلید/مقدار آزاد می‌نویسید و کلیدِ خودش به‌عنوان برچسب روی چیپ کارت می‌نشیند. کلید فقط می‌تواند حروف کوچک انگلیسی، رقم و زیرخط باشد.'],
    ];

    $roleRows = [
        'creator' => [
            'note' => 'شما این وظیفه را ساخته‌اید؛ به خودتان یا به فرد دیگری محول شده باشد.',
            ['icon' => 'visibility', 'color' => 'primary', 'label' => 'می‌بینید', 'text' => 'تا وقتی وظیفه محول نشده، در تب «وظایف من» می‌مانَد؛ به‌محض این‌که آن را به فرد دیگری محول کنید، برای پیگیری به تب «محول شده» منتقل می‌شود.'],
            ['icon' => 'bolt', 'color' => 'tertiary', 'label' => 'اقدام شما', 'text' => 'اگر وظیفه را به فرد دیگری محول کرده‌اید، تغییر وضعیت با اوست و شما فقط می‌توانید در گفتگوی وظیفه پیام بدهید؛ اگر هنوز محول نشده، خودتان هم می‌توانید وضعیت را تغییر دهید.'],
            ['icon' => 'notifications', 'color' => 'secondary', 'label' => 'اعلان', 'text' => 'تا وقتی وظیفه محول نشده و در ستون «انجام نشده» است، نشان کارتابل شما روشن می‌ماند؛ پس از محول‌کردن، نشان و زنگولهٔ تغییرات به مسئول انجام منتقل می‌شود و برای شما فقط پاسخ‌های تازهٔ او زنگوله می‌زند.'],
        ],
        'assignee' => [
            'note' => 'وظیفه‌ای به شما محول شده است.',
            ['icon' => 'visibility', 'color' => 'primary', 'label' => 'می‌بینید', 'text' => 'وظیفه‌ای که به شما محول شده، در تب «وظایف من» شما دیده می‌شود؛ تب «محول شده» فقط برای کسی است که خودش وظیفه را به دیگری واگذار کرده.'],
            ['icon' => 'bolt', 'color' => 'tertiary', 'label' => 'اقدام شما', 'text' => 'وضعیت وظیفه (در حال انجام / انجام‌شده) را خودتان تغییر می‌دهید و می‌توانید در گفتگوی وظیفه با ایجادکننده پیام رد و بدل کنید.'],
            ['icon' => 'notifications', 'color' => 'secondary', 'label' => 'اعلان', 'text' => 'نشان کارتابل تا وقتی وظیفهٔ محول‌شده به شما در ستون «انجام نشده» باشد روشن می‌ماند؛ زنگوله با هر پیام تازهٔ ایجادکننده.'],
        ],
    ];

    $doneRows = [
        ['icon' => 'task_alt', 'color' => 'secondary', 'label' => 'تعیین تکلیف پیش‌نیاز انجام‌شده است', 'text' => 'تا وقتی فیلد «تعیین تکلیف» (تمدید/توقف/تکمیل) در تب «اقدام و پیگیری» مودال مشخص نشده، وظیفه به ستون «انجام‌شده» منتقل نمی‌شود — نه با کشیدن روی ستون، نه با جابه‌جایی بین کارت‌های همان ستون، نه با انتقال گروهی؛ در انتقال گروهی، فقط وظایف بدون تعیین‌تکلیف رد می‌شوند و بقیه طبق معمول منتقل می‌شوند.'],
        ['icon' => 'history', 'color' => 'primary', 'label' => 'پنجرهٔ ۴۵ روزه', 'text' => 'وظایف انجام‌شده‌ای که بیش از ۴۵ روز از آخرین تغییرشان می‌گذرد، به‌طور پیش‌فرض از کارتابل پنهان می‌شوند تا ستون شلوغ نشود؛ با دکمهٔ «تاریخ» در سرستون، همهٔ موارد قدیمی‌تر را ببینید. این فقط یک فیلتر نمایش است و چیزی حذف نمی‌شود.'],
        ['icon' => 'archive', 'color' => 'tertiary', 'label' => 'آرشیو', 'text' => 'با دکمهٔ آرشیو روی هر کارت انجام‌شده، آن را به‌اختیار از فهرست فعال پنهان کنید؛ با دکمهٔ آرشیو در سرستون، فقط موارد آرشیو‌شده را ببینید و با «خروج از آرشیو» آن‌ها را برگردانید. باز کردن دوباره یا جابه‌جایی یک وظیفهٔ آرشیو‌شده، خودکار آن را از آرشیو خارج می‌کند. آرشیو چیزی را حذف نمی‌کند.'],
        ['icon' => 'schedule', 'color' => 'secondary', 'label' => 'آرشیو خودکار شبانه', 'text' => 'وظیفهٔ انجام‌شده و تأییدشده‌ای که مدتی (پیش‌فرض ۴۵ روز، قابل تنظیم در پروژه) از تأییدش گذشته، خودکار شبانه آرشیو می‌شود. عددِ کنار دکمهٔ آرشیو در سرستون همیشه تعداد همین موارد را نشان می‌دهد — حتی صفر — با کلیک روی آن وارد آرشیو شوید و با «خروج از آرشیو» هرکدام را برگردانید؛ چیزی حذف نمی‌شود.'],
        ['icon' => 'delete', 'color' => 'error', 'label' => 'حذف و پاک‌سازی خودکار', 'text' => 'دکمهٔ سطل زباله (فقط برای ایجادکننده) یک حذف نرم است؛ ردیف ۳۰ روز در سطل زباله می‌ماند و سپس برای همیشه پاک می‌شود. هیچ وظیفهٔ انجام‌شده‌ای که خودتان حذف نکرده‌اید، به‌مرور زمان حذف نمی‌شود.'],
    ];

    $filtersRows = [
        ['icon' => 'search', 'color' => 'primary', 'label' => 'جستجو پنجره‌ها را باز می‌کند', 'text' => 'جستجو در عنوان و توضیحات، صفحه‌بندی را ریست و موقتاً پنجرهٔ ' . convertToPersian('45') . ' روزه و فیلتر آرشیو را غیرفعال می‌کند؛ نتایج شامل آرشیوها و انجام‌شده‌های قدیمی هم می‌شود.'],
        ['icon' => 'tune', 'color' => 'secondary', 'label' => 'ترکیب فیلترها', 'text' => 'فیلترهای ضرب‌الاجل، پروژه، اولویت، برچسب، طرح، واحد و بخش همزمان قابل ترکیب‌اند؛ در موبایل زیر «فیلترهای بیشتر» جمع می‌شوند.'],
        ['icon' => 'person_check', 'color' => 'tertiary', 'label' => 'فیلتر مسئول انجام', 'text' => 'فقط وظایف محول‌شده از/به شما را نشان می‌دهد؛ در «وظایف من» همیشه خودتان هستید، پس فقط در «محول شده» معنی پیدا می‌کند.'],
        ['icon' => 'hub', 'color' => 'primary', 'label' => 'زنجیرهٔ منبع اقدام', 'text' => 'حوزه و منبع اقدام زنجیره‌ای‌اند: انتخاب حوزه، فهرست منبع را به همان حوزه محدود و انتخاب قبلی را پاک می‌کند.'],
        ['icon' => 'sell', 'color' => 'secondary', 'label' => 'برچسب چندانتخابی', 'text' => 'برچسب‌ها چندانتخابی‌اند؛ فهرست فقط وظایفی با همهٔ برچسب‌های انتخاب‌شده را نشان می‌دهد (همه، نه هرکدام). افزودن برچسب تازه همیشه ممکن است و پیشنهادیِ برچسب‌های قبلیِ شما زیر ورودی ظاهر می‌شود.'],
        ['icon' => 'label', 'color' => 'tertiary', 'label' => 'کلیک روی برچسب = فیلتر', 'text' => 'کلیک روی هر برچسبِ روی کارت، فهرست را بلافاصله بر همان برچسب فیلتر می‌کند؛ بدون بازکردن نوار فیلترها.'],
        ['icon' => 'assignment_turned_in', 'color' => 'tertiary', 'label' => 'گزارش تسک‌شیت', 'text' => 'دکمهٔ کنار فیلترها در نوار بالای برد، گزارش عملکرد شخصی شما را برای بازهٔ تاریخی دلخواه در تب جدید باز می‌کند؛ همان گزارش با یک اقدام از داخلش برای مدیرتان هم قابل اشتراک‌گذاری است.'],
    ];

    $cardRows = [
        ['icon' => 'group', 'color' => 'tertiary', 'label' => 'همکاران روی کارت', 'text' => 'همکاران به‌صورت آواتار کوچک (۳ نفر + شمارنده) در پایین کارت؛ فقط گفتگو می‌توانند، نه ویرایش یا تغییر وضعیت.'],
        ['icon' => 'badge', 'color' => 'secondary', 'label' => 'چیپ جوابگو و دپارتمان', 'text' => 'چیپ «جوابگو» و «دپارتمان» روی کارت؛ نگه‌داشتن اشاره‌گر نام کامل و — اگر دارد — «واحد» و «بخش» را زیر نام دپارتمان نشان می‌دهد و فیلترهای بیشتر برد را بر همان اساس محدود می‌کند.'],
        ['icon' => 'push_pin', 'color' => 'primary', 'label' => 'کارت سنجاق‌شده', 'text' => 'کارت سنجاق‌شده خودکار به بالای همان ستون می‌رود تا همیشه در دید باشد.'],
        ['icon' => 'palette', 'color' => 'primary', 'label' => 'رنگ‌آمیزی شخصی کارت', 'text' => 'با دکمهٔ پالت در پایین کارت، برای خودتان یک رنگ ثابت انتخاب کنید تا کارت سریع‌تر پیدا شود؛ رنگ فقط برای شما و روی همین مرورگر ذخیره می‌شود و با کانال/تماس هم‌رنگ است. کارت‌های فوری، در انتظار واگذاری و آرشیوشده رنگ ثابت نمی‌گیرند و رنگ سیستمی خود را نگه می‌دارند.'],
        ['icon' => 'bolt', 'color' => 'error', 'label' => 'نوار فوریت کناری', 'text' => 'اولویت بالا/فوری نوار فوریتِ کناری کارت را روشن می‌کند، حتی بدون ضرب‌الاجل؛ کارت تازه در جایگاه اولویت خودش می‌نشیند، نه لزوماً بالای ستون.'],
        ['icon' => 'forum', 'color' => 'secondary', 'label' => 'شمارندهٔ گفتگو و پیوست', 'text' => 'شمارندهٔ گفتگو و پیوست (کنار شمارهٔ وظیفه) تعداد را بدون بازکردن کارت نشان می‌دهد؛ نگه‌داشتن اشاره‌گر روی شمارندهٔ پیوست فهرستشان را باز می‌کند (تصویر در گالری مودال، فایل در تب جدید).'],
        ['icon' => 'donut_large', 'color' => 'tertiary', 'label' => 'پیش‌نمایش چک‌لیست', 'text' => 'نگه‌داشتن اشاره‌گر روی حلقهٔ پیشرفتِ چک‌لیست، فهرست موارد را فقط‌خواندنی نشان می‌دهد؛ ویرایش همچنان داخل کارت است.'],
        ['icon' => 'event', 'color' => 'secondary', 'label' => 'نمایش تاریخ ضرب‌الاجل', 'text' => 'اگر ضرب‌الاجل در هفتهٔ آینده باشد، روی کارت فقط روزِ هفته و تاریخ کوتاه دیده می‌شود («شنبه ۵ شهریور») تا بدون شمردن معلوم شود؛ مهلت‌های دورتر تاریخ کامل با سال می‌گیرند. همین قالب در گزارش و پنل مدیریت هم یکسان است.'],
        ['icon' => 'travel_explore', 'color' => 'secondary', 'label' => 'چیپ منشأ اقدام', 'text' => 'اگر «منشأ اقدام» یا «حوزهٔ منشأ اقدام» برای وظیفه ثبت شده باشد، چیپی با همین آیکون روی کارت ظاهر می‌شود؛ نگه‌داشتن اشاره‌گر هر دو مقدار را کامل نشان می‌دهد.'],
    ];

    $cardSubTabs = [
        ['id' => 'chips', 'icon' => 'sell', 'label' => 'چیپ‌ها و شمارنده‌ها', 'rows' => [$cardRows[1], $cardRows[5], $cardRows[6], $cardRows[7], $cardRows[8]]],
        ['id' => 'signals', 'icon' => 'bolt', 'label' => 'همکاران و فوریت', 'rows' => [$cardRows[0], $cardRows[4]]],
        ['id' => 'personal', 'icon' => 'palette', 'label' => 'سنجاق و رنگ', 'rows' => [$cardRows[2], $cardRows[3]]],
    ];

    $notes = [
        '«نظرات» خصوصی است: فقط بین ایجادکننده، مسئول انجام و همکارانِ افزوده‌شده در اطلاعات تکمیلی وظیفه است؛ فرد دیگری در آن مشارکت نمی‌کند. با ثبت هر پاسخ تازه، یک اعلان کارتابل برای بقیهٔ همین سه نقش (به‌جز نویسندهٔ پاسخ) فرستاده می‌شود. اگر وظیفه به پروژه‌ای متصل باشد، این با برگه‌های «فعالیت‌ها و نظرات» و «چت زندهٔ تیم» آن پروژه فرق دارد: آن دو برای همهٔ اعضای پروژه باز است، این یکی فقط برای همین سه نقشِ همین وظیفه.',
        'همین قابلیت گفتگوی دوطرفه در تیکتینگ هم وجود دارد؛ برای جزئیات، راهنمای بخش تیکتینگ را ببینید.',
        'کارت‌هایی که برچسب «از تیکت» دارند، به‌صورت خودکار از یک تیکت ساخته شده‌اند و فقط برای پیگیری‌اند؛ ویرایش و گفتگو دربارهٔ آن‌ها فقط از طریق خودِ تیکت انجام می‌شود. اولویت این کارت‌ها هم همان اولویتِ تیکت است و دستی قابل‌تغییر نیست.',
        'وقتی وظیفه‌ای را به فرد دیگری محول می‌کنید، نمای شما خودکار به زبانهٔ «محول شده» می‌رود. واگردانیِ محول‌کردن فقط توسط ایجادکننده و فقط وقتی وظیفه انجام‌شده نیست و از تیکت نیست ممکن است.',
        'دکمهٔ «کپی» روی هر کارت، وظیفهٔ تازه‌ای با همان عنوان (+ «کپی»)، توضیحات، برچسب‌ها، اولویت، پروژه، همکاران و جوابگو می‌سازد؛ چک‌لیست هم با همان وزن‌های هر مورد کپی می‌شود اما همهٔ موارد آن دوباره تیک‌نخورده شروع می‌شوند، و مسئول انجامِ کپی، خودِ شما خواهید بود. وظایفِ از تیکت یا آرشیوشده قابل کپی نیستند.',
        'تغییر مسئول انجام، ضرب‌الاجل، اولویت، برچسب‌ها، پروژه، «جوابگو» یا «دپارتمان» در تب اطلاعات تکمیلی، «تعیین تکلیف» در تب اقدام و پیگیری، یا افزودن/حذف/تغییرِ کلید در تب «دیتای سفارشی»، خودکار یک ردیف فعالیت ثبت می‌کند؛ تغییر جوابگو علاوه‌بر آن، ضرب‌الاجل وظیفه را در تقویم شخصیِ فرد جدید هم به‌اشتراک می‌گذارد. اگر وظیفه به پروژه‌ای متصل باشد، این فعالیت‌ها در تب «فعالیت‌ها و نظرات» همان پروژه دیده می‌شوند؛ برای وظایف بدون پروژه، همین زمان‌بندی در تب «تاریخچه» (آخرین برگهٔ مودال وظیفه) در دسترس است.',
        'دکمهٔ «تمرکز» روی هر ستون در دسکتاپ آن را به نیم‌صفحه گسترش می‌دهد و بقیهٔ ستون‌ها را بدون تغییر اندازه با swipe در دسترس می‌گذارد (در موبایل همان ستون بالای پشته می‌آید)؛ کلیک دوباره یا Escape آن را برمی‌گرداند، انتخاب در مرورگر به‌خاطر سپرده می‌شود و با بزرگ‌نمایی ناسازگار است.',
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
                ['id' => 'board', 'icon' => 'view_kanban', 'label' => 'برد و کارت', 'rows' => [$projectRows[0], $projectRows[1], $projectRows[2]]],
                ['id' => 'rules', 'icon' => 'tune', 'label' => 'تنظیمات و قواعد پروژه', 'rows' => [$projectRows[3], $projectRows[4], $projectRows[5]]],
            ]],
            ['id' => 'filters', 'subs' => [
                ['id' => 'basic', 'icon' => 'search', 'label' => 'جستجو و ترکیب', 'rows' => [$filtersRows[0], $filtersRows[1], $filtersRows[6]]],
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