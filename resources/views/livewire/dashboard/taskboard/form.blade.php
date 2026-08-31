@php
    $labelClass         = 'block text-sm font-semibold text-[var(--md-sys-color-on-surface)] mb-1.5';
    $errorClass         = 'flex items-center gap-1.5 mt-1.5 text-[11px] font-medium text-[var(--md-sys-color-error)]';
    $sectionHeaderClass = 'md:col-span-2 flex items-center gap-2 text-[var(--md-sys-color-primary)] font-bold text-sm pb-2 border-b border-[var(--md-sys-color-outline-variant)]/30 mt-4 first:mt-0 mb-1';

    $tabs = $presenter->taskFormTabs();
    $defaultTab = $presenter->defaultTaskFormTab($tabs, $errors);
    $badgeCounts = $presenter->tabBadgeCounts($this->taskComments);
@endphp

<x-ui.modals.action
    wire:model="isModalOpen"
    wire:key="unified-task-modal"
    title="{{ $isReadOnly ? 'مشاهده وظیفه' : ($isEditMode ? 'ویرایش وظیفه و اقدامات' : 'ایجاد وظیفه جدید') }}"
    action="{{ $isEditMode ? 'updateTask' : 'createTask' }}"
    confirm-text="{{ $isEditMode ? 'ذخیره تغییرات' : 'ایجاد وظیفه' }}"
    cancel-text="انصراف"
    :readonly="$isReadOnly"
    class="!max-w-3xl !w-full md:!w-7xl"
>
    <div class="modal-inner-card !w-full !max-w-none !p-5 md:!p-6" dir="rtl"
         x-effect="if (show && !formReady) { formTab = '{{ $defaultTab }}'; syncFormArrays(); setTimeout(() => { if (show) formReady = true }, 1000) } else if (!show) { formReady = false }"
         x-show="formReady">

        <nav
            class="flex items-center gap-1.5 p-1 bg-[var(--md-sys-color-surface-container-high)]/60 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30 w-fit mb-6 shadow-sm overflow-x-auto max-w-full">
            @foreach($tabs as $t)
                @continue($t['key'] === 'reply' && !$editingTaskId)
                @continue($t['key'] === 'history' && (!$editingTaskId || $this->editingTask?->project_id !== null))
                @php
                    $badgeCount = $badgeCounts[$t['key']] ?? 0;
                @endphp
                <button
                    type="button"
                    @click="formTab = '{{ $t['key'] }}'"
                    title="{{ $t['description'] ?? '' }}"
                    :class="formTab === '{{ $t['key'] }}'
                        ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-sm font-bold'
                        : 'text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-container-highest)] font-medium'"
                    class="relative px-3.5 py-2 rounded-xl text-xs sm:text-sm transition-all duration-200 flex items-center gap-2 shrink-0 select-none"
                >
                    <span class="material-symbols-rounded text-lg">{{ $t['icon'] }}</span>
                    <span>{{ $t['label'] }}</span>
                    @if($t['key'] === 'followup')
                        <span x-show="followupBadge > 0" x-text="followupBadge"
                              class="min-w-[1.25rem] h-5 px-1.5 rounded-full text-[11px] font-bold flex items-center justify-center tabular-nums transition-colors"
                              :class="formTab === '{{ $t['key'] }}' ? 'bg-[var(--md-sys-color-on-primary)]/20 text-[var(--md-sys-color-on-primary)]' : 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]'"></span>
                    @elseif($t['key'] === 'info')
                        <span x-show="infoBadge > 0" x-text="infoBadge"
                              class="min-w-[1.25rem] h-5 px-1.5 rounded-full text-[11px] font-bold flex items-center justify-center tabular-nums transition-colors"
                              :class="formTab === '{{ $t['key'] }}' ? 'bg-[var(--md-sys-color-on-primary)]/20 text-[var(--md-sys-color-on-primary)]' : 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]'"></span>
                    @elseif($badgeCount > 0)
                        <span
                            class="min-w-[1.25rem] h-5 px-1.5 rounded-full text-[11px] font-bold flex items-center justify-center tabular-nums transition-colors"
                            :class="formTab === '{{ $t['key'] }}' ? 'bg-[var(--md-sys-color-on-primary)]/20 text-[var(--md-sys-color-on-primary)]' : 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]'">
                            {{ $badgeCount }}
                        </span>
                    @endif
                    @if($errors->hasAny($t['errors'] ?? []))
                        <span
                            class="w-2 h-2 rounded-full bg-[var(--md-sys-color-error)] ring-2 ring-[var(--md-sys-color-surface)]"></span>
                    @endif
                </button>
            @endforeach
        </nav>

        @error('form')
        <div
            class="{{ $errorClass }} mb-4 p-3.5 rounded-2xl bg-[var(--md-sys-color-error-container)]/70 text-[var(--md-sys-color-on-error-container)] border border-[var(--md-sys-color-error)]/20">
            <span class="material-symbols-rounded text-base">error</span>
            <span class="text-xs leading-relaxed">{{ $message }}</span>
        </div>
        @enderror

        @if($editingTaskId && $this->editingTask?->ticket_id)
            <div
                class="flex items-center gap-3 mb-5 px-4 py-3 rounded-2xl bg-[var(--md-sys-color-secondary-container)]/50 text-[var(--md-sys-color-on-secondary-container)] border border-[var(--md-sys-color-secondary)]/20 text-xs">
                <span class="material-symbols-rounded text-xl shrink-0 text-[var(--md-sys-color-secondary)]">support_agent</span>
                <span class="leading-relaxed">این وظیفه به‌صورت خودکار از یک تیکت پشتیبانی ایجاد شده است. گفتگو و ویرایش جزئیات فقط از طریق تیکت مبدأ امکان‌پذیر است.</span>
            </div>
        @endif

        <div x-show="formTab === 'content'" x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
             class="space-y-5">
            <x-ui.forms.input label="عنوان وظیفه" name="form.newTitle" wire:model="form.newTitle" icon="title" required
                              :disabled="$isReadOnly" dir="auto"/>

            <x-ui.forms.textarea label="توضیحات" name="form.newDescription" wire:model="form.newDescription"
                                 icon="notes" rows="3" :disabled="$isReadOnly" :maximizable="!$isReadOnly" dir="auto"/>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-ui.forms.select label="مسئول انجام" name="form.selectedAssignee" wire:model="form.selectedAssignee"
                                   icon="person" :disabled="$isReadOnly">
                    <option value="">خودم (شخصی)</option>
                    @foreach($staffMembers as $staff)
                        <option value="{{ $staff['id'] }}">{{ $staff['full_name'] }}</option>
                    @endforeach
                </x-ui.forms.select>

                <x-ui.forms.select label="پروژه (اختیاری)" name="form.projectId" wire:model="form.projectId"
                                   icon="folder" :disabled="$isReadOnly">
                    <option value="">بدون پروژه</option>
                    @foreach($this->projectOptions as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </x-ui.forms.select>

                <x-ui.forms.select label="اولویت" name="form.priority" wire:model="form.priority" icon="flag"
                                   :disabled="$isReadOnly">
                    <option value="">بدون اولویت</option>
                    <option value="low">کم</option>
                    <option value="medium">متوسط</option>
                    <option value="high">بالا</option>
                    <option value="urgent">فوری</option>
                </x-ui.forms.select>
            </div>

            @if(!$isReadOnly && !$form->projectId)
                <div class="rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] p-3.5">
                    @if($form->pendingProjectName)
                        <div class="flex items-center gap-2 text-xs">
                            <span class="material-symbols-rounded text-base text-[var(--md-sys-color-primary)]">workspaces</span>
                            <span class="flex-1">پروژهٔ «{{ $form->pendingProjectName }}» هنگام ذخیرهٔ این وظیفه ساخته و پیوند می‌شود.</span>
                            <button type="button" wire:click="cancelPendingProject"
                                    class="px-2.5 py-1 rounded-lg text-[11px] font-semibold transition-all duration-150 hover:brightness-90 active:scale-95 bg-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_25%,transparent)] text-[var(--md-sys-color-on-surface-variant)]">
                                لغو
                            </button>
                        </div>
                    @else
                        <button type="button" wire:click="toggleLevelUpForm"
                                class="flex items-center gap-2 text-xs font-bold text-[var(--md-sys-color-primary)] hover:opacity-80 active:scale-[0.98] transition">
                            <span class="material-symbols-rounded text-base">workspaces</span>
                            <span>ساخت پروژه از این وظیفه</span>
                            <span class="material-symbols-rounded text-base mr-auto">{{ $showLevelUpForm ? 'expand_less' : 'expand_more' }}</span>
                        </button>

                        @if($showLevelUpForm)
                            <div class="mt-3 space-y-3">
                                <x-ui.forms.input label="نام پروژه" name="levelUpForm.name" wire:model="levelUpForm.name" icon="workspaces" required dir="auto"/>
                                @error('levelUpForm.name')
                                <div class="{{ $errorClass }}">
                                    <span class="material-symbols-rounded text-sm">error</span>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" wire:click="toggleLevelUpForm"
                                            class="px-3.5 py-1.5 rounded-lg text-[11px] font-semibold transition-all duration-150 hover:brightness-90 active:scale-95 bg-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_25%,transparent)] text-[var(--md-sys-color-on-surface-variant)]">
                                        انصراف
                                    </button>
                                    <button type="button" wire:click="createProjectFromTask" wire:loading.attr="disabled" wire:target="createProjectFromTask"
                                            class="px-3.5 py-1.5 rounded-lg text-[11px] font-semibold transition-all duration-150 hover:brightness-110 active:scale-95 disabled:opacity-40 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]">
                                        ثبت
                                    </button>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            @endif

            <div>
                <x-ui.forms.date
                    label="مهلت انجام"
                    prefix="form.deadline"
                    :startYear="jNow()"
                    :endYear="jNow() + 5"
                    :disabled="$isReadOnly"
                />
                @error('form.deadline')
                <div class="{{ $errorClass }}">
                    <span class="material-symbols-rounded text-sm">error</span>
                    <span>{{ $message }}</span>
                </div>
                @enderror
            </div>
        </div>

        @include('livewire.dashboard.taskboard.detail-fields', [
            'availableUnits'    => $this->availableUnits,
            'availableSections' => $this->availableSections,
        ])

        @include('livewire.dashboard.taskboard.meta-fields')

        @include('livewire.dashboard.taskboard.checklist')

        @if($editingTaskId)
            <div x-show="formTab === 'reply'" x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                 class="space-y-4">
                @if($this->editingTask?->ticket_id)
                    <div
                        class="rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] p-6 text-center space-y-3">
                        <div
                            class="w-12 h-12 rounded-2xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center mx-auto shadow-sm">
                            <span class="material-symbols-rounded text-2xl">support_agent</span>
                        </div>
                        <div class="space-y-1">
                            <h4 class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">پاسخ‌گویی از طریق تیکت
                                مبدأ</h4>
                            <p class="text-xs text-[var(--md-sys-color-on-surface-variant)] max-w-md mx-auto leading-relaxed">
                                این وظیفه از یک تیکت ایجاد شده است؛ تمامی گفتگوها و سوابق پاسخ‌دهی در همان تیکت ذخیره
                                می‌شوند.</p>
                        </div>
                        <a href="{{ route('ths', ['open' => $this->editingTask->ticket_id]) }}"
                           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] hover:opacity-90 active:scale-95 transition-all shadow-sm">
                            <span class="material-symbols-rounded text-base">open_in_new</span>
                            <span>مشاهده و ارسال پاسخ در تیکت</span>
                        </a>
                    </div>
                @else
                    <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] px-1 mb-3">این گفتگو خصوصی است؛ فقط ایجادکننده، مسئول انجام و همکاران این وظیفه آن را می‌بینند و پاسخ‌ها را دریافت می‌کنند.</p>

                    <div class="relative flex flex-col gap-3 max-h-96 overflow-y-auto custom-scrollbar pr-1 pl-1">
                        @if($this->taskComments->isNotEmpty())
                            <div class="absolute top-3 bottom-3 right-[13px] w-px bg-[var(--md-sys-color-outline-variant)] opacity-30"></div>
                        @endif

                        @forelse($this->taskComments as $reply)
                            @include('livewire.dashboard.taskboard.reply-row', ['reply' => $reply])
                        @empty
                            <div
                                class="flex flex-col items-center justify-center py-10 px-4 rounded-2xl border border-dashed border-[var(--md-sys-color-outline-variant)]/50 text-center">
                                <span class="material-symbols-rounded text-3xl text-[var(--md-sys-color-outline)] mb-2">forum</span>
                                <p class="text-xs font-medium text-[var(--md-sys-color-outline)]">هنوز گفتگویی برای این
                                    وظیفه ثبت نشده است.</p>
                            </div>
                        @endforelse
                    </div>

                    @if($this->canReplyToTask)
                        <form wire:submit.prevent="postTaskReply"
                              class="pt-3 border-t border-[var(--md-sys-color-outline-variant)]/30 space-y-3">
                            <div
                                class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container-lowest)] p-2.5 transition-all focus-within:border-[var(--md-sys-color-primary)] focus-within:ring-2 focus-within:ring-[var(--md-sys-color-primary)]/20">
                                <textarea wire:model.defer="taskReplyForm.body"
                                          rows="3"
                                          placeholder="پاسخ خود را بنویسید…"
                                          class="w-full bg-transparent p-1.5 text-xs sm:text-sm resize-none outline-none border-none focus:ring-0 text-[var(--md-sys-color-on-surface)] placeholder-[var(--md-sys-color-outline)]"></textarea>

                                @error('taskReplyForm.body')
                                <p class="text-[11px] font-medium text-[var(--md-sys-color-error)] px-1.5 mt-1">{{ $message }}</p>
                                @enderror

                                @if(count($taskReplyForm->files))
                                    <div class="flex flex-wrap items-center gap-1.5 pt-2">
                                        @foreach($taskReplyForm->files as $i => $file)
                                            <div wire:key="staged-reply-file-{{ $i }}"
                                                 class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-lg max-w-[180px] bg-[var(--md-sys-color-surface-container-high)] border border-[var(--md-sys-color-outline-variant)]/40">
                                                @if(str_starts_with($file->getMimeType() ?? '', 'image/'))
                                                    <img src="{{ $file->temporaryUrl() }}"
                                                         class="w-4 h-4 rounded object-cover flex-shrink-0" alt="">
                                                @else
                                                    <span class="material-symbols-rounded text-[12px] flex-shrink-0">attach_file</span>
                                                @endif
                                                <span
                                                    class="text-[10px] font-bold truncate">{{ $file->getClientOriginalName() }}</span>
                                                <button type="button" wire:click="removeReplyAttachment({{ $i }})"
                                                        aria-label="حذف فایل"
                                                        class="flex-shrink-0 w-4 h-4 rounded-full flex items-center justify-center hover:bg-[var(--md-sys-color-error-container)] hover:text-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-surface-variant)] transition-colors">
                                                    <span class="material-symbols-rounded text-[11px]">close</span>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <div
                                    class="flex items-center justify-between gap-2 pt-2 border-t border-[var(--md-sys-color-outline-variant)]/30">
                                    <label
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-medium text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-highest)] cursor-pointer transition-colors">
                                        <span
                                            class="material-symbols-rounded text-base text-[var(--md-sys-color-primary)]">attach_file</span>
                                        <span>پیوست فایل</span>
                                        <input type="file" multiple wire:model="taskReplyForm.files" class="hidden"/>
                                    </label>

                                    <x-ui.buttons.form type="submit"
                                                       loading="postTaskReply"
                                                       wire:loading.attr="disabled"
                                                       wire:target="postTaskReply"
                                                       class="!px-4 !py-1.5 !h-8 text-xs font-bold rounded-lg shadow-sm">
                                        ارسال پاسخ
                                    </x-ui.buttons.form>
                                </div>
                            </div>

                            @error('taskReplyForm.files')
                            <p class="{{ $errorClass }}">{{ $message }}</p>
                            @enderror
                        </form>
                    @endif
                @endif
            </div>
        @endif

        @if($editingTaskId && $this->editingTask?->project_id === null)
            <div x-show="formTab === 'history'" x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                 class="flex flex-col gap-3 h-96 overflow-y-auto custom-scrollbar pr-1 pl-1">
                @forelse($this->taskHistory as $entry)
                    <div wire:key="task-history-{{ $entry['id'] }}" class="flex flex-col items-center gap-1 shrink-0">
                        <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg text-[11px] font-bold border bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)] border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] hover:brightness-110 transition">
                            <span class="material-symbols-rounded text-[13px]">{{ $entry['icon'] }}</span>
                            <span dir="auto">{{ $entry['body'] }}</span>
                        </span>
                        <span class="block text-[10px] text-[var(--md-sys-color-on-surface-variant)] opacity-60" title="{{ toJalali($entry['created_at']) }}">{{ toJalaliRelative($entry['created_at']) }}</span>
                    </div>
                @empty
                    <x-ui.empty icon="timeline" title="هنوز تغییری برای این وظیفه ثبت نشده" variant="list" :fill="true"/>
                @endforelse
            </div>
        @endif
    </div>
</x-ui.modals.action>
