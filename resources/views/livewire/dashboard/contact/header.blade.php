@php
    $presence = \App\Enums\PresenceStatus::tryFrom($activeContact->presence->value ?? '');
@endphp

<header class="relative z-10 flex flex-shrink-0 items-center gap-4 border-b px-5 py-3 backdrop-blur-xl transition-all duration-300
               bg-[color-mix(in_srgb,var(--md-sys-color-surface)_92%,transparent)]
               border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_60%,transparent)]
               shadow-[0_8px_32px_color-mix(in_srgb,var(--md-sys-color-primary)_15%,transparent)]">

    <button wire:click="backToList" aria-label="بازگشت"
            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] transition-all duration-200 ease-out hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)] active:scale-95 md:hidden">
        <span class="material-symbols-rounded text-base">arrow_forward</span>
    </button>

    <div class="relative flex-shrink-0">
        <div class="flex h-11 w-11 select-none items-center justify-center overflow-hidden rounded-2xl font-bold shadow-sm ring-1 ring-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)]
                    bg-[linear-gradient(135deg,var(--md-sys-color-primary-container),var(--md-sys-color-secondary-container))]
                    text-[var(--md-sys-color-on-primary-container)]">
            <x-ui.avatar :image="null" :existingImage="$activeContact->getProfileImageUrl()"
                         :alt="$activeContact->name" icon-size="text-xl" class="rounded-2xl" />
        </div>
        @if($presence)
            <span class="absolute -bottom-0.5 -end-0.5 h-3.5 w-3.5 rounded-full border-2 border-[var(--md-sys-color-surface)] {{ $presence->activeClass() }}"></span>
        @endif
    </div>

    <div class="relative min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
            <h2 class="truncate text-base font-bold tracking-tight text-[var(--md-sys-color-on-surface)]">{{ $activeContact->name }}</h2>
            @if($presence)
                <span class="rounded-md px-2 py-0.5 text-[10px] font-bold tracking-wide {{ $presence->iconBgClass() }}" title="{{ $presence->label() }}">
                    {{ $presence->label() }}
                </span>
            @endif
        </div>
        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs">
            @if($activeContact->profile?->position)
                <span class="truncate font-medium text-[var(--md-sys-color-on-surface-variant)]">{{ $activeContact->profile->position_label }}</span>
                <span class="h-1 w-1 rounded-full bg-[var(--md-sys-color-on-surface-variant)] opacity-40"></span>
            @endif
            @if($activeContact->profile?->department)
                <span class="rounded-md bg-[var(--md-sys-color-secondary-container)] px-1.5 py-0.5 text-[10px] font-medium text-[var(--md-sys-color-on-secondary-container)]"
                      title="{{ $activeContact->profile->department->tooltipLabel() }}">
                    {{ $activeContact->profile->department->displayLabel() }}
                </span>
                <span class="h-1 w-1 rounded-full bg-[var(--md-sys-color-on-surface-variant)] opacity-40"></span>
            @endif
            <span class="truncate font-medium text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_70%,transparent)]">
                {{ toJalaliRelative($activeContact->last_seen) ?: 'نامشخص' }}
            </span>
        </div>
        @include('livewire.dashboard.contact.search')
    </div>

    <div class="flex flex-wrap items-center gap-1">
        <button type="button" x-on:click="openMessageSearch()" aria-label="جستجوی پیام" title="جستجوی پیام"
                class="flex h-8 w-8 items-center justify-center rounded-xl transition-all duration-200 ease-out active:scale-95"
                :class="searchMessages ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)]' : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)]'">
            <span class="material-symbols-rounded text-base">search</span>
        </button>

        <button type="button" x-on:click="$store.sound.toggleMute({{ $activeContact->id }}, 'contact')"
                :aria-pressed="$store.sound.isMuted({{ $activeContact->id }}, 'contact')"
                :aria-label="$store.sound.isMuted({{ $activeContact->id }}, 'contact') ? 'باصدا کردن' : 'بی‌صدا کردن'"
                :title="$store.sound.isMuted({{ $activeContact->id }}, 'contact') ? 'باصدا کردن' : 'بی‌صدا کردن'"
                class="flex h-8 w-8 items-center justify-center rounded-xl transition-all duration-200 ease-out active:scale-95"
                :class="$store.sound.isMuted({{ $activeContact->id }}, 'contact') ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)]' : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)]'">
            <span class="material-symbols-rounded text-base" x-text="$store.sound.isMuted({{ $activeContact->id }}, 'contact') ? 'volume_off' : 'volume_up'"></span>
        </button>

        <button type="button" @click="toggleHighlight()" aria-label="پیش زمینه چت" title="پیش زمینه چت"
                class="flex h-8 w-8 items-center justify-center rounded-xl transition-all duration-200 ease-out active:scale-95"
                :class="isHighlighted ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)]' : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)]'">
            <span class="material-symbols-rounded text-base" x-text="isHighlighted ? 'hide_image' : 'texture'"></span>
        </button>

        <button @click="toggleMaximize()"
                :title="max ? 'کوچک کردن' : 'بزرگ کردن'"
                aria-label="تغییر اندازه"
                class="flex h-8 w-8 items-center justify-center rounded-xl transition-all duration-200 ease-out active:scale-95"
                :class="max ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)]' : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)]'">
            <span class="material-symbols-rounded text-base" x-text="max ? 'close_fullscreen' : 'open_in_full'" aria-hidden="true"></span>
        </button>

        <button x-on:click="showInfo = !showInfo" aria-label="اطلاعات بیشتر" title="اطلاعات بیشتر"
                class="flex h-8 w-8 items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] transition-all duration-200 ease-out hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)] active:scale-95">
            <span class="material-symbols-rounded text-base">info</span>
        </button>
    </div>
</header>
