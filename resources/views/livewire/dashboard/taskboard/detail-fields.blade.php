<div x-show="tab === 'classification'" class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-5">

    <div class="{{ $sectionHeaderClass }}">
        <span class="material-symbols-rounded text-lg">domain</span>
        ساختار سازمانی و پروژه
    </div>

    <x-ui.forms.select label="واحد سازمانی/دپارتمان" name="form.departmentId" wire:model.live="form.departmentId" :disabled="$isReadOnly">
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

    <x-ui.forms.input label="پروژه" name="form.project" wire:model="form.project" icon="folder" :disabled="$isReadOnly"/>
    <x-ui.forms.input label="طرح" name="form.scheme" wire:model="form.scheme" icon="assignment" :disabled="$isReadOnly"/>

    <div class="md:col-span-2">
        <x-ui.forms.select label="همکاران" name="form.collaborators" wire:model="form.collaborators" multiple class="min-h-[100px]" :disabled="$isReadOnly">
            @foreach($staffMembers as $staff)
                <option value="{{ $staff['id'] }}">{{ $staff['full_name'] }}</option>
            @endforeach
        </x-ui.forms.select>
    </div>
</div>

<div x-show="tab === 'action'" class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-5">

    <div class="{{ $sectionHeaderClass }}">
        <span class="material-symbols-rounded text-lg">flag</span>
        جزئیات اقدام
    </div>

    <x-ui.forms.textarea label="حوزه منشاء اقدام" name="form.actionSourceDomain" wire:model="form.actionSourceDomain" icon="hub" rows="2" :disabled="$isReadOnly"/>
    <x-ui.forms.textarea label="منشاء اقدام" name="form.actionSource" wire:model="form.actionSource" icon="hub" rows="2" :disabled="$isReadOnly"/>

    <x-ui.forms.select label="تعیین تکلیف" name="form.state" wire:model="form.state" :disabled="$isReadOnly">
        <option value="">انتخاب کنید</option>
        @foreach(\App\Filament\Resources\TaskResource\Enums\TaskState::cases() as $stateCase)
            <option value="{{ $stateCase->value }}">{{ $stateCase->getLabel() }}</option>
        @endforeach
    </x-ui.forms.select>

    <div class="{{ $sectionHeaderClass }}">
        <span class="material-symbols-rounded text-lg">attach_file</span>
        فایل‌ها و مستندات
    </div>

    <div class="md:col-span-2">
        <label class="{{ $labelClass }}">پیوست‌ها</label>

        @if(!empty($form->existingAttachments))
            <ul class="space-y-1.5 mb-2">
                @foreach($form->existingAttachments as $index => $attachment)
                    <li class="flex items-center justify-between gap-2 px-3 py-2 rounded-lg bg-[var(--md-sys-color-surface-container)] text-xs">
                        <a href="{{ asset('storage/' . $attachment['path']) }}" target="_blank"
                           class="flex items-center gap-1.5 text-[var(--md-sys-color-primary)] truncate">
                            <span class="material-symbols-rounded text-sm">description</span>
                            {{ $attachment['name'] ?? basename($attachment['path']) }}
                        </a>
                        @unless($isReadOnly)
                            <button type="button" wire:click="removeExistingAttachment({{ $index }})"
                                    class="text-[var(--md-sys-color-error)] flex items-center justify-center">
                                <span class="material-symbols-rounded text-sm">close</span>
                            </button>
                        @endunless
                    </li>
                @endforeach
            </ul>
        @elseif($isReadOnly)
            <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">پیوستی ثبت نشده است.</p>
        @endif

        @unless($isReadOnly)
            <input type="file" multiple wire:model="form.attachments"
                   class="w-full text-xs rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface)] p-2.5 file:ml-2 file:px-3 file:py-1.5 file:rounded-lg file:border-0 file:bg-[var(--md-sys-color-primary-container)] file:text-[var(--md-sys-color-on-primary-container)] file:text-xs file:font-bold"
            >

            <div wire:loading wire:target="form.attachments" class="text-xs text-[var(--md-sys-color-primary)] mt-2">
                در حال آپلود...
            </div>

            @if(!empty($form->attachments))
                <ul class="space-y-1.5 mt-2">
                    @foreach($form->attachments as $index => $file)
                        <li class="flex items-center justify-between gap-2 px-3 py-2 rounded-lg bg-[var(--md-sys-color-surface-container)] text-xs">
                            <span class="flex items-center gap-1.5 text-[var(--md-sys-color-on-surface)] truncate">
                                <span class="material-symbols-rounded text-sm">upload_file</span>
                                {{ $file->getClientOriginalName() }}
                            </span>
                            <button type="button" wire:click="removeAttachment({{ $index }})"
                                    class="text-[var(--md-sys-color-error)] flex items-center justify-center">
                                <span class="material-symbols-rounded text-sm">close</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif

            @foreach(['form.attachments', 'attachments', 'attachments.*'] as $errorKey)
                @error($errorKey)
                <div class="{{ $errorClass }}"><span class="material-symbols-rounded text-sm">error</span><span>{{ $message }}</span></div>
                @enderror
            @endforeach
        @endunless
    </div>
</div>
