@php
    $legend = \App\Enums\ReservationError::legend();
@endphp

<div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade" dir="rtl">

    <div class="px-5 py-4 bg-[var(--md-sys-color-surface-container-low)] border-b border-[var(--md-sys-color-outline-variant)]">
        <h3 class="text-[14px] font-black text-[var(--md-sys-color-on-surface)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[22px] text-[var(--md-sys-color-primary)]">error</span>
            کدهای خطای رزرو
        </h3>
        <p class="text-[12px] font-semibold text-[var(--md-sys-color-on-surface-variant)] mt-1">هر خطا در پیام کاربر به‌شکل [ERR-XXX] نمایش داده می‌شود. ستون «کلید سیاست» نشان می‌دهد کدام قانون این خطا را تولید کرده است.</p>
    </div>

    <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
        @foreach($legend as $code => $row)
            <div class="flex items-start gap-5 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-black tracking-widest bg-danger-200 text-[var(--md-sys-color-on-error-container)] shadow-sm">
                        {{ $code }}
                    </span>
                </div>

                <div class="flex-1 flex flex-col gap-2">
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] uppercase tracking-widest font-bold text-[var(--md-sys-color-outline)]">کلید سیاست</span>
                        @if($row['policy'] === '—')
                            <span class="text-[var(--md-sys-color-outline-variant)] text-xs font-bold">—</span>
                        @else
                            <code class="px-2.5 py-1 rounded-lg text-[11px] font-mono font-bold bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] border border-[var(--md-sys-color-outline-variant)]/50">{{ $row['policy'] }}</code>
                        @endif
                    </div>

                    <p class="text-[13px] text-[var(--md-sys-color-on-surface-variant)] leading-relaxed font-semibold">
                        {{ $row['hint'] }}
                    </p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="px-6 py-4 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
        <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">lightbulb</span>
            کد خطا در پیام کاربر به شکل <code class="mx-1 px-2 py-0.5 rounded-md bg-red-200 text-[var(--md-sys-color-on-surface)] font-mono tracking-widest border border-[var(--md-sys-color-outline-variant)] shadow-sm">[ERR-XXX]</code> نمایش داده می‌شود.
        </p>
    </div>
</div>