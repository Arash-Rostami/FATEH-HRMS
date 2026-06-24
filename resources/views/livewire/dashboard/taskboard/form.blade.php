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
    <div class="modal-inner-card" dir="rtl" x-data="{ tab: '{{ $defaultTab }}' }">

        <nav class="flex flex-wrap p-1 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30 w-fit mb-6 shadow-sm">
            @foreach($tabs as $t)
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
                <option value="">{{ auth()->user()?->name }}</option>
                @foreach($staffMembers as $staff)
                    <option value="{{ $staff['id'] }}">{{ $staff['full_name'] }}</option>
                @endforeach
            </x-ui.forms.select>
        </div>

        @include('livewire.dashboard.taskboard.detail-fields', [
            'availableUnits'    => $this->availableUnits,
            'availableSections' => $this->availableSections,
        ])
    </div>
</x-ui.modals.action>
