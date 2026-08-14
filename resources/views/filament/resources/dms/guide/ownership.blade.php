@php
    $rows = [
        [
            'icon' => 'corporate_fare',
            'label' => 'واحدهای مالک (owners)',
            'hint' => 'فیلد <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">owners</code> یک آرایهٔ JSON از کدهای واحد است. انتخاب «همه واحدها» مقدار ویژهٔ <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">ALL</code> را ذخیره می‌کند — این کلمهٔ کلیدی است، نه یک واحد واقعی. اسکوپ <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">visibleToUser</code> با <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">orWhereJsonContains(owners, ALL)</code> سند را به کل سازمان منتشر می‌کند.',
        ],
        [
            'icon' => 'person',
            'label' => 'کاربران اختصاصی (users)',
            'hint' => 'فیلد <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">users</code> برای زمانی است که یک فرد خاص (بدون توجه به واحدش) باید سند را ببیند — مثلاً یک مدیر پروژه از واحد دیگر. این کاربران در کنار اعضای واحدهای مالک، سند را دریافت می‌کنند.',
        ],
        [
            'icon' => 'visibility',
            'label' => 'پیش‌نمایش کاربران دریافت‌کننده',
            'hint' => 'پس از انتخاب واحدها، فیلد غیرفعالِ «کاربران واحدهای انتخاب‌شده» خودکار پر می‌شود (<code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">GenerateOwnerPreview</code>). این فقط نمایش است و ذخیره نمی‌شود (<code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">dehydrated(false)</code>) — به شما نشان می‌دهد قبل از ذخیره، دقیقاً چه کسی سند را خواهد دید.',
        ],
        [
            'icon' => 'groups',
            'label' => 'فیلتر زبانهٔ فهرست بر اساس واحد',
            'hint' => 'فیلتر «واحد سازمانی» در فهرست، با <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">orWhereJsonContains</code> روی هر کد کار می‌کند و سندهای <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">ALL</code> را هم شامل می‌شود — یعنی فیلتر کردن روی واحد «مالی»، اسناد سراسری را هم می‌آورد.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">share</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">مالکیت و دسترسی تعیین می‌کند سند به کی می‌رسد</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر سند با ترکیب «واحدهای مالک» + «کاربران اختصاصی» توزیع می‌شود. هیچ خروجی اعلانِ فعال نیست — سند به‌محض «فعال» شدن در کارتابل کاربران ظاهر می‌شود و وضعیت تأیید آنها در جدول <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">reads</code> ثبت می‌شود.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">build</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">اجزای مالکیت</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($rows as $r)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $r['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $r['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $r['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                برای انتشار سراسری، فقط «همه واحدها» را انتخاب کنید — نیازی به افزودن تک‌تک کاربران نیست؛ فیلد <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">users</code> فقط برای استثناهای فردی است.
            </p>
        </div>
    </div>
</div>