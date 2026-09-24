@props(['activeTab', 'tabs'])

@php
    $perPage = max((int) ceil(count($tabs) / 2), 4);
    $chunks = array_chunk($tabs, $perPage, true);
    $pageOne = $chunks[0] ?? [];
    $pageTwo = $chunks[1] ?? [];
    $pageTwoKeys = array_keys($pageTwo);
    $initialPage = in_array($activeTab, $pageTwoKeys) ? 1 : 0;
@endphp

<aside
    x-data="{ activeTab: @entangle('activeTab'), ...mobile({{ $initialPage }}, @js($pageTwoKeys)) }"
    @touchstart="handleTouchStart"
    @touchend="handleTouchEnd"
    @keydown.window.arrow-right.prevent="!$event.repeat && $wire.navigateTab(1)"
    @keydown.window.arrow-left.prevent="!$event.repeat && $wire.navigateTab(-1)"
    :class="[isAtBottom ? 'bottom-14' : 'bottom-0', labelPreset === 'smart' && barHidden ? 'pointer-events-none' : '']"
    class="lg:hidden fixed inset-x-3 z-50 h-20 pb-[env(safe-area-inset-bottom)] rounded-2xl transition-[bottom] duration-300 ease-in-out"
>
    <div class="absolute -top-5 left-1/2 -translate-x-1/2 z-50" x-show="!(labelPreset === 'smart' && barHidden)" x-transition.opacity.duration.200ms>
        <button @click="toggle()" class="w-10 h-10 rounded-xl flex items-center justify-center bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-lg border-[3px] border-[var(--md-sys-color-surface)] transition-all duration-500 hover:scale-110 active:scale-95">
          <span
              class="material-symbols-rounded text-[20px]"
              :style="{ transform: page === 0 ? 'rotate(0deg)' : 'rotate(180deg)', transition: 'transform 0.5s cubic-bezier(0.34,1.56,0.64,1)' }"
          >swap_horiz</span>
        </button>
    </div>

    <div
        class="relative w-full h-full bg-[var(--md-sys-color-surface-container-high)] border border-[var(--md-sys-color-outline-variant)]/20 rounded-2xl shadow-[0_-8px_24px_color-mix(in_srgb,var(--md-sys-color-shadow)_12%,transparent)] overflow-hidden transition-transform duration-300 ease-in-out"
        :class="labelPreset === 'smart' && barHidden ? 'translate-y-[calc(100%+2rem)] opacity-0' : ''"
    >
        <div class="flex w-[200%] h-full transition-transform duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)]" :class="page === 0 ? 'translate-x-0' : '-translate-x-1/2'">

            <div class="w-1/2 h-full flex items-center justify-between px-4 gap-2">
                @foreach($pageOne as $key => $tab)
                    <button wire:click="setTab('{{ $key }}')" @click="scheduleSmartHide()" class="relative flex-1 h-full max-h-[48px] rounded-xl flex flex-col items-center justify-center gap-0.5 transition-all duration-200 active:scale-90 {{ $activeTab === $key ? 'text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-primary)]/10' : 'text-[var(--md-sys-color-on-surface-variant)] opacity-50' }}">
                        <span class="material-symbols-rounded text-[26px] {{ $activeTab === $key ? 'font-fill' : '' }}">{{ $tab['icon'] }}</span>
                        <span class="text-[10px] font-bold leading-none whitespace-nowrap overflow-hidden text-ellipsis max-w-full px-1" x-show="showLabel('{{ $key }}')" x-transition.opacity.duration.200ms>{{ $tab['label'] }}</span>
                        @if(isset($tab['badge']) && collect((array) $tab['badge'])->contains(fn($k) => $menuState[$k] ?? false))
                            <span class="absolute -top-0.5 -right-0.5 flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-700 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-red-700 border-2 border-[var(--md-sys-color-surface)]"></span>
                            </span>
                        @endif
                    </button>
                @endforeach
                @for($i = count($pageOne); $i < $perPage; $i++)
                    <div class="flex-1"></div>
                @endfor
            </div>

            <div class="w-1/2 h-full flex items-center justify-between px-4 gap-2">
                @foreach($pageTwo as $key => $tab)
                    <button wire:click="setTab('{{ $key }}')" @click="scheduleSmartHide()" class="relative flex-1 h-full max-h-[48px] rounded-xl flex flex-col items-center justify-center gap-0.5 transition-all duration-200 active:scale-90 {{ $activeTab === $key ? 'text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-primary)]/10' : 'text-[var(--md-sys-color-on-surface-variant)] opacity-50' }}">
                        <span class="material-symbols-rounded text-[26px] {{ $activeTab === $key ? 'font-fill' : '' }}">{{ $tab['icon'] }}</span>
                        <span class="text-[10px] font-bold leading-none whitespace-nowrap overflow-hidden text-ellipsis max-w-full px-1" x-show="showLabel('{{ $key }}')" x-transition.opacity.duration.200ms>{{ $tab['label'] }}</span>
                        @if(isset($tab['badge']) && collect((array) $tab['badge'])->contains(fn($k) => $menuState[$k] ?? false))
                            <span class="absolute -top-0.5 -right-0.5 flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-700 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-red-700 border-2 border-[var(--md-sys-color-surface)]"></span>
                            </span>
                        @endif
                    </button>
                @endforeach
                @if(count($pageTwo) < $perPage)
                    <div wire:key="btm-labels-editor" class="shrink-0 flex items-center gap-1 max-h-[48px]">
                        <div class="h-7 w-px bg-[var(--md-sys-color-outline-variant)]/40"></div>
                        <button type="button" @click="labelsOpen = !labelsOpen" title="حالت برچسب‌ها"
                                class="w-9 h-9 rounded-lg bg-[var(--md-sys-color-surface)]/70 border border-[var(--md-sys-color-outline-variant)]/40 flex items-center justify-center transition-all duration-200 active:scale-90"
                                :class="labelsOpen ? 'text-[var(--md-sys-color-primary)] border-[var(--md-sys-color-primary)]/60' : 'text-[var(--md-sys-color-on-surface-variant)]'">
                            <span class="material-symbols-rounded text-[20px]">text_fields</span>
                        </button>
                    </div>
                @endif
                @for($i = count($pageTwo) + (count($pageTwo) < $perPage ? 1 : 0); $i < $perPage; $i++)
                    <div class="flex-1"></div>
                @endfor
            </div>

        </div>
    </div>

    <div x-show="labelPreset === 'smart'" x-cloak x-transition.opacity.duration.200ms
         class="absolute right-3 z-50 pointer-events-auto w-10 h-10 rounded-lg bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/40 shadow-lg flex items-center justify-center cursor-pointer text-[var(--md-sys-color-on-surface-variant)]"
         :class="barHidden ? 'bottom-3' : '-top-4'"
         @click="toggleBar()"
         :title="barHidden ? 'نمایش نوار پایین' : 'پنهان‌کردن نوار پایین'">
        <span class="material-symbols-rounded text-[22px]">apps</span>
    </div>

    <div x-show="labelsOpen" x-cloak x-transition.opacity.duration.200ms @click.outside="labelsOpen = false"
         class="absolute bottom-full right-6 mb-3 w-44 rounded-2xl bg-[var(--md-sys-color-surface-container-high)] border border-[var(--md-sys-color-outline-variant)]/30 shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-shadow)_18%,transparent)] p-1.5 z-50">
        <button type="button" @click="setLabelPreset('all')" class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-[11px] font-bold transition-colors duration-200" :class="labelPreset === 'all' ? 'bg-[var(--md-sys-color-primary)]/10 text-[var(--md-sys-color-primary)]' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'">
            <span class="material-symbols-rounded text-[16px]">visibility</span>
            <span>آیکون + برچسب (پیش‌فرض)</span>
        </button>
        <button type="button" @click="setLabelPreset('icons')" class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-[11px] font-bold transition-colors duration-200" :class="labelPreset === 'icons' ? 'bg-[var(--md-sys-color-primary)]/10 text-[var(--md-sys-color-primary)]' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'">
            <span class="material-symbols-rounded text-[16px]">visibility_off</span>
            <span>فقط آیکون</span>
        </button>
        <button type="button" @click="setLabelPreset('smart')" class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-[11px] font-bold transition-colors duration-200" :class="labelPreset === 'smart' ? 'bg-[var(--md-sys-color-primary)]/10 text-[var(--md-sys-color-primary)]' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'">
            <span class="material-symbols-rounded text-[16px]">auto_awesome</span>
            <span>هوشمند (نوار خودپنهان)</span>
        </button>
    </div>
</aside>
