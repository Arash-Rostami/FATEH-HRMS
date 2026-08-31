@php
    $config = $presenter->columnConfig()[$column];
    $columnTasks = $tasks[$column] ?? [];
    $taskCount = $totalCount[$column] ?? 0;
    $pager = $presenter->columnPagerMeta($taskCount, $perPage, $page[$column] ?? 1);

    $isDoneColumn = $column === 'done';
    $isTodoColumn = $column === 'todo';
@endphp

<div
    data-column="{{ $column }}"
    @dragover.prevent="handleDragOver($event)"
    @drop="handleDrop($event, col($el))"
    :class="{
        'bg-[var(--md-sys-color-primary-container)]/10 !border-dashed !border-[var(--md-sys-color-primary)] scale-[1.01]': dragTask,
        'max-widget-column': maximizedColumn === $el.dataset.column
    }"
    class="flex-1 min-w-[280px] sm:min-w-[320px] md:min-w-0 max-h-[calc(100vh-220px)] min-h-[320px] flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] p-3 md:p-4 shadow-sm border border-[var(--md-sys-color-outline-variant)]/40 transition-all duration-300"
    wire:loading.class="opacity-60 pointer-events-none"
    wire:target="reorderTask,updateTaskStatus"
>
    <div class="flex items-center justify-between gap-2 pb-3 border-b border-[var(--md-sys-color-outline-variant)]/40 w-full">
        <div class="flex items-center gap-2.5">
            <div class="flex items-center justify-center w-8 h-8 shrink-0 animate-rotate-in animate-delay-3000">
                <span class="inline-flex items-center justify-center text-lg leading-none select-none" role="img" aria-hidden="true">
                    {{ $config['icon'] }}
                </span>
            </div>

            <div class="flex flex-col gap-0.5">
                <div class="flex items-center gap-1.5">
                    <h3 class="font-bold text-[var(--md-sys-color-on-surface)] text-[13px] sm:text-sm tracking-tight leading-none">
                        {{ ($showArchived && $isDoneColumn) ? 'آرشیو' : $config['title'] }}
                    </h3>
                    <span class="tabular-nums px-1.5 py-0.5 rounded-sm text-[10px] font-bold leading-none"
                          style="background: color-mix(in srgb, var(--md-sys-color-{{ $config['color'] }}) 12%, transparent); border: 1px solid color-mix(in srgb, var(--md-sys-color-{{ $config['color'] }}) 25%, transparent); color: var(--md-sys-color-{{ $config['color'] }});">
                        {{ $taskCount }}
                    </span>
                </div>
                <div class="flex items-center">
                    @if($isDoneColumn && $search === '')
                        <span class="text-[9px] font-medium leading-none">
                            @if($showArchived)
                                <span class="text-[var(--md-sys-color-tertiary)]">آرشیو شده‌ها</span>
                            @elseif(!$showAllDone)
                                <span class="text-[var(--md-sys-color-primary)]">۴۵ روز اخیر</span>
                            @endif
                        </span>
                    @else
                        <span class="text-[9px] font-medium text-[var(--md-sys-color-on-surface-variant)]/70 leading-none">
                            وظایف ستون
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex items-center gap-0.5">
            @if($isDoneColumn)
                <x-ui.hover-popover alignment="top-full left-0 mt-2 origin-top-left" width="w-56" surface="tertiary">
                    <x-slot:trigger>
                        <span class="relative inline-flex relative top-1">
                            <button
                                wire:click="toggleShowArchived"
                                @class([
                                    $presenter->columnButtonBase(),
                                    'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]' => $showArchived,
                                    $presenter->columnButtonIcon() => !$showArchived,
                                ])
                                title="{{ $showArchived ? 'نمایش انجام‌شده‌ها' : 'نمایش آرشیو' }}"
                            >
                                <span class="material-symbols-rounded text-[16px]">{{ $showArchived ? 'unarchive' : 'archive' }}</span>
                            </button>
                            <span class="absolute -top-1 -left-1 flex h-4 w-4 min-w-[16px] items-center justify-center rounded-sm bg-[var(--md-sys-color-tertiary)] px-1 text-[9px] font-bold leading-none text-[var(--md-sys-color-on-tertiary)] shadow ring-2 ring-[var(--md-sys-color-surface)]">{{ $archivedCount > 99 ? '99+' : $archivedCount }}</span>
                        </span>
                    </x-slot:trigger>
                    <x-slot:body>
                        <div class="p-3 text-[11.5px] leading-6">
                            <p class="font-bold mb-1">{{ $archivedCount }} وظیفهٔ آرشیو‌شده</p>
                            <p>وظایف انجام‌شده و تأییدشده‌ای که مدتی از تأییدشان گذشته، خودکار شبانه آرشیو می‌شوند — چیزی حذف نمی‌شود. با کلیک روی این دکمه وارد آرشیو شوید و با «خروج از آرشیو» روی هر مورد، آن را برگردانید.</p>
                        </div>
                    </x-slot:body>
                </x-ui.hover-popover>

                @if(!$showArchived)
                    <button
                        wire:click="toggleShowAllDone"
                        @class([
                            $presenter->columnButtonBase(),
                            'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]' => $showAllDone,
                            $presenter->columnButtonIcon() => !$showAllDone,
                        ])
                        title="{{ ($showAllDone || $search !== '') ? 'نمایش ۴۵ روز اخیر' : 'نمایش تمام موارد قدیمی‌تر' }}"
                    >
                        <span class="material-symbols-rounded text-[16px]">{{ ($showAllDone || $search !== '') ? 'history_toggle_off' : 'history' }}</span>
                    </button>
                @endif
            @endif

            <button
                class="{{ $presenter->columnButtonBase() }} {{ $presenter->columnButtonIcon() }}"
                @click="toggleMaximize(col($el))"
                :title="maximizedColumn === col($el) ? 'کوچک کردن' : 'بزرگ کردن'"
                :class="{ '!bg-[var(--md-sys-color-primary-container)] !text-[var(--md-sys-color-on-primary-container)]': maximizedColumn === col($el) }"
            >
                <span
                    class="material-symbols-rounded text-[16px] transition-transform duration-300"
                    x-text="maximizedColumn === col($el) ? 'close_fullscreen' : 'open_in_full'"
                ></span>
            </button>

            <button
                class="{{ $presenter->columnButtonBase() }} {{ $presenter->columnButtonIcon() }}"
                @click="toggleSpotlight(col($el))"
                :title="spotlightColumn === col($el) ? 'خروج از حالت تمرکز' : 'تمرکز روی این ستون'"
                :class="{ '!bg-[var(--md-sys-color-primary-container)] !text-[var(--md-sys-color-on-primary-container)]': spotlightColumn === col($el) }"
            >
                <span class="material-symbols-rounded text-[16px]">center_focus_strong</span>
            </button>

            <button
                class="{{ $presenter->columnButtonBase() }} {{ $presenter->columnButtonIcon() }}"
                @click="toggleCollapsed(col($el))"
                title="جمع/باز کردن ستون"
                :aria-expanded="!isCollapsed(col($el))"
            >
                <span
                    class="material-symbols-rounded text-[16px] transition-transform duration-300"
                    :class="isCollapsed(col($el)) ? 'rotate-180' : ''"
                >expand_more</span>
            </button>

            @if($isTodoColumn)
                <button
                    wire:click="openCreateModal"
                    class="{{ $presenter->columnButtonBase() }} text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] ml-0.5"
                    title="افزودن سریع"
                    style="background: color-mix(in srgb, var(--md-sys-color-primary) 10%, transparent);"
                >
                    <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">add</span>
                </button>
            @endif
        </div>
    </div>

    <div x-show="!isCollapsed(col($el))" class="flex-1 flex flex-col min-h-0 w-full mt-3">
        <div class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden rounded-[14px] bg-[var(--md-sys-color-surface-variant)]/20 p-2 flex flex-col gap-3 taskboard-column-list scroll-smooth container-scrollbar custom-scrollbar border border-[var(--md-sys-color-outline-variant)]/20">
            @forelse($columnTasks as $task)
                @include('livewire.dashboard.taskboard.card', ['task' => $task, 'column' => $column, 'isPersonalBoard' => true])
            @empty
                @php
                    $empty = $presenter->emptyStateFlags($column, $showAllDone, $showArchived, $search, $doneTotalCount);
                @endphp

                @if($empty['archiveEmpty'])
                    <x-ui.empty icon="archive" title="مورد آرشیو شده‌ای نیست" description="وظایف انجام‌شده‌ی آرشیو‌شده اینجا نمایش داده می‌شوند" variant="list" />
                @elseif($empty['olderExist'])
                    <x-ui.empty icon="history" title="هیچ موردی در ۴۵ روز اخیر نیست" description="تسک‌های انجام‌شده قدیمی‌تر پنهان شده‌اند" variant="list">
                        <x-slot:slot>
                            <button
                                wire:click="toggleShowAllDone"
                                class="ripple-effect px-3 py-1.5 rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] text-xs font-bold transition-all duration-200 active:scale-95 inline-flex items-center gap-1"
                            >
                                <span class="material-symbols-rounded text-[14px]">history</span>
                                نمایش موارد قدیمی‌تر
                            </button>
                        </x-slot:slot>
                    </x-ui.empty>
                @else
                    <x-ui.empty icon="inbox" title="هیچ موردی وجود ندارد" description="با افزودن وظیفه، کارت‌ها اینجا نمایش داده می‌شوند" variant="list" />
                @endif
            @endforelse

            <div
                x-show="dragTask"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="h-28 rounded-2xl border border-dashed flex items-center justify-center gap-1.5"
                style="border-color: var(--md-sys-color-primary); background: color-mix(in srgb, var(--md-sys-color-primary) 8%, transparent);"
            >
                <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)] animate-bounce">south</span>
                <span class="text-[11px] font-bold text-[var(--md-sys-color-primary)] tracking-wide">اینجا رها کنید</span>
            </div>
        </div>

        @if($taskCount > $perPage)
            <div class="flex items-center justify-between gap-3 pt-3 mt-3 border-t border-[var(--md-sys-color-outline-variant)]/40 w-full px-1">
                <button
                    wire:click="prevPage('{{ $column }}')"
                    class="{{ $presenter->columnButtonBase() }} {{ $presenter->columnButtonIcon() }} disabled:opacity-30 disabled:pointer-events-none"
                    {{ $pager['isFirstPage'] ? 'disabled' : '' }}
                >
                    <span class="material-symbols-rounded text-[16px]">chevron_right</span>
                </button>

                <div
                    x-data="{ editing: false, val: {{ $page[$column] ?? 1 }} }"
                    class="text-[10px] font-bold text-[var(--md-sys-color-on-surface-variant)] tabular-nums tracking-widest px-2.5 py-1 rounded-md border border-[var(--md-sys-color-outline-variant)]/30"
                    style="background: color-mix(in srgb, var(--md-sys-color-surface-container) 60%, transparent);"
                >
                    <button
                        x-show="!editing"
                        @click="editing = true; val = {{ $page[$column] ?? 1 }}; $nextTick(() => $refs.jump.select())"
                        type="button"
                        title="رفتن به صفحه"
                        class="cursor-pointer transition-colors hover:text-[var(--md-sys-color-primary)] flex items-center gap-1"
                    >
                        <span>{{ $page[$column] ?? 1 }}</span>
                        <span class="opacity-50">/</span>
                        <span>{{ $pager['lastPage'] }}</span>
                    </button>

                    <div x-show="editing" x-cloak class="flex items-center gap-1">
                        <input
                            x-ref="jump"
                            type="number"
                            min="1"
                            max="{{ $pager['lastPage'] }}"
                            x-model.number="val"
                            @keydown.enter.prevent="editing = false; $wire.jumpToPage('{{ $column }}', val)"
                            @keydown.escape.prevent="editing = false"
                            @blur="editing = false"
                            class="w-6 bg-transparent text-center outline-none border-b border-[var(--md-sys-color-primary)] text-[var(--md-sys-color-primary)] [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none"
                        />
                        <span class="opacity-50">/ {{ $pager['lastPage'] }}</span>
                    </div>
                </div>

                <button
                    wire:click="nextPage('{{ $column }}')"
                    class="{{ $presenter->columnButtonBase() }} {{ $presenter->columnButtonIcon() }} disabled:opacity-30 disabled:pointer-events-none"
                    {{ $pager['isLastPage'] ? 'disabled' : '' }}
                >
                    <span class="material-symbols-rounded text-[16px]">chevron_left</span>
                </button>
            </div>
        @endif
    </div>
</div>
