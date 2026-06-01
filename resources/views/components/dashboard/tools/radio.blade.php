@props(['id' => 'jazz-radio'])

<div x-data="radio('{{ $id }}')" x-init="init()" dir="rtl" x-cloak>

    <x-ui.modals.backdrop x-show="open" :minimizable="true">

        <x-slot:icon>
            <div x-show="playing" class="flex items-end gap-[3px] h-5">
                <template x-for="(d,i) in [0,90,180,270,360]" :key="i">
                    <span class="w-[3px] rounded-full bg-[var(--md-sys-color-on-primary)] block animate-eq"
                          :style="`animation-delay: ${d}ms; height: ${[10,20,14,20,10][i]}px`"
                          style="transform-origin: bottom;"></span>
                </template>
            </div>
            <span x-show="!playing" class="material-symbols-rounded text-[var(--md-sys-color-on-primary)] text-[24px]">radio</span>
        </x-slot:icon>

        <x-slot:heading>
            <p class="truncate text-sm font-semibold leading-none text-[var(--md-sys-color-on-primary)]"
               x-text="`رادیو ${currentGenreLabel}`"></p>
            <p class="mt-1 text-xs leading-none text-[rgba(255,255,255,.70)]" x-text="status"></p>
        </x-slot:heading>

        {{-- Status --}}
        <div class="px-5 pt-5 my-8">
            <div class="flex items-center gap-3 rounded-xl px-4 py-3 bg-[var(--md-sys-color-surface-variant)] border border-[var(--md-sys-color-outline-variant)]">
                <span class="h-2 w-2 shrink-0 rounded-xl"
                      :style="playing ? 'background:#1a7f5a' : (status==='خطا'||status==='آفلاین' ? 'background:var(--md-sys-color-error)' : 'background:var(--md-sys-color-outline)')"></span>
                <span class="flex-1 truncate text-sm text-[var(--md-sys-color-on-surface-variant)]"
                      x-text="currentStationUrl ? currentStationName : status"></span>
                <span x-show="isLoading" class="material-symbols-rounded animate-spin text-[18px] text-[var(--md-sys-color-outline)]">progress_activity</span>
            </div>
        </div>

        {{-- Selects --}}
        <div class="grid grid-cols-2 gap-3 px-5 pt-4 my-8">
            @foreach([['dir'=>'rtl','model'=>'currentGenre','label'=>'ژانر','items'=>'genres','val'=>'genre.value','text'=>'genre.label'],['dir'=>'ltr','model'=>'currentStationUrl','label'=>'ایستگاه','items'=>'stations','val'=>'station.url_resolved','text'=>'station.name','key'=>'station.stationuuid']] as $sel)
                <div>
                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-widest text-[var(--md-sys-color-outline)]">{{ $sel['label'] }}</label>
                    <div class="relative">
                        <select dir="{{ $sel['dir'] }}" x-model="{{ $sel['model'] }}"
                                class="w-full appearance-none rounded-xl px-4 py-3 pl-10 text-sm outline-none bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] border border-[var(--md-sys-color-outline-variant)]">
                            <template x-for="{{ isset($sel['key']) ? 'station' : 'genre' }} in {{ $sel['items'] }}" :key="{{ $sel['key'] ?? 'genre.value' }}">
                                <option :value="{{ $sel['val'] }}" x-text="{{ $sel['text'] }}"></option>
                            </template>
                        </select>
                        <span class="material-symbols-rounded pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-[var(--md-sys-color-outline)]">expand_more</span>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Controls --}}
        <div class="px-5 py-5 my-8">
            <div class="flex items-center justify-between gap-3 rounded-[1.5rem] p-3 bg-[var(--md-sys-color-surface-variant)] border border-[var(--md-sys-color-outline-variant)]">
                <button @click="nextStation()"
                        class="flex items-center justify-center rounded-xl transition-all duration-150 hover:-translate-y-px active:scale-95 w-[48px] h-[48px] bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]">
                    <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-on-surface-variant)]">skip_next</span>
                </button>

                <button @click="togglePlay()"
                        class="flex items-center justify-center rounded-xl transition-all duration-150 hover:-translate-y-px active:scale-95 w-[66px] h-[66px] shadow-lg"
                        style="background:linear-gradient(135deg,var(--md-sys-color-primary),color-mix(in srgb,var(--md-sys-color-primary) 80%,#000 20%)); animation:softPulse 3.2s ease-in-out infinite;">
                    <span class="material-symbols-rounded text-[32px] text-[var(--md-sys-color-on-primary)]" x-text="playing ? 'pause' : 'play_arrow'"></span>
                </button>

                <button @click="fetchStations(true)"
                        class="flex items-center justify-center rounded-xl transition-all duration-150 hover:-translate-y-px active:scale-95 w-[48px] h-[48px] bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]">
                    <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-on-surface-variant)]" :class="isLoading ? 'animate-spin' : ''">refresh</span>
                </button>
            </div>
        </div>

        <div class="px-5 pb-5 pt-0 flex justify-end mt-8">
            <span class="text-xs text-[var(--md-sys-color-outline)]" x-text="stations.length ? `${stations.length} ایستگاه` : ''"></span>
        </div>

    </x-ui.modals.backdrop>

        {{-- Minimized tab --}}
    <template x-teleport="#tool-dock">
        <div x-show="minimized"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-x-8"
             x-transition:enter-end="opacity-100 translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-x-0"
             x-transition:leave-end="opacity-0 translate-x-8"
             class="flex items-center justify-between overflow-hidden cursor-pointer select-none rounded-md bg-[var(--md-sys-color-surface-variant)] border border-[var(--md-sys-color-outline-variant)] shadow-md pr-1.5 pl-3 py-1.5 hover:bg-[var(--md-sys-color-surface)] transition-colors group w-[220px]"
             @click="restore()">

            <div class="flex items-center gap-3">
                <div class="flex h-[36px] w-[36px] shrink-0 items-center justify-center rounded-md bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-inner">
                    <div x-show="playing" class="flex items-end gap-[2px] h-4">
                        <template x-for="(d,i) in [0,100,200,150,50]" :key="i">
                            <span class="w-[2px] rounded-lg bg-[var(--md-sys-color-on-primary)] block animate-eq"
                                  :style="`animation-delay: ${d}ms; height: ${[8,16,11,16,8][i]}px`"
                                  style="transform-origin:bottom;"></span>
                        </template>
                    </div>
                    <span x-show="!playing" class="material-symbols-rounded text-[18px]">radio</span>
                </div>

                <div class="flex flex-col justify-center max-w-[90px]">
                    <span class="text-[10px] font-medium text-[var(--md-sys-color-outline)] leading-tight">رادیو</span>
                    <span class="text-[13px] font-bold text-[var(--md-sys-color-on-surface)] leading-tight truncate w-full"
                          x-text="currentStationName || 'آماده'" dir="rtl"></span>
                </div>
            </div>

            <div class="flex items-center gap-1">
                <button @click.stop="togglePlay()" title="پخش/توقف"
                        class="flex items-center justify-center rounded-md w-[28px] h-[28px] hover:bg-[var(--md-sys-color-primary)]/10 text-[var(--md-sys-color-primary)] transition-colors">
                    <span class="material-symbols-rounded text-[18px]" x-text="playing ? 'pause' : 'play_arrow'"></span>
                </button>
                <button @click.stop="restore()" title="بزرگنمایی"
                        class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center rounded-md w-[28px] h-[28px] hover:bg-[var(--md-sys-color-primary)]/10 text-[var(--md-sys-color-primary)]">
                    <span class="material-symbols-rounded text-[18px]">open_in_full</span>
                </button>
                <button @click.stop="closeModal()" title="بستن"
                        class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center rounded-md w-[28px] h-[28px] hover:bg-red-500/10 hover:text-red-500 text-[var(--md-sys-color-outline)]">
                    <span class="material-symbols-rounded text-[18px]">close</span>
                </button>
            </div>
        </div>
    </template>
</div>