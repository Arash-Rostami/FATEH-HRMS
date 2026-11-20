@use('App\Livewire\Dashboard\Correspondence\Forms\LetterForm')
@use('App\Models\Correspondence')
@use('App\Models\Letterhead')
@use('App\Support\CorrespondenceAccessPolicy')
@if(isset($partyRows))
    @php
        $people = ($tools ?? $presenter->editorTools($this->people->all()))['people'];
        $roles = $presenter->roleLabels();
        $deliveries = $presenter->deliveryLabels();
        $mailOn = correspondenceMailOn();
    @endphp
    @foreach($partyRows as $index => $party)
        @php
            $at = $partyModel . '.' . $index;
        @endphp
        <div wire:key="correspondence-{{ $partyKey }}-party-{{ $index }}" class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] p-3 grid grid-cols-1 md:grid-cols-2 gap-2">
            @if($partyLock)
                <div wire:key="correspondence-{{ $partyKey }}-party-locked-{{ $index }}" class="contents">
                    <x-ui.forms.select label="شخص داخل سازمان" name="{{ $at }}.user_id" wire:model="{{ $at }}.user_id" disabled>
                        <option value="">—</option>
                        @foreach($people as $person)
                            <option value="{{ $person['value'] }}">{{ $person['label'] }}</option>
                        @endforeach
                    </x-ui.forms.select>
                </div>
            @else
                <div wire:key="correspondence-{{ $partyKey }}-party-pick-{{ $index }}-{{ (int) ($party['user_id'] ?? 0) }}" class="contents">
                    <x-ui.forms.select label="شخص داخل سازمان" name="{{ $at }}.user_id" wire:model.live="{{ $at }}.user_id">
                        <option value="">—</option>
                        @foreach($people as $person)
                            <option value="{{ $person['value'] }}">{{ $person['label'] }}</option>
                        @endforeach
                    </x-ui.forms.select>
                </div>
            @endif
            <x-ui.forms.select label="وظیفهٔ فرد" name="{{ $at }}.role" wire:model.live="{{ $at }}.role" :disabled="$partyLock">
                @foreach($roles as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </x-ui.forms.select>
            @if(($party['role'] ?? '') === 'fyi')
                <label wire:key="correspondence-{{ $partyKey }}-party-cc-{{ $index }}" class="flex items-center gap-2 text-sm">
                    <x-ui.forms.checkbox name="{{ $at }}.print_in_cc" :disabled="$partyLock"/>
                    {{ __('resources/correspondence/strings.fields.print_in_cc') }}
                </label>
                @if(!filled($party['user_id'] ?? null))
                    <div wire:key="correspondence-{{ $partyKey }}-party-external-{{ $index }}" class="contents">
                        <x-ui.forms.input label="نام" name="{{ $at }}.name" wire:model="{{ $at }}.name" :disabled="$partyLock"/>
                        <x-ui.forms.input label="سازمان" name="{{ $at }}.org" wire:model="{{ $at }}.org" :disabled="$partyLock"/>
                        @if($mailOn)
                            <div wire:key="correspondence-{{ $partyKey }}-party-email-{{ $index }}" class="contents">
                                <x-ui.forms.input label="ایمیل" name="{{ $at }}.email" type="email" dir="ltr" wire:model="{{ $at }}.email" :disabled="$partyLock"/>
                            </div>
                        @endif
                        <x-ui.forms.input label="سمت" name="{{ $at }}.title" wire:model="{{ $at }}.title" :disabled="$partyLock"/>
                        <x-ui.forms.input label="نشانی" name="{{ $at }}.address" wire:model="{{ $at }}.address" :disabled="$partyLock"/>
                        @if($mailOn)
                            <div wire:key="correspondence-{{ $partyKey }}-party-delivery-{{ $index }}" class="contents">
                                <x-ui.forms.select label="روش تحویل پس از تأیید" name="{{ $at }}.delivery" wire:model="{{ $at }}.delivery" :disabled="$partyLock">
                                    @foreach($deliveries as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </x-ui.forms.select>
                            </div>
                        @endif
                    </div>
                @endif
            @endif
            @unless($partyLock)
                <div wire:key="correspondence-{{ $partyKey }}-party-actions-{{ $index }}" class="flex gap-1">
                    @if($partyMove)
                        <div wire:key="correspondence-{{ $partyKey }}-party-move-{{ $index }}" class="contents">
                            <x-ui.buttons.form type="button" variant="ghost" size="icon" icon="arrow_upward" title="بالا" aria-label="بالا" class="!h-8 !w-8 !p-0 !rounded-lg" wire:click="moveParty({{ (int) $index }}, -1)"/>
                            <x-ui.buttons.form type="button" variant="ghost" size="icon" icon="arrow_downward" title="پایین" aria-label="پایین" class="!h-8 !w-8 !p-0 !rounded-lg" wire:click="moveParty({{ (int) $index }}, 1)"/>
                        </div>
                    @endif
                    <x-ui.buttons.form type="button" variant="ghost" size="icon" icon="close" title="حذف" aria-label="حذف" class="!h-8 !w-8 !p-0 !rounded-lg" wire:click="{{ $partyRemove }}({{ (int) $index }})"/>
                </div>
            @endunless
        </div>
    @endforeach
@else
@php
    $lockHead = !$state['structural'] || !$state['canSave'];
    $lockAll = !$state['canSave'];
    $tools = $presenter->editorTools($this->people->all());
    $incoming = LetterForm::directionOf($form->type) === Correspondence::DIRECTION_IN;
    $private = $form->classification !== Correspondence::CLASSIFICATION_NORMAL;
    $heads = $this->letterheads->where('kind', $incoming ? Letterhead::KIND_REGISTRY : Letterhead::KIND_HEAD);
    $size = $this->letterheads->firstWhere('id', (int) $form->letterheadId)?->size;
    $savedFiles = $letter && is_array($letter->spec['attachments'] ?? null) ? array_values($letter->spec['attachments']) : [];
    $fileNames = $presenter->attachmentNames($letter);
    $estimate = $presenter->pageEstimate($form->blocks, $size, $savedFiles);
    $route = $presenter->routeSummary($form->parties, $form->classification, $form->signMode, $form->routeMode);
    $elsewhere = $this->generatingId !== null && $this->generatingId !== (int) $letterId;
    $stamp = count($form->blocks) . '-' . substr($contentHash, 0, 6);
    $buttons = $tools['buttons'];
    $tableItems = $tools['table'];
@endphp

<div wire:key="correspondence-composer"
     x-data="correspondenceComposer()"
     data-locked="{{ $lockAll ? 1 : 0 }}"
     data-normal="{{ Correspondence::CLASSIFICATION_NORMAL }}"
     class="print:hidden flex flex-col gap-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm p-4">
    @if($state['smartOn'])
        @php
            $quota = $this->quota;
        @endphp
        <x-ui.quota-meter wire:key="correspondence-quota-usage" class="print:hidden"
            label="سهمیهٔ امروز" aria="سهمیهٔ امروز" bar-key="correspondence-quota-bar" warn-key="correspondence-quota-left"
            :used="$quota['used']" :cap="$quota['cap']" :pct="(int) $state['quotaPct']" :tone="$state['quotaTone']"
            :warn="! $state['exhausted'] && $state['quotaPct'] >= 80 ? 'فقط '.convertToPersian((string) $state['quotaLeft']).' بار دیگر امروز می‌توانید نامهٔ هوشمند بسازید.' : null" />
    @endif
    @if($letter && $presenter->hasUnfilled($letter))
        <p wire:key="correspondence-unfilled" class="text-sm text-[var(--md-sys-color-error)]">{{ __('resources/correspondence/strings.errors.placeholders') }}</p>
    @endif
    @error('form')
        <p wire:key="correspondence-form-error" class="text-sm text-[var(--md-sys-color-error)]">{{ $message }}</p>
    @enderror
    @if($elsewhere)
        <div wire:key="correspondence-generating-elsewhere" class="flex flex-wrap items-center gap-2 text-sm text-[var(--md-sys-color-on-surface-variant)]">
            <span>یک نامهٔ هوشمند دیگر در حال تولید است.</span>
            <x-ui.buttons.form type="button" variant="ghost" icon="visibility" wire:click="$parent.open({{ (int) $this->generatingId }})">نمایش آن</x-ui.buttons.form>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <x-ui.forms.select label="{{ __('resources/correspondence/strings.fields.type') }}" name="form.type" wire:model.live="form.type" :disabled="$lockHead">
            @foreach($state['types'] as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </x-ui.forms.select>
        <x-ui.forms.select label="سربرگ" name="form.letterheadId" wire:model="form.letterheadId" :disabled="$lockHead">
            <option value="">—</option>
            @foreach($heads as $head)
                <option value="{{ $head->id }}">{{ $head->name }}</option>
            @endforeach
        </x-ui.forms.select>
        <x-ui.forms.select label="{{ __('resources/correspondence/strings.fields.classification') }}" name="form.classification" wire:model.live="form.classification" :disabled="$lockHead" x-on:change="classify($event.target.value)">
            @foreach((array) config('ai.correspondence.classifications') as $key)
                <option value="{{ $key }}">{{ __('resources/correspondence/strings.classification.'.$key) }}</option>
            @endforeach
        </x-ui.forms.select>
        <x-ui.forms.select label="{{ __('resources/correspondence/strings.fields.urgency') }}" name="form.urgency" wire:model="form.urgency" :disabled="$lockHead">
            @foreach((array) config('ai.correspondence.urgencies') as $key)
                <option value="{{ $key }}">{{ __('resources/correspondence/strings.urgency.'.$key) }}</option>
            @endforeach
        </x-ui.forms.select>
        <x-ui.forms.select label="نوع امضا" name="form.signMode" wire:model="form.signMode" :disabled="$lockHead">
            @foreach(LetterForm::SIGN_MODES as $key)
                <option value="{{ $key }}">{{ __('resources/correspondence/strings.sign_mode.'.$key) }}</option>
            @endforeach
        </x-ui.forms.select>
        <x-ui.forms.select label="ترتیب گرفتن امضاها" name="form.routeMode" wire:model="form.routeMode" :disabled="$lockHead">
            @foreach($presenter->routeModeLabels() as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </x-ui.forms.select>
        @foreach(array_filter(['deadline' => 'مهلت پیگیری (اختیاری)', 'theirDate' => $incoming ? 'تاریخ نامهٔ فرستنده' : null]) as $field => $label)
            <div wire:key="correspondence-date-{{ $field }}-{{ $form->{$field} }}"
                 data-field="{{ $field }}"
                 x-data="jalaliField()"
                 class="grid grid-cols-3 gap-2 items-start">
                <p class="col-span-3 text-xs font-bold text-[var(--md-sys-color-on-surface-variant)]">{{ $label }}</p>
                <x-ui.forms.select label="سال" name="correspondence-{{ $field }}-year" x-model="y" x-on:change="push()" :disabled="$lockHead">
                    <option value=""></option>
                    @foreach($field === 'deadline' ? range(jNow() - 1, jNow() + 3) : range(jNow() - 10, jNow() + 1) as $year)
                        <option value="{{ $year }}">{{ convertToPersian($year) }}</option>
                    @endforeach
                </x-ui.forms.select>
                <x-ui.forms.select label="ماه" name="correspondence-{{ $field }}-month" x-model="m" x-on:change="push()" :disabled="$lockHead">
                    <option value=""></option>
                    @foreach(['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'] as $i => $monthName)
                        <option value="{{ $i + 1 }}">{{ $monthName }}</option>
                    @endforeach
                </x-ui.forms.select>
                <x-ui.forms.select label="روز" name="correspondence-{{ $field }}-day" x-model="d" x-on:change="push()" :disabled="$lockHead">
                    <option value=""></option>
                    @foreach(range(1, 31) as $day)
                        <option value="{{ $day }}">{{ convertToPersian($day) }}</option>
                    @endforeach
                </x-ui.forms.select>
                @error('form.' . $field)
                    <p wire:key="correspondence-date-error-{{ $field }}" class="col-span-3 text-xs text-[var(--md-sys-color-error)] animate-pulse">{{ $message }}</p>
                @enderror
            </div>
        @endforeach
        <div class="grid grid-cols-2 gap-2">
            <x-ui.forms.select label="مورد مرتبط" name="form.relatedType" wire:model="form.relatedType" :disabled="$lockHead">
                <option value="">—</option>
                @foreach((array) config('ai.correspondence.related_types') as $key)
                    <option value="{{ $key }}">{{ __('resources/correspondence/strings.related.'.$key) }}</option>
                @endforeach
            </x-ui.forms.select>
            <x-ui.forms.input label="شناسهٔ مورد مرتبط" name="form.relatedId" type="number" dir="ltr" wire:model="form.relatedId" :disabled="$lockHead"/>
            <p class="col-span-2 text-xs text-[var(--md-sys-color-on-surface-variant)]">اختیاری است؛ برای ارتباط نامه با یک پروژه، تیکت یا مخاطب، نوع آن و شناسهٔ ثبت‌شده در همان بخش را وارد کنید.</p>
        </div>
        <x-ui.forms.tags label="برچسب‌ها" name="form.tags" prefix="correspondence-tag" :tags="$form->tags" :max="12" :max-length="32" :disabled="$lockHead" class="md:col-span-2"/>
    </div>

    <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">مهلت، آخرین روز پیگیری نامه است و نامه را خودکار حذف یا بسته نمی‌کند. برای نامهٔ محرمانه و فوق‌محرمانه، نوشتن هوشمند و ارسال ایمیل در دسترس نیست. در امضای دستی هم تأییدهای سامانه انجام می‌شود؛ امضای روی کاغذ را پس از تأیید نهایی می‌گیرید.</p>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <x-ui.forms.input label="سازمان گیرنده" name="form.recipientOrg" wire:model="form.recipientOrg" :disabled="$lockHead"/>
        <x-ui.forms.input label="شخص گیرنده" name="form.recipientPerson" wire:model="form.recipientPerson" :disabled="$lockHead"/>
        <x-ui.forms.input label="سمت گیرنده" name="form.recipientTitle" wire:model="form.recipientTitle" :disabled="$lockHead"/>
    </div>
    <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">این مشخصات در متن بالای نامه چاپ می‌شود. برای دسترسی همکاران یا ارسال به گیرندهٔ بیرونی، افراد را در بخش «افراد و ترتیب امضا» هم اضافه کنید.</p>

    <x-ui.forms.input label="{{ __('resources/correspondence/strings.fields.subject') }}" name="form.subject" wire:model="form.subject" :disabled="$lockAll"
                      data-private="{{ $private ? 1 : 0 }}" data-fresh="{{ $letter ? 0 : 1 }}"
                      x-init="initSubject($el)"
                      x-on:input.debounce.500ms="rememberSubject($el)"/>

    @if($incoming)
        <div wire:key="correspondence-incoming" class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] p-3 flex flex-col gap-3">
            <p class="text-sm font-bold">ثبت نامهٔ دریافتی</p>
            <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">شماره و تاریخ را از نامهٔ اصلی فرستنده بردارید. با «ثبت وصول»، سامانه شمارهٔ ثبت داخلی می‌دهد؛ ارجاع نامه برای اقدام، مرحلهٔ جداگانه‌ای است.</p>
            @if($this->incomingDuplicate)
                <p wire:key="correspondence-duplicate" class="text-sm text-[var(--md-sys-color-error)]">نامه‌ای با همین شماره و تاریخ فرستنده قبلاً ثبت شده است.</p>
            @endif
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <x-ui.forms.input label="شماره نامهٔ فرستنده" name="form.theirNumber" dir="ltr" wire:model="form.theirNumber" :disabled="$lockHead"/>
                <x-ui.forms.input label="فرستنده" name="form.sender" wire:model="form.sender" :disabled="$lockHead"/>
                <x-ui.forms.select label="دستهٔ فرستنده" name="form.senderCategory" wire:model.live="form.senderCategory" :disabled="$lockHead">
                    @foreach(LetterForm::CATEGORIES as $key)
                        <option value="{{ $key }}">{{ __('resources/correspondence/strings.sender_category.'.$key) }}</option>
                    @endforeach
                </x-ui.forms.select>
            </div>
        </div>
    @endif

    <div class="flex flex-wrap items-center gap-2">
        <span class="text-sm font-bold {{ $estimate['warn'] ? 'text-[var(--md-sys-color-error)]' : 'text-[var(--md-sys-color-on-surface-variant)]' }}">{{ $estimate['label'] }}</span>
        <span class="text-xs text-[var(--md-sys-color-on-surface-variant)]">پیشنهاد {{ $estimate['suggest_label'] }}</span>
        <x-ui.buttons.form type="button" variant="ghost" icon="add" wire:click="addBlock" :disabled="$lockHead">افزودن بخش به متن</x-ui.buttons.form>
    </div>
    @error('form.blocks')
        <p wire:key="correspondence-blocks-error" class="text-xs text-[var(--md-sys-color-error)] animate-pulse">{{ $message }}</p>
    @enderror
    @error('form.blocks.*')
        <p wire:key="correspondence-blocks-star-error" class="text-xs text-[var(--md-sys-color-error)] animate-pulse">{{ $message }}</p>
    @enderror

    @foreach($form->blocks as $index => $block)
        <div wire:key="correspondence-block-{{ $index }}" class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container-low)] p-3 flex flex-col gap-2">
            <div class="flex items-center justify-between gap-2">
                @if(($block['type'] ?? '') === 'image')
                    <p wire:key="correspondence-block-heading-image-{{ $index }}" class="text-sm font-bold">تصویر</p>
                @else
                    <div wire:key="correspondence-block-heading-type-{{ $index }}" class="contents">
                        <x-ui.forms.select label="نوع بخش" name="form.blocks.{{ $index }}.type" wire:model.live="form.blocks.{{ $index }}.type" :disabled="$lockHead">
                            <option value="html">متن با ابزار ویرایش</option>
                            <option value="text">متن ساده</option>
                        </x-ui.forms.select>
                    </div>
                @endif
                <x-ui.buttons.form type="button" variant="ghost" size="icon" icon="close" title="حذف بخش" aria-label="حذف بخش" class="!h-8 !w-8 !p-0 !rounded-lg" x-on:click="{{ confirmDialog('removeBlock', 'حذف بخش', 'این بخش حذف شود؟', (int) $index, 'dispatch', 'danger') }}" :disabled="$lockHead"/>
            </div>
            <x-ui.forms.input label="عنوان بخش (اختیاری)" name="form.blocks.{{ $index }}.heading" wire:model="form.blocks.{{ $index }}.heading" :disabled="$lockAll"/>
            @if(($block['type'] ?? '') === 'image')
                @php
                    $thumb = $presenter->blockImage($letter, $block);
                @endphp
                <div wire:key="correspondence-block-image-{{ $index }}" class="grid grid-cols-1 md:grid-cols-3 gap-2">
                    @if($thumb !== '')
                        <img wire:key="correspondence-block-thumb-{{ $index }}" src="{{ $thumb }}" alt="" loading="lazy" class="md:col-span-3 max-h-40 w-auto rounded-xl border border-[var(--md-sys-color-outline-variant)] object-contain">
                    @endif
                    <x-ui.forms.input label="شرح" name="form.blocks.{{ $index }}.caption" wire:model="form.blocks.{{ $index }}.caption" :disabled="$lockAll"/>
                    <x-ui.forms.select label="اندازه" name="form.blocks.{{ $index }}.size" wire:model="form.blocks.{{ $index }}.size" :disabled="$lockAll">
                        <option value="small">کوچک</option>
                        <option value="medium">متوسط</option>
                        <option value="full">تمام‌عرض</option>
                    </x-ui.forms.select>
                    <x-ui.forms.select label="چیدمان" name="form.blocks.{{ $index }}.align" wire:model="form.blocks.{{ $index }}.align" :disabled="$lockAll">
                        <option value="right">راست</option>
                        <option value="center">وسط</option>
                        <option value="left">چپ</option>
                    </x-ui.forms.select>
                </div>
            @elseif(($block['type'] ?? '') === 'text')
                <div wire:key="correspondence-block-text-{{ $index }}">
                    <x-ui.forms.textarea label="متن" name="form.blocks.{{ $index }}.text" wire:model="form.blocks.{{ $index }}.text" :disabled="$lockAll"/>
                </div>
            @else
                <div wire:key="correspondence-block-html-{{ $index }}-{{ $stamp }}"
                     x-data="letterBlockEditor()"
                     data-letter-editor
                     data-model="form.blocks.{{ $index }}.html"
                     data-value="{{ $block['html'] ?? '' }}"
                     data-dir="{{ $presenter->editorDir($block['html'] ?? '') }}"
                     data-tokens="{{ json_encode($tools['tokens'], JSON_UNESCAPED_UNICODE) }}"
                     data-link-error="نشانی پیوند را کامل وارد کنید؛ فقط نشانی سایت با http یا https و پیوند ایمیل با mailto پذیرفته می‌شود."
                     data-paste-warning="متن کپی‌شده خیلی حجیم بود؛ فقط متن آن بدون قالب‌بندی درج شد."
                     data-unit="بایت"
                     wire:ignore.self
                     x-bind:data-fullscreen="fullscreen"
                     x-on:keydown.window.escape="close()"
                     class="flex flex-col gap-2">
                    <div x-show="fullscreen" style="display: none;" class="flex items-center justify-between pb-3 border-b border-[var(--md-sys-color-outline-variant)]/30">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">edit</span>
                            <span class="text-[var(--md-sys-color-on-surface)] font-medium">متن نامه</span>
                            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-surface-variant)]">fullscreen</span>
                        </div>
                        <button type="button" title="بستن" x-on:click="close()" class="p-2 !pb-0 rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-variant-hover)] transition-colors">
                            <span class="material-symbols-rounded text-[20px]">close</span>
                        </button>
                    </div>
                    <div class="flex flex-wrap items-center gap-1 rounded-xl bg-[var(--md-sys-color-surface-container)] p-1" role="toolbar" aria-label="ابزار متن">
                        @foreach($buttons as $group)
                            <div class="flex items-center gap-1 {{ $loop->first ? '' : 'ms-1 ps-1 border-s border-[var(--md-sys-color-outline-variant)]' }}">
                                @foreach($group as [$call, $icon, $title, $active])
                                    <x-ui.buttons.form type="button" variant="ghost" size="icon" :icon="$icon" :title="$title" :aria-label="$title" class="!h-8 !w-8 !p-0 !rounded-lg aria-pressed:bg-[var(--md-sys-color-primary-container)]" @mousedown.prevent="" x-on:click="{{ $call }}" x-bind:aria-pressed="{{ $active }}"/>
                                @endforeach
                            </div>
                        @endforeach

                        <x-ui.modals.popover alignment="top-full right-0 origin-top-right">
                            <x-slot:trigger>
                                <x-ui.buttons.form type="button" variant="ghost" size="icon" icon="title" title="عنوان" aria-label="عنوان" class="!h-8 !w-8 !p-0 !rounded-lg" @mousedown.prevent=""/>
                            </x-slot:trigger>
                            <x-slot:content>
                                <x-ui.modals.popover-item icon="text_fields" label="متن عادی" @mousedown.prevent="" x-on:click="heading(0)"/>
                                @foreach([2, 3, 4] as $level)
                                    <x-ui.modals.popover-item icon="title" label="عنوان {{ convertToPersian($level) }}" @mousedown.prevent="" x-on:click="heading({{ $level }})" :last="$loop->last"/>
                                @endforeach
                            </x-slot:content>
                        </x-ui.modals.popover>

                        <x-ui.modals.popover alignment="top-full right-0 origin-top-right">
                            <x-slot:trigger>
                                <x-ui.buttons.form type="button" variant="ghost" size="icon" icon="text_fields" title="قلم" aria-label="قلم" class="!h-8 !w-8 !p-0 !rounded-lg" @mousedown.prevent=""/>
                            </x-slot:trigger>
                            <x-slot:content>
                                @foreach($tools['fonts'] as $family => $fontLabel)
                                    <x-ui.modals.popover-item icon="text_fields" :label="$fontLabel" data-family="{{ $family }}" @mousedown.prevent="" x-on:click="fontFamily($el.dataset.family)" :last="$loop->last"/>
                                @endforeach
                            </x-slot:content>
                        </x-ui.modals.popover>

                        <x-ui.modals.popover alignment="top-full right-0 origin-top-right">
                            <x-slot:trigger>
                                <x-ui.buttons.form type="button" variant="ghost" size="icon" icon="format_size" title="اندازه" aria-label="اندازه" class="!h-8 !w-8 !p-0 !rounded-lg" @mousedown.prevent=""/>
                            </x-slot:trigger>
                            <x-slot:content>
                                @foreach($tools['sizes'] as $step)
                                    <x-ui.modals.popover-item icon="format_size" label="{{ convertToPersian($step) }} پوینت" @mousedown.prevent="" x-on:click="size('{{ $step }}pt')" :last="$loop->last"/>
                                @endforeach
                            </x-slot:content>
                        </x-ui.modals.popover>

                        <x-ui.modals.popover alignment="top-full right-0 origin-top-right">
                            <x-slot:trigger>
                                <x-ui.buttons.form type="button" variant="ghost" size="icon" icon="format_color_text" title="رنگ متن" aria-label="رنگ متن" class="!h-8 !w-8 !p-0 !rounded-lg" @mousedown.prevent=""/>
                            </x-slot:trigger>
                            <x-slot:content>
                                <div class="flex flex-wrap gap-2 p-3">
                                    @foreach($tools['colors'] as $hex => $colorLabel)
                                        <x-ui.buttons.form type="button" variant="none" title="{{ $colorLabel }}" aria-label="{{ $colorLabel }}" data-hex="{{ $hex }}" @mousedown.prevent="" x-on:click="color($el.dataset.hex)" class="h-8 w-8 rounded-lg border border-[var(--md-sys-color-outline-variant)]" style="background-color: {{ $hex }}"/>
                                    @endforeach
                                </div>
                            </x-slot:content>
                        </x-ui.modals.popover>

                        <x-ui.modals.popover alignment="top-full right-0 origin-top-right">
                            <x-slot:trigger>
                                <x-ui.buttons.form type="button" variant="ghost" size="icon" icon="ink_highlighter" title="هایلایت" aria-label="هایلایت" class="!h-8 !w-8 !p-0 !rounded-lg aria-pressed:bg-[var(--md-sys-color-primary-container)]" @mousedown.prevent="" x-bind:aria-pressed="isActive('highlight')"/>
                            </x-slot:trigger>
                            <x-slot:content>
                                <div class="flex flex-wrap gap-2 p-3">
                                    @foreach($tools['fills'] as $hex => $fillLabel)
                                        <x-ui.buttons.form type="button" variant="none" title="{{ $fillLabel }}" aria-label="{{ $fillLabel }}" data-hex="{{ $hex }}" @mousedown.prevent="" x-on:click="highlight($el.dataset.hex)" class="h-8 w-8 rounded-lg border border-[var(--md-sys-color-outline-variant)]" style="background-color: {{ $hex }}"/>
                                    @endforeach
                                    <x-ui.buttons.form type="button" variant="ghost" size="icon" icon="format_color_reset" title="بدون هایلایت" aria-label="بدون هایلایت" class="!h-8 !w-8 !p-0 !rounded-lg" @mousedown.prevent="" x-on:click="highlight('')"/>
                                </div>
                            </x-slot:content>
                        </x-ui.modals.popover>

                        <x-ui.modals.popover alignment="top-full right-0 origin-top-right">
                            <x-slot:trigger>
                                <x-ui.buttons.form type="button" variant="ghost" size="icon" icon="link" title="پیوند" aria-label="پیوند" class="!h-8 !w-8 !p-0 !rounded-lg aria-pressed:bg-[var(--md-sys-color-primary-container)]" @mousedown.prevent="" x-on:click="prepareLink()" x-bind:aria-pressed="isActive('link')"/>
                            </x-slot:trigger>
                            <x-slot:content>
                                <div data-link-form class="flex flex-col gap-2 p-3">
                                    <div x-on:click.stop>
                                        <x-ui.forms.input label="نشانی پیوند" name="correspondence-link-{{ $index }}" dir="ltr" x-model="linkUrl" x-on:keydown.enter.prevent="submitLink($event)"/>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <x-ui.buttons.form type="button" variant="tonal" size="icon" icon="check" title="اعمال پیوند" aria-label="اعمال پیوند" data-link-apply class="!h-8 !w-8 !p-0 !rounded-lg" @mousedown.prevent="" x-on:click="applyLink()"/>
                                        <x-ui.buttons.form type="button" variant="ghost" size="icon" icon="link_off" title="حذف پیوند" aria-label="حذف پیوند" class="!h-8 !w-8 !p-0 !rounded-lg" @mousedown.prevent="" x-on:click="removeLink()"/>
                                    </div>
                                </div>
                            </x-slot:content>
                        </x-ui.modals.popover>

                        <x-ui.modals.popover alignment="top-full right-0 origin-top-right">
                            <x-slot:trigger>
                                <x-ui.buttons.form type="button" variant="ghost" size="icon" icon="table" title="جدول" aria-label="جدول" class="!h-8 !w-8 !p-0 !rounded-lg" @mousedown.prevent=""/>
                            </x-slot:trigger>
                            <x-slot:content>
                                <x-ui.modals.popover-item icon="table" label="جدول تازه ۳×۳" @mousedown.prevent="" x-on:click="table(3, 3)"/>
                                @foreach($tableItems as [$call, $icon, $itemLabel])
                                    <x-ui.modals.popover-item :icon="$icon" :label="$itemLabel" @mousedown.prevent="" x-on:click="{{ $call }}"/>
                                @endforeach
                                <div class="flex items-center gap-2 px-4 py-3">
                                    <span class="material-symbols-rounded !text-[20px] text-[var(--md-sys-color-primary)]">format_color_fill</span>
                                    @foreach($tools['fills'] as $hex => $fillLabel)
                                        <x-ui.buttons.form type="button" variant="none" title="{{ $fillLabel }}" aria-label="{{ $fillLabel }}" data-hex="{{ $hex }}" @mousedown.prevent="" x-on:click="cellBg($el.dataset.hex)" class="h-8 w-8 rounded-lg border border-[var(--md-sys-color-outline-variant)]" style="background-color: {{ $hex }}"/>
                                    @endforeach
                                </div>
                            </x-slot:content>
                        </x-ui.modals.popover>

                        <x-ui.modals.popover alignment="top-full right-0 origin-top-right">
                            <x-slot:trigger>
                                <x-ui.buttons.form type="button" variant="ghost" size="icon" icon="data_object" title="درج جای‌خالی" aria-label="درج جای‌خالی" class="!h-8 !w-8 !p-0 !rounded-lg" @mousedown.prevent=""/>
                            </x-slot:trigger>
                            <x-slot:content>
                                @foreach($tools['tokens'] as $token)
                                    <x-ui.modals.popover-item icon="data_object" :label="$token" data-token="{{ $token }}" @mousedown.prevent="" x-on:click="insertToken($el.dataset.token)" :last="$loop->last"/>
                                @endforeach
                            </x-slot:content>
                        </x-ui.modals.popover>

                        <x-ui.buttons.form type="button" variant="ghost" size="icon" icon="undo" title="واگرد" aria-label="واگرد" class="!h-8 !w-8 !p-0 !rounded-lg" @mousedown.prevent="" x-on:click="undo()" x-bind:disabled="undoOff"/>
                        <x-ui.buttons.form type="button" variant="ghost" size="icon" icon="redo" title="از نو" aria-label="از نو" class="!h-8 !w-8 !p-0 !rounded-lg" @mousedown.prevent="" x-on:click="redo()" x-bind:disabled="redoOff"/>
                        <x-ui.buttons.form type="button" variant="ghost" size="icon" icon="delete_sweep" title="پاک‌کردن همهٔ متن" aria-label="پاک‌کردن همهٔ متن" class="!h-8 !w-8 !p-0 !rounded-lg" @mousedown.prevent="" x-on:click="clear()"/>
                    </div>
                    <div wire:ignore class="correspondence-editor relative min-h-32 rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] p-3 pl-12">
                        <x-ui.forms.maximize-trigger class="top-3 left-2"/>
                        <div x-ref="surface"></div>
                    </div>
                    <p class="text-xs text-[var(--md-sys-color-on-surface-variant)] aria-invalid:text-[var(--md-sys-color-error)]" x-bind:aria-invalid="over" x-text="counter"></p>
                </div>
            @endif
        </div>
    @endforeach

    <div class="flex flex-wrap items-center gap-2">
        <label for="correspondence-image" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl text-sm font-bold bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-surface-variant)] cursor-pointer">
            <span class="material-symbols-rounded !text-[20px]">image</span>
            افزودن تصویر داخل متن
        </label>
        <input id="correspondence-image" type="file" wire:model="form.image" accept="image/jpeg,image/png,image/webp" class="hidden" @disabled($lockHead)>
        <label for="correspondence-files" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl text-sm font-bold bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)] cursor-pointer">
            <span class="material-symbols-rounded !text-[20px]">attach_file</span>
            انتخاب فایل پیوست
        </label>
        <input id="correspondence-files" type="file" wire:model="form.files" multiple class="hidden" @disabled($lockAll)>
        @if($form->image)
            <span wire:key="correspondence-image-pending" class="text-xs text-[var(--md-sys-color-on-surface-variant)]">تصویر آمادهٔ ذخیره است.</span>
        @endif
        @if($form->files !== [])
            <span wire:key="correspondence-files-pending" class="text-xs text-[var(--md-sys-color-on-surface-variant)]">{{ convertToPersian(count($form->files)) }} پیوست آمادهٔ ذخیره است.</span>
        @endif
        <div wire:loading.delay wire:target="form.files, form.image">
            <x-ui.loaders.bar/>
        </div>
    </div>
    <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">تصویر داخل متن در خود نامه نمایش داده می‌شود؛ پیوست، فایل جداگانهٔ همراه نامه است. پس از انتخاب، «ذخیره» را بزنید تا فایل‌ها ثبت شوند. {{ $state['rules']['فایل‌ها'] }}</p>
    @if($form->image)
        <div wire:key="correspondence-image-options" class="grid grid-cols-1 md:grid-cols-3 gap-2">
            <x-ui.forms.input label="شرح تصویر تازه" name="form.imageCaption" wire:model="form.imageCaption" :disabled="$lockAll"/>
            <x-ui.forms.select label="اندازهٔ تصویر تازه" name="form.imageSize" wire:model="form.imageSize" :disabled="$lockAll">
                <option value="small">کوچک</option>
                <option value="medium">متوسط</option>
                <option value="full">تمام‌عرض</option>
            </x-ui.forms.select>
            <x-ui.forms.select label="چیدمان تصویر تازه" name="form.imageAlign" wire:model="form.imageAlign" :disabled="$lockAll">
                <option value="right">راست</option>
                <option value="center">وسط</option>
                <option value="left">چپ</option>
            </x-ui.forms.select>
        </div>
    @endif
    @error('form.image')
        <p wire:key="correspondence-image-error" class="text-xs text-[var(--md-sys-color-error)] animate-pulse">{{ $message }}</p>
    @enderror
    @error('form.files.*')
        <p wire:key="correspondence-files-error" class="text-xs text-[var(--md-sys-color-error)] animate-pulse">{{ $message }}</p>
    @enderror
    @foreach($savedFiles as $fileIndex => $file)
        <div wire:key="correspondence-file-{{ $fileIndex }}" class="flex items-center justify-between gap-2 text-sm rounded-xl border border-[var(--md-sys-color-outline-variant)] px-3 py-2">
            @if(isset($fileNames[$fileIndex]))
                <x-ui.buttons.form wire:key="correspondence-file-link" :href="route('correspondence.attachment', [$letter->id, $fileIndex])" target="_blank" rel="nofollow" variant="ghost" icon="attach_file" class="no-underline"><bdi>{{ $file['name'] ?? '' }}</bdi></x-ui.buttons.form>
            @else
                <span wire:key="correspondence-file-name" class="min-w-0 truncate"><bdi>{{ $file['name'] ?? '' }}</bdi></span>
            @endif
            <x-ui.buttons.form type="button" variant="ghost" size="icon" icon="close" title="حذف پیوست" aria-label="حذف پیوست" class="!h-8 !w-8 !p-0 !rounded-lg" x-on:click="{{ confirmDialog('removeAttachment', 'حذف پیوست', 'این پیوست حذف شود؟', (int) $fileIndex, 'dispatch', 'danger') }}" :disabled="$lockAll"/>
        </div>
    @endforeach

    <div class="flex items-center justify-between gap-2">
        <p class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">افراد و ترتیب امضا</p>
        @unless($lockHead)
            <div wire:key="correspondence-form-party-tools" class="flex gap-1">
                <x-ui.buttons.form type="button" variant="ghost" icon="add" wire:click="addParty">افزودن فرد</x-ui.buttons.form>
                <x-ui.buttons.form type="button" variant="ghost" wire:click="applySuggestions">افزودن افراد پیشنهادی</x-ui.buttons.form>
            </div>
        @endunless
    </div>
    <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">نقش «تأیید و امضا» برای همکاران امضاکننده است و نقش «فقط خواندن و ثبت نظر» برای افراد مطلع. در روش ترتیبی، امضا از بالای فهرست شروع می‌شود؛ نام آخرین تأییدکنندهٔ فهرست روی نسخهٔ چاپی می‌آید. برای گیرندهٔ بیرونی، شخص داخلی را خالی بگذارید و مشخصات او را در نقش «فقط خواندن و ثبت نظر» وارد کنید.</p>
    @include('livewire.dashboard.correspondence.composer', ['partyRows' => $form->parties, 'partyModel' => 'form.parties', 'partyKey' => 'form', 'partyLock' => $lockHead, 'partyMove' => true, 'partyRemove' => 'removeParty'])

    @if($state['smartBlocked'])
        <p wire:key="correspondence-smart-blocked" class="text-sm text-[var(--md-sys-color-on-surface-variant)]">{{ __('resources/correspondence/strings.errors.confidential_smart') }}</p>
    @elseif($state['smartOn'])
        <div wire:key="correspondence-smart" class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] p-3 flex flex-col gap-3">
            <x-ui.forms.textarea label="از هوش مصنوعی چه نامه‌ای می‌خواهید؟" name="form.prompt" wire:model="form.prompt" :disabled="$lockAll"/>
            <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">هدف نامه، مخاطب و اطلاعات قطعی مثل نام‌ها، مبلغ و تاریخ را بنویسید. فایل پیوست به‌عنوان منبع به هوش مصنوعی فرستاده نمی‌شود. نتیجه را خودتان بررسی کنید و همهٔ «[…]»ها را پیش از ارسال برای امضا کامل کنید.</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <x-ui.forms.select label="گروه مخاطب نامه" name="form.audience" wire:model="form.audience" :disabled="$lockAll">
                    <option value="management">مدیریت</option>
                    <option value="internal">داخلی</option>
                    <option value="external">بیرون سازمان</option>
                </x-ui.forms.select>
                <x-ui.forms.select label="لحن" name="form.tone" wire:model="form.tone" :disabled="$lockAll">
                    <option value="formal">رسمی</option>
                    <option value="neutral">معمول و بی‌طرف</option>
                    <option value="firm">قاطع</option>
                </x-ui.forms.select>
                <x-ui.forms.select label="طول متن" name="form.length" wire:model="form.length" :disabled="$lockAll">
                    <option value="short">کوتاه</option>
                    <option value="standard">معمول</option>
                    <option value="long">مفصل</option>
                </x-ui.forms.select>
            </div>
            <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">گروه مخاطب، لحن و طول متن فقط شیوهٔ نوشتن را تغییر می‌دهند و به کسی دسترسی نمی‌دهند. دسترسی افراد را در فهرست افراد نامه تعیین کنید.</p>
        </div>
    @endif

    @if($letter && CorrespondenceAccessPolicy::isAuthor(auth()->user(), $letter))
        <div wire:key="correspondence-template-save" class="grid grid-cols-1 md:grid-cols-3 gap-3 items-start">
            <x-ui.forms.input label="نام الگو" name="templateName" wire:model="templateName" :disabled="$lockAll"/>
            <x-ui.forms.select label="استفاده‌کنندگان الگو" name="templateScope" wire:model="templateScope" :disabled="$lockAll">
                @foreach($presenter->scopeLabels() as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </x-ui.forms.select>
            <x-ui.buttons.form type="button" variant="tonal" icon="content_copy" loading="saveAsTemplate" wire:click="saveAsTemplate" :disabled="$lockAll">ذخیره به‌عنوان الگو</x-ui.buttons.form>
            <p class="md:col-span-3 text-xs text-[var(--md-sys-color-on-surface-variant)]">همین نامه در فهرست الگوها قرار می‌گیرد. استفاده از آن بعداً پیش‌نویس جداگانه‌ای می‌سازد؛ ذخیرهٔ الگو، نامه را برای امضا یا گیرنده ارسال نمی‌کند.</p>
        </div>
    @endif

    <div class="flex flex-wrap gap-2">
        <x-ui.buttons.form type="button" variant="tonal" icon="save" loading="save" x-bind:disabled="blocked" x-on:click="save()">ذخیره</x-ui.buttons.form>
        @if($state['structural'])
            <div wire:key="correspondence-share-actions" class="contents">
                <x-ui.buttons.form wire:key="correspondence-share-open" type="button" variant="primary" icon="send" x-bind:disabled="blocked" x-on:click="openShare()">ارسال برای امضا</x-ui.buttons.form>
                <x-ui.modals.dialog name="correspondence-share" title="بررسی افراد پیش از ارسال برای امضا">
                    <div class="flex flex-col gap-2 text-sm">
                        <p>با این کار نامه برای افراد تعیین‌شده در دسترس قرار می‌گیرد و مسیر امضا شروع می‌شود. ایمیل گیرندگان بیرونی را پس از تأیید نهایی، جداگانه ارسال کنید.</p>
                        <p>امضاکنندگان: <bdi>{{ $route['approvers'] === [] ? '—' : implode('، ', $route['approvers']) }}</bdi></p>
                        <p>امضای پایانی: <bdi>{{ $route['signer'] !== '' ? $route['signer'] : '—' }}</bdi></p>
                        <p>اطلاع: <bdi>{{ $route['fyi'] === [] ? '—' : implode('، ', $route['fyi']) }}</bdi></p>
                        <p>رونوشت چاپی: <bdi>{{ $route['cc'] === [] ? '—' : implode('، ', $route['cc']) }}</bdi></p>
                        <p>بیرون سازمان: <bdi>{{ $route['external'] === [] ? '—' : implode('، ', $route['external']) }}</bdi></p>
                        <p>{{ $route['classification'] }} · {{ $route['sign_mode'] }} · {{ $route['route_mode'] }}</p>
                        <x-ui.buttons.form type="button" variant="primary" icon="send" loading="shareLetter" x-bind:disabled="over" x-on:click="share()">ارسال</x-ui.buttons.form>
                    </div>
                </x-ui.modals.dialog>
            </div>
        @endif
        @if($state['smartOn'])
            <x-ui.buttons.form wire:key="correspondence-generate" type="button" variant="ai" icon="auto_awesome" loading="generate" :disabled="!$state['canSubmit']" x-on:click="generate()">نوشتن هوشمند</x-ui.buttons.form>
        @endif
        @if($letter && in_array($letter->status, Correspondence::DRAFT_LIKE, true))
            <x-ui.buttons.form wire:key="correspondence-delete-draft" type="button" variant="ghost" x-on:click="{{ confirmDialog('deleteDraft', 'حذف پیش‌نویس', 'این پیش‌نویس حذف شود؟', null, 'dispatch', 'danger') }}">حذف پیش‌نویس</x-ui.buttons.form>
        @endif
    </div>

</div>
@endif
