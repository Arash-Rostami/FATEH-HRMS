<div class="space-y-4 suggestion-form-enter animate-fade">
    @php($stage = $p->stageConfig())

    {{-- ══ HEADER ══ --}}
    <div class="rounded-2xl overflow-hidden shadow-sm bg-[var(--md-sys-color-surface)]">
        <div class="h-1 {{ 'bg-[var(--md-sys-color-' . $stage['bg'] . ')]' }}"></div>

        <div class="p-5 md:p-6">
            <div class="text-sm pb-2">
                شناسه:
                <span class="font-mono" dir="ltr">
                    SN-{{ $this->suggestion->created_at->format('Ymd') }}-{{ str_pad($this->suggestion->id, 6, '0', STR_PAD_LEFT) }}
                </span>
            </div>

            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 shadow-sm
                            {{ 'bg-[var(--md-sys-color-' . $stage['bg'] . ')]' }}
                            {{ 'text-[var(--md-sys-color-' . $stage['on'] . ')]' }}">
                    <span class="material-symbols-rounded text-2xl font-fill">
                        {{ $stage['icon'] }}
                    </span>
                </div>

                <div class="flex-1 min-w-0">
                    <h2 class="text-xl font-bold leading-snug text-[var(--md-sys-color-on-surface)]">
                        {{ $p->suggestion()->title }}
                    </h2>

                    <p class="leading-snug text-[var(--md-sys-color-on-surface)] text-sm py-2">
                        <span class="material-symbols-rounded relative top-2">person</span>
                        ثبت کننده: {{ $p->suggestion()->user->name ?? '-' }}
                    </p>

                    <div class="flex flex-wrap items-center gap-1.5 mt-2">
                        <span
                            title="مرحله/وضعیت"
                            class="cursor-help px-2.5 py-1 rounded-lg text-xs font-bold
                                  {{ 'bg-[var(--md-sys-color-' . $stage['bg'] . ')]' }}
                                  {{ 'text-[var(--md-sys-color-' . $stage['on'] . ')]' }}">
                            {{ $stage['badge_icon'] }} {{ $stage['label'] }}
                        </span>

                        @php($pr = $p->priorityConfig())
                        <span
                            title="اولویت"
                            class="cursor-help px-2.5 py-1 rounded-lg text-xs font-bold flex items-center gap-1
                                   {{ 'bg-[var(--md-sys-color-' . $pr[0] . ')]' }}
                                   {{ 'text-[var(--md-sys-color-' . $pr[1] . ')]' }}">
                            <span class="material-symbols-rounded !text-xs">
                                {{ $pr[2] }}
                            </span>
                            {{ $pr[3] }}
                        </span>

                        @if($p->isSelfFill())
                            <span class="px-2.5 py-1 rounded-lg text-xs font-bold flex items-center gap-1
                                         bg-[var(--md-sys-color-secondary-container)]
                                         text-[var(--md-sys-color-on-secondary-container)]">
                                <span class="material-symbols-rounded !text-xs">edit_note</span>
                                تکمیل شخصی
                            </span>
                        @endif
                    </div>
                </div>

                @if($p->attachmentUrl())
                    @if($p->attachmentIsImage())
                        <a href="{{ $p->attachmentUrl() }}" data-fancybox="suggestion-attachment" data-caption="پیوست"
                           class="shrink-0 block w-16 h-16 rounded-lg overflow-hidden border border-[var(--md-sys-color-outline-variant)] shadow-sm hover:brightness-90 transition-all">
                            <img src="{{ $p->attachmentUrl() }}" alt="پیوست" loading="lazy" class="w-full h-full object-cover">
                        </a>
                    @else
                        <a
                            href="{{ $p->attachmentUrl() }}"
                            target="_blank"
                            class="shrink-0 flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-bold transition hover:brightness-95
                                   bg-[var(--md-sys-color-primary-container)]
                                   text-[var(--md-sys-color-on-primary-container)]">
                            <span class="material-symbols-rounded text-sm">attach_file</span>
                            پیوست
                        </a>
                    @endif
                @endif
            </div>

            {{-- Stats --}}
            <div class="grid grid-cols-3 gap-2 mt-5 pt-4
                        border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)]">
                @foreach($p->stats() as $stat)
                    <div class="text-center py-2 rounded-xl
                                bg-[color-mix(in_srgb,var(--md-sys-color-surface-variant)_60%,transparent)]">
                        <span class="material-symbols-rounded text-base font-fill block mb-0.5 {{ $stat['color'] }}">
                            {{ $stat['icon'] }}
                        </span>
                        <div class="text-xl font-bold {{ $stat['color'] }}">
                            {{ $stat['count'] }}
                        </div>
                        <div class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">
                            {{ $stat['label'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ══ WORKFLOW ══ --}}
    <div class="rounded-2xl p-5 shadow-sm bg-[var(--md-sys-color-surface)]
                border border-[var(--md-sys-color-outline-variant)]">

        <x-ui.title icon="account_tree" title="روند بررسی پیشنهاد"/>

        <div class="mt-5 relative">
            <div class="absolute right-5 top-5 bottom-5 w-[2px]
                        bg-[var(--md-sys-color-outline-variant)] z-0"></div>

            <div class="space-y-4 relative z-10">
                @foreach($p->workflowItems() as $item)
                    <div class="relative flex items-start gap-4 group">

                        <div class="relative flex-shrink-0 w-10 h-10 z-10">
                            <div class="{{ $item['icon_classes'] }}">
                                <span class="material-symbols-rounded text-lg font-fill">
                                    {{ $item['display_icon'] }}
                                </span>
                            </div>
                        </div>

                        <div class="{{ $item['card_classes'] }}">
                            <div class="flex items-start justify-between gap-3 flex-wrap">
                                <div class="min-w-0">
                                    <span class="{{ $item['title_classes'] }}">
                                        {{ $item['step'] }}. {{ $item['title'] }}
                                    </span>
                                    <p class="text-xs mt-1 leading-relaxed text-[var(--md-sys-color-on-surface-variant)]">
                                        {{ $item['description'] }}
                                    </p>
                                </div>

                                <span
                                    class="inline-flex items-center gap-1.5 text-[10px] px-2.5 py-0.5 rounded-lg font-semibold {{ $item['badge_classes'] }}">
                                    @if($item['state'] === 'active')
                                        <span class="material-symbols-rounded text-[14px] animate-spin leading-none">
                                            progress_activity
                                        </span>
                                    @else
                                        <span class="material-symbols-rounded text-[14px] leading-none">
                                            {{ $item['display_icon'] }}
                                        </span>
                                    @endif
                                    {{ $item['badge_label'] }}
                                </span>
                            </div>
                        </div>

                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ══ META + DESCRIPTION ══ --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        {{-- META --}}
        <div class="rounded-2xl overflow-hidden bg-[var(--md-sys-color-surface)]
                    border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_25%,transparent)]
                    shadow-[0_1px_3px_rgba(0,0,0,0.06),0_1px_2px_rgba(0,0,0,0.04)]
                    hover:shadow-[0_4px_12px_rgba(0,0,0,0.08),0_2px_4px_rgba(0,0,0,0.06)]
                    transition-all duration-300">

            <div class="px-5 md:px-6 pt-5">
                <x-ui.title icon="info" title="اطلاعات تکمیلی"/>
            </div>

            <div class="p-5 md:px-6 md:pb-6">
                <dl class="divide-y divide-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_25%,transparent)]">

                    @foreach($p->infoRows() as $row)
                        <div class="flex items-center gap-3 py-2 px-1 -mx-1 rounded-lg
                                    hover:bg-[var(--md-sys-color-surface-variant)]/40
                                    transition-colors duration-200 group/row">

                            <div class="w-9 h-9 flex items-center justify-center rounded-xl
                                        bg-[var(--md-sys-color-primary)]/10
                                        text-[var(--md-sys-color-primary)]
                                        shrink-0 ring-1 ring-[var(--md-sys-color-primary)]/15
                                        group-hover/row:scale-105 transition-transform duration-200">
                                <span class="material-symbols-rounded text-[18px]">
                                    {{ $row['icon'] }}
                                </span>
                            </div>

                            <dt class="flex-1 text-[13px] font-medium
                                       text-[var(--md-sys-color-on-surface-variant)] leading-5">
                                {{ $row['label'] }}
                            </dt>

                            <dd class="text-[13px] font-semibold
                                       text-[var(--md-sys-color-on-surface)]
                                       text-end max-w-[55%] leading-5">

                                @switch($row['type'])

                                    @case('bool')
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[11px] font-bold tracking-wide
                                            {{ $row['value'] === 'بله'
                                                ? 'bg-[var(--md-sys-color-primary)]/10 text-[var(--md-sys-color-primary)] ring-1 ring-[var(--md-sys-color-primary)]/20'
                                                : 'bg-[var(--md-sys-color-surface-variant)]/60 text-[var(--md-sys-color-on-surface-variant)] ring-1 ring-[var(--md-sys-color-outline)]/10' }}">
                                            <span class="material-symbols-rounded text-[13px]">
                                                {{ $row['value'] === 'بله' ? 'check_circle' : 'cancel' }}
                                            </span>
                                            {{ $row['value'] }}
                                        </span>
                                        @break

                                    @case('warning')
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[11px] font-bold tracking-wide
                                                     bg-amber-500/10 text-amber-600 ring-1 ring-amber-500/20">
                                            <span class="material-symbols-rounded text-[13px]">warning_amber</span>
                                            {{ str_replace('⚠', '', $row['value']) }}
                                        </span>
                                        @break

                                    @case('zero')
                                        <span class="text-[var(--md-sys-color-on-surface-variant)] italic">—</span>
                                        @break

                                    @default
                                        <span class="break-words">{{ $row['value'] }}</span>

                                @endswitch
                            </dd>
                        </div>
                    @endforeach

                </dl>
            </div>
        </div>

        {{-- DESCRIPTION --}}
        <div class="rounded-2xl p-5 shadow-sm space-y-4 bg-[var(--md-sys-color-surface)]">

            <div>
                <x-ui.title icon="description" title="شرح"/>
                <p class="mt-3 text-sm leading-relaxed text-justify whitespace-pre-wrap text-[var(--md-sys-color-on-surface-variant)] rich-colors">
                    {!! \Illuminate\Support\Str::sanitizeHtml(is_array($p->suggestion()->description)
                        ? ($p->suggestion()->description['self'] ?? '')
                        : $p->suggestion()->description) !!}
                </p>
            </div>

            {{-- PURPOSE --}}
            <div class="pt-3 border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]">
                <x-ui.title icon="rocket_launch" title="اهداف"/>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @foreach((array)$p->suggestion()->purpose as $pv)
                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold
                                     bg-[var(--md-sys-color-secondary-container)]
                                     text-[var(--md-sys-color-on-secondary-container)]">
                            {{ $p->purposeLabels()[$pv] ?? $pv }}
                        </span>
                    @endforeach
                </div>
            </div>

            {{-- RULES --}}
            <div class="pt-3 border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]">
                <x-ui.title icon="rule" title="قواعد"/>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @foreach((array)$p->suggestion()->rule as $rv)
                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold
                                     bg-[var(--md-sys-color-tertiary-container)]
                                     text-[var(--md-sys-color-on-tertiary-container)]">
                            {{ $p->ruleLabels()[$rv] ?? $rv }}
                        </span>
                    @endforeach
                </div>
            </div>

            {{-- PRIORITY --}}
            <div class="pt-3 border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]">
                <x-ui.title icon="flag" title="اولویت پیگیری"/>
                @php([$prioBg, $prioOn, $prioIcon, $prioLabel] = $p->priorityConfig())
                <div class="mt-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold
                                 bg-[var(--md-sys-color-{{ $prioBg }})]
                                 text-[var(--md-sys-color-{{ $prioOn }})]">
                        <span class="material-symbols-rounded !text-[14px]">{{ $prioIcon }}</span>
                        {{ $prioLabel }}
                    </span>
                </div>
            </div>

        </div>
    </div>

    {{-- ══ DEPARTMENTS STATUS ══ --}}
    @if(count($p->suggestion()->departments ?? []))
        <div class="rounded-2xl p-5 shadow-sm bg-[var(--md-sys-color-surface)]">
            <x-ui.title icon="corporate_fare" title="واحدهای ذی‌نفع"/>
            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                @foreach($p->departmentStatuses() as $dept)
                    <div @class([
                    "flex items-center gap-3 p-3 rounded-xl",
                    $dept['bg_class'] => true,
                    'border-r-2 border-[var(--md-sys-color-primary)]' => $dept['is_action'],
                ])>
                    <span class="material-symbols-rounded text-base font-fill {{ $dept['text_class'] }}">
                        {{ $dept['style'][2] }}
                    </span>
                        <span class="text-sm font-bold flex-1 text-[var(--md-sys-color-on-surface)]"
                              title="{{ $dept['tooltip'] ?? '' }}">
                        {{ $dept['name'] }}
                    </span>
                        @if($dept['is_action'])
                            <span class="text-[10px] px-2 py-0.5 rounded-lg font-bold flex items-center gap-1
                                     bg-[var(--md-sys-color-primary-container)]
                                     text-[var(--md-sys-color-on-primary-container)]">
                            <span class="material-symbols-rounded text-xs">forward_to_inbox</span>
                            ارجاع
                        </span>
                            @if($dept['is_complete'])
                                <span class="material-symbols-rounded text-base text-[var(--md-sys-color-tertiary)]" title="تکمیل شده">task_alt</span>
                            @elseif($dept['has_review'])
                                <span class="material-symbols-rounded text-base text-[var(--md-sys-color-on-surface-variant)]" title="در انتظار">hourglass_empty</span>
                            @endif
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ══ REVIEWS ══ --}}
    @if($p->suggestion()->reviews->isNotEmpty())
        <div class="rounded-2xl p-5 shadow-sm bg-[var(--md-sys-color-surface)]
                border border-[var(--md-sys-color-outline-variant)]">
            <x-ui.title icon="rate_review" title="بازخوردها"/>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-4">
                @foreach($p->reviewItems() as $item)
                    <div @class([
                    'rounded-xl p-4 space-y-3 border transition-all duration-200 relative overflow-hidden',
                    $item['bg_class'] => true,
                    'border-r-[3px] border-r-[var(--md-sys-color-primary)] border-[var(--md-sys-color-outline-variant)]' => $item['is_ma'],
                    'border-[3px] border-[var(--md-sys-color-primary-container)] border-[var(--md-sys-color-outline-variant)]' => !$item['is_ma'] && $item['is_action'],
                    'border-[var(--md-sys-color-outline-variant)]' => !$item['is_ma'] && !$item['is_action'],
                ])>
                        @if($item['is_system_generated'])
                            <span class="pointer-events-none absolute -top-1 left-3 rotate-[-8deg] text-[30px] font-black tracking-wider opacity-[0.06] select-none text-[var(--md-sys-color-on-surface)]">خودکار</span>
                        @endif
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 {{ $item['icon_bg_class'] }}">
                                <span class="material-symbols-rounded text-sm font-fill {{ $item['icon_text_class'] }}">
                                    {{ $item['style'][2] }}
                                </span>
                                </div>
                                <span class="text-sm font-bold truncate text-[var(--md-sys-color-on-surface)]"
                                      title="{{ $item['tooltip'] ?? '' }}">
                                {{ $item['label'] }}
                            </span>
                                @if($item['is_action'])
                                    <span class="material-symbols-rounded text-xs shrink-0 text-[var(--md-sys-color-secondary)]" title="ارجاع برای اقدام">
                                    forward_to_inbox
                                </span>
                                @endif
                                @if($item['is_system_generated'])
                                    <span class="text-[9px] px-1.5 py-0.5 rounded-lg font-bold shrink-0 inline-flex items-center gap-0.5 bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]">
                                        <span class="material-symbols-rounded text-[10px]">smart_toy</span>
                                        تولید خودکار
                                    </span>
                                @endif
                            </div>
                            <span class="text-[10px] px-2.5 py-0.5 rounded-lg font-bold shrink-0 {{ $item['badge_bg_class'] }} {{ $item['badge_text_class'] }}">
                            {{ $item['feedback_label'] }}
                        </span>
                        </div>

                        @if($item['review']->comments)
                            <p class="text-xs leading-relaxed whitespace-pre-wrap text-[var(--md-sys-color-on-surface-variant)] px-1">
                                {{ $item['review']->comments }}
                            </p>
                        @endif

                        @if($item['review']->actions)
                            <div class="mt-3 rounded-lg overflow-hidden border border-[var(--md-sys-color-primary)]/15 bg-[var(--md-sys-color-primary-container)]">
                                <div class="flex items-center gap-2 px-2 py-2 bg-[var(--md-sys-color-primary)]/10 border-b border-[var(--md-sys-color-primary)]/10">
                                    <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">assignment</span>
                                    <span class="text-[11px] font-semibold tracking-wider text-[var(--md-sys-color-on-primary-container)]">دستورالعمل اجرا:</span>
                                    @if($item['review']->referral)
                                        <div class="flex flex-wrap gap-1 mr-auto cursor-help" title="واحدهای موظف">
                                            @foreach($item['referral_labels'] as $rl)
                                                <span class="inline-flex items-center text-[10px] font-medium px-2 py-0.5 rounded-sm leading-none
                                                         bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]
                                                         ring-1 ring-[var(--md-sys-color-primary)]/30"
                                                      title="{{ $rl['tooltip'] }}">
                                                {{ $rl['label'] }}
                                            </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <p class="px-3 py-2.5 text-xs leading-6 whitespace-pre-wrap text-[var(--md-sys-color-on-primary-container)]/85">
                                    {{ $item['review']->actions }}
                                </p>
                            </div>
                        @endif

                        <div class="flex items-center justify-between pt-2
                                border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)]">
                        <span class="text-[10px] flex items-center gap-1 text-[var(--md-sys-color-on-surface-variant)]">
                            <span class="material-symbols-rounded text-xs">schedule</span>
                            {{ toJalali($item['review']->created_at, 'j F Y') }}
                        </span>
                            @if($item['is_action'])
                                <span class="text-[10px] px-2 py-0.5 rounded-lg font-bold flex items-center gap-1
                                         bg-[var(--md-sys-color-primary-container)]
                                         text-[var(--md-sys-color-on-primary-container)]">
                                <span class="material-symbols-rounded text-xs">forward_to_inbox</span>
                                ارجاع
                            </span>
                                @if($item['review']->complete)
                                    <span class="material-symbols-rounded text-base text-[var(--md-sys-color-tertiary)]" title="تکمیل شده">task_alt</span>
                                @else
                                    <span class="material-symbols-rounded text-base text-[var(--md-sys-color-on-surface-variant)]" title="در انتظار">hourglass_empty</span>
                                @endif
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ══ MARK IMPLEMENTATION COMPLETE ══ --}}
    @if($this->canMarkComplete)
        <div class="rounded-2xl p-5 shadow-sm bg-[var(--md-sys-color-surface)]
                    border border-[var(--md-sys-color-outline-variant)]">
            <x-ui.title icon="task_alt" title="تکمیل اقدام واحد"/>
            <p class="mt-3 text-sm leading-7 text-[var(--md-sys-color-on-surface-variant)]">
                واحد شما به عنوان مسئول اقدام ارجاع داده شده است. در صورت اتمام اجرای دستورالعمل، اقدام را تکمیل‌شده اعلام کنید.
            </p>
            <div class="mt-4 flex justify-end">
                <x-ui.buttons.form type="button"
                        wire:click="markComplete"
                        loading="markComplete"
                        icon="check_circle"
                        class="px-5 py-2.5 rounded-xl text-sm font-bold bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] hover:opacity-90">
                    ثبت تکمیل اقدام
                </x-ui.buttons.form>
            </div>
        </div>
    @endif

    {{-- ══ DEPT FEEDBACK ══ --}}
    @if($this->canGiveFeedback)
        <div class="rounded-2xl p-5 shadow-sm bg-[var(--md-sys-color-surface)]
                    border border-[var(--md-sys-color-outline-variant)]">

            <x-ui.title icon="add_comment" title="ثبت بازخورد"/>

            <div class="mt-4 space-y-4">

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    @foreach(array_diff_key($p->feedbackLabels(), ['incomplete'=>'','unknown'=>'']) as $val => $label)
                        @php($fs = $p->feedbackStyle($val))

                        <label class="relative flex items-center gap-3 p-3.5 rounded-xl cursor-pointer select-none transition-all
                                      border-2 border-transparent hover:border-[var(--md-sys-color-outline)]
                                      {{ 'bg-[color-mix(in_srgb,var(--md-sys-color-' . $fs[0] . ') 20%,transparent)]' }}
                                      has-[:checked]:border-[var(--md-sys-color-primary)]
                                      has-[:checked]:bg-[color-mix(in_srgb,var(--md-sys-color-primary-container)_60%,transparent)]">

                            <input type="radio" wire:model="feedbackForm.feedback" value="{{ $val }}"
                                   class="w-4 h-4 accent-[var(--md-sys-color-primary)]">

                            <span class="material-symbols-rounded text-sm font-fill
                                         {{ 'text-[var(--md-sys-color-' . $fs[1] . ')]' }}">
                                {{ $fs[2] }}
                            </span>

                            <span class="text-sm font-medium text-[var(--md-sys-color-on-surface)]">
                                {{ $label }}
                            </span>

                        </label>
                    @endforeach
                </div>

                @error('feedbackForm.feedback')
                <p class="text-xs text-[var(--md-sys-color-error)] flex items-center gap-1">
                    <span class="material-symbols-rounded text-sm">error</span>
                    {{ $message }}
                </p>
                @enderror

                <x-ui.forms.textarea label="توضیحات" name="comment" :rows="4" wire:model="feedbackForm.comment" :maximizable="true"/>

                <x-ui.buttons.form icon="send" wire:click="submitFeedback" loading="submitFeedback">
                    ثبت بازخورد
                </x-ui.buttons.form>

            </div>
        </div>
    @endif

    {{-- ══ CEO DECISION ══ --}}
    @if($this->canDecide)
        <div class="rounded-2xl p-5 shadow-sm bg-[var(--md-sys-color-surface)]
                    border border-[var(--md-sys-color-outline-variant)]">

            <x-ui.title icon="gavel" title="تصمیم نهایی"/>

            <div class="mt-4 space-y-4">

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    @foreach([
                        'accepted'     => ['primary','on-primary','check_circle','پذیرش'],
                        'rejected'     => ['error','on-error','cancel','رد'],
                        'under_review' => ['outline','surface-variant','find_in_page','تکمیل مجدد'],
                    ] as $val => [$bg,$on,$ic,$lbl])

                        <label class="relative flex items-center gap-3 p-3.5 rounded-xl cursor-pointer select-none transition-all
                                      border-2 border-transparent hover:brightness-95
                                      {{ 'bg-[var(--md-sys-color-' . $bg . ')]' }}
                                      {{ 'has-[:checked]:border-[var(--md-sys-color-' . $on . ')]' }}
                                      has-[:checked]:scale-[1.02]">

                            <input type="radio" wire:model.live="decisionForm.decision" value="{{ $val }}"
                                   class="w-4 h-4 accent-[var(--md-sys-color-primary)]">

                            <span class="text-sm font-bold {{ 'text-[var(--md-sys-color-' . $on . ')]' }}">
                                {{ $lbl }}
                            </span>

                            <span class="material-symbols-rounded text-base font-fill mr-auto
                                         {{ 'text-[var(--md-sys-color-' . $on . ')]' }}">
                                {{ $ic }}
                            </span>

                        </label>
                    @endforeach
                </div>

                @error('decisionForm.decision')
                <p class="text-xs text-[var(--md-sys-color-error)] flex items-center gap-1">
                    <span class="material-symbols-rounded text-sm">error</span>
                    {{ $message }}
                </p>
                @enderror

                <x-ui.forms.textarea
                    label="یادداشت تصمیم"
                    name="decisionComment"
                    :rows="4"
                    wire:model="decisionForm.decisionComment"
                    :maximizable="true"
                />

                @if($decisionForm->decision === 'accepted')
                    <div class="rounded-xl overflow-hidden border border-[var(--md-sys-color-outline-variant)]">

                        <div class="flex items-center gap-2 px-4 py-3 bg-[var(--md-sys-color-surface-variant)]">
                            <span class="material-symbols-rounded text-base text-[var(--md-sys-color-primary)]">
                                forward_to_inbox
                            </span>
                            <span class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">
                                ارجاع برای اقدام
                            </span>
                            <span class="text-xs text-[var(--md-sys-color-on-surface-variant)] mr-1">
                                (اختیاری)
                            </span>
                        </div>

                        <div
                            class="grid grid-cols-1 sm:grid-cols-2 gap-2 p-3 bg-[var(--md-sys-color-surface)] max-h-60 overflow-y-auto">
                            @foreach($this->allDepartments as $code => $name)
                                <label class="flex items-center gap-2 p-2.5 rounded-xl cursor-pointer select-none transition-all
                                              border border-transparent
                                              hover:border-[var(--md-sys-color-outline-variant)]
                                              hover:bg-[var(--md-sys-color-surface-variant)]
                                              has-[:checked]:border-[var(--md-sys-color-primary)]
                                              has-[:checked]:bg-[color-mix(in_srgb,var(--md-sys-color-primary-container)_50%,transparent)]">

                                    <input type="checkbox" wire:model.live="decisionForm.referralDepts"
                                           value="{{ $code }}"
                                           class="w-4 h-4 accent-[var(--md-sys-color-primary)]">

                                    <span class="text-sm text-[var(--md-sys-color-on-surface)]"
                                          title="{{ $this->allDepartmentTooltips[$code] ?? '' }}">
                                        {{ $name }}
                                    </span>

                                </label>
                            @endforeach
                        </div>

                        @if($decisionForm->referralDepts)
                            <div
                                class="p-4 border-t border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)]">
                                <x-ui.forms.textarea
                                    label="دستورالعمل اقدام"
                                    name="referralActions"
                                    :rows="3"
                                    wire:model="decisionForm.referralActions"
                                    placeholder="دستورالعمل اجرایی خود را برای واحدهای انتخاب شده بنویسید..."
                                    :maximizable="true"
                                />
                            </div>
                        @endif

                    </div>
                @endif

                <x-ui.buttons.form icon="gavel" wire:click="submitDecision" loading="submitDecision">
                    ثبت تصمیم
                </x-ui.buttons.form>

            </div>
        </div>
    @endif

</div>
