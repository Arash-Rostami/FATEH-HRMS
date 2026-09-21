@php
    $errorClass = 'flex items-center gap-1.5 mt-2 text-[11px] text-[var(--md-sys-color-error)]';
    $tabs = $presenter->projectFormTabs();
    $defaultTab = $presenter->defaultProjectFormTab($tabs, $errors);
@endphp

<x-ui.modals.action
    :title="$isEditing ? 'ویرایش پروژه' : 'ایجاد پروژهٔ جدید'"
    wire:model="isFormOpen"
    :action="$isEditing ? 'updateProject' : 'createProject'"
>
    <div class="modal-inner-card !w-full !max-w-none !p-5 md:!p-6" dir="rtl"
         x-data="{ tab: '{{ $defaultTab }}', ready: false }"
         x-effect="if (show && !ready) { setTimeout(() => { if (show) ready = true }, 1000) } else if (!show) { ready = false }"
         x-show="ready">

        <nav class="flex flex-wrap p-1 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30 w-fit mb-6 shadow-sm">
            @foreach($tabs as $t)
                @php
                    $badgeCount = match($t['key']) {
                        'details' => count($projectForm->memberIds),
                        'settings' => count($projectForm->customSchema),
                        default => 0,
                    };
                @endphp
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
                    @if($badgeCount > 0)
                        <span class="min-w-[1.1rem] h-[1.1rem] px-1 rounded-full text-[10px] font-bold flex items-center justify-center bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]" :class="tab === '{{ $t['key'] }}' ? '!bg-[var(--md-sys-color-on-primary)]/20 !text-[var(--md-sys-color-on-primary)]' : ''">{{ $badgeCount }}</span>
                    @endif
                    @if($errors->hasAny($t['errors']))
                        <span class="w-1.5 h-1.5 rounded-full bg-[var(--md-sys-color-error)]"></span>
                    @endif
                </button>
            @endforeach
        </nav>

        @error('form')
        <div class="{{ $errorClass }} mb-4 p-3 rounded-xl bg-[var(--md-sys-color-error-container)]"><span class="material-symbols-rounded text-sm">error</span><span>{{ $message }}</span></div>
        @enderror

        <div x-show="tab === 'details'" class="space-y-5">
            <x-ui.forms.input label="نام پروژه" name="projectForm.name" wire:model="projectForm.name" icon="workspaces"/>

            <label class="text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-2 block">اعضا</label>

            <x-dashboard.member-picker wire:key="project-form-members-candidates" model="projectForm.memberIds" :candidates="$this->memberCandidates" height="h-72"/>
            @error('projectForm.memberIds') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror

            <x-ui.forms.select label="دپارتمان‌های ذی‌نفع" name="projectForm.departments" wire:model="projectForm.departments" icon="corporate_fare" multiple class="min-h-[160px]">
                @foreach($this->availableDepartments as $code => $label)
                    <option value="{{ $code }}">{{ $label }}</option>
                @endforeach
            </x-ui.forms.select>
        </div>

        <div x-show="tab === 'settings'" class="space-y-5">
            <label class="flex items-center gap-3 p-3 rounded-xl cursor-pointer transition-all hover:brightness-95 select-none bg-[var(--md-sys-color-surface-variant)]">
                <x-ui.forms.checkbox name="projectForm.requiresApproval"/>
                <span class="text-sm text-[var(--md-sys-color-on-surface)]">تأیید مدیر پروژه هنگام «انجام‌شده»</span>
            </label>
            @error('projectForm.requiresApproval') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror

            <x-ui.forms.input label="سقف ساعت SLA" name="projectForm.slaHours" wire:model="projectForm.slaHours" icon="schedule" type="number"/>

            <x-ui.forms.date
                label="مهلت پروژه (سقف مهلت وظایف)"
                prefix="projectForm.deadline"
                :startYear="jNow()"
                :endYear="jNow() + 5"
            />
            @foreach(['projectForm.deadlineYear', 'projectForm.deadlineMonth', 'projectForm.deadlineDay'] as $errorKey)
                @error($errorKey) <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
            @endforeach

            <div class="space-y-2">
                <label class="text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-2 block">متای سفارشی وظایف</label>
                <p class="text-[11px] leading-relaxed text-[var(--md-sys-color-on-surface-variant)]">
                    برای وظایف این پروژه فیلدهایی با برچسب دلخواه تعریف کنید؛ مقدار هر فیلد هنگام کار روی وظیفه پر می‌شود.
                </p>

                @foreach($projectForm->customSchema as $i => $row)
                    <div class="flex items-center gap-2">
                        <div class="w-2/5">
                            <x-ui.forms.input label="کلید (a-z، 0-9، _)" name="projectForm.customSchema.{{ $i }}.key" wire:model="projectForm.customSchema.{{ $i }}.key" dir="ltr"/>
                        </div>
                        <div class="flex-1">
                            <x-ui.forms.input label="برچسب" name="projectForm.customSchema.{{ $i }}.label" wire:model="projectForm.customSchema.{{ $i }}.label"/>
                        </div>
                        <button type="button" wire:click="removeSchemaRow({{ $i }})" aria-label="حذف"
                                class="flex items-center justify-center w-9 h-9 rounded-lg text-[var(--md-sys-color-error)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-error)_12%,transparent)] transition-all active:scale-90">
                            <span class="material-symbols-rounded text-[18px]">close</span>
                        </button>
                    </div>
                @endforeach

                <button type="button" wire:click="addSchemaRow"
                        class="flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-xs font-bold transition-all hover:brightness-110 active:scale-95 bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                    <span class="material-symbols-rounded text-base">add</span>
                    افزودن فیلد
                </button>

                @error('projectForm.customSchema') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-2">
                <label class="text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-2 block">تنظیمات دیگر</label>
                <p class="text-[11px] leading-relaxed text-[var(--md-sys-color-on-surface-variant)]">
                    هر کلید و مقدار دلخواهی که در گزینه‌های بالا نمی‌گنجد را اینجا اضافه کنید.
                </p>

                @foreach($projectForm->extraSettings as $i => $row)
                    <div class="flex items-center gap-2">
                        <div class="w-2/5">
                            <x-ui.forms.input label="کلید (a-z، 0-9، _)" name="projectForm.extraSettings.{{ $i }}.key" wire:model="projectForm.extraSettings.{{ $i }}.key" dir="ltr"/>
                        </div>
                        <div class="flex-1">
                            <x-ui.forms.input label="مقدار" name="projectForm.extraSettings.{{ $i }}.value" wire:model="projectForm.extraSettings.{{ $i }}.value"/>
                        </div>
                        <button type="button" wire:click="removeExtraSettingRow({{ $i }})" aria-label="حذف"
                                class="flex items-center justify-center w-9 h-9 rounded-lg text-[var(--md-sys-color-error)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-error)_12%,transparent)] transition-all active:scale-90">
                            <span class="material-symbols-rounded text-[18px]">close</span>
                        </button>
                    </div>
                @endforeach

                <button type="button" wire:click="addExtraSettingRow"
                        class="flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-xs font-bold transition-all hover:brightness-110 active:scale-95 bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                    <span class="material-symbols-rounded text-base">add</span>
                    افزودن تنظیم
                </button>

                @error('projectForm.extraSettings') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>
</x-ui.modals.action>
