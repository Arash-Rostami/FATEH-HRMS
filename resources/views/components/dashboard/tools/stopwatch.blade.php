<div x-data="stopwatch('{{Vite::asset('resources/assets/audio/alarm.mp3') }}')"
     x-init="init()"
     x-ref="stopwatchModal"
     dir="rtl"
     class="fixed top-auto z-10 inset-0 hidden overflow-y-auto bg-gray-500 bg-opacity-75 transition-opacity justify-center">

    <div class="flex items-center justify-center min-h-screen p-4 w-2/3 md:w-1/4 max-h-screen">
        <div
            class="relative bg-[var(--md-sys-color-primary)] rounded-xl shadow-xl transform transition-all sm:max-w-md w-full p-8">
            <button
                title="بستن"
                @click="closeModal()"
                class="absolute top-3 left-3 w-6 h-6 rounded-lg flex items-center justify-center bg-black/40 text-white/90 hover:bg-black/60 hover:scale-110 hover:text-white transition-all shadow-lg z-20 border border-white/10"
            >
                <span class="material-symbols-rounded scale-[.75]">close</span>
            </button>
            <!-- Stopwatch Display -->
            <div class="text-center py-4 mb-4">
                <div class="relative mx-auto mb-4 w-32 h-32 flex items-center justify-center">
                    <!-- Animated Ring -->
                    <svg class="absolute inset-0 w-full h-full -rotate-90">
                        <circle cx="64" cy="64" r="56" fill="none" stroke="currentColor" class="text-black/40 hover:text-black/60 hover:scale-110" stroke-width="8"/>
                        <circle cx="64" cy="64" r="56" fill="none" stroke="currentColor" class="text-[var(--md-sys-color-primary-container)]" stroke-width="8" stroke-linecap="round" :stroke-dasharray="`${(timer.seconds / 300) * 351.86} 351.86`" style="transition: stroke-dasharray 1s linear;"/>
                    </svg>
                    <!-- Stopwatch Value -->
                    <div class="text-3xl font-bold text-[var(--md-sys-color-on-primary]"
                         x-text="formatSeconds(timer.seconds)"
                         style="font-variant-numeric: tabular-nums;"></div>
                </div>

                <div class="text-xs opacity-60 text-[var(--md-sys-color-on-primary]">
                    <span x-text="timer.running ? 'فعال ...' : 'متوقف'"></span>
                </div>
            </div>

            <!-- Controls -->
            <div class="flex justify-center gap-3 mb-4">
                <button @click="resetTimer()"
                        class="p-4 rounded-full transition-all hover:scale-110 cursor-pointer bg-[var(--md-sys-color-primary-container)]"
                        title="ریست">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </button>
                <button @click="toggleTimer()"
                        class="p-4 rounded-full transition-all hover:scale-110 shadow-lg cursor-pointer bg-[var(--md-sys-color-primary-container)]"
                        :title="timer.running ? 'توقف' : 'شروع'">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path x-show="!timer.running" d="M8 5v14l11-7z"/><path x-show="timer.running" d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/></svg>
                </button>
                <button  @click="stopAlarm()"
                         x-show="alarmInterval"
                        class="p-4 rounded-full transition-all hover:scale-110 animate-pulse bg-[var(--md-sys-color-primary-container)]"
                        title="Stop Alarm">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M6 6h12v12H6z"/></svg>
                </button>
            </div>

            <!-- Presets -->
            <div class="space-y-2 text-center">
                <div class="text-xs opacity-60 text-[var(--md-sys-color-primary-container)] mb-2">دقایق پیش فرض</div>
                <div class="flex gap-2 flex-wrap text-center justify-center">
                    <button class="chip text-[var(--md-sys-color-primary-container)]" @click="setTimerPreset(300)">5m</button>
                    <button class="chip text-[var(--md-sys-color-primary-container)]" @click="setTimerPreset(600)">10m</button>
                    <button class="chip text-[var(--md-sys-color-primary-container)]" @click="setTimerPreset(900)">15m</button>
                    <button class="chip text-[var(--md-sys-color-primary-container)]" @click="setTimerPreset(1800)">30m</button>
                    <button class="chip text-[var(--md-sys-color-primary-container)]" @click="setTimerPreset(3600)">60m</button>
                </div>
                <input type="number" min="1" step="1"
                       class="input-inline h-8 w-full rounded-lg bg-black/40 text-white/90 hover:bg-black/60 hover:scale-110 hover:text-white text-center justify-center custom-arrows"
                       placeholder="تنظیم دستی"
                       x-model.number="customMins"
                       @dblclick="setTimerPreset(customMins*60)"
                       @blur="setTimerPreset(customMins*60)"
                       @keydown.enter.prevent="setTimerPreset(customMins*60)">
            </div>
        </div>
    </div>
</div>
