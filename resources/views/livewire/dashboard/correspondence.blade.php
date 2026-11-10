@use('App\Services\Menu\BadgeLegendCatalog')
@php
    $poll = (int) config('ai.correspondence.poll_seconds');
    $switchTargets = "toggleView,switchTab('table'),switchTab('report'),switchTab('compose')";
    $pollList = $this->generatingId !== null && ($this->generatingId !== $letterId || $activeTab === 'table');
    $classic = [];
    foreach ($presenter->classicTabs() as $id => $label) {
        $classic[] = ['id' => $id, 'icon' => $id === 'table' ? 'table_rows' : 'edit_note', 'label' => $label];
    }
    $listIcons = ['waiting' => 'inbox', 'mine' => 'person', 'incoming' => 'move_to_inbox', 'all' => 'mail', 'registry' => 'inventory_2', 'templates' => 'content_copy'];
    $listTabs = [];
    foreach ($presenter->listTabs() as $id => $label) {
        $listTabs[] = ['id' => $id, 'icon' => $listIcons[$id] ?? 'mail', 'label' => $label];
    }
@endphp

<div dir="rtl"
     x-data="correspondencePage()"
     x-on:correspondence-open.window="scrollToLetter()"
     class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto animate-fade print:overflow-visible print:h-auto print:p-0">
    <div class="max-w-[88rem] mx-auto page-wrapper flex flex-col gap-4 print:gap-3">
        <div class="print:hidden">
            <x-ui.title icon="mail" :title="__('resources/correspondence/strings.label')">
                <x-slot:actions>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.buttons.view-toggle
                            responsive
                            state="$wire.activeTab"
                            action="toggleView"
                            :modes="$presenter->viewModes()"
                        />
                        <x-ui.buttons.form wire:key="correspondence-blank" type="button" variant="tonal" size="icon" icon="add" title="نامه جدید" aria-label="نامه جدید" class="!h-8 !w-8 !p-0 !rounded-lg" wire:click="blank"/>
                        <button type="button" wire:key="correspondence-badge-legend-button" x-on:click="openModal('correspondence-badge-legend')" title="راهنمای نشانگر اعلان" aria-label="راهنمای نشانگر اعلان" class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors">
                            <span class="material-symbols-rounded !text-[18px]">notifications</span>
                        </button>
                        <button type="button" wire:key="correspondence-legend-button" x-on:click="openModal('correspondence-legend')" title="راهنمای مکاتبات" aria-label="راهنمای مکاتبات" class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors">
                            <span class="material-symbols-rounded !text-[18px]">help</span>
                        </button>
                    </div>
                </x-slot:actions>
            </x-ui.title>
        </div>

        <x-dashboard.modal.badge-legend
            name="correspondence-badge-legend"
            :items="array_values(array_filter([BadgeLegendCatalog::get('correspondence-controller'), BadgeLegendCatalog::get('correspondence-controller:nudge')]))"
            title="راهنمای نشانگر مکاتبات"
        />

        <x-ui.modals.dialog name="correspondence-legend" title="راهنمای مکاتبات">
            @include('livewire.dashboard.correspondence.legend')
        </x-ui.modals.dialog>

        @if($activeTab !== 'compose')
            <div wire:key="correspondence-view-classic" class="contents">
                <div wire:key="correspondence-classic-tabs" class="w-fit max-w-full mx-auto md:mx-0 z-1 print:hidden">
                    <x-ui.buttons.tab-selector :active-tab="$activeTab" :has-a11y="true" class="!mb-0" :tabs="$classic"/>
                </div>
                @if($activeTab === 'table')
                    <div wire:key="correspondence-view-table" x-data="correspondenceTable()" wire:loading.remove wire:target="{{ $switchTargets }}" class="flex flex-col gap-3 print:hidden">
                        @include('livewire.dashboard.correspondence.table')
                    </div>
                @else
                    <div wire:key="correspondence-view-report" wire:loading.remove wire:target="{{ $switchTargets }}" id="correspondence-letter" class="flex flex-col gap-4 min-w-0 print:w-full print:max-w-none">
                        <livewire:dashboard.correspondence.workspace :letter-id="$letterId" :reply-to-id="$replyToId" :key="'correspondence-workspace-'.$epoch" />

                    </div>
                @endif
                @if($pollList)
                    <div wire:key="correspondence-classic-poll" wire:poll.visible.{{ $poll }}s="pollList"></div>
                @endif
            </div>
        @else
            <div wire:key="correspondence-view-compose" wire:loading.remove wire:target="{{ $switchTargets }}" class="grid grid-cols-1 lg:grid-cols-[1fr_minmax(240px,300px)] gap-4 items-start">
                <div id="correspondence-letter" class="flex flex-col gap-4 min-w-0 print:w-full print:max-w-none">
                    <livewire:dashboard.correspondence.workspace :letter-id="$letterId" :reply-to-id="$replyToId" :key="'correspondence-workspace-'.$epoch" />

                </div>
                @if($pollList)
                    <aside wire:key="correspondence-list-polling" wire:poll.visible.{{ $poll }}s="pollList"
                           class="flex flex-col gap-2 lg:sticky lg:top-4 p-3 rounded-2xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] shadow-sm print:hidden">
                        @include('livewire.dashboard.correspondence.list')
                    </aside>
                @else
                    <aside wire:key="correspondence-list-idle"
                           class="flex flex-col gap-2 lg:sticky lg:top-4 p-3 rounded-2xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] shadow-sm print:hidden">
                        @include('livewire.dashboard.correspondence.list')
                    </aside>
                @endif
            </div>
        @endif

        <div wire:key="correspondence-switch-skeleton" wire:loading.flex wire:target="{{ $switchTargets }}" class="flex-col gap-2 print:hidden" role="status" aria-label="در حال بارگذاری...">
            <x-ui.loaders.skeleton.bar height="h-11"/>
            <x-ui.loaders.skeleton.table-stripe :columns="4" :rows="6"/>
        </div>
    </div>
</div>
