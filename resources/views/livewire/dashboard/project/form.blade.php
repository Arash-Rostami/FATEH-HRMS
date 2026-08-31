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
                        'departments' => count($projectForm->departments),
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

        <div x-show="tab === 'details'" x-data="{ memberQuery: '' }" class="space-y-5">
            <x-ui.forms.input label="نام پروژه" name="projectForm.name" wire:model="projectForm.name" icon="workspaces"/>

            <label class="text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-2 block">اعضا</label>

            @if(count($this->memberCandidates) > 0)
                <div class="relative mb-2">
                    <span class="material-symbols-rounded absolute right-3 top-1/2 -translate-y-1/2 text-[16px] text-[var(--md-sys-color-on-surface-variant)] pointer-events-none">search</span>
                    <input type="text" x-model="memberQuery" placeholder="جستجوی کاربر..."
                           class="md3-input w-full rounded-xl text-sm outline-none focus:ring-2 pr-9 h-10 bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] border border-[var(--md-sys-color-outline-variant)]/50">
                </div>

                <div class="max-h-72 overflow-y-auto custom-scrollbar rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 divide-y divide-[var(--md-sys-color-outline-variant)]/20">
                    @foreach($this->memberCandidates as $candidate)
                        <label x-show="memberQuery === '' || @js($candidate['name'] ?? '').toLowerCase().includes(memberQuery.toLowerCase())"
                               class="flex items-center gap-3 px-4 py-3 hover:bg-[var(--md-sys-color-surface-container)]/60 cursor-pointer transition-colors">
                            <input type="checkbox" value="{{ $candidate['id'] }}" wire:model="projectForm.memberIds"
                                   class="w-4 h-4 rounded text-[var(--md-sys-color-primary)] border-[var(--md-sys-color-outline-variant)] focus:ring-[var(--md-sys-color-primary)]">
                            <span class="text-sm font-medium text-[var(--md-sys-color-on-surface)]">{{ $candidate['name'] }}</span>
                        </label>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-[var(--md-sys-color-on-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/40 rounded-2xl">
                    <span class="material-symbols-rounded text-4xl mb-2 block opacity-40">group</span>
                    <p class="text-sm">کاربر فعال دیگری برای افزودن وجود ندارد.</p>
                </div>
            @endif
            @error('projectForm.memberIds') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
        </div>

        <div x-show="tab === 'departments'" class="space-y-5">
            <div class="space-y-2">
                @foreach(\App\Models\Department::getCachedOptions() as $code => $label)
                    <label class="flex items-center gap-3 p-3 rounded-xl cursor-pointer transition-all hover:brightness-95 select-none bg-[var(--md-sys-color-surface-variant)]">
                        <input type="checkbox" wire:model="projectForm.departments" value="{{ $code }}" class="w-4 h-4 rounded-lg accent-[var(--md-sys-color-primary)]">
                        <span class="text-sm text-[var(--md-sys-color-on-surface)]">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            @error('projectForm.departments') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
        </div>

        <div x-show="tab === 'settings'" class="space-y-5">
            <label class="flex items-center gap-3 p-3 rounded-xl cursor-pointer transition-all hover:brightness-95 select-none bg-[var(--md-sys-color-surface-variant)]">
                <input type="checkbox" wire:model="projectForm.requiresApproval" class="w-4 h-4 rounded-lg accent-[var(--md-sys-color-primary)]">
                <span class="text-sm text-[var(--md-sys-color-on-surface)]">تأیید مدیر پروژه هنگام «انجام‌شده»</span>
            </label>
            @error('projectForm.requiresApproval') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror

            <x-ui.forms.input label="سقف ساعت SLA" name="projectForm.slaHours" wire:model="projectForm.slaHours" icon="schedule" type="number"/>
            @error('projectForm.slaHours') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror

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
                        <input type="text" wire:model="projectForm.customSchema.{{ $i }}.key" placeholder="کلید (a-z، 0-9، _)" dir="ltr"
                               class="md3-input w-2/5 rounded-xl text-xs outline-none h-10 bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] border border-[var(--md-sys-color-outline-variant)]/50">
                        <input type="text" wire:model="projectForm.customSchema.{{ $i }}.label" placeholder="برچسب"
                               class="md3-input flex-1 rounded-xl text-xs outline-none h-10 bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] border border-[var(--md-sys-color-outline-variant)]/50">
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

                @foreach(['projectForm.customSchema', 'projectForm.customSchema.*.key', 'projectForm.customSchema.*.label'] as $errorKey)
                    @error($errorKey) <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                @endforeach
            </div>

            <div class="space-y-2">
                <label class="text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-2 block">تنظیمات دیگر</label>
                <p class="text-[11px] leading-relaxed text-[var(--md-sys-color-on-surface-variant)]">
                    هر کلید و مقدار دلخواهی که در گزینه‌های بالا نمی‌گنجد را اینجا اضافه کنید.
                </p>

                @foreach($projectForm->extraSettings as $i => $row)
                    <div class="flex items-center gap-2">
                        <input type="text" wire:model="projectForm.extraSettings.{{ $i }}.key" placeholder="کلید (a-z، 0-9، _)" dir="ltr"
                               class="md3-input w-2/5 rounded-xl text-xs outline-none h-10 bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] border border-[var(--md-sys-color-outline-variant)]/50">
                        <input type="text" wire:model="projectForm.extraSettings.{{ $i }}.value" placeholder="مقدار"
                               class="md3-input flex-1 rounded-xl text-xs outline-none h-10 bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] border border-[var(--md-sys-color-outline-variant)]/50">
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

                @foreach(['projectForm.extraSettings', 'projectForm.extraSettings.*.key', 'projectForm.extraSettings.*.value'] as $errorKey)
                    @error($errorKey) <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                @endforeach
            </div>
        </div>
    </div>
</x-ui.modals.action>
