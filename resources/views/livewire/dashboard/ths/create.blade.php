@php
    $aiOn = thsAiOn();
    $aiTip = fn () => 'نوشتن هوشمند · ' . convertToPersian($this->aiLeft) . ' بار دیگر امروز';
    $aiNeedArea = 'ابتدا حوزهٔ درخواست را انتخاب کنید';
    $aiLong = 'برای دریافت پیشنهاد، متن را کوتاه‌تر کنید.';
    $aiShort = 'برای استفاده از دستیار، متن را کوتاه‌تر کنید';
    $aiLimits = $presenter->aiLimits();
    $aiLimitSubject = $aiLimits['subject'];
    $aiLimitDescription = $aiLimits['description'];
@endphp

<form wire:submit.prevent="submitTicket" class="space-y-6"
      @if($aiOn) data-ai-blank-text="جاهای خالی «____» را پر یا حذف کنید."
         x-on:submit.capture="aiGuardSubmit($event, $el.dataset.aiBlankText)"
         x-on:keydown.escape="aiSuggested && !$event.isComposing && $event.keyCode !== 229 && $event.target.closest('[data-ai-blank], [data-ai-chips]') && (aiUndo(), $event.stopPropagation())" @endif>

    @if($aiOn)
        <span class="sr-only" role="status" aria-live="polite" data-ai-working="در حال نوشتن…"
              x-text="aiLoading ? $el.dataset.aiWorking : ''"></span>
    @endif

    {{-- ── Section 1: Classification ── --}}
    <div class="space-y-4">
        <x-ui.title icon="tune" title="دسته‌بندی تیکت"/>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">

            <div class="col-span-1 sm:col-span-3">
                <x-ui.forms.select label="واحد رسیدگی‌کننده" name="ticket.targetDepartment"
                                   wire:model.live="ticket.targetDepartment" icon="domain">
                    <option value="">انتخاب واحد...</option>
                    @unless(\App\Models\Department::anyHasCustomTicketOptions())
                        <option value="N/A">پشتیبانی عمومی</option>
                    @endunless
                    @foreach ($departmentOptions as $code => $name)
                        <option value="{{ $code }}">{{ $name }}</option>
                    @endforeach
                </x-ui.forms.select>
                <p class="mt-1.5 text-[11px] text-[var(--md-sys-color-on-surface-variant)]">واحدی را انتخاب کنید که باید به درخواست شما رسیدگی کند. سپس نوع و حوزهٔ درخواست را مشخص کنید.</p>
            </div>

            @if($this->ticket->targetDepartment !== '')
                <div wire:key="ths-create-fields" class="contents">
                <x-ui.forms.select label="نوع درخواست" name="ticket.requestType"
                                   wire:model.live="ticket.requestType" icon="category">
                    @foreach ($ticket->requestTypeOptions as  $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-ui.forms.select>


                <div class="space-y-1.5">
                    <x-ui.forms.select label="حوزهٔ درخواست" name="ticket.requestArea"
                                       wire:model.live="ticket.requestArea" mark
                                       :icon="$presenter->requestAreaIcon($this->ticket->requestArea, $this->ticket->targetDepartment)">
                        @foreach($requestAreas as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-ui.forms.select>

                    @if($typicalHint = $presenter->typicalHint($typicalMap, $this->ticket->requestType, $this->ticket->requestArea))
                        <p wire:key="ths-create-typical"
                           class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-1.5">
                            <span class="material-symbols-rounded text-[13px] text-[var(--md-sys-color-primary)]">schedule</span>
                            {{ $typicalHint }}؛ این زمان تقریبی است.
                        </p>
                    @endif
                </div>

                <div class="space-y-1.5">
                    <label
                        class="text-[11px] font-bold uppercase tracking-widest text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-1.5">
                        <span
                            class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-primary)]">flag</span>
                        اولویت
                    </label>
                    <div class="grid grid-cols-3 gap-1.5 h-11">
                        @foreach([
                            'low'    => [\App\Enums\TicketPriority::Low->getLabel(),    'low_priority', '--md-sys-color-secondary', '--md-sys-color-secondary-container', '--md-sys-color-on-secondary-container', '100%'],
                            'medium' => [\App\Enums\TicketPriority::Medium->getLabel(), 'drag_handle',  '--md-sys-color-tertiary',  '--md-sys-color-tertiary-container',  '--md-sys-color-on-tertiary-container',  '100%'],
                            'high'   => [\App\Enums\TicketPriority::High->getLabel(),   'priority_high','--md-sys-color-error',     '--md-sys-color-error',              '--md-sys-color-on-error',               '15%'],
                        ] as $val => [$label, $icon, $border, $bg, $text, $bgOpacity])
                            <label class="cursor-pointer" x-data>
                                <input type="radio" wire:model.live="ticket.priority" value="{{ $val }}"
                                       class="sr-only">
                                <div
                                    class="h-11 flex flex-col items-center justify-center rounded-xl border text-[10px] font-bold gap-0.5 transition-all hover:border-[var(--md-sys-color-outline)]"
                                    :style="$wire.ticket.priority === '{{ $val }}'
                                     ? 'border-color:var({{ $border }});background-color:color-mix(in srgb,var({{ $bg }}) {{ $bgOpacity }},transparent);color:var({{ $text }})'
                                     : 'border-color:var(--md-sys-color-outline-variant);background-color:var(--md-sys-color-surface-container-low);color:var(--md-sys-color-on-surface-variant)'">
                                    <span class="material-symbols-rounded text-[16px]">{{ $icon }}</span>
                                    {{ $label }}
                                </div>
                            </label>
                        @endforeach
                    </div>
                    <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)]">اولویت را بر اساس فوریت درخواست انتخاب کنید. پس از ثبت، فقط مدیر می‌تواند آن را تغییر دهد.</p>
                    @error('ticket.priority') <p
                        class="text-[10px] text-[var(--md-sys-color-error)] mt-1">{{ $message }}</p> @enderror
                </div>
                </div>
            @endif

        </div>
    </div>

    @if($this->ticket->targetDepartment !== '')
        <div wire:key="ths-create-body" class="space-y-6">
        {{-- ── Section 2: Content + Attachments ── --}}
        <div wire:key="ths-create-content" class="space-y-4">

            <x-ui.title icon="edit_note" title="موضوع و شرح درخواست"/>
            @if($aiOn)
                <p wire:key="ths-create-ai-help" class="text-[11px] text-[var(--md-sys-color-on-surface-variant)]">«نوشتن هوشمند» موضوع و شرح را پیشنهاد می‌دهد. متن و انتخاب‌های فرم به سرویس هوش مصنوعی فرستاده می‌شوند. اطلاعات محرمانه وارد نکنید.</p>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">

                <div class="lg:col-span-3 space-y-5">
                    <div @if($aiOn) class="relative" data-ai-blank="«____»" data-ai-next="ticket.description" x-bind:inert="aiLoading"
                         x-bind:class="{ 'ai-suggested': aiSuggested, 'ai-halo': aiLoading }" x-bind:aria-busy="aiLoading"
                         x-on:keydown.tab="!$event.shiftKey && aiBlanks > 0 && aiNextBlank($event.target) && $event.preventDefault()" @endif>
                        <x-ui.forms.input label="موضوع تیکت" name="ticket.subject"
                                          wire:model="ticket.subject" icon="label" mark>
                            @if($aiOn)
                                <x-slot:actions>
                                    <x-ui.forms.ai-trigger class="inset-y-0 left-0 -ml-3"
                                                           :tip="$aiTip()"
                                                           :need="$aiNeedArea"
                                                           :long="$aiLong"
                                                           :short="$aiShort"
                                                           :limit-subject="$aiLimitSubject"
                                                           :limit-description="$aiLimitDescription"/>
                                </x-slot:actions>
                            @endif
                        </x-ui.forms.input>
                        <p class="mt-1.5 text-[11px] text-[var(--md-sys-color-on-surface-variant)]">در یک جمله بنویسید چه می‌خواهید یا چه مشکلی پیش آمده است.</p>
                        @if($aiOn)
                            <x-ui.loaders.ai-shimmer x-show="aiLoading" style="display: none;" class="rounded-t-lg"/>
                        @endif
                    </div>

                    <div>
                        <div @if($aiOn) class="relative" data-ai-blank="«____»" x-bind:inert="aiLoading"
                             x-bind:class="{ 'ai-suggested': aiSuggested, 'ai-halo': aiLoading }" x-bind:aria-busy="aiLoading"
                             x-on:keydown.tab="!$event.shiftKey && aiBlanks > 0 && aiNextBlank($event.target) && $event.preventDefault()" @endif>
                            <x-ui.forms.textarea label="شرح درخواست" name="ticket.description"
                                                 wire:model="ticket.description" icon="description" rows="5" :maximizable="true" mark>
                                @if($aiOn)
                                    <x-slot:actions>
                                        <x-ui.forms.ai-trigger class="bottom-2 left-0 -ml-3"
                                                               :tip="$aiTip()"
                                                               :need="$aiNeedArea"
                                                               :long="$aiLong"
                                                               :short="$aiShort"
                                                               :limit-subject="$aiLimitSubject"
                                                               :limit-description="$aiLimitDescription"/>
                                    </x-slot:actions>
                                @endif
                            </x-ui.forms.textarea>
                            <p class="mt-1.5 text-[11px] text-[var(--md-sys-color-on-surface-variant)]">بنویسید در کدام بخش چه کاری انجام دادید و چه اتفاقی افتاد. اگر خطایی دیدید، متن آن را بنویسید یا تصویرش را پیوست کنید.</p>
                            @if($aiOn)
                                <x-ui.loaders.ai-shimmer x-show="aiLoading" style="display: none;" class="rounded-t-lg"/>
                            @endif
                        </div>

                        @if($aiOn)
                            <div wire:key="ths-create-ai-chips" data-ai-chips aria-live="polite" aria-atomic="true" x-bind:inert="aiLoading" x-show="aiSuggested || aiQuestion || aiBlankLeft() > 0 || aiReady()" style="display: none;"
                                 class="mt-2 flex flex-wrap items-center gap-2">
                                <x-ui.badge x-show="aiSuggested && aiSource === 'ai'" style="display: none;"
                                            class="!bg-[var(--ai-bg)] !text-[var(--ai-text)] animate-slide-up-fade motion-reduce:!animate-none animate-delay-0">
                                    پیش‌نویس هوش مصنوعی
                                </x-ui.badge>
                                <x-ui.badge x-show="aiSuggested && aiSource === 'skeleton'" style="display: none;"
                                            class="animate-slide-up-fade motion-reduce:!animate-none animate-delay-0">
                                    الگوی آماده
                                </x-ui.badge>
                                <button type="button" x-ref="aiAccept" x-show="aiSuggested" style="display: none;"
                                        x-on:click="aiAccept()"
                                        class="rounded-md animate-slide-up-fade motion-reduce:!animate-none animate-delay-100 transition-transform active:scale-95">
                                    <x-ui.badge variant="info">پذیرش ↵</x-ui.badge>
                                </button>
                                <button type="button" x-show="aiSuggested" style="display: none;"
                                        x-on:click="aiUndo(); aiFocus()"
                                        class="rounded-md animate-slide-up-fade motion-reduce:!animate-none animate-delay-150 transition-transform active:scale-95">
                                    <x-ui.badge>بازگشت متن قبلی Esc</x-ui.badge>
                                </button>
                                <button type="button" aria-label="نوشتن پیشنهاد دیگر" x-show="aiSuggested && !aiLoading" style="display: none;"
                                        x-on:click="aiAgain()"
                                        class="rounded-md animate-slide-up-fade motion-reduce:!animate-none animate-delay-200 transition-transform active:scale-95">
                                    <x-ui.badge class="gap-1">
                                        <span class="material-symbols-rounded text-[14px]">refresh</span>
                                        پیشنهاد دیگر
                                    </x-ui.badge>
                                </button>
                                <x-ui.badge x-show="aiReady()" style="display: none;"
                                            class="gap-1 !bg-[var(--tool-sage-bg)] !text-[var(--tool-sage-text)] animate-slide-up-fade motion-reduce:!animate-none animate-delay-300">
                                    <span class="material-symbols-rounded text-[14px]">check_circle</span>
                                    جاهای خالی تکمیل شد
                                </x-ui.badge>
                                <button type="button" aria-label="پرکردن جای خالی بعدی" x-show="aiBlankLeft() > 0" style="display: none;"
                                        x-on:click="aiJumpBlank()"
                                        class="rounded-md animate-slide-up-fade motion-reduce:!animate-none animate-delay-300 transition-transform active:scale-95">
                                    <x-ui.badge class="!bg-[var(--tool-gold-bg)] !text-[var(--tool-gold-text)]">
                                        جاهای خالی را پر کنید (<span x-text="aiBlankLeft().toLocaleString('fa-IR')"></span>)
                                    </x-ui.badge>
                                </button>
                                <button type="button" wire:key="ths-create-ai-question" x-show="aiQuestion" style="display: none;"
                                        x-on:click="aiAsk()"
                                        class="max-w-full rounded-md animate-slide-up-fade motion-reduce:!animate-none animate-delay-400 transition-transform active:scale-95">
                                    <x-ui.badge class="!bg-[var(--ai-bg)] !text-[var(--ai-text)] animate-alert-soft gap-1 whitespace-normal text-start">
                                        <span class="material-symbols-rounded text-[14px]">help</span>
                                        <span x-text="aiQuestion"></span>
                                    </x-ui.badge>
                                </button>
                                <x-ui.quota-meter wire:key="ths-ai-quota-usage" class="mr-auto w-48" :icon="null"
                                                  label="سهمیهٔ نوشتن هوشمند" aria="سهمیهٔ نوشتن هوشمند"
                                                  :used="$this->aiMeter['used']" :cap="$this->aiMeter['cap']"
                                                  :pct="$this->aiMeter['pct']" :tone="$this->aiMeter['tone']"/>
                            </div>
                        @endif
                    </div>
                </div>

                <div
                    class="lg:col-span-2 bg-[var(--md-sys-color-surface-variant)]/20 border border-[var(--md-sys-color-outline-variant)]/60 rounded-xl overflow-hidden">
                    <div
                        class="flex items-center justify-between px-4 py-3 border-b border-[var(--md-sys-color-outline-variant)]/60">
                        <div class="flex items-center gap-2">
                        <span
                            class="material-symbols-rounded text-[var(--md-sys-color-primary)] text-base">attach_file</span>
                            <span class="text-xs font-bold text-[var(--md-sys-color-on-surface)]">پیوست‌ها</span>
                            <span
                                class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] bg-[var(--md-sys-color-surface-variant)] px-2 py-0.5 rounded-lg">اختیاری</span>
                        </div>
                        <button type="button" wire:click="addFileInput"
                                class="w-7 h-7 flex items-center justify-center rounded-lg text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] active:scale-95 transition-all">
                            <span class="material-symbols-rounded text-lg">add</span>
                        </button>
                    </div>

                    <div class="p-3 space-y-2 max-h-[240px] overflow-y-auto">
                        @foreach ($ticket->fileInputs as $index => $key)
                            <div class="relative group" wire:key="upload-wrapper-{{ $key }}">
                                <div x-data="{ url:'', name:'', size:'', type:'',
                                           initFile(e) {
                                               const f=e.target.files[0]; if(!f) return;
                                               this.name=f.name; this.size=(f.size/1024/1024).toFixed(2);
                                               this.type=f.type; this.url=URL.createObjectURL(f);
                                           }}"
                                     class="flex items-center gap-2">

                                    <label for="dropzone-file-{{ $key }}"
                                           class="flex-1 flex items-center gap-3 h-[52px] px-3 border border-dashed rounded-xl cursor-pointer transition-all"
                                           :class="$wire.errors?.has?.('ticket.files.{{ $key }}')
                                           ? 'border-[var(--md-sys-color-error)] bg-[color-mix(in_srgb,var(--md-sys-color-error)_8%,transparent)]'
                                           : 'border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] hover:bg-[var(--md-sys-color-surface-container)] hover:border-[var(--md-sys-color-primary)]'">

                                        <template x-if="url && type.startsWith('image/')">
                                            <div
                                                class="w-8 h-8 rounded-lg overflow-hidden shrink-0 border border-[var(--md-sys-color-outline-variant)]">
                                                <img :src="url" class="w-full h-full object-cover">
                                            </div>
                                        </template>
                                        <template x-if="url && !type.startsWith('image/')">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-[var(--md-sys-color-secondary-container)] flex items-center justify-center shrink-0">
                                            <span
                                                class="material-symbols-rounded text-sm text-[var(--md-sys-color-on-secondary-container)]">description</span>
                                            </div>
                                        </template>
                                        <template x-if="!url">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-[var(--md-sys-color-surface-container)] flex items-center justify-center shrink-0">
                                            <span
                                                class="material-symbols-rounded text-lg text-[var(--md-sys-color-primary)]">cloud_upload</span>
                                            </div>
                                        </template>

                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-bold text-[var(--md-sys-color-on-surface)] truncate"
                                               x-text="name || 'انتخاب فایل'"></p>
                                            <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]"
                                               x-show="size" x-text="size + ' MB'"></p>
                                            <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]"
                                               x-show="!size">حداکثر ۱۰ مگابایت</p>
                                        </div>

                                        <button type="button" x-show="url"
                                                @click.prevent="url='';name='';size='';type='';
                                                             $el.closest('label').querySelector('input').value='';
                                                             $wire.set('ticket.files.{{ $key }}', null)"
                                                class="shrink-0 w-6 h-6 flex items-center justify-center rounded-lg text-[var(--md-sys-color-error)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-error)_12%,transparent)] transition-colors">
                                            <span class="material-symbols-rounded text-sm">close</span>
                                        </button>

                                        <input id="dropzone-file-{{ $key }}" type="file" class="hidden"
                                               wire:model="ticket.files.{{ $key }}" @change="initFile"/>
                                    </label>

                                    @if($index > 0)
                                        <button type="button" wire:click="removeFileInput('{{ $key }}')"
                                                class="shrink-0 w-7 h-7 flex items-center justify-center rounded-lg bg-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-error)] max-sm:opacity-100 opacity-0 group-hover:opacity-100 active:scale-95 transition-all shadow-sm">
                                            <span class="material-symbols-rounded text-sm">close</span>
                                        </button>
                                    @endif
                                </div>
                                @error('ticket.files.' . $key) <p
                                    class="text-[10px] text-[var(--md-sys-color-error)] mt-1 pr-1">{{ $message }}</p> @enderror
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>

        {{-- ── Actions ── --}}
        <div
            class="flex items-center justify-between gap-3 pt-2 border-t border-[var(--md-sys-color-outline-variant)]/50">
            <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] hidden sm:flex items-center gap-1.5">
                <span class="material-symbols-rounded text-[13px] text-[var(--md-sys-color-primary)]">info</span>
                کادرهای ستاره‌دار را پر کنید.
            </p>
            <x-ui.buttons.submit
                target="submitTicket"
                text="ثبت درخواست"
                loading-text="در حال ارسال..."
                icon="send"
                class="px-6 h-11 font-bold rounded-xl shadow-md mr-auto"
            />
        </div>
        </div>
    @endif
</form>
