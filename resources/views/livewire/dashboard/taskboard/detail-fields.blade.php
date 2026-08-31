<div x-show="formTab === 'info'" class="grid grid-cols-1 gap-4 md:grid-cols-2 md:gap-5">
    <div class="{{ $sectionHeaderClass }}">
        <span class="text-lg material-symbols-rounded">domain</span>
        ساختار سازمانی و پروژه
    </div>

    <x-ui.forms.select label="دپارتمان" name="form.departmentId" column-hint="department_id" wire:model.live="form.departmentId" :disabled="$isReadOnly">
        <option value="">انتخاب کنید</option>
        @foreach($departmentOptions as $code => $name)
            <option value="{{ $code }}">{{ $name }}</option>
        @endforeach
    </x-ui.forms.select>

    <x-ui.forms.select label="واحد (زیرمجموعه)" name="form.unit" wire:model="form.unit" :disabled="$isReadOnly">
        <option value="">انتخاب کنید</option>
        @foreach($availableUnits as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </x-ui.forms.select>

    <x-ui.forms.select label="بخش (زیرمجموعه)" name="form.section" wire:model="form.section" :disabled="$isReadOnly">
        <option value="">انتخاب کنید</option>
        @foreach($availableSections as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </x-ui.forms.select>

    <x-ui.forms.select label="جوابگو" name="form.responsibleUserId" wire:model="form.responsibleUserId" :disabled="$isReadOnly">
        <option value="">انتخاب کنید</option>
        @foreach($staffMembers as $staff)
            <option value="{{ $staff['id'] }}">{{ $staff['full_name'] }}</option>
        @endforeach
    </x-ui.forms.select>

    <div class="w-full space-y-1.5">
        <x-ui.forms.input
            label="برچسب پروژه (متن آزاد، بدون اتصال)"
            name="form.project"
            column-hint="project"
            wire:model="form.project"
            icon="folder"
            placeholder="نام سفارشی یا برچسب یکتا را وارد کنید (اختیاری)…"
            :disabled="$isReadOnly"
        />

        <div class="flex items-start gap-1.5 px-1 text-[11px] leading-relaxed text-right text-[var(--md-sys-color-on-surface-variant)]">
            <span class="shrink-0 mt-0.5 text-[15px] text-[var(--md-sys-color-primary)] material-symbols-rounded">info</span>
            <span>
                شناسه سیستمی به‌صورت خودکار تخصیص می‌یابد؛ با این حال می‌توانید برای دسته‌بندی بهتر، ثبت عنوان جدید یا یکپارچه‌سازی وظایف آزاد قبلی، یک <strong>نام یا برچسب یکتا</strong> وارد کنید.
            </span>
        </div>
    </div>

    <x-ui.forms.input label="طرح" name="form.scheme" wire:model="form.scheme" icon="assignment" :disabled="$isReadOnly" />

    <div class="{{ $sectionHeaderClass }}">
        <span class="text-lg material-symbols-rounded">hub</span>
        منشاء اقدام
    </div>

    <x-ui.forms.textarea label="حوزه منشاء اقدام" name="form.actionSourceDomain" wire:model="form.actionSourceDomain" icon="hub" rows="2" :disabled="$isReadOnly" />

    <x-ui.forms.textarea label="منشاء اقدام" name="form.actionSource" wire:model="form.actionSource" icon="hub" rows="2" :disabled="$isReadOnly" />

    <div class="md:col-span-2">
        <div class="rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] transition-all overflow-hidden">
            <button type="button"
                    @click="foldLabels = !foldLabels"
                    class="w-full flex items-center justify-between p-3.5 sm:p-4 text-right select-none focus:outline-none bg-[var(--md-sys-color-surface-container)]/30 hover:bg-[var(--md-sys-color-surface-container)]/70 transition-colors">
                <div class="flex items-center gap-2.5">
                    <span class="material-symbols-rounded text-lg text-[var(--md-sys-color-primary)]">label</span>
                    <span class="text-xs sm:text-sm font-bold text-[var(--md-sys-color-on-surface)]">برچسب‌ها</span>
                    <span x-show="labelsCount > 0" x-text="labelsCount"
                          class="px-2 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] text-[11px] font-semibold tabular-nums"></span>
                </div>
                <span
                    class="material-symbols-rounded text-lg text-[var(--md-sys-color-outline)] transition-transform duration-200"
                    :class="{ 'rotate-180': foldLabels }">expand_more</span>
            </button>

            <div x-show="foldLabels" x-collapse
                 class="p-3.5 sm:p-4 space-y-3.5 border-t border-[var(--md-sys-color-outline-variant)]/20">
                @unless($isReadOnly)
                    <div
                        class="relative flex items-center rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container-lowest)] p-1 transition-all focus-within:border-[var(--md-sys-color-primary)] focus-within:ring-2 focus-within:ring-[var(--md-sys-color-primary)]/20">
                        <input type="text"
                               x-model="newLabel"
                               list="task-label-suggestions"
                               @keydown.enter.prevent="addLabelItem()"
                               placeholder="افزودن برچسب جدید و فشردن Enter…"
                               class="w-full bg-transparent px-3 py-1.5 text-xs sm:text-sm text-[var(--md-sys-color-on-surface)] placeholder-[var(--md-sys-color-outline)] outline-none border-none focus:ring-0"/>
                        <datalist id="task-label-suggestions">
                            @foreach($this->labelOptions as $label)
                                <option value="{{ $label }}"></option>
                            @endforeach
                        </datalist>
                        <button type="button"
                                @click="addLabelItem()"
                                :disabled="!newLabel.trim()"
                                class="shrink-0 flex items-center justify-center w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] transition-all hover:opacity-90 active:scale-95 disabled:opacity-40 disabled:pointer-events-none"
                                aria-label="افزودن برچسب">
                            <span class="material-symbols-rounded text-base sm:text-lg">add</span>
                        </button>
                    </div>
                @endunless

                <div wire:ignore class="flex flex-wrap gap-2">
                    <template x-for="(label, index) in labels" :key="'lbl-' + index + '-' + label">
                        <span
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)] border border-[var(--md-sys-color-outline-variant)]/40 transition-all">
                            <span x-text="label"></span>
                            @unless($isReadOnly)
                                <button type="button"
                                        @click="removeLabel(index)"
                                        class="w-4 h-4 rounded-full flex items-center justify-center hover:bg-[var(--md-sys-color-error-container)] hover:text-[var(--md-sys-color-error)] transition-colors"
                                        aria-label="حذف برچسب">
                                    <span class="material-symbols-rounded text-[13px]">close</span>
                                </button>
                            @endunless
                        </span>
                    </template>
                    <div class="w-full py-4 text-center" x-show="labels.length === 0">
                        <p class="text-xs text-[var(--md-sys-color-outline)] font-medium">هیچ برچسبی اختصاص داده نشده است.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
