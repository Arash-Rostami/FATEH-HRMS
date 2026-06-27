<div wire:key="recurring-panel" class="flex flex-col gap-4">

    @if($this->allowsRepeat)
    <div class="flex items-center gap-3">
        <button
            wire:click="$toggle('isRecurring')"
            @class([
                'flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-300 border outline-none',
                'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] border-transparent shadow-md shadow-[var(--md-sys-color-primary)]/20' => $isRecurring,
                'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline-variant)] hover:bg-[var(--md-sys-color-surface-variant)]' => !$isRecurring,
            ])
            style="{{ !$isRecurring ? 'border-color: color-mix(in srgb, var(--md-sys-color-outline-variant) 40%, transparent);' : '' }}"
        >
            <span class="material-symbols-rounded text-[18px] {{ $isRecurring ? 'font-fill' : '' }}">repeat</span>
            رزرو تکرارشونده
        </button>
    </div>

    @if($isRecurring)
        <div class="flex flex-wrap gap-6 animate-slide-up-fade">

            <div class="flex flex-col gap-2">
                <label class="text-[11px] uppercase tracking-widest font-bold text-[var(--md-sys-color-primary)] flex items-center gap-2">
                    <span class="material-symbols-rounded text-[16px]">calendar_view_week</span>
                    الگوی تکرار
                </label>
                <div class="flex gap-2">
                    @foreach(['daily' => 'روزانه', 'weekly' => 'هفتگی'] as $value => $label)
                        <button
                            wire:click="$set('recurPattern', '{{ $value }}')"
                            @class([
                                'px-4 py-2 rounded-lg text-sm font-semibold transition-all duration-300 border outline-none',
                                'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] border-transparent shadow-md' => $recurPattern === $value,
                                'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline-variant)] hover:bg-[var(--md-sys-color-surface-variant)]' => $recurPattern !== $value,
                            ])
                            style="{{ $recurPattern !== $value ? 'border-color: color-mix(in srgb, var(--md-sys-color-outline-variant) 40%, transparent);' : '' }}"
                        >{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <label class="text-[11px] uppercase tracking-widest font-bold text-[var(--md-sys-color-primary)] flex items-center gap-2">
                    <span class="material-symbols-rounded text-[16px]">format_list_numbered</span>
                    تعداد تکرار: <span class="normal-case text-[var(--md-sys-color-on-surface)] mr-1">{{ convertToPersian($recurCount) }} بار</span>
                </label>
                <div class="flex gap-2 overflow-x-auto pb-1 no-scrollbar">
                    @foreach(range(2, 10) as $n)
                        <button
                            wire:key="recur-count-{{ $n }}"
                            wire:click="$set('recurCount', {{ $n }})"
                            @class([
                                'shrink-0 w-10 h-10 rounded-lg text-sm font-bold transition-all duration-300 border outline-none',
                                'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] border-transparent shadow-md' => $recurCount === $n,
                                'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline-variant)] hover:bg-[var(--md-sys-color-surface-variant)]' => $recurCount !== $n,
                            ])
                            style="{{ $recurCount !== $n ? 'border-color: color-mix(in srgb, var(--md-sys-color-outline-variant) 40%, transparent);' : '' }}"
                        >{{ convertToPersian($n) }}</button>
                    @endforeach
                </div>
            </div>

        </div>

        @if(!empty($this->recurPreview))
            <div class="flex flex-col gap-2 animate-slide-up-fade">
                <div class="flex items-center gap-1.5">
                    <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-on-surface-variant)]">event_repeat</span>
                    <span class="text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)]">تاریخ‌های برنامه‌ریزی‌شده</span>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($this->recurPreview as $i => $occ)
                        <span
                            @class([
                                'inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold border transition-all',
                                'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] border-transparent' => $occ['ok'],
                                'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline-variant)]/40 line-through opacity-60' => !$occ['ok'],
                            ])
                            title="{{ $occ['ok'] ? 'ایجاد می‌شود' : 'در این تاریخ ایجاد نمی‌شود (روز/بازه مجاز نیست)' }}"
                        >
                            @if(!$occ['ok'])
                                <span class="material-symbols-rounded text-[12px]">block</span>
                            @endif
                            {{ $occ['date'] }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
    @endif

</div>
