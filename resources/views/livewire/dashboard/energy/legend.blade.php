@php
    $rows = [
        ['icon' => 'event_repeat', 'color' => 'primary', 'label' => 'دورهٔ خنک‌سازی ۲۵ روزه', 'text' => 'پس از تکمیل پرسشنامه، تا ۲۵ روز فرم بسته می‌ماند و فقط تب «نتایج» در دسترس است؛ پس از این مدت پرسشنامهٔ تازه باز می‌شود.'],
        ['icon' => 'checklist', 'color' => 'tertiary', 'label' => 'گزینهٔ آخر هر بخش، انحصاری است', 'text' => 'در هر بخش، انتخاب گزینهٔ آخر (معمولاً «هیچ‌کدام») سایر گزینه‌های همان بخش را خودکار پاک می‌کند؛ برعکس، انتخاب هر گزینهٔ دیگر گزینهٔ آخر را پاک می‌کند.'],
    ];
@endphp

<div class="space-y-2">
    @foreach($rows as $row)
        @php
            $chipClasses = match ($row['color']) {
                'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
                'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
            };
        @endphp
        <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipClasses }}">
                <span class="material-symbols-rounded text-[16px]">{{ $row['icon'] }}</span>
            </div>
            <div class="min-w-0">
                <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">{{ $row['label'] }}</p>
                <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $row['text'] }}</p>
            </div>
        </div>
    @endforeach
</div>
