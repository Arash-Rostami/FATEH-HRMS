@use('App\Services\Menu\BadgeLegendCatalog')
@php
    $switchTargets = "toggleView,switchTab('table'),switchTab('report'),switchTab('compose')";
    $pollList = $this->generatingId !== null && ($this->generatingId !== $letterId || $activeTab === 'table');
    $tableLabel = collect($classic)->firstWhere('id', 'table')['label'] ?? null;
    $moduleLabel = __('resources/correspondence/strings.label');
    $paneLinks = collect($classic)->map(fn ($t) => $t + ['condition' => $activeTab !== 'compose', 'click' => '$wire.switchTab(\'' . $t['id'] . '\')'])->all();
    $paneLinks[] = ['id' => 'new', 'icon' => 'add', 'label' => 'نامهٔ جدید', 'short' => 'جدید', 'click' => '$wire.blank()'];
@endphp

<div dir="rtl"
     x-data="correspondencePage()"
     x-on:correspondence-open.window="scrollToLetter()"
     class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto animate-fade print:overflow-visible print:h-auto print:p-0">
    <div class="max-w-[88rem] mx-auto page-wrapper flex flex-col gap-4 print:gap-3">
        <div class="print:hidden">
            <x-ui.title icon="mail" :title="$activeTab === 'table' && $tableLabel ? $moduleLabel . ' (' . $tableLabel . ')' : $moduleLabel" :count="$this->totalLetters" countLabel="نامه">
                <x-slot:actions>
                    <x-ui.buttons.module-links :active="$activeTab" :tabs="$paneLinks"/>
                    <x-ui.buttons.title-more guide="correspondence">
                    <x-slot:actions>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.buttons.view-toggle
                            responsive
                            state="$wire.activeTab"
                            action="toggleView"
                            :modes="$presenter->viewModes()"
                        />
                        <button type="button" wire:key="correspondence-badge-legend-button" x-on:click="openModal('correspondence-badge-legend')" title="راهنمای نشانگر اعلان" aria-label="راهنمای نشانگر اعلان" class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors">
                            <span class="material-symbols-rounded !text-[18px]">notifications</span>
                        </button>
                        <button type="button" wire:key="correspondence-legend-button" x-on:click="openModal('correspondence-legend')" title="راهنمای مکاتبات" aria-label="راهنمای مکاتبات" class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors">
                            <span class="material-symbols-rounded !text-[18px]">help</span>
                        </button>
                    </div>
                    </x-slot:actions>
                    <x-slot:menu>
                        <div x-on:click.stop>
                            <x-ui.modals.menu-row icon="edit_note" label="نمای فشرده" role="menuitemradio" checked="$wire.activeTab === 'compose'" click="if ($wire.activeTab !== 'compose') { $wire.activeTab = 'compose'; $wire.toggleView('compose') }"/>
                            <x-ui.modals.menu-row icon="table_rows" label="نمای کلاسیک" role="menuitemradio" checked="$wire.activeTab !== 'compose'" click="if ($wire.activeTab === 'compose') { $wire.activeTab = 'report'; $wire.toggleView('report') }"/>
                        </div>
                        <x-ui.modals.menu-row icon="notifications" label="راهنمای نشانگر اعلان" click="open = false; openModal('correspondence-badge-legend')"/>
                        <x-ui.modals.menu-row icon="help" label="راهنمای مکاتبات" click="open = false; openModal('correspondence-legend')"/>
                    </x-slot:menu>
                    </x-ui.buttons.title-more>
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
                @if($activeTab === 'table')
                    <div wire:key="correspondence-view-table" x-data="correspondenceTable()" wire:loading.remove wire:target="{{ $switchTargets }}" class="flex flex-col gap-3 print:hidden">
                        @include('livewire.dashboard.correspondence.table')
                    </div>
                @else
                    <div wire:key="correspondence-view-report" wire:loading.remove wire:target="{{ $switchTargets }}" id="correspondence-letter" class="flex flex-col gap-4 min-w-0 print:w-full print:max-w-none">
                        <livewire:dashboard.correspondence.workspace :letter-id="$letterId" :reply-to-id="$replyToId" :key="'correspondence-workspace-report-'.$epoch" />

                    </div>
                @endif
                @if($pollList)
                    <div wire:key="correspondence-classic-poll" wire:poll.visible.{{ $poll }}s="pollList"></div>
                @endif
            </div>
        @else
            <div wire:key="correspondence-view-compose" wire:loading.remove wire:target="{{ $switchTargets }}" class="grid grid-cols-1 lg:grid-cols-[1fr_minmax(240px,300px)] gap-4 items-start">
                <div id="correspondence-letter" class="flex flex-col gap-4 min-w-0 print:w-full print:max-w-none">
                    <livewire:dashboard.correspondence.workspace :letter-id="$letterId" :reply-to-id="$replyToId" :key="'correspondence-workspace-compose-'.$epoch" />

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
