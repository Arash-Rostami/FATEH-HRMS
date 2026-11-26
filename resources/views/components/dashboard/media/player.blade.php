@props(['part' => 'bar'])

@if($part === 'bar')
    @php($secondaryButtonClass = 'h-8 w-8 shrink-0 rounded-xl bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface-variant)] ring-1 ring-inset ring-[var(--md-sys-color-outline-variant)] transition-all hover:bg-[var(--md-sys-color-surface-variant)] active:scale-90')

    <footer
        {{ $attributes->merge(['class' => 'relative z-[5] flex items-center gap-3 bg-[var(--md-sys-color-surface)] border-t border-[var(--md-sys-color-outline-variant)]/30 shadow-md min-h-16 pb-[calc(env(safe-area-inset-bottom,0px)+0.5rem)] px-4']) }}
        dir="ltr"
        aria-label="پخش‌کنندهٔ رسانه"
        x-on:fullscreenchange.document="fullscreen = !!document.fullscreenElement"
        wire:ignore
    >
        <div
            class="absolute inset-x-0 top-0 z-10 h-3 cursor-pointer touch-none md:hidden"
            aria-hidden="true"
            @pointerdown="seekFromEvent($event, $el)"
        >
            <span class="block h-[3px] bg-[var(--md-sys-color-surface-variant)] overflow-hidden">
                <span
                    class="block h-full bg-[var(--md-sys-color-primary)] transition-[width] duration-100 ease-linear"
                    :style="`width: ${progressRatio * 100}%`"
                ></span>
            </span>
        </div>

        <div class="flex items-center justify-center gap-1">
            <div class="flex items-center gap-1">
                <x-ui.buttons.form variant="none" icon="skip_previous" x-on:click="prev()" aria-label="جلسهٔ قبل"
                                   class="{{ $secondaryButtonClass }} max-md:hidden"/>
                <x-ui.buttons.form variant="none" x-on:click="skip(-10)" aria-label="۱۰ ثانیه به عقب" title="۱۰ ثانیه به عقب"
                                   class="{{ $secondaryButtonClass }}">
                    <span aria-hidden="true" class="material-symbols-rounded text-[16px]">keyboard_double_arrow_left</span>
                </x-ui.buttons.form>
            </div>
            <x-ui.buttons.form variant="none" size="icon" x-on:click="toggle()" aria-label="پخش یا توقف"
                               class="group shrink-0 shadow-lg transition-all duration-300 hover:scale-105 active:scale-95"
                               style="background:linear-gradient(135deg,var(--md-sys-color-primary),color-mix(in srgb,var(--md-sys-color-primary) 70%,#000 30%))">
                <span aria-hidden="true" class="pointer-events-none absolute inset-0 rounded-xl bg-white opacity-0 transition-opacity group-hover:opacity-10"></span>
                <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary)]" x-text="playing ? 'pause' : 'play_arrow'">play_arrow</span>
            </x-ui.buttons.form>
            <div class="flex items-center gap-1">
                <x-ui.buttons.form variant="none" x-on:click="skip(10)" aria-label="۱۰ ثانیه به جلو" title="۱۰ ثانیه به جلو"
                                   class="{{ $secondaryButtonClass }}">
                    <span aria-hidden="true" class="material-symbols-rounded text-[16px]">keyboard_double_arrow_right</span>
                </x-ui.buttons.form>
                <x-ui.buttons.form variant="none" icon="skip_next" x-on:click="next()" aria-label="جلسهٔ بعد"
                                   class="{{ $secondaryButtonClass }} max-md:hidden"/>
            </div>
        </div>

        <div class="hidden min-w-0 flex-[1.6] flex-col items-center gap-0.5 md:flex">
            <div class="flex w-full items-center gap-2.5 text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)]">
                <span class="tabular-nums" x-text="faTime(t)"></span>
                <div class="group/seek relative h-5 min-w-0 flex-1 cursor-pointer touch-none"
                     role="slider"
                     tabindex="0"
                     aria-label="زمان پخش"
                     aria-valuemin="0"
                     :aria-valuemax="Math.floor(duration)"
                     :aria-valuenow="Math.floor(t)"
                     :aria-valuetext="faTime(t) + ' از ' + faTime(duration)"
                     @pointerdown="seekFromEvent($event, $refs.seekTrack)"
                     @keydown.arrow-left.stop.prevent="skip(-10)"
                     @keydown.arrow-right.stop.prevent="skip(10)"
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
            <x-ui.buttons.form variant="none" x-on:click="toggleStopAfter()" x-bind:aria-pressed="stopAfter" title="توقف پس از این جلسه" aria-label="توقف پس از این جلسه"
                               class="{{ $secondaryButtonClass }}"
                               x-bind:class="stopAfter && '!bg-[var(--md-sys-color-primary-container)] !text-[var(--md-sys-color-on-primary-container)]'">
                <span class="material-symbols-rounded text-[20px]" aria-hidden="true">stop_circle</span>
            </x-ui.buttons.form>
            <x-ui.buttons.form variant="none" x-on:click="setSpeed()" aria-label="سرعت پخش"
                               class="{{ $secondaryButtonClass }} text-[11px] font-bold" x-text="fa(speed) + '×'"/>
            <input type="range" min="0" max="1" step="0.05" dir="ltr" :value="vol" @input="setVolume($event.target.value)"
                   class="h-1 w-20 cursor-pointer accent-[var(--md-sys-color-primary)]"
                   aria-label="بلندی صدا">
            <x-ui.buttons.form variant="none" x-on:click="toggleMute()" x-bind:aria-label="muted ? 'وصل کردن صدا' : 'بی‌صدا کردن'"
                               class="group h-8 w-8 shrink-0 rounded-lg text-[var(--md-sys-color-on-surface-variant)] transition-colors hover:bg-[var(--md-sys-color-surface-variant)] hover:text-[var(--md-sys-color-primary)] active:scale-95">
                <span class="material-symbols-rounded text-[18px] transition-transform group-active:scale-90"
                      x-text="muted || vol === 0 ? 'volume_off' : (vol < 0.5 ? 'volume_down' : 'volume_up')">volume_up</span>
            </x-ui.buttons.form>
            <x-ui.buttons.form variant="none" x-show="isVideo && canPictureInPicture" x-cloak x-on:click="togglePictureInPicture()" x-bind:aria-pressed="pictureInPicture" aria-label="تصویر در تصویر" title="تصویر در تصویر"
                               class="{{ $secondaryButtonClass }}">
                <span class="material-symbols-rounded text-[20px]" aria-hidden="true">picture_in_picture_alt</span>
            </x-ui.buttons.form>
            <x-ui.buttons.form variant="none" x-show="isVideo" x-cloak x-on:click="toggleFullscreen()" x-bind:aria-pressed="fullscreen" aria-label="تمام‌صفحه" title="تمام‌صفحه (F)"
                               class="{{ $secondaryButtonClass }}">
                <span class="material-symbols-rounded text-[20px]" aria-hidden="true" x-text="fullscreen ? 'fullscreen_exit' : 'fullscreen'">fullscreen</span>
            </x-ui.buttons.form>
        </div>

        <template x-if="currentEpisode">
            <div dir="rtl" class="flex min-w-0 flex-1 items-center gap-3">
                <div class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                    <span class="material-symbols-rounded text-[20px] font-fill"
                          :class="playing ? 'animate-eq' : ''"
                          x-text="playing ? 'graphic_eq' : (isVideo ? 'smart_display' : 'headphones')">headphones</span>
                </div>
                <div class="min-w-0">
                    <p class="truncate text-[13px] font-bold text-[var(--md-sys-color-on-surface)]" x-text="currentEpisode?.title"></p>
                    <p class="truncate text-[11px] text-[var(--md-sys-color-on-surface-variant)]" x-text="item()?.title"></p>
                    <p x-show="upNext" x-cloak class="truncate text-[11px] text-[var(--md-sys-color-on-surface-variant)]" x-text="'جلسهٔ بعد: ' + upNext?.title"></p>
                </div>
            </div>
        </template>
        <template x-if="!currentEpisode">
            <div dir="rtl" class="flex min-w-0 flex-1 items-center gap-2 text-[12px] font-medium text-[var(--md-sys-color-on-surface-variant)]">
                <span class="material-symbols-rounded text-[20px]">play_circle</span>
                <span>یک جلسه برای پخش انتخاب کنید</span>
            </div>
        </template>

        <template x-if="tapToPlay && currentEpisode && !error">
            <div class="absolute -top-12 left-1/2 -translate-x-1/2 shadow-lg">
                <x-ui.buttons.form variant="tonal" x-on:click="toggle()">ادامهٔ پخش</x-ui.buttons.form>
            </div>
        </template>

        <template x-if="undo">
            <div class="absolute -top-12 start-4 shadow-lg">
                <x-ui.buttons.form variant="tonal" x-on:click="undoSwitch()">ادامهٔ جلسهٔ قبلی</x-ui.buttons.form>
            </div>
        </template>

        <template x-if="error && currentEpisode">
            <div dir="rtl" class="flex items-center gap-2 text-[12px] font-bold text-[var(--md-sys-color-error)]">
                <span class="material-symbols-rounded text-[18px]">error</span>
                <span>پخش فایل ممکن نشد</span>
                <a :href="currentEpisode?.file_url" class="text-[var(--md-sys-color-primary)] underline" x-show="currentEpisode?.file_url">دانلود فایل</a>
            </div>
        </template>
    </footer>
@elseif($part === 'stage')
    <template x-if="currentEpisode && isVideo">
        <div class="relative mt-2 md:mt-4 aspect-video max-h-96 [.view-maximized_&]:max-h-[62dvh] w-full overflow-hidden rounded-2xl bg-[color-mix(in_srgb,var(--md-sys-color-primary)_45%,var(--md-sys-color-surface))] shadow-sm [&:fullscreen]:rounded-none [&:fullscreen]:bg-black md:mx-7 md:w-[calc(100%-3.5rem)]"
             :class="playing ? '' : 'paused'"
             data-video-stage
             x-init="mountVideo()"
             wire:ignore>
            <span class="pointer-events-none absolute inset-0 grid place-items-center text-5xl opacity-35 material-symbols-rounded font-fill" x-text="item()?.icon || 'smart_display'">smart_display</span>

            <div x-ref="videoHost" class="absolute inset-0 grid place-items-center [&>video]:h-full [&>video]:w-full [&>video]:object-contain"></div>

            <div @click="toggle()"
                 class="absolute inset-0 grid place-items-center transition-opacity"
                 :class="playing ? 'opacity-0 pointer-events-none' : 'opacity-100'">
                <x-ui.buttons.form size="icon" x-on:click.stop="toggle()" aria-label="پخش یا توقف">
                    <span class="material-symbols-rounded text-[20px] font-fill" x-text="playing ? 'pause' : 'play_arrow'">play_arrow</span>
                </x-ui.buttons.form>
            </div>

            <div dir="ltr" class="absolute inset-x-0 bottom-0 flex flex-col gap-1 bg-gradient-to-t from-black/70 to-transparent px-4 pb-3 pt-11 text-white opacity-0 transition-opacity duration-200 [.paused_&]:opacity-100 hover:opacity-100 focus-within:opacity-100">
                <div class="group/seek relative h-4 cursor-pointer touch-none" @pointerdown="seekFromEvent($event, $el)">
                    <div class="absolute inset-x-0 top-1.5 h-1 overflow-hidden rounded-full bg-white/30 transition-all duration-200 group-hover/seek:top-1 group-hover/seek:h-2">
                        <div class="block h-full bg-white" :style="`width: ${progressRatio * 100}%`"></div>
                    </div>
                </div>
                <div class="flex items-center gap-1 text-[12px]">
                    <x-ui.buttons.form variant="none" icon="skip_previous" x-on:click="prev()" aria-label="جلسهٔ قبل" class="w-8 h-8 rounded-lg hover:bg-white/20"/>
                    <x-ui.buttons.form variant="none" x-on:click="skip(-10)" aria-label="۱۰ ثانیه به عقب" title="۱۰ ثانیه به عقب" class="w-8 h-8 rounded-lg hover:bg-white/20">
                        <span class="material-symbols-rounded text-[16px]" aria-hidden="true">keyboard_double_arrow_left</span>
                    </x-ui.buttons.form>
                    <x-ui.buttons.form variant="none" x-on:click="toggle()" aria-label="پخش یا توقف" class="w-8 h-8 rounded-lg hover:bg-white/20">
                        <span class="material-symbols-rounded text-[20px] font-fill" x-text="playing ? 'pause' : 'play_arrow'">play_arrow</span>
                    </x-ui.buttons.form>
                    <x-ui.buttons.form variant="none" x-on:click="skip(10)" aria-label="۱۰ ثانیه به جلو" title="۱۰ ثانیه به جلو" class="w-8 h-8 rounded-lg hover:bg-white/20">
                        <span class="material-symbols-rounded text-[16px]" aria-hidden="true">keyboard_double_arrow_right</span>
                    </x-ui.buttons.form>
                    <x-ui.buttons.form variant="none" icon="skip_next" x-on:click="next()" aria-label="جلسهٔ بعد" class="w-8 h-8 rounded-lg hover:bg-white/20"/>
                    <span class="mx-2 tabular-nums" x-text="faTime(t) + ' / ' + faTime(duration)"></span>
                    <span dir="rtl" class="ms-auto min-w-0 truncate max-md:hidden" x-text="currentEpisode?.title"></span>
                    <x-ui.buttons.form variant="none" x-on:click="setSpeed()" aria-label="سرعت پخش" title="سرعت پخش" class="w-8 h-8 rounded-lg text-[11px] font-bold hover:bg-white/20" x-text="fa(speed) + '×'"/>
                    <x-ui.buttons.form variant="none" x-on:click="toggleMute()" x-bind:aria-label="muted ? 'وصل کردن صدا' : 'بی‌صدا کردن'" class="w-8 h-8 rounded-lg hover:bg-white/20">
                        <span class="material-symbols-rounded text-[20px]" x-text="muted || vol === 0 ? 'volume_off' : (vol < 0.5 ? 'volume_down' : 'volume_up')">volume_up</span>
                    </x-ui.buttons.form>
                    <x-ui.buttons.form variant="none" x-show="canPictureInPicture" x-on:click="togglePictureInPicture()" x-bind:aria-pressed="pictureInPicture" aria-label="تصویر در تصویر" title="تصویر در تصویر" class="w-8 h-8 rounded-lg hover:bg-white/20">
                        <span class="material-symbols-rounded text-[20px]">picture_in_picture_alt</span>
                    </x-ui.buttons.form>
                    <x-ui.buttons.form variant="none" x-on:click="toggleFullscreen()" x-bind:aria-pressed="fullscreen" aria-label="تمام‌صفحه" title="تمام‌صفحه (F)" class="w-8 h-8 rounded-lg hover:bg-white/20">
                        <span class="material-symbols-rounded text-[20px]" x-text="fullscreen ? 'fullscreen_exit' : 'fullscreen'">fullscreen</span>
                    </x-ui.buttons.form>
                </div>
            </div>
        </div>
    </template>
@endif
