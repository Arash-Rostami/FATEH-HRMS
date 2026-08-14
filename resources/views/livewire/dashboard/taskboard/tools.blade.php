<div class="flex flex-col gap-4 w-full">
    <div class="flex items-center gap-3 w-full">
        <div class="flex-1 min-w-0">
            <x-ui.forms.search
                model="search"
                placeholder="جستجو در وظایف..."
            />
        </div>

        <x-ui.buttons.form
            wire:click="openCreateModal"
            aria-label="ایجاد وظیفه جدید"
            icon="add"
            class="ripple-effect shrink-0 whitespace-nowrap"
        >
            وظیفه جدید
        </x-ui.buttons.form>

        <button
            type="button"
            @click="$store.density.toggle()"
            :class="{ 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]': $store.density.compact, 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-highest)]': !$store.density.compact }"
            :title="$store.density.compact ? 'نمایش عادی' : 'نمایش فشرده'"
            :aria-label="$store.density.compact ? 'نمایش عادی' : 'نمایش فشرده'"
            class="ripple-effect flex items-center justify-center shrink-0 w-10 h-10 rounded-xl transition-all duration-200 active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[var(--md-sys-color-primary)]"
        >
            <span class="material-symbols-rounded text-xl" x-text="$store.density.compact ? 'view_comfy' : 'view_compact'"></span>
        </button>

        <button
            type="button"
            wire:click="toggleSelectionMode"
            @class([
                'ripple-effect flex items-center justify-center shrink-0 w-10 h-10 rounded-xl transition-all duration-200 active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[var(--md-sys-color-primary)]',
                'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' => $selectionMode,
                'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-highest)]' => ! $selectionMode,
            ])
            title="انتخاب گروهی"
            aria-label="انتخاب گروهی"
        >
            <span class="material-symbols-rounded text-xl">checklist</span>
        </button>
    </div>

    <div
        x-data="{ dismissed: localStorage.getItem('taskboard-swipe-hint-dismissed') === '1' }"
        x-show="!dismissed"
        x-cloak
        x-transition.opacity.duration.300ms
        @click="dismissed = true; localStorage.setItem('taskboard-swipe-hint-dismissed', '1')"
        role="button"
        aria-label="بستن راهنما"
        class="flex md:hidden items-center justify-center gap-2 px-4 py-2 mx-auto cursor-pointer rounded-full bg-[var(--md-sys-color-surface-container-low)] text-[var(--md-sys-color-on-surface-variant)] text-[12px] font-medium transition-colors hover:bg-[var(--md-sys-color-surface-container)] active:scale-95 w-max"
    >
        <span class="material-symbols-rounded text-[14px] animate-pulse">chevron_right</span>
        <span>برای دیدن سایر ستون‌ها بکشید</span>
        <span class="material-symbols-rounded text-[14px] animate-pulse">chevron_left</span>
    </div>
</div>
