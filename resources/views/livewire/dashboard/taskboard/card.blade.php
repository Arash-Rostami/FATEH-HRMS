@php
    $taskId = $task['id'];
    $isArchived = (bool) ($task['is_archived'] ?? false);
    $canChangeStatus = (bool) ($task['can_change_status'] ?? false);
    $ticketId = $task['ticket_id'] ?? null;

    $card = $presenter->cardMeta($task, $isPersonalBoard, $departmentOptions);
    $urgency = $card['urgency'];
    $colState = $card['colState'];
    $checklist = $card['checklist'];
    $checklistTotal = $card['checklistTotal'];
    $checklistDone = $card['checklistDone'];
    $attachments = $card['attachments'];
    $attachmentsCount = $card['attachmentsCount'];
    $stateChip = $card['stateChip'];
    $responsibleUser = $card['responsibleUser'];
    $departmentLabel = $card['departmentLabel'];
    $unit = $card['unit'] ?? null;
    $section = $card['section'] ?? null;
    $projectLabel = $card['projectLabel'] ?? null;
    $projectHasLink = $card['projectHasLink'] ?? false;
    $collaboratorUsers = $card['collaboratorUsers'];
    $canCyclePriority = $card['canCyclePriority'];
    $labelFilterMethod = $card['labelFilterMethod'];
    $hasDeadline = $card['hasDeadline'];
    $deadlineTitle = $card['deadlineTitle'];
    $isPending = $card['isPending'];
    $isPendingApproval = $card['isPendingApproval'] ?? false;
    $canApprove = $card['canApprove'] ?? false;
    $isUrgent = $card['isUrgent'];
    $metaChips = $card['metaChips'] ?? [];
    $actionSourceChip = $card['actionSourceChip'] ?? null;
    $lastTouchedBy = $card['lastTouchedBy'] ?? null;
    $isFavoriteExpression = $card['isFavoriteExpression'];
    $showTaskExpression = $card['showTaskExpression'];
@endphp

<div
    wire:key="task-card-{{ $taskId }}"
    data-rf="task-{{ $taskId }}"
    draggable="{{ $canChangeStatus && !$isArchived ? 'true' : 'false' }}"
    x-on:dragstart="{{ $canChangeStatus && !$isArchived ? 'handleDragStart($event, ' . $taskId . ')' : 'event.preventDefault()' }}"
    x-on:dragend="handleDragEnd($event)"
    x-on:dragover.prevent
    x-on:drop.stop="handleCardDrop($event, {{ $taskId }}, @js($column))"
    x-show="{{ $showTaskExpression }}"
    x-bind:class="{ 'order-first': {{ $isFavoriteExpression }} }"
    x-bind:style="(! @js($isUrgent || $isPending || $isArchived) && $store.tagged.tagBg(@js($taskId), @js('task'))) ? { backgroundColor: $store.tagged.tagBg(@js($taskId), @js('task')) } : {}"
    class="group relative flex flex-col gap-0 p-4 md:p-5 pt-6 taskboard-card rounded-2xl animate-bubble-in [content-visibility:auto] [contain-intrinsic-size:auto_260px] {{ $isPending ? 'bg-[var(--md-sys-color-error-container)]/30 border-[var(--md-sys-color-error)] border-2 shadow-[0_0_15px_color-mix(in_srgb,var(--md-sys-color-error)_40%,transparent)] animate-pulse-slow' : 'bg-[var(--md-sys-color-primary-container)]/70 border border-[var(--md-sys-color-outline-variant)] shadow-sm hover:shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] hover:border-[var(--md-sys-color-primary)]/40' }} hover:-translate-y-1 transition-all duration-300 {{ $isArchived ? 'cursor-default' : ($canChangeStatus ? 'cursor-grab active:cursor-grabbing' : 'cursor-default opacity-80') }} active:scale-[0.98] select-none {{ $isUrgent ? 'taskboard-card--urgent' : '' }} {{ $isArchived ? 'opacity-70 grayscale' : ($isPendingApproval ? 'opacity-50' : '') }}"
    dir="rtl"
    style="{{ $isUrgent ? '--urgency:' . $urgency['score'] . ';' : '' }}"
>
    <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-32 md:w-40 h-[2px] rounded-b-full opacity-80 group-hover:opacity-100 transition-opacity bg-gradient-to-r {{ $colState['lightGradient'] }} animate-subtle-pulse" style="box-shadow: 0 4px 12px color-mix(in srgb, var(--md-sys-color-{{ $colState['color'] }}) 40%, transparent);"></div>

    @if($isPersonalBoard && $selectionMode)
        <button
            wire:click="toggleTaskSelection({{ $taskId }})"
            class="absolute top-3 right-3 min-w-[28px] min-h-[28px] rounded-lg border-2 flex items-center justify-center transition-all duration-150 {{ in_array($taskId, $selectedTasks) ? 'bg-[var(--md-sys-color-primary)] border-[var(--md-sys-color-primary)]' : 'border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)]' }}"
        >
            @if(in_array($taskId, $selectedTasks))
                <span class="material-symbols-rounded text-sm text-[var(--md-sys-color-on-primary)]">check</span>
            @endif
        </button>
    @endif

    {{-- ═══ HEADER ═══ --}}
    <div class="flex items-center justify-between gap-2 pb-2.5 border-b border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)]">
        <div class="flex items-center gap-1 min-w-0 text-[10px] text-[var(--md-sys-color-on-surface-variant)]/70 font-medium tracking-wider uppercase">
            <span class="truncate shrink-0">#{{ $taskId }}</span>
            @if($projectLabel)
                @if($projectHasLink)
                    <a href="{{ route('projects', ['open' => $task['project_id'], 'tab' => 'report']) }}" wire:navigate
                       class="inline-flex items-center gap-0.5 min-w-0 text-[var(--md-sys-color-primary)] hover:brightness-110 transition"
                       title="پروژه: {{ $projectLabel }}">
                        <span class="material-symbols-rounded text-[12px] shrink-0">workspaces</span>
                        <span class="max-w-[120px] truncate normal-case" dir="auto">{{ $projectLabel }}</span>
                    </a>
                @else
                    <span class="inline-flex items-center gap-0.5 min-w-0" title="برچسب پروژه (بدون پروژهٔ متصل): {{ $projectLabel }}">
                        <span class="material-symbols-rounded text-[12px] shrink-0">workspaces</span>
                        <span class="max-w-[120px] truncate normal-case" dir="auto">{{ $projectLabel }}</span>
                    </span>
                @endif
                <span class="shrink-0">•</span>
            @endif
            <span class="truncate">{{ $task['created_formatted'] }}</span>
        </div>

        <div class="flex items-center gap-1 shrink-0 text-[10px] text-[var(--md-sys-color-on-surface-variant)]/70 font-medium tracking-wider uppercase">
            @if(($task['replies_count'] ?? 0) > 0)
                <span class="inline-flex items-center gap-0.5">
                    <span class="material-symbols-rounded text-[12px]">forum</span>{{ $task['replies_count'] }}
                </span>
            @endif
            @if($attachmentsCount > 0)
                <span class="inline-flex items-center gap-0.5">
                    <x-ui.hover-popover alignment="top-full right-0 mt-2 origin-top-right" width="w-48" surface="tertiary">
                        <x-slot:trigger>
                            <span class="inline-flex items-center gap-0.5"><span class="material-symbols-rounded text-[12px]">attach_file</span>{{ $attachmentsCount }}</span>
                        </x-slot:trigger>
                        <x-slot:body>
                            <div class="max-h-48 overflow-y-auto custom-scrollbar divide-y divide-[var(--md-sys-color-outline-variant)]/30">
                                @foreach($attachments as $attachment)
                                    @php
                                        $isImage = str_starts_with($attachment['mime'] ?? '', 'image/');
                                    @endphp
                                    @php
                                        $fileIcon = $dmsPresenter->extensionIcon(pathinfo($attachment['path'] ?? '', PATHINFO_EXTENSION));
                                    @endphp
                                    @if($isImage)
                                        <a href="{{ rtrim(asset('storage'), '/') }}/{{ $attachment['path'] }}" data-fancybox="task-{{ $taskId }}-attachments" data-caption="{{ $attachment['name'] ?? '' }}"
                                           class="flex items-center gap-2 px-3 py-2 text-xs text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-container-highest)] transition-colors">
                                            <img src="{{ rtrim(asset('storage'), '/') }}/{{ $attachment['path'] }}" class="w-5 h-5 rounded object-cover shrink-0" alt="">
                                            <span class="truncate" dir="auto">{{ $attachment['name'] ?? basename($attachment['path']) }}</span>
                                        </a>
                                    @else
                                        <a href="{{ rtrim(asset('storage'), '/') }}/{{ $attachment['path'] }}" target="_blank"
                                           class="flex items-center gap-2 px-3 py-2 text-xs text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-container-highest)] transition-colors">
                                            <span class="material-symbols-rounded text-[14px] {{ $fileIcon['text'] }}">{{ $fileIcon['icon'] }}</span>
                                            <span class="truncate" dir="auto">{{ $attachment['name'] ?? basename($attachment['path']) }}</span>
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </x-slot:body>
                    </x-ui.hover-popover>
                </span>
            @endif
        </div>
    </div>

    {{-- ═══ MAIN ═══ --}}
    <div class="flex flex-col gap-2.5 py-3">
        <x-ui.hover-popover class="!block w-full" alignment="top-full right-0 mt-2 origin-top-right" width="w-64" surface="default">
            <x-slot:trigger>
                <h3 class="text-sm font-bold text-[var(--md-sys-color-on-surface)] leading-snug line-clamp-2" dir="auto">
                    {{ superClean($task['title']) }}
                </h3>
            </x-slot:trigger>
            <x-slot:body>
                <div class="px-3 py-2 text-xs leading-relaxed text-[var(--md-sys-color-on-surface)]" dir="auto">{{ superClean($task['title']) }}</div>
            </x-slot:body>
        </x-ui.hover-popover>

        @if(!empty($task['description']))
            <x-ui.hover-popover class="!block w-full" alignment="top-full right-0 mt-2 origin-top-right" width="w-64" surface="default">
                <x-slot:trigger>
                    <p class="taskboard-card-description text-[11px] text-[var(--md-sys-color-on-surface-variant)] line-clamp-2 leading-relaxed" dir="auto">
                        {{ superClean($task['description']) }}
                    </p>
                </x-slot:trigger>
                <x-slot:body>
                    <div class="px-3 py-2 text-xs leading-relaxed text-[var(--md-sys-color-on-surface)]" dir="auto">{{ superClean($task['description']) }}</div>
                </x-slot:body>
            </x-ui.hover-popover>
        @endif

        @if($task['deadline'] || $ticketId || ($activeTab === 'my-tasks' && $task['assignee_name'] && $task['user_id'] !== auth()->id()) || ($activeTab === 'assigned-tasks' && $task['assignee_name']) || (!$isPersonalBoard && $task['assignee_name']) || in_array($urgency['kind'] ?? null, ['idle', 'sla', 'project-deadline-exceeded'], true) || $task['priority'] || !empty($task['labels']) || $checklistTotal > 0 || $stateChip || $responsibleUser || $departmentLabel || $isPendingApproval || $metaChips !== [] || $actionSourceChip)
            <div class="flex flex-wrap items-center gap-1.5">
                @php
                    $chip = $presenter->priorityChip($task['priority'] ?? null);
                @endphp
                @if($chip)
                    <x-ui.hover-popover alignment="top-full right-0 mt-2 origin-top-right" width="w-44" surface="default">
                        <x-slot:trigger>
                            <button
                                type="button"
                                x-on:click="{{ $canCyclePriority ? '$wire.cyclePriority(' . $taskId . ')' : 'event.preventDefault()' }}"
                                class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-lg text-[10px] font-bold border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] {{ $chip['class'] }} {{ $canCyclePriority ? 'hover:brightness-110 active:scale-95 transition cursor-pointer' : 'cursor-default' }}"
                            >
                                <span class="material-symbols-rounded text-[12px] {{ $chip['isUrgent'] ? 'font-fill' : '' }}">flag</span>
                                <span class="{{ $chip['isUrgent'] ? 'animate-pulse-ring' : '' }}">{{ $chip['label'] }}</span>
                            </button>
                        </x-slot:trigger>
                        <x-slot:body>
                            <div class="px-3 py-2 text-xs text-[var(--md-sys-color-on-surface)]">{{ $canCyclePriority ? 'تغییر اولویت' : $chip['label'] }}</div>
                        </x-slot:body>
                    </x-ui.hover-popover>
                @endif

                @if($stateChip)
                    <x-ui.hover-popover alignment="top-full right-0 mt-2 origin-top-right" width="w-44" surface="default">
                        <x-slot:trigger>
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-lg text-[10px] font-bold border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] {{ $stateChip['class'] }}">
                                <span class="material-symbols-rounded text-[12px]">{{ $stateChip['icon'] }}</span>
                                <span>{{ $stateChip['label'] }}</span>
                            </span>
                        </x-slot:trigger>
                        <x-slot:body>
                            <div class="px-3 py-2 text-xs text-[var(--md-sys-color-on-surface)]">تعیین تکلیف: {{ $stateChip['label'] }}</div>
                        </x-slot:body>
                    </x-ui.hover-popover>
                @endif

                @if($responsibleUser)
                    <x-ui.hover-popover alignment="top-full right-0 mt-2 origin-top-right" width="w-48" surface="default">
                        <x-slot:trigger>
                            <div class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-lg text-[10px] font-bold border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                                <span class="material-symbols-rounded text-[12px]">support_agent</span>
                                <span class="max-w-[140px] truncate">{{ $responsibleUser['name'] }}</span>
                            </div>
                        </x-slot:trigger>
                        <x-slot:body>
                            <div class="px-3 py-2 text-xs text-[var(--md-sys-color-on-surface)]" dir="auto">جوابگو: {{ $responsibleUser['name'] }}</div>
                        </x-slot:body>
                    </x-ui.hover-popover>
                @endif

                @if($departmentLabel)
                    <x-ui.hover-popover alignment="top-full right-0 mt-2 origin-top-right" width="w-56" surface="default">
                        <x-slot:trigger>
                            <div class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-lg text-[10px] font-bold border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">
                                <span class="material-symbols-rounded text-[12px]">corporate_fare</span>
                                <span class="max-w-[140px] truncate">{{ $departmentLabel }}</span>
                            </div>
                        </x-slot:trigger>
                        <x-slot:body>
                            <div class="px-3 py-2 text-xs text-[var(--md-sys-color-on-surface)] space-y-1.5" dir="auto">
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
                    @php
                        $tone = ['amethyst', 'sapphire', 'sage', 'gold'][crc32($label) % 4];
                    @endphp
                    <x-ui.hover-popover alignment="top-full right-0 mt-2 origin-top-right" width="w-48" surface="default">
                        <x-slot:trigger>
                            <button type="button" wire:click="{{ $labelFilterMethod }}({{ \Illuminate\Support\Js::from($label) }})"
                                    class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-lg text-[10px] font-bold border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] hover:brightness-110 active:scale-95 transition"
                                    style="background: var(--tool-{{ $tone }}-bg); color: var(--tool-{{ $tone }}-text);">
                                <span class="material-symbols-rounded text-[12px]">sell</span>
                                <span class="max-w-[140px] truncate" dir="auto">{{ $label }}</span>
                            </button>
                        </x-slot:trigger>
                        <x-slot:body>
                            <div class="px-3 py-2 text-xs text-[var(--md-sys-color-on-surface)]" dir="auto">{{ $label }}</div>
                        </x-slot:body>
                    </x-ui.hover-popover>
                @endforeach

                @foreach($metaChips as $metaChip)
                    <span title="{{ $metaChip['label'] }}: {{ $metaChip['value'] }}"
                          class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-lg text-[10px] font-bold border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)]">
                        <span class="material-symbols-rounded text-[12px]">database</span>
                        <span class="max-w-[100px] truncate" dir="auto">{{ $metaChip['label'] }}</span>
                        <span class="max-w-[120px] truncate" dir="auto">{{ $metaChip['value'] }}</span>
                    </span>
                @endforeach

                @if($actionSourceChip)
                    @php
                        $actionSourceTitle = trim(collect([
                            $actionSourceChip['source'] !== '' ? 'منشأ اقدام: ' . $actionSourceChip['source'] : null,
                            $actionSourceChip['domain'] !== '' ? 'حوزهٔ منشأ: ' . $actionSourceChip['domain'] : null,
                        ])->filter()->implode(' — '));
                    @endphp
                    <span title="{{ $actionSourceTitle }}"
                          class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-lg text-[10px] font-bold border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)]">
                        <span class="material-symbols-rounded text-[12px]">travel_explore</span>
                        <span class="max-w-[120px] truncate" dir="auto">{{ $actionSourceChip['label'] }}</span>
                    </span>
                @endif

                @if($checklistTotal > 0)
                    @php
                        $isComplete = $task['progress_percent'] === 100;
                    @endphp
                    <x-ui.hover-popover alignment="top-full right-0 mt-2 origin-top-right" width="w-56" surface="tertiary">
                        <x-slot:trigger>
                            <div class="inline-flex items-center px-1 py-0.5 rounded-lg border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] {{ $isComplete ? 'bg-[var(--md-sys-color-tertiary-container)]' : 'bg-[var(--md-sys-color-surface-container-highest)]' }}">
                                <x-ui.decor.progress-ring
                                    :percent="$task['progress_percent']"
                                    :size="26" :stroke="3" :label="$checklistDone.'/'.$checklistTotal"
                                    :color="$isComplete ? 'var(--md-sys-color-on-tertiary-container)' : 'var(--md-sys-color-on-surface-variant)'"/>
                            </div>
                        </x-slot:trigger>
                        <x-slot:body>
                            <div class="max-h-48 overflow-y-auto custom-scrollbar divide-y divide-[var(--md-sys-color-outline-variant)]/30">
                                @foreach($checklist as $item)
                                    <div class="flex items-center gap-2 px-3 py-2 text-xs {{ ($item['done'] ?? false) ? 'text-[var(--md-sys-color-on-surface-variant)] line-through' : 'text-[var(--md-sys-color-on-surface)]' }}">
                                        <span class="material-symbols-rounded text-[14px] shrink-0 {{ ($item['done'] ?? false) ? 'text-[var(--md-sys-color-tertiary)]' : 'text-[var(--md-sys-color-outline)]' }}">{{ ($item['done'] ?? false) ? 'check_box' : 'check_box_outline_blank' }}</span>
                                        <span class="truncate" dir="auto">{{ $item['text'] ?? '' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </x-slot:body>
                    </x-ui.hover-popover>
                @endif

                @if($ticketId)
                    <x-ui.hover-popover alignment="top-full right-0 mt-2 origin-top-right" width="w-44" surface="default">
                        <x-slot:trigger>
                            <a
                                href="{{ route('ths', ['open' => $ticketId]) }}"
                                wire:navigate
                                class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-lg text-[10px] font-bold border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] hover:brightness-110 transition"
                            >
                                <span class="material-symbols-rounded text-[12px]">support_agent</span>
                                <span>از تیکت</span>
                            </a>
                        </x-slot:trigger>
                        <x-slot:body>
                            <div class="px-3 py-2 text-xs text-[var(--md-sys-color-on-surface)]">مشاهده تیکت مرتبط</div>
                        </x-slot:body>
                    </x-ui.hover-popover>
                @endif

                @if($hasDeadline)
                    <x-ui.hover-popover alignment="top-full right-0 mt-2 origin-top-right" width="w-52" surface="default">
                        <x-slot:trigger>
                            <div class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-lg text-[10px] font-bold border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)]
                                {{ isPast($task['deadline']) && $task['status'] !== 'done'
                                    ? 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]'
                                    : 'bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)]' }}"
                            >
                                <span class="material-symbols-rounded text-[12px]">schedule</span>
                                <span class="tracking-wide">{{ in_array($urgency['kind'], ['overdue', 'due'], true) ? $urgency['label'] : $task['deadline_formatted'] }}</span>
                            </div>
                        </x-slot:trigger>
                        <x-slot:body>
                            <div class="px-3 py-2 text-xs leading-relaxed text-[var(--md-sys-color-on-surface)] whitespace-pre-wrap">{{ $deadlineTitle }}</div>
                        </x-slot:body>
                    </x-ui.hover-popover>
                @endif

                @if($activeTab === 'my-tasks')
                    @if($task['assignee_name'] && $task['user_id'] !== auth()->id())
                        <x-ui.hover-popover alignment="top-full right-0 mt-2 origin-top-right" width="w-48" surface="default">
                            <x-slot:trigger>
                                <div class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-lg text-[10px] font-bold border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                                    <span class="material-symbols-rounded text-[12px]">arrow_downward</span>
                                    <span class="max-w-[140px] truncate">از: {{ $task['delegator_name'] }}</span>
                                </div>
                            </x-slot:trigger>
                            <x-slot:body>
                                <div class="px-3 py-2 text-xs text-[var(--md-sys-color-on-surface)]" dir="auto">از: {{ $task['delegator_name'] }}</div>
                            </x-slot:body>
                        </x-ui.hover-popover>
                    @endif
                @elseif($activeTab === 'assigned-tasks')
                    @if($task['assignee_name'])
                        <x-ui.hover-popover alignment="top-full right-0 mt-2 origin-top-right" width="w-48" surface="default">
                            <x-slot:trigger>
                                <div class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-lg text-[10px] font-bold border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">
                                    <span class="material-symbols-rounded text-[12px]">arrow_upward</span>
                                    <span class="max-w-[140px] truncate">به: {{ $task['assignee_name'] }}</span>
                                </div>
                            </x-slot:trigger>
                            <x-slot:body>
                                <div class="px-3 py-2 text-xs text-[var(--md-sys-color-on-surface)]" dir="auto">به: {{ $task['assignee_name'] }}</div>
                            </x-slot:body>
                        </x-ui.hover-popover>
                    @endif
                @elseif($task['assignee_name'])
                    <x-ui.hover-popover alignment="top-full right-0 mt-2 origin-top-right" width="w-48" surface="default">
                        <x-slot:trigger>
                            <div class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-lg text-[10px] font-bold border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                                <span class="material-symbols-rounded text-[12px]">person</span>
                                <span class="max-w-[140px] truncate">{{ $task['assignee_name'] }}</span>
                            </div>
                        </x-slot:trigger>
                        <x-slot:body>
                            <div class="px-3 py-2 text-xs text-[var(--md-sys-color-on-surface)]" dir="auto">{{ $task['assignee_name'] }}</div>
                        </x-slot:body>
                    </x-ui.hover-popover>
                @endif

                @if(($urgency['kind'] ?? null) === 'idle')
                    <div class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-lg text-[10px] font-bold border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)]">
                        <span class="material-symbols-rounded text-[12px]">hourglass_top</span>
                        <span>{{ $urgency['label'] }}</span>
                    </div>
                @elseif(($urgency['kind'] ?? null) === 'sla')
                    <div class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-lg text-[10px] font-bold border border-[color-mix(in_srgb,var(--md-sys-color-error)_40%,transparent)] bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]">
                        <span class="material-symbols-rounded text-[12px]">gpp_bad</span>
                        <span>{{ $urgency['label'] }}</span>
                    </div>
                @elseif(($urgency['kind'] ?? null) === 'project-deadline-exceeded')
                    <div class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-lg text-[10px] font-bold border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">
                        <span class="material-symbols-rounded text-[12px]">flag</span>
                        <span>{{ $urgency['label'] }}</span>
                    </div>
                @endif

                @if($isPendingApproval)
                    <x-ui.hover-popover alignment="top-full right-0 mt-2 origin-top-right" width="w-44" surface="default">
                        <x-slot:trigger>
                            <div class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-lg text-[10px] font-bold border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                                <span class="material-symbols-rounded text-[12px]">verified_user</span>
                                <span class="animate-subtle-pulse">منتظر تأیید</span>
                            </div>
                        </x-slot:trigger>
                        <x-slot:body>
                            <div class="px-3 py-2 text-xs leading-relaxed text-[var(--md-sys-color-on-surface)]">وظیفه انجام‌شده است و منتظر تأیید مدیر پروژه می‌ماند.
                                @if($lastTouchedBy)
                                    <span class="block mt-1 text-[var(--md-sys-color-on-surface-variant)]">ویرایش توسط {{ $lastTouchedBy['user_name'] }} · {{ $lastTouchedBy['ago'] }}</span>
                                @endif
                            </div>
                        </x-slot:body>
                    </x-ui.hover-popover>
                @endif
            </div>
        @endif
    </div>

    {{-- ═══ FOOTER ═══ --}}
    <div class="flex items-center justify-between gap-1 pt-2.5 border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)]">
        @if(!empty($collaboratorUsers))
            <div class="flex items-center -space-x-1.5 rtl:space-x-reverse shrink-0" title="همکاران: {{ implode('، ', array_column($collaboratorUsers, 'name')) }}">
                @foreach(array_slice($collaboratorUsers, 0, 3) as $collaborator)
                    <img src="{{ $collaborator['avatar_url'] }}" alt="{{ $collaborator['name'] }}"
                         class="w-5 h-5 rounded-full border border-[var(--md-sys-color-surface)] object-cover ring-1 ring-[var(--md-sys-color-outline-variant)]">
                @endforeach
                @if(count($collaboratorUsers) > 3)
                    <span class="w-5 h-5 rounded-full bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)] text-[9px] font-bold flex items-center justify-center border border-[var(--md-sys-color-surface)] ring-1 ring-[var(--md-sys-color-outline-variant)]">
                        +{{ count($collaboratorUsers) - 3 }}
                    </span>
                @endif
            </div>
        @else
            <span></span>
        @endif

        <div class="task-action-rail flex items-center gap-1">
            @if($canApprove)
                <button
                    wire:click="approveTask({{ $taskId }})"
                    class="w-6 h-6 p-0 rounded-xl text-[var(--md-sys-color-tertiary)] hover:bg-[var(--md-sys-color-tertiary-container)] hover:text-[var(--md-sys-color-on-tertiary-container)] transition-all duration-200 active:scale-95 flex items-center justify-center"
                    title="تأیید وظیفه"
                    aria-label="تأیید وظیفه"
                >
                    <span class="material-symbols-rounded text-[16px]">task_alt</span>
                </button>
            @endif

            @if($isPersonalBoard)
                @if($canChangeStatus && !$isArchived)
                    <button
                        wire:click="editTask({{ $taskId }})"
                        class="opacity-0 group-hover:opacity-100 w-6 h-6 p-0 rounded-xl text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-all duration-200 active:scale-95 flex items-center justify-center"
                        title="{{ $ticketId ? 'مشاهده' : 'ویرایش' }}"
                        aria-label="{{ $ticketId ? 'مشاهده' : 'ویرایش' }}"
                    >
                        <span class="material-symbols-rounded text-[16px]">{{ $ticketId ? 'visibility' : 'edit' }}</span>
                    </button>
                @elseif($task['is_delegator'] && !$isArchived)
                    <button
                        wire:click="viewTask({{ $taskId }})"
                        class="opacity-0 group-hover:opacity-100 w-6 h-6 p-0 rounded-xl text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)] hover:text-[var(--md-sys-color-on-surface)] transition-all duration-200 active:scale-95 flex items-center justify-center"
                        title="مشاهده"
                        aria-label="مشاهده"
                    >
                        <span class="material-symbols-rounded text-[16px]">visibility</span>
                    </button>
                @endif
            @else
                <button
                    x-on:click="Livewire.dispatch('project-open-task', { taskId: {{ $taskId }} })"
                    class="opacity-0 group-hover:opacity-100 w-6 h-6 p-0 rounded-xl transition-all duration-200 active:scale-95 flex items-center justify-center {{ $canChangeStatus ? 'text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)]' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)] hover:text-[var(--md-sys-color-on-surface)]' }}"
                    title="{{ $canChangeStatus ? 'ویرایش' : 'مشاهده' }}"
                    aria-label="{{ $canChangeStatus ? 'ویرایش' : 'مشاهده' }}"
                >
                    <span class="material-symbols-rounded text-[16px]">{{ $canChangeStatus ? 'edit' : 'visibility' }}</span>
                </button>
            @endif

            @if($isPersonalBoard && $canChangeStatus && !$ticketId && !$isArchived)
                <div x-data="{ open: false }" class="relative">
                    <button
                        x-on:click="open = !open"
                        x-on:click.outside="open = false"
                        class="opacity-0 group-hover:opacity-100 w-6 h-6 p-0 rounded-xl text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-all duration-200 active:scale-95 flex items-center justify-center"
                        title="محول کردن"
                        aria-label="محول کردن"
                    >
                        <span class="material-symbols-rounded text-[16px]">person_add</span>
                    </button>
                    <div
                        x-show="open"
                        x-transition
                        class="absolute bottom-full mb-2 right-0 w-48 bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] rounded-xl shadow-lg z-50 overflow-hidden"
                        style="display: none;"
                    >
                        <div class="p-2 border-b border-[var(--md-sys-color-outline-variant)]/40 text-xs font-bold text-[var(--md-sys-color-on-surface-variant)]">
                            محول کردن به:
                        </div>
                        <ul class="max-h-40 overflow-y-auto custom-scrollbar">
                            <li>
                                <button
                                    wire:click="assignTask({{ $taskId }}, null)"
                                    x-on:click="open = false"
                                    class="w-full text-right px-3 py-2 text-xs hover:bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] transition-colors"
                                >
                                    بدون مسئول (خودم)
                                </button>
                            </li>
                            @foreach($staffMembers as $staff)
                                <li>
                                    <button
                                        wire:click="assignTask({{ $taskId }}, {{ $staff['id'] }})"
                                        x-on:click="open = false"
                                        class="w-full text-right px-3 py-2 text-xs hover:bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] transition-colors {{ $task['assigned_to'] === $staff['id'] ? 'bg-[var(--md-sys-color-primary-container)]/50 text-[var(--md-sys-color-primary)] font-bold' : '' }}"
                                    >
                                        {{ $staff['full_name'] }}
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @if(!($isUrgent || $isPending || $isArchived))
            <div x-data="{ tagOpen: false }" class="relative" x-on:click.away="tagOpen = false">
                <button
                    type="button"
                    x-on:click="tagOpen = !tagOpen"
                    :class="$store.tagged.isTagged(@js($taskId), @js('task')) ? '!opacity-100' : 'opacity-0 group-hover:opacity-100'"
                    class="w-6 h-6 p-0 rounded-xl text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)] hover:text-[var(--md-sys-color-on-surface)] transition-all duration-200 active:scale-95 flex items-center justify-center"
                    title="رنگ‌آمیزی کارت"
                    aria-label="رنگ‌آمیزی کارت"
                >
                    <span
                        class="material-symbols-rounded text-[16px]"
                        :style="$store.tagged.isTagged(@js($taskId), @js('task')) ? { color: $store.tagged.solid($store.tagged.getTag(@js($taskId), @js('task'))) } : null"
                    >palette</span>
                </button>
                <div
                    x-show="tagOpen"
                    x-cloak
                    x-transition
                    style="display: none;"
                    class="absolute bottom-full mb-2 right-0 z-50 flex items-center gap-1 p-1 rounded-lg bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] shadow-lg"
                >
                    <template x-for="(col, i) in $store.tagged.palette" :key="i">
                        <button
                            type="button"
                            x-on:click.stop="$store.tagged.setTag(@js($taskId), i, @js('task')); tagOpen = false"
                            :aria-label="'رنگ ' + (i + 1)"
                            class="w-5 h-5 rounded-full border-2 transition-transform hover:scale-110"
                            :class="$store.tagged.getTag(@js($taskId), @js('task')) === i ? 'border-[var(--md-sys-color-on-surface)]' : 'border-transparent'"
                            :style="{ 'background-color': col }"
                        ></button>
                    </template>
                    <button
                        type="button"
                        x-on:click.stop="$store.tagged.clearTag(@js($taskId), @js('task')); tagOpen = false"
                        aria-label="برداشتن رنگ"
                        title="برداشتن رنگ"
                        class="w-5 h-5 rounded-full flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-error)]"
                    >
                        <span class="material-symbols-rounded text-[14px]">close</span>
                    </button>
                </div>
            </div>
            @endif

            @if($isPersonalBoard)
                <button
                    type="button"
                    x-on:click="toggleFavorite({{ $taskId }})"
                    :class="isFavorite({{ $taskId }}) ? '!opacity-100' : 'opacity-0 group-hover:opacity-100'"
                    class="w-6 h-6 p-0 rounded-xl text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] transition-all duration-200 active:scale-95 flex items-center justify-center"
                    title="سنجاق"
                    aria-label="سنجاق"
                >
                    <span class="material-symbols-rounded text-[16px]" :class="isFavorite({{ $taskId }}) ? 'font-fill' : ''">push_pin</span>
                </button>
            @endif

            @if($canChangeStatus && !$isArchived && !$ticketId)
                <button
                    @if($isPersonalBoard)
                        wire:click="duplicateTask({{ $taskId }})"
                    @else
                        x-on:click="Livewire.dispatch('project-duplicate-task', { taskId: {{ $taskId }} })"
                    @endif
                    class="opacity-0 group-hover:opacity-100 w-6 h-6 p-0 rounded-xl text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)] hover:text-[var(--md-sys-color-on-surface)] transition-all duration-200 active:scale-95 flex items-center justify-center"
                    title="تکثیر"
                    aria-label="تکثیر"
                >
                    <span class="material-symbols-rounded text-[16px]">copy_all</span>
                </button>
            @endif

            <x-ui.buttons.copy
                :text="route('tasks', ['open' => $taskId])"
                message="لینک وظیفه کپی شد"
                class="opacity-0 group-hover:opacity-100 !w-6 !h-6 !p-0 !rounded-xl transition-all duration-200"
            />

            @if($isPersonalBoard && $task['can_delete'] && $task['status'] === 'done' && !$isArchived && !$ticketId)
                <button
                    wire:click="archiveTask({{ $taskId }})"
                    class="opacity-0 group-hover:opacity-100 w-6 h-6 p-0 rounded-xl text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)] hover:text-[var(--md-sys-color-on-surface)] transition-all duration-200 active:scale-95 flex items-center justify-center"
                    title="آرشیو"
                    aria-label="آرشیو"
                >
                    <span class="material-symbols-rounded text-[16px]">archive</span>
                </button>
            @endif

            @if($isPersonalBoard && $task['can_delete'] && $isArchived)
                <button
                    wire:click="unarchiveTask({{ $taskId }})"
                    class="opacity-0 group-hover:opacity-100 w-6 h-6 p-0 rounded-xl text-[var(--md-sys-color-on-tertiary-container)] hover:bg-[var(--md-sys-color-tertiary-container)] transition-all duration-200 active:scale-95 flex items-center justify-center"
                    title="خروج از آرشیو"
                    aria-label="خروج از آرشیو"
                >
                    <span class="material-symbols-rounded text-[16px]">unarchive</span>
                </button>
            @endif

            @if($isPersonalBoard && $task['is_delegator'] && $column !== 'done' && !$ticketId)
                <button
                    wire:click="undoAssignment({{ $taskId }})"
                    class="opacity-0 group-hover:opacity-100 w-6 h-6 p-0 rounded-xl text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)] hover:text-[var(--md-sys-color-on-surface)] transition-all duration-200 active:scale-95 flex items-center justify-center"
                    title="لغو واگذاری"
                    aria-label="لغو واگذاری"
                >
                    <span class="material-symbols-rounded text-[16px]">undo</span>
                </button>
            @endif

            @if($isPersonalBoard && $task['can_delete'] && !$ticketId && !$isArchived)
                <button
                    wire:click="deleteTask({{ $taskId }})"
                    class="opacity-0 group-hover:opacity-100 w-6 h-6 p-0 rounded-xl text-[var(--md-sys-color-error)] hover:bg-[var(--md-sys-color-error-container)] hover:text-[var(--md-sys-color-on-error-container)] transition-all duration-200 active:scale-95 flex items-center justify-center"
                    title="حذف"
                    aria-label="حذف"
                >
                    <span class="material-symbols-rounded text-[16px]">delete</span>
                </button>
            @endif
        </div>
    </div>
</div>
