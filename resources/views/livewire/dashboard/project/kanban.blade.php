@php
    $taskBoardPresenter = new \App\Livewire\Dashboard\TaskBoard\Presentation\TaskBoardPresenter();
    $dmsPresenter = new \App\Livewire\Dashboard\Dms\Presentation\DmsPresenter();
    $isPersonalBoard = false;
    $activeTab = null;
    $staffMembers = $this->staffMembers;
    $departmentOptions = $this->departmentOptions;
@endphp

<div wire:key="kanban-{{ $activeProjectId }}" x-data="{ moreOpen: false }" @project-kanban-refresh.window="$wire.loadKanbanBoard()">
    <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-2.5 sm:gap-2 p-2 mb-3 rounded-xl bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-low)_88%,transparent)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]">
        <div class="relative flex-1 min-w-[160px] max-w-xs">
            <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-on-surface-variant)] absolute top-1/2 -translate-y-1/2 right-3 pointer-events-none">search</span>
            <input type="text" wire:model.live.debounce.300ms="kanbanSearch" placeholder="جستجو در برد…"
                   class="w-full h-9 pr-9 pl-9 rounded-xl text-xs bg-[var(--md-sys-color-surface-container-highest)] border border-[var(--md-sys-color-outline-variant)] outline-none transition-colors text-[var(--md-sys-color-on-surface)] placeholder:text-[var(--md-sys-color-on-surface-variant)]/60"/>
        </div>

        @php
            $onlyMine = (int) $this->kanbanAssigneeFilter === (int) auth()->id();
        @endphp
        <button type="button" wire:click="toggleKanbanMine" title="فقط وظایف محول‌شده به من"
                class="flex shrink-0 items-center gap-1 px-3 py-1.5 rounded-lg text-[11px] font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)] whitespace-nowrap {{ $onlyMine ? 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] font-bold' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/50' }}">
            <span class="material-symbols-rounded text-[14px]">person_check</span>
            <span>وظایف من</span>
        </button>

        <span class="relative inline-flex shrink-0">
            <button
                type="button"
                @click="moreOpen = !moreOpen"
                class="flex items-center justify-center gap-1 px-3 py-1.5 rounded-lg text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/50 transition-colors focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)] whitespace-nowrap"
            >
                <span>سایر فیلترها</span>
                <span class="material-symbols-rounded text-[14px] transition-transform duration-300" x-bind:class="moreOpen ? 'rotate-180' : ''">expand_more</span>
            </button>
            @if($this->kanbanActiveFilterCount > 0)
                <span class="absolute -top-1 -left-1 flex h-4 w-4 min-w-[16px] items-center justify-center rounded-sm bg-[var(--md-sys-color-tertiary)] px-1 text-[9px] font-bold leading-none text-[var(--md-sys-color-on-tertiary)] shadow ring-2 ring-[var(--md-sys-color-surface)]">{{ $this->kanbanActiveFilterCount > 9 ? '9+' : $this->kanbanActiveFilterCount }}</span>
            @endif
        </span>

        @if($this->kanbanActiveFilterCount > 0)
            <button
                type="button"
                wire:click="clearKanbanFilters"
                class="flex items-center justify-center gap-1 px-3 py-1.5 rounded-lg text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/50 transition-colors focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)] whitespace-nowrap shrink-0"
            >
                <span class="material-symbols-rounded text-[14px]">filter_alt_off</span>
                <span>پاک‌کردن فیلترها</span>
            </button>
        @endif
    </div>

    <div
        x-show="moreOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="flex flex-col sm:flex-row flex-wrap sm:items-center gap-2 p-2 mb-3 rounded-xl bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-low)_88%,transparent)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]"
    >
        <div class="w-full sm:w-40 shrink-0">
            <x-ui.forms.select label="مهلت" name="kanbanDeadlineFilter" wire:model.live="kanbanDeadlineFilter" icon="schedule">
                @php $dfCounts = $this->kanbanDeadlineFilterCounts; @endphp
                @php $deadlineOptions = ['overdue' => 'سررسید گذشته', 'today' => 'امروز', 'week' => 'این هفته']; @endphp
                <option value="">همه مهلت‌ها</option>
                @foreach($deadlineOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}@if(($dfCounts[$value] ?? 0) > 0) ({{ $dfCounts[$value] }})@endif</option>
                @endforeach
            </x-ui.forms.select>
        </div>

        <div class="w-full sm:w-36 shrink-0">
            <x-ui.forms.select label="اولویت" name="kanbanPriorityFilter" wire:model.live="kanbanPriorityFilter" icon="flag">
                <option value="">همه اولویت‌ها</option>
                @foreach(\App\Filament\Resources\TaskResource\Enums\TaskPriority::cases() as $p)
                    <option value="{{ $p->value }}">{{ $p->getLabel() }}</option>
                @endforeach
            </x-ui.forms.select>
        </div>

        @if(count($this->kanbanLabelOptions))
            <div
                class="relative w-full sm:w-36 shrink-0 md3-input-group"
                x-data="{ open: false }"
                @click.away="open = false"
            >
                <button
                    type="button"
                    @click="open = !open"
                    :aria-expanded="open"
                    class="md3-input flex w-full appearance-none items-center pr-10 text-right text-sm"
                >
                    <span class="truncate">
                        {{ count($this->kanbanLabelFilter) ? 'برچسب‌ها (' . count($this->kanbanLabelFilter) . ')' : 'برچسب‌ها' }}
                    </span>
                </button>

                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-[var(--md-sys-color-on-surface-variant)]">
                    <span class="material-symbols-rounded text-[20px]">
                        sell
                    </span>
                </div>

                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-[var(--md-sys-color-on-surface-variant)]">
                    <span
                        class="material-symbols-rounded text-[18px]"
                        :class="{ 'rotate-180': open }"
                    >
                        expand_more
                    </span>
                </div>

                <div
                    x-show="open"
                    x-cloak
                    class="absolute z-40 mt-1 w-full overflow-hidden rounded-lg border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-high)] shadow-xl"
                >
                    <div class="max-h-60 overflow-y-auto custom-scrollbar">
                        @foreach($this->kanbanLabelOptions as $label)
                            @php
                                $isChecked = in_array($label, $this->kanbanLabelFilter, true);
                            @endphp

                            <button
                                type="button"
                                wire:click="toggleKanbanLabelFilter({{ \Illuminate\Support\Js::from($label) }})"
                                @class([
                                    'flex w-full items-center gap-2 px-3 py-2.5 text-right text-xs',
                                    'bg-[var(--md-sys-color-primary-container)]/50 text-[var(--md-sys-color-on-surface)] font-medium' => $isChecked,
                                    'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container)]' => !$isChecked,
                                ])
                            >
                                <span class="material-symbols-rounded text-[17px]">
                                    {{ $isChecked ? 'check_box' : 'check_box_outline_blank' }}
                                </span>

                                <span class="min-w-0 truncate" dir="auto">
                                    {{ $label }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @if(count($this->kanbanSchemeOptions))
            <div class="w-full sm:w-36 shrink-0">
                <x-ui.forms.select label="طرح" name="kanbanSchemeFilter" wire:model.live="kanbanSchemeFilter" icon="schema">
                    <option value="">همه طرح‌ها</option>
                    @foreach($this->kanbanSchemeOptions as $scheme)
                        <option value="{{ $scheme }}">{{ $scheme }}</option>
                    @endforeach
                </x-ui.forms.select>
            </div>
        @endif

        @if(count($this->kanbanUnitOptions))
            <div class="w-full sm:w-36 shrink-0">
                <x-ui.forms.select label="واحد" name="kanbanUnitFilter" wire:model.live="kanbanUnitFilter" icon="account_tree">
                    <option value="">همه واحدها</option>
                    @foreach($this->kanbanUnitOptions as $unit)
                        <option value="{{ $unit }}">{{ $unit }}</option>
                    @endforeach
                </x-ui.forms.select>
            </div>
        @endif

        @if(count($this->kanbanSectionOptions))
            <div class="w-full sm:w-36 shrink-0">
                <x-ui.forms.select label="بخش" name="kanbanSectionFilter" wire:model.live="kanbanSectionFilter" icon="segment">
                    <option value="">همه بخش‌ها</option>
                    @foreach($this->kanbanSectionOptions as $section)
                        <option value="{{ $section }}">{{ $section }}</option>
                    @endforeach
                </x-ui.forms.select>
            </div>
        @endif

        <div class="w-full sm:w-36 shrink-0">
            <x-ui.forms.select label="جوابگو" name="kanbanResponsibleFilter" wire:model.live="kanbanResponsibleFilter" icon="support_agent">
                <option value="">همه جوابگوها</option>
                @foreach($staffMembers as $staff)
                    <option value="{{ $staff['id'] }}">{{ $staff['full_name'] }}</option>
                @endforeach
            </x-ui.forms.select>
        </div>

        <div class="w-full sm:w-36 shrink-0">
            <x-ui.forms.select label="دپارتمان" name="kanbanDepartmentFilter" wire:model.live="kanbanDepartmentFilter" icon="corporate_fare">
                <option value="">همه دپارتمان‌ها</option>
                @foreach($departmentOptions as $code => $label)
                    <option value="{{ $code }}">{{ $label }}</option>
                @endforeach
            </x-ui.forms.select>
        </div>

        @if(count($this->kanbanAssigneeOptions))
            <div class="w-full sm:w-36 shrink-0">
                <x-ui.forms.select label="مسئول انجام" name="kanbanAssigneeFilter" wire:model.live="kanbanAssigneeFilter" icon="assignment_ind">
                    <option value="">همه مسئول‌ها</option>
                    @foreach($this->kanbanAssigneeOptions as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </x-ui.forms.select>
            </div>
        @endif
    </div>

    <div wire:loading.remove wire:target="loadKanbanBoard" class="flex flex-col md:flex-row flex-1 min-h-0 items-start overflow-x-auto gap-3 md:gap-4 pb-2 snap-x snap-mandatory md:snap-none">
        @foreach(['todo', 'in-progress', 'pending', 'done'] as $column)
            <div class="snap-center shrink-0 w-full sm:w-[calc(100%-2rem)] md:w-1/4 md:flex-1 min-w-[240px] sm:min-w-[280px] md:min-w-0 max-w-full">
                @php
                    $presenter = $taskBoardPresenter;
                    $columnConfig = $presenter->columnConfig()[$column];
                    $columnTasks = $this->kanbanBoard['tasks'][$column] ?? [];
                    $columnTaskCount = $this->kanbanBoard['totalCount'][$column] ?? 0;
                @endphp

                <div data-column="{{ $column }}"
                     @dragover.prevent="handleDragOver($event)"
                     @drop="handleDrop($event, col($el))"
                     :class="{ 'bg-[var(--md-sys-color-primary-container)]/10 !border-dashed !border-[var(--md-sys-color-primary)]': dragTask }"
                     wire:loading.class="opacity-60 pointer-events-none"
                     wire:target="reorderTask,updateTaskStatus"
                     class="flex flex-col flex-1 min-w-[240px] min-h-[240px] max-h-[calc(100vh-360px)] p-2.5 transition-all duration-300 border shadow-sm sm:min-w-[280px] md:min-w-0 md:p-3 rounded-2xl bg-[var(--md-sys-color-surface-container-low)] border-[var(--md-sys-color-outline-variant)]/40">

                    <div class="flex items-center justify-between w-full gap-2 pb-2.5 border-b border-[var(--md-sys-color-outline-variant)]/40">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center justify-center text-base leading-none select-none" role="img" aria-hidden="true">
                                {{ $columnConfig['icon'] }}
                            </span>

                            <h3 class="font-bold tracking-tight leading-none text-[12px] sm:text-[13px] text-[var(--md-sys-color-on-surface)]">
                                {{ $columnConfig['title'] }}
                            </h3>

                            <span class="px-1.5 py-0.5 rounded-sm tabular-nums text-[10px] font-bold leading-none"
                                  style="background: color-mix(in srgb, var(--md-sys-color-{{ $columnConfig['color'] }}) 12%, transparent); border: 1px solid color-mix(in srgb, var(--md-sys-color-{{ $columnConfig['color'] }}) 25%, transparent); color: var(--md-sys-color-{{ $columnConfig['color'] }});">
                                {{ $columnTaskCount }}
                            </span>
                        </div>

                        @if($column === 'todo')
                            <button @click="Livewire.dispatch('project-open-task-create')"
                                    class="flex items-center justify-center w-6 h-6 transition-all duration-200 rounded-lg active:scale-95 ripple-effect text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)]"
                                    title="افزودن وظیفه">
                                <span class="text-[15px] material-symbols-rounded">add</span>
                            </button>
                        @elseif($column === 'done')
                            <x-ui.hover-popover alignment="top-full left-0 mt-2 origin-top-left" width="w-56" surface="tertiary">
                                <x-slot:trigger>
                                    <span class="relative inline-flex items-center justify-center w-6 h-6 rounded-lg text-[var(--md-sys-color-on-surface-variant)]">
                                        <span class="material-symbols-rounded text-[15px]">archive</span>
                                        <span class="absolute -top-1 -left-1 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-[var(--md-sys-color-tertiary)] px-1 text-[9px] font-bold leading-none text-[var(--md-sys-color-on-tertiary)] shadow ring-2 ring-[var(--md-sys-color-surface)]">{{ $this->archivedCount > 99 ? '99+' : $this->archivedCount }}</span>
                                    </span>
                                </x-slot:trigger>
                                <x-slot:body>
                                    <div class="p-3 text-[11.5px] leading-6">
                                        <p class="font-bold mb-1">{{ $this->archivedCount }} وظیفهٔ آرشیو‌شده</p>
                                        <p>وظایف انجام‌شده و تأییدشدهٔ این پروژه که مدتی از تأییدشان گذشته، خودکار شبانه آرشیو و از این برد پنهان می‌شوند — چیزی حذف نمی‌شود. فقط ایجادکنندهٔ هر وظیفه می‌تواند آن را از آرشیو خارج کند.</p>
                                        @if($this->archivedCount > 0)
                                            <button type="button" @click="$dispatch('open-modal', { name: 'kanban-archive-{{ $activeProjectId }}' })"
                                                    class="mt-2 inline-flex items-center gap-1 font-bold text-[var(--md-sys-color-primary)] hover:underline">
                                                <span class="material-symbols-rounded text-[13px]">unarchive</span>
                                                مشاهده و بازگردانی
                                            </button>
                                        @endif
                                    </div>
                                </x-slot:body>
                            </x-ui.hover-popover>

                            <x-ui.modals.dialog name="kanban-archive-{{ $activeProjectId }}" title="وظایف آرشیوشده">
                                <div class="max-h-80 overflow-y-auto custom-scrollbar divide-y divide-[var(--md-sys-color-outline-variant)]/30">
                                    @forelse($this->archivedTasks as $item)
                                        <div class="flex items-center justify-between gap-2 py-2.5 text-xs">
                                            <div class="min-w-0">
                                                <p class="truncate font-medium text-[var(--md-sys-color-on-surface)]" dir="auto">{{ $item['title'] }}</p>
                                                <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">{{ $item['archived_at'] }}</p>
                                            </div>
                                            @if($item['can_restore'])
                                                <button type="button" wire:click="unarchiveTask({{ $item['id'] }})"
                                                        class="shrink-0 inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] font-bold text-[var(--md-sys-color-on-tertiary-container)] bg-[var(--md-sys-color-tertiary-container)] hover:brightness-110 transition">
                                                    <span class="material-symbols-rounded text-[13px]">unarchive</span>
                                                    بازگردانی
                                                </button>
                                            @endif
                                        </div>
                                    @empty
                                        <p class="py-4 text-center text-xs text-[var(--md-sys-color-on-surface-variant)]">وظیفه‌ای آرشیو نشده است.</p>
                                    @endforelse
                                </div>
                            </x-ui.modals.dialog>
                        @endif
                    </div>

                    <div class="flex flex-col flex-1 gap-2 p-1.5 mt-2.5 min-h-0 overflow-x-hidden overflow-y-auto scroll-smooth rounded-[14px] taskboard-column-list container-scrollbar custom-scrollbar">
                        @forelse($columnTasks as $task)
                            @include('livewire.dashboard.taskboard.card', ['task' => $task, 'column' => $column, 'isPersonalBoard' => false])
                        @empty
                            <x-ui.empty icon="inbox" title="هیچ موردی وجود ندارد" variant="list" />
                        @endforelse

                        <div x-show="dragTask"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             class="flex items-center justify-center h-20 gap-1.5 border border-dashed rounded-2xl"
                             style="border-color: var(--md-sys-color-primary); background: color-mix(in srgb, var(--md-sys-color-primary) 8%, transparent);">
                            <span class="text-[16px] animate-bounce material-symbols-rounded text-[var(--md-sys-color-primary)]">south</span>
                            <span class="text-[10px] font-bold tracking-wide text-[var(--md-sys-color-primary)]">اینجا رها کنید</span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div wire:loading wire:target="loadKanbanBoard" class="w-full overflow-x-auto flex items-start gap-4 pb-4">
        @for($i = 0; $i < 4; $i++)
            <x-ui.loaders.skeleton.column-stack :cards="3"/>
        @endfor
    </div>
</div>
