@php
    $showFilterHint = $showFilterHint ?? true;
    $tierTexts = [
        \App\Enums\SkillTier::Endorsed->value => 'حداقل ' . \App\Models\SkillUser::ENDORSEMENT_SATURATION_CAP . ' همکار این مهارت را در این فرد تأیید کرده‌اند.',
        \App\Enums\SkillTier::Active->value => 'این همکار اخیراً (در بازهٔ زمانی فعال) از این مهارت استفاده کرده است.',
        \App\Enums\SkillTier::Unused->value => 'این مهارت ثبت شده، اما هنوز به آستانهٔ تأیید نرسیده و استفادهٔ اخیری برایش ثبت نشده است.',
    ];
@endphp

<div class="space-y-2">
    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1 mb-1">
        مهارت‌ها بر اساس میزان تأیید همکاران و تازگی استفاده، به‌ترتیب تأییدشده ← فعال ← بدون استفاده مرتب می‌شوند و نشان رنگی زیر روی هر کارت نمایش داده می‌شود.
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

    @if($showFilterHint)
        <div class="mt-4 pt-3 border-t border-[var(--md-sys-color-outline-variant)]/40">
            <div class="flex items-start gap-2 px-1">
                <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">info</span>
                <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">نشان سطح مهارت فقط روی کارت‌هایی نمایش داده می‌شود که یک فیلتر مهارت فعال است؛ در حالت عادی (بدون فیلتر مهارت) این نشان دیده نمی‌شود.</p>
            </div>
        </div>
    @endif
</div>
