<div dir="rtl" class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 animate-fade">
    <div class="max-w-[88rem] mx-auto">
        <x-ui.buttons.tab-selector
            :activeTab="$activeTab"
            :tabs="[
                ['id' => 'reports', 'label' => 'گزارشات', 'icon' => 'show_chart'],
                ['id' => 'media', 'label' => 'رسانه', 'icon' => 'video_library', 'condition' => !tenantOff('knowledge:media')]
            ]"
        />

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
