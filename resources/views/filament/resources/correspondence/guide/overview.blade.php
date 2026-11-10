@php
    $rows = [
        ['icon' => 'edit_off', 'color' => 'primary', 'label' => 'این بخش فقط پایش است', 'text' => 'نامه‌ها از پنل کاربر ساخته و در جریان امضا می‌شوند؛ در پنل ادمین نه ساخت ممکن است نه ویرایش، امضا یا چاپ. هر ردیف یک نامه است و جزئیات کامل آن — تاریخچه، گیرندگان، نسخه‌ها و پیش‌نمایش کاغذ — در صفحهٔ «مشاهده» باز می‌شود.'],
        ['icon' => 'mail_lock', 'color' => 'error', 'label' => 'نامه‌های محرمانه', 'text' => 'موضوع و متن نامه‌های «محرمانه» و «فوق‌محرمانه» در جدول و صفحهٔ مشاهده دیده نمی‌شود و جای آن «نامه محرمانه» می‌آید؛ پیش‌نمایش کاغذ هم برای این‌ها نمایش داده نمی‌شود. تاریخچه، گیرندگان و سایر اطلاعات قابل پایش باقی می‌ماند؛ متن نظرها و دلیل ردّ هم در تاریخچه پوشانده می‌شود.'],
        ['icon' => 'history_edu', 'color' => 'tertiary', 'label' => 'تاریخچه، گیرندگان و نسخه‌ها', 'text' => 'صفحهٔ هر نامه رویدادهای آن را به زبان ساده می‌گوید (چه کسی، کِی، چه کاری کرد: ایجاد، ویرایش، نظر، تأیید، چاپ، امضای دستی، ثبت تحویل خارج از سامانه و…). کنار هر گیرندهٔ بیرونی روش تحویل، زمان ارسال و اولین باز شدن دیده می‌شود. نامهٔ تأییدشده‌ای که هیچ ایمیلی برایش ثبت نشده برچسب «ارسال دستی / بدون ایمیل» می‌گیرد. فهرست نسخه‌ها نویسندهٔ هر نسخه را هم نشان می‌دهد.'],
        ['icon' => 'account_tree', 'color' => 'primary', 'label' => 'دو نوع آدم و مسیر نامه', 'text' => 'هر نامه فقط دو نوع نفر دارد: «اطلاع‌رسانی» (می‌خواند و نظر می‌دهد) و «تأییدکننده» (می‌خواند، نظر می‌دهد، ویرایش می‌کند و امضا می‌کند؛ آخرین تأییدکننده امضاکنندهٔ نهایی است). ستون «مسیر» نشان می‌دهد نامه «ترتیبی» است (هر نفر پس از نفر قبلی) یا «موازی» (همه همزمان خبر می‌گیرند). ستون «نوع امضا» نشان می‌دهد امضا الکترونیکی است یا دستی. جدول عمداً ساده است: ستون‌های جزئی‌تر (جهت، طبقه‌بندی، فوریت، مسیر، نوع، ارسال، تأیید، در انتظار و نوع امضا) پنهان‌اند و از منوی ستون‌ها روشن می‌شوند.'],
        ['icon' => 'draft', 'color' => 'secondary', 'label' => 'پیش‌نویس‌های قدیمی و الگوها', 'text' => 'فیلتر «پیش‌نویس‌های قدیمی» نامه‌های نیمه‌تمامِ بیش از ' . convertToPersian(config('ai.correspondence.stale_draft_days')) . ' روز را نشان می‌دهد؛ نامه‌هایی که به «الگو» تبدیل شده‌اند هرگز قدیمی حساب نمی‌شوند. الگوها مال خود کاربران‌اند (شخصی یا برای واحدشان) و ادمین فقط دامنهٔ آن‌ها را می‌بیند؛ با فیلتر «الگو» می‌توانید فقط الگوها یا فقط نامه‌های عادی را ببینید.'],
        ['icon' => 'type_specimen', 'color' => 'primary', 'label' => 'سربرگ‌ها و شماره‌گذاری', 'text' => 'مدیریت سربرگ‌ها با دکمه‌های بالای همین صفحه باز می‌شود. شماره‌ها از الگوی هر سربرگ ساخته می‌شود؛ الگو باید {SEQ} یا {SEQ:n} (n یک یا دو رقم) داشته باشد و توکن‌های مجاز {PREFIX} {DEPT} {TYPE} {YYYY} {YY} {MM} {SEQ} {SEQ:n} هستند و پیش‌نمایش شمارهٔ بعدی زنده است. مقدار {PREFIX} را در فیلد «پیشوند» می‌نویسید (مثلاً PL)، {YYYY} سال شمسی لحظهٔ تأیید است و شمارنده هر نوروز از نو شروع می‌شود، {DEPT} کد واحد نویسنده و {TYPE} کد نوع نامه است. افزودن یا حذف یک توکن شمارندهٔ تازه‌ای باز می‌کند (از «شروع از»)، ولی تغییر متن ثابت یا تعداد رقم شمارنده را از نو نمی‌کند. «شروع از» فقط جلو می‌رود و دکمهٔ «ادامه از آخرین» آن را بعد از آخرین شمارهٔ گرفته‌شده می‌گذارد؛ نامهٔ باطل‌شده شمارهٔ خود را نگه می‌دارد. سربرگِ زیرمجموعه شماره‌گذاری سربرگ والد را ادامه می‌دهد (الگو، پیشوند و شمارنده را از والد می‌گیرد؛ مثلاً نسخهٔ A5 یک سربرگ A4). یک سربرگ می‌تواند سرصفحه یا پاصفحهٔ سربرگ دیگری را قرض بگیرد؛ سربرگی که قرض داده شده حذف نمی‌شود و زنجیرهٔ قرض‌گرفتن مجاز نیست. سربرگِ نوع «دبیرخانه» فقط نام، اندازه، زبان و شماره‌گذاری دارد (تنظیم صفحه ندارد). سربرگی که نامه یا زیرمجموعه دارد حذف نمی‌شود؛ به‌جای حذف آن را غیرفعال کنید، مگر اینکه سربرگ دیگری از آن قرض گرفته باشد که در این حالت نه حذف می‌شود و نه غیرفعال.'],
        ['icon' => 'tune', 'color' => 'tertiary', 'label' => 'تنظیم صفحه', 'text' => 'حاشیه‌ها (بر اساس سانتی‌متر؛ با تغییر اندازهٔ کاغذ به مقدار پیش‌فرض برمی‌گردند)، قلم و اندازه‌ها، جعبهٔ شماره/تاریخ/پیوست و بخش «تنظیم صفحه» (ارتفاع سرصفحه و پاصفحه، تکرار در هر صفحه، جای امضا و مُهر و رونوشت، حاشیهٔ جدول، قالب شمارهٔ صفحه، شفافیت نقش‌آب) همه روی خود سربرگ ذخیره می‌شود و چیزی در قالب نامه ثابت نیست. تصویر سربرگ (کلاهک، پابرگ، مُهر) تا ' . convertToPersian(config('ai.correspondence.upload.image_kb')) . ' کیلوبایت و ' . convertToPersian(config('ai.correspondence.upload.image_max_width')) . ' پیکسل پهنا می‌پذیرد و بهتر است بدون برچسب و نقطه‌چین باشد؛ برچسب‌ها را خود سامانه می‌کشد.'],
        ['icon' => 'workspace_premium', 'color' => 'secondary', 'label' => 'امضا و کد امضا', 'text' => 'در فرم ویرایش هر کاربر می‌توانید تصویر امضای او را بارگذاری کنید و یک «کد امضای سری» تعیین کنید؛ کد با نیاز به رمز خودتان ثبت می‌شود، ذخیره‌اش یک‌طرفه است و هرگز دوباره نمایش داده نمی‌شود؛ با گزینهٔ «حذف کد» (آن هم با رمز شما) می‌توان آن را پاک کرد. اگر کدی تعیین نشود، هیچ‌کس نمی‌تواند به‌جای این کاربر امضا کند. خود کاربر هم می‌تواند کدش را در پروفایل (زبانهٔ اعتبارنامه‌ها) با رمز ورود خودش تعیین یا پاک کند؛ تصویر امضا فقط از همین فرم ثبت می‌شود و فقط روی نامهٔ تأییدشده چاپ می‌شود. همین بخش گزینهٔ «دسترسی دبیرخانه» را هم دارد. نامهٔ امضاشده‌ای که تأیید شده با تغییر بعدی تصویر امضا یا سربرگ عوض نمی‌شود.'],
        ['icon' => 'sign_language', 'color' => 'tertiary', 'label' => 'دبیرخانه و امضای دستی', 'text' => 'در امضای دستی، نامه پس از تأیید نهایی شماره می‌خورد و منجمد می‌شود؛ سپس دبیرخانه آن را چاپ و برای امضای دستی می‌برد و رویدادهای «چاپ» و «امضای دستی» در تاریخچه ثبت می‌شود. تحویل یا پیگیریِ خارج از سامانه با یک رویداد عمومی ثبت می‌گردد. ثبت و ارجاع نامهٔ وارده فقط با دسترسی دبیرخانه و فقط در پنل کاربر ممکن است.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">mail</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">مکاتبات؛ پایش نامه‌های رسمی و مدیریت سربرگ‌ها</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        کاربران نامه‌های خود را در صفحهٔ <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">/correspondence</code> می‌سازند و برای تأیید و امضا ارجاع می‌دهند. شما در این صفحه فقط نتیجه را پایش می‌کنید و سربرگ‌ها و ابزارهای امضا را آماده می‌کنید.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">co_present</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">نکته‌های مهم</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($rows as $row)
                @php
                    $chipClasses = match ($row['color']) {
                        'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
                        'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
                        'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
                        'error' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
                    };
                @endphp
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $chipClasses }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $row['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $row['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $row['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
