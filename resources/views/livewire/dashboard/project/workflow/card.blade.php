@php
    $isDraft = $wf->isDraft();
    $currentStep = $wf->currentStep();
    $totalSteps = count($wf->steps);
    $stepNumber = $workflowPresenter->stepNumber($wf);
    $canAct = $workflowPresenter->canAct($currentStep, $isOwner, (int) auth()->id());
    $isPopulated = $workflowPresenter->isPopulated($currentStep);
    $task = $workflowPresenter->populatedTask($currentStep, $batchedTasks);
@endphp

<div wire:key="workflow-card-{{ $wf->id }}" class="group/wf relative flex flex-col rounded-2xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] bg-[var(--md-sys-color-surface)] p-4 sm:p-5 space-y-4 shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_4%,transparent)] transition-all duration-300 ease-out hover:border-[color-mix(in_srgb,var(--md-sys-color-primary)_30%,transparent)] hover:shadow-[0_12px_32px_color-mix(in_srgb,var(--md-sys-color-primary)_8%,transparent)]">

    <!-- Header -->
    <div class="flex items-start justify-between gap-3">
        <div class="flex items-start gap-3 min-w-0">
            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] text-[var(--md-sys-color-primary)] shrink-0 shadow-sm transition-transform duration-300 group-hover/wf:scale-105">
                <span class="material-symbols-rounded text-[20px]">{{ $isDraft ? 'edit_document' : 'account_tree' }}</span>
            </div>
            <div class="min-w-0 pt-0.5">
                <p class="text-[14px] sm:text-[15px] font-bold text-[var(--md-sys-color-on-surface)] truncate tracking-tight">{{ $wf->name }}</p>
                <div class="flex flex-wrap items-center gap-2 mt-1.5">
                    <x-ui.decor.status-pill :title="$isDraft ? 'پیش‌نویس' : 'در حال اجرا'" :color="$isDraft ? 'secondary' : 'primary'"/>

                    @if(!$isDraft)
                        <span wire:key="workflow-card-step-count-{{ $wf->id }}" class="inline-flex items-center justify-center h-[22px] px-2 rounded-md border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] bg-[var(--md-sys-color-surface-container)] text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] tabular-nums shadow-sm">
                            مرحله {{ convertToPersian($stepNumber) }} از {{ convertToPersian($totalSteps) }}
                        </span>
                    @endif

                    @if(!$isDraft && $workflowPresenter->isStuck($wf))
                        <span class="inline-flex items-center gap-1 h-[22px] px-2 rounded-md border border-[color-mix(in_srgb,var(--md-sys-color-error)_30%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-error)_12%,transparent)] text-[11px] font-bold text-[var(--md-sys-color-error)] shadow-sm animate-pulse-ring">
                            <span class="material-symbols-rounded text-[13px]">warning</span> معطل مانده
                        </span>
                    @endif
                </div>
            </div>
        </div>

        @if($isOwner)
            <div wire:key="workflow-card-owner-actions-{{ $wf->id }}" class="flex items-center gap-1 shrink-0 bg-[var(--md-sys-color-surface-container-lowest)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] rounded-[14px] p-1 opacity-100 sm:opacity-50 group-hover/wf:opacity-100 transition-opacity duration-200">
                @if($isDraft)
                    <div wire:key="workflow-card-draft-actions-{{ $wf->id }}" class="contents">
                        <button type="button" wire:click="openEditWorkflow({{ $wf->id }})" title="ویرایش" class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-on-surface)_8%,transparent)] hover:text-[var(--md-sys-color-on-surface)] active:scale-[0.95] transition-all duration-200">
                            <span class="material-symbols-rounded text-[18px]">edit</span>
                        </button>
                        <x-ui.buttons.form wire:click="startWorkflow({{ $wf->id }})" size="icon" variant="tonal" icon="play_arrow" title="شروع چرخه" class="w-8 h-8 rounded-lg !p-0 !min-w-0" />
                    </div>
                @endif
                <button type="button" wire:click="saveAsTemplate({{ $wf->id }})" title="ذخیره به‌عنوان الگو" class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-on-surface)_8%,transparent)] hover:text-[var(--md-sys-color-primary)] active:scale-[0.95] transition-all duration-200">
                    <span class="material-symbols-rounded text-[18px]">bookmark_add</span>
                </button>
                <button type="button" wire:click="cancelWorkflow({{ $wf->id }})" wire:confirm="این چرخه لغو شود؟" title="لغو چرخه" class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-error)_12%,transparent)] hover:text-[var(--md-sys-color-error)] active:scale-[0.95] transition-all duration-200">
                    <span class="material-symbols-rounded text-[18px]">cancel</span>
                </button>
            </div>
        @endif
    </div>

    <!-- Body -->
    @if($isDraft)
        <div wire:key="workflow-card-draft-body-{{ $wf->id }}" class="flex flex-col items-center justify-center py-6 px-4 rounded-xl border border-dashed border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_60%,transparent)] bg-[var(--md-sys-color-surface-container-lowest)] text-center animate-toast-in">
            <span class="material-symbols-rounded text-[28px] text-[var(--md-sys-color-on-surface-variant)] opacity-40 mb-2">linear_scale</span>
            <p class="text-[13px] font-medium text-[var(--md-sys-color-on-surface-variant)]">
                {{ convertToPersian($totalSteps) }} مرحله تعریف‌شده است.<br>برای به‌جریان افتادن وظایف، دکمهٔ <strong class="text-[var(--md-sys-color-primary)]">شروع</strong> را بزنید.
            </p>
        </div>
    @elseif($currentStep)
        <div wire:key="workflow-card-active-body-{{ $wf->id }}" class="flex flex-col space-y-4 pt-3 border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]">

            <!-- Current Step Info Card -->
            <div class="relative flex items-center gap-3.5 rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] bg-[var(--md-sys-color-surface-container-lowest)] p-3">
                <div class="flex items-center justify-center w-9 h-9 rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)] text-[14px] font-black shrink-0 shadow-sm">
                    {{ convertToPersian($stepNumber) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-[13px] font-bold text-[var(--md-sys-color-on-surface)] truncate">{{ $currentStep['label'] }}</p>
                    <p class="flex items-center gap-1.5 text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)] mt-0.5 truncate">
                        <span class="material-symbols-rounded text-[14px] opacity-70">group</span>
                        مسئولان: {{ collect($currentStep['assignee_user_ids'] ?? [])->map(fn($id) => $memberNames[$id] ?? ((int) $id === (int) auth()->id() ? 'شما' : '—'))->implode('، ') ?: '—' }}
                    </p>
                </div>
            </div>

            <!-- Populated Task -->
            @if($isPopulated)
                <div wire:key="workflow-step-render-populated-{{ $wf->id }}" class="animate-toast-in">
                    @if($task && !$task->trashed())
                        <div wire:key="workflow-populated-task-live-{{ $wf->id }}" class="relative overflow-hidden rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-primary)_20%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-primary)_4%,transparent)] p-3.5 shadow-sm transition-colors duration-300 hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_6%,transparent)]">
                            <div class="absolute inset-y-0 right-0 w-[3px] bg-[var(--md-sys-color-primary)]"></div>

                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="text-[13px] font-bold text-[var(--md-sys-color-primary)] truncate">{{ $task->title }}</p>
                                    <p class="flex items-center gap-1.5 text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)] mt-1.5 truncate">
                                        <span class="material-symbols-rounded text-[14px]">person</span> {{ $task->assignee?->name ?? '—' }}
                                        @if($task->deadline)
                                            <span class="opacity-40 select-none">•</span>
                                            <span class="material-symbols-rounded text-[14px]">event</span> {{ toJalaliSmart($task->deadline) }}
                                        @endif
                                    </p>
                                </div>
                                <div class="shrink-0 scale-90 origin-top-left -mt-1 -ml-1">
                                    <x-ui.decor.status-pill :state="$taskBoardPresenter->columnState($task->status)"/>
                                </div>
                            </div>

                            <div class="flex items-center justify-between gap-2 border-t border-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] pt-2.5 mt-3">
                                <a href="{{ route('tasks', ['open' => $task->id]) }}" wire:navigate dir="auto" class="inline-flex items-center gap-1 text-[12px] font-bold text-[var(--md-sys-color-primary)] hover:text-[color-mix(in_srgb,var(--md-sys-color-primary)_80%,black)] hover:underline active:scale-[0.97] transition-all">
                                    نمایش در بورد <span class="material-symbols-rounded text-[16px]">arrow_forward</span>
                                </a>
                                @if($isOwner)
                                    <button type="button" wire:click="forceAdvance({{ $wf->id }}, {{ $wf->current_step }})" wire:confirm="پیشروی اجباری این مرحله انجام شود؟" class="inline-flex items-center gap-1 text-[11px] font-bold text-[var(--md-sys-color-error)] opacity-80 hover:opacity-100 hover:underline active:scale-[0.97] transition-all">
                                        پیشروی اجباری <span class="material-symbols-rounded text-[14px]">fast_forward</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @else
                        <div wire:key="workflow-populated-task-deleted-{{ $wf->id }}" class="flex items-center justify-between gap-2 rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-error)_30%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-error)_5%,transparent)] p-3 shadow-sm">
                            <span class="flex items-center gap-1.5 text-[12px] font-medium text-[var(--md-sys-color-error)]">
                                <span class="material-symbols-rounded text-[16px]">delete_forever</span>
                                وظیفهٔ این مرحله حذف شده است.
                            </span>
                            @if($isOwner)
                                <button type="button" wire:click="forceAdvance({{ $wf->id }}, {{ $wf->current_step }})" wire:confirm="پیشروی اجباری این مرحله انجام شود؟" class="text-[11px] font-bold text-[var(--md-sys-color-error)] underline underline-offset-2 hover:text-[color-mix(in_srgb,var(--md-sys-color-error)_80%,black)] transition-colors">پیشروی اجباری</button>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Approval Box -->
            @elseif(($currentStep['type'] ?? 'action') === 'approval')
                @if($canAct)
                    <div wire:key="workflow-step-render-approval-{{ $wf->id }}" class="flex flex-col gap-3 animate-toast-in">
                        <x-ui.forms.input label="یادداشت تأییدیه (اختیاری)" name="stepNotes.{{ $wf->id }}" wire:model="stepNotes.{{ $wf->id }}"/>
                        <div class="flex items-center gap-2">
                            <x-ui.buttons.form wire:click="advanceStep({{ $wf->id }}, {{ $wf->current_step }}, true)" icon="check_circle" class="flex-1 justify-center shadow-sm hover:shadow-md transition-shadow">تأیید و ادامه</x-ui.buttons.form>
                            <x-ui.buttons.form wire:click="advanceStep({{ $wf->id }}, {{ $wf->current_step }}, false)" wire:confirm="این مرحله رد و بازگردانده شود؟" variant="danger" icon="undo" class="flex-1 justify-center shadow-sm hover:shadow-md transition-shadow">رد و بازگشت</x-ui.buttons.form>
                        </div>
                    </div>
                @endif

                <!-- Action Box -->
            @else
                @if($canAct)
                    <div wire:key="workflow-step-render-action-{{ $wf->id }}" class="flex flex-col gap-3 animate-toast-in">
                        <x-ui.forms.input label="گزارش اقدام (اختیاری)" name="stepNotes.{{ $wf->id }}" wire:model="stepNotes.{{ $wf->id }}"/>
                        <x-ui.buttons.form wire:click="advanceStep({{ $wf->id }}, {{ $wf->current_step }}, true)" icon="task_alt" class="w-full justify-center shadow-sm hover:shadow-md transition-shadow">تکمیل و ادامه</x-ui.buttons.form>
                    </div>
                @endif
            @endif

            <!-- Reassign Module -->
            @if($canAct)
                <div wire:key="workflow-reassign-trigger-{{ $wf->id }}" class="pt-1">
                    @if($reassigningWorkflowId === $wf->id)
                        <div wire:key="workflow-reassign-open-{{ $wf->id }}" x-data="{ memberQuery: '' }" class="rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] bg-[var(--md-sys-color-surface-container-lowest)] p-3 space-y-2.5 animate-toast-in shadow-sm">
                            <x-dashboard.member-search/>
                            <div class="bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] rounded-lg p-1 min-h-[40px]">
                                <x-dashboard.member-picker wire:key="workflow-reassign-picker-{{ $wf->id }}" model="reassignSelection.{{ $wf->id }}" :candidates="$memberCandidates" :search="false" rounded="rounded-md" py="py-1.5"/>
                            </div>
                            <div class="flex items-center gap-2 pt-1">
                                <x-ui.buttons.form wire:click="reassignStep({{ $wf->id }})" variant="tonal" icon="check" class="flex-1 justify-center">ثبت واگذاری</x-ui.buttons.form>
                                <x-ui.buttons.form wire:click="closeReassign" variant="ghost" class="flex-1 justify-center bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)]">انصراف</x-ui.buttons.form>
                            </div>
                        </div>
                    @else
                        <button type="button" wire:key="workflow-reassign-closed-{{ $wf->id }}" wire:click="openReassign({{ $wf->id }})" class="group/reassign inline-flex items-center gap-1.5 text-[12px] font-bold text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-primary)] active:scale-[0.97] transition-all duration-200">
                            <span class="material-symbols-rounded text-[16px] group-hover/reassign:-rotate-12 transition-transform duration-300">manage_accounts</span>
                            واگذاری به فرد دیگر
                        </button>
                    @endif
                </div>
            @endif

            <!-- Log History -->
            @if(!empty($wf->step_log))
                <details wire:key="workflow-log-{{ $wf->id }}" class="group/log rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] bg-[var(--md-sys-color-surface-container-lowest)] overflow-hidden transition-all duration-300 mt-2">
                    <summary class="flex items-center justify-between cursor-pointer p-3 select-none hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_4%,transparent)] transition-colors">
                        <span class="flex items-center gap-1.5 text-[12px] font-bold text-[var(--md-sys-color-on-surface)]">
                            <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-on-surface-variant)]">history</span>
                            تاریخچه مراحل ({{ convertToPersian(count($wf->step_log)) }})
                        </span>
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-on-surface-variant)] group-open/log:-scale-y-100 transition-transform duration-300">expand_more</span>
                    </summary>

                    <ul class="px-4 pb-4 space-y-3 border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)] pt-3 animate-toast-in">
                        @foreach(array_reverse($wf->step_log) as $entry)
                            <li class="relative pl-3 flex flex-col gap-1 text-[12px] text-[var(--md-sys-color-on-surface-variant)] border-r-2 border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] pr-3">
                                <span class="font-bold text-[var(--md-sys-color-on-surface)]">
                                    «{{ $wf->steps[$entry['step']]['label'] ?? '—' }}»
                                    <span class="font-medium text-[var(--md-sys-color-on-surface-variant)] text-[11px] mr-1">توسط {{ $batchedUserNames[$entry['completed_by']] ?? 'کاربر حذف‌شده' }}</span>
                                </span>

                                <div class="flex items-center gap-2.5 text-[10px]">
                                    @if(($entry['approved'] ?? true) === false)
                                        <span class="inline-flex items-center h-4 px-1.5 rounded-sm bg-[color-mix(in_srgb,var(--md-sys-color-error)_12%,transparent)] text-[var(--md-sys-color-error)] font-bold">رد شد</span>
                                    @endif
                                    @if($entry['forced'] ?? false)
                                        <span class="inline-flex items-center h-4 px-1.5 rounded-sm bg-[color-mix(in_srgb,var(--md-sys-color-warning)_15%,transparent)] text-[var(--md-sys-color-warning)] font-bold">اجباری</span>
                                    @endif
                                    <span class="opacity-70 flex items-center gap-1 font-medium">
                                        <span class="material-symbols-rounded text-[12px]">schedule</span>
                                        {{ toJalaliRelative($entry['completed_at']) }}
                                    </span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </details>
            @endif

        </div>
    @endif
</div>
