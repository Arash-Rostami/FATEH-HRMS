<button type="button" x-on:click="openMessageSearch()" aria-label="جستجوی پیام" title="جستجوی پیام"
        class="h-8 w-8 items-center justify-center rounded-xl transition-all duration-200 ease-out active:scale-95"
        :class="[headerBtnClass(false), searchMessages ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)]' : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)]']">
    <span class="material-symbols-rounded text-base">search</span>
</button>

<button type="button" x-on:click="toggleFilterPanel()" aria-label="فیلتر نوع محتوا" title="فیلتر نوع محتوا"
        class="h-8 w-8 items-center justify-center rounded-xl transition-all duration-200 ease-out active:scale-95"
        :class="[headerBtnClass(false), typeFilterOpen ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)]' : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)]']">
    <span class="material-symbols-rounded text-base" :class="!typeFilterOpen && typeFilter ? 'text-[var(--md-sys-color-primary)]' : ''">filter_alt</span>
</button>

<button type="button" x-on:click="toggleBookmarks()" aria-label="پیام‌های نشان‌شده" title="پیام‌های نشان‌شده"
        class="h-8 w-8 items-center justify-center rounded-xl transition-all duration-200 ease-out active:scale-95"
        :class="[headerBtnClass(false), bookmarksOpen ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)]' : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)]']">
    <span class="material-symbols-rounded text-base" :class="bookmarksOpen ? 'font-fill' : ''">bookmarks</span>
</button>

<span class="h-4 w-px self-center mx-1 bg-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_100%,transparent)]" :class="headerBtnClass(false, 'block')" aria-hidden="true"></span>

{{ $sound ?? '' }}

<div class="items-center" :class="headerBtnClass(false, 'block')">
    @include('livewire.dashboard.messaging.pattern-picker')
</div>

<span class="h-4 w-px self-center mx-1 bg-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_100%,transparent)]" :class="headerBtnClass(false, 'block')" aria-hidden="true"></span>

<button type="button" x-on:click="showInfo = !showInfo" aria-label="اطلاعات بیشتر" title="اطلاعات بیشتر"
        class="h-8 w-8 items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] transition-all duration-200 ease-out hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)] active:scale-95"
        :class="headerBtnClass(false)">
    <span class="material-symbols-rounded text-base">info</span>
</button>

<button type="button" x-on:click="backToList()" aria-label="{{ $closeLabel ?? 'بازگشت' }}" title="{{ $closeLabel ?? 'بازگشت' }}"
        class="h-8 w-8 items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] transition-all duration-200 ease-out hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] active:scale-95"
        :class="headerBtnClass(false)">
    <span class="material-symbols-rounded text-base">arrow_forward</span>
</button>

<button type="button" @click="toggleMaximize()"
        :title="max ? 'کوچک کردن' : 'بزرگ کردن'"
        aria-label="تغییر اندازه"
        class="h-8 w-8 items-center justify-center rounded-xl transition-all duration-200 ease-out active:scale-95"
        :class="[headerBtnClass(false), max ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)]' : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)]']">
    <span class="material-symbols-rounded text-base" x-text="max ? 'close_fullscreen' : 'open_in_full'" aria-hidden="true"></span>
</button>

<button type="button" x-on:click="toggleHeaderDeclutter()" :aria-pressed="headerDeclutter"
        :aria-label="headerDeclutter ? 'نمایش سربرگ' : 'خلوت‌سازی'"
        :title="headerDeclutter ? 'نمایش سربرگ' : 'خلوت‌سازی'"
        class="hidden md:flex h-8 w-8 items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] transition-all duration-200 ease-out hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)] active:scale-95"
        :class="{ 'mx-auto opacity-0': headerDeclutter }">
    <span class="material-symbols-rounded text-base rotate-90" x-text="headerDeclutter ? 'unfold_more' : 'unfold_less'"></span>
</button>
<div x-data="{ open: false, pos: {} }">
    <button type="button" id="chat-header-tools-trigger"
            x-on:click="pos = $el.getBoundingClientRect().toJSON(); open = !open"
            aria-label="ابزارهای بیشتر" title="ابزارهای بیشتر"
            class="md:hidden flex h-8 w-8 items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] transition-all duration-200 ease-out active:scale-95">
        <span class="material-symbols-rounded text-base">more_horiz</span>
    </button>
    <template x-teleport="body">
        <div x-show="open" x-cloak dir="rtl"
             x-on:click.away="if (!document.getElementById('chat-header-tools-trigger')?.contains($event.target)) open = false"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             :style="{ position: 'fixed', top: (pos.bottom + 8) + 'px', left: pos.left + 'px' }"
             class="p-1.5 rounded-xl z-40 bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] shadow-[0_12px_48px_color-mix(in_srgb,var(--md-sys-color-shadow)_18%,transparent)]"
             role="menu" aria-label="ابزارهای بیشتر">
            <button type="button" x-on:click="open = false; openMessageSearch()"
                    class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-medium hover:bg-[var(--md-sys-color-surface-container-high)]/50">
                <span class="material-symbols-rounded text-[16px]">search</span>
                <span>جستجوی پیام</span>
            </button>
            <button type="button" x-on:click="open = false; toggleFilterPanel()"
                    class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-medium hover:bg-[var(--md-sys-color-surface-container-high)]/50">
                <span class="material-symbols-rounded text-[16px]">filter_alt</span>
                <span>فیلتر نوع محتوا</span>
            </button>
            <button type="button" x-on:click="open = false; toggleBookmarks()"
                    class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-medium hover:bg-[var(--md-sys-color-surface-container-high)]/50">
                <span class="material-symbols-rounded text-[16px]">bookmarks</span>
                <span>پیام‌های نشان‌شده</span>
            </button>
            {{ $overflow ?? '' }}
            <button type="button" x-on:click="open = false; $dispatch('open-pattern', { bottom: document.getElementById('chat-header-tools-trigger').getBoundingClientRect().bottom, left: document.getElementById('chat-header-tools-trigger').getBoundingClientRect().left })"
                    class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-medium hover:bg-[var(--md-sys-color-surface-container-high)]/50">
                <span class="material-symbols-rounded text-[16px]">texture</span>
                <span>پیش زمینه چت</span>
            </button>
            <button type="button" x-on:click="open = false; showInfo = !showInfo"
                    class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-medium hover:bg-[var(--md-sys-color-surface-container-high)]/50">
                <span class="material-symbols-rounded text-[16px]">info</span>
                <span>اطلاعات بیشتر</span>
            </button>
            <button type="button" x-on:click="open = false; toggleMaximize()"
                    class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-medium hover:bg-[var(--md-sys-color-surface-container-high)]/50">
                <span class="material-symbols-rounded text-[16px]" x-text="max ? 'close_fullscreen' : 'open_in_full'" aria-hidden="true"></span>
                <span x-text="max ? 'کوچک کردن' : 'بزرگ کردن'"></span>
            </button>
            <button type="button" x-on:click="open = false; toggleHeaderDeclutter()"
                    class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-medium hover:bg-[var(--md-sys-color-surface-container-high)]/50">
                <span class="material-symbols-rounded text-[16px] rotate-90" aria-hidden="true">unfold_less</span>
                <span>خلوت‌سازی</span>
            </button>
        </div>
    </template>
</div>