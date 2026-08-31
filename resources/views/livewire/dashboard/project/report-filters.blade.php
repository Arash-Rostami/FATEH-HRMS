@php($opts = $options)
@php($dropdowns = [
    [
        'field' => 'scheme',
        'icon' => 'schema',
        'label' => 'طرح',
        'allLabel' => 'همه طرح‌ها',
        'options' => collect($opts['schemes'] ?? [])->map(fn($s) => ['value' => $s, 'label' => $s])->values()->all(),
    ],
    [
        'field' => 'department',
        'icon' => 'corporate_fare',
        'label' => 'دپارتمان',
        'allLabel' => 'همه دپارتمان‌ها',
        'options' => collect($opts['departments'] ?? [])->map(fn($name, $code) => ['value' => $code, 'label' => $name])->values()->all(),
    ],
    [
        'field' => 'assignee',
        'icon' => 'person',
        'label' => 'مسئول',
        'allLabel' => 'همه مسئول‌ها',
        'options' => collect($opts['assignees'] ?? [])->map(fn($name, $id) => ['value' => $id, 'label' => $name])->values()->all(),
    ],
    [
        'field' => 'priority',
        'icon' => 'flag',
        'label' => 'اولویت',
        'allLabel' => 'همه اولویت‌ها',
        'options' => [['value' => 'urgent', 'label' => 'فوری'], ['value' => 'high', 'label' => 'بالا'], ['value' => 'medium', 'label' => 'متوسط'], ['value' => 'low', 'label' => 'پایین']],
    ],
])
<div class="flex flex-wrap items-center gap-2">
    <div class="relative flex-1 min-w-[200px]">
        <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-on-surface-variant)] absolute top-1/2 -translate-y-1/2 right-3 pointer-events-none">search</span>
        <input type="text" wire:model.live.debounce.300ms="reportSearch" placeholder="جستجو در عنوان و توضیحات…"
               @class([
                   'w-full h-9 pr-9 pl-9 rounded-xl text-xs bg-[var(--md-sys-color-surface-container-highest)] border outline-none transition-colors text-[var(--md-sys-color-on-surface)] placeholder:text-[var(--md-sys-color-on-surface-variant)]/60',
                   'border-[var(--md-sys-color-primary)]' => $reportSearch !== '',
                   'border-[var(--md-sys-color-outline-variant)]' => $reportSearch === '',
               ])/>
        @if($reportSearch !== '')
            <button type="button" wire:click="$set('reportSearch', '')" class="absolute top-1/2 -translate-y-1/2 left-2 text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] transition-colors">
                <span class="material-symbols-rounded text-[16px]">close</span>
            </button>
        @endif
    </div>

    <button type="button" wire:click="setReportStatusFilter('')" aria-pressed="{{ $reportStatusFilter === '' ? 'true' : 'false' }}"
            @class([
                'inline-flex items-center h-9 px-3 rounded-xl text-xs font-medium transition-colors',
                'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' => $reportStatusFilter === '',
                'bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)]' => $reportStatusFilter !== '',
            ])>
        همه
    </button>

    @foreach($dropdowns as $dd)
        @php($current = match ($dd['field']) {
            'scheme' => $reportSchemeFilter,
            'department' => $reportDepartmentFilter,
            'assignee' => $reportAssigneeFilter,
            'priority' => $reportPriorityFilter,
        })
        <div x-data="{ open: false }" @click.outside="open = false" class="relative">
            <button type="button" @click="open = !open"
                    @class([
                        'inline-flex items-center gap-1.5 h-9 px-3 rounded-xl text-xs font-medium border transition-colors',
                        'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] border-transparent' => $current !== null,
                        'bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline-variant)]' => $current === null,
                    ])>
                <span class="material-symbols-rounded text-[14px]">{{ $dd['icon'] }}</span>
                <span>{{ $dd['label'] }}</span>
                <span class="material-symbols-rounded text-[16px] transition-transform duration-200" :class="open ? 'rotate-180' : ''">arrow_drop_down</span>
            </button>
            <div x-show="open" x-cloak
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1 scale-[0.97]"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="absolute mt-2 w-56 overflow-hidden rounded-2xl bg-[var(--tool-amethyst-bg)] border border-[var(--tool-amethyst-color)]/30 shadow-[0_12px_32px_color-mix(in_srgb,var(--md-sys-color-scrim)_22%,transparent)] z-50">
                <div class="flex items-center justify-between gap-2 px-3 py-1.5 border-b border-[var(--tool-amethyst-color)]/25">
                    <span class="flex items-center gap-1.5 text-[11px] font-bold text-[var(--tool-amethyst-color)]">
                        <span class="material-symbols-rounded text-[15px]">{{ $dd['icon'] }}</span>
                        {{ $dd['allLabel'] }}
                    </span>
                    @if($current !== null)
                        <button type="button" wire:click="setReportFilter('{{ $dd['field'] }}', '')" @click="open = false" class="text-[11px] font-bold text-[var(--tool-amethyst-color)] hover:opacity-70 transition-opacity">پاک کردن</button>
                    @endif
                </div>
                <ul class="max-h-40 overflow-y-auto custom-scrollbar">
                    @foreach($dd['options'] as $opt)
                        @php($isActive = (string) $current === (string) $opt['value'])
                        <li>
                            <button type="button" wire:click="setReportFilter('{{ $dd['field'] }}', {{ \Illuminate\Support\Js::from($opt['value']) }})" @click="open = false"
                                    @class([
                                        'w-full text-right px-3 py-2 text-xs transition-colors flex items-center gap-2',
                                        'bg-[var(--tool-amethyst-color)]/20 text-[var(--tool-amethyst-text)] font-bold' => $isActive,
                                        'text-[var(--tool-amethyst-text)] hover:bg-[var(--tool-amethyst-color)]/15' => !$isActive,
                                    ])>
                                <span dir="auto">{{ $opt['label'] }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endforeach

    <a href="{{ route('tasksheet') }}" target="_blank"
       class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl text-xs font-medium bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)]">
        <span class="material-symbols-rounded text-[14px]">assignment_turned_in</span>
        <span>گزارش تسک‌شیت</span>
    </a>
</div>