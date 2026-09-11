@php
    $tracks = [];
    foreach (glob(public_path('audio/music').'/*.mp3') ?: [] as $musicFile) {
        $base = basename($musicFile, '.mp3');
        $tracks[] = ['name' => strtoupper($base), 'src' => asset('audio/music/'.basename($musicFile))];
    }
@endphp
<div dir="rtl" x-data="audioPlayer('{{ asset('audio/music/lofi.mp3') }}', 'LOFI', {{ json_encode($tracks) }})" x-init="init()" class="relative shrink-0">
    <input type="file" accept="audio/*" class="hidden" x-ref="file" @change="onFile($event)">

    <div dir="ltr"
         class="group relative isolate flex h-11 items-center gap-2 overflow-hidden rounded-xl pl-1.5 pr-2.5 backdrop-blur-xl transition-all duration-300 ease-out"
         :class="playing
             ? 'border-[color-mix(in_srgb,var(--md-sys-color-primary)_35%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-primary)_8%,transparent)] shadow-[0_8px_20px_-6px_color-mix(in_srgb,var(--md-sys-color-primary)_25%,transparent)]'
             : 'border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-surface-variant)_25%,transparent)] hover:border-[color-mix(in_srgb,var(--md-sys-color-primary)_25%,transparent)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-surface-variant)_40%,transparent)]'">

        <button type="button" @click="toggle()" :title="playing ? 'توقف پخش' : 'پخش'"
                class="relative z-30 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-sm transition-transform duration-200 hover:scale-105 active:scale-95">
            <span x-show="playing" x-cloak class="material-symbols-rounded text-[20px] leading-none">pause</span>
            <span x-show="!playing" class="material-symbols-rounded text-[20px] leading-none">play_arrow</span>
        </button>

        <div class="relative z-30 h-5 w-[110px] shrink-0 overflow-hidden sm:w-[150px] [mask-image:linear-gradient(to_right,transparent,black_10%,black_90%,transparent)] [-webkit-mask-image:linear-gradient(to_right,transparent,black_10%,black_90%,transparent)]">
            <div class="animate-ticker flex h-full w-max items-center" :style="playing ? '' : 'animation-play-state: paused'">
                <template x-for="i in 2" :key="i">
                    <span class="flex shrink-0 items-center gap-2 whitespace-nowrap px-4">
                        <span class="font-mono text-[11px] font-semibold tracking-wide text-[var(--md-sys-color-on-surface)]" x-text="trackName"></span>
                        <span class="h-1 w-1 shrink-0 rounded-full bg-[color-mix(in_srgb,var(--md-sys-color-primary)_60%,transparent)]"></span>
                        <span class="font-mono text-[11px] font-medium tracking-wider text-[var(--md-sys-color-on-surface-variant)]" x-text="timeLabel"></span>
                    </span>
                </template>
            </div>
        </div>

        <div class="relative z-30 flex items-center gap-0.5">
            <button type="button" @click="toggleMute(); volOpen = true" @mouseenter="libraryOpen = false; volOpen = true; clearTimeout(_volT)" @mouseleave="_volT = setTimeout(() => volOpen = false, 150)" :title="muted ? 'باصدا کردن' : 'بی‌صدا کردن'"
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[var(--md-sys-color-on-surface-variant)] transition-colors duration-200 hover:bg-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_12%,transparent)] hover:text-[var(--md-sys-color-primary)] active:scale-90">
                <span class="material-symbols-rounded text-[16px] leading-none" x-text="muted || volume == 0 ? 'volume_off' : 'volume_up'"></span>
            </button>

            <button type="button" @click="libraryOpen = !libraryOpen" title="انتخاب قطعه"
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[var(--md-sys-color-on-surface-variant)] transition-colors duration-200 hover:bg-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_12%,transparent)] hover:text-[var(--md-sys-color-primary)] active:scale-90">
                <span class="material-symbols-rounded text-[16px] leading-none">library_music</span>
            </button>

            <button x-show="isCustom" x-cloak type="button" @click="reset()" title="بازگشت به قطعه پیش‌فرض"
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[var(--md-sys-color-on-surface-variant)] transition-colors duration-200 hover:bg-[color-mix(in_srgb,var(--md-sys-color-error)_12%,transparent)] hover:text-[var(--md-sys-color-error)] active:scale-90">
                <span class="material-symbols-rounded text-[16px] leading-none">restart_alt</span>
            </button>
        </div>

        <div x-ref="seekHitbox"
             @pointerdown="onSeekDown($event)"
             @pointermove="onSeekMove($event)"
             @pointerup="onSeekUp($event)"
             @pointercancel="onSeekUp($event)"
             @pointerleave="if (!seeking) hoverProgress = null"
             class="group/seek absolute inset-x-0 bottom-0 z-20 flex h-4 cursor-pointer items-end px-2.5 pb-0.5 touch-none">

            <div x-show="hoverProgress !== null" x-cloak
                 class="pointer-events-none absolute bottom-4.5 -translate-x-1/2 rounded border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-highest)_95%,transparent)] px-1.5 py-0.5 font-mono text-[9px] font-medium tracking-tight text-[var(--md-sys-color-on-surface)] shadow-md backdrop-blur-md"
                 :style="`left: clamp(24px, ${hoverProgress * 100}%, calc(100% - 24px))`"
                 x-text="hoverTimeLabel">
            </div>

            <div x-ref="seekTrack" class="relative flex h-[2.5px] w-full items-center rounded-full bg-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] transition-all duration-200 group-hover/seek:h-[4.5px]">
                <div class="absolute inset-y-0 left-0 rounded-full bg-[color-mix(in_srgb,var(--md-sys-color-primary)_25%,transparent)]"
                     :style="`width: ${(hoverProgress ?? 0) * 100}%`"></div>

                <div class="relative h-full rounded-full bg-[var(--md-sys-color-primary)]"
                     :class="seeking ? 'transition-none' : 'transition-[width] duration-100 ease-linear'"
                     :style="`width: ${progress * 100}%`">
                    <span class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-1/2 h-2.5 w-2.5 rounded-full bg-[var(--md-sys-color-primary)] shadow-sm ring-2 ring-[var(--md-sys-color-surface)] transition-transform duration-150 group-hover/seek:scale-100"
                          :class="seeking ? 'scale-125' : 'scale-0'"></span>
                </div>
            </div>
        </div>
    </div>

    <div x-show="volOpen" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-2 scale-95"
         @mouseenter="volOpen = true; clearTimeout(_volT)" @mouseleave="_volT = setTimeout(() => volOpen = false, 150)" @click.outside="volOpen = false"
         class="absolute bottom-full right-0 z-30 flex origin-bottom pb-2">
        <div class="flex h-8 w-28 items-center rounded-full border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-highest)_85%,transparent)] px-3.5 shadow-lg backdrop-blur-xl">
            <input type="range" min="0" max="1" step="0.05" :value="volume"
                   @input="setVolume($event.target.value)" @change="persist()"
                   class="h-1 w-full cursor-pointer accent-[var(--md-sys-color-primary)]">
        </div>
    </div>

    <div x-show="libraryOpen" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-2 scale-95"
         @click.outside="libraryOpen = false"
         class="absolute bottom-full right-0 z-30 mb-2 w-40 origin-bottom overflow-hidden rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_45%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-surface)_92%,transparent)] shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_15%,transparent)] backdrop-blur-xl">
        <template x-for="t in tracks" :key="t.src">
            <button type="button" @click="chooseTrack(t)"
                    class="flex w-full items-center gap-2 px-3 py-2 transition-colors hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)]"
                    :class="!isCustom && currentSrc === t.src ? 'text-[var(--md-sys-color-primary)]' : 'text-[var(--md-sys-color-on-surface-variant)]'">
                <span class="material-symbols-rounded text-[14px] leading-none" x-text="!isCustom && currentSrc === t.src ? 'check' : 'music_note'"></span>
                <span class="truncate font-mono text-[11px] font-medium" x-text="t.name"></span>
            </button>
        </template>
        <button type="button" @click="pick()"
                class="flex w-full items-center gap-2 border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)] px-3 py-2 text-[var(--md-sys-color-on-surface-variant)] transition-colors hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)]">
            <span class="material-symbols-rounded text-[14px] leading-none">upload_file</span>
            <span class="text-[11px]">فایل دلخواه…</span>
        </button>
    </div>
</div>
