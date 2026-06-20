<div class="space-y-4 suggestion-form-enter">

    <div class="rounded-2xl overflow-hidden shadow-sm relative bg-[var(--md-sys-color-surface)]">
        <div class="absolute inset-0 pointer-events-none bg-[var(--md-sys-color-primary-container)]"></div>
        <div class="absolute -left-8 -top-8 w-48 h-48 rounded-xl pointer-events-none bg-[var(--md-sys-color-primary-container)]"></div>

        <div class="relative p-5 md:p-6 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center shadow bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]">
                <span class="material-symbols-rounded text-2xl">edit_note</span>
            </div>

            <div class="flex-1">
                <h2 class="text-xl font-bold bg-[var(--md-sys-color-primary-container)]">
                    ثبت پیشنهاد جدید
                </h2>
                <p class="text-[11px] mt-0.5" style="color: color-mix(in srgb, var(--md-sys-color-on-primary-container) 65%, transparent)">
                    فرم ثبت پیشنهاد بهبود سازمانی
                </p>
            </div>

            <div class="scale-70">
                <x-ui.buttons.form wire:click="$set('showWorkflowModal', true)" title="راهنما">
                    <span class="material-symbols-rounded text-base relative top-1">schema</span>
                    <span class="relative bottom-1">راهنما</span>
                </x-ui.buttons.form>

                <x-ui.buttons.form wire:click="$set('panel', 'empty')" class="pb-0" title="بستن فرم">
                    <span class="material-symbols-rounded text-base relative top-1">close</span>
                </x-ui.buttons.form>
            </div>
        </div>

        <x-ui.modals.base wire:model="showWorkflowModal" title="فلوچارت روند بررسی پیشنهاد" contentClass="max-w-5xl w-full">
            <div class="space-y-4">
                <img src="{{ asset('build/assets/img/suggestion.png') }}" alt="Flowchart" class="w-full h-auto rounded-xl shadow-sm">
            </div>
        </x-ui.modals.base>
    </div>

    <div class="bg-[var(--md-sys-color-surface)]">
        <x-ui.forms.card>
            <x-ui.title icon="description" title="اطلاعات پایه"/>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="md:col-span-2 space-y-4">
                    <x-ui.forms.input label="عنوان پیشنهاد" name="form.title" icon="label" wire:model="form.title"/>
                    <x-ui.forms.textarea label="شرح پیشنهاد، استدلال‌ها و محاسبات" name="form.descriptionSelf" icon="edit" :rows="5" wire:model="form.descriptionSelf"/>
                </div>

                <div>
                    <x-ui.forms.select class="text-xs pb-3 h-[14rem]" label="واحدهای ذی‌نفع" name="form.departments" icon="corporate_fare" multiple wire:model.live="form.departments">
                        @foreach($this->availableDepartments as $code => $name)
                            <option value="{{ $code }}">{{ $name }}</option>
                        @endforeach
                    </x-ui.forms.select>

                    <p class="mt-1.5 text-[11px]" style="color: var(--md-sys-color-on-surface-variant)">
                        Ctrl + کلیک برای انتخاب چندتایی
                    </p>
                </div>
            </div>
        </x-ui.forms.card>
    </div>

    <div class="bg-[var(--md-sys-color-surface)]">
        <x-ui.forms.card>
            <x-ui.title icon="rule" title="دسته‌بندی و اهداف"/>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div>
                    <p class="text-sm font-bold mb-3 flex items-center gap-2 text-[var(--md-sys-color-on-surface)]">
                        <span class="material-symbols-rounded text-base text-[var(--md-sys-color-secondary)]">lightbulb</span>
                        این پیشنهاد در راستای کدام قاعده سازمان است؟
                    </p>

                    <div class="space-y-2">
                        @foreach($rules as $value => $label)
                            <label class="flex items-center gap-3 p-3 rounded-xl cursor-pointer transition-all hover:brightness-95 select-none bg-[var(--md-sys-color-surface-variant)]">
                                <input type="checkbox" wire:model="form.rule" value="{{ $value }}" class="w-4 h-4 rounded-lg accent-[var(--md-sys-color-primary)]">
                                <span class="text-sm text-[var(--md-sys-color-on-surface)]">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    @error('form.rule')
                    <p class="mt-2 text-xs text-[var(--md-sys-color-error)]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <p class="text-sm font-bold mb-3 flex items-center gap-2 text-[var(--md-sys-color-on-surface)]">
                        <span class="material-symbols-rounded text-base text-[var(--md-sys-color-secondary)]">rocket_launch</span>
                        این پیشنهاد موجب کدام بهبودها خواهد شد؟
                    </p>

                    <div class="space-y-2">
                        @foreach($purposes as $value => $label)
                            <label class="flex items-center gap-3 p-3 rounded-xl cursor-pointer transition-all hover:brightness-95 select-none bg-[var(--md-sys-color-surface-variant)]">
                                <input type="checkbox" wire:model="form.purpose" value="{{ $value }}" class="w-4 h-4 rounded-lg accent-[var(--md-sys-color-primary)]">
                                <span class="text-sm text-[var(--md-sys-color-on-surface)]">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    @error('form.purpose')
                    <p class="mt-2 text-xs text-[var(--md-sys-color-error)]">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </x-ui.forms.card>
    </div>

    <div class="bg-[var(--md-sys-color-surface)]">
        <x-ui.forms.card>
            <x-ui.title icon="attach_file" title="فایل ضمیمه"/>

            <div>
                @if($this->form->attachment)
                    <div class="flex items-center justify-between p-4 rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-variant)] shadow-sm">
                        <div class="flex items-center gap-4 overflow-hidden">
                            @if(in_array(strtolower($this->form->attachment->extension()), ['png', 'jpg', 'jpeg', 'webp']))
                                <a href="{{ $this->form->attachment->temporaryUrl() }}" target="_blank" class="shrink-0 w-16 h-16 rounded-lg border border-[var(--md-sys-color-outline-variant)] overflow-hidden shadow-sm hover:brightness-90 transition-all cursor-zoom-in">
                                    <img src="{{ $this->form->attachment->temporaryUrl() }}" class="w-full h-full object-cover" alt="Preview">
                                </a>
                            @else
                                <div class="shrink-0 w-16 h-16 rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center shadow-sm">
                                    <span class="material-symbols-rounded text-3xl">description</span>
                                </div>
                            @endif

                            <div class="flex flex-col min-w-0">
                                <span class="text-sm font-bold text-[var(--md-sys-color-on-surface)] truncate" dir="ltr">
                                    {{ $this->form->attachment->getClientOriginalName() }}
                                </span>
                                <span class="text-xs text-[var(--md-sys-color-on-surface-variant)] mt-1">
                                    {{ round($this->form->attachment->getSize() / 1024, 2) }} KB
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            @if(in_array(strtolower($this->form->attachment->extension()), ['png', 'jpg', 'jpeg', 'webp']))
                                <a href="{{ $this->form->attachment->temporaryUrl() }}" target="_blank" class="w-10 h-10 rounded-lg bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] border border-[var(--md-sys-color-outline-variant)] flex items-center justify-center transition-colors shadow-sm" title="مشاهده فایل">
                                    <span class="material-symbols-rounded text-lg">visibility</span>
                                </a>
                            @endif
                            <button type="button" wire:click="$set('form.attachment', null)" x-on:click="uploading = false; progress = 0" class="w-10 h-10 rounded-lg bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-error)] hover:bg-[var(--md-sys-color-error-container)] border border-[var(--md-sys-color-outline-variant)] flex items-center justify-center transition-colors shadow-sm" title="حذف فایل">
                                <span class="material-symbols-rounded text-lg">delete</span>
                            </button>
                        </div>
                    </div>
                @else
                    <label for="suggestion-attachment"
                           class="flex flex-col items-center justify-center h-32 rounded-xl border-2 border-dashed cursor-pointer transition-all"
                           :class="uploading ? 'opacity-60 cursor-wait' : 'hover:brightness-95'"
                           style="border-color: color-mix(in srgb, var(--md-sys-color-outline-variant) 65%, transparent); background: var(--md-sys-color-surface-variant)">

                        <div x-show="!uploading" class="text-center pointer-events-none flex flex-col items-center">
                            <span class="material-symbols-rounded text-4xl block mb-2 text-[var(--md-sys-color-on-surface-variant)]">upload_file</span>
                            <p class="text-sm font-bold text-[var(--md-sys-color-on-surface-variant)]">انتخاب فایل</p>
                            <p class="text-[11px] mt-1" style="color: color-mix(in srgb, var(--md-sys-color-on-surface-variant) 65%, transparent)">PDF, PNG, JPG — حداکثر ۱۰ مگابایت</p>
                        </div>

                        <div x-show="uploading" class="w-1/2 flex flex-col items-center gap-2">
                            <span class="text-sm font-bold text-[var(--md-sys-color-primary)]">در حال آپلود...</span>
                            <progress :value="progress" max="100" class="w-full rounded-xl h-1.5 overflow-hidden accent-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-outline-variant)]"></progress>
                        </div>

                        <input id="suggestion-attachment" type="file" wire:model="form.attachment" class="hidden" accept=".pdf,.png,.jpg,.jpeg,.webp">
                    </label>
                @endif
            </div>

            @error('form.attachment')
            <p class="mt-2 text-xs text-[var(--md-sys-color-error)]">{{ $message }}</p>
            @enderror
        </x-ui.forms.card>
    </div>

    <div class="bg-[var(--md-sys-color-surface)]">
        <x-ui.forms.card class="!p-0">

            <x-ui.buttons.toggle wire:click="$toggle('form.selfFill')" :checked="$this->form->selfFill" icon="group" title="بازخورد سایر اعضا" description="با فعال کردن این گزینه، می‌توانید نظرات ذی‌نفعان را خودتان ثبت کنید." name="form.selfFill" bordered="true"/>

            @if($this->form->selfFill)
                <div style="border-top: 1px solid color-mix(in srgb, var(--md-sys-color-outline-variant) 40%, transparent)">

                    <div class="p-5">
                        <p class="text-sm font-bold mb-3 flex items-center gap-2 text-[var(--md-sys-color-on-surface)]">
                            <span class="material-symbols-rounded text-base text-[var(--md-sys-color-primary)]">group</span>
                            نظر هم‌تیمی‌ها/مدیر واحد
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                @foreach(collect($reviewFeedback)->except(['incomplete', 'unknown']) as $val => $feedbackLabel)
                                    <label class="flex items-center gap-3 p-3 rounded-xl cursor-pointer select-none hover:brightness-95 transition-all bg-[var(--md-sys-color-surface-variant)]">
                                        <input type="radio" wire:model="form.feedbackTeam" value="{{ $val }}" class="w-4 h-4 rounded-lg accent-[var(--md-sys-color-primary)] ">
                                        <span class="text-sm text-[var(--md-sys-color-on-surface)]">{{ $feedbackLabel }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <x-ui.forms.textarea label="توضیحات تیم" name="descriptionTeam" :rows="4" wire:model="form.descriptionTeam"/>
                        </div>
                    </div>

                    @foreach($this->form->departments as $dept)
                        <div class="px-5 pb-5 pt-4" style="border-top: 1px solid color-mix(in srgb, var(--md-sys-color-outline-variant) 30%, transparent)">
                            <p class="text-sm font-bold mb-3 flex items-center gap-2 text-[var(--md-sys-color-on-surface)]">
                                <span class="material-symbols-rounded text-base text-[var(--md-sys-color-secondary)]">corporate_fare</span>
                                <span class="px-2.5 py-0.5 rounded-lg text-xs font-bold text-[var(--md-sys-color-on-secondary-container)] bg-[var(--md-sys-color-secondary-container)]">
                                    {{ $this->departmentNames[$dept] ?? $dept }}
                                </span>
                            </p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    @foreach(collect($reviewFeedback)->except(['incomplete', 'unknown']) as $val => $feedbackLabel)
                                        <label class="flex items-center gap-3 p-3 rounded-xl cursor-pointer select-none hover:brightness-95 transition-all bg-[var(--md-sys-color-surface-variant)]">
                                            <input type="radio" wire:model="form.feedback.{{ $dept }}" value="{{ $val }}" class="accent-[var(--md-sys-color-primary)]">
                                            <span class="text-sm text-[var(--md-sys-color-on-surface)]">{{ $feedbackLabel }}</span>
                                        </label>
                                    @endforeach
                                    @error("form.feedback.{$dept}")
                                    <p class="text-xs text-[var(--md-sys-color-error)]">{{ $message }}</p>
                                    @enderror
                                </div>
                                <x-ui.forms.textarea label="توضیحات" name="descriptionDepts.{{ $dept }}" :rows="4" wire:model="form.descriptionDepts.{{ $dept }}"/>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.forms.card>
    </div>

    <div class="flex justify-center gap-3 pb-8">
        <x-ui.buttons.form class="w-40 justify-center" icon="send" loading="submit" wire:click="submit" wire:loading.attr="disabled">
            ارسال
        </x-ui.buttons.form>

        <x-ui.buttons.form class="w-40 justify-center !bg-[var(--md-sys-color-error)] !text-[var(--md-sys-color-on-primary)]" variant="ghost" wire:click="$set('panel', 'empty')">
            انصراف
        </x-ui.buttons.form>
    </div>

</div>
