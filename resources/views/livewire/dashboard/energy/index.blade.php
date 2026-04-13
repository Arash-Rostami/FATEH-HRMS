<div dir="rtl"
     x-data="{
     activeTab: @entangle('activeTab').live,
     canProceed: @entangle('canProceed'),
     canSubmit: @entangle('canSubmit'),
     step: @entangle('step'),
     sections: @js($sections ?? []),
     categoryKeys: @js($categoryKeys ?? []),
}"
     class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto"
     style="scrollbar-width: thin; scrollbar-color: color-mix(in srgb, var(--md-sys-color-primary) 30%, transparent) transparent;">

    <div class="max-w-[88rem] mx-auto page-wrapper">
        <x-dashboard.tab.title
            icon="energy"
            title="پرسشنامه انرژی"
            count="{{ $this->userTestCount }}"
        />

        <x-dashboard.button.tab-selector
            :activeTab="$activeTab"
            class="gap-1"
            :tabs="[
                    ['id' => 'survey','label' => 'پرسشنامه','icon' => 'poll'],
                    ['id' => 'chart','label' => 'نتایج','icon' => 'bar_chart']
                  ]"
        />

        <div class="flex justify-center mb-8">

            <div x-show="activeTab==='survey'"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="animate-fade">

                @if($showSurvey)
                    @php
                        $cat     = $categoryKeys[$step - 1];
                        $qs      = $questions[$cat];
                        $lastIdx = count($qs) - 1;
                    @endphp

                    <div class="max-w-2xl mx-auto px-4">

                        <div class="flex items-center justify-center gap-1 mb-8" role="progressbar">
                            @foreach($categoryKeys as $i => $catKey)
                                @php
                                    $done    = $step > $i + 1;
                                    $current = $step === $i + 1;
                                @endphp
                                <div class="flex flex-col items-center gap-1.5">
                                    <div @class([
                                'w-10 h-10 rounded-2xl flex items-center justify-center transition-all duration-300',
                                'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md' => $done,
                                'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] ring-2 ring-[var(--md-sys-color-primary)] ring-offset-2 ring-offset-[var(--md-sys-color-surface)]' => $current,
                                'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]' => !$done && !$current,
                            ])>
                                <span class="material-symbols-rounded text-[20px] font-fill">
                                    {{ $done ? 'check' : $presenter->getIcon($catKey) }}
                                </span>
                                    </div>
                                    <span @class([
                                'text-[10px] font-bold',
                                'text-[var(--md-sys-color-primary)]' => $current,
                                'text-[var(--md-sys-color-on-surface-variant)]' => !$current,
                            ])>{{ $presenter->formatSectionTitle($sections[$catKey]) }}</span>
                                </div>
                                @if(!$loop->last)
                                    <div @class([
                                'w-8 sm:w-14 h-0.5 mb-5 transition-all duration-500',
                                'bg-[var(--md-sys-color-primary)]' => $step > $i + 1,
                                'bg-[var(--md-sys-color-outline-variant)]/40' => $step <= $i + 1,
                            ])></div>
                                @endif
                            @endforeach
                        </div>

                        <div
                            class="rounded-2xl border border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface)] shadow-sm overflow-hidden">
                            {{-- Card header --}}
                            <div class="px-5 py-4 border-b border-[var(--md-sys-color-outline-variant)]/40"
                                 style="background: color-mix(in srgb, var({{ $presenter->getContainerColorVar($cat) }}) 25%, var(--md-sys-color-surface))">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                                         style="background: var({{ $presenter->getContainerColorVar($cat) }}); color: var({{ $presenter->getOnContainerColorVar($cat) }})">
                                    <span
                                        class="material-symbols-rounded text-[18px] font-fill">{{ $presenter->getIcon($cat) }}</span>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-widest text-[var(--md-sys-color-on-surface-variant)]">
                                            {{ $presenter->formatSectionTitle($sections[$cat]) }}
                                        </p>
                                    </div>
                                    <div class="mr-auto flex items-center gap-1.5">
                                        @for($i = 1; $i <= $totalSteps; $i++)
                                            <div @class([
                                        'h-1.5 rounded-full transition-all duration-300',
                                        'w-6 bg-[var(--md-sys-color-primary)]' => $step === $i,
                                        'w-2 bg-[var(--md-sys-color-primary)]' => $step > $i,
                                        'w-2 bg-[var(--md-sys-color-outline-variant)]/50' => $step < $i,
                                    ])></div>
                                        @endfor
                                    </div>
                                </div>
                                <p class="mt-3 text-sm font-bold leading-relaxed text-[var(--md-sys-color-on-surface)]">
                                    {{ $prompts[$cat] }}
                                </p>
                            </div>

                            {{-- Answers --}}
                            <div class="p-4 space-y-2.5">
                                @foreach($qs as $idx => $question)
                                    @php
                                        $isChecked = $answers[$cat][$idx] ?? false;
                                        $isNone    = $idx === $lastIdx;
                                    @endphp
                                    <label class="block cursor-pointer" wire:key="opt-{{ $cat }}-{{ $idx }}">
                                        <input type="checkbox"
                                               wire:model.live="answers.{{ $cat }}.{{ $idx }}"
                                               class="sr-only">
                                        <div @class([
                                                    'flex items-start gap-3 p-3.5 rounded-xl border-2 transition-all duration-200 select-none',
                                                    'border-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-primary-container)]' => $isChecked && !$isNone,
                                                    'border-[var(--md-sys-color-secondary)] bg-[var(--md-sys-color-secondary-container)]' => $isChecked && $isNone,
                                                    'border-[var(--md-sys-color-outline-variant)]/60 bg-[var(--md-sys-color-surface)] hover:bg-[var(--md-sys-color-surface-variant)]/30' => !$isChecked,
                                                ])>
                                            <div @class([
                                                        'w-5 h-5 rounded-md border-2 flex items-center justify-center flex-shrink-0 mt-0.5 transition-all duration-200',
                                                        'border-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-primary)]' => $isChecked && !$isNone,
                                                        'border-[var(--md-sys-color-secondary)] bg-[var(--md-sys-color-secondary)]' => $isChecked && $isNone,
                                                        'border-[var(--md-sys-color-outline-variant)]' => !$isChecked,
                                                    ])>
                                                @if($isChecked)
                                                    <span class="material-symbols-rounded font-fill text-white"
                                                          style="font-size:13px">check</span>
                                                @endif
                                            </div>
                                            <span @class([
                                                        'text-sm leading-relaxed',
                                                        'text-[var(--md-sys-color-on-primary-container)] font-medium' => $isChecked && !$isNone,
                                                        'text-[var(--md-sys-color-on-secondary-container)] font-bold' => $isChecked && $isNone,
                                                        'text-[var(--md-sys-color-on-surface)]' => !$isChecked && !$isNone,
                                                        'text-[var(--md-sys-color-on-surface-variant)] font-semibold' => !$isChecked && $isNone,
                                                    ])>
                                                {{ $question }}
                                            </span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            @include('livewire.dashboard.energy.partials.navigations')
                        </div>
                    </div>
                @else
                    @include('livewire.dashboard.energy.partials.after-test')
                @endif
            </div>
            <div x-show="activeTab==='chart'" class="animate-fade">
                <livewire:dashboard.energy.chart/>
            </div>
        </div>
    </div>
</div>
