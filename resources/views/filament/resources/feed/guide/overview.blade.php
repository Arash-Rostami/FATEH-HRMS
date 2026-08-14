<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">info</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">«فید» یک پست سازمانی است: متن، رسانه و optionally یک نظرسنجی</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر فید توسط یک کاربر منتشر می‌شود و در یکی از پنج دسته قرار می‌گیرد. کاربران فیدها را در پنل کاربری (زبانهٔ اخبار) می‌بینند و می‌توانند نظر بدهند، واکنش نشان دهند و در نظرسنجی رأی بدهند. شما در این صفحه کل فیدها را نظارت می‌کنید: محتوا را بازبینی، ویرایش یا حذف می‌کنید و خروجی اکسل می‌گیرید.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">category</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">دسته‌بندی‌های فید</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @php
                $cats = [
                    ['emoji' => '📢', 'label' => 'عمومی', 'hint' => 'اطلاع‌رسانی عمومی — بدون رفتار خاص.'],
                    ['emoji' => '📅', 'label' => 'رویداد', 'hint' => 'اطلاع‌رسانی رویداد.'],
                    ['emoji' => '🎂', 'label' => 'تولد', 'hint' => 'تبریک تولد همکار.'],
                    ['emoji' => '🏆', 'label' => 'سالگرد کاری', 'hint' => 'جشن سالگرد استخدام.'],
                    ['emoji' => '📊', 'label' => 'نظرسنجی', 'hint' => 'فقط این دسته وسیلهٔ نظرسنجی دارد: تک‌انتخابی/چندانتخابی و فعال/غیرفعال بودن نظر و واکنش.'],
                ];
            @endphp
            @foreach($cats as $c)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] text-[20px]">{{ $c['emoji'] }}</span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $c['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $c['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">widgets</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">اجزای یک فید</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                        <span class="material-symbols-rounded text-[22px]">edit_note</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">متن غنی</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">محتوا با ویرایشگر غنی (رنگ متن، تیتر، هم‌تراز، فهرست، نقل‌قول، هایلایت و پیوند) نوشته می‌شود و هنگام ذخیره از طریق سرویس پاک‌سازی، HTML ناخواسته پاک می‌شود.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                        <span class="material-symbols-rounded text-[22px]">photo_library</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">رسانه (تصویر + ویدیو)</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">تا {{ convertToPersian('8') }} تصویر و {{ convertToPersian('1') }} ویدیو. مسیرها در یک فیلد <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">media_paths</code> (آرایه) ذخیره می‌شوند و مدل بر اساس پسوند، تصویر و ویدیو را جدا می‌کند.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                        <span class="material-symbols-rounded text-[22px]">ballot</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">نظرسنجی (فقط دستهٔ نظرسنجی)</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">گزینه‌ها و تنظیمات در <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">poll_options</code> به‌صورت <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">[mode, comments, reactions, ...choices]</code> بسته‌بندی می‌شوند. برای دسته‌های دیگر این فیلد <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">null</code> می‌شود.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                        <span class="material-symbols-rounded text-[22px]">link</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">وابسته‌ها: نظر، واکنش، رأی</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">هر فید سه مدیریت ارتباط دارد: نظرات (زنجیره‌ای با پاسخ)، نظرسنجی‌ها (رأی‌های کاربران) و واکنش‌ها (ایموجی). شمارش هر سه در جدول و اینفولیست آماده است.</p>
                </div>
            </div>
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                حذف یک فید، همهٔ نظرات، واکنش‌ها و رأی‌های آن را هم پاک می‌کند (حذف آبشاری در مدل). خروجی اکسل فقط متن پاک‌شده و شمارش‌ها را صادر می‌کند، نه رسانه را.
            </p>
        </div>
    </div>
</div>