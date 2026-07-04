<div class="countdown-banner-slot" aria-live="polite">

    <div wire:key="countdown-banner">
        @if($event ?? false)
            <div
                x-data="countdown({ dateIso: @js($event['date_iso']), messages: @js($event['messages']), mood: @js($event['mood']), confetti: @js($event['confetti']) })"
                class="countdown-banner animate-countdown-pop"
                :class="{ 'countdown-banner--mourning': mood === 'mourning' }"
                dir="rtl"
                role="status"
                aria-live="polite">
                <canvas x-ref="confettiCanvas" class="countdown-confetti-canvas" aria-hidden="true"></canvas>
                <button type="button" class="countdown-close" wire:click="dismiss" wire:loading.attr="disabled"
                        aria-label="بستن بنر رویداد">
                    <span class="material-symbols-rounded" aria-hidden="true">close</span>
                </button>

                <div class="countdown-head">
                    <span class="countdown-icon material-symbols-rounded"
                          x-text="mood === 'mourning' ? 'local_florist' : 'schedule'" aria-hidden="true">schedule</span>
                    <span class="countdown-title">{{ $event['title'] }}</span>
                </div>

                <div class="countdown-ticker-window" x-show="messages.length > 0" aria-hidden="true">
                    <template x-if="messages.length > 1">
                        <div class="countdown-ticker-track" :class="{ 'no-transition': noTransition }"
                             :style="`transform: translateY(calc(-${tickIndex} * 1.75rem))`">
                            <template x-for="(msg, i) in [...messages, ...messages]" :key="i">
                                <div class="countdown-ticker-item" x-text="msg"></div>
                            </template>
                        </div>
                    </template>
                    <div class="countdown-ticker-item" x-show="messages.length === 1" x-text="messages[0]"></div>
                </div>

                <div class="countdown-digits" dir="ltr">
                    <div class="countdown-unit">
                        <span class="countdown-digit" aria-label="روز" x-text="days">۰۰</span>
                        <span class="countdown-unit-label">روز</span>
                    </div>
                    <span class="countdown-divider" aria-hidden="true"></span>
                    <div class="countdown-unit">
                        <span class="countdown-digit" aria-label="ساعت" x-text="hours">۰۰</span>
                        <span class="countdown-unit-label">ساعت</span>
                    </div>
                    <span class="countdown-divider" aria-hidden="true"></span>
                    <div class="countdown-unit">
                        <span class="countdown-digit" aria-label="دقیقه" x-text="minutes">۰۰</span>
                        <span class="countdown-unit-label">دقیقه</span>
                    </div>
                    <span class="countdown-divider" aria-hidden="true"></span>
                    <div class="countdown-unit">
                        <span class="countdown-digit" aria-label="ثانیه" x-text="seconds">۰۰</span>
                        <span class="countdown-unit-label">ثانیه</span>
                    </div>
                </div>

                <div class="countdown-accent" aria-hidden="true"></div>
            </div>
        @endif
    </div>
</div>
