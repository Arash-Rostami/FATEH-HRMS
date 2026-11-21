@props(['part' => 'bar'])

@if($part === 'bar')
    <footer
        {{ $attributes->merge(['class' => 'relative z-[5] flex items-center gap-4 px-4 py-2 bg-[var(--md-sys-color-surface)] border-t border-[var(--md-sys-color-outline-variant)]/30 shadow-[0_-8px_28px_color-mix(in_srgb,var(--md-sys-color-primary)_8%,transparent)] min-h-[68px] md:min-h-[76px] pb-[calc(env(safe-area-inset-bottom,0px)+0.5rem)]']) }}
        dir="rtl"
        aria-label="پخش‌کنندهٔ رسانه"
        wire:ignore
    >
        <span
            class="absolute inset-x-0 top-0 h-[3px] bg-[var(--md-sys-color-surface-variant)] overflow-hidden md:hidden"
            aria-hidden="true"
        >
            <span
                class="block h-full bg-[var(--md-sys-color-primary)] transition-[width] duration-100 ease-linear"
                :style="`width: ${progressRatio * 100}%`"
            ></span>
        </span>

        <template x-if="currentEpisode">
            <div class="flex min-w-0 flex-1 items-center gap-3">
                <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                    <span class="material-symbols-rounded text-[22px] font-fill" x-text="playing ? 'graphic_eq' : (item()?.kind === 'video' ? 'smart_display' : 'headphones')">headphones</span>
                </div>
                <div class="min-w-0">
                    <p class="truncate text-[13px] font-bold text-[var(--md-sys-color-on-surface)]" x-text="currentEpisode?.title"></p>
                    <p class="truncate text-[11px] text-[var(--md-sys-color-on-surface-variant)]" x-text="item()?.title"></p>
                </div>
            </div>
        </template>
        <template x-if="!currentEpisode">
            <div class="flex min-w-0 flex-1 items-center gap-2 text-[12px] font-medium text-[var(--md-sys-color-on-surface-variant)]">
                <span class="material-symbols-rounded text-[20px]">play_circle</span>
                <span>جلسه‌ای برای پخش انتخاب نشده است</span>
            </div>
        </template>

        <div class="flex items-center justify-center gap-1">
            <button type="button" @click="prev()"
                    class="hidden h-11 w-11 place-items-center rounded-xl text-[var(--md-sys-color-on-surface-variant)] transition-colors hover:bg-[var(--md-sys-color-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] active:scale-95 md:grid"
                    aria-label="جلسهٔ قبل">
                <span class="material-symbols-rounded text-[24px] font-fill">skip_previous</span>
            </button>
            <button type="button" @click="skip(-10)"
                    class="hidden h-11 w-11 place-items-center rounded-xl text-[var(--md-sys-color-on-surface-variant)] transition-colors hover:bg-[var(--md-sys-color-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] active:scale-95 md:grid"
                    aria-label="۱۰ ثانیه عقب">
                <span class="material-symbols-rounded text-[24px]">replay_10</span>
            </button>
            <button type="button" @click="toggle()"
                    class="grid h-11 w-11 place-items-center rounded-2xl bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md transition-transform hover:scale-105 active:scale-95"
                    aria-label="پخش یا توقف">
                <span class="material-symbols-rounded text-[26px] font-fill" x-text="playing ? 'pause' : 'play_arrow'">play_arrow</span>
            </button>
            <button type="button" @click="skip(10)"
                    class="hidden h-11 w-11 place-items-center rounded-xl text-[var(--md-sys-color-on-surface-variant)] transition-colors hover:bg-[var(--md-sys-color-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] active:scale-95 md:grid"
                    aria-label="۱۰ ثانیه جلو">
                <span class="material-symbols-rounded text-[24px]">forward_10</span>
            </button>
            <button type="button" @click="next()"
                    class="hidden h-11 w-11 place-items-center rounded-xl text-[var(--md-sys-color-on-surface-variant)] transition-colors hover:bg-[var(--md-sys-color-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] active:scale-95 md:grid"
                    aria-label="جلسهٔ بعد">
                <span class="material-symbols-rounded text-[24px] font-fill">skip_next</span>
            </button>
        </div>

        <div class="hidden min-w-0 flex-[1.6] flex-col items-center gap-0.5 md:flex">
            <div class="flex w-full items-center gap-2.5 text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)] font-[var(--font-mono,'_')]">
                <span class="tabular-nums" x-text="faTime(t)"></span>
                <div class="group/seek relative h-5 min-w-0 flex-1 cursor-pointer touch-none"
                     role="slider"
                     tabindex="0"
                     aria-label="جایگاه پخش"
                     aria-valuemin="0"
                     :aria-valuemax="Math.floor(duration)"
                     :aria-valuenow="Math.floor(t)"
                     :aria-valuetext="faTime(t) + ' از ' + faTime(duration)"
                     @pointerdown="seekFromEvent($event, $refs.seekTrack)"
                     @keydown.arrow-left.prevent="skip(10)"
                     @keydown.arrow-right.prevent="skip(-10)"
                     x-ref="seekTrack">
                    <div class="absolute inset-x-0 top-2 h-1 overflow-hidden rounded-full bg-[var(--md-sys-color-surface-variant)] transition-all duration-200 group-hover/seek:top-1.5 group-hover/seek:h-1.5">
                        <div class="absolute inset-y-0 start-0 bg-[var(--md-sys-color-primary)]"
                             :class="seeking ? 'transition-none' : 'transition-[width] duration-100 ease-linear'"
                             :style="`width: ${progressRatio * 100}%`"></div>
                    </div>
                </div>
                <span class="tabular-nums" x-text="faTime(duration)"></span>
            </div>
        </div>

        <div class="hidden items-center justify-end gap-1 md:flex">
            <button type="button" @click="setSpeed()" class="h-8 min-w-[46px] rounded-lg bg-[var(--md-sys-color-surface-variant)] px-2.5 text-[12px] font-extrabold text-[var(--md-sys-color-on-surface-variant)] transition-colors hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)]"
                    aria-label="سرعت پخش"
                    x-text="faTime(speed).replace(/:[^:]*$/, '') + '×'"></button>
            <button type="button" @click="toggleMute()" class="grid h-9 w-9 place-items-center rounded-lg text-[var(--md-sys-color-on-surface-variant)] transition-colors hover:bg-[var(--md-sys-color-surface-variant)]" :aria-label="muted ? 'باصدا کردن' : 'بی‌صدا کردن'">
                <span class="material-symbols-rounded text-[20px]" x-text="muted || vol === 0 ? 'volume_off' : 'volume_up'">volume_up</span>
            </button>
            <input type="range" min="0" max="1" step="0.05" dir="ltr" :value="vol" @input="setVolume($event.target.value)"
                   class="w-20 cursor-pointer accent-[var(--md-sys-color-primary)]"
                   aria-label="بلندی صدا">
        </div>

        <template x-if="buffering && currentEpisode">
            <span class="absolute inset-x-0 top-0 h-[3px] overflow-hidden" aria-hidden="true">
                <span class="block h-full w-1/3 animate-[slide-l_1.2s_ease-in-out_infinite] bg-[var(--md-sys-color-primary)]"></span>
            </span>
        </template>

        <template x-if="tapToPlay && currentEpisode && !error">
            <button type="button" @click="toggle(); tapToPlay = false"
                    class="absolute -top-12 left-1/2 -translate-x-1/2 rounded-xl bg-[var(--md-sys-color-on-surface)] px-4 py-2 text-[12px] font-bold text-[var(--md-sys-color-surface)] shadow-lg">
                برای ادامه لمس کنید
            </button>
        </template>

        <template x-if="error && currentEpisode">
            <div class="flex items-center gap-2 text-[12px] font-bold text-[var(--md-sys-color-error)]">
                <span class="material-symbols-rounded text-[18px]">error</span>
                <span>پخش نشد</span>
                <a :href="currentEpisode?.download_url" class="text-[var(--md-sys-color-primary)] underline" x-show="currentEpisode?.download_url">دانلود فایل</a>
            </div>
        </template>
    </footer>
@elseif($part === 'stage')
    <template x-if="currentEpisode && item()?.kind === 'video'">
        <div class="relative mx-4 mt-4 aspect-video max-h-[360px] w-[calc(100%-2rem)] overflow-hidden rounded-2xl bg-[color-mix(in_srgb,var(--md-sys-color-primary)_45%,var(--md-sys-color-surface))] shadow-sm md:mx-7 md:w-[calc(100%-3.5rem)]"
             :class="playing ? '' : 'paused'"
             wire:ignore>
            <span class="pointer-events-none absolute inset-0 grid place-items-center text-[96px] opacity-35 material-symbols-rounded font-fill" x-text="item()?.icon || 'smart_display'">smart_display</span>

            <button type="button" @click="toggle()"
                    class="absolute inset-0 grid place-items-center"
                    aria-label="پخش یا توقف">
                <span class="grid h-[72px] w-[72px] place-items-center rounded-[22px] bg-black/45 text-white transition-transform hover:scale-105">
                    <span class="material-symbols-rounded text-[38px] font-fill" x-text="playing ? 'pause' : 'play_arrow'">play_arrow</span>
                </span>
            </button>

            <div class="absolute inset-x-0 bottom-0 flex flex-col gap-2 px-4 pb-3 pt-11 opacity-0 transition-opacity duration-200 [.paused_&]:opacity-100 hover:opacity-100 focus-within:opacity-100"
                 style="background: linear-gradient(to top, rgba(0,0,0,.7), transparent); color: #fff;">
                <div class="h-1 overflow-hidden rounded-full bg-white/30">
                    <div class="block h-full bg-white" :style="`width: ${progressRatio * 100}%`"></div>
                </div>
                <div class="flex items-center gap-3 text-[12px]">
                    <button type="button" @click="prev()" class="grid h-9 w-9 place-items-center rounded-lg hover:bg-white/20" aria-label="قبلی">
                        <span class="material-symbols-rounded text-[20px] font-fill">skip_previous</span>
                    </button>
                    <button type="button" @click="toggle()" class="grid h-9 w-9 place-items-center rounded-lg hover:bg-white/20" aria-label="پخش">
                        <span class="material-symbols-rounded text-[20px] font-fill" x-text="playing ? 'pause' : 'play_arrow'">play_arrow</span>
                    </button>
                    <button type="button" @click="next()" class="grid h-9 w-9 place-items-center rounded-lg hover:bg-white/20" aria-label="بعدی">
                        <span class="material-symbols-rounded text-[20px] font-fill">skip_next</span>
                    </button>
                    <span class="tabular-nums" x-text="faTime(t) + ' / ' + faTime(duration)"></span>
                    <span class="ms-auto truncate" x-text="currentEpisode?.title"></span>
                </div>
            </div>
        </div>
    </template>
@endif
