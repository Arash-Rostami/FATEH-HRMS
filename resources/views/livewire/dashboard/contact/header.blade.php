<header class="border-runner flex-shrink-0 flex items-center gap-4 px-5 md:px-6 py-3 border-b  z-10 bg-[color-mix(in srgb, var(--md-sys-color-surface) 85%, transparent)] border-[var(--md-sys-color-primary-container)]">
    @php($isOnline = method_exists($activeContact, 'isOnline') && $activeContact->isOnline())

    <button
        wire:click="backToList"
        class="md:hidden w-9 h-9 rounded-xl flex items-center justify-center transition-all hover:brightness-95 active:scale-[0.92] bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]" aria-label="بازگشت">
        <span class="material-symbols-rounded text-base" aria-hidden="true">arrow_forward</span>
    </button>
    <div class="relative flex-shrink-0">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center text-base font-bold select-none overflow-hidden shadow-sm bg-[linear-gradient(135deg, var(--md-sys-color-primary-container), var(--md-sys-color-secondary-container))] text-[var(--md-sys-color-on-primary-container)]">
            <x-ui.avatar
                :image="null"
                :existingImage="$activeContact->profile->image "
                class="rounded-lg" />
        </div>
        @if($isOnline)
            <span class="absolute -bottom-0.5 -left-0.5 w-3 h-3 rounded-full border-[2.5px] online-pulse bg-[var(--online)] border-[var(--md-sys-color-surface)]"
                  aria-hidden="true"></span>
        @endif
    </div>
    <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2">
            <h2 class="text-sm font-bold tracking-tight text-[var(--md-sys-color-on-surface)]">{{ $activeContact->name }}</h2>
            @if($isOnline)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-[var(--online-container)] text-[var(--online)]">
                    <span class="w-1 h-1 rounded-full bg-current animate-pulse" aria-hidden="true"></span>آنلاین
                </span>
            @else
                <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">آخرین بازدید: {{ $activeContact->last_seen?->diffForHumans(null, true) ?? 'نامشخص' }}</span>
            @endif
        </div>
        <div class="flex items-center gap-2 mt-0.5 flex-wrap">
            @if($activeContact->profile?->position)
                <span class="text-[10px] font-medium text-[var(--md-sys-color-on-surface-variant)]">{{ $activeContact->profile->position }}</span>
            @endif
            @if($activeContact->profile?->department?->name)
                <span class="text-[10px] font-medium px-2 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">{{ $activeContact->profile->department->name }}</span>
            @endif
        </div>
    </div>
    <button class="w-9 h-9 rounded-lg flex items-center justify-center transition-all hover:brightness-95 active:scale-[0.92] bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]"
            aria-label="اطلاعات بیشتر"
            x-on:click="showInfo = !showInfo">
        <span class="material-symbols-rounded text-[16px]" aria-hidden="true">info</span>
    </button>
</header>
