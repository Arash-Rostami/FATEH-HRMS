<header class="border-runner flex-shrink-0 flex items-center gap-4 px-5 md:px-6 py-3 border-b z-10 bg-[color-mix(in_srgb,var(--md-sys-color-surface)_85%,transparent)] backdrop-blur-md border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)]">
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

    <div class="relative">
        <button type="button"
                @click="isHighlighted = !isHighlighted; backgroundPattern = backgroundPattern === 'a' ? 'b' : 'a'"
                class="w-10 h-10 rounded-lg flex items-center justify-center transition-all hover:bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] active:scale-95 focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)]"
                :class="activeMenu === 'theme' ? 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-primary)]' : ''"
                aria-label="تغییر تم"
                title="تغییر تم">
            <span class="material-symbols-rounded text-[20px]" aria-hidden="true">palette</span>
        </button>
    </div>


    <button @click="max = !max"
            :title="max ? 'کوچک کردن' : 'بزرگ کردن'"
            class="w-10 h-10 rounded-lg flex items-center justify-center transition-all hover:bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] active:scale-95 focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)]"
            aria-label="تغییر اندازه">
        <span class="material-symbols-rounded text-[20px]" x-text="max ? 'close_fullscreen' : 'open_in_full'" aria-hidden="true"></span>
    </button>

    <button class="w-9 h-9 rounded-lg flex items-center justify-center transition-all hover:brightness-95 active:scale-[0.92] bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]"
            aria-label="اطلاعات بیشتر"
            x-on:click="showInfo = !showInfo">
        <span class="material-symbols-rounded text-[16px]" aria-hidden="true">info</span>
    </button>
</header>
