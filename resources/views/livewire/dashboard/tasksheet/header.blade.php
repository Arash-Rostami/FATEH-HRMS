@if($readOnly)
    <div class="flex items-center gap-2 rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container-low)] px-4 py-3 text-sm text-[var(--md-sys-color-on-surface-variant)]">
        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">visibility</span>
        این گزارش فقط‌خواندنی است — {{ $presenter->windowStatement($windowStart, $windowEnd) }}
    </div>
@else
    <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-center sm:justify-between gap-3 print:hidden">
        <x-ui.buttons.tab-selector
            class="!mb-0"
            active-tab="tasksheet"
            :tabs="[
                ['id' => 'tasks', 'label' => 'برد وظایف', 'icon' => 'dashboard', 'route' => route('tasks')],
                ['id' => 'projects', 'label' => 'پروژه‌ها', 'icon' => 'workspaces', 'route' => route('projects')],
            ]"
        />

        <div class="flex flex-wrap items-center gap-2">
            <x-ui.hover-popover width="w-72" alignment="top-full right-0 mt-2 origin-top-right">
                <x-slot:trigger>
                    <div @class([
                            'inline-flex items-center gap-1.5 h-9 px-3 rounded-xl text-xs font-medium transition-colors',
                            'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' => $preset === 'custom',
                            'bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)]' => $preset !== 'custom',
                        ])>
                        <span class="material-symbols-rounded text-[16px]">date_range</span>
                        {{ $preset === 'custom' ? $presenter->windowStatement($windowStart, $windowEnd) : 'بازهٔ دلخواه' }}
                    </div>
                </x-slot:trigger>

                <x-slot:body>
                    <div class="p-3 flex flex-col gap-3">
                        <x-ui.forms.date label="از تاریخ" prefix="from" icon="event"
                                          :startYear="\Morilog\Jalali\Jalalian::now()->getYear() - 3"
                                          :endYear="\Morilog\Jalali\Jalalian::now()->getYear()"/>
                        <x-ui.forms.date label="تا تاریخ" prefix="to" icon="event"
                                          :startYear="\Morilog\Jalali\Jalalian::now()->getYear() - 3"
                                          :endYear="\Morilog\Jalali\Jalalian::now()->getYear()"/>
                        <button type="button" wire:click="setCustomRange" @click="open = false"
                                class="inline-flex items-center justify-center h-9 rounded-xl bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] text-xs font-medium">
                            اعمال
                        </button>
                    </div>
                </x-slot:body>
            </x-ui.hover-popover>

            @foreach(['this_month' => 'این ماه', 'last_month' => 'ماه گذشته', 'this_quarter' => 'این فصل'] as $value => $label)
                <button type="button" wire:click="setPreset('{{ $value }}')"
                        @class([
                            'inline-flex items-center h-9 px-3 rounded-xl text-xs font-medium transition-colors',
                            'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' => $preset === $value,
                            'bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)]' => $preset !== $value,
                        ])>
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>
@endif
