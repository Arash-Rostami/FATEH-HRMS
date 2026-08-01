@php
    $labelClass         = 'block text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-2';
    $errorClass         = 'flex items-center gap-1.5 mt-2 text-[11px] text-[var(--md-sys-color-error)]';
    $sectionHeaderClass = 'md:col-span-2 flex items-center gap-2 text-[var(--md-sys-color-primary)] font-black text-sm pb-2 border-b border-[var(--md-sys-color-outline-variant)]/30 mt-4 first:mt-0 mb-1';

    $tabs = $presenter->taskFormTabs();
    $defaultTab = $presenter->defaultTaskFormTab($tabs, $errors);
@endphp

<x-ui.modals.action
    wire:model="isModalOpen"
    wire:key="unified-task-modal"
    title="{{ $isReadOnly ? 'مشاهده وظیفه' : ($isEditMode ? 'ویرایش وظیفه' : 'ایجاد وظیفه جدید') }}"
    action="{{ $isEditMode ? 'updateTask' : 'createTask' }}"
    confirm-text="{{ $isEditMode ? 'ذخیره تغییرات' : 'ایجاد وظیفه' }}"
    cancel-text="انصراف"
    :readonly="$isReadOnly"
    class="!max-w-3xl !w-full md:!w-7xl"
>
    <div class="modal-inner-card !w-full !max-w-none !p-5 md:!p-6" dir="rtl"
         x-data="{ tab: '{{ $defaultTab }}', ready: false }"
         x-effect="if (show && !ready) { setTimeout(() => { if (show) ready = true }, 1000) } else if (!show) { ready = false }"
         x-show="ready">

        <nav class="flex flex-wrap p-1 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30 w-fit mb-6 shadow-sm">
            @foreach($tabs as $t)
                @continue($t['key'] === 'reply' && !$editingTaskId)
                <button
                    type="button"
                    @click="tab = '{{ $t['key'] }}'"
                    :class="tab === '{{ $t['key'] }}'
                        ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_12px_color-mix(in_srgb,var(--md-sys-color-primary)_30%,transparent)]'
                        : 'text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                    class="relative px-4 py-2 rounded-xl text-sm font-bold transition-all duration-300 flex items-center gap-2"
                >
                    <span class="material-symbols-rounded text-base">{{ $t['icon'] }}</span>
                    {{ $t['label'] }}
                    @if($errors->hasAny($t['errors']))
                        <span class="w-1.5 h-1.5 rounded-full bg-[var(--md-sys-color-error)]"></span>
                    @endif
                </button>
            @endforeach
        </nav>

        @if($editingTaskId && $this->editingTask?->ticket_id)
            <div class="flex items-center gap-2 mb-5 px-4 py-2.5 rounded-xl bg-[var(--md-sys-color-primary-container)]/40 text-[var(--md-sys-color-on-primary-container)] text-xs">
                <span class="material-symbols-rounded text-[16px]">support_agent</span>
                <span>این وظیفه به‌صورت خودکار از یک تیکت ایجاد شده و فقط برای پیگیری است؛ ویرایش و گفتگو فقط از طریق خودِ تیکت انجام می‌شود.</span>
            </div>
        @endif

        <div x-show="tab === 'content'" class="space-y-5 md:space-y-6">
            <x-ui.forms.input label="عنوان وظیفه" name="form.newTitle" wire:model="form.newTitle" icon="title" required :disabled="$isReadOnly"/>
            <x-ui.forms.textarea label="توضیحات" name="form.newDescription" wire:model="form.newDescription" icon="notes" rows="3" :disabled="$isReadOnly" :maximizable="!$isReadOnly"/>

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

            <x-ui.forms.select label="مسئول انجام" name="form.selectedAssignee" wire:model="form.selectedAssignee" :disabled="$isReadOnly">
                <option value="">خودم (شخصی)</option>
                @foreach($staffMembers as $staff)
                    <option value="{{ $staff['id'] }}">{{ $staff['full_name'] }}</option>
                @endforeach
            </x-ui.forms.select>
        </div>

        @include('livewire.dashboard.taskboard.detail-fields', [
            'availableUnits'    => $this->availableUnits,
            'availableSections' => $this->availableSections,
        ])

        @if($editingTaskId)
            <div x-show="tab === 'reply'" class="space-y-3">
                @if($this->editingTask?->ticket_id)
                    <div class="rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] p-6 text-center space-y-3">
                        <span class="material-symbols-rounded text-3xl text-[var(--md-sys-color-primary)]">support_agent</span>
                        <p class="text-sm text-[var(--md-sys-color-on-surface-variant)]">این وظیفه از یک تیکت ایجاد شده؛ گفتگو و پاسخ‌گویی در همان تیکت انجام می‌شود.</p>
                        <a href="{{ route('ths', ['open' => $this->editingTask->ticket_id]) }}"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] hover:brightness-110 transition">
                            <span class="material-symbols-rounded text-[16px]">open_in_new</span>
                            مشاهده و پاسخ در تیکت
                        </a>
                    </div>
                @else
                    <div class="space-y-3 max-h-96 overflow-y-auto custom-scrollbar pr-1">
                        @forelse($this->editingTask?->replies ?? [] as $reply)
                            @include('livewire.dashboard.ths.reply-bubble', ['reply' => $reply])
                        @empty
                            <p class="text-xs italic opacity-70 text-[var(--md-sys-color-on-surface-variant)] text-center py-4">هنوز پاسخی ثبت نشده است...</p>
                        @endforelse
                    </div>

                    @if($this->canReplyToTask)
                        <form wire:submit.prevent="postTaskReply" class="pt-3 border-t border-[var(--md-sys-color-outline-variant)]/30 space-y-2">
                            <textarea wire:model.defer="taskReplyForm.body" rows="2" placeholder="پاسخ خود را بنویسید..."
                                      class="w-full rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] px-3 py-2.5 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-surface)]"></textarea>
                            @error('taskReplyForm.body') <p class="text-[11px] text-[var(--md-sys-color-error)]">{{ $message }}</p> @enderror

                            <div class="flex items-center justify-between gap-2">
                                <label class="text-xs cursor-pointer flex items-center gap-1.5 text-[var(--md-sys-color-on-surface-variant)]">
                                    <span class="material-symbols-rounded text-[16px]">attach_file</span>
                                    <input type="file" multiple wire:model="taskReplyForm.files" class="hidden"/>
                                    پیوست
                                </label>
                                <button type="submit" wire:loading.attr="disabled" wire:target="postTaskReply"
                                        class="px-4 py-2 rounded-xl text-xs font-bold bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] hover:brightness-110 transition disabled:opacity-50">
                                    ارسال پاسخ
                                </button>
                            </div>
                            @error('taskReplyForm.files') <p class="text-[11px] text-[var(--md-sys-color-error)]">{{ $message }}</p> @enderror
                        </form>
                    @endif
                @endif
            </div>
        @endif
    </div>
</x-ui.modals.action>
