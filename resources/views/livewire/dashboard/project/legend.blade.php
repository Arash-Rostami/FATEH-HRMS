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
            'intro' => 'دسترسی پروژه با مالک، اعضا و واحدهای انتخاب‌شده مشخص می‌شود. مدیران دارای مجوز هم می‌توانند آن را در پنل مدیریت بررسی کنند.',
            'rows' => [
                ['icon' => 'space_dashboard', 'color' => 'primary', 'label' => 'بخش‌های پروژه', 'text' => 'چرخهٔ کاری برای مراحل پشت‌سرهم است؛ فعالیت‌ها سابقهٔ تغییرها را نشان می‌دهد؛ چت برای هماهنگی تیم است. تقویم، برد وظایف، گزارش و تحلیل‌ها هم در همین صفحه‌اند. با باز کردن پروژه، چرخهٔ کاری نمایش داده می‌شود.'],
                ['icon' => 'compare_arrows', 'color' => 'secondary', 'label' => 'نظر را کجا بنویسم؟', 'text' => 'برای هماهنگی روزمره از چت تیم استفاده کنید. برای بحث دربارهٔ یک کار، گفتگوی همان وظیفه را باز کنید. در فعالیت‌ها، همه سابقه را می‌خوانند ولی فقط مالک پروژه می‌تواند نظر تازه ثبت کند.'],
                ['icon' => 'push_pin', 'color' => 'tertiary', 'label' => 'پیدا کردن و یادآوری پروژه', 'text' => 'سنجاق، رنگ و بی‌صدا کردن فقط در مرورگر شما ذخیره می‌شوند. دکمهٔ زنگ برای خودتان یادآورِ این پروژه می‌سازد؛ یادآور مربوط به کل پروژه است، نه یک وظیفهٔ خاص.'],
                ['icon' => 'zoom_in', 'color' => 'tertiary', 'label' => 'اندازهٔ آیکون‌ها و دکمه‌ها', 'text' => 'با «−» و «+» در بالای صفحه، آیکون‌ها و دکمه‌های نوار کناری و بالای پروژه را کوچک یا بزرگ کنید. برای برگشت به اندازهٔ اولیه، روی عدد وسط بزنید. در گوشی، این ابزارها در منوی «⋯» کنار عنوان هستند. این تنظیم در همین مرورگر برای پیام‌رسان، گروه‌ها و پروژه‌ها مشترک است.'],
                ['icon' => 'open_in_full', 'color' => 'primary', 'label' => 'تمام‌صفحه کردن فضای کاری', 'text' => 'دکمهٔ کنار نام پروژه کل فضای کاری را تمام‌صفحه می‌کند؛ برای بازگشت همان دکمه یا Esc.'],
                ['icon' => 'unfold_less', 'color' => 'tertiary', 'label' => 'خلوت‌سازی سربرگ پروژه', 'text' => 'با دکمهٔ پیکان‌های افقی، سربرگ و ابزارهای آن جمع می‌شوند تا فضای بیشتری برای کار داشته باشید. دکمهٔ روشن گوشهٔ صفحه آن‌ها را برمی‌گرداند. در موبایل، «خلوت‌سازی» در منوی سه‌نقطه‌ای قرار دارد. انتخاب شما در همین مرورگر حفظ می‌شود. «حالت خلوت» در منوی اصلی نیز سربرگ را جمع می‌کند و با خاموش‌کردن آن، سربرگ باز می‌شود.'],
                ['icon' => 'settings', 'color' => 'secondary', 'label' => 'تنظیمات و انتقال مدیریت', 'text' => 'مالک پروژه از دکمهٔ ویرایش می‌تواند اعضا، واحدهای دارای دسترسی، تأیید انجام کار، مهلت‌ها و اطلاعات دلخواه را تنظیم کند. با انتقال مالکیت، مدیر تازه اختیارات پروژه را می‌گیرد و مالک قبلی عضو می‌ماند.'],
                ['icon' => 'warning', 'color' => 'error', 'label' => 'هشدارهای بالای پروژه', 'text' => '«دیرکرد» یعنی مهلت کار گذشته است؛ «سررسید نزدیک» یعنی مهلت در سه روز آینده است. هشدار مهلت پیش‌فرض یا مهلت پروژه، کارهای نیازمند پیگیری را جدا نشان می‌دهد. کارهای انجام‌شده، در انتظار و متوقف در این هشدارها شمرده نمی‌شوند.'],
                ['icon' => 'bookmark_add', 'color' => 'tertiary', 'label' => 'استفادهٔ دوباره از مراحل', 'text' => 'با «ذخیره به‌عنوان الگو»، مراحل را برای پروژه‌های دیگر نگه دارید. ساخت چرخه از الگو یا «دوباره اجرا کن»، پیش‌نویس تازه می‌سازد؛ پس از بررسی مراحل و مسئولان، آن را شروع کنید. اجرای قبلی تغییر نمی‌کند.'],
                ['icon' => 'task_alt', 'color' => 'primary', 'label' => 'مرحله‌ای که وظیفه می‌سازد', 'text' => 'وقتی نوبت مرحله برسد، وظیفه در برد ساخته می‌شود؛ نفر اول مسئول انجام و بقیه همکارند. کار را در خود وظیفه انجام و در صورت نیاز تأیید کنید تا چرخه خودکار ادامه پیدا کند. دکمهٔ تکمیل جداگانه‌ای برای این مرحله نیست.'],
                ['icon' => 'shield_person', 'color' => 'secondary', 'label' => 'چه کسی مرحله را تمام می‌کند؟', 'text' => 'برای مرحلهٔ بدون وظیفه، اقدام یکی از مسئولان یا مالک پروژه کافی است؛ تأیید همه لازم نیست. رد کردن، چرخه را به مرحلهٔ تعیین‌شده برمی‌گرداند. مالک می‌تواند از مرحله عبور کند؛ در این صورت وظیفهٔ ناتمام آن متوقف می‌شود. وظیفهٔ مرحلهٔ جاری تا عبور یا لغو چرخه قابل حذف یا انتقال به پروژهٔ دیگر نیست.'],
                ['icon' => 'layers', 'color' => 'tertiary', 'label' => 'چند چرخهٔ هم‌زمان روی یک پروژه', 'text' => 'هر پروژه می‌تواند چند چرخهٔ کاری فعال هم‌زمان داشته باشد؛ شروع چرخهٔ تازه، چرخه‌های دیگرِ در حال اجرا را متوقف نمی‌کند.'],
            ],
        ],
        'comms' => [
            'intro' => 'برای هماهنگی تیم از چت استفاده کنید؛ برای صحبت دربارهٔ یک کار، گفتگوی همان وظیفه را باز کنید. فعالیت‌ها سابقهٔ تغییرهای پروژه را نگه می‌دارد.',
            'rows' => [
                ['icon' => 'forum', 'color' => 'primary', 'label' => 'گفتگوی تیم پروژه', 'text' => 'این برگه همان گروه خصوصی پروژه است. برای امکانات بیشتر، دکمهٔ باز کردن گروه کنار برگه‌ها را بزنید. اعضای فعال پروژه عضو گروه‌اند؛ کاربران نمی‌توانند آن را عمومی یا حذف کنند. با باز کردن برگه، پیام‌ها خوانده‌شده حساب می‌شوند.'],
                ['icon' => 'alternate_email', 'color' => 'secondary', 'label' => 'خبر دادن به یک نفر با @', 'text' => 'با نوشتن @ و انتخاب نام، به همان فرد اعلان می‌رسد. «پاسخ‌گو نیست» یعنی فرد در مرخصی یا مأموریت است؛ همچنان می‌توانید نام او را انتخاب کنید.'],
                ['icon' => 'search', 'color' => 'tertiary', 'label' => 'پیدا کردن یک فعالیت', 'text' => 'متن نظرها را جستجو کنید یا نوع رویداد را انتخاب کنید. برای نگه داشتن یک نظر، «نشان کردن» را بزنید. «فقط نشان‌شده‌ها» نظرهایی را که نشان کرده‌اید جدا می‌کند.'],
                ['icon' => 'attach_file', 'color' => 'primary', 'label' => 'پیوست فایل در فعالیت‌ها', 'text' => 'هنگام نوشتن نظر تا ۳ فایل پیوست کنید؛ تصاویر پیش‌نمایش می‌شوند و سایر فرمت‌ها لینک دانلود می‌شوند.'],
                ['icon' => 'attach_file', 'color' => 'tertiary', 'label' => 'پیوست فایل در چت زندهٔ تیم', 'text' => 'در چت زندهٔ تیم تا ۵ فایل (هرکدام حداکثر ۱۰ مگابایت) پیوست کنید — سقف جداگانه‌ای از فعالیت‌ها دارد.'],
                ['icon' => 'mark_chat_unread', 'color' => 'secondary', 'label' => 'پیام‌های خوانده‌نشدهٔ تیم', 'text' => 'خط «پیام‌های جدید» شروع پیام‌های خوانده‌نشدهٔ تیم را نشان می‌دهد؛ پیام‌های خودتان حساب نمی‌شوند. این خط در اولین بازدید نمایش داده نمی‌شود. با بستن و باز کردن دوبارهٔ گفتگو، جای خط به‌روز می‌شود.'],
                ['icon' => 'format_color_fill', 'color' => 'tertiary', 'label' => 'شدت رنگ پس‌زمینهٔ پیام‌های من', 'text' => 'در «پروفایل ← تنظیمات ← گفتگو»، پس‌زمینهٔ پیام‌هایتان در گفتگوی تیم را بین ۴۰ تا ۱۰۰ درصد کم‌رنگ یا پررنگ کنید. این تنظیم برای پروژه‌ها جداگانه در همین مرورگر ذخیره می‌شود. برای دیدن تغییر، صفحه را دوباره بارگذاری کنید.'],
                ['icon' => 'edit', 'color' => 'error', 'label' => 'ویرایش و حذف نظر خودتان', 'text' => 'تا ۱۰ دقیقه پس از ثبت، نظر خودتان را ویرایش یا حذف کنید؛ رویدادهای سیستمی هرگز قابل تغییر نیستند.'],
                ['icon' => 'edit_note', 'color' => 'primary', 'label' => 'نظر مالک و گفتگوی وظیفه', 'text' => 'فقط مالک پروژه در فعالیت‌ها نظر تازه ثبت می‌کند؛ بقیه می‌توانند بخوانند و ریاکشن بگذارند. پیام‌های پشت‌سرهمِ یک وظیفه در یک ردیف خلاصه می‌شوند؛ برای خواندن، آن ردیف را باز کنید.'],
                ['icon' => 'add_reaction', 'color' => 'secondary', 'label' => 'ریاکشن با ایموجی', 'text' => 'روی هر رویدادِ فعالیت‌ها ریاکشن بگذارید؛ نگه‌داشتن اشاره‌گر روی ریاکشن، نام ریاکشن‌دهندگان را نشان می‌دهد.'],
            ],
        ],
        'planning' => [
            'intro' => 'تقویم مهلت‌های این پروژه را نشان می‌دهد؛ برد وظایف نسخهٔ خلاصهٔ برد اصلی است.',
            'rows' => [
                ['icon' => 'today', 'color' => 'primary', 'label' => 'مهلت‌های امروز و عقب‌افتاده', 'text' => 'سررسید امروز و روزهای گذشتهٔ حل‌نشده در تقویم پررنگ می‌شوند؛ کلیک روی هر مهلت همان وظیفه را در همان‌جا باز می‌کند.'],
                ['icon' => 'view_list', 'color' => 'secondary', 'label' => 'نمای فهرستی تقویم', 'text' => 'با دکمهٔ کنار عنوان ماه از نمای شبکه‌ای به فهرست عمودیِ مهلت‌های همان ماه سوئیچ کنید.'],
                ['icon' => 'view_kanban', 'color' => 'tertiary', 'label' => 'برد همین پروژه', 'text' => 'فقط کارهای این پروژه را می‌بینید. کارت را بکشید تا وضعیت عوض شود، یا باز کنید تا جزئیات را ببینید. تغییر وضعیت نیازمند اجازهٔ شماست و برای اعلام انجام کار، نتیجهٔ کار باید مشخص باشد. ابزارهای بیشتر در برد کامل قرار دارند.'],
                ['icon' => 'palette', 'color' => 'secondary', 'label' => 'معنی رنگ‌های تقویم', 'text' => 'سبز: ثبت وظیفه؛ زرد: تغییر وضعیت؛ قرمز: مهلت کار ناتمام؛ آبی: انجام کار. مهلت کار انجام‌شده یا بایگانی‌شده خاکستری می‌شود. پرچم، مهلت پروژه را نشان می‌دهد و نشان چرخه، شروع یا پایان چرخه را. برای جزئیات روی روز بزنید؛ راهنمای کامل رنگ‌ها کنار نام ماه است.'],
                ['icon' => 'view_timeline', 'color' => 'primary', 'label' => 'نمای خط زمانی وظایف', 'text' => 'در نمای گانت، هر کار روی روزهای ماه به شکل یک نوار دیده می‌شود. قرمزِ پس از مهلت، دیرکرد را نشان می‌دهد؛ لبهٔ خط‌چین یعنی کار هنوز باز است و مهلت ندارد. روی نوار بزنید تا وظیفه باز شود.'],
                ['icon' => 'date_range', 'color' => 'error', 'label' => 'کارهای عقب‌افتاده و تغییر مهلت', 'text' => 'کارهای ناتمامِ پیش از این ماه در نوار قرمز بالای تقویم دیده می‌شوند. «جابجایی ×تعداد» تعداد دفعات تغییر مهلت است؛ تعداد روزهای تأخیر نیست.'],
                ['icon' => 'swap_horiz', 'color' => 'primary', 'label' => 'برد وظایف و تسک‌شیت', 'text' => 'کنار عنوان صفحه، «برد وظایف» را بزنید تا وظایف را ببینید. در برد، «پروژه‌ها» شما را به این صفحه برمی‌گرداند. «تسک‌شیت» گزارش عملکردتان را در زبانه‌ای تازه باز می‌کند.'],
                ['icon' => 'verified', 'color' => 'tertiary', 'label' => 'مهلت و تأیید انجام کار', 'text' => 'اگر تأیید مالک لازم باشد، اعلام «انجام‌شده» توسط عضو، کار را منتظر تأیید می‌گذارد. انجام کار توسط خود مالک همان موقع تأیید می‌شود. مهلت تازهٔ وظیفه نباید از مهلت نهایی پروژه دیرتر باشد. مهلت پیش‌فرض پروژه برای کارهایی است که بدون تاریخ مهلت ثبت می‌شوند.'],
                ['icon' => 'person_check', 'color' => 'primary', 'label' => 'فیلتر «وظایف من»', 'text' => 'این گزینه کارهایی را نشان می‌دهد که مسئول انجام یا همکار آن‌ها هستید. دوباره بزنید تا فیلتر برداشته شود. بقیهٔ فیلترها هم‌زمان اعمال می‌شوند.'],
            ],
        ],
        'report' => [
            'intro' => 'جدول گزارش همهٔ وظایف پروژه را با فیلتر، مرتب‌سازی و جزئیاتِ قابل‌بازشدن نشان می‌دهد.',
            'rows' => [
                ['icon' => 'checklist', 'color' => 'tertiary', 'label' => 'پیشرفت از چک‌لیست می‌آید', 'text' => 'درصد پیشرفتِ هر وظیفه بر اساس تکمیل چک‌لیست آن محاسبه می‌شود، نه یک عدد دستی.'],
                ['icon' => 'open_in_new', 'color' => 'primary', 'label' => 'کلیک روی عنوان یا لینکِ کپی', 'text' => 'در جدول گزارش، کلیک روی عنوان هر ردیف مستقیم شما را به همان وظیفه در برد وظایف می‌برد؛ دکمهٔ کوچک کنار عنوان لینک آن را کپی می‌کند.'],
                ['icon' => 'expand', 'color' => 'secondary', 'label' => 'گشودن جزئیات در همان ردیف', 'text' => 'روی فلشِ کنار ردیف یا روی خود ردیف کلیک کنید؛ فقط یک ردیف در هر لحظه باز می‌ماند و با Esc بسته می‌شود.'],
                ['icon' => 'sort', 'color' => 'primary', 'label' => 'مرتب‌سازی ستون‌ها', 'text' => 'ستون‌های اولویت، مهلت و آخرین فعالیت با کلیک روی سرستون مرتب می‌شوند.'],
                ['icon' => 'filter_alt', 'color' => 'secondary', 'label' => 'فیلتر و جستجوی گزارش', 'text' => 'در عنوان و شرح کارها جستجو کنید و نتیجه را با مسئول، اولویت، برچسب، بخش سازمانی یا طرح محدود کنید. کلیک روی برچسب داخل جزئیات، همان برچسب را انتخاب می‌کند.'],
                ['icon' => 'hub', 'color' => 'secondary', 'label' => 'چرا بعضی کارها دیده نمی‌شوند؟', 'text' => 'جستجو و همهٔ فیلترها با هم اعمال می‌شوند؛ هر کار باید همهٔ شرط‌ها را داشته باشد. اگر نتیجه‌ای نمی‌بینید، فیلترها را پاک کنید یا شرط کمتری بگذارید.'],
                ['icon' => 'corporate_fare', 'color' => 'tertiary', 'label' => 'گروه‌بندی بر اساس بخش سازمانی', 'text' => 'کارها بر اساس بخش سازمانی ثبت‌شده در جزئیات همان وظیفه دسته‌بندی می‌شوند. کارهایی که این اطلاعات را ندارند در گروه «بدون بخش سازمانی» می‌آیند.'],
                ['icon' => 'leaderboard', 'color' => 'tertiary', 'label' => 'نوار خلاصهٔ گزارش', 'text' => 'بالای جدول تعداد هر وضعیت و سررسیدهای گذشته و درصد کلی پیشرفت نمایش داده می‌شود؛ در پروژهٔ نیازمند تأیید، وظیفهٔ انجام‌شده تا تأیید مدیر در درصد پیشرفت شمرده نمی‌شود.'],
                ['icon' => 'donut_large', 'color' => 'secondary', 'label' => 'پیشرفت طرح‌ها', 'text' => 'درصد هر طرح از تعداد کارهای انجام‌شده نسبت به همهٔ کارهای همان طرح در نتیجهٔ فعلی حساب می‌شود. با تغییر فیلترها، این درصد هم می‌تواند تغییر کند.'],
                ['icon' => 'attach_file', 'color' => 'primary', 'label' => 'پیوست کارهای نتیجهٔ فعلی', 'text' => 'بخش پیوست‌ها، فایل‌های همهٔ کارهایی را که با جستجو و فیلترهای فعلی پیدا می‌شوند یکجا نشان می‌دهد. نام زیر هر فایل، وظیفهٔ مربوط را باز می‌کند.'],
                ['icon' => 'assignment_turned_in', 'color' => 'tertiary', 'label' => 'گزارش کارکرد من', 'text' => 'این دکمه گزارش کارکرد شما را برای بازهٔ دلخواه در زبانهٔ تازه باز می‌کند. گزارش مربوط به همهٔ کارهای شماست و فقط به این پروژه محدود نیست.'],
            ],
        ],
        'analytics' => [
            'intro' => 'نمودارها وضعیت کل پروژه را بررسی می‌کنند؛ فیلترهای برگهٔ گزارش روی آن‌ها اثر ندارند. تعداد کارها به‌تنهایی معیار دشواری یا کیفیت کار افراد نیست.',
            'rows' => [
                ['icon' => 'waterfall_chart', 'color' => 'secondary', 'label' => 'چه چیزی را بررسی کنم؟', 'text' => '«جریان کار» تعداد کارهای انجام‌شده و مدت بی‌تغییر ماندن کارها را نشان می‌دهد؛ «ریسک و مهلت» کارهای فوری و مهلت‌ها را؛ «کارهای اعضا» تعداد کارهای هر فرد و بخش سازمانی را.'],
                ['icon' => 'timer', 'color' => 'tertiary', 'label' => 'زمان معمول انجام یعنی چه؟', 'text' => 'زمان بین ثبت و انجام کارها به روش آماری «میانه» خلاصه می‌شود: مقدار وسط، یا میانگین دو مقدار وسط. این زمان، ساعت کاری ثبت‌شده نیست. برای نمایش آن، دست‌کم سه کار باید انجام شده باشند.'],
                ['icon' => 'grid_view', 'color' => 'error', 'label' => 'خانه‌های قرمز جدول', 'text' => 'در جدول اولویت و وضعیت، خانه‌های قرمز یعنی کار فوری هنوز شروع نشده یا در حال انجام است.'],
                ['icon' => 'visibility_off', 'color' => 'secondary', 'label' => 'نمودارهای بدون اطلاعات', 'text' => 'اگر برای کارها برچسب یا بخش سازمانی ثبت نشده باشد، نمودار مربوط به آن نمایش داده نمی‌شود.'],
            ],
        ],
    ];

    $notes = [
        'اگر عضو یا واحدی انتخاب نکنید، از میان کاربران عادی فقط مالک پروژه دسترسی دارد؛ مدیران دارای مجوز می‌توانند آن را در پنل مدیریت ببینند.',
        'حلقهٔ کنار نام پروژه درصد کارهای انجام‌شده را نشان می‌دهد. نشان چرخه، مرحلهٔ فعلی یا تعداد چرخه‌های فعال را نشان می‌دهد.',
        'نقطهٔ قرمز روی تصویر پروژه یعنی دعوت آن را هنوز باز نکرده‌اید. عدد کنار «پروژه‌ها» تعداد همین دعوت‌های بازنشده است.',
        'نشان کوچک روی برگه یعنی در آن بخش تغییر تازه‌ای رخ داده است.',
        'روی تعداد اعضا بزنید تا نام اعضا و وضعیت حضورشان را ببینید.',
        'شمارندهٔ دیرکرد، کارهای عقب‌افتاده را نشان می‌دهد. شمارش زمان تا مهلت پروژه پس از تکمیل همهٔ کارها یا بایگانی پروژه پنهان می‌شود.',
        'در پروژهٔ نیازمند تأیید، کار تأییدشده برای اعضا قفل است؛ فقط مالک می‌تواند آن را تغییر دهد یا با «بازگشایی» دوباره برای انجام باز کند.',
        'برای دیدن پیام‌ها و فعالیت‌های قدیمی‌تر، «موارد قدیمی‌تر» را بزنید.',
        'پیوند کپی‌شدهٔ یک نظر، همان نظر را در پروژه باز و مشخص می‌کند؛ دسترسی گیرنده همچنان بررسی می‌شود.',
        'در «اطلاعات دلخواه» وظیفه، اطلاعات خواسته‌شدهٔ پروژه را وارد کنید. اگر پروژه قسمت ازپیش‌تعریف‌شده ندارد، نام انگلیسی و مقدار دلخواه وارد می‌شود؛ نام فقط حروف کوچک انگلیسی، عدد و زیرخط (_) می‌پذیرد.',
        'کار انجام‌شده و تأییدشده پس از گذشت مدت تعیین‌شده از تأیید یا آخرین تغییر، هرکدام دیرتر باشد، بایگانی و از برد پنهان می‌شود. مالک پروژه، ثبت‌کننده یا مسئول انجام می‌تواند از فهرست بایگانی آن را برگرداند.',
        'بلندگوی بالای فهرست، صدای همهٔ پروژه‌ها را در همین مرورگر خاموش یا روشن می‌کند.',
        'در رایانه، فلش کنار عنوان فهرست آن را جمع می‌کند. «بازگشت» در سربرگ، پروژهٔ باز را می‌بندد و شما را به فهرست برمی‌گرداند.',
        'در گوشی، بعضی ابزارها و برگه‌ها در منوی سه‌نقطه‌ای قرار دارند.',
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
            ['id' => 'space', 'icon' => 'space_dashboard', 'label' => 'فضای کاری', 'rows' => [$w[0], $w[1], $w[2], $w[3], $w[4], $w[5]]],
            ['id' => 'settings', 'icon' => 'settings', 'label' => 'تنظیمات و هشدار', 'rows' => [$w[6], $w[7]]],
            ['id' => 'cycle', 'icon' => 'conversion_path', 'label' => 'چرخهٔ کاری', 'rows' => [$w[8], $w[9], $w[10], $w[11]]],
        ],
        'comms' => [
            ['id' => 'activity', 'icon' => 'edit', 'label' => 'فعالیت‌ها و نظرات', 'rows' => [$c[2], $c[3], $c[5], $c[6], $c[7]]],
            ['id' => 'chat', 'icon' => 'forum', 'label' => 'چت زندهٔ تیم', 'rows' => [$c[0], $c[1], $c[4]]],
        ],
        'planning' => [
            ['id' => 'calendar', 'icon' => 'today', 'label' => 'تقویم', 'rows' => [$p[0], $p[1]]],
            ['id' => 'markers', 'icon' => 'palette', 'label' => 'نشانه‌های رنگی', 'rows' => [$p[3]]],
            ['id' => 'gantt', 'icon' => 'view_timeline', 'label' => 'گانت و دیرکرد', 'rows' => [$p[4], $p[5]]],
            ['id' => 'board', 'icon' => 'view_kanban', 'label' => 'برد وظایف', 'rows' => [$p[2], $p[6], $p[7], $p[8]]],
        ],
        'report' => [
            ['id' => 'table', 'icon' => 'view_list', 'label' => 'جدول', 'rows' => [$r[0], $r[1], $r[2], $r[3]]],
            ['id' => 'filters', 'icon' => 'filter_alt', 'label' => 'فیلتر و جستجو', 'rows' => [$r[4], $r[5], $r[6], $r[8], $r[10]]],
            ['id' => 'extra', 'icon' => 'attach_file', 'label' => 'خلاصه و پیوست', 'rows' => [$r[7], $r[9]]],
        ],
    ];

    $notesGroups = [
        ['id' => 'invite', 'icon' => 'person', 'label' => 'حریم و دعوت', 'rows' => [$notes[0], $notes[2]]],
        ['id' => 'header', 'icon' => 'badge', 'label' => 'سربرگ و فهرست', 'rows' => [$notes[1], $notes[4], $notes[5], $notes[10], $notes[11], $notes[12]]],
        ['id' => 'tabs', 'icon' => 'forum', 'label' => 'برگه‌ها و گفتگو', 'rows' => [$notes[3], $notes[6], $notes[7]]],
        ['id' => 'tasks', 'icon' => 'task_alt', 'label' => 'وظایف و آرشیو', 'rows' => [$notes[8], $notes[9]]],
    ];
@endphp

<div x-data="{ tab: 'workspace', sub: 'space' }">
    <div class="flex p-1 mb-5 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
        @foreach($tabs as $tab)
            <button type="button" @click="tab = '{{ $tab['id'] }}'@if(!empty($tab['sub'])) ; sub = '{{ $tab['sub'] }}'@endif"
                    title="{{ $tab['label'] }}"
                    :class="tab === '{{ $tab['id'] }}'
                        ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                        : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                    class="flex-1 flex flex-col items-center justify-center gap-0.5 px-1.5 py-2 rounded-xl text-[11px] font-bold transition-all duration-200">
                <span class="material-symbols-rounded text-[18px]">{{ $tab['icon'] }}</span>
                <span class="hidden sm:block leading-tight text-center">{{ $tab['label'] }}</span>
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
                                class="flex-1 flex items-center justify-center gap-1.5 px-1.5 sm:px-3 py-2 rounded-xl text-[11px] sm:text-[13px] font-bold transition-all duration-200">
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
                                class="flex-1 flex items-center justify-center gap-1.5 px-1.5 sm:px-3 py-2 rounded-xl text-[11px] sm:text-[13px] font-bold transition-all duration-200">
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
