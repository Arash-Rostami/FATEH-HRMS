@php
    $tabs = [
        ['id' => 'workspace', 'icon' => 'space_dashboard', 'label' => 'فضای کاری', 'sub' => 'space'],
        ['id' => 'comms', 'icon' => 'forum', 'label' => 'فعالیت و گفتگو', 'sub' => 'activity'],
        ['id' => 'planning', 'icon' => 'event', 'label' => 'برنامه‌ریزی و برد', 'sub' => 'calendar'],
        ['id' => 'report', 'icon' => 'leaderboard', 'label' => 'گزارش', 'sub' => 'table'],
        ['id' => 'analytics', 'icon' => 'monitoring', 'label' => 'تحلیل‌ها'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات', 'sub' => 'invite'],
    ];

    $sections = [
        'workspace' => [
            'intro' => 'هر پروژه یک فضای کاری خصوصی با پنج برگه است؛ فقط سازنده و اعضای افزوده‌شده آن را می‌بینند.',
            'rows' => [
                ['icon' => 'space_dashboard', 'color' => 'primary', 'label' => 'یک فضای کاری، پنج برگه', 'text' => 'فعالیت‌ها و نظرات، چت زندهٔ تیم، تقویم، برد وظایف و گزارش همگی در همین صفحه‌اند — نیازی به رفتن به ماژول جداگانه نیست.'],
                ['icon' => 'compare_arrows', 'color' => 'secondary', 'label' => 'فعالیت‌ها و نظرات یا چت زندهٔ تیم؟', 'text' => 'فعالیت‌ها و نظرات برای سابقهٔ رسمی (تصمیم، تأیید، دلیل تغییر وضعیت) است؛ چت زندهٔ تیم برای هماهنگی روزمره‌ای که نیازی به ثبت در تاریخچه نیست. جدا از این دو، هر وظیفه یک «گفتگوی وظیفه» خصوصیِ خودش هم دارد که فقط ایجادکننده، مسئول انجام و همکاران همان وظیفه می‌بینند — در برد وظایف، نه اینجا.'],
                ['icon' => 'push_pin', 'color' => 'tertiary', 'label' => 'سنجاق، بی‌صدا و رنگ‌آمیزی پروژه‌ها', 'text' => 'با نگه‌داشتن اشاره‌گر روی هر پروژه در فهرست کنار، سنجاق‌کردن، بی‌صداکردن اعلان و رنگ‌آمیزی ظاهر می‌شود؛ فقط در همین مرورگر ذخیره می‌شوند.'],
                ['icon' => 'open_in_full', 'color' => 'primary', 'label' => 'تمام‌صفحه کردن فضای کاری', 'text' => 'دکمهٔ کنار نام پروژه کل فضای کاری را تمام‌صفحه می‌کند؛ برای بازگشت همان دکمه یا Esc.'],
                ['icon' => 'settings', 'color' => 'secondary', 'label' => 'تنظیمات پروژه', 'text' => 'از دکمهٔ ویرایش در سربرگ: نیاز به تأیید مدیر پروژه برای انجام‌شدنِ وظایف، SLA (ساعت)، سقف مهلت پروژه برای همهٔ وظایف آن، تعریف فیلدهای دیتای سفارشی، و بخش «تنظیمات دیگر» برای هر کلید/مقدار دلخواه دیگری که در گزینه‌های ثابت نمی‌گنجد. هر تغییر در همین تنظیمات، خودکار یک ردیف در برگهٔ «فعالیت‌ها و نظرات» پروژه ثبت می‌کند.'],
                ['icon' => 'warning', 'color' => 'error', 'label' => 'نشان‌های هشدار سربرگ', 'text' => 'کنار شمارندهٔ وظایف، سه نشان هشدار ممکن است بنشیند: «دیرکرد» (مهلت گذشته)، «در معرض تأخیر» (وظیفه‌ای که SLA پروژه را نقض کرده یا ضرب‌الاجلش از سقف مهلت پروژه فراتر است — انجام‌شده‌ها و «تعیین تکلیف»ها شمرده نمی‌شوند) و شمارش معکوس تا سقف مهلت پروژه (سبز بیش از یک هفته، زرد هفتهٔ آخر، قرمز امروز یا گذشته). زیر عنوان هم خطی کم‌رنگ، تنظیمات فعال پروژه را خلاصه می‌کند.'],
            ],
        ],
        'comms' => [
            'intro' => 'سه سطح گفت‌وگوی جدا در این محصول: «فعالیت‌ها و نظرات» سابقهٔ رسمی پروژه (رویداد سیستمی + نظر باز) را برای همهٔ اعضا نگه می‌دارد؛ «چت زندهٔ تیم» همان کانال واقعی پروژه در ماژول «کانال‌ها» برای پیام‌رسانی آنی همهٔ اعضاست؛ «گفتگوی وظیفه» (داخل خودِ کارت در برد وظایف) خصوصی و محدود به ایجادکننده، مسئول انجام و همکاران همان یک وظیفه است.',
            'rows' => [
                ['icon' => 'forum', 'color' => 'primary', 'label' => 'چت زندهٔ تیم همان کانال واقعی است', 'text' => 'برای هر پروژه یک کانال واقعی ساخته می‌شود؛ برای امکانات کامل‌تر دکمهٔ رنگیِ کنار برگه‌ها را بزنید تا کانال کامل در تب جدید باز شود.'],
                ['icon' => 'alternate_email', 'color' => 'secondary', 'label' => 'اشاره با @', 'text' => 'با تایپ @ فهرست اعضا باز می‌شود؛ نام انتخاب‌شده در پیام هایلایت شده و به فرد اشاره‌شده اعلان می‌رسد.'],
                ['icon' => 'search', 'color' => 'tertiary', 'label' => 'جستجو، فیلتر نوع و خروجی فعالیت‌ها', 'text' => 'جستجوی متن نظرها، فیلتر یک نوع رویداد، سنجاق نظرهای مهم و خروجی متنی همان فهرست.'],
                ['icon' => 'attach_file', 'color' => 'primary', 'label' => 'پیوست فایل در فعالیت‌ها', 'text' => 'هنگام نوشتن نظر تا ۳ فایل پیوست کنید؛ تصاویر پیش‌نمایش می‌شوند و سایر فرمت‌ها لینک دانلود می‌شوند.'],
                ['icon' => 'attach_file', 'color' => 'tertiary', 'label' => 'پیوست فایل در چت زندهٔ تیم', 'text' => 'در چت زندهٔ تیم تا ۵ فایل (هرکدام حداکثر ۱۰ مگابایت) پیوست کنید — سقف جداگانه‌ای از فعالیت‌ها دارد.'],
                ['icon' => 'edit', 'color' => 'error', 'label' => 'ویرایش و حذف نظر خودتان', 'text' => 'تا ۱۰ دقیقه پس از ثبت، نظر خودتان را ویرایش یا حذف کنید؛ رویدادهای سیستمی هرگز قابل تغییر نیستند.'],
                ['icon' => 'add_reaction', 'color' => 'secondary', 'label' => 'واکنش با ایموجی', 'text' => 'روی هر رویدادِ فعالیت‌ها واکنش بگذارید؛ نگه‌داشتن اشاره‌گر روی واکنش، نام واکنش‌دهندگان را نشان می‌دهد.'],
            ],
        ],
        'planning' => [
            'intro' => 'تقویم مهلت‌های این پروژه را نشان می‌دهد؛ برد وظایف نسخهٔ خلاصهٔ برد اصلی است.',
            'rows' => [
                ['icon' => 'today', 'color' => 'primary', 'label' => 'مهلت‌های امروز و عقب‌افتاده', 'text' => 'سررسید امروز و روزهای گذشتهٔ حل‌نشده در تقویم پررنگ می‌شوند؛ کلیک روی هر مهلت همان وظیفه را در همان‌جا باز می‌کند.'],
                ['icon' => 'view_list', 'color' => 'secondary', 'label' => 'نمای فهرستی تقویم', 'text' => 'با دکمهٔ کنار عنوان ماه از نمای شبکه‌ای به فهرست عمودیِ مهلت‌های همان ماه سوئیچ کنید.'],
                ['icon' => 'view_kanban', 'color' => 'tertiary', 'label' => 'برد وظایف نسخهٔ خلاصهٔ برد اصلی', 'text' => 'فقط وظایف همین پروژه؛ با کشیدن-و-رهاکردن وضعیت را تغییر دهید یا با کلیک جزئیات را ویرایش کنید. برای آرشیو یا کارهای دسته‌جمعی دکمهٔ رنگیِ کنار برگه‌ها را بزنید تا برد کامل باز شود.'],
                ['icon' => 'palette', 'color' => 'secondary', 'label' => 'نقطه‌های رنگی روی هر روز', 'text' => 'سبز=ایجاد وظیفه، زرد=تغییر وضعیت، قرمز=مهلت باز، آبی=تکمیل؛ مهلتی که وظیفه‌اش انجام یا آرشیو شده به‌جای حذف، خاکستری می‌ماند. پرچم آبی‌کمرنگ (مهلت پروژه) روزِ سقف مهلتِ خود پروژه را نشان می‌دهد. با کلیک روی روز، جزئیات هر رویداد (و مدت انتظار در صورت وجود) پایین تقویم باز می‌شود؛ راهنمای رنگ‌ها با آیکون پالت کنار عنوان ماه.'],
                ['icon' => 'view_timeline', 'color' => 'primary', 'label' => 'نمای گانت وظایف', 'text' => 'سومین دکمهٔ کنار عنوان ماه هر وظیفه را به‌صورت نوار افقی روی روزهای ماه نشان می‌دهد؛ کلیک روی ردیف یا نوار، خودِ وظیفه را باز می‌کند. لبهٔ خط‌چین یعنی وظیفهٔ بدون مهلت هنوز باز است، نوارهای تک‌روزه لوزی می‌شوند، دنبالهٔ قرمز یعنی روزهای گذشته از مهلت، و خط نقطه‌چینِ سوم، سقف مهلت پروژه است.'],
                ['icon' => 'date_range', 'color' => 'error', 'label' => 'دیرکردهای پیش از این ماه و جابجایی مهلت', 'text' => 'وظایف عقب‌افتاده‌ای که مهلتشان قبل از این ماه بوده، در نوار قرمز بالای تقویم جمع می‌شوند. اگر سررسیدِ وظیفه‌ای دستکاری شده باشد، برچسب «جابجایی ×تعداد (از تاریخ اولیه)» کنار همان وظیفه در تقویم و گانت می‌نشیند — شمارش رویدادهای تغییر مهلت است، نه شمارش روزها.'],
                ['icon' => 'swap_horiz', 'color' => 'primary', 'label' => 'جابجایی بین برد و پروژه‌ها', 'text' => 'با دکمهٔ بالای صفحه بدون بارگذاری مجدد بین «برد وظایف» و «پروژه‌ها» جابجا شوید.'],
                ['icon' => 'verified', 'color' => 'tertiary', 'label' => 'تأیید انجام، سقف مهلت و SLA', 'text' => 'اگر «نیاز به تأیید مدیر» در تنظیمات پروژه فعال باشد، وظیفهٔ رسیده به «انجام‌شده» حالت «منتظر تأیید» می‌گیرد تا مدیر پروژه با دکمهٔ روی کارت آن را تأیید کند (اگر خودِ مدیر انجامش دهد، خودکار تأیید می‌شود)؛ زنگولهٔ مدیر پس از ۲۴ و سپس ۴۸ ساعت یادآوری و فوری می‌شود. سقف مهلتِ پروژه، ثبت وظیفهٔ فراتر از آن را رد می‌کند و وظیفهٔ فراتر رفته را با برچسب فوریت نشان می‌دهد؛ SLA (ساعت) هم مشابه با برچسب «نقض SLA» هشدار می‌دهد.'],
                ['icon' => 'person_check', 'color' => 'primary', 'label' => 'چیپ «وظایف من» روی برد', 'text' => 'چیپ کنار جستجو فقط کارهایی را نگه می‌دارد که مسئولِ انجامشان خودِ شما هستید؛ کلیکِ دوباره همهٔ کارها را برمی‌گرداند. این فیلتر با بقیهٔ فیلترها جمع می‌شود و در شمارندهٔ فیلترهای فعال هم حساب می‌شود.'],
            ],
        ],
        'report' => [
            'intro' => 'جدول گزارش همهٔ وظایف پروژه را با فیلتر، مرتب‌سازی و جزئیاتِ قابل‌بازشدن نشان می‌دهد.',
            'rows' => [
                ['icon' => 'checklist', 'color' => 'tertiary', 'label' => 'پیشرفت از چک‌لیست می‌آید', 'text' => 'درصد پیشرفتِ هر وظیفه بر اساس تکمیل چک‌لیست آن محاسبه می‌شود، نه یک عدد دستی.'],
                ['icon' => 'open_in_new', 'color' => 'primary', 'label' => 'کلیک روی عنوان یا لینکِ کپی', 'text' => 'در جدول گزارش، کلیک روی عنوان هر ردیف مستقیم شما را به همان وظیفه در برد وظایف می‌برد؛ دکمهٔ کوچک کنار عنوان لینک آن را کپی می‌کند.'],
                ['icon' => 'expand', 'color' => 'secondary', 'label' => 'گشودن جزئیات در همان ردیف', 'text' => 'روی فلشِ کنار ردیف یا روی خود ردیف کلیک کنید؛ فقط یک ردیف در هر لحظه باز می‌ماند و با Esc بسته می‌شود.'],
                ['icon' => 'sort', 'color' => 'primary', 'label' => 'مرتب‌سازی ستون‌ها', 'text' => 'ستون‌های اولویت، مهلت و آخرین فعالیت با کلیک روی سرستون مرتب می‌شوند.'],
                ['icon' => 'filter_alt', 'color' => 'secondary', 'label' => 'فیلتر و جستجوی گزارش', 'text' => 'جستجو روی عنوان/توضیحات و فیلتر مسئول، اولویت، برچسب، دپارتمان، طرح و منبع اقدام؛ کلیک روی هر برچسب داخل جزئیات همان فیلتر را اعمال می‌کند.'],
                ['icon' => 'hub', 'color' => 'secondary', 'label' => 'فیلتر دومرحله‌ای منبع اقدام', 'text' => 'ابتدا «حوزهٔ منبع اقدام» را انتخاب کنید؛ فهرست «منبع اقدام» خودکار فقط به مقادیری محدود می‌شود که با همان حوزه ثبت شده‌اند. بدون انتخاب حوزه، فهرست کامل منابع را می‌بینید.'],
                ['icon' => 'corporate_fare', 'color' => 'tertiary', 'label' => 'گروه‌بندی بر اساس دپارتمان', 'text' => 'ردیف‌های جدول زیر سرگروهِ دپارتمانِ همان وظیفه (از تب «اطلاعات سازمانی» وظیفه) دسته‌بندی می‌شوند؛ وظایف بدون دپارتمان در یک گروه «بدون دپارتمان» در انتهای جدول جمع می‌شوند.'],
                ['icon' => 'leaderboard', 'color' => 'tertiary', 'label' => 'نوار خلاصهٔ گزارش', 'text' => 'بالای جدول تعداد هر وضعیت و سررسیدهای گذشته و درصد کلی پیشرفت نمایش داده می‌شود.'],
                ['icon' => 'donut_large', 'color' => 'secondary', 'label' => 'نوار پیشرفت هر طرح', 'text' => 'اگر فیلتر «طرح» فعال باشد یک حلقهٔ پیشرفت برای همان طرح می‌بینید؛ بدون فیلتر طرح، یک حلقه برای هر طرحِ موجود در نتیجهٔ فعلی نمایش داده می‌شود — نسبت وظایفِ انجام‌شده به کل وظایف همان طرح، با در نظر گرفتن سایر فیلترهای فعال.'],
                ['icon' => 'attach_file', 'color' => 'primary', 'label' => 'پیوست‌های همهٔ وظایفِ فیلترشده', 'text' => 'بخش «پیوست‌ها» زیر فیلترها، پیوست همهٔ وظایفی را که با فیلتر/جست‌وجوی فعلی مطابقت دارند یک‌جا جمع می‌کند؛ نام وظیفهٔ صاحب هر پیوست هم زیر آن دیده می‌شود و به همان وظیفه لینک می‌دهد. تصویرها در یک گالری قابل‌ورق‌زدن باز می‌شوند (تصویرهای یک وظیفه با هم، جدا از وظایف دیگر)، فایل‌های دیگر در تب جدید.'],
                ['icon' => 'assignment_turned_in', 'color' => 'tertiary', 'label' => 'گزارش تسک‌شیت', 'text' => 'دکمهٔ گزارش تسک‌شیت در نوار فیلترهای همین تب، گزارش عملکرد شخصی شما (نه فقط این پروژه) را برای بازهٔ دلخواه در تب جدید باز می‌کند؛ همان گزارش قابل اشتراک‌گذاری با مدیرتان است.'],
            ],
        ],
        'analytics' => [
            'intro' => 'برگهٔ تحلیل‌ها از رویدادهای تغییر وضعیت وظایف، چرخهٔ انجام واقعی، ریسک‌های مهلت و بار افراد را استخراج می‌کند؛ مستقل از فیلترهای برگهٔ گزارش.',
            'rows' => [
                ['icon' => 'waterfall_chart', 'color' => 'secondary', 'label' => 'سه تب موضوعی', 'text' => '«جریان کار» ریتم تکمیل و کارهای بی‌تغییر را نشان می‌دهد، «ریسک و مهلت» ماتریس اولویت×وضعیت و سررسیدها را، و «بار افراد» توزیع کار و دپارتمان‌ها را.'],
                ['icon' => 'timer', 'color' => 'tertiary', 'label' => 'چرخهٔ انجام واقعی', 'text' => 'میانگین چرخه و رعایت مهلت از رویدادهای «تغییر وضعیت به انجام‌شده» به دست می‌آید، نه از زمان ویرایش؛ بنابراین فقط وقتی حداقل سه کار واقعاً تکمیل شده باشند عدد می‌بینید.'],
                ['icon' => 'grid_view', 'color' => 'error', 'label' => 'خانه‌های قرمز ماتریس', 'text' => 'در ماتریس اولویت×وضعیت، خانه‌های قرمز کارهای فوریِ شروع‌نشده یا در حال انجام هستند.'],
                ['icon' => 'visibility_off', 'color' => 'secondary', 'label' => 'مخفی شدن نمودارهای بدون داده', 'text' => 'نمودار «برچسب‌ها» و «دپارتمان» فقط وقتی آن بعد پر باشد ظاهر می‌شوند؛ پروژه‌ای که برچسب یا دپارتمان ندارد، نمودار خالی نمی‌بینید.'],
            ],
        ],
    ];

    $notes = [
        'هر پروژهٔ تازه به‌صورت پیش‌فرض خصوصی است و فقط برای سازنده‌اش دیده می‌شود؛ برای دیدن آن توسط دیگران باید عضو یا دپارتمان را اضافه کنید.',
        'در فهرست کنار، حلقهٔ رنگی کنار نام هر پروژه درصد تکمیل وظایف آن را نشان می‌دهد و در پایان سبز می‌شود.',
        'نقطهٔ قرمز روی آواتار یک پروژه یعنی به آن دعوت شده‌اید ولی هنوز وارد نشده‌اید؛ شمارهٔ کنار «پروژه‌ها» مجموع همین دعوت‌هاست.',
        'اگر در برگه‌ای هستید و در برگه‌های دیگر چیزی تغییر کند، همان برگه با نشان کوچکی علامت می‌خورد تا سر بزنید.',
        'روی شمارندهٔ اعضا در سربرگ کلیک کنید تا فهرست اعضا با آواتار، وضعیت حضور و رویداد ویژهٔ امروز (تولد یا سالگرد) باز شود.',
        'وقتی پروژه وظیفهٔ عقب‌افتاده داشته باشد، شمارندهٔ دیرکرد کنار «انجام‌شده از کل» در سربرگ نمایش داده می‌شود.',
        'برگهٔ فعالیت‌ها و نظرات و چت زندهٔ تیم هر دو فقط تازه‌ترین موارد را می‌آورند؛ با دکمهٔ «موارد قدیمی‌تر» صفحهٔ بعدی را بدون رفرش بارگذاری کنید.',
        'کپی لینک روی هر نظر، با باز شدن مستقیم به همان نظر اسکرول کرده و آن را برای چند لحظه پررنگ می‌کند — حتی اگر قدیمی و خارج از بارگذاری اولیه باشد.',
        'روی کارت‌های برد وظایف این پروژه، تب «دیتای سفارشی» مودال وظیفه همیشه فعال است: اگر پروژه فیلدهایی با برچسب تعریف کرده باشد همان‌ها ظاهر می‌شوند، وگرنه — روی هر وظیفه‌ای — کلید/مقدار آزاد می‌نویسید؛ کلید فقط حروف کوچک انگلیسی، رقم و زیرخط می‌پذیرد.',
        'وظیفهٔ انجام‌شده و تأییدشدهٔ این پروژه که مدتی (پیش‌فرض ۴۵ روز) از تأییدش بگذرد، خودکار شبانه آرشیو می‌شود و از برد این پروژه پنهان می‌شود؛ عددِ کنار آیکون آرشیو در سرستونِ «انجام‌شده» همیشه تعداد این موارد را نشان می‌دهد (حتی صفر). با «مشاهده و بازگردانی» زیر همان راهنما، فهرست وظایف آرشیوشدهٔ همین پروژه را می‌بینید و ایجادکنندهٔ هر وظیفه می‌تواند همان‌جا آن را از آرشیو خارج کند؛ سایرین فقط عنوان و تاریخ آرشیو را می‌بینند.',
    ];

    $chipClasses = fn($c) => match ($c) {
        'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
        'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
        'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
        'error' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
    };

    $w = $sections['workspace']['rows'];
    $c = $sections['comms']['rows'];
    $p = $sections['planning']['rows'];
    $r = $sections['report']['rows'];
    $groups = [
        'workspace' => [
            ['id' => 'space', 'icon' => 'space_dashboard', 'label' => 'فضای کاری', 'rows' => [$w[0], $w[1], $w[2], $w[3]]],
            ['id' => 'settings', 'icon' => 'settings', 'label' => 'تنظیمات و هشدار', 'rows' => [$w[4], $w[5]]],
        ],
        'comms' => [
            ['id' => 'activity', 'icon' => 'edit', 'label' => 'فعالیت‌ها و نظرات', 'rows' => [$c[2], $c[3], $c[5], $c[6]]],
            ['id' => 'chat', 'icon' => 'forum', 'label' => 'چت زندهٔ تیم', 'rows' => [$c[0], $c[1], $c[4]]],
        ],
        'planning' => [
            ['id' => 'calendar', 'icon' => 'today', 'label' => 'تقویم', 'rows' => [$p[0], $p[1], $p[3], $p[4], $p[5]]],
            ['id' => 'board', 'icon' => 'view_kanban', 'label' => 'برد وظایف', 'rows' => [$p[2], $p[8], $p[7], $p[6]]],
        ],
        'report' => [
            ['id' => 'table', 'icon' => 'view_list', 'label' => 'جدول', 'rows' => [$r[0], $r[1], $r[2], $r[3]]],
            ['id' => 'filters', 'icon' => 'filter_alt', 'label' => 'فیلتر و جستجو', 'rows' => [$r[4], $r[5], $r[6], $r[8], $r[10]]],
            ['id' => 'extra', 'icon' => 'attach_file', 'label' => 'خلاصه و پیوست', 'rows' => [$r[7], $r[9]]],
        ],
    ];

    $notesGroups = [
        ['id' => 'invite', 'icon' => 'person', 'label' => 'حریم و دعوت', 'rows' => [$notes[0], $notes[2]]],
        ['id' => 'header', 'icon' => 'badge', 'label' => 'سربرگ و فهرست', 'rows' => [$notes[1], $notes[4], $notes[5]]],
        ['id' => 'tabs', 'icon' => 'forum', 'label' => 'برگه‌ها و گفتگو', 'rows' => [$notes[3], $notes[6], $notes[7]]],
        ['id' => 'tasks', 'icon' => 'task_alt', 'label' => 'وظایف و آرشیو', 'rows' => [$notes[8], $notes[9]]],
    ];
@endphp

<div x-data="{ tab: 'workspace', sub: 'space' }">
    <div class="flex p-1 mb-5 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
        @foreach($tabs as $tab)
            <button type="button" @click="tab = '{{ $tab['id'] }}'@if(!empty($tab['sub'])) ; sub = '{{ $tab['sub'] }}'@endif"
                    :class="tab === '{{ $tab['id'] }}'
                        ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                        : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                    class="flex-1 flex flex-col items-center justify-center gap-0.5 px-1.5 py-2 rounded-xl text-[11px] font-bold transition-all duration-200">
                <span class="material-symbols-rounded text-[18px]">{{ $tab['icon'] }}</span>
                <span class="leading-tight text-center">{{ $tab['label'] }}</span>
            </button>
        @endforeach
    </div>

    @foreach($tabs as $tab)
        @if($tab['id'] === 'notes')
            <div x-show="tab === 'notes'" x-cloak>
                <div class="flex p-1 mb-4 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
                    @foreach($notesGroups as $g)
                        <button type="button" @click="sub = '{{ $g['id'] }}'"
                                :class="sub === '{{ $g['id'] }}'
                                    ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                                    : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                                class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-[13px] font-bold transition-all duration-200">
                            <span class="material-symbols-rounded text-[17px]">{{ $g['icon'] }}</span>
                            {{ $g['label'] }}
                        </button>
                    @endforeach
                </div>
                @foreach($notesGroups as $g)
                    <div x-show="sub === '{{ $g['id'] }}'" x-cloak class="space-y-2">
                        @foreach($g['rows'] as $note)
                            <div class="flex items-start gap-2 px-1">
                                <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">info</span>
                                <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $note }}</p>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @elseif(!empty($groups[$tab['id']]))
            @php($sec = $sections[$tab['id']])
            <div x-show="tab === '{{ $tab['id'] }}'" x-cloak>
                @if(!empty($sec['intro']))
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1 mb-2">{{ $sec['intro'] }}</p>
                @endif
                <div class="flex p-1 mb-4 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
                    @foreach($groups[$tab['id']] as $g)
                        <button type="button" @click="sub = '{{ $g['id'] }}'"
                                :class="sub === '{{ $g['id'] }}'
                                    ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                                    : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                                class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-[13px] font-bold transition-all duration-200">
                            <span class="material-symbols-rounded text-[17px]">{{ $g['icon'] }}</span>
                            {{ $g['label'] }}
                        </button>
                    @endforeach
                </div>
                @foreach($groups[$tab['id']] as $g)
                    <div x-show="sub === '{{ $g['id'] }}'" x-cloak class="space-y-2">
                        @foreach($g['rows'] as $s)
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
        @else
            @php($sec = $sections[$tab['id']])
            <div x-show="tab === '{{ $tab['id'] }}'" x-cloak class="space-y-2">
                @if(!empty($sec['intro']))
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1 mb-1">{{ $sec['intro'] }}</p>
                @endif
                @foreach($sec['rows'] as $s)
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
        @endif
    @endforeach
</div>