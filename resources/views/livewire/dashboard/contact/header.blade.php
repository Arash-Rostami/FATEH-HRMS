<header class="border-runner flex-shrink-0 flex items-center gap-4 px-5 md:px-6 py-3 border-b  z-10 bg-[color-mix(in srgb, var(--md-sys-color-surface) 85%, transparent)] border-[var(--md-sys-color-primary-container)]">
    @php($isOnline = method_exists($activeContact, 'isOnline') && $activeContact->isOnline())
    @php($presence = \App\Enums\PresenceStatus::tryFrom($activeContact->presence->value ?? ''))

    <button
        wire:click="backToList"
        class="md:hidden w-9 h-9 rounded-xl flex items-center justify-center transition-all hover:brightness-95 active:scale-[0.92] bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]" aria-label="بازگشت">
        <span class="material-symbols-rounded text-base" aria-hidden="true">arrow_forward</span>
    </button>
    <div class="relative flex-shrink-0">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center text-base font-bold select-none overflow-hidden shadow-sm bg-[linear-gradient(135deg, var(--md-sys-color-primary-container), var(--md-sys-color-secondary-container))] text-[var(--md-sys-color-on-primary-container)]">
            <x-ui.avatar
                :image="null"
                :existingImage="$activeContact->getProfileImageUrl()"
                :alt="$activeContact->name"
                icon-size="text-2xl"
                class="rounded-lg" />
        </div>
    </div>
    <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 flex-wrap">
            <h2 class="text-sm font-bold tracking-tight text-[var(--md-sys-color-on-surface)]">{{ $activeContact->name }}</h2>
            @if($presence)
                <span @class(['inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[9px] font-bold cursor-help', $presence->iconBgClass()])
                title="{{ $presence->label() }}">
                    <span class="material-symbols-rounded text-[10px]" aria-hidden="true">{{ $presence->icon() }}</span>
                </span>
            @endif
            <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">آخرین بازدید: {{ toJalaliRelative($activeContact->last_seen) ?: 'نامشخص' }}</span>
        </div>
        <div class="flex items-center gap-2 mt-0.5 flex-wrap">
            @if($activeContact->profile?->position)
                <span class="text-[10px] font-medium text-[var(--md-sys-color-on-surface-variant)]">{{ $activeContact?->profile->position_label }}</span>
            @endif
            @if($activeContact->profile?->department?->name)
                <span class="text-[10px] font-medium px-2 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">{{ $activeContact?->profile?->department->description }}</span>
            @endif
        </div>
    </div>

    <div class="relative" x-data="{ bgMenuOpen: false }" x-on:click.outside="bgMenuOpen = false">
        <button class="w-9 h-9 rounded-lg flex items-center justify-center transition-all hover:brightness-95 active:scale-[0.92] bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]"
                aria-label="تغییر پس‌زمینه"
                title="تغییر پس‌زمینه"
                x-on:click="bgMenuOpen = !bgMenuOpen">
            <span class="material-symbols-rounded text-[16px]" aria-hidden="true">wallpaper</span>
        </button>

        <div x-show="bgMenuOpen" x-transition class="absolute left-0 top-full mt-2 w-56 max-h-[300px] overflow-y-auto rounded-xl shadow-lg border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container-high)] z-50 p-2 flex flex-col gap-1 msg-scrollbar" style="display: none;">
            <div class="flex items-center justify-between px-2 py-1.5 mb-1 border-b border-[var(--md-sys-color-outline-variant)]">
                <span class="text-xs font-bold text-[var(--md-sys-color-on-surface)]">پس‌زمینه چت</span>
                <button x-on:click="$store.background.togglePattern(!$store.background.patternEnabled)" class="text-[20px] transition-colors" :class="$store.background.patternEnabled ? 'text-[var(--md-sys-color-primary)]' : 'text-[var(--md-sys-color-on-surface-variant)] opacity-50'">
                    <span class="material-symbols-rounded" x-text="$store.background.patternEnabled ? 'toggle_on' : 'toggle_off'"></span>
                </button>
            </div>

            <template x-for="pattern in $store.background.patterns" :key="pattern.id">
                <button x-on:click="$store.background.setPattern(pattern.id)"
                        class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium transition-all text-right w-full"
                        :class="$store.background.activePattern === pattern.id ? 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]' : 'hover:bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]'">
                    <span class="material-symbols-rounded text-[14px]" :class="$store.background.activePattern === pattern.id ? 'opacity-100' : 'opacity-0'">check</span>
                    <span x-text="pattern.name"></span>
                </button>
            </template>
        </div>
    </div>

    <button class="w-9 h-9 rounded-lg flex items-center justify-center transition-all hover:brightness-95 active:scale-[0.92] bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]"
            aria-label="اطلاعات بیشتر"
            x-on:click="showInfo = !showInfo">
        <span class="material-symbols-rounded text-[16px]" aria-hidden="true">info</span>
    </button>
</header>
