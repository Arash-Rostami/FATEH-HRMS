<div class="relative flex flex-col rounded-2xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] bg-[var(--md-sys-color-surface)] p-5 md:p-6 space-y-6 shadow-[0_12px_40px_color-mix(in_srgb,var(--md-sys-color-primary)_6%,transparent)] overflow-hidden">

    <!-- Header -->
    <div class="flex items-center justify-between pb-4 border-b border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]">
        <div class="flex items-center gap-3">
            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] text-[var(--md-sys-color-primary)] shadow-sm">
                <span class="material-symbols-rounded text-[22px]">{{ $editingWorkflowId ? 'edit_document' : 'add_task' }}</span>
            </div>
            <p class="text-[16px] font-bold text-[var(--md-sys-color-on-surface)] tracking-wide">{{ $editingWorkflowId ? 'ویرایش چرخه' : 'چرخهٔ جدید' }}</p>
        </div>
        <x-ui.modals.close-button close="$wire.closeEditor()" size="lg"/>
    </div>

    <!-- General Info -->
    <div class="space-y-1">
        <x-ui.forms.input label="نام چرخه" name="workflowName" wire:model="workflowName" icon="conversion_path"/>
        @error('name')
        <p class="flex items-center gap-1 text-[12px] font-medium text-[var(--md-sys-color-error)] animate-toast-in">
            <span class="material-symbols-rounded text-[14px]">error</span>
            {{ $message }}
        </p>
        @enderror
    </div>

    <!-- Steps -->
    <div class="space-y-4">
        @foreach($steps as $i => $step)
            <div wire:key="workflow-step-{{ $i }}" class="group relative flex flex-col rounded-2xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] bg-[var(--md-sys-color-surface-container-lowest)] p-4 sm:p-5 space-y-5 transition-all duration-300 ease-out hover:border-[color-mix(in_srgb,var(--md-sys-color-primary)_30%,transparent)] hover:shadow-sm">

                <!-- Step Header: Number, Title, Remove -->
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-8 h-8 rounded-xl bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] text-[var(--md-sys-color-primary)] text-[13px] font-black shrink-0 shadow-[0_2px_8px_color-mix(in_srgb,var(--md-sys-color-primary)_15%,transparent)]">
                        {{ convertToPersian($i + 1) }}
                    </div>

                    <div class="flex-1">
                        <x-ui.forms.input label="عنوان مرحله (مثلاً: بررسی اولیه)" name="steps.{{ $i }}.label" wire:model="steps.{{ $i }}.label"/>
                    </div>

                    @if(count($steps) > 1)
                        <button type="button" wire:key="workflow-step-remove-{{ $i }}" wire:click="removeStep({{ $i }})" title="حذف مرحله" class="flex items-center justify-center w-9 h-9 rounded-xl text-[var(--md-sys-color-on-surface-variant)] opacity-60 hover:opacity-100 hover:bg-[color-mix(in_srgb,var(--md-sys-color-error)_12%,transparent)] hover:text-[var(--md-sys-color-error)] active:scale-[0.95] transition-all duration-200 shrink-0">
                            <span class="material-symbols-rounded text-[20px]">delete_outline</span>
                        </button>
                    @endif
                </div>

                <!-- Step Configurations -->
                <div class="flex flex-wrap items-center gap-4 border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)] pt-4">
                    <div class="w-40">
                        <x-ui.forms.select label="نوع مرحله" name="steps.{{ $i }}.type" wire:model.live="steps.{{ $i }}.type">
                            <option value="action">اقدام</option>
                            <option value="approval">تأییدیه</option>
                        </x-ui.forms.select>
                    </div>

                    @if(($step['type'] ?? 'action') !== 'approval')
                        <label wire:key="workflow-step-populate-toggle-{{ $i }}" class="group/toggle flex items-center gap-2.5 cursor-pointer select-none">
                            <x-ui.forms.checkbox wire:key="workflow-step-populate-box-{{ $i }}" name="steps.{{ $i }}.populate_task" :live="true"/>
                            <span class="text-[13px] font-medium text-[var(--md-sys-color-on-surface-variant)] group-hover/toggle:text-[var(--md-sys-color-on-surface)] transition-colors duration-200">این مرحله یک وظیفهٔ واقعی بسازد</span>
                        </label>
                    @endif
                </div>

                <!-- Approval: Rejection Target -->
                @if(($step['type'] ?? 'action') === 'approval')
                    <div wire:key="workflow-step-reject-target-{{ $i }}" class="animate-toast-in">
                        <x-ui.forms.select label="در صورت رد، بازگشت به" name="steps.{{ $i }}.reject_to_step" wire:model="steps.{{ $i }}.reject_to_step" icon="keyboard_return"
                            class="!bg-[color-mix(in_srgb,var(--md-sys-color-error)_6%,transparent)] !border-[color-mix(in_srgb,var(--md-sys-color-error)_30%,transparent)] focus:!border-[var(--md-sys-color-error)] focus:!ring-[color-mix(in_srgb,var(--md-sys-color-error)_20%,transparent)]">
                            <option value="">مرحله قبل (پیش‌فرض)</option>
                            @for($j = 0; $j < $i; $j++)
                                <option value="{{ $j }}">مرحلهٔ «{{ $steps[$j]['label'] ?: convertToPersian($j + 1) }}»</option>
                            @endfor
                        </x-ui.forms.select>
                    </div>
                @endif

                <!-- Task Populator Fields -->
                @if(($step['populate_task'] ?? false) && ($step['type'] ?? 'action') !== 'approval')
                    <div wire:key="workflow-step-populate-fields-{{ $i }}" class="relative overflow-hidden rounded-2xl border border-[color-mix(in_srgb,var(--md-sys-color-primary)_25%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-primary)_4%,transparent)] p-4 sm:p-5 space-y-3 animate-toast-in">

                        <div class="absolute -right-6 -top-6 text-[var(--md-sys-color-primary)] opacity-[0.03] pointer-events-none select-none">
                            <span class="material-symbols-rounded" style="font-size: 140px;">assignment</span>
                        </div>

                        <div class="relative z-10 space-y-3">
                            <h4 class="text-[12px] font-bold text-[var(--md-sys-color-primary)] mb-2 flex items-center gap-1.5">
                                <span class="material-symbols-rounded text-[16px]">task_alt</span>
                                جزئیات وظیفهٔ خودکار
                            </h4>

                            <x-ui.forms.input label="عنوان وظیفه (مثلاً: تکمیل فرم اطلاعات)" name="steps.{{ $i }}.task_title" wire:model="steps.{{ $i }}.task_title"/>

                            <x-ui.forms.textarea label="توضیحات وظیفه (اختیاری)" name="steps.{{ $i }}.task_description" wire:model="steps.{{ $i }}.task_description" :rows="2"/>

                            <x-ui.forms.input type="number" min="0" max="365" label="مهلت (روز، از زمان فعال‌شدن این مرحله)" name="steps.{{ $i }}.deadline_days" wire:model="steps.{{ $i }}.deadline_days" icon="hourglass_empty"/>
                        </div>
                    </div>
                @endif

                <!-- Assignees Picker -->
                <div wire:key="workflow-step-assignees-{{ $i }}" x-data="{ memberQuery: '' }" class="pt-2">
                    <label class="flex items-center gap-1.5 text-[12px] font-bold text-[var(--md-sys-color-on-surface-variant)] mb-3 select-none">
                        <span class="material-symbols-rounded text-[18px]">group</span>
                        مسئولان مرحله
                    </label>
                    <div class="space-y-2">
                        <x-dashboard.member-search/>
                        <div class="bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] rounded-xl p-2 min-h-[48px]">
                            <x-dashboard.member-picker wire:key="workflow-step-picker-{{ $i }}" model="steps.{{ $i }}.assignee_user_ids" :candidates="$memberCandidates" :search="false"/>
                        </div>
                    </div>
                    @error("steps.{$i}.assignee_user_ids")
                    <p class="flex items-center gap-1 text-[12px] font-medium text-[var(--md-sys-color-error)] mt-1.5 animate-toast-in"><span class="material-symbols-rounded text-[14px]">error</span>{{ $message }}</p>
                    @enderror
                </div>
            </div>
        @endforeach
    </div>

    <!-- Actions / Footer -->
    <div class="flex items-center justify-between pt-2 border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)] mt-2">
        <button type="button" wire:click="addStep" class="flex items-center gap-2 h-10 px-4 rounded-xl text-[13px] font-bold text-[var(--md-sys-color-primary)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] active:scale-[0.97] transition-all duration-200">
            <span class="material-symbols-rounded text-[20px]">add</span>
            افزودن مرحله
        </button>

        <x-ui.buttons.form type="button" wire:click="saveWorkflow" icon="save" class="shadow-sm hover:shadow-md transition-all duration-200 active:scale-[0.97]">
            ذخیره چرخه
        </x-ui.buttons.form>
    </div>
</div>
