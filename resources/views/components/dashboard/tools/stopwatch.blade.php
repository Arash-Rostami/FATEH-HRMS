<div x-data="stopwatch('{{ asset('build/assets/audio/alarm.mp3') }}')"
     x-init="init()"
     x-ref="stopwatchModal"
     dir="rtl" x-cloak>

    <x-ui.modals.backdrop x-show="open" :minimizable="true">

        <x-slot:icon>
            <span class="material-symbols-rounded text-[var(--md-sys-color-on-primary)] text-[24px]">alarm</span>
        </x-slot:icon>

        <x-slot:heading>
            <p class="text-sm font-semibold leading-none" style="color:var(--md-sys-color-on-primary);"
               x-text="reminderTitle ? 'یادآوری: ' + reminderTitle : 'تایمر'"></p>
            <p class="mt-1 text-xs leading-none" style="color:rgba(255,255,255,.70);"
               x-text="timer.running ? 'فعال ...' : 'متوقف'"></p>
        </x-slot:heading>

        {{-- Ring --}}
        <div class="flex flex-col items-center px-5 pt-6 pb-2">
            <div class="relative w-36 h-36 flex items-center justify-center">
                <svg class="absolute inset-0 w-full h-full -rotate-90" viewBox="0 0 144 144">
                    <circle cx="72" cy="72" r="62" fill="none" stroke="currentColor"
                            class="text-white/10" stroke-width="8"/>
                    <circle cx="72" cy="72" r="62" fill="none"
                            stroke="var(--md-sys-color-primary-container)"
                            stroke-width="8" stroke-linecap="round"
                            :stroke-dasharray="`${(timer.seconds / 300) * 389.56} 389.56`"
                            style="transition:stroke-dasharray 1s linear;"/>
                </svg>
                <div class="text-center z-10">
                    <div class="text-3xl font-bold text-[var(--md-sys-color-on-surface)] [font-feature-settings:'tnum']"
                         x-text="formatSeconds(timer.seconds)"></div>
                    <div class="text-xs mt-1 text-[var(--md-sys-color-outline)]">ثانیه</div>
                </div>
            </div>
        </div>

        {{-- Controls --}}
        <div class="flex items-center justify-center gap-4 px-5 py-4">
            <button @click="resetTimer()" title="ریست"
                    class="flex items-center justify-center rounded-xl transition-all duration-150 hover:-translate-y-px active:scale-95 bg-[var(--md-sys-color-primary-container)] w-[52px] h-[52px]">
                <span class="material-symbols-rounded text-[var(--md-sys-color-on-surface-variant)]">restart_alt</span>
            </button>

            <button @click="toggleTimer()" :title="timer.running ? 'توقف' : 'شروع'"
                    class="flex items-center justify-center rounded-xl transition-all duration-150 hover:-translate-y-px active:scale-95 animate-pulse bg-[var(--md-sys-color-primary)] w-[66px] h-[66px] shadow-lg">
                <span class="material-symbols-rounded text-[32px] text-[var(--md-sys-color-on-primary)]"
                      x-text="timer.running ? 'pause' : 'play_arrow'"></span>
            </button>

            <button @click="stopAlarm()" x-show="alarmInterval" title="توقف آلارم"
                    class="flex items-center justify-center rounded-xl animate-pulse transition-all duration-150 hover:-translate-y-px bg-[var(--md-sys-color-error)] active:scale-95 w-[52px] h-[52px] shadow-md">
                <span class="material-symbols-rounded text-[32px] text-[var(--md-sys-color-on-primary]">alarm_off</span>
            </button>
            <div x-show="!alarmInterval" class="w-[52px] h-[52px]"></div>
        </div>

        {{-- Presets --}}
        <div class="px-5 pb-2">
            <p class="text-[11px] font-semibold uppercase tracking-widest mb-3 text-center text-[var(--md-sys-color-outline)]">دقایق پیش‌فرض</p>
            <div class="flex flex-wrap justify-center gap-2">
                @foreach([[300,'۵'],[600,'۱۰'],[900,'۱۵'],[1800,'۳۰'],[3600,'۶۰']] as [$secs,$label])
                    <button @click="setTimerPreset({{ $secs }})"
                            class="rounded-lg px-4 py-1.5 text-xs font-semibold transition-all duration-150 hover:-translate-y-px active:scale-95 bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] border border-[var(--md-sys-color-outline-variant)]">
                        {{ $label }} م
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Custom input --}}
        <div class="px-5 pb-6 pt-3">
            <input type="number" min="1" step="1"
                   x-model.number="customMins"
                   @dblclick="setTimerPreset(customMins*60)"
                   @blur="setTimerPreset(customMins*60)"
                   @keydown.enter.prevent="setTimerPreset(customMins*60)"
                   placeholder="تنظیم دستی (دقیقه)"
                   class="w-full rounded-lg px-4 py-3 text-sm text-center outline-none transition-all duration-150 bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] border border-[var(--md-sys-color-outline-variant)]">
        </div>

    </x-ui.modals.backdrop>

    {{-- ── Minimized dock pill ─────────────────────────────────────────── --}}
    <template x-teleport="#tool-dock">
        <x-dashboard.navbars.dock
            show="minimized"
            on-click="restore()"
            label-expr="reminderTitle ? 'یادآوری: ' + reminderTitle : (timer.running ? 'در حال اجرا' : 'متوقف')"
            value-expr="formatSeconds(timer.seconds)"
            alarm="alarmInterval"
            restore-fn="restore()"
            close-fn="closeModal()">

            <x-slot:icon>
                <span class="material-symbols-rounded text-[18px]"
                      x-text="alarmInterval ? 'notifications_active' : 'timer'"></span>
            </x-slot:icon>

            <x-slot:controls>
                <button @click.stop="toggleTimer()" title="پخش/توقف"
                        class="flex items-center justify-center rounded-md w-[28px] h-[28px]
                               hover:bg-[var(--md-sys-color-primary)]/10 text-[var(--md-sys-color-primary)] transition-colors">
                    <span class="material-symbols-rounded text-[18px]"
                          x-text="timer.running ? 'pause' : 'play_arrow'"></span>
                </button>
            </x-slot:controls>

            <x-slot:alarmText>
                <span class="text-[12px] font-bold text-red-500 animate-bounce">آلارم!</span>
            </x-slot:alarmText>

            <x-slot:alarmControls>
                <button @click.stop="stopAlarm(); restore()"
                        class="flex items-center justify-center rounded-lg px-3 py-1 bg-red-500 text-white hover:bg-red-600 transition-colors text-[11px] font-bold">
                    توقف
                </button>
            </x-slot:alarmControls>

        </x-dashboard.navbars.dock>
    </template>
</div>
