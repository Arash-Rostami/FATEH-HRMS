@php
    $topics = [
        ['icon' => 'bolt', 'color' => 'primary', 'label' => 'رویدادها', 'text' => 'ماژول و رویدادهایی را که باید باعث ارسال شوند انتخاب کنید، مانند ثبت، ویرایش یا حذف. سامانه با رخ دادن رویداد، شرط‌ها و گیرندگان قانون را بررسی می‌کند.'],
        ['icon' => 'alternate_email', 'color' => 'secondary', 'label' => 'روش‌های ارسال', 'text' => 'ایمیل، پیامک یا هر دو را انتخاب کنید. روش‌های غیرفعال برای سازمان در فهرست دیده نمی‌شوند. اگر روش انتخابی فعال باشد و تنظیمات سرویس ناقص باشد، علت ارسال ناموفق را در «گزارش ارسال» ببینید.'],
        ['icon' => 'edit_note', 'color' => 'primary', 'label' => 'نوشتن متن پیام', 'text' => 'عبارت‌هایی مثل {نام ماژول} را از فهرست زیر «موضوع پیام» بردارید و همراه با علامت‌های { } در متن بگذارید. سامانه هنگام ارسال، هر عبارت را با اطلاعات همان مورد جایگزین می‌کند.'],
        ['icon' => 'palette', 'color' => 'secondary', 'label' => 'ظاهر ایمیل', 'text' => '«ساده» فقط متن پیام است. «سازمانی» با رنگ اصلی و لوگوی سازمان نمایش داده می‌شود. این گزینه فقط روی ایمیل اثر دارد؛ پیامک همیشه متن ساده است. با «ارسال آزمایشی» می‌توانید ظاهر را در ایمیل خودتان ببینید.'],
        ['icon' => 'group', 'color' => 'tertiary', 'label' => 'گیرندگان', 'text' => 'دست‌کم یک گیرنده انتخاب کنید. می‌توانید تا ۱۰۰ کاربر مشخص و ۲۰ گیرندهٔ خارج از سامانه اضافه کنید. برای ارسال به یک واحد یا همهٔ کارکنان، گزینهٔ مربوط را انتخاب کنید. نشانی یا شمارهٔ تکراری فقط یک بار پیام می‌گیرد.'],
        ['icon' => 'visibility', 'color' => 'primary', 'label' => 'فقط با تغییر این اطلاعات', 'text' => 'برای رویداد «ویرایش»، مشخص کنید تغییر کدام اطلاعات مهم است. مثلاً برای ارسال هنگام زیاد شدن اولویت، اینجا «اولویت» را انتخاب کنید و در «شرط‌ها» بگذارید «اولویت برابر است با زیاد». سامانه هم تغییر اولویت و هم مقدار تازه را بررسی می‌کند.'],
        ['icon' => 'filter_alt', 'color' => 'tertiary', 'label' => 'شرط‌ها', 'text' => 'شرط‌ها مقدار ثبت‌شده برای اطلاعاتی مثل وضعیت یا واحد را بررسی می‌کنند. برای مثال، «واحد برابر است با فروش» قانون را به موارد واحد فروش محدود می‌کند. اگر چند شرط دارید، مشخص کنید همهٔ آن‌ها باید برقرار باشند یا یکی کافی است. اگر هنگام ارسال مورد دیگر با شرط‌ها مطابقت نداشته باشد، علت را در «گزارش ارسال» می‌بینید.'],
        ['icon' => 'schedule', 'color' => 'secondary', 'label' => 'فعال‌سازی و بازهٔ اعتبار', 'text' => 'قانون غیرفعال یا خارج از بازهٔ اعتبار پیام نمی‌فرستد. بازه در لحظهٔ رخ دادن رویداد بررسی می‌شود و پایان آن نمی‌تواند پیش از شروعش باشد.'],
        ['icon' => 'send', 'color' => 'tertiary', 'label' => 'ارسال آزمایشی', 'text' => 'پس از ذخیرهٔ قانون، «ایمیل آزمایشی» یا «پیامک آزمایشی» را بزنید. نمونه فقط به نشانی یا شمارهٔ خودتان فرستاده می‌شود. نتیجهٔ آزمایش همان‌جا نمایش داده می‌شود و شرط‌های قانون بررسی نمی‌شوند.'],
        ['icon' => 'outgoing_mail', 'color' => 'secondary', 'label' => 'ارسال فوری', 'text' => 'برای فرستادن یک پیام بدون ساخت قانون، «ارسال فوری» را بزنید. روش ارسال، گیرندگان، موضوع و متن پیام را وارد کنید. پیام در صف قرار می‌گیرد و با عنوان «ارسال دستی» ثبت می‌شود. هر مدیر در هر دقیقه می‌تواند تا ۳ ارسال فوری ثبت کند. نتیجه را در «گزارش ارسال» ببینید.'],
        ['icon' => 'receipt_long', 'color' => 'primary', 'label' => 'گزارش ارسال', 'text' => 'برای دیدن نتیجهٔ یک قانون، در صفحهٔ ویرایش آن «گزارش ارسال» را باز کنید. برای دیدن همهٔ ارسال‌ها، از دکمهٔ «گزارش ارسال» بالای فهرست قانون‌ها استفاده کنید.'],
        ['icon' => 'fact_check', 'color' => 'secondary', 'label' => 'خواندن نتیجهٔ ارسال', 'text' => '«خلاصهٔ اجرا» تعداد گیرندگان و نتیجهٔ هر نوبت را نشان می‌دهد. «ناموفق» یعنی روند ارسال با خطا روبه‌رو شده است. «ارسال‌نشده» یعنی سامانه از ارسال به آن گیرنده صرف‌نظر کرده، مثلاً چون شمارهٔ همراه ثبت نشده است. علت ثبت‌شده کنار هر مورد دیده می‌شود. «ارسال‌شده» وضعیت فرستادن پیام را نشان می‌دهد. دریافت یا خواندن پیام در این گزارش ثبت نمی‌شود.'],
        ['icon' => 'schedule_send', 'color' => 'secondary', 'label' => 'پیگیری ارسال', 'text' => 'ارسال به تعداد زیاد ممکن است چند دقیقه طول بکشد. یک ایمیل و یک پیامک برای یک نفر، دو ارسال حساب می‌شود. با «مشاهده» در خلاصهٔ اجرا، تا ۵۰ مورد اولِ ناموفق یا ارسال‌نشده را ببینید. برای بررسی گیرندگان، فیلتر «وضعیت» را روی «همه» بگذارید. گزارش‌ها پس از ۹۰ روز پاک می‌شوند.'],
        ['icon' => 'groups', 'color' => 'tertiary', 'label' => 'ارسال به واحدها یا همهٔ کارکنان', 'text' => '«همهٔ کارکنان» یا «واحدهای انتخابی» را انتخاب کنید تا سامانه فهرست گیرندگان را هنگام ارسال آماده کند. اگر تعداد گیرندگان از ۵٬۰۰۰ نفر بیشتر شود، هیچ پیامی فرستاده نمی‌شود و دلیل آن در گزارش ثبت می‌شود.'],
        ['icon' => 'sms', 'color' => 'secondary', 'label' => 'پیامک تقویم', 'text' => 'پیامک‌های تبریک تولد و سالگرد کاری که از تقویم یا پیام‌رسان فرستاده می‌شوند با منبع «پیامک تقویم» ثبت می‌شوند. برای دیدن آن‌ها، در «گزارش ارسال» همین منبع را انتخاب کنید و وضعیت را روی «همه» بگذارید.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">campaign</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">«قوانین اعلان» — ارسال خودکار ایمیل و پیامک هنگام تغییر داده‌ها</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر قانون می‌گوید: «وقتی یک مورد از این ماژول ایجاد، ویرایش یا حذف شد، این پیام را برای این گیرندگان بفرست». در متن پیام می‌توانید اطلاعات خودکار بگذارید؛ مثلاً
        <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">{نام ماژول}</code>،
        <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">{نوع تغییر}</code>،
        <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">{انجام‌دهنده}</code>
        یا عنوان اطلاعات همان ماژول؛ فهرست کامل زیر «موضوع پیام» نمایش داده می‌شود.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">menu_book</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">اجزای یک قانون</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($topics as $t)
                @php
                    $chip = match ($t['color']) {
                        'primary'   => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
                        'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
                        'tertiary'  => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
                    };
                @endphp
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl {{ $chip }}">
                            <span class="material-symbols-rounded text-[22px]">{{ $t['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $t['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $t['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-error-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-error-container)]">warning</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-error-container)]">هشدار</p>
        </div>
        <div class="px-5 py-4 flex flex-col gap-2">
            <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] leading-6">
                قانون ویرایش با گیرندگان زیاد، با هر بار ذخیرهٔ یک مورد برای همهٔ گیرندگان پیام می‌فرستد. برای ماژول‌هایی که زیاد تغییر می‌کنند، حتماً از «فقط با تغییر این اطلاعات» و بازهٔ اعتبار استفاده کنید.
            </p>
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">info</span>
                ویرایش یا حذف گروهیِ موردها که یکجا و بدون ذخیرهٔ تک‌تک آن‌ها انجام شود، پیامی نمی‌فرستد.
            </p>
        </div>
    </div>
</div>
