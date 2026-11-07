@php
    $quota = $this->quota;
    $exhausted = $quota['used'] >= $quota['cap'];
    $inactive = $owner && $report !== null && !$report->active;
    $refining = $owner && $report !== null && $presenter->canRefine($report);
    $blockLabel = $refining && $this->form->block !== null ? $presenter->blockLabel($report, $this->form->block) : null;
    $sectionRefine = $blockLabel !== null;
    $busy = $report !== null && $report->status === \App\Models\AiReport::STATUS_GENERATING;
    $generatingElsewhere = $this->hasGenerating;
    $accept = config('ai_report.upload.accept');
    $canSubmit = $refining ? ! ($exhausted || $generatingElsewhere) : ! ($exhausted || $busy || $generatingElsewhere);
    $quotaPct = min(100, max(0, (int) round($quota['used'] * 100 / max(1, $quota['cap']))));
    $rules = [
        'سهمیه' => 'روزانه ' . convertToPersian((string) $quota['cap']) . ' بار می‌توانید گزارش بسازید یا اصلاح کنید. ویرایش دستی، برگشت به نسخهٔ قبل، اشتراک‌گذاری و چاپ سهمیه مصرف نمی‌کنند؛ ساخت ناموفق برگردانده می‌شود، مگر درخواست بیش‌ازحد بزرگ یا محتوای مسدودشده.',
        'فایل‌ها' => 'حداکثر ' . convertToPersian((string) $limits['files']) . ' فایل؛ PDF و تصویر هرکدام تا ' . convertToPersian((string) $limits['inline_mb']) . ' و روی‌هم تا ' . convertToPersian((string) $limits['inline_total_mb']) . ' مگابایت. فایل‌ها پس از ساخت نگه داشته نمی‌شوند؛ برای اصلاح بعدی دوباره پیوست کنید.',
        'همزمانی' => 'در هر زمان فقط یک گزارش در حال ساخت است؛ تا آماده‌شدن آن، گزارش تازه‌ای ساخته نمی‌شود.',
        'اشتراک' => 'گزارش اشتراک‌گذاری‌شده قفل می‌شود؛ اصلاح آن یک نسخهٔ خصوصی تازه برای شما می‌سازد و عنوان یا ویرایش دستی تا برداشتن اشتراک ممکن نیست.',
        'بازبینی' => 'هوش مصنوعی ممکن است اشتباه کند؛ ارقام را پیش از استفاده یا ارسال با منبع اصلی تطبیق دهید.',
    ];
    $quotaLeft = max(0, $quota['cap'] - $quota['used']);
    $quotaTone = $exhausted
        ? ['text' => 'text-[var(--md-sys-color-error)]', 'fill' => 'bg-[var(--md-sys-color-error)]']
        : ($quotaPct >= 80
            ? ['text' => 'text-[var(--tool-gold-color)]', 'fill' => 'bg-[var(--tool-gold-color)]']
            : ['text' => 'text-[var(--md-sys-color-on-surface-variant)]', 'fill' => 'bg-[var(--md-sys-color-primary)]']);
@endphp

<div wire:key="ai-report-composer" id="ai-report-composer" x-data="aiReportComposer" x-cloak
     data-open="{{ $report === null ? '1' : '0' }}" data-can-submit="{{ $canSubmit ? '1' : '0' }}"
     data-action="{{ $refining ? 'refine' : 'generate' }}" data-max="{{ (int) config('ai_report.prompt.max') }}"
     x-on:ai-report-open.window="open = true; $nextTick(() => $el.querySelector('textarea')?.focus())"
     x-on:livewire-upload-start="uploading = true" x-on:livewire-upload-finish="uploading = false"
     x-on:livewire-upload-error="uploading = false" x-on:livewire-upload-cancel="uploading = false"
     class="flex flex-col gap-3 p-4 rounded-2xl bg-[color-mix(in_srgb,var(--md-sys-color-surface)_92%,transparent)] border border-[var(--md-sys-color-outline-variant)]/40 print:hidden">
    <div wire:key="ai-report-quota-usage" class="print:hidden">
        <div class="flex items-center gap-2 mb-1.5">
            <span class="material-symbols-rounded text-[16px] {{ $quotaTone['text'] }}">stacks</span>
            <span class="text-[11px] font-semibold {{ $quotaTone['text'] }}">
                سهمیهٔ امروز: {{ convertToPersian((string) $quota['used']) }} / {{ convertToPersian((string) $quota['cap']) }}
            </span>
        </div>
        <div wire:key="ai-report-quota-bar" role="progressbar" aria-label="سهمیهٔ امروز"
             aria-valuenow="{{ $quota['used'] }}" aria-valuemin="0" aria-valuemax="{{ $quota['cap'] }}"
             class="h-1.5 w-full rounded-full bg-[var(--md-sys-color-surface-variant)] overflow-hidden">
            <div class="h-full rounded-full transition-all duration-500 {{ $quotaTone['fill'] }}" style="width: {{ $quotaPct }}%"></div>
        </div>
        @if(! $exhausted && $quotaPct >= 80)
            <p wire:key="ai-report-quota-left" class="mt-1.5 text-[11px] font-semibold {{ $quotaTone['text'] }}">فقط {{ convertToPersian((string) $quotaLeft) }} بار دیگر امروز می‌توانید گزارش بسازید یا اصلاح کنید.</p>
        @endif
    </div>
    <div wire:key="ai-report-rules" x-data="{ open: false }" class="print:hidden">
        <x-ui.buttons.form variant="ghost" icon="rule" x-on:click="open = !open" x-bind:aria-expanded="open" class="!h-8 !px-2 text-[11px] font-bold">
            قوانین
            <x-ui.badge class="!px-1.5 !py-0 !text-[10px]">{{ convertToPersian((string) count($rules)) }}</x-ui.badge>
            <span class="material-symbols-rounded text-[16px] transition-transform duration-200" :class="open ? 'rotate-180' : ''">expand_more</span>
        </x-ui.buttons.form>

        <div x-show="open" x-collapse x-cloak class="mt-2 flex flex-col gap-2 rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container-low)] p-3">
            @foreach($rules as $group => $text)
                <div wire:key="ai-report-rule-{{ $loop->index }}" class="flex items-start gap-1.5">
                    <span class="w-16 shrink-0 text-[10px] font-bold text-[var(--md-sys-color-on-surface-variant)]">{{ $group }}</span>
                    <p class="text-[11px] leading-6 text-[var(--md-sys-color-on-surface)]">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </div>
    <div class="flex items-center justify-between print:hidden">
        <span wire:key="ai-report-composer-title" class="text-xs font-bold text-[var(--md-sys-color-on-surface)]">{{ $refining ? 'اصلاح گزارش' : 'ساخت گزارش' }}</span>
        <button type="button" wire:key="ai-report-composer-toggle" @click="toggle()" :aria-expanded="open"
                aria-label="باز و بسته کردن فرم درخواست" title="باز و بسته کردن فرم درخواست"
                class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors">
            <span class="material-symbols-rounded text-lg" x-text="open ? 'expand_less' : 'expand_more'">expand_more</span>
        </button>
    </div>
    <div x-show="open" class="flex flex-col gap-3">
    @if($inactive)
        <div wire:key="ai-report-inactive-composer" class="contents">
            <x-ui.notice-card icon="visibility_off" :actions="[['label' => 'فعال‌سازی دوباره', 'onClick' => '$wire.toggleActive()', 'variant' => 'secondary']]">
                <p class="text-xs font-bold text-[var(--md-sys-color-on-surface)]">این گزارش غیرفعال است.</p>
                <p class="text-[11px] leading-6 mt-1 text-[var(--md-sys-color-on-surface-variant)]">تا دوباره فعال نشود، نمی‌توانید آن را اصلاح کنید و درخواست تازه‌ای هم جای آن ساخته نمی‌شود؛ برای بازگشت، فعالش کنید.</p>
            </x-ui.notice-card>
        </div>
    @else
    <div wire:key="ai-report-composer-form" class="contents">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <x-ui.forms.select label="نوع گزارش" name="form.type" wire:model="form.type" icon="category" :disabled="$sectionRefine">
            @foreach(config('ai_report.types') as $key => $type)
                <option value="{{ $key }}">{{ $type['label'] }}</option>
            @endforeach
        </x-ui.forms.select>
        <x-ui.forms.select label="حجم گزارش" name="form.length" wire:model="form.length" icon="straighten" :disabled="$sectionRefine">
            @foreach(config('ai_report.lengths') as $key => $length)
                <option value="{{ $key }}">{{ $length['label'] }}</option>
            @endforeach
        </x-ui.forms.select>
        <x-ui.forms.select label="مخاطب" name="form.audience" wire:model="form.audience" icon="groups">
            @foreach(config('ai_report.audiences') as $key => $audience)
                <option value="{{ $key }}">{{ $audience['label'] }}</option>
            @endforeach
        </x-ui.forms.select>
    </div>
    @if($sectionRefine)
        <p wire:key="ai-report-section-refine-hint" class="text-[10.5px] text-[var(--md-sys-color-on-surface-variant)]">نوع و حجم گزارش برای اصلاح یک بخش اثری ندارند؛ فقط مخاطب اعمال می‌شود.</p>
    @endif
    @php($originalPrompt =$refining ? $presenter->originalPrompt($report) : null)
    @if($originalPrompt !== null)
        <p wire:key="ai-report-original-prompt" class="text-[10.5px] leading-5 text-[var(--md-sys-color-on-surface-variant)]" dir="auto">{{ $originalPrompt['trimmed'] ? 'قدیمی‌ترین درخواست نگه‌داشته‌شده' : 'درخواست اولیه' }}: {{ $originalPrompt['text'] }}</p>
    @endif
    <x-ui.forms.textarea :label="$refining ? 'توضیح تغییر مورد نظر' : 'درخواست گزارش'" name="form.prompt" wire:model="form.prompt" dir="auto"
                         :rows="4" :maximizable="true" icon="edit" :maxlength="(int) config('ai_report.prompt.max')"
                         x-on:input="count($event)"
                         x-on:keydown.ctrl.enter.prevent="submit()"
                         x-on:keydown.meta.enter.prevent="submit()"/>
    <p wire:key="ai-report-prompt-hint" class="text-[10.5px] text-[var(--md-sys-color-on-surface-variant)]">طول درخواست از {{ $limits['prompt_min'] }} تا {{ $limits['prompt_max'] }} حرف است.</p>
    <p wire:key="ai-report-prompt-count" class="text-[10.5px] text-[var(--md-sys-color-on-surface-variant)]"><span class="tabular-nums" x-text="faCount()">{{ convertToPersian(mb_strlen((string) ($this->form->prompt ?? ''))) }}</span> / {{ $limits['prompt_max'] }} حرف</p>
    @if($refining)
        <p wire:key="ai-report-refine-hint" class="text-[10.5px] text-[var(--md-sys-color-on-surface-variant)]">این متن فقط تغییر مورد نظر را توضیح می‌دهد؛ نیازی به نوشتن دوبارهٔ کل درخواست نیست. فایل‌های پیوست نگه داشته نمی‌شوند؛ اگر به آن‌ها نیاز دارید، دوباره پیوست کنید.</p>
    @endif
    @if($blockLabel !== null)
        <div wire:key="ai-report-block-chip" class="flex items-center justify-between gap-2 px-2.5 py-1.5 rounded-lg bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">
            <span class="flex items-center gap-1.5 min-w-0 text-[11px] font-bold">
                <span class="material-symbols-rounded text-[14px]">crop</span>
                <span class="truncate">فقط «{{ $blockLabel }}» اصلاح می‌شود</span>
            </span>
            <button type="button" wire:click="clearBlock" title="بازگشت به اصلاح کل گزارش" aria-label="بازگشت به اصلاح کل گزارش"
                    class="flex items-center justify-center w-6 h-6 rounded-md hover:bg-[var(--md-sys-color-tertiary)]/20 transition-colors">
                <span class="material-symbols-rounded text-[15px]">close</span>
            </button>
        </div>
    @endif

    <x-ui.notice-card icon="shield">
        <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">متن و فایل‌های پیوست برای تولید گزارش به سرویس هوش مصنوعی خارجی ارسال می‌شود؛ اطلاعات محرمانه پیوست نکنید.</p>
    </x-ui.notice-card>

    <div class="flex flex-col gap-2">
        <div class="flex flex-wrap items-center gap-2">
            <label for="ai-report-files" tabindex="0" role="button"
                   x-on:keydown.enter.prevent="$el.control?.click()" x-on:keydown.space.prevent="$el.control?.click()"
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] text-[11.5px] font-bold hover:bg-[var(--md-sys-color-surface-container-highest)] transition-colors cursor-pointer">
                <span class="material-symbols-rounded text-[16px]">attach_file</span>
                افزودن فایل (PDF، تصویر، Word، Excel، CSV، متن)
            </label>
            <span class="text-[10.5px] text-[var(--md-sys-color-on-surface-variant)]">حداکثر {{ $limits['files'] }} فایل (PDF و تصویر هرکدام تا {{ $limits['inline_mb'] }} و روی‌هم تا {{ $limits['inline_total_mb'] }} مگابایت، سایر انواع تا {{ $limits['per_file_mb'] }} مگابایت، مجموع تا {{ $limits['total_mb'] }} مگابایت)</span>
        </div>
        <input type="file" id="ai-report-files" wire:model="form.files" multiple class="hidden"
               accept="{{ $accept }}"/>
        @error('form.files') <p wire:key="ai-report-error-files" class="text-xs text-[var(--md-sys-color-error)] animate-pulse">{{ $message }}</p> @enderror
        @error('form.files.*') <p wire:key="ai-report-error-files-item" class="text-xs text-[var(--md-sys-color-error)] animate-pulse">{{ $message }}</p> @enderror

        @if(count($this->form->files))
            <div wire:key="ai-report-staged-strip" class="flex flex-wrap items-center gap-1.5 p-2 rounded-xl bg-[color-mix(in_srgb,var(--md-sys-color-surface-variant)_50%,transparent)] border border-[var(--md-sys-color-outline-variant)]/30">
                @foreach($this->form->files as $i => $file)
                    <div wire:key="ai-report-staged-{{ $i }}"
                         class="flex items-center gap-1.5 ps-1 pe-2 py-1 rounded-lg max-w-[180px] bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/40">
                        @if(str_starts_with($file->getMimeType() ?? '', 'image/'))
                            <img wire:key="ai-report-staged-thumb-{{ $i }}" src="{{ $file->temporaryUrl() }}" class="w-5 h-5 rounded-md object-cover flex-shrink-0" alt="">
                        @else
                            <span wire:key="ai-report-staged-icon-{{ $i }}" class="material-symbols-rounded text-[13px] flex-shrink-0 text-[var(--md-sys-color-on-surface-variant)]">description</span>
                        @endif
                        <span class="text-[10px] font-medium truncate text-[var(--md-sys-color-on-surface)]">{{ $file->getClientOriginalName() }}</span>
                        <button type="button" wire:key="ai-report-staged-remove-{{ $i }}" wire:click="removeUpload('form.files', '{{ $file->getFilename() }}')"
                                aria-label="حذف {{ $file->getClientOriginalName() }}"
                                class="flex-shrink-0 text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-error)] transition-colors">
                            <span class="material-symbols-rounded text-[14px]">close</span>
                        </button>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="flex flex-wrap items-center justify-end gap-2">
        @if($refining)
            <div wire:key="ai-report-composer-refine" class="contents">
                <x-ui.buttons.form wire:click="refine" loading="generate,refine" loadingText="در حال اصلاح…" variant="primary" icon="autorenew"
                    x-bind:disabled="uploading || {{ $exhausted || $generatingElsewhere ? 'true' : 'false' }}"><span x-text="uploading ? 'در حال بارگذاری فایل…' : 'اصلاح گزارش'">اصلاح گزارش</span></x-ui.buttons.form>
            </div>
        @else
            <div wire:key="ai-report-composer-generate" class="contents">
                <x-ui.buttons.form wire:click="generate" loading="generate,refine" loadingText="در حال ساخت…" variant="primary" icon="auto_awesome"
                    x-bind:disabled="uploading || {{ $exhausted || $busy || $generatingElsewhere ? 'true' : 'false' }}"><span x-text="uploading ? 'در حال بارگذاری فایل…' : 'ساخت گزارش'">ساخت گزارش</span></x-ui.buttons.form>
            </div>
        @endif
    </div>

    @if($generatingElsewhere)
        <div wire:key="ai-report-busy-elsewhere">
            <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)]">یک گزارش در حال تولید است؛ تا آماده شدن آن، گزارش تازه‌ای ساخته نمی‌شود.</p>
        </div>
    @endif

    @if($exhausted)
        <div wire:key="ai-report-quota-exhausted">
            <x-ui.notice-card icon="error">
                <p class="text-xs font-bold text-[var(--md-sys-color-error)]">سهمیهٔ امروز شما به پایان رسیده است.</p>
                <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)]">ساخت گزارش فردا از نو شروع می‌شود.</p>
            </x-ui.notice-card>
        </div>
    @endif
    </div>
    @endif
    </div>
</div>
