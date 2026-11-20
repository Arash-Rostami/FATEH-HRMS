        @if($this->activeLimitUsage !== null || $this->cancelLimitUsage !== null)
        @php($tones = [
            'full' => ['text' => 'text-[var(--md-sys-color-error)]', 'fill' => 'bg-[var(--md-sys-color-error)]'],
            'warn' => ['text' => 'text-[var(--tool-gold-color)]', 'fill' => 'bg-[var(--tool-gold-color)]'],
            'ok' => ['text' => 'text-[var(--md-sys-color-on-surface-variant)]', 'fill' => 'bg-[var(--md-sys-color-primary)]'],
        ])
        <div wire:key="reservation-limits" class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6 animate-slide-up-fade">
        @if($this->activeLimitUsage !== null)
            @php($usage = $this->activeLimitUsage)
            @php($tone = $tones[$usage['level']])
            @php($pct = $usage['max'] > 0 ? min(100, (int) round($usage['count'] * 100 / $usage['max'])) : 100)
            <x-ui.quota-meter wire:key="reservation-limit-usage" warn-key="reservation-quota-left"
                label="سهمیهٔ رزرو در ماه انتخابی" :used="$usage['count']" :cap="$usage['max']" :pct="$pct" :tone="$tone"
                :warn="$usage['level'] === 'warn' ? 'فقط '.convertToPersian((string) $usage['left']).' رزرو دیگر باقی مانده است' : null">
                <p class="mt-1.5 text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)]">رزروهای فعال و آزادشده در ماه شروع حساب می‌شوند؛ رزروهای لغوشده حساب نمی‌شوند.</p>
                @if($usage['reset'])
                    <p wire:key="reservation-quota-reset" class="mt-1.5 text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)]">سهمیه از {{ $usage['reset']['date'] }} بازمی‌گردد ({{ convertToPersian((string) $usage['reset']['days']) }} روز دیگر)</p>
                @endif
                @if($this->lastMinuteOpensAt)
                    <p wire:key="reservation-last-minute-opens" class="mt-1.5 text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)]">تاریخ انتخابی شما از {{ $this->lastMinuteOpensAt }} برای رزرو لحظه‌آخری باز می‌شود.</p>
                @endif
                @if($usage['near'] && $this->lastMinuteHours !== null)
                    <p wire:key="reservation-last-minute-hint" class="mt-1.5 text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)]">اگر مورد آزاد پیدا شود و سایر قوانین اجازه دهند، برای رزروی با شروع تا {{ convertToPersian((string) $this->lastMinuteHours) }} ساعت آینده، سقف ماهانه مانع نیست.</p>
                @endif
            </x-ui.quota-meter>
        @endif

        @if($this->cancelLimitUsage !== null)
            @php($cancelUsage = $this->cancelLimitUsage)
            @php($cancelTone = $tones[$cancelUsage['level']])
            @php($cancelPct = $cancelUsage['max'] > 0 ? min(100, (int) round($cancelUsage['count'] * 100 / $cancelUsage['max'])) : 100)
            <x-ui.quota-meter wire:key="reservation-cancel-usage" icon="event_busy"
                :label="'لغوهای '.convertToPersian((string) $cancelUsage['days']).' روز اخیر'"
                :used="$cancelUsage['count']" :cap="$cancelUsage['max']" :pct="$cancelPct" :tone="$cancelTone">
                <p class="mt-1.5 text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)]">{{ $cancelUsage['max'] === 0 ? 'لغو برای این نوع رزرو مجاز نیست؛ این مورد مانع رزرو تازه نیست.' : 'با پر شدن این سقف، لغو بیشتر و رزرو تازه تا کاهش تعداد لغوهای محاسبه‌شده ممکن نیست.' }}</p>
                @if($cancelUsage['dropsInDays'])
                    <p wire:key="reservation-cancel-drops" class="mt-1.5 text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)]">یکی از لغوهای شما {{ convertToPersian((string) $cancelUsage['dropsInDays']) }} روز دیگر از شمارش خارج می‌شود</p>
                @endif
            </x-ui.quota-meter>
        @endif
        </div>
        @endif
