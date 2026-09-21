@props(['perPage' => 5, 'closeAction' => '', 'orientation' => 'vertical'])

@php
    $isHorizontal = $orientation === 'horizontal';
    $containerClass = $isHorizontal ? 'flex flex-row items-center gap-2' : 'flex flex-col gap-2';
    $dividerClass = $isHorizontal
        ? 'w-px self-stretch bg-[var(--md-sys-color-outline-variant)] opacity-50'
        : 'h-px bg-[var(--md-sys-color-outline-variant)] opacity-50';
    $prevIcon = $isHorizontal ? 'chevron_right' : 'keyboard_arrow_up';
    $nextIcon = $isHorizontal ? 'chevron_left' : 'keyboard_arrow_down';
@endphp

<div x-data="{ page: 0, perPage: {{ $perPage }}, groupTitles: ['تیره', 'میانه', 'روشن'] }" class="{{ $containerClass }}">
    <button @click="$store.appTheme?.toggleMode()"
            class="group relative w-10 h-10 rounded-full flex items-center justify-center bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)] hover:brightness-95 transition-all mx-auto shrink-0">
        <span class="material-symbols-rounded text-[22px]"
              x-text="$store.appTheme?.mode === 'dark' ? 'dark_mode' : 'light_mode'"></span>
        <x-ui.modals.tooltip text="تغییر حالت شب/روز" position="right"/>
    </button>

    <div class="{{ $dividerClass }}"></div>

    <button
        @click="page = (page - 1 + groupTitles.length) % groupTitles.length"
        :title="['روشن','تیره','میانه'][page]"
        class="w-6 h-6 rounded-full flex items-center justify-center mx-auto text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)] transition-all active:scale-90 shrink-0">
        <span class="material-symbols-rounded text-[16px]">{{ $prevIcon }}</span>
    </button>

    <span x-text="groupTitles[page]"
          class="text-[10px] font-medium text-center text-[var(--md-sys-color-on-surface-variant)] tracking-widest leading-none mx-auto opacity-60 shrink-0"></span>

    <div class="{{ $dividerClass }}"></div>

    <template x-for="color in ($store.appTheme?.colors || []).slice(page * perPage, page * perPage + perPage)"
              :key="color.name">
        <button @click="$store.appTheme.set(color.name); {{ $closeAction }}"
                :title="color.title"
                class="relative w-8 h-8 rounded-full mx-auto shadow-md transition-all duration-200 hover:scale-110 active:scale-95 flex items-center justify-center shrink-0"
                :style="'background:' + color.color"
                :class="$store.appTheme?.current === color.name
                    ? 'scale-110 ring-2 ring-white/60 ring-offset-2 ring-offset-[var(--md-sys-color-surface)] shadow-xl animate-pulse'
                    : 'ring-1 ring-black/10'">
            <span x-show="$store.appTheme?.current === color.name"
                  class="material-symbols-rounded text-[var(--header-border-color)] !text-sm drop-shadow">check</span>
        </button>
    </template>

    <button
        @click="page = (page + 1) % groupTitles.length"
        :title="['میانه','روشن','تیره'][page]"
        class="w-6 h-6 rounded-full flex items-center justify-center mx-auto text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)] transition-all active:scale-90 shrink-0">
        <span class="material-symbols-rounded text-[16px]">{{ $nextIcon }}</span>
    </button>
</div>
