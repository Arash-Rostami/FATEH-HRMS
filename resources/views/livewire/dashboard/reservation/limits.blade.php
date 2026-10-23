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
            <div wire:key="reservation-limit-usage">
                <div class="flex items-center gap-2 mb-1.5">
                    <span
                        class="material-symbols-rounded text-[16px] {{ $tone['text'] }}">stacks</span>
                    <span
                        class="text-[11px] font-semibold {{ $tone['text'] }}">
                        رزروهای فعال این ماه: {{ convertToPersian((string) $usage['count']) }} / {{ convertToPersian((string) $usage['max']) }}
                    </span>
                </div>
                <div class="h-1.5 w-full rounded-full bg-[var(--md-sys-color-surface-variant)] overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-500 {{ $tone['fill'] }}"
                        style="width: {{ $pct }}%"></div>
                </div>
                @if($usage['level'] === 'warn')
                    <p wire:key="reservation-quota-left" class="mt-1.5 text-[11px] font-semibold {{ $tone['text'] }}">فقط {{ convertToPersian((string) $usage['left']) }} رزرو دیگر باقی مانده است</p>
                @endif
                @if($usage['reset'])
                    <p wire:key="reservation-quota-reset" class="mt-1.5 text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)]">سهمیه از {{ $usage['reset']['date'] }} بازمی‌گردد ({{ convertToPersian((string) $usage['reset']['days']) }} روز دیگر)</p>
                @endif
                @if($this->lastMinuteOpensAt)
                    <p wire:key="reservation-last-minute-opens" class="mt-1.5 text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)]">تاریخ انتخابی شما از {{ $this->lastMinuteOpensAt }} برای رزرو لحظه‌آخری باز می‌شود.</p>
                @endif
                @if($usage['near'] && $this->lastMinuteHours !== null)
                    <p wire:key="reservation-last-minute-hint" class="mt-1.5 text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)]">رزرو لحظه‌آخری باز است: اگر منبع آزاد باشد، برای رزروی که حداکثر {{ convertToPersian((string) $this->lastMinuteHours) }} ساعت دیگر شروع می‌شود، سقف ماهانه مانع نیست.</p>
                @endif
            </div>
        @endif

        @if($this->cancelLimitUsage !== null)
            @php($cancelUsage = $this->cancelLimitUsage)
            @php($cancelTone = $tones[$cancelUsage['level']])
            @php($cancelPct = $cancelUsage['max'] > 0 ? min(100, (int) round($cancelUsage['count'] * 100 / $cancelUsage['max'])) : 100)
            <div wire:key="reservation-cancel-usage">
                <div class="flex items-center gap-2 mb-1.5">
                    <span
                        class="material-symbols-rounded text-[16px] {{ $cancelTone['text'] }}">event_busy</span>
                    <span
                        class="text-[11px] font-semibold {{ $cancelTone['text'] }}">
                        لغوهای {{ convertToPersian((string) $cancelUsage['days']) }} روز اخیر: {{ convertToPersian((string) $cancelUsage['count']) }} / {{ convertToPersian((string) $cancelUsage['max']) }}
                    </span>
                </div>
                <div class="h-1.5 w-full rounded-full bg-[var(--md-sys-color-surface-variant)] overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-500 {{ $cancelTone['fill'] }}"
                        style="width: {{ $cancelPct }}%"></div>
                </div>
                @if($cancelUsage['dropsInDays'])
                    <p wire:key="reservation-cancel-drops" class="mt-1.5 text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)]">یکی از لغوهای شما {{ convertToPersian((string) $cancelUsage['dropsInDays']) }} روز دیگر از شمارش خارج می‌شود</p>
                @endif
            </div>
        @endif
        </div>
        @endif
