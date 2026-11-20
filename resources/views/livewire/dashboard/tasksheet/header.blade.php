@if($scopeProjectId)
    <div wire:key="tasksheet-scope-chip" class="print:hidden">
        <x-ui.badge variant="info" class="gap-1.5">
            فقط جدول این پروژه
            @unless($readOnly)
                <button type="button" wire:click="clearScope" title="نمایش همهٔ پروژه‌ها" class="inline-flex items-center">
                    <span class="material-symbols-rounded text-[14px]">close</span>
                </button>
            @endunless
        </x-ui.badge>
        <p class="mt-2 text-xs leading-6 text-[var(--md-sys-color-on-surface-variant)]">فهرست جزئیات به این پروژه محدود شده است. خلاصهٔ گزارش، فعالیت‌ها و خروجی اکسل همچنان کل گزارش را نشان می‌دهند.</p>
    </div>
@endif

@if($this->rangeInvalid)
    <p wire:key="tasksheet-range-invalid" class="text-xs text-[var(--md-sys-color-error)] print:hidden">تاریخ شروع و پایان را کامل و درست وارد کنید. تا آن زمان، بازهٔ معتبر قبلی یا این ماه نمایش داده می‌شود.</p>
@endif

@if($readOnly)
    <div wire:key="tasksheet-shared-header" class="flex items-center gap-2 rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container-low)] px-4 py-3 text-sm text-[var(--md-sys-color-on-surface-variant)]">
        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">visibility</span>
        این گزارش برای مشاهده است؛ نمی‌توانید بازهٔ آن را تغییر دهید. {{ $presenter->windowStatement($windowStart, $windowEnd) }}
    </div>
@else
    <div wire:key="tasksheet-personal-header" class="flex flex-col sm:flex-row sm:flex-wrap sm:items-center sm:justify-between gap-3 print:hidden">
        <x-ui.buttons.tab-selector
            class="!mb-0"
            active-tab="tasksheet"
            :tabs="[
                ['id' => 'tasks', 'label' => 'برد وظایف', 'icon' => 'dashboard', 'route' => route('tasks')],
                ['id' => 'projects', 'label' => 'پروژه‌ها', 'icon' => 'workspaces', 'route' => route('projects')],
            ]"
        />

        <div class="flex flex-wrap items-center gap-2">
            <x-ui.forms.date-span-popover apply="setCustomRange" :clear="false" :basisOptions="false" :active="$preset === 'custom'"/>

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
