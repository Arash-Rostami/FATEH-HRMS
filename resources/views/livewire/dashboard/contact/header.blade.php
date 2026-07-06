<header class="border-runner flex-shrink-0 flex items-center gap-3 px-4 md:px-5 py-2.5 border-b z-10
               bg-[color-mix(in_srgb,var(--md-sys-color-surface)_90%,transparent)]
               border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]">

    @php($presence = \App\Enums\PresenceStatus::tryFrom($activeContact->presence->value ?? ''))

    {{-- Back (mobile) --}}
    <button wire:click="backToList" aria-label="بازگشت"
            class="md:hidden w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0
                   bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]
                   hover:brightness-95 active:scale-90 transition-all">
        <span class="material-symbols-rounded text-[18px]">arrow_forward</span>
    </button>

    {{-- Avatar --}}
    <div class="relative flex-shrink-0">
        <div class="w-10 h-10 rounded-xl overflow-hidden shadow-sm ring-1 ring-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)]
                    bg-[linear-gradient(135deg,var(--md-sys-color-primary-container),var(--md-sys-color-secondary-container))]
                    text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center font-bold select-none">
            <x-ui.avatar :image="null" :existingImage="$activeContact->getProfileImageUrl()"
                         :alt="$activeContact->name" icon-size="text-xl" class="rounded-xl" />
        </div>
        @if($presence)
            <span class="absolute -bottom-0.5 -end-0.5 w-3 h-3 rounded-full border-2 border-[var(--md-sys-color-surface)] {{ $presence->activeClass() }}"></span>
        @endif
    </div>

    {{-- Identity --}}
    <div class="flex-1 min-w-0">
        <div class="flex items-center gap-1.5 flex-wrap">
            <h2 class="text-sm font-semibold tracking-tight text-[var(--md-sys-color-on-surface)] truncate">{{ $activeContact->name }}</h2>
            @if($presence)
                <span class="text-[9px] font-bold px-1.5 py-px rounded {{ $presence->iconBgClass() }}" title="{{ $presence->label() }}">{{ $presence->label() }}</span>
            @endif
        </div>
        <div class="flex items-center gap-2 mt-px flex-wrap">
            @if($activeContact->profile?->position)
                <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] truncate">{{ $activeContact->profile->position_label }}</span>
            @endif
            @if($activeContact->profile?->department)
                <span class="text-[10px] font-medium px-1.5 py-px rounded-md
                             bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]"
                      title="{{ $activeContact->profile->department->tooltipLabel() }}">
                    {{ $activeContact->profile->department->displayLabel() }}
                </span>
            @endif
            <span class="text-[10px] text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_60%,transparent)]">
                {{ toJalaliRelative($activeContact->last_seen) ?: 'نامشخص' }}
            </span>
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex items-center gap-1.5 flex-shrink-0">
        <button type="button" x-on:click="openMessageSearch()" aria-label="جستجوی پیام" title="جستجوی پیام"
                class="w-8 h-8 rounded-lg flex items-center justify-center transition-all
                       bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]
                       active:scale-90"
                :class="searchMessages ? '!bg-[var(--md-sys-color-primary)] !text-[var(--md-sys-color-on-primary)]' : 'hover:brightness-95'">
            <span class="material-symbols-rounded text-[18px]">search</span>
        </button>

        <button type="button" @click="toggleHighlight()" aria-label="پیش زمینه چت" title="پیش زمینه چت"
                class="w-8 h-8 rounded-lg flex items-center justify-center transition-all
                       bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]
                       active:scale-90"
                :class="isHighlighted ? '!bg-[var(--md-sys-color-primary)] !text-[var(--md-sys-color-on-primary)]' : 'hover:brightness-95'">
            <span class="material-symbols-rounded text-[18px]" x-text="isHighlighted ? 'hide_image' : 'texture'"></span>
        </button>

        <button @click="toggleMaximize()"
                :title="max ? 'کوچک کردن' : 'بزرگ کردن'"
                class="w-8 h-8 rounded-lg flex items-center justify-center transition-all
                       bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]
                       active:scale-90" aria-label="تغییر اندازه">
            <span class="material-symbols-rounded text-[20px]" x-text="max ? 'close_fullscreen' : 'open_in_full'" aria-hidden="true"></span>
        </button>


        <button x-on:click="showInfo = !showInfo" aria-label="اطلاعات بیشتر" title="اطلاعات بیشتر"
                class="w-8 h-8 rounded-lg flex items-center justify-center transition-all
                       bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]
                       hover:!bg-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary)]
                       active:scale-90">
            <span class="material-symbols-rounded text-[18px]">info</span>
        </button>
    </div>
</header>
