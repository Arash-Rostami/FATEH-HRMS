<div
    class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto animate-fade"
   
    dir="rtl"
    x-data="ths()"
    @keydown.escape.window="if(max) toggleMaximize(null)"
>
    <div
        class="transition-all duration-300 max-w-[88rem] mx-auto page-wrapper">
        <x-ui.title
            icon="support_agent"
            title="تیکتینگ"
            :count="$this->totalTickets"
        >
            <x-slot:actions>
                <button
                    type="button"
                    @click="$dispatch('open-modal', { name: 'ths-badge-legend' })"
                    title="راهنمای نشانگر اعلان"
                    class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
                >
                    <span class="material-symbols-rounded text-lg">notifications</span>
                </button>
                <button
                    type="button"
                    @click="$dispatch('open-modal', { name: 'ths-legend' })"
                    title="راهنمای گردش‌کار تیکتینگ"
                    class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
                >
                    <span class="material-symbols-rounded text-lg">help</span>
                </button>
            </x-slot:actions>
        </x-ui.title>

        <x-dashboard.modal.badge-legend
            name="ths-badge-legend"
            :items="[\App\Services\Menu\BadgeLegendCatalog::get('ths-controller')]"
        />

        <x-ui.modals.dialog name="ths-legend" title="راهنمای گردش‌کار تیکتینگ">
            @include('livewire.dashboard.ths.legend')
        </x-ui.modals.dialog>

        @include('components.dashboard.header.focus-chip')

        <x-ui.buttons.tab-selector
            :active-tab="$activeTab"
            :has-a11y="true"
            :tabs="[
                ['id' => 'rate', 'icon' => 'star','label' => 'ارزیابی','condition' => $ticketToRate ?? false],
                ['id' => 'new','icon' => 'add_box','label' => 'تیکت جدید','condition' => !($ticketToRate ?? false)],
                ['id' => 'log','icon' => 'history','label' => 'تاریخچه']
            ]"/>

        <div x-show="$wire.activeTab !== 'log'"
             class='py-4 px-4 md:px-6 min-h-[400px] relative index-10 overflow-x-auto rounded-2xl  bg-[var(--md-sys-color-surface)] shadow-sm'>
            <div x-show="$wire.activeTab === 'new' && !@js($ticketToRate)"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="w-full">
                @include('livewire.dashboard.ths.create')
            </div>
            <div x-show="$wire.activeTab === 'rate' && @js($ticketToRate)"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="w-full">
                @include('livewire.dashboard.ths.rate')
            </div>
        </div>
        <div x-show="$wire.activeTab === 'log'"
             x-transition:enter="animate-fade"
             class="w-full">
            <x-ui.modals.max-backdrop/>
            <div>
                <div class="flex mb-4">
                    <x-ui.buttons.toggle
                        wire:click="toggleActionableFilter"
                        :checked="$listFilter === 'actionable'"
                        title="نیاز به اقدام من"
                        name="listFilter"
                        bordered="true"
                        class="mr-auto"
                    />

                    @if($this->isHead)
                        <div wire:key="ths-head-tools" class="contents">
                            <x-ui.buttons.toggle
                                wire:click="toggleDepartmentFilter"
                                :checked="$listFilter === 'department'"
                                title="تیکت‌های واحد من"
                                name="deptFilter"
                                bordered="true"
                                class="mr-2"
                            />
                        </div>
                    @endif
                </div>

                @if($activeTab === 'log')
                    <div wire:key="ths-log-panel" class="contents">
                        <div wire:key="ths-search-filters" class="contents">
                            <x-ui.forms.filters placeholder="جستجو در تیکت‌ها..." searchModel="ticketSearch"
                                                activeCondition='this.$wire.get("statusFilter") !== "all"'
                                                clearAction='this.$wire.set("ticketSearch", ""); this.$wire.set("statusFilter", "all")'
                                                filterTitle=" " :declutterHide="true">
                                <div class="flex flex-col gap-2">
                                    <span class="text-xs font-medium text-[var(--md-sys-color-on-surface-variant)] px-1">وضعیت تیکت</span>
                                    <div class="flex flex-wrap items-center gap-2 justify-start w-full scale-[0.8] md:scale-[1]">
                                        @foreach(['all' => 'همه', 'open' => 'باز', 'in-progress' => 'در حال بررسی', 'closed' => 'بسته‌شده'] as $statusValue => $statusLabel)
                                            <button
                                                type="button"
                                                wire:key="ths-status-chip-{{ $statusValue }}"
                                                wire:click="$set('statusFilter', '{{ $statusValue }}')"
                                                class="flex shrink-0 items-center gap-2 h-8 px-4 rounded-xl text-sm font-medium border transition-all duration-200"
                                                :class="$wire.statusFilter === '{{ $statusValue }}' ? 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] border-transparent shadow-sm' : 'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline)] hover:bg-[var(--md-sys-color-surface-container-low)]'"
                                            >{{ $statusLabel }}</button>
                                        @endforeach
                                    </div>
                                </div>
                            </x-ui.forms.filters>
                        </div>

                        @include('livewire.dashboard.ths.ticket-table', ['tickets' => $this->activeTickets])
                    </div>
                @endif
            </div>
        </div>
    </div>
    @include('livewire.dashboard.ths.modal')
</div>
