@php
    $report = $this->report();
    [$windowStart, $windowEnd] = $presenter->windowBounds($report['window']);
@endphp

<div dir="rtl" x-data="tasksheet(@js($readOnly))" class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto animate-fade">
    <div class="max-w-[88rem] mx-auto page-wrapper flex flex-col gap-4 print:gap-3">
        <x-ui.title icon="assignment_turned_in" title="تسک‌شیت">
            <x-slot:actions>
                @unless($readOnly)
                    <div class="flex items-center gap-2 print:hidden">
                        <x-ui.hover-popover width="w-72" alignment="top-full left-0 mt-2 origin-top-left">
                            <x-slot:trigger>
                                <div title="ارسال برای مدیر"
                                     class="flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-highest)] transition-colors">
                                    <span class="material-symbols-rounded text-[20px]">forward_to_inbox</span>
                                </div>
                            </x-slot:trigger>

                            <x-slot:body>
                                <div class="p-3 flex flex-col gap-3">
                                    <x-ui.forms.select label="گیرنده" name="shareRecipientId" wire:model="shareRecipientId"
                                                        icon="person" x-on:change="rememberRecipient($event.target.value)">
                                        <option value="">انتخاب کنید</option>
                                        @foreach($this->shareRecipientOptions as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </x-ui.forms.select>
                                    <button type="button" wire:click="shareWithManager" @click="open = false"
                                            class="inline-flex items-center justify-center h-9 rounded-xl bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] text-xs font-medium">
                                        ارسال
                                    </button>
                                </div>
                            </x-slot:body>
                        </x-ui.hover-popover>

                        <x-ui.buttons.form wire:click="export" variant="tonal" size="icon" icon="download" title="خروجی اکسل"/>
                        <x-ui.buttons.form type="button" variant="tonal" size="icon" icon="print" title="چاپ گزارش"
                                            x-on:click="printExpandAll()"/>
                    </div>
                @endunless

                <button
                    type="button"
                    @click="$dispatch('open-modal', { name: 'tasksheet-badge-legend' })"
                    title="راهنمای نشانگر اعلان"
                    class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
                >
                    <span class="material-symbols-rounded text-lg">notifications</span>
                </button>
                <button
                    type="button"
                    @click="$dispatch('open-modal', { name: 'tasksheet-legend' })"
                    title="راهنمای تسک‌شیت"
                    class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
                >
                    <span class="material-symbols-rounded text-lg">help</span>
                </button>
            </x-slot:actions>
        </x-ui.title>

        <x-dashboard.modal.badge-legend
            name="tasksheet-badge-legend"
            :items="[\App\Services\Menu\BadgeLegendCatalog::get('tasksheet-controller')]"
        />

        <x-ui.modals.dialog name="tasksheet-legend" title="راهنمای تسک‌شیت">
            @include('livewire.dashboard.tasksheet.legend')
        </x-ui.modals.dialog>

        @include('livewire.dashboard.tasksheet.header', ['report' => $report, 'presenter' => $presenter, 'windowStart' => $windowStart, 'windowEnd' => $windowEnd])
        @include('livewire.dashboard.tasksheet.scorecard', ['report' => $report, 'presenter' => $presenter, 'windowStart' => $windowStart, 'windowEnd' => $windowEnd])
        @include('livewire.dashboard.tasksheet.highlights', ['report' => $report])
        @include('livewire.dashboard.tasksheet.projects-accordion', ['report' => $report, 'presenter' => $presenter])
        @include('livewire.dashboard.tasksheet.standalone-accordion', ['report' => $report, 'presenter' => $presenter])
        @include('livewire.dashboard.tasksheet.activity-accordion')
    </div>
</div>
