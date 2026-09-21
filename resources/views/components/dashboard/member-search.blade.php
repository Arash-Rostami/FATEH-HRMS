<div class="relative group mb-2" x-data="{ fullscreen: false, get value() { return memberQuery }, set value(v) { memberQuery = v } }">
    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-[var(--md-sys-color-on-surface-variant)] group-focus-within:text-[var(--md-sys-color-primary)] transition-colors">
        <span class="material-symbols-rounded text-[20px]">search</span>
    </div>

    <x-ui.forms.maximize-trigger class="inset-y-0 left-10"/>

    <input type="text" x-model="memberQuery" placeholder="جستجوی کاربر..." autocomplete="off" spellcheck="false"
           class="md3-input w-full pr-10 pl-20 h-10 rounded-xl text-sm outline-none transition-all focus:ring-2 bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] border border-[var(--md-sys-color-outline-variant)]/50"/>

    <button type="button" title="حذف" x-show="memberQuery.length > 0" x-on:click="memberQuery = ''" x-transition:opacity style="display: none;"
            class="absolute inset-y-0 left-0 pl-3 flex items-center text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] transition-opacity duration-200">
        <span class="material-symbols-rounded text-[18px]">close</span>
    </button>

    <x-ui.forms.maximize-overlay icon="search" title="جستجوی کاربر"/>
</div>