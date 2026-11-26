<x-ui.forms.card class="flex flex-col gap-3">
    <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">فایل‌های آمادهٔ برنامه را اینجا بارگذاری کنید و مشخص کنید چه کسانی بتوانند آن را ببینند. برای آشنایی با روش افزودن برنامه، راهنمای بالای صفحه را بخوانید.</p>
    <div wire:key="launchpad-cap-rules" x-data="{ open: false }">
        <x-ui.buttons.form variant="ghost" icon="rule" x-on:click="open = !open" x-bind:aria-expanded="open" class="!h-8 !px-2 text-[11px] font-bold">
            نکته‌های مهم دربارهٔ ظرفیت دپارتمان
            <x-ui.badge class="!px-1.5 !py-0 !text-[10px]">{{ convertToPersian((string) count($capRules)) }}</x-ui.badge>
            <span class="material-symbols-rounded text-[16px] transition-transform duration-200" :class="open ? 'rotate-180' : ''">expand_more</span>
        </x-ui.buttons.form>
        <div x-show="open" x-collapse x-cloak class="mt-2 flex flex-col gap-2 rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container-low)] p-3">
            @foreach($capRules as $group => $text)
                <div wire:key="launchpad-cap-rule-{{ $loop->index }}" class="flex items-start gap-1.5">
                    <span class="w-16 shrink-0 text-[10px] font-bold text-[var(--md-sys-color-on-surface-variant)]">{{ $group }}</span>
                    <p class="text-[11px] leading-6 text-[var(--md-sys-color-on-surface)]">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </div>
    <x-ui.forms.input label="نام برنامه" name="form.title" wire:model="form.title" mark/>
    <x-ui.forms.textarea label="توضیح کوتاه (اختیاری)" name="form.description" wire:model="form.description"/>
    <x-ui.forms.select label="دپارتمان" name="form.department_id" wire:model="form.department_id" mark>
        <option value="">انتخاب دپارتمان</option>
        @foreach($departments as $code => $label)<option value="{{ $code }}">{{ $label }}</option>@endforeach
    </x-ui.forms.select>
    @if(filled($sites) || $this->form->kind === 'link')
    <div wire:key="launchpad-kind-switch" class="flex items-center justify-between bg-[var(--md-sys-color-surface-container)] p-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/20">
        <div class="flex items-center gap-3 text-[var(--md-sys-color-on-surface)]">
            <div class="p-2 bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] rounded-lg">
                <span class="material-symbols-rounded">{{ $this->form->kind === 'link' ? 'link' : 'upload_file' }}</span>
            </div>
            <div>
                <p class="font-bold text-sm">ساخت میان‌بر به سامانهٔ دیگر</p>
                <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">{{ $this->form->kind === 'link' ? 'با باز کردن این میان‌بر، سامانهٔ انتخاب‌شده در زبانهٔ جدید باز می‌شود. نیازی به فایل ندارید.' : 'این گزینه خاموش است؛ باید فایل‌های آمادهٔ برنامه را بارگذاری کنید.' }}</p>
            </div>
        </div>
        <label class="cursor-pointer">
            <x-ui.forms.switch x-bind:checked="$wire.form.kind === 'link'" x-on:change="$wire.$set('form.kind', $event.target.checked ? 'link' : 'static')"/>
        </label>
    </div>
    @endif
    @if($this->form->kind === 'link')
        <div wire:key="launchpad-kind-link" class="flex flex-col gap-3">
            <x-ui.forms.select label="سامانهٔ مقصد" name="form.site" wire:model="form.site" icon="language" mark>
                <option value="">سامانه را انتخاب کنید</option>
                @foreach($sites as $name => $sibling)<option value="{{ $name }}">{{ $sibling['title'] }}</option>@endforeach
            </x-ui.forms.select>
            <x-ui.forms.input label="صفحهٔ موردنظر در سامانه (اختیاری)" name="form.path" wire:model="form.path"/>
            <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">برای باز شدن صفحهٔ اصلی، این قسمت را خالی بگذارید. برای صفحه‌ای دیگر، فقط بخش بعد از نشانی سامانه را بنویسید؛ مثلاً <bdi>reports/q1</bdi>. نشانی کامل یا علامت / در ابتدای آن ننویسید.</p>
        </div>
    @else
        <div wire:key="launchpad-kind-static" class="flex flex-col gap-3">
    <div x-on:change.capture="limit($event)">
        <input id="launchpad-files" type="file" multiple wire:model="form.files" data-max="{{ $this->limits['max_files'] }}" data-over="تعداد فایل‌ها بیش از حد مجاز است." class="hidden"/>
        <label id="form.files" for="launchpad-files" tabindex="0" role="button" x-on:keydown.enter.prevent="$el.control?.click()" x-on:keydown.space.prevent="$el.control?.click()" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] text-[11.5px] font-bold hover:bg-[var(--md-sys-color-surface-container-highest)] transition-colors cursor-pointer"><span class="material-symbols-rounded text-[16px]">attach_file</span>انتخاب فایل‌ها<span class="text-[var(--md-sys-color-error)]">*</span></label>
    </div>
    <div wire:loading.delay wire:target="form.files">
        <x-ui.loaders.bar/>
    </div>
    @error('form.files')<p wire:key="launchpad-files-error" class="flex items-center gap-1 mt-1 text-[12px] font-medium text-[var(--md-sys-color-error)] animate-toast-in"><span class="material-symbols-rounded text-[14px]">error</span>{{ $message }}</p>@enderror
    @error('form.files.*')<p wire:key="launchpad-file-error" class="flex items-center gap-1 mt-1 text-[12px] font-medium text-[var(--md-sys-color-error)] animate-toast-in"><span class="material-symbols-rounded text-[14px]">error</span>{{ $message }}</p>@enderror
    <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">فایل اصلی با نام دقیق <bdi>index.html</bdi> و همهٔ فایل‌های همراه آن را با هم انتخاب کنید. همه باید در یک پوشه و کنار هم باشند؛ فایل ZIP یا پوشه انتخاب نکنید. هر بار انتخاب، فهرست قبلی را عوض می‌کند. حداکثر {{ convertToPersian((string) $this->limits['max_files']) }} فایل؛ حجم هر فایل تا {{ convertToPersian((string) $this->limits['per_file_mb']) }} مگابایت و حجم همهٔ فایل‌ها با هم تا {{ convertToPersian((string) $this->limits['total_mb']) }} مگابایت.</p>
    @if($precheck === [])
        <div wire:key="launchpad-precheck-empty" class="contents"><x-ui.empty icon="folder_open" title="فایلی انتخاب نشده است"/></div>
    @elseif($precheck['ok'])
        <div wire:key="launchpad-precheck-ok" class="flex flex-col gap-2">
            <p class="text-sm">فایل‌ها برای ذخیره آماده‌اند. فایل اصلی <bdi>index.html</bdi> پیدا شد. تعداد: {{ convertToPersian((string) $precheck['count']) }} از {{ convertToPersian((string) $precheck['max_count']) }} فایل مجاز</p>
            <x-ui.quota-meter label="حجم کل فایل‌ها (به بایت)" :used="$precheck['bytes']" :cap="$precheck['max_bytes']" :pct="$precheck['pct']" :tone="$precheck['tone']"/>
        </div>
    @else
        <div wire:key="launchpad-precheck-blocked" class="flex flex-col gap-2">
            <p class="text-sm">پیش از ذخیره، مشکل‌های زیر را برطرف کنید. فایل اصلی <bdi>index.html</bdi>: {{ $precheck['has_entry'] ? 'پیدا شد' : 'پیدا نشد' }}. تعداد: {{ convertToPersian((string) $precheck['count']) }} از {{ convertToPersian((string) $precheck['max_count']) }} فایل مجاز</p>
            <x-ui.quota-meter label="حجم کل فایل‌ها (به بایت)" :used="$precheck['bytes']" :cap="$precheck['max_bytes']" :pct="$precheck['pct']" :tone="$precheck['tone']"/>
            @foreach($precheck['rejected'] as $rejected)<p wire:key="launchpad-rejected-{{ $loop->index }}" class="text-sm text-[var(--md-sys-color-error)]"><bdi>{{ $rejected['name'] }}</bdi>: {{ $rejected['reason'] }}</p>@endforeach
            @foreach($precheck['messages'] as $message)<p wire:key="launchpad-precheck-error-{{ $loop->index }}" class="text-sm text-[var(--md-sys-color-error)]">{{ $message }}</p>@endforeach
        </div>
    @endif
    @php($sourceError = collect($errors->keys())->contains(fn ($key) => str_starts_with($key, 'form.source')))
    <div wire:key="launchpad-source" x-data="{ open: false }" class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container-low)] w-full overflow-hidden">
        @if($sourceError)<span wire:key="launchpad-source-open" x-init="open = true" class="hidden"></span>@endif
        <button type="button" @click="open = !open" :aria-expanded="open" class="w-full flex items-center justify-between gap-2 px-4 py-3 text-right hover:bg-[var(--md-sys-color-surface-variant)]/30 transition-colors">
            <span class="flex flex-col gap-0.5">
                <span class="flex items-center gap-2 text-sm font-bold text-[var(--md-sys-color-on-surface)]"><span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">cloud_sync</span>دریافت خودکار اطلاعات (اختیاری)@if($sourceError)<span wire:key="launchpad-source-error-flag" class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-error)]">error</span>@endif</span>
                <span class="text-xs text-[var(--md-sys-color-on-surface-variant)]">فقط برای برنامه‌هایی که اطلاعات تازه را از سرویس دیگر یا گوگل‌شیت می‌گیرند. در غیر این صورت، این بخش را خالی بگذارید.</span>
            </span>
            <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-on-surface-variant)] transition-transform duration-200" :class="open ? 'rotate-180' : ''">expand_more</span>
        </button>
        <div x-show="open" x-collapse x-cloak style="display:none" class="flex flex-col gap-3 px-4 pb-4 pt-3 border-t border-[var(--md-sys-color-outline-variant)]/60">
            <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">سامانه هر شب اطلاعات را از سرویس انتخاب‌شده می‌گیرد و در فایل <bdi>data.json</bdi> می‌گذارد. برنامه باید برای خواندن این فایل آماده شده باشد. همهٔ اطلاعات دریافتی برای مخاطبان برنامه و مدیران قابل مشاهده است. اگر تنظیمات اتصال را نمی‌دانید، آن‌ها را از سازندهٔ برنامه بگیرید. اگر «نوع منبع داده» را روی «بدون منبع داده» قرار دهید، اتصال قبلی حفظ می‌شود.</p>
            @foreach($this->form->sourceRows() as $rowId => $fields)
                <div wire:key="launchpad-source-row-{{ $rowId }}" class="grid grid-cols-1 gap-3 {{ match (array_sum(array_map(fn ($f) => ($f['wide'] ?? false) ? 2 : 1, $fields))) { 1 => '', 2 => 'md:grid-cols-2', default => 'md:grid-cols-3' } }}">
                    @foreach($fields as $f)
                        @php($label = $f['type'] === 'select' ? $f['label'] : $f['label'] . (($f['required'] ?? false) ? ' (الزامی)' : ' (اختیاری)'))
                        <div wire:key="launchpad-source-{{ $f['key'] }}" class="flex flex-col gap-1 {{ ($f['wide'] ?? false) ? 'md:col-span-2' : '' }}">
                            @if($f['type'] === 'select')
                                <x-ui.forms.select :label="$label" name="{{ $f['model'] }}" wire:model.live="{{ $f['model'] }}">
                                    @foreach($f['options'] as $value => $optionLabel)<option value="{{ $value }}">{{ $optionLabel }}</option>@endforeach
                                </x-ui.forms.select>
                            @elseif($f['type'] === 'textarea')
                                <x-ui.forms.textarea :label="$label" name="{{ $f['model'] }}" wire:model="{{ $f['model'] }}" autocomplete="off" dir="auto" style="unicode-bidi: plaintext"/>
                            @else
                                <x-ui.forms.input :label="$label" name="{{ $f['model'] }}" wire:model="{{ $f['model'] }}" :type="($f['secret'] ?? false) ? 'password' : 'text'" autocomplete="off" dir="auto" style="unicode-bidi: plaintext"/>
                            @endif
                            @if($f['hint'])<p wire:key="launchpad-hint-{{ $f['key'] }}" class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] px-1 mt-1">{{ $f['hint'] }}@if($f['placeholder']) نمونه: <bdi>{{ $f['placeholder'] }}</bdi>@endif</p>@endif
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
    </div>
    @endif
    <div wire:key="launchpad-public-switch" class="flex items-center justify-between bg-[var(--md-sys-color-surface-container)] p-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/20">
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
            <x-ui.forms.switch name="form.public" :live="true"/>
        </label>
    </div>
    @if(!$this->form->public)
        <div wire:key="launchpad-audience-block" class="flex flex-col gap-3">
            <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">چه کسانی برنامه را ببینند؟ افراد را از فهرست زیر انتخاب کنید. اگر کسی را انتخاب نکنید، فقط شما و مدیران برنامه را می‌بینید. مخاطبان و تاریخ‌ها را بعداً می‌توانید از «جزئیات» و سپس «ویرایش» تغییر دهید.</p>
            @error('form.audience')<p wire:key="launchpad-audience-error" class="flex items-center gap-1 mt-1 text-[12px] font-medium text-[var(--md-sys-color-error)] animate-toast-in"><span class="material-symbols-rounded text-[14px]">error</span>{{ $message }}</p>@enderror
            <x-dashboard.member-picker wire:key="launchpad-audience-picker" model="form.audience" :candidates="$this->candidates" mode="multi" bulk :dept-options="$this->deptOptions"/>
        </div>
    @endif
    <x-ui.forms.date label="از چه تاریخی باز شود؟ (اختیاری)" prefix="from" :startYear="jNow()" :endYear="jNow() + 5"/>
    <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] px-1 mt-1">خالی بگذارید تا مخاطبان از همین حالا به برنامه دسترسی داشته باشند. تاریخ‌ها را شمسی وارد کنید.</p>
    <x-ui.forms.date label="تا چه تاریخی باز بماند؟ (اختیاری)" prefix="to" :startYear="jNow()" :endYear="jNow() + 5"/>
    <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] px-1 mt-1">تا پایان این روز برای مخاطبان باز می‌ماند. پس از آن بسته می‌شود، اما حذف نمی‌شود. خالی یعنی بدون محدودیت پایان دسترسی.</p>
    <x-ui.forms.date label="آخرین روز نگهداری برنامه (اختیاری)" prefix="expiry" :startYear="jNow()" :endYear="jNow() + 5"/>
    <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] px-1 mt-1">پس از پایان این روز، برنامه از دسترس مخاطبان خارج می‌شود و هنگام پاک‌سازی خودکار، همراه فایل‌هایش حذف می‌شود. خالی بگذارید تا خودکار حذف نشود.</p>
    @error('form.accessible_from')<p wire:key="launchpad-access-from-error" class="flex items-center gap-1 mt-1 text-[12px] font-medium text-[var(--md-sys-color-error)] animate-toast-in"><span class="material-symbols-rounded text-[14px]">error</span>{{ $message }}</p>@enderror
    @error('form.accessible_to')<p wire:key="launchpad-access-to-error" class="flex items-center gap-1 mt-1 text-[12px] font-medium text-[var(--md-sys-color-error)] animate-toast-in"><span class="material-symbols-rounded text-[14px]">error</span>{{ $message }}</p>@enderror
    @error('form.expire_at')<p wire:key="launchpad-expire-error" class="flex items-center gap-1 mt-1 text-[12px] font-medium text-[var(--md-sys-color-error)] animate-toast-in"><span class="material-symbols-rounded text-[14px]">error</span>{{ $message }}</p>@enderror
    <x-ui.buttons.form loading="save" wire:click="save" wire:loading.attr="disabled" wire:target="form.files">ذخیره برنامه</x-ui.buttons.form>
</x-ui.forms.card>
