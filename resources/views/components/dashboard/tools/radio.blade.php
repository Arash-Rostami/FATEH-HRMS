@props(['id' => 'jazz-radio'])

<div x-data="radio('{{ $id }}')" x-init="init()" dir="rtl" x-cloak>

    <x-ui.modals.backdrop x-show="open" :minimizable="true">

        <x-slot:icon>
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[var(--md-sys-color-primary-container)]">
                <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-on-primary-container)]">radio</span>
            </div>
        </x-slot:icon>

        <x-slot:heading>
            <div class="flex flex-col justify-center">
                <h3 class="truncate text-sm font-bold tracking-tight text-[var(--md-sys-color-on-primary)]"
                    x-text="`رادیو ${currentGenreLabel}`"></h3>
                <p class="text-[11px] font-medium text-[rgba(255,255,255,0.7)]" x-text="status"></p>
            </div>
        </x-slot:heading>

        <div class="flex flex-col gap-5 px-5 py-6">

            {{-- Digital Receiver Display --}}
            <div class="relative flex min-h-[64px] items-center gap-4 overflow-hidden rounded-2xl bg-[var(--md-sys-color-surface-container-highest)] px-4 py-3 shadow-inner ring-1 ring-inset ring-[var(--md-sys-color-outline-variant)]">
                <div class="absolute inset-0 bg-gradient-to-br from-white/5 to-transparent pointer-events-none"></div>

                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[var(--md-sys-color-surface)] shadow-sm ring-1 ring-[var(--md-sys-color-outline-variant)]/50">
                    <div x-show="playing && volume > 0" class="flex h-4 items-end gap-[2px]">
                        <template x-for="(d,i) in [0,90,180,270,360]" :key="i">
                            <span class="block w-[2px] animate-eq rounded-full bg-[var(--md-sys-color-primary)]"
                                  :style="`animation-delay: ${d}ms; height: ${[6,14,9,14,6][i]}px`"
                                  style="transform-origin: bottom;"></span>
                        </template>
                    </div>
                    <span x-show="!(playing && volume > 0)" class="h-2.5 w-2.5 rounded-full shadow-inner"
                          :style="playing ? 'background:#1a7f5a' : (status==='خطا'||status==='آفلاین' ? 'background:var(--md-sys-color-error)' : 'background:var(--md-sys-color-outline)')"></span>
                </div>

                <div class="flex min-w-0 flex-1 flex-col">
                    <span class="truncate text-sm font-bold text-[var(--md-sys-color-on-surface)]"
                          x-text="currentStationUrl ? currentStationName : 'آماده به کار'"></span>
                    <span class="truncate text-[11px] font-medium text-[var(--md-sys-color-outline)]"
                          x-text="currentStationUrl ? currentGenreLabel : status"></span>
                </div>

                <div x-show="isLoading" class="flex shrink-0 items-center justify-center">
                    <span class="material-symbols-rounded animate-spin text-[20px] text-[var(--md-sys-color-primary)]">progress_activity</span>
                </div>
            </div>

            {{-- Tabs (x-ui.buttons.tab-selector grammar; Alpine-driven, so not the shared Livewire component itself) --}}
            <div class="flex w-full rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30 bg-[var(--md-sys-color-surface-variant)]/40 p-1 shadow-sm">
                <button type="button" @click="tab = 'stations'"
                        :class="tab === 'stations'
                            ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_12px_color-mix(in_srgb,var(--md-sys-color-primary)_30%,transparent)]'
                            : 'text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                        class="flex flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-xl px-3 py-2 text-xs font-bold transition-all duration-300">
                    <span class="material-symbols-rounded text-base">library_music</span>
                    ایستگاه‌ها
                </button>
                <button type="button" @click="tab = 'play'"
                        :class="tab === 'play'
                            ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_12px_color-mix(in_srgb,var(--md-sys-color-primary)_30%,transparent)]'
                            : 'text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                        class="flex flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-xl px-3 py-2 text-xs font-bold transition-all duration-300">
                    <span class="material-symbols-rounded text-base">smart_display</span>
                    پخش
                </button>
            </div>

            <div class="relative min-h-[160px]">
                {{-- Tab: Play --}}
                <div x-show="tab === 'play'" x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="absolute inset-0 flex items-center justify-between gap-4 px-2">

                    {{-- Volume Fader --}}
                    <div class="flex flex-col items-center gap-3">
                        <button @click="toggleMute()" :title="volume == 0 ? 'باصدا کردن' : 'بی‌صدا کردن'"
                                class="group flex h-8 w-8 items-center justify-center rounded-lg text-[var(--md-sys-color-on-surface-variant)] transition-colors hover:bg-[var(--md-sys-color-surface-variant)] hover:text-[var(--md-sys-color-primary)] active:scale-95">
                            <span class="material-symbols-rounded text-[18px] transition-transform group-active:scale-90"
                                  x-text="volume == 0 ? 'volume_off' : (volume < 0.5 ? 'volume_down' : 'volume_up')"></span>
                        </button>

                        <div x-ref="volTrack" role="slider" tabindex="0" aria-label="بلندی صدا"
                             :aria-valuenow="Math.round(volume * 100)" aria-valuemin="0" aria-valuemax="100"
                             @keydown.arrow-up.prevent="setVolume(volume + 0.05)"
                             @keydown.arrow-down.prevent="setVolume(volume - 0.05)"
                             @pointerdown="volDragging = true; $event.currentTarget.setPointerCapture?.($event.pointerId); setVolumeFromY($event)"
                             @pointermove="volDragging && setVolumeFromY($event)"
                             @pointerup="volDragging = false"
                             @pointercancel="volDragging = false"
                             class="relative h-20 w-8 cursor-pointer touch-none rounded-full bg-[var(--md-sys-color-surface-container-highest)] shadow-inner ring-1 ring-inset ring-[var(--md-sys-color-outline-variant)]">
                            <div class="absolute inset-1 overflow-hidden rounded-full bg-[var(--md-sys-color-surface)]">
                                <div class="absolute inset-x-0 bottom-0 w-full rounded-full bg-[var(--md-sys-color-primary)]"
                                     :class="volDragging ? 'transition-none' : 'transition-[height] duration-150 ease-out'"
                                     :style="`height: ${volume * 100}%`">
                                    <div class="absolute inset-x-1 top-1 h-1 rounded-full bg-white/40"></div>
                                </div>
                            </div>
                        </div>

                        <span class="text-[10px] font-bold text-[var(--md-sys-color-outline)]" x-text="faPercent + '٪'"></span>
                    </div>

                    {{-- Main Deck Controls --}}
                    <div class="flex flex-1 items-center justify-center gap-4">
                        <button @click="nextStation()"
                                class="flex h-11 w-11 items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-container)] ring-1 ring-inset ring-[var(--md-sys-color-outline-variant)] transition-all hover:bg-[var(--md-sys-color-surface-variant)] active:scale-90">
                            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-surface-variant)]">skip_next</span>
                        </button>

                        <button @click="togglePlay()"
                                class="group relative flex h-20 w-20 items-center justify-center rounded-xl shadow-lg transition-all duration-300 hover:scale-105 active:scale-95"
                                style="background:linear-gradient(135deg,var(--md-sys-color-primary),color-mix(in srgb,var(--md-sys-color-primary) 70%,#000 30%)); animation:softPulse 3.2s ease-in-out infinite;">
                            <div class="absolute inset-0 rounded-xl bg-white opacity-0 transition-opacity group-hover:opacity-10"></div>
                            <span class="material-symbols-rounded text-[36px] text-[var(--md-sys-color-on-primary)]" x-text="playing ? 'pause' : 'play_arrow'"></span>
                        </button>

                        <button @click="prevStation()"
                                class="flex h-11 w-11 items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-container)] ring-1 ring-inset ring-[var(--md-sys-color-outline-variant)] transition-all hover:bg-[var(--md-sys-color-surface-variant)] active:scale-90">
                            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-surface-variant)]">skip_previous</span>
                        </button>

                        <button @click="fetchStations(true)"
                                class="flex h-11 w-11 items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-container)] ring-1 ring-inset ring-[var(--md-sys-color-outline-variant)] transition-all hover:bg-[var(--md-sys-color-surface-variant)] active:scale-90">
                            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-surface-variant)]" :class="isLoading ? 'animate-spin' : ''">refresh</span>
                        </button>
                    </div>
                </div>

                {{-- Tab: Stations --}}
                <div x-show="tab === 'stations'"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="absolute inset-0 flex flex-col justify-center gap-5">

                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <label class="text-[10px] font-bold uppercase tracking-wider text-[var(--md-sys-color-outline)]">انتخاب ژانر</label>
                        </div>
                        <div class="flex snap-x overflow-x-auto pb-2 [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                            <div class="flex gap-2">
                                <template x-for="g in genres" :key="g.value">
                                    <button @click="currentGenre = g.value"
                                            class="shrink-0 snap-start rounded-xl px-4 py-2 text-xs font-bold transition-all duration-200 active:scale-95"
                                            :class="currentGenre === g.value
                                                ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                                                : 'bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface-variant)] ring-1 ring-inset ring-[var(--md-sys-color-outline-variant)]/50 hover:bg-[var(--md-sys-color-surface-variant)]'"
                                            x-text="g.label"></button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-[10px] font-bold uppercase tracking-wider text-[var(--md-sys-color-outline)]">ایستگاه‌های در دسترس</label>
                        <div class="relative">
                            <select dir="ltr" x-model="currentStationUrl"
                                    class="w-full appearance-none rounded-xl bg-[var(--md-sys-color-surface-container)] px-4 py-3.5 pl-12 text-sm font-medium text-[var(--md-sys-color-on-surface)] shadow-sm ring-1 ring-inset ring-[var(--md-sys-color-outline-variant)] outline-none transition-all focus:ring-2 focus:ring-[var(--md-sys-color-primary)]">
                                <template x-for="station in stations" :key="station.stationuuid">
                                    <option :value="station.url_resolved" x-text="station.name"></option>
                                </template>
                            </select>
                            <span class="material-symbols-rounded pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-[20px] text-[var(--md-sys-color-outline)]">music_note</span>
                        </div>
                        <p class="mt-2 text-start text-[10px] font-medium text-[var(--md-sys-color-outline)]"
                           x-text="stations.length ? faDigits(stations.length) + ' ایستگاه پیدا شد' : ''"></p>
                    </div>
                </div>
            </div>
        </div>

    </x-ui.modals.backdrop>

    <template x-teleport="#tool-dock">
        <x-dashboard.navbars.dock
            show="minimized"
            on-click="restore()"
            label="رادیو"
            value-expr="currentStationName || 'آماده'"
            restore-fn="restore()"
            close-fn="closeModal()">

            <x-slot:icon>
                <div class="flex h-6 w-6 items-center justify-center rounded-lg bg-[var(--md-sys-color-surface-container-high)] ring-1 ring-inset ring-[var(--md-sys-color-outline-variant)]">
                    <div x-show="playing && volume > 0" class="flex h-3 items-end gap-[2px]">
                        <template x-for="(d,i) in [0,100,200,150,50]" :key="i">
                            <span class="block w-[1.5px] animate-eq rounded-full bg-[var(--md-sys-color-primary)]"
                                  :style="`animation-delay: ${d}ms; height: ${[5,10,7,10,5][i]}px`"
                                  style="transform-origin:bottom;"></span>
                        </template>
                    </div>
                    <span x-show="!(playing && volume > 0)" class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-on-surface-variant)]">radio</span>
                </div>
            </x-slot:icon>

            <x-slot:controls>
                <button @click.stop="togglePlay()" title="پخش/توقف"
                        class="flex h-8 w-8 items-center justify-center rounded-md text-[var(--md-sys-color-primary)] transition-all hover:bg-[var(--md-sys-color-primary)]/10 active:scale-90">
                    <span class="material-symbols-rounded text-[20px]"
                          x-text="playing ? 'pause' : 'play_arrow'"></span>
                </button>
            </x-slot:controls>

        </x-dashboard.navbars.dock>
    </template>
</div>
