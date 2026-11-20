<x-ui.modals.action wire:model="editOpen" title="ویرایش برنامه" action="saveEdit" confirm-text="ذخیره برنامه" cancel-text="انصراف">
    <div class="modal-inner-card !w-full !max-w-none !p-5 md:!p-6 flex flex-col gap-3" dir="rtl">
        <div wire:key="launchpad-edit-context" class="rounded-xl border border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface-container-low)] divide-y divide-[var(--md-sys-color-outline-variant)]/40">
            @foreach([['نام برنامه', $editContext['title'] ?? ''], ['دپارتمان', $editContext['department'] ?? '']] as [$label, $value])
                <div wire:key="launchpad-edit-context-{{ $loop->index }}" class="flex items-start justify-between gap-4 px-4 py-2.5 text-sm">
                    <span class="shrink-0 font-semibold text-[var(--md-sys-color-on-surface-variant)]">{{ $label }}</span>
                    <span dir="auto" class="min-w-0 break-words font-bold text-[var(--md-sys-color-on-surface)]">{{ $value }}</span>
                </div>
            @endforeach
        </div>
        <x-ui.forms.textarea label="توضیح کوتاه (اختیاری)" name="editForm.description" wire:model="editForm.description"/>
        @if($editForm->kind === 'static')
            @php($sourceError = collect($errors->keys())->contains(fn ($key) => str_starts_with($key, 'editForm.source')))
            @php($keepSecrets = $editForm->storedAuth !== '' && $editForm->storedAuth === $editForm->sourceAuth)
            <div wire:key="launchpad-edit-source" x-data="{ open: false }" class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container-low)] w-full overflow-hidden">
                @if($sourceError)<span wire:key="launchpad-edit-source-open" x-init="open = true" class="hidden"></span>@endif
                <button type="button" @click="open = !open" :aria-expanded="open" class="w-full flex items-center justify-between gap-2 px-4 py-3 text-right hover:bg-[var(--md-sys-color-surface-variant)]/30 transition-colors">
                    <span class="flex flex-col gap-0.5">
                        <span class="flex items-center gap-2 text-sm font-bold text-[var(--md-sys-color-on-surface)]"><span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">cloud_sync</span>دریافت خودکار اطلاعات (اختیاری)@if($sourceError)<span wire:key="launchpad-edit-source-error-flag" class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-error)]">error</span>@endif</span>
                        <span class="text-xs text-[var(--md-sys-color-on-surface-variant)]">فقط برای برنامه‌هایی که اطلاعات تازه را از سرویس دیگر یا گوگل‌شیت می‌گیرند. در غیر این صورت، این بخش را خالی بگذارید.</span>
                    </span>
                    <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-on-surface-variant)] transition-transform duration-200" :class="open ? 'rotate-180' : ''">expand_more</span>
                </button>
                <div x-show="open" x-collapse x-cloak style="display:none" class="flex flex-col gap-3 px-4 pb-4 pt-3 border-t border-[var(--md-sys-color-outline-variant)]/60">
                    <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">سامانه هر شب اطلاعات را از سرویس انتخاب‌شده می‌گیرد و در فایل <bdi>data.json</bdi> می‌گذارد. برنامه باید برای خواندن این فایل آماده شده باشد. همهٔ اطلاعات دریافتی برای مخاطبان برنامه و مدیران قابل مشاهده است. اگر تنظیمات اتصال را نمی‌دانید، آن‌ها را از سازندهٔ برنامه بگیرید.</p>
                    @foreach($editForm->sourceRows() as $rowId => $fields)
                        <div wire:key="launchpad-edit-source-row-{{ $rowId }}" class="grid grid-cols-1 gap-3 {{ match (array_sum(array_map(fn ($f) => ($f['wide'] ?? false) ? 2 : 1, $fields))) { 1 => '', 2 => 'md:grid-cols-2', default => 'md:grid-cols-3' } }}">
                            @foreach($fields as $f)
                                @php($label = $f['type'] === 'select' ? $f['label'] : $f['label'] . (($f['required'] ?? false) && !($keepSecrets && ($f['secret'] ?? false)) ? ' (الزامی)' : ' (اختیاری)'))
                                <div wire:key="launchpad-edit-source-{{ $f['key'] }}" class="flex flex-col gap-1 {{ ($f['wide'] ?? false) ? 'md:col-span-2' : '' }}">
                                    @if($f['type'] === 'select')
                                        <x-ui.forms.select :label="$label" name="{{ $f['model'] }}" wire:model.live="{{ $f['model'] }}">
                                            @foreach($f['options'] as $value => $optionLabel)<option value="{{ $value }}">{{ $optionLabel }}</option>@endforeach
                                        </x-ui.forms.select>
                                    @elseif($f['type'] === 'textarea')
                                        <x-ui.forms.textarea :label="$label" name="{{ $f['model'] }}" wire:model="{{ $f['model'] }}" autocomplete="off" dir="auto" style="unicode-bidi: plaintext"/>
                                    @else
                                        <x-ui.forms.input :label="$label" name="{{ $f['model'] }}" wire:model="{{ $f['model'] }}" :type="($f['secret'] ?? false) ? 'password' : 'text'" autocomplete="off" dir="auto" style="unicode-bidi: plaintext"/>
                                    @endif
                                    @if($f['hint'])<p wire:key="launchpad-edit-hint-{{ $f['key'] }}" class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] px-1 mt-1">{{ $f['hint'] }}@if($f['placeholder']) نمونه: <bdi>{{ $f['placeholder'] }}</bdi>@endif</p>@endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
        <div wire:key="launchpad-edit-public-switch" class="flex items-center justify-between bg-[var(--md-sys-color-surface-container)] p-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/20">
            <div class="flex items-center gap-3 text-[var(--md-sys-color-on-surface)]">
                <div class="p-2 bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] rounded-lg">
                    <span class="material-symbols-rounded">public</span>
                </div>
                <div>
                    <p class="font-bold text-sm">برای همه کاربران</p>
                    <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">اگر روشن باشد، همهٔ کاربران سامانه می‌توانند برنامه را ببینند و لازم نیست مخاطب انتخاب کنید. اگر خاموش باشد، فقط مالک، مدیران و مخاطبان انتخاب‌شده آن را می‌بینند.</p>
                </div>
            </div>
            <label class="cursor-pointer">
                <x-ui.forms.switch name="editForm.public" :live="true"/>
            </label>
        </div>
        @if(!$editForm->public)
            <div wire:key="launchpad-edit-audience-block" class="flex flex-col gap-3">
                @error('editForm.audience')<p wire:key="launchpad-edit-audience-error" class="flex items-center gap-1 mt-1 text-[12px] font-medium text-[var(--md-sys-color-error)] animate-toast-in"><span class="material-symbols-rounded text-[14px]">error</span>{{ $message }}</p>@enderror
                <x-dashboard.member-picker wire:key="launchpad-edit-audience-picker" model="editForm.audience" :candidates="$this->editCandidates" mode="multi" bulk :dept-options="$this->deptOptions"/>
            </div>
        @endif
        <x-ui.forms.date label="از چه تاریخی باز شود؟ (اختیاری)" prefix="editFrom" :startYear="$yearFloors['editFrom'] ?? jNow()" :endYear="jNow() + 5"/>
        <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] px-1 mt-1">خالی بگذارید تا مخاطبان از همین حالا به برنامه دسترسی داشته باشند. تاریخ‌ها را شمسی وارد کنید.</p>
        <x-ui.forms.date label="تا چه تاریخی باز بماند؟ (اختیاری)" prefix="editTo" :startYear="$yearFloors['editTo'] ?? jNow()" :endYear="jNow() + 5"/>
        <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] px-1 mt-1">تا پایان این روز برای مخاطبان باز می‌ماند. پس از آن بسته می‌شود، اما حذف نمی‌شود. خالی یعنی بدون محدودیت پایان دسترسی.</p>
        <x-ui.forms.date label="آخرین روز نگهداری برنامه (اختیاری)" prefix="editExpiry" :startYear="$yearFloors['editExpiry'] ?? jNow()" :endYear="jNow() + 5"/>
        <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] px-1 mt-1">پس از پایان این روز، برنامه از دسترس مخاطبان خارج می‌شود و هنگام پاک‌سازی خودکار، همراه فایل‌هایش حذف می‌شود. خالی بگذارید تا خودکار حذف نشود.</p>
        @error('editForm.accessible_from')<p wire:key="launchpad-edit-access-from-error" class="flex items-center gap-1 mt-1 text-[12px] font-medium text-[var(--md-sys-color-error)] animate-toast-in"><span class="material-symbols-rounded text-[14px]">error</span>{{ $message }}</p>@enderror
        @error('editForm.accessible_to')<p wire:key="launchpad-edit-access-to-error" class="flex items-center gap-1 mt-1 text-[12px] font-medium text-[var(--md-sys-color-error)] animate-toast-in"><span class="material-symbols-rounded text-[14px]">error</span>{{ $message }}</p>@enderror
        @error('editForm.expire_at')<p wire:key="launchpad-edit-expire-error" class="flex items-center gap-1 mt-1 text-[12px] font-medium text-[var(--md-sys-color-error)] animate-toast-in"><span class="material-symbols-rounded text-[14px]">error</span>{{ $message }}</p>@enderror
    </div>
</x-ui.modals.action>
