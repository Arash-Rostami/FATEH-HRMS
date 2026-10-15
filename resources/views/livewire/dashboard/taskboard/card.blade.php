@php
    $taskId = $task['id'];
    $isArchived = (bool) ($task['is_archived'] ?? false);
    $canChangeStatus = (bool) ($task['can_change_status'] ?? false);
    $ticketId = $task['ticket_id'] ?? null;
    $canEdit = $canChangeStatus && !$isArchived;

    $card = $presenter->cardMeta($task, $isPersonalBoard, $departmentOptions);
    extract($card + [
        'unit' => null, 'section' => null, 'projectLabel' => null, 'projectHasLink' => false,
        'isPendingApproval' => false, 'canApprove' => false, 'metaChips' => [],
        'actionSourceChip' => null, 'lastTouchedBy' => null,
    ], EXTR_SKIP);

    extract($presenter->cardChrome($task, $card, $isPersonalBoard, $activeTab ?? null, $column ?? 'todo'), EXTR_SKIP);
@endphp

<div
    wire:key="task-card-{{ $taskId }}"
    data-rf="task-{{ $taskId }}"
    data-ver="{{ $task['version'] ?? '' }}"
    draggable="{{ $canEdit ? 'true' : 'false' }}"
    x-on:dragstart="{{ $canEdit ? 'handleDragStart($event, ' . $taskId . ')' : 'event.preventDefault()' }}"
    x-on:dragend="handleDragEnd($event)"
    x-on:dragover.prevent
    x-on:drop.stop="handleCardDrop($event, {{ $taskId }}, @js($column))"
    x-show="{{ $showTaskExpression }}"
    x-bind:class="{ 'order-first': {{ $isFavoriteExpression }} }"
    x-bind:style="(! @js($isUrgent || $isPending || $isArchived) && $store.tagged.tagBg(@js($taskId), @js('task'))) ? { backgroundColor: $store.tagged.tagBg(@js($taskId), @js('task')) } : {}"
    class="group relative flex flex-col p-4 md:p-5 pt-6 taskboard-card rounded-2xl animate-bubble-in [content-visibility:auto] [contain-intrinsic-size:auto_260px] hover:-translate-y-1 hover:z-10 transition-all duration-300 active:scale-[0.98] select-none
        {{ $isPending ? 'bg-[var(--md-sys-color-error-container)]/30 border-[var(--md-sys-color-error)] border-2 shadow-[0_0_15px_color-mix(in_srgb,var(--md-sys-color-error)_40%,transparent)] animate-pulse-slow' : 'bg-[var(--md-sys-color-primary-container)]/70 border border-[var(--md-sys-color-outline-variant)] shadow-sm hover:shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] hover:border-[var(--md-sys-color-primary)]/40' }}
        {{ $isArchived ? 'cursor-default opacity-70 grayscale' : ($canChangeStatus ? 'cursor-grab active:cursor-grabbing' : 'cursor-default opacity-80') }}
        {{ !$isArchived && $isPendingApproval ? 'opacity-50' : '' }} {{ $isUrgent ? 'taskboard-card--urgent' : '' }}"
    dir="rtl"
    style="{{ $isUrgent ? '--urgency:' . $urgency['score'] . ';' : '' }}"
>
    {{-- status accent --}}
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-32 md:w-40 h-[3px] rounded-b-full opacity-80 group-hover:opacity-100 transition-opacity bg-gradient-to-r {{ $colState['lightGradient'] }} animate-subtle-pulse"
         style="box-shadow: 0 4px 12px color-mix(in srgb, var(--md-sys-color-{{ $colState['color'] }}) 40%, transparent);"></div>

    @if($isPersonalBoard && $selectionMode)
        @php $selected = in_array($taskId, $selectedTasks); @endphp
        <button
            wire:key="taskboard-selection-btn"
            wire:click="toggleTaskSelection({{ $taskId }})"
            class="absolute top-3 right-3 min-w-[28px] min-h-[28px] rounded-lg border-2 flex items-center justify-center transition-all duration-150 {{ $selected ? 'bg-[var(--md-sys-color-primary)] border-[var(--md-sys-color-primary)]' : 'border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)]' }}"
        >
            @if($selected)
                <span wire:key="taskboard-selection-check" class="material-symbols-rounded text-sm text-[var(--md-sys-color-on-primary)]">check</span>
            @endif
        </button>
    @endif

    {{-- HEADER --}}
    <div class="flex items-center justify-between gap-2 pb-2.5 border-b border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] text-[10px] font-medium tracking-wider text-[var(--md-sys-color-on-surface-variant)]/70">
        <div class="flex items-center gap-1 min-w-0">
            <span class="shrink-0 tabular-nums">#{{ $taskId }}</span>
            @if($projectLabel)
                @if($projectHasLink)
                    <a wire:key="taskboard-card-project-link" href="{{ route('projects', ['open' => $task['project_id'], 'tab' => 'report']) }}" wire:navigate
                       class="inline-flex items-center gap-0.5 min-w-0 text-[var(--md-sys-color-primary)] hover:brightness-110 transition" title="پروژه: {{ $projectLabel }}">
                        <span class="material-symbols-rounded text-[12px] shrink-0">workspaces</span>
                        <span class="max-w-[120px] truncate" dir="auto">{{ $projectLabel }}</span>
                    </a>
                @else
                    <span wire:key="taskboard-card-project-plain" class="inline-flex items-center gap-0.5 min-w-0" title="برچسب پروژه (بدون پروژهٔ متصل): {{ $projectLabel }}">
                        <span class="material-symbols-rounded text-[12px] shrink-0">workspaces</span>
                        <span class="max-w-[120px] truncate" dir="auto">{{ $projectLabel }}</span>
                    </span>
                @endif
                <span wire:key="taskboard-card-project-dot" class="shrink-0">•</span>
            @endif
            <span class="truncate">{{ $task['created_formatted'] }}</span>
        </div>

        <div class="flex items-center gap-1.5 shrink-0">
            @if(($task['replies_count'] ?? 0) > 0)
                <span wire:key="taskboard-card-replies" class="inline-flex items-center gap-0.5">
                    <span class="material-symbols-rounded text-[12px]">forum</span>{{ convertToPersian($task['replies_count']) }}
                </span>
            @endif
            @if($attachmentsCount > 0)
                <x-ui.hover-popover wire:key="taskboard-card-attachments" :alignment="$pop" width="w-48" surface="tertiary">
                    <x-slot:trigger>
                        <span class="inline-flex items-center gap-0.5"><span class="material-symbols-rounded text-[12px]">attach_file</span>{{ $attachmentsCount }}</span>
                    </x-slot:trigger>
                    <x-slot:body>
                        <div class="max-h-48 overflow-y-auto custom-scrollbar divide-y divide-[var(--md-sys-color-outline-variant)]/30">
                            @foreach($attachments as $attachment)
                                @php
                                    $path = $attachment['path'] ?? '';
                                    $name = $attachment['name'] ?? basename($path);
                                    $isImage = str_starts_with($attachment['mime'] ?? '', 'image/');
                                    $fileIcon = $dmsPresenter->extensionIcon(pathinfo($path, PATHINFO_EXTENSION));
                                @endphp
                                <a wire:key="taskboard-attachment-{{ $isImage ? 'image' : 'file' }}-{{ crc32($path) }}" href="{{ $url($path) }}"
                                   @if($isImage) data-fancybox="task-{{ $taskId }}-attachments" data-caption="{{ $attachment['name'] ?? '' }}" @else target="_blank" @endif
                                   class="flex items-center gap-2 px-3 py-2 text-xs text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-container-highest)] transition-colors">
                                    @if($isImage)
                                        <img src="{{ $url($path) }}" class="w-5 h-5 rounded object-cover shrink-0" alt="">
                                    @else
                                        <span class="material-symbols-rounded text-[14px] {{ $fileIcon['text'] }}">{{ $fileIcon['icon'] }}</span>
                                    @endif
                                    <span class="truncate" dir="auto">{{ $name }}</span>
                                </a>
                            @endforeach
                        </div>
                    </x-slot:body>
                </x-ui.hover-popover>
            @endif
        </div>
    </div>

    {{-- MAIN --}}
    <div class="flex flex-col gap-2.5 py-3">
        <x-ui.hover-popover wire:key="taskboard-card-text" class="!block w-full" :alignment="$pop" width="w-64" surface="default">
            <x-slot:trigger>
                <div class="flex flex-col gap-1">
                    <h3 class="text-sm font-bold text-[var(--md-sys-color-on-surface)] leading-snug line-clamp-2" dir="auto">{{ superClean($task['title']) }}</h3>
                    @if(!empty($task['description']))
                        <p data-col="description" class="taskboard-card-descriptiontext-[11px] text-[var(--md-sys-color-on-surface-variant)] line-clamp-2 leading-relaxed" dir="auto">{{ superClean($task['description']) }}</p>
                    @endif
                </div>
            </x-slot:trigger>
            <x-slot:body>
                <div class="{{ $popBody }} space-y-1" dir="auto">
                    <div class="font-bold">{{ superClean($task['title']) }}</div>
                    @if(!empty($task['description']))
                        <div class="text-[var(--md-sys-color-on-surface-variant)]">{{ superClean($task['description']) }}</div>
                    @endif
                </div>
            </x-slot:body>
        </x-ui.hover-popover>

        @if($showChips)
            <div wire:key="taskboard-card-chips" class="flex flex-wrap items-center gap-1.5">
                {{-- signals --}}
                @if($chip = $presenter->priorityChip($task['priority'] ?? null))
                    <button wire:key="taskboard-card-priority" data-col="priority" type="button"
                            title="{{ $canCyclePriority ? 'تغییر اولویت' : $chip['label'] }}"
                            x-on:click="{{ $canCyclePriority ? '$wire.cyclePriority(' . $taskId . ')' : 'event.preventDefault()' }}"
                            class="{{ $cb }} {{ $bd }} {{ $chip['class'] }} {{ $canCyclePriority ? 'hover:brightness-110 active:scale-95 transition cursor-pointer' : 'cursor-default' }}">
                        <span class="material-symbols-rounded text-[12px] {{ $chip['isUrgent'] ? 'font-fill' : '' }}">flag</span>
                        <span class="{{ $chip['isUrgent'] ? 'animate-pulse-ring' : '' }}">{{ $chip['label'] }}</span>
                    </button>
                @endif

                @if($stateChip)
                    <span wire:key="taskboard-card-state" title="تعیین تکلیف: {{ $stateChip['label'] }}" class="{{ $cb }} {{ $bd }} {{ $stateChip['class'] }}">
                        <span class="material-symbols-rounded text-[12px]">{{ $stateChip['icon'] }}</span>
                        <span>{{ $stateChip['label'] }}</span>
                    </span>
                @endif

                @if($hasDeadline)
                    <x-ui.hover-popover wire:key="taskboard-card-deadline" data-col="deadline" :alignment="$pop" width="w-52" surface="default">
                        <x-slot:trigger>
                            <div class="{{ $cb }} {{ isPast($task['deadline']) && $task['status'] !== 'done' ? $tone['over'] : $tone['neu'] }}">
                                <span class="material-symbols-rounded text-[12px]">schedule</span>
                                <span class="tracking-wide">{{ in_array($kind, ['overdue', 'due'], true) ? $urgency['label'] : $task['deadline_formatted'] }}</span>
                            </div>
                        </x-slot:trigger>
                        <x-slot:body>
                            <div class="{{ $popBody }} whitespace-pre-wrap">{{ $deadlineTitle }}</div>
                        </x-slot:body>
                    </x-ui.hover-popover>
                @endif

                @if($urgencyChip)
                    <div wire:key="taskboard-urgency-{{ $kind === 'project-deadline-exceeded' ? 'project' : $kind }}" data-col="deadline" class="{{ $cb }} {{ $tone[$urgencyChip[1]] }}">
                        <span class="material-symbols-rounded text-[12px]">{{ $urgencyChip[0] }}</span>
                        <span>{{ $urgency['label'] }}</span>
                    </div>
                @endif

                @if($isPendingApproval)
                    <x-ui.hover-popover wire:key="taskboard-card-pending" :alignment="$pop" width="w-44" surface="default">
                        <x-slot:trigger>
                            <div class="{{ $cb }} {{ $tone['sec'] }}">
                                <span class="material-symbols-rounded text-[12px]">verified_user</span>
                                <span class="animate-subtle-pulse">منتظر تأیید</span>
                            </div>
                        </x-slot:trigger>
                        <x-slot:body>
                            <div class="{{ $popBody }}">وظیفه انجام‌شده است و منتظر تأیید مدیر پروژه می‌ماند.
                                @if($lastTouchedBy)
                                    <span class="block mt-1 text-[var(--md-sys-color-on-surface-variant)]">ویرایش توسط {{ $lastTouchedBy['user_name'] }} · {{ $lastTouchedBy['ago'] }}</span>
                                @endif
                            </div>
                        </x-slot:body>
                    </x-ui.hover-popover>
                @endif

                {{-- people --}}
                @if($assignee)
                    <div wire:key="taskboard-assignee-{{ $assignee[0] }}" data-col="people" title="{{ $assignee[3] }}" class="{{ $cb }} {{ $tone[$assignee[2]] }}">
                        <span class="material-symbols-rounded text-[12px]">{{ $assignee[1] }}</span>
                        <span class="max-w-[140px] truncate" dir="auto">{{ $assignee[3] }}</span>
                    </div>
                @endif

                @if($responsibleUser)
                    <div wire:key="taskboard-card-responsible" data-col="people" title="جوابگو: {{ $responsibleUser['name'] }}" class="{{ $cb }} {{ $tone['sec'] }}">
                        <span class="material-symbols-rounded text-[12px]">support_agent</span>
                        <span class="max-w-[140px] truncate" dir="auto">{{ $responsibleUser['name'] }}</span>
                    </div>
                @endif

                {{-- context --}}
                @if($departmentLabel)
                    <x-ui.hover-popover wire:key="taskboard-card-department" data-col="context" :alignment="$pop" width="w-56" surface="default">
                        <x-slot:trigger>
                            <div class="{{ $cb }} {{ $tone['ter'] }}">
                                <span class="material-symbols-rounded text-[12px]">corporate_fare</span>
                                <span class="max-w-[140px] truncate">{{ $departmentLabel }}</span>
                            </div>
                        </x-slot:trigger>
                        <x-slot:body>
                            <div class="{{ $popBody }} space-y-1.5" dir="auto">
                                <div class="flex items-center gap-1.5 font-bold">
                                    <span class="material-symbols-rounded text-[14px]">corporate_fare</span>
                                    <span>{{ $departmentLabel }}</span>
                                </div>
                                @if($unit)
                                    <div class="flex items-center gap-1.5 text-[var(--md-sys-color-on-surface-variant)]">
                                        <span class="material-symbols-rounded text-[13px]">account_tree</span>
                                        <span>واحد: {{ $unit }}</span>
                                    </div>
                                @endif
                                @if($section)
                                    <div class="flex items-center gap-1.5 text-[var(--md-sys-color-on-surface-variant)]">
                                        <span class="material-symbols-rounded text-[13px]">segment</span>
                                        <span>بخش: {{ $section }}</span>
                                    </div>
                                @endif
                            </div>
                        </x-slot:body>
                    </x-ui.hover-popover>
                @endif

                @foreach(($task['labels'] ?? []) as $label)
                    @php $t = ['amethyst', 'sapphire', 'sage', 'gold'][crc32($label) % 4]; @endphp
                    <button wire:key="taskboard-card-label-{{ crc32($label) }}" data-col="labels" type="button" title="{{ $label }}"
                            wire:click="{{ $labelFilterMethod }}({{ \Illuminate\Support\Js::from($label) }})"
                            class="{{ $cb }} {{ $bd }} hover:brightness-110 active:scale-95 transition"
                            style="background: var(--tool-{{ $t }}-bg); color: var(--tool-{{ $t }}-text);">
                        <span class="material-symbols-rounded text-[12px]">sell</span>
                        <span class="max-w-[140px] truncate" dir="auto">{{ $label }}</span>
                    </button>
                @endforeach

                @foreach($metaChips as $metaChip)
                    <span wire:key="taskboard-meta-chip-{{ crc32($metaChip['label'] . $metaChip['value']) }}" data-col="context" title="{{ $metaChip['label'] }}: {{ $metaChip['value'] }}" class="{{ $cb }} {{ $tone['neu'] }}">
                        <span class="material-symbols-rounded text-[12px]">database</span>
                        <span class="max-w-[100px] truncate" dir="auto">{{ $metaChip['label'] }}</span>
                        <span class="max-w-[120px] truncate" dir="auto">{{ $metaChip['value'] }}</span>
                    </span>
                @endforeach

                @if($actionSourceChip)
                    <{{ ($actionSourceChip['url'] ?? null) ? 'a' : 'span' }} wire:key="taskboard-card-action-source{{ ($actionSourceChip['url'] ?? null) ? '-cycle' : '' }}" data-col="context" class="{{ $cb }} {{ $tone['neu'] }}"
                          @if($actionSourceChip['url'] ?? null) href="{{ $actionSourceChip['url'] }}" wire:navigate @endif
                          title="{{ collect([
                              ($actionSourceChip['locked'] ?? false) ? 'مرحلهٔ جاری چرخه؛ حذف و انتقال پروژه قفل است' : null,
                              $actionSourceChip['source'] !== '' ? 'منشأ اقدام: ' . $actionSourceChip['source'] : null,
                              $actionSourceChip['domain'] !== '' ? 'حوزهٔ منشأ: ' . $actionSourceChip['domain'] : null,
                          ])->filter()->implode(' — ') }}">
                        <span class="material-symbols-rounded text-[12px]">{{ ($actionSourceChip['locked'] ?? false) ? 'lock' : 'travel_explore' }}</span>
                        <span class="max-w-[120px] truncate" dir="auto">{{ $actionSourceChip['label'] }}</span>
                    </{{ ($actionSourceChip['url'] ?? null) ? 'a' : 'span' }}>
                @endif

                @if($ticketId)
                    <a href="{{ route('ths', ['open' => $ticketId]) }}" data-col="context" wire:navigate title="مشاهده تیکت مرتبط" class="{{ $cb }} {{ $tone['pri'] }} hover:brightness-110 transition">
                        <span class="material-symbols-rounded text-[12px]">support_agent</span>
                        <span>از تیکت</span>
                    </a>
                @endif

                @if($checklistTotal > 0)
                    @php $isComplete = $task['progress_percent'] === 100; @endphp
                    <x-ui.hover-popover wire:key="taskboard-card-checklist" data-col="checklist" :alignment="$pop" width="w-56" surface="tertiary">
                        <x-slot:trigger>
                            <div class="inline-flex items-center px-1 py-0.5 rounded-lg border {{ $isComplete ? "$bd bg-[var(--md-sys-color-tertiary-container)]" : $tone['neu'] }}">
                                <x-ui.decor.progress-ring
                                    :percent="$task['progress_percent']"
                                    :size="26" :stroke="3" :label="$checklistDone.'/'.$checklistTotal"
                                    :color="$isComplete ? 'var(--md-sys-color-on-tertiary-container)' : 'var(--md-sys-color-on-surface-variant)'"/>
                            </div>
                        </x-slot:trigger>
                        <x-slot:body>
                            <div class="max-h-48 overflow-y-auto custom-scrollbar divide-y divide-[var(--md-sys-color-outline-variant)]/30">
                                @foreach($checklist as $item)
                                    @php $done = $item['done'] ?? false; @endphp
                                    <div class="flex items-center gap-2 px-3 py-2 text-xs {{ $done ? 'text-[var(--md-sys-color-on-surface-variant)] line-through' : 'text-[var(--md-sys-color-on-surface)]' }}">
                                        <span class="material-symbols-rounded text-[14px] shrink-0 {{ $done ? 'text-[var(--md-sys-color-tertiary)]' : 'text-[var(--md-sys-color-outline)]' }}">{{ $done ? 'check_box' : 'check_box_outline_blank' }}</span>
                                        <span class="truncate" dir="auto">{{ $item['text'] ?? '' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </x-slot:body>
                    </x-ui.hover-popover>
                @endif
            </div>
        @endif
    </div>

    {{-- FOOTER --}}
    <div class="flex items-center justify-between gap-1 pt-2.5 border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)]">
        @if(!empty($collaboratorUsers))
            <x-ui.decor.avatar-stack wire:key="taskboard-card-collaborators" data-col="collaborators" :users="$collaboratorUsers"
                                     title="همکاران: {{ implode('، ', array_column($collaboratorUsers, 'name')) }}"/>
        @else
            <span wire:key="taskboard-card-collaborators-empty"></span>
        @endif

        <div class="task-action-rail flex items-center gap-1">
            @foreach($rail as $a)
                @switch($a['k'] ?? null)
                    @case('assign')
                        @if($a['show'])
                            @php $picker = $this->userPickerResults; @endphp
                            <div wire:key="taskboard-assign-dropdown" class="contents">
                                <x-ui.modals.popover alignment="bottom-full right-0 origin-bottom-right">
                                    <x-slot:trigger>
                                        <button type="button" wire:click="resetUserPicker" class="{{ $hov }} {{ $btn }} {{ $btnTone['pri'] }}" title="محول کردن" aria-label="محول کردن">
                                            <span class="material-symbols-rounded text-[16px]">person_add</span>
                                        </button>
                                    </x-slot:trigger>
                                    <x-slot:content>
                                        <div class="p-2 border-b border-[var(--md-sys-color-outline-variant)]/40 text-xs font-bold text-[var(--md-sys-color-on-surface-variant)]">محول کردن به:</div>
                                        <div class="relative flex items-center px-1.5 pt-1.5 pb-1 border-b border-[var(--md-sys-color-outline-variant)]/40" @click.stop>
                                            <span class="material-symbols-rounded absolute top-1/2 -translate-y-1/2 right-3 text-[13px] text-[var(--md-sys-color-outline)] pointer-events-none">search</span>
                                            <input type="text" wire:model.live.400ms="userSearch" placeholder="جستجوی نام..."
                                                   class="w-full h-7 pr-7 pl-2 rounded-md bg-[var(--md-sys-color-surface-variant)] text-[10px] text-[var(--md-sys-color-on-surface)] placeholder-[var(--md-sys-color-outline)] border border-[var(--md-sys-color-outline-variant)]/50 outline-none focus:border-[var(--md-sys-color-primary)] transition-colors"/>
                                        </div>
                                        <ul class="max-h-40 overflow-y-auto custom-scrollbar">
                                            <li>
                                                <button wire:click="assignTask({{ $taskId }}, null)"
                                                        class="w-full text-right px-3 py-2 text-xs hover:bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] transition-colors">بدون مسئول (خودم)</button>
                                            </li>
                                            @forelse($picker['candidates'] as $staff)
                                                <li wire:key="up-{{ $staff['id'] }}">
                                                    <button wire:click="assignTask({{ $taskId }}, {{ $staff['id'] }})"
                                                            class="w-full text-right px-3 py-2 text-xs hover:bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] transition-colors {{ $task['assigned_to'] === $staff['id'] ? 'bg-[var(--md-sys-color-primary-container)]/50 text-[var(--md-sys-color-primary)] font-bold' : '' }}">{{ $staff['full_name'] }}</button>
                                                </li>
                                            @empty
                                                <li wire:key="up-empty">
                                                    <p class="px-3 py-3 text-center text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)]">کاربری با این جست‌وجو یافت نشد</p>
                                                </li>
                                            @endforelse
                                        </ul>
                                        @if($picker['hasMore'])
                                            <x-ui.buttons.load-more wire:key="up-load-more" action="loadMoreUsers" text="بیشتر" icon="expand_more" @click.stop
                                                class="w-full py-1 text-[10px] font-semibold text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-primary)]"/>
                                        @endif
                                    </x-slot:content>
                                </x-ui.modals.popover>
                            </div>
                        @endif
                        @break

                    @case('palette')
                        @if($a['show'])
                            <div wire:key="taskboard-tag-palette" x-data="{ tagOpen: false }" class="relative" x-on:click.away="tagOpen = false">
                                <button type="button" x-on:click="tagOpen = !tagOpen"
                                        :class="$store.tagged.isTagged(@js($taskId), @js('task')) ? '!opacity-100' : '{{ $hov }}'"
                                        class="{{ $btn }} {{ $btnTone['neu'] }}" title="رنگ‌آمیزی کارت" aria-label="رنگ‌آمیزی کارت">
                                    <span class="material-symbols-rounded text-[16px]"
                                          :style="$store.tagged.isTagged(@js($taskId), @js('task')) ? { color: $store.tagged.solid($store.tagged.getTag(@js($taskId), @js('task'))) } : null">palette</span>
                                </button>
                                <div x-show="tagOpen" x-cloak x-transition style="display: none;"
                                     class="absolute bottom-full mb-2 right-0 z-50 flex items-center gap-1 p-1 rounded-lg bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] shadow-lg">
                                    <template x-for="(col, i) in $store.tagged.palette" :key="i">
                                        <button type="button"
                                                x-on:click.stop="$store.tagged.setTag(@js($taskId), i, @js('task')); tagOpen = false"
                                                :aria-label="'رنگ ' + (i + 1)"
                                                class="w-5 h-5 rounded-full border-2 transition-transform hover:scale-110"
                                                :class="$store.tagged.getTag(@js($taskId), @js('task')) === i ? 'border-[var(--md-sys-color-on-surface)]' : 'border-transparent'"
                                                :style="{ 'background-color': col }"></button>
                                    </template>
                                    <button type="button" x-on:click.stop="$store.tagged.clearTag(@js($taskId), @js('task')); tagOpen = false"
                                            aria-label="برداشتن رنگ" title="برداشتن رنگ"
                                            class="w-5 h-5 rounded-full flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-error)]">
                                        <span class="material-symbols-rounded text-[14px]">close</span>
                                    </button>
                                </div>
                            </div>
                        @endif
                        @break

                    @case('fav')
                        @if($a['show'])
                            <button type="button" wire:key="taskboard-card-fav-btn" x-on:click="toggleFavorite({{ $taskId }})"
                                    :class="isFavorite({{ $taskId }}) ? '!opacity-100' : '{{ $hov }}'"
                                    class="{{ $btn }} {{ $btnTone['pri'] }}" title="سنجاق" aria-label="سنجاق">
                                <span class="material-symbols-rounded text-[16px]" :class="isFavorite({{ $taskId }}) ? 'font-fill' : ''">push_pin</span>
                            </button>
                        @endif
                        @break

                    @case('copy')
                        <x-ui.buttons.copy wire:key="taskboard-card-copy-btn" :text="route('tasks', ['open' => $taskId])" message="لینک وظیفه کپی شد"
                                           class="{{ $hov }} !w-6 !h-6 !p-0 !rounded-xl transition-all duration-200"/>
                        @break

                    @case('reminder')
                        @if($a['show'])
                            <span wire:key="taskboard-card-reminder" @click.stop>
                                <x-dashboard.reminder-trigger :for="\App\Models\Task::make(['id' => $taskId])" variant="inline" tooltip-position="right"/>
                            </span>
                        @endif
                        @break

                    @default
                        <button wire:key="taskboard-{{ $a[0] }}-btn" type="button" {{ $a[1] }}="{{ $a[2] }}"
                        class="{{ ($a[6] ?? false) ? '' : $hov }} {{ $btn }} {{ $btnTone[$a[5]] }}"
                        title="{{ $a[3] }}" aria-label="{{ $a[3] }}">
                        <span class="material-symbols-rounded text-[16px]">{{ $a[4] }}</span>
                        </button>
                @endswitch
            @endforeach
        </div>
    </div>
</div>
