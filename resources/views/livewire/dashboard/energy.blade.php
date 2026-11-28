<div dir="rtl"
     x-data="{
     activeTab: @entangle('activeTab').live,
     canProceed: @entangle('canProceed'),
     canSubmit: @entangle('canSubmit'),
     step: @entangle('step'),
     sections: @js($sections ?? []),
     categoryKeys: @js($categoryKeys ?? []),
}"
     class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto animate-fade"
    >

    <div class="max-w-[88rem] mx-auto page-wrapper">
        <x-ui.title
            icon="energy"
            :title="$activeTab === 'chart' ? 'پرسشنامه انرژی (نتایج)' : 'پرسشنامه انرژی'"
            count="{{ $this->userTestCount }}"
        >
            <x-slot:actions>
                <x-ui.buttons.module-links :active="$activeTab" :tabs="[
                    ['id' => 'survey', 'icon' => 'poll', 'label' => 'پرسشنامه', 'click' => '$wire.switchTab(\'survey\')'],
                    ['id' => 'chart', 'icon' => 'bar_chart', 'label' => 'نتایج', 'click' => '$wire.switchTab(\'chart\')'],
                ]"/>
                <x-ui.buttons.title-more guide="energy">
                    <x-slot:actions>
                        <button
                            type="button"
                            @click="$dispatch('open-modal', { name: 'energy-badge-legend' })"
                            title="راهنمای نشانگر اعلان"
                            class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
                        >
                            <span class="material-symbols-rounded text-lg">notifications</span>
                        </button>
                        <button
                            type="button"
                            @click="$dispatch('open-modal', { name: 'energy-legend' })"
                            title="راهنمای پرسشنامه انرژی"
                            class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
                        >
                            <span class="material-symbols-rounded text-lg">help</span>
                        </button>
                    </x-slot:actions>
                    <x-slot:menu>
                        <x-ui.modals.menu-row icon="notifications" label="راهنمای نشانگر اعلان" click="open = false; $dispatch('open-modal', { name: 'energy-badge-legend' })"/>
                        <x-ui.modals.menu-row icon="help" label="راهنمای پرسشنامه انرژی" click="open = false; $dispatch('open-modal', { name: 'energy-legend' })"/>
                    </x-slot:menu>
                </x-ui.buttons.title-more>
            </x-slot:actions>
        </x-ui.title>

        <x-dashboard.modal.badge-legend
            name="energy-badge-legend"
            :items="[\App\Services\Menu\BadgeLegendCatalog::get('energy-controller')]"
            title="راهنمای نشانگر انرژی"
        />

        <x-ui.modals.dialog name="energy-legend" title="راهنمای پرسشنامه انرژی">
            @include('livewire.dashboard.energy.legend')
        </x-ui.modals.dialog>

        <div class="w-full mb-8">
            <div x-show="activeTab==='survey'"
                 class="w-full animate-fade"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
            >

                @if($showSurvey)
                    @php
                        $cat     = $categoryKeys[$step - 1];
                        $qs      = $questions[$cat];
                        $lastIdx = count($qs) - 1;
                    @endphp

                    <div wire:key="energy-survey" class="max-w-2xl mx-auto px-4">

                        <div
                            class="rounded-2xl border border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface)] shadow-sm overflow-hidden">
                            @include('livewire.dashboard.energy.header')

                            @include('livewire.dashboard.energy.answers')

                            @include('livewire.dashboard.energy.navigations')
                        </div>
                    </div>
                @else
                    @include('livewire.dashboard.energy.after-test')
                @endif
            </div>
            @if($activeTab === 'chart')
                <div wire:key="energy-chart-pane" class="w-full animate-fade">
                    <livewire:dashboard.energy.chart wire:key="energy-chart"/>
                </div>
            @endif
        </div>
    </div>
</div>
