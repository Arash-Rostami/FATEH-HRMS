@php
    $p = $this->presenter;
    $header = $p->channelHeader($this->activeChannel);
@endphp

<header class="relative z-10 flex flex-shrink-0 items-center gap-4 border-b px-5 py-3 transition-all duration-300
               bg-[color-mix(in_srgb,var(--md-sys-color-surface)_92%,transparent)]
               border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_60%,transparent)]
               shadow-[0_8px_32px_color-mix(in_srgb,var(--md-sys-color-primary)_15%,transparent)]">

    <button x-on:click="backToList()" aria-label="بازگشت"
            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] transition-all duration-200 ease-out hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)] active:scale-95 md:hidden">
        <span class="material-symbols-rounded text-base">arrow_forward</span>
    </button>

    <div class="relative flex-shrink-0">
        <div class="flex h-11 w-11 select-none items-center justify-center rounded-2xl shadow-sm ring-1 ring-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)]"
             style="background: color-mix(in srgb, {{ $header['type_color'] }} 14%, transparent);">
            <span class="material-symbols-rounded text-xl" style="color: {{ $header['type_color'] }}">{{ $header['type_icon'] }}</span>
        </div>
    </div>

    <div class="relative min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
            <h2 class="truncate text-base font-bold tracking-tight text-[var(--md-sys-color-on-surface)]">{{ $header['name'] }}</h2>
            <span @class([
                    'rounded-md px-2 py-0.5 text-[10px] font-bold tracking-wide',
                    'bg-[color-mix(in_srgb,var(--md-sys-color-success)_15%,transparent)] text-[var(--md-sys-color-success)]' => $header['type'] === 'open',
                    'bg-[color-mix(in_srgb,var(--md-sys-color-warning)_15%,transparent)] text-[var(--md-sys-color-warning)]' => $header['type'] === 'private',
                ]) title="{{ $header['type_label'] }}">
                {{ $header['type_label'] }}
            </span>
        </div>
        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs">
            @if(!empty($header['slug_handle']))
                <span class="truncate font-semibold text-[var(--md-sys-color-primary)]" dir="auto">{{ $header['slug_handle'] }}</span>
                <span class="h-1 w-1 rounded-full bg-[var(--md-sys-color-on-surface-variant)] opacity-40"></span>
            @endif
            <span class="truncate font-medium text-[var(--md-sys-color-on-surface-variant)]">{{ $header['members_count'] }} عضو</span>
            <span class="h-1 w-1 rounded-full bg-[var(--md-sys-color-on-surface-variant)] opacity-40"></span>
            <span class="truncate font-medium text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_70%,transparent)]">{{ $header['owner_name'] }}</span>
        </div>
        @include('livewire.dashboard.channel.search')
    </div>

    <div class="flex flex-wrap items-center gap-1">
        <button type="button" x-on:click="openMessageSearch()" aria-label="جستجوی پیام" title="جستجوی پیام"
                class="flex h-8 w-8 items-center justify-center rounded-xl transition-all duration-200 ease-out active:scale-95"
                :class="searchMessages ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)]' : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)]'">
            <span class="material-symbols-rounded text-base">search</span>
        </button>

        <button type="button" x-on:click="$store.sound.toggleMute({{ $header['id'] }})"
                :aria-pressed="$store.sound.isMuted({{ $header['id'] }})"
                :aria-label="$store.sound.isMuted({{ $header['id'] }}) ? 'باصدا کردن کانال' : 'بی‌صدا کردن کانال'"
                :title="$store.sound.isMuted({{ $header['id'] }}) ? 'باصدا کردن کانال' : 'بی‌صدا کردن کانال'"
                class="flex h-8 w-8 items-center justify-center rounded-xl transition-all duration-200 ease-out active:scale-95"
                :class="$store.sound.isMuted({{ $header['id'] }}) ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)]' : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)]'">
            <span class="material-symbols-rounded text-base" x-text="$store.sound.isMuted({{ $header['id'] }}) ? 'volume_off' : 'volume_up'"></span>
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

        <button x-on:click="leaveChannel({{ $header['id'] }})"
                aria-label="خروج از کانال" title="خروج از کانال"
                class="flex h-8 w-8 items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] transition-all duration-200 ease-out hover:bg-[var(--md-sys-color-error)] hover:text-[var(--md-sys-color-on-error)] hover:shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-error)_40%,transparent)] active:scale-95">
            <span class="material-symbols-rounded text-base">logout</span>
        </button>
    </div>
</header>
