<x-ui.forms.card class="flex flex-col gap-3">
    <x-ui.forms.input label="عنوان" name="form.title" wire:model="form.title"/>
    <x-ui.forms.textarea label="توضیحات" name="form.description" wire:model="form.description"/>
    <x-ui.forms.select label="بخش" name="form.department_id" wire:model="form.department_id">
        <option value="">انتخاب بخش</option>
        @foreach($departments as $code => $label)<option value="{{ $code }}">{{ $label }}</option>@endforeach
    </x-ui.forms.select>
    <div x-on:change.capture="limit($event)">
        <input id="launchpad-files" type="file" multiple wire:model="form.files" data-max="{{ $this->limits['max_files'] }}" data-over="تعداد فایل‌ها بیش از حد مجاز است." class="hidden"/>
        <label for="launchpad-files" tabindex="0" role="button" x-on:keydown.enter.prevent="$el.control?.click()" x-on:keydown.space.prevent="$el.control?.click()" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] text-[11.5px] font-bold hover:bg-[var(--md-sys-color-surface-container-highest)] transition-colors cursor-pointer"><span class="material-symbols-rounded text-[16px]">attach_file</span>انتخاب فایل‌ها</label>
    </div>
    <x-ui.loaders.bar wire:loading wire:target="form.files"/>
    @error('form.files')<p wire:key="launchpad-files-error" class="flex items-center gap-1 mt-1 text-[12px] font-medium text-[var(--md-sys-color-error)] animate-toast-in"><span class="material-symbols-rounded text-[14px]">error</span>{{ $message }}</p>@enderror
    @error('form.files.*')<p wire:key="launchpad-file-error" class="flex items-center gap-1 mt-1 text-[12px] font-medium text-[var(--md-sys-color-error)] animate-toast-in"><span class="material-symbols-rounded text-[14px]">error</span>{{ $message }}</p>@enderror
    @error('form.audience')<p wire:key="launchpad-audience-error" class="flex items-center gap-1 mt-1 text-[12px] font-medium text-[var(--md-sys-color-error)] animate-toast-in"><span class="material-symbols-rounded text-[14px]">error</span>{{ $message }}</p>@enderror
    <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">همه فایل‌های پوشه را یک‌جا انتخاب کنید؛ حداکثر {{ convertToPersian((string) $this->limits['max_files']) }} فایل، هر فایل {{ convertToPersian((string) $this->limits['per_file_mb']) }} مگابایت و مجموع {{ convertToPersian((string) $this->limits['total_mb']) }} مگابایت.</p>
    @if($precheck === [])
        <div wire:key="launchpad-precheck-empty" class="contents"><x-ui.empty icon="folder_open" title="فایلی انتخاب نشده است"/></div>
    @elseif($precheck['ok'])
        <div wire:key="launchpad-precheck-ok" class="flex flex-col gap-2">
            <p class="text-sm">index.html موجود است؛ {{ convertToPersian((string) $precheck['count']) }} / {{ convertToPersian((string) $precheck['max_count']) }} فایل</p>
            <x-ui.quota-meter label="حجم فایل‌ها (بایت)" :used="$precheck['bytes']" :cap="$precheck['max_bytes']" :pct="$precheck['pct']" :tone="$precheck['tone']"/>
        </div>
    @else
        <div wire:key="launchpad-precheck-blocked" class="flex flex-col gap-2">
            <p class="text-sm">index.html: {{ $precheck['has_entry'] ? 'موجود' : 'موجود نیست' }}؛ {{ convertToPersian((string) $precheck['count']) }} / {{ convertToPersian((string) $precheck['max_count']) }} فایل</p>
            <x-ui.quota-meter label="حجم فایل‌ها (بایت)" :used="$precheck['bytes']" :cap="$precheck['max_bytes']" :pct="$precheck['pct']" :tone="$precheck['tone']"/>
            @foreach($precheck['rejected'] as $rejected)<p wire:key="launchpad-rejected-{{ $loop->index }}" class="text-sm text-[var(--md-sys-color-error)]"><bdi>{{ $rejected['name'] }}</bdi>: {{ $rejected['reason'] }}</p>@endforeach
            @foreach($precheck['messages'] as $message)<p wire:key="launchpad-precheck-error-{{ $loop->index }}" class="text-sm text-[var(--md-sys-color-error)]">{{ $message }}</p>@endforeach
        </div>
    @endif
    <x-dashboard.member-picker model="form.audience" :candidates="$this->candidates" mode="multi"/>
    <x-ui.buttons.form variant="tonal" icon="group_add" wire:click="addDepartment">افزودن اعضای بخش</x-ui.buttons.form>
    <x-ui.forms.date label="شروع دسترسی" prefix="from"/>
    <x-ui.forms.date label="پایان دسترسی" prefix="to"/>
    <x-ui.forms.date label="تاریخ انقضا" prefix="expiry"/>
    @error('form.accessible_from')<p wire:key="launchpad-access-from-error" class="flex items-center gap-1 mt-1 text-[12px] font-medium text-[var(--md-sys-color-error)] animate-toast-in"><span class="material-symbols-rounded text-[14px]">error</span>{{ $message }}</p>@enderror
    @error('form.accessible_to')<p wire:key="launchpad-access-to-error" class="flex items-center gap-1 mt-1 text-[12px] font-medium text-[var(--md-sys-color-error)] animate-toast-in"><span class="material-symbols-rounded text-[14px]">error</span>{{ $message }}</p>@enderror
    @error('form.expire_at')<p wire:key="launchpad-expire-error" class="flex items-center gap-1 mt-1 text-[12px] font-medium text-[var(--md-sys-color-error)] animate-toast-in"><span class="material-symbols-rounded text-[14px]">error</span>{{ $message }}</p>@enderror
    <x-ui.buttons.form loading="save" wire:click="save" wire:loading.attr="disabled" wire:target="form.files">ذخیره برنامه</x-ui.buttons.form>
    <x-ui.modals.action wire:model="replaceOpen" title="جایگزینی برنامه" action="confirmReplace" confirm-text="جایگزینی" cancel-text="انصراف">برنامه‌ای با این عنوان وجود دارد. فایل‌های آن جایگزین شوند؟ مخاطبان و تاریخ‌های اعتبار بدون تغییر باقی می‌مانند.</x-ui.modals.action>
</x-ui.forms.card>
