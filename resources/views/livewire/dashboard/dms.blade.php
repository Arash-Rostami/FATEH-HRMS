<div dir="rtl"
     x-data="dms()"
     class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto animate-fade"
     style="scrollbar-width: thin; scrollbar-color: color-mix(in srgb, var(--md-sys-color-primary) 30%, transparent) transparent;">

    <div class="max-w-[88rem] mx-auto page-wrapper">

        <x-ui.title
                icon="folder_open"
                :title="$activeTab === 'systematic' ? 'مستندات سیستمی' : 'مستندات غیر سیستمی'"
                :count="$this->totalDocs"
                countLabel="سند"/>

        @include('components.dashboard.header.focus-banner')

        <div class="w-fit z-1 bg-[var(--md-sys-color-surface)] mb-6">
            <x-ui.buttons.tab-selector
                wire:key="tab-selector-{{ $activeTab }}"
                :active-tab="$activeTab"
                :has-a11y="true"
                button-base-class="group relative flex items-center justify-center gap-2 px-6 py-3 text-sm font-semibold rounded-xl transition-all duration-200 outline-none flex-1 min-w-[140px]"
                button-active-class="bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md shadow-[var(--md-sys-color-primary)]/20"
                button-inactive-class="text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] hover:text-[var(--md-sys-color-on-surface)]"
                icon-base-class="material-symbols-rounded text-xl"
                icon-active-class="font-variation-fill"
                icon-inactive-class="opacity-70 group-hover:opacity-100"
                :tabs="[
                    ['id' => 'systematic', 'label' => 'سیستمی', 'icon' => 'account_tree'],
                    ['id' => 'non_systematic', 'label' => 'غیر سیستمی', 'icon' => 'description']
                ]"
            />
        </div>

        <div class="mb-6 z-10 relative">

            @include('livewire.dashboard.dms.filters')

        </div>

        @include('livewire.dashboard.dms.pdf-viewer')

        <div class="space-y-6 relative z-10">

            @include('livewire.dashboard.dms.legend')
            @include('livewire.dashboard.dms.table')

        </div>
    </div>
</div>
