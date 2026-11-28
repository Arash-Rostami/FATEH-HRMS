<div dir="rtl" class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 animate-fade">
    <div class="max-w-[88rem] mx-auto">
        <x-ui.title icon="video_library" :title="$activeTab === 'media' ? 'دانش سازمانی (رسانه)' : 'دانش سازمانی (گزارش‌ها)'">
            <x-slot:actions>
                <x-ui.buttons.module-links :active="$activeTab" :tabs="[
                    ['id' => 'reports', 'label' => 'گزارش‌ها', 'icon' => 'show_chart', 'route' => route('knowledge', ['tab' => 'reports'])],
                    ['id' => 'media', 'label' => 'رسانه', 'icon' => 'video_library', 'condition' => !tenantOff('knowledge:media'), 'route' => route('knowledge', ['tab' => 'media'])],
                ]"/>
                <x-ui.buttons.title-more id="knowledge-header-tools-menu" wire:ignore>
                    <x-slot:actions>
                        <div id="knowledge-header-tools" wire:ignore></div>
                    </x-slot:actions>
                </x-ui.buttons.title-more>
                <div id="knowledge-header-count" wire:ignore class="flex items-center"></div>
            </x-slot:actions>
        </x-ui.title>

        @if($activeTab === 'reports')
            <div wire:key="knowledge-reports-pane" class="w-full">
                <livewire:dashboard.knowledge.reports wire:key="knowledge-reports" :open="$open"/>
            </div>
        @elseif($activeTab === 'media')
            <div wire:key="knowledge-library-pane" class="w-full">
                <livewire:dashboard.knowledge.library wire:key="knowledge-library" :open="$open"/>
            </div>
        @endif
    </div>
</div>
