@php
    $ranks = [
        ['rank' => 1, 'code' => 'chairman', 'label' => 'رئیس هیئت مدیره', 'color' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]', 'note' => 'بالاترین رتبه — همراهِ مدیرعامل.'],
        ['rank' => 1, 'code' => 'ceo', 'label' => 'مدیرعامل', 'color' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]', 'note' => 'همان رتبهٔ رئیس هیئت مدیره. مدیرعامل با کد واحد MA شناسایی می‌شود.'],
        ['rank' => 2, 'code' => 'c-manager', 'label' => 'مدیر ارشد', 'color' => 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]', 'note' => 'بالای مدیران.'],
        ['rank' => 3, 'code' => 'manager', 'label' => 'مدیر', 'color' => 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]', 'note' => 'مدیر واحد.'],
        ['rank' => 4, 'code' => 'supervisor', 'label' => 'سرپرست', 'color' => 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]', 'note' => '—'],
        ['rank' => 5, 'code' => 'senior', 'label' => 'کارشناس ارشد', 'color' => 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]', 'note' => '—'],
        ['rank' => 6, 'code' => 'expert', 'label' => 'کارشناس', 'color' => 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]', 'note' => '—'],
        ['rank' => 7, 'code' => 'employee', 'label' => 'کارمند', 'color' => 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]', 'note' => 'پایین‌ترین رتبه.'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">account_tree</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">سمت‌ها هشت رتبه دارند؛ واحد تعیین می‌کند چه‌کس رئیسِ آن واحد است</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        سمتِ هر پرسنل روی پروفایل او ذخیره می‌شود (فیلد position) و یکی از هشت مقدار جدول زیر است. رتبه از ۱ (بالاترین) تا ۷ (پایین‌ترین) شمرده می‌شود؛ «رئیس هیئت مدیره» و «مدیرعامل» هر دو رتبهٔ ۱ دارند. واحد سازمانیِ کاربر (کد واحد روی پروفایل) تعیین می‌کند که در همان واحد، آیا کسی با رتبهٔ بالاتر از او هست یا نه — اگر نبود، همان کاربر «رئیس واحد» محسوب می‌شود.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">leaderboard</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">هشت سمت و رتبهٔ آنها</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($ranks as $r)
                <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl {{ $r['color'] }}">
                            <span class="text-[13px] font-black font-mono">{{ convertToPersian((string) $r['rank']) }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $r['label'] }}</p>
                            <code class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/50">{{ $r['code'] }}</code>
                        </div>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{{ $r['note'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                رتبه‌ها در پروفایل کاربر ذخیره می‌شوند، نه در این صفحه — اینجا فقط واحدها تعریف می‌شوند؛ سمتِ پرسنل از صفحهٔ «کاربران» و پروفایل اوセット می‌شود.
            </p>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">balance</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">قواعد رئیسِ واحد و مدیرعامل</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">work</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">رئیس واحد (isDeptHead)</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">کاربر «رئیس واحد» است اگر در همان واحدِ سازمانی، هیچ کاربرِ فعالِ دیگری با رتبهٔ بالاتر (عدد کوچک‌تر) وجود نداشته باشد. مثلاً اگر در واحدی یک «مدیر» (رتبهٔ ۳) و یک «سرپرست» (رتبهٔ ۴) باشند، مدیر رئیسِ واحد است. اگر کسی بالاتر از مدیر نباشد، خودِ مدیر رئیس می‌شود.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">star</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">کد واحد MA برای مدیرعامل</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">شناسایی مدیرعامل بر اساس کد واحد است، نه فقط سمت: کاربری «مدیرعامل» است که در واحد با کد <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">MA</code> قرار دارد. اگر می‌خواهید کاربری مدیرعامل شناخته شود، پروفایل او را به واحدی با کد MA و سمتِ ceo وصل کنید.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-[20px]">group</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">اتصال پرسنل از طریق پروفایل</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">وصلِ یک کاربر به واحد در این صفحه انجام نمی‌شود — فیلد <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">department_id</code> روی پروفایل کاربر (در صفحهٔ «کاربران») تنظیم می‌شود و باید دقیقاً برابر با «کد» یکی از واحدهای همین جدول باشد.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3 px-1 pt-2">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">stacked_bar_chart</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">سلسله‌مراتبِ خودِ واحدها در نمودار سازمانی — جدا از رتبهٔ پرسنل بالا</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        جدول بالا نشان می‌دهد «چه کسی رئیسِ یک واحد است»؛ این بخش نشان می‌دهد «خودِ واحدها در نمودار سازمانی کجا نمایش داده می‌شوند» — دو مفهوم کاملاً مستقل، هرکدام با فیلدهای خودشان.

    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">layers</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">فیلد «سطح نمایش در نمودار سازمانی» (level)</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)] shrink-0"><span class="text-[13px] font-black font-mono">۰</span></span>
                <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">این واحد اصلاً در نمودار سازمانی نمایش داده نمی‌شود — حالت پیش‌فرض برای واحدهای تازه. برای نمایش، سطح را به ۱ یا ۲ تغییر دهید.</p>
            </div>
            <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] shrink-0"><span class="text-[13px] font-black font-mono">۱</span></span>
                <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">یک ردیف زیر رأس سازمان (رئیس هیئت مدیره/مدیرعامل) نمایش داده می‌شود.</p>
            </div>
            <div class="flex items-start gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] shrink-0"><span class="text-[13px] font-black font-mono">۲</span></span>
                <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">دو ردیف زیر رأس سازمان، در ردیفِ جداگانهٔ «سطح ۲» نمایش داده می‌شود.</p>
            </div>
        </div>
    </div>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-tertiary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-tertiary-container)]">account_tree</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-tertiary-container)]">فیلد «زیرمجموعه واحد» (subordinate_to)</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] shrink-0"><span class="material-symbols-rounded text-[20px]">subdirectory_arrow_left</span></span>
                <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">اگر واحدی زیرمجموعهٔ واحد دیگری تعریف شود، به‌جای گرفتن ردیف مستقل، به‌صورت تودرتو زیر همان واحد والد نمایش داده می‌شود — سطح (level) آن دیگر نمی‌تواند ۰ باشد و در صورت انتخاب زیرمجموعه، سطح ۰ به‌طور خودکار به ۱ اصلاح می‌شود.</p>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] shrink-0"><span class="material-symbols-rounded text-[20px]">block</span></span>
                <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">یک واحد نمی‌تواند زیرمجموعهٔ خودش یا زیرمجموعهٔ یکی از زیرمجموعه‌های خودش باشد (چرخه) — سامانه این حالت را رد می‌کند.</p>
            </div>
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                رئیس هیئت مدیره و مدیرعامل بر اساس سمت (رتبهٔ ۱) همیشه در رأس نمودار می‌مانند، مستقل از سطح یا زیرمجموعهٔ واحدشان — حتی اگر واحد «مدیریت» (کد MA) سطح ۱ یا ۲ داشته باشد، این دو نفر داخل کارت آن واحد تکرار نمی‌شوند.
            </p>
        </div>
    </div>
</div>