@php
    $p = $this->presenter;
    $header = $p->channelHeader($this->activeChannel);
@endphp
<header class="border-runner flex-shrink-0 flex items-center gap-3 px-4 md:px-5 py-2.5 border-b z-10
               bg-[color-mix(in_srgb,var(--md-sys-color-surface)_90%,transparent)]
               border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]">

    <button x-on:click="backToList()" aria-label="بازگشت"
            class="md:hidden min-w-10 min-h-10 rounded-lg flex items-center justify-center flex-shrink-0
                   bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]
                   hover:brightness-95 active:scale-90 transition-all">
        <span class="material-symbols-rounded text-[18px]">arrow_forward</span>
    </button>

    <div class="relative flex-shrink-0">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center select-none shadow-sm ring-1 ring-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)]"
             style="background: color-mix(in srgb, {{ $header['type_color'] }} 14%, transparent);">
            <span class="material-symbols-rounded text-xl" style="color: {{ $header['type_color'] }}">{{ $header['type_icon'] }}</span>
        </div>
    </div>

    <div class="flex-1 min-w-0 relative">
        <div class="flex items-center gap-1.5 flex-wrap">
            <h2 class="text-sm font-semibold tracking-tight text-[var(--md-sys-color-on-surface)] truncate">{{ $header['name'] }}</h2>
            <span class="text-[9px] font-bold px-1.5 py-px rounded
                         @class([
                             'bg-[color-mix(in_srgb,var(--md-sys-color-success)_15%,transparent)] text-[var(--md-sys-color-success)]' => $header['type'] === 'open',
                             'bg-[color-mix(in_srgb,var(--md-sys-color-warning)_15%,transparent)] text-[var(--md-sys-color-warning)]' => $header['type'] === 'private',
                         ])"
                  title="{{ $header['type_label'] }}">{{ $header['type_label'] }}</span>
        </div>
        <div class="flex items-center gap-2 mt-px flex-wrap">
            @if(!empty($header['slug_handle']))
                <span class="text-[10px] text-[var(--md-sys-color-primary)] truncate font-medium" dir="auto">{{ $header['slug_handle'] }}</span>
                <span class="w-[2px] h-[2px] rounded-full bg-[var(--md-sys-color-on-surface-variant)] opacity-30"></span>
            @endif
            <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] truncate">
                {{ $header['members_count'] }} عضو
            </span>
            <span class="w-[2px] h-[2px] rounded-full bg-[var(--md-sys-color-on-surface-variant)] opacity-30"></span>
            <span class="text-[10px] text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_60%,transparent)] truncate">
                {{ $header['owner_name'] }}
            </span>
        </div>
        @include('livewire.dashboard.channel.search')
    </div>

    <div class="flex items-center gap-1.5 flex-wrap">
        <button type="button" x-on:click="openMessageSearch()" aria-label="جستجوی پیام" title="جستجوی پیام"
                class="min-w-10 min-h-10 rounded-lg flex items-center justify-center transition-all
                       bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]
                       active:scale-90"
                :class="searchMessages ? '!bg-[var(--md-sys-color-primary)] !text-[var(--md-sys-color-on-primary)]' : 'hover:brightness-95'">
            <span class="material-symbols-rounded text-[18px]">search</span>
        </button>

        <button type="button" @click="toggleHighlight()" aria-label="پیش زمینه چت" title="پیش زمینه چت"
                class="min-w-10 min-h-10 rounded-lg flex items-center justify-center transition-all
                       bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]
                       active:scale-90"
                :class="isHighlighted ? '!bg-[var(--md-sys-color-primary)] !text-[var(--md-sys-color-on-primary)]' : 'hover:brightness-95'">
            <span class="material-symbols-rounded text-[18px]" x-text="isHighlighted ? 'hide_image' : 'texture'"></span>
        </button>

        <button @click="toggleMaximize()"
                :title="max ? 'کوچک کردن' : 'بزرگ کردن'"
                class="min-w-10 min-h-10 rounded-lg flex items-center justify-center transition-all
                       bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]
                       active:scale-90" aria-label="تغییر اندازه">
            <span class="material-symbols-rounded text-[20px]" x-text="max ? 'close_fullscreen' : 'open_in_full'" aria-hidden="true"></span>
        </button>

        <button x-on:click="showInfo = !showInfo" aria-label="اطلاعات بیشتر" title="اطلاعات بیشتر"
                class="min-w-10 min-h-10 rounded-lg flex items-center justify-center transition-all
                       bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]
                       hover:!bg-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary)]
                       active:scale-90">
            <span class="material-symbols-rounded text-[18px]">info</span>
        </button>

        <button x-on:click="leaveChannel({{ $header['id'] }})"
                aria-label="خروج از کانال" title="خروج از کانال"
                class="min-w-10 min-h-10 rounded-lg flex items-center justify-center transition-all
                       bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]
                       hover:!bg-[var(--md-sys-color-error)] hover:text-[var(--md-sys-color-on-error)]
                       active:scale-90">
            <span class="material-symbols-rounded text-[18px]">logout</span>
        </button>
    </div>
</header>
