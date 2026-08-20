@php
    $showFilterHint = $showFilterHint ?? true;
    $tierTexts = [
        \App\Enums\SkillTier::Endorsed->value => 'حداقل ' . \App\Models\SkillUser::ENDORSEMENT_SATURATION_CAP . ' همکار این مهارت را در این فرد تأیید کرده‌اند.',
        \App\Enums\SkillTier::Active->value => 'این همکار اخیراً (در بازهٔ زمانی فعال) از این مهارت استفاده کرده است.',
        \App\Enums\SkillTier::Unused->value => 'این مهارت با موفقیت ثبت شده و پتانسیل به‌کارگیری در فعالیت‌های آتی و دریافت تأییدیه را دارد.',
    ];

    $tabs = [
        ['id' => 'levels', 'icon' => 'workspace_premium', 'label' => 'سطوح'],
        ['id' => 'signals', 'icon' => 'military_tech', 'label' => 'نشان‌ها'],
        ['id' => 'structure', 'icon' => 'account_tree', 'label' => 'ساختار'],
        ['id' => 'notes', 'icon' => 'info', 'label' => 'نکات'],
    ];
@endphp

<div x-data="{ tab: 'levels' }">
    <div class="flex p-1 mb-5 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
        @foreach($tabs as $t)
            <button
                type="button"
                @click="tab = '{{ $t['id'] }}'"
                :class="tab === '{{ $t['id'] }}'
                    ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                    : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                class="flex-1 flex flex-col items-center justify-center gap-0.5 px-1.5 py-2 rounded-xl text-[11px] font-bold transition-all duration-200"
            >
                <span class="material-symbols-rounded text-[18px]">{{ $t['icon'] }}</span>
                <span class="leading-tight text-center">{{ $t['label'] }}</span>
            </button>
        @endforeach
    </div>

    <div x-show="tab === 'levels'" x-cloak class="space-y-2">
        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1 mb-1">
            مهارت‌ها بر اساس میزان تأیید همکاران و تازگی استفاده، به‌ترتیب تأییدشده ← فعال ← آماده مشارکت مرتب می‌شوند و نشان رنگی زیر روی هر کارت نمایش داده می‌شود.
        </p>

        @foreach(\App\Enums\SkillTier::cases() as $tier)
            <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $tier->badgeClasses() }}">
                    <span class="material-symbols-rounded text-[16px]">{{ $tier->icon() }}</span>
                </div>
                <div class="min-w-0">
                    <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">{{ $tier->label() }}</p>
                    <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $tierTexts[$tier->value] }}</p>
                </div>
            </div>
        @endforeach

        <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ (new \App\Models\SkillUser())->dormantBadgeClasses() }}">
                <span class="material-symbols-rounded text-[16px]">history</span>
            </div>
            <div class="min-w-0">
                <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">کم‌فعالیت</p>
                <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">مهارت «تأییدشده»ای که بیش از {{ \App\Models\SkillUser::ACTIVE_WINDOW_DAYS }} روز از آخرین استفادهٔ ثبت‌شده‌اش گذشته؛ همچنان در بالای فهرست می‌ماند اما کم‌رنگ‌تر نمایش داده می‌شود.</p>
            </div>
        </div>
    </div>

    <div x-show="tab === 'signals'" x-cloak class="space-y-2">
        @php
            $silverSample = new \App\Models\SkillUser(['endorsements_count' => 1]);
            $goldSample = new \App\Models\SkillUser(['endorsements_count' => \App\Models\SkillUser::ENDORSEMENT_SATURATION_CAP]);
        @endphp
        <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
            <div class="flex items-center gap-1.5 shrink-0">
                <span class="flex h-8 w-8 items-center justify-center rounded-full {{ $silverSample->endorsementMetalClasses() }}">
                    <span class="material-symbols-rounded text-[16px]" style="font-variation-settings:'FILL' 1">military_tech</span>
                </span>
                <span class="flex h-8 w-8 items-center justify-center rounded-full {{ $goldSample->endorsementMetalClasses() }}">
                    <span class="material-symbols-rounded text-[16px]" style="font-variation-settings:'FILL' 1">military_tech</span>
                </span>
            </div>
            <div class="min-w-0">
                <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">نشان نقره‌ای و طلایی تأیید</p>
                <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">نشان نقره‌ای («تأیید تک‌نفره» یا «تأیید چندنفره» روی هاور) یعنی این مهارت را حداقل یک همکار تأیید کرده اما هنوز به آستانهٔ {{ \App\Models\SkillUser::ENDORSEMENT_SATURATION_CAP }} تأیید نرسیده؛ با رسیدن به این آستانه، نشان طلایی می‌شود و مهارت به سطح «تأییدشده» ارتقا می‌یابد.</p>
            </div>
        </div>

        <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[var(--md-sys-color-primary)]/10 text-[var(--md-sys-color-primary)]">
                <span class="material-symbols-rounded text-[16px]">school</span>
            </div>
            <div class="min-w-0">
                <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">آمادهٔ راهنمایی</p>
                <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">با فعال‌کردن گزینهٔ «آماده راهنمایی» کنار جستجوی مهارت، فقط همکارانی نمایش داده می‌شوند که مایل به راهنمایی دیگران در این مهارت هستند.</p>
            </div>
        </div>

        <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[var(--tool-gold-bg)] text-[var(--tool-gold-text)]">
                <span class="material-symbols-rounded text-[16px]">celebration</span>
            </div>
            <div class="min-w-0">
                <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">مناسبت‌های امروز</p>
                <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">نوار بالای فهرست فقط وقتی نمایش داده می‌شود که تاریخ تولد یا استخدامِ ثبت‌شده در پروفایل یک همکار دقیقاُ امروز باشد — نه یک روز نزدیک. کارمندان با وضعیت «پایان‌یافته» و کاربران نوع «مهمان» هرگز نمایش داده نمی‌شوند.</p>
            </div>
        </div>

        <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[var(--md-sys-color-primary)]/10 text-[var(--md-sys-color-primary)]">
                <span class="material-symbols-rounded text-[16px]">domain</span>
            </div>
            <div class="min-w-0">
                <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">نشان رزرو امروز</p>
                <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">وقتی همکاری امروز میز کار، پارکینگ یا اتاق جلسه‌ای را رزرو کرده باشد، این نشان روی کارتش ظاهر می‌شود؛ نگه‌داشتن نشانگر روی آن جزئیات کامل منبع (طبقه/واحد/داخلی برای میز کار، یا کارت پارکینگ، یا ظرفیت و برنامهٔ اتاق جلسه) را در راهنما نشان می‌دهد.</p>
            </div>
        </div>
    </div>

    <div x-show="tab === 'structure'" x-cloak class="space-y-2">
        <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[var(--md-sys-color-primary)]/10 text-[var(--md-sys-color-primary)]">
                <span class="material-symbols-rounded text-[16px]">account_tree</span>
            </div>
            <div class="min-w-0">
                <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">ساختار سازمانی</p>
                <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">نمای ساختاری، همکاران را بر اساس دپارتمان و رتبهٔ شغلی چیده می‌شود. رئیس هیئت مدیره و مدیرعامل در رأس سازمان، و در هر دپارتمان بالاترین رتبه به‌عنوان سرپرست آن دپارتمان نمایش داده می‌شود و سایر اعضا بر اساس رتبه پایین‌تر می‌آیند.</p>
            </div>
        </div>

        <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[var(--md-sys-color-primary)]/10 text-[var(--md-sys-color-primary)]">
                <span class="material-symbols-rounded text-[16px]">stacked_bar_chart</span>
            </div>
            <div class="min-w-0">
                <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">سطح‌بندی و زیرمجموعه‌کردن دپارتمان‌ها</p>
                <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">در تنظیمات ادمین، هر دپارتمان یک «سطح نمایش» (۰، ۱ یا ۲) و به‌دلخواه یک «زیرمجموعه واحد» دارد. سطح ۰ یعنی آن دپارتمان اصلاً در نمودار نمایش داده نمی‌شود؛ همهٔ دپارتمان‌ها روی همان خط پایه کنار هم می‌نشینند و سطح ۲ فقط کمی پایین‌تر با یک رابط بلندتر جدا می‌شود، نه در ردیف کاملاً مجزا. دپارتمانی که زیرمجموعه دپارتمان دیگری تعریف شده باشد، به‌صورت تودرتو زیر همان دپارتمان نمایش داده می‌شود.</p>
            </div>
        </div>

        <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">
                <span class="material-symbols-rounded text-[16px]">apartment</span>
            </div>
            <div class="min-w-0">
                <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">واحد و بخش داخل دپارتمان</p>
                <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">اگر واحد یا بخشِ همکاری در مشخصات او تعیین شده باشد، به‌جای کارت ساده در فهرست اعضا، داخل یک خوشهٔ برچسب‌دار با نام همان واحد/بخش نمایش داده می‌شود تا عضویت مستقیم در دپارتمان از عضویت در واحد/بخش زیرمجموعه آن قابل تشخیص باشد.</p>
            </div>
        </div>

        <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">
                <span class="material-symbols-rounded text-[16px]">supervisor_account</span>
            </div>
            <div class="min-w-0">
                <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">سرپرست دپارتمان (بالفعل)</p>
                <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">سرپرست هر دپارتمان از روی پایگاه دادهٔ «مدیر» تعیین نمی‌شود؛ بلکه بالاترین رتبهٔ فعالِ حاضر در همان دپارتمان به‌طور خودکار به‌عنوان سرپرست در نظر گرفته می‌شود. بنابراین اگر مدیر اصلی غایب یا غیرفعال باشد، ارشد بعدی به‌جای او نشان داده می‌شود.</p>
            </div>
        </div>

        <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                <span class="material-symbols-rounded text-[16px]">unfold_more</span>
            </div>
            <div class="min-w-0">
                <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">باز و بسته‌کردن دپارتمان‌ها</p>
                <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">برای دپارتمان‌های بزرگ می‌توانید روی عنوان دپارتمان یا نشان <span class="material-symbols-rounded text-[12px] align-middle">unfold_more</span> روی کارت سرپرست بزنید تا اعضا جمع شوند؛ وضعیت باز/بسته‌شدن ذخیره می‌ماند و با باز کردن پنجرهٔ جزئیات همکار یا تغییر فیلترها از بین نمی‌رود.</p>
            </div>
        </div>

        <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[var(--md-sys-color-primary)]/10 text-[var(--md-sys-color-primary)]">
                <span class="material-symbols-rounded text-[16px]">auto_stories</span>
            </div>
            <div class="min-w-0">
                <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">جزئیات و زیرمجموعه‌ها</p>
                <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">با کلیک روی هر کارت، پنجرهٔ «درباره من» باز می‌شود و بخش «زیرمجموعه‌ها»، افراد زیرنظر او را بر اساس رتبه نشان می‌دهد؛ کلیک روی هر زیرمجموعه نیز پنجرهٔ همان همکار را باز می‌کند.</p>
            </div>
        </div>

        <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]">
                <span class="material-symbols-rounded text-[16px]">filter_alt</span>
            </div>
            <div class="min-w-0">
                <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">اثر فیلترها بر نمای ساختاری</p>
                <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">فیلتر وضعیت حضور، جستجوی مهارت و دسته‌بندی‌ها روی نمای ساختاری هم اعمال می‌شود؛ بنابراین با فعال‌کردن یک فیلتر، فقط بخشی از ساختار که شرط آن را برآورده می‌کند نمایش داده می‌شود.</p>
            </div>
        </div>
    </div>

    <div x-show="tab === 'notes'" x-cloak class="space-y-2">
        @if($showFilterHint)
            <div class="flex items-start gap-2 px-1">
                <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">info</span>
                <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">نشان سطح مهارت فقط روی کارت‌هایی نمایش داده می‌شود که یک فیلتر مهارت فعال است؛ در حالت عادی (بدون فیلتر مهارت) این نشان دیده نمی‌شود.</p>
            </div>
        @endif
    </div>
</div>
