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
     style="scrollbar-width: thin; scrollbar-color: color-mix(in srgb, var(--md-sys-color-primary) 30%, transparent) transparent;">

    <div class="max-w-[88rem] mx-auto page-wrapper">
        <x-ui.placeholder/>

        <x-ui.title
            icon="energy"
            title="پرسشنامه انرژی"
            count="{{ $this->userTestCount }}"
        />

        <x-ui.buttons.tab-selector
            :activeTab="$activeTab"
            class="gap-1"
            :tabs="[
                    ['id' => 'survey','label' => 'پرسشنامه','icon' => 'poll'],
                    ['id' => 'chart','label' => 'نتایج','icon' => 'bar_chart']
                  ]"
        />

        <div class="flex justify-center mb-8">
            <div x-show="activeTab==='survey'"
                 class="animate-fade"
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

                    <div class="max-w-2xl mx-auto px-4">

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
            <div x-show="activeTab==='chart'"
                 class="animate-fade">
                <livewire:dashboard.energy.chart/>
            </div>
        </div>
    </div>
</div>
