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
            <x-ui.forms.input label="نام پروژه" name="projectForm.name" wire:model.live.debounce.600ms="projectForm.name" icon="workspaces" mark/>
            @if($this->hasDuplicateName)
                <p wire:key="project-form-duplicate-name" class="text-[11px] leading-relaxed text-[var(--md-sys-color-on-surface-variant)]">پروژه‌ای با این نام دارید؛ می‌توانید ادامه دهید.</p>
            @endif

            <label class="text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-2 block">اعضا</label>

            <x-dashboard.member-picker wire:key="project-form-members-candidates" model="projectForm.memberIds" :candidates="$this->memberCandidates" height="h-72" bulk :dept-options="$this->deptOptions"/>
            @error('projectForm.memberIds') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
            @unless($isEditing)
                <p wire:key="project-form-members-hint" class="text-[11px] leading-relaxed text-[var(--md-sys-color-on-surface-variant)]">شما مدیر پروژه هستید و خودکار عضو گفتگوی پروژه می‌شوید.</p>
            @endunless

            @if($isEditing)
                <div wire:key="project-form-owner" class="space-y-2">
                    <label class="text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-2 block">مالک پروژه</label>
                    <x-dashboard.member-picker model="projectForm.ownerId" mode="single" :candidates="$this->ownerCandidates" :search="false" height="h-40"/>
                    @error('projectForm.ownerId') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    <p class="text-[11px] leading-relaxed text-[var(--md-sys-color-on-surface-variant)]">با ذخیره، مدیریت پروژه به این فرد منتقل می‌شود و شما عضو پروژه می‌مانید. مالک تازه باید از اعضای فعال پروژه باشد.</p>
                </div>
            @endif

            <x-ui.forms.select label="واحدهای دارای دسترسی" name="projectForm.departments" wire:model="projectForm.departments" icon="corporate_fare" multiple class="min-h-[160px]">
                @foreach($this->availableDepartments as $code => $label)
                    <option value="{{ $code }}">{{ $label }}</option>
                @endforeach
            </x-ui.forms.select>
            @unless($isEditing)
                <p wire:key="project-form-departments-hint" class="text-[11px] leading-relaxed text-[var(--md-sys-color-on-surface-variant)]">اعضای فعال واحدهای انتخاب‌شده به پروژه و گفتگوی آن دسترسی می‌گیرند؛ لازم نیست تک‌تک آن‌ها را انتخاب کنید.</p>
            @endunless
            <p class="text-[11px] leading-relaxed text-[var(--md-sys-color-on-surface-variant)]">با حذف نام یک نفر، اگر هنوز از راه واحدش عضو پروژه باشد، دسترسی او باقی می‌ماند. اگر دسترسی‌اش کاملاً برداشته شود، مسئولیت‌های باز او در پروژه نیز آزاد می‌شوند.</p>
        </div>

        <div x-show="tab === 'settings'" class="space-y-5">
            <label class="flex items-center gap-3 p-3 rounded-xl cursor-pointer transition-all hover:brightness-95 select-none bg-[var(--md-sys-color-surface-variant)]">
                <x-ui.forms.checkbox name="projectForm.requiresApproval"/>
                <span class="text-sm text-[var(--md-sys-color-on-surface)]">انجام کار به تأیید مالک پروژه نیاز دارد</span>
            </label>
            @error('projectForm.requiresApproval') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
            <p class="text-[11px] leading-relaxed text-[var(--md-sys-color-on-surface-variant)]">{{ __('resources/project/strings.hints.requires_approval') }}</p>

            <x-ui.forms.input label="مهلت پیش‌فرض وظیفه (ساعت)" name="projectForm.slaHours" wire:model="projectForm.slaHours" icon="schedule" type="number"/>
            <p class="text-[11px] leading-relaxed text-[var(--md-sys-color-on-surface-variant)]">{{ __('resources/project/strings.hints.sla') }}</p>

            <x-ui.forms.input label="بایگانی خودکار پس از (روز)" name="projectForm.autoArchiveDays" wire:model="projectForm.autoArchiveDays" icon="inventory_2" type="number"/>
            <p class="text-[11px] leading-relaxed text-[var(--md-sys-color-on-surface-variant)]">{{ __('resources/project/strings.hints.auto_archive_days') }} بایگانی، وظیفه را از برد کنار می‌گذارد و آن را حذف نمی‌کند.</p>

            <div class="space-y-2" x-data="{ items: @entangle('projectForm.labels'), draft: '', add() { const v = this.draft.trim(); this.draft = ''; if (v && !this.items.includes(v) && this.items.length < 30) this.items = [...this.items, v]; } }">
                <label class="text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-2 block">برچسب‌های مجاز وظایف</label>
                <input type="text" x-model="draft" @keydown.enter.prevent="add()" @blur="add()" maxlength="30"
                       placeholder="برچسب را بنویسید و Enter بزنید…"
                       class="w-full rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container-lowest)] px-3 py-2 text-sm text-[var(--md-sys-color-on-surface)] placeholder-[var(--md-sys-color-outline)] outline-none focus:border-[var(--md-sys-color-primary)]"/>
                <div class="flex flex-wrap gap-2" x-show="items.length">
                    <template x-for="(item, i) in items" :key="item">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)] border border-[var(--md-sys-color-outline-variant)]/40">
                            <span x-text="item"></span>
                            <button type="button" @click="items = items.filter((_, n) => n !== i)" aria-label="حذف"
                                    class="w-4 h-4 flex items-center justify-center rounded-md hover:bg-[var(--md-sys-color-error-container)] hover:text-[var(--md-sys-color-error)] transition-colors">
                                <span class="material-symbols-rounded text-[13px]">close</span>
                            </button>
                        </span>
                    </template>
                </div>
                <p class="text-[11px] leading-relaxed text-[var(--md-sys-color-on-surface-variant)]">اگر برچسبی اضافه کنید، برای برچسب‌های تازهٔ وظایف فقط همین فهرست قابل انتخاب است؛ برچسب‌های قبلی باقی می‌مانند. اگر خالی بگذارید، محدودیتی برای انتخاب برچسب نیست.</p>
                @error('projectForm.labels') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
            </div>

            <x-ui.forms.date
                label="مهلت نهایی پروژه (اختیاری)"
                prefix="projectForm.deadline"
                :startYear="jNow()"
                :endYear="jNow() + 5"
            />
            <p class="text-[11px] leading-relaxed text-[var(--md-sys-color-on-surface-variant)]">{{ __('resources/project/strings.hints.deadline') }}</p>
            @foreach(['projectForm.deadlineYear', 'projectForm.deadlineMonth', 'projectForm.deadlineDay'] as $errorKey)
                @error($errorKey) <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
            @endforeach

            <div class="space-y-2">
                <label class="text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-2 block">اطلاعات دلخواه وظایف</label>
                <p class="text-[11px] leading-relaxed text-[var(--md-sys-color-on-surface-variant)]">
                    {{ __('resources/project/strings.hints.custom_schema') }} اگر اطلاعات اضافه‌ای لازم ندارید، این قسمت را خالی بگذارید.
                </p>

                @foreach($projectForm->customSchema as $i => $row)
                    <div class="flex items-center gap-2">
                        <div class="w-2/5">
                            <x-ui.forms.input label="نام انگلیسی (a-z، 0-9، _)" name="projectForm.customSchema.{{ $i }}.key" wire:model="projectForm.customSchema.{{ $i }}.key" dir="ltr"/>
                        </div>
                        <div class="flex-1">
                            <x-ui.forms.input label="عنوان نمایشی" name="projectForm.customSchema.{{ $i }}.label" wire:model="projectForm.customSchema.{{ $i }}.label"/>
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
                    این بخش اختیاری است. برای تنظیمات آماده، نام دقیق را بنویسید: escalation_days یعنی چند روز پس از تأخیر به مسئول پیگیری خبر داده شود؛ due_soon_hours یعنی چند ساعت پیش از مهلت هشدار داده شود. برای این دو، مقدار را به صورت عدد وارد کنید. نام دلخواه، به‌تنهایی قابلیت تازه‌ای در برنامه ایجاد نمی‌کند.
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
