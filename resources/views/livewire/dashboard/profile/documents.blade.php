<div class="space-y-5 animate-[fade-in_0.4s_ease-out]" dir="rtl">

    <div class="relative overflow-hidden rounded-2xl bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] border border-[var(--md-sys-color-tertiary)]/20 p-5">
        <div class="absolute -right-4 -top-4 opacity-[0.08] pointer-events-none">
            <span class="material-symbols-rounded text-[100px]">cloud_upload</span>
        </div>

        <div class="relative z-10 flex gap-4 items-start md:items-center">
            <div class="w-10 h-10 flex-shrink-0 mt-1 rounded-xl bg-[var(--md-sys-color-tertiary)] text-[var(--md-sys-color-on-tertiary)] flex items-center justify-center shadow-inner">
                <span class="material-symbols-rounded text-[22px]">info</span>
            </div>

            <div class="flex-1">
                <h3 class="text-sm font-bold tracking-tight mb-2">راهنمای بارگذاری مدارک و الزامات پرونده</h3>
                <p class="text-xs opacity-90 leading-relaxed mb-4">
                    کاربر گرامی، تصاویر اسکن‌شده باید با کیفیت مطلوب و حجم کمتر از ۲ مگابایت باشند. توجه داشته باشید که پس از تایید نهایی، امکان ویرایش مستندات وجود نخواهد داشت. رعایت موارد زیر جهت تکمیل پرونده الزامی است:
                </p>

                <ul class="text-xs opacity-90 leading-relaxed space-y-3">
                    <li class="flex items-start gap-2">
                        <span class="material-symbols-rounded text-[16px] opacity-80 shrink-0">document_scanner</span>
                        <span>تصاویر مدارک باید دارای کادربندی استاندارد باشند؛ لطفاً از بارگذاری تصاویر زاویه‌دار، فاقد نور مناسب یا ناخوانا اکیداً خودداری فرمایید.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="material-symbols-rounded text-[16px] opacity-80 shrink-0">group</span>
                        <span>علاوه بر مدارک هویتی پایه، بارگذاری تصویر <strong>تمامی صفحات</strong> شناسنامه همسر و فرزندان نیز الزامی است.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="material-symbols-rounded text-[16px] opacity-80 shrink-0">policy</span>
                        <span>همچنین، ارائه و بارگذاری تصویر گواهی معتبر عدم سوء پیشینه کیفری. </span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="material-symbols-rounded text-[16px] opacity-80 shrink-0">account_balance</span>
                        <span>بارگذاری تصویر مستندات بانکی (یک برگه) حاوی شماره حساب فعال به نام شخص متقاضی، منحصراً نزد <strong>بانک تجارت</strong>.</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] rounded-2xl overflow-hidden shadow-sm">
        <div class="px-6 py-4 border-b border-[var(--md-sys-color-outline-variant)]/60 flex items-center gap-3">
            <span class="material-symbols-rounded text-[var(--md-sys-color-primary)] text-xl">folder_open</span>
            <div>
                <h3 class="font-bold text-[var(--md-sys-color-on-surface)] text-sm">بارگذاری مدارک هویتی</h3>
                <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] mt-0.5">تصاویر اسکن شده مدارک خود را با کیفیت مناسب بارگذاری نمایید.</p>
            </div>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($standardTypes as $key => $type)
                    @php
                        $doc = $presenter->docStatus($parsedAttachments, $resetAction, $form, $key);
                        $uploadedDoc = $doc['uploadedDoc'];
                        $status = $doc['status'];
                    @endphp

                    <div
                        class="group relative flex flex-col rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-variant)]/20 overflow-hidden transition-all duration-200 hover:border-[var(--md-sys-color-primary)]/40 hover:shadow-sm">

                        <div class="h-[3px] w-full
                            {{ $status === 'approved' ? 'bg-[var(--md-sys-color-primary)]' :
                               ($status === 'pending'  ? 'bg-[var(--md-sys-color-tertiary)]' :
                               ($status === 'rejected' ? 'bg-[var(--md-sys-color-error)]' : 'bg-[var(--md-sys-color-outline-variant)]')) }}"
                             style="{{ $status === 'approved' ? 'box-shadow: 0 0 8px color-mix(in srgb, var(--md-sys-color-primary) 50%, transparent)' : '' }}">
                        </div>

                        <div class="p-4 flex flex-col gap-3 h-full">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0
                                    {{ $status === 'approved' ? 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]' :
                                       ($status === 'pending'  ? 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]' :
                                       ($status === 'rejected' ? 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]' :
                                        'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]')) }}">
                                    <span class="material-symbols-rounded text-[18px]">
                                        {{ $status === 'approved' ? 'check_circle' : ($status === 'pending' ? 'hourglass_top' : ($status === 'rejected' ? 'cancel' : 'upload_file')) }}
                                    </span>
                                </div>
                                <div class="overflow-hidden">
                                    <h4 class="font-bold text-sm text-[var(--md-sys-color-on-surface)] leading-tight">{{ $type['label'] }}</h4>
                                    <span class="text-[10px] font-medium
                                        {{ $status === 'approved' ? 'text-[var(--md-sys-color-primary)]' :
                                           ($status === 'pending'  ? 'text-[var(--md-sys-color-tertiary)]' :
                                           ($status === 'rejected' ? 'text-[var(--md-sys-color-error)]' : 'text-[var(--md-sys-color-on-surface-variant)]')) }}">
                                        {{ $status === 'approved' ? 'تایید شده' : ($status === 'pending' ? 'در انتظار تایید' : ($status === 'rejected' ? 'رد شده' : 'بارگذاری نشده')) }}
                                    </span>
                                </div>
                            </div>

                            <div class="mt-auto">
                                @if($status === 'empty')
                                    <label for="file-{{ $key }}"
                                           class="flex items-center justify-center gap-2 w-full py-2.5 border border-dashed border-[var(--md-sys-color-outline-variant)] rounded-xl cursor-pointer text-xs font-bold text-[var(--md-sys-color-on-surface-variant)] hover:border-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary)]/5 transition-all">
                                        <span class="material-symbols-rounded text-base">cloud_upload</span>
                                        انتخاب فایل
                                        <input type="file" id="file-{{ $key }}" wire:model="form.files.{{ $key }}"
                                               class="hidden" accept=".jpg,.jpeg,.png,.pdf">
                                    </label>
                                    <div wire:loading wire:target="form.files.{{ $key }}"
                                         class="text-center mt-2 text-[10px] text-[var(--md-sys-color-primary)] font-bold animate-pulse">
                                        در حال آماده‌سازی...
                                    </div>
                                @elseif($status === 'pending')
                                    <div
                                        class="flex items-center justify-between bg-[var(--md-sys-color-tertiary-container)]/30 border border-[var(--md-sys-color-tertiary)]/20 px-3 py-2 rounded-xl">
                                        <span class="text-[11px] font-bold text-[var(--md-sys-color-tertiary)]">در انتظار تایید</span>
                                        @if($resetAction->hasFile($form, $key))
                                            <button type="button" wire:click.prevent="removeFile('{{ $key }}')"
                                                    class="text-[var(--md-sys-color-error)] hover:bg-[var(--md-sys-color-error-container)] p-1 rounded-lg transition-colors">
                                                <span class="material-symbols-rounded text-[16px]">close</span>
                                            </button>
                                        @endif
                                    </div>
                                @elseif($status === 'approved')
                                    <a href="{{ $uploadedDoc['url'] }}" target="_blank"
                                       class="flex items-center justify-center gap-2 w-full py-2.5 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] rounded-xl font-bold text-xs hover:opacity-90 hover:-translate-y-0.5 transition-all shadow-sm">
                                        <span class="material-symbols-rounded text-[16px]">visibility</span>
                                        مشاهده مدرک
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach

                <button type="button" wire:click="$dispatch('open-modal', { name: 'upload-custom-modal' })"
                        class="group relative flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-[var(--md-sys-color-outline-variant)] bg-transparent p-6 hover:border-[var(--md-sys-color-primary)]/60 hover:bg-[var(--md-sys-color-primary)]/5 transition-all min-h-[140px]">
                    <div
                        class="w-12 h-12 rounded-xl bg-[var(--md-sys-color-surface-variant)] group-hover:bg-[var(--md-sys-color-primary-container)] flex items-center justify-center transition-colors">
                        <span
                            class="material-symbols-rounded text-[26px] text-[var(--md-sys-color-on-surface-variant)] group-hover:text-[var(--md-sys-color-on-primary-container)] transition-colors">add</span>
                    </div>
                    <div class="text-center">
                        <p class="font-bold text-sm text-[var(--md-sys-color-on-surface)]">افزودن مدرک دیگر</p>
                        <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] mt-0.5">مدارک جانبی یا
                            اختیاری</p>
                    </div>
                </button>
            </div>

            @php $customDocs = $presenter->customDocs($parsedAttachments); @endphp
            @if($customDocs->count() > 0)
                <div class="mt-6 pt-6 border-t border-[var(--md-sys-color-outline-variant)]/60">
                    <div class="flex items-center gap-2 mb-4">
                        <span
                            class="material-symbols-rounded text-[var(--md-sys-color-primary)] text-lg">folder_special</span>
                        <h3 class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">سایر مدارک بارگذاری شده</h3>
                        <span
                            class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">{{ $customDocs->count() }}</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($customDocs as $doc)
                            <div
                                class="flex items-center justify-between p-3 rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-variant)]/20 hover:border-[var(--md-sys-color-primary)]/30 transition-colors">
                                <div class="flex items-center gap-3 overflow-hidden">
                                    <div
                                        class="w-9 h-9 flex-shrink-0 flex items-center justify-center rounded-xl bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">
                                        <span class="material-symbols-rounded text-[18px]">description</span>
                                    </div>
                                    <div class="overflow-hidden">
                                        <p class="font-bold text-xs text-[var(--md-sys-color-on-surface)] truncate"
                                           title="{{ $doc['key'] }}">{{ $doc['key'] }}</p>
                                        <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] mt-0.5"
                                           dir="ltr">{{ $doc['uploadedTime'] }}</p>
                                    </div>
                                </div>
                                <a href="{{ $doc['url'] }}" target="_blank"
                                   class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary)] transition-colors">
                                    <span class="material-symbols-rounded text-[16px]">download</span>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <x-ui.modals.dialog name="upload-custom-modal" title="افزودن مدرک سفارشی">
        <div x-data="{
            fileName: '', previewUrl: '',
            handleCustomFileSelect(e) {
                if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
                const f = e.target.files[0];
                if (!f || f.size > 5242880) { this.fileName=''; this.previewUrl=''; e.target.value=null; return; }
                this.fileName = f.name;
                this.previewUrl = ['image/jpeg','image/png','image/gif','image/webp','application/pdf'].includes(f.type) ? URL.createObjectURL(f) : '';
            }
        }" class="space-y-5 bg-[var(--md-sys-color-primary-container)]">
            <x-ui.forms.input label="عنوان مدرک" name="form.customType" wire:model="form.customType"
                              placeholder="مثال: گواهی دوره آموزشی" icon="label"/>

            <div>
                <label class="block text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-2">فایل مدرک</label>
                <div x-show="!fileName">
                    <label for="custom-file-upload"
                           class="flex flex-col items-center justify-center w-full py-8 border border-dashed border-[var(--md-sys-color-outline-variant)] rounded-xl cursor-pointer bg-[var(--md-sys-color-surface-variant)]/20 hover:border-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary)]/5 transition-all">
                        <span
                            class="material-symbols-rounded text-[36px] text-[var(--md-sys-color-on-surface-variant)] mb-2">note_add</span>
                        <span class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">انتخاب فایل مدرک</span>
                        <span class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] mt-1">PDF، JPG یا PNG — حداکثر ۵ مگابایت</span>
                    </label>
                    <input id="custom-file-upload" type="file" class="hidden" wire:model="form.customFile"
                           @change="handleCustomFileSelect($event)" accept=".pdf,.jpg,.png,.jpeg">
                    <div wire:loading wire:target="form.customFile"
                         class="text-center mt-2 text-xs text-[var(--md-sys-color-primary)] font-bold animate-pulse">در
                        حال آماده‌سازی فایل...
                    </div>
                </div>
                <div x-show="fileName" x-cloak class="space-y-3">
                    <div
                        class="flex items-center justify-between p-3 rounded-xl bg-[var(--md-sys-color-surface-variant)]/40 border border-[var(--md-sys-color-outline-variant)]">
                        <div class="flex items-center gap-3 overflow-hidden">
                            <span
                                class="material-symbols-rounded text-[22px] text-[var(--md-sys-color-on-surface-variant)]">draft</span>
                            <span x-text="fileName"
                                  class="text-sm font-bold text-[var(--md-sys-color-on-surface)] truncate"
                                  dir="ltr"></span>
                        </div>
                        <button type="button"
                                @click="fileName=''; previewUrl=''; document.getElementById('custom-file-upload').value=''"
                                class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-error)] transition-colors">
                            <span class="material-symbols-rounded text-[16px]">close</span>
                        </button>
                    </div>
                    <a x-show="previewUrl" :href="previewUrl" target="_blank"
                       class="flex items-center justify-center gap-2 py-2.5 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-bold text-sm">
                        <span class="material-symbols-rounded text-[18px]">visibility</span>
                        پیش‌نمایش فایل
                    </a>
                </div>
                @error('form.customType') <p
                    class="text-xs text-[var(--md-sys-color-error)] mt-2 font-medium">{{ $message }}</p> @enderror
                @error('form.customFile') <p
                    class="text-xs text-[var(--md-sys-color-error)] mt-2 font-medium">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-[var(--md-sys-color-outline-variant)]/60">
                <x-ui.buttons.form type="button" variant="ghost"
                                   x-on:click="window.dispatchEvent(new CustomEvent('close-modal'))">انصراف
                </x-ui.buttons.form>
                <x-ui.buttons.form type="button" wire:click="showCustomUploadConfirmation" wire:loading.attr="disabled"
                                   wire:target="form.customFile" icon="upload" variant="primary">مرحله بعد
                </x-ui.buttons.form>
            </div>
        </div>
    </x-ui.modals.dialog>

</div>
