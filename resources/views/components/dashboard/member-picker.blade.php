@props([
    'model' => null,
    'candidates' => [],
    'size' => 'md',
    'height' => null,
    'rounded' => 'rounded-2xl',
    'nameKey' => 'name',
    'deptLabelKey' => 'dept_name',
    'search' => true,
    'disabled' => false,
    'emptyTitle' => 'کاربر فعال دیگری برای افزودن وجود ندارد.',
    'mode' => 'multi',
    'noneLabel' => null,
    'server' => false,
    'searchModel' => 'userSearch',
    'deptModel' => 'userDept',
    'deptOptions' => [],
    'hasMore' => false,
    'loadMoreAction' => 'loadMoreUsers',
    'bulk' => false,
    'floating' => false,
    'placeholder' => 'جستجوی نام کاربر...',
])

@php
    $sizeMap = [
        'sm' => [
            'height' => 'max-h-44',
            'rowPad' => 'px-3 py-2',
            'text' => 'text-xs',
            'avatar' => 'w-6 h-6 text-[10px]',
            'controlH' => 'h-8 text-xs',
            'iconSize' => 'text-[16px]',
        ],
        'md' => [
            'height' => 'max-h-60',
            'rowPad' => 'px-3.5 py-2.5',
            'text' => 'text-sm',
            'avatar' => 'w-7 h-7 text-[11px]',
            'controlH' => 'h-10 text-sm',
            'iconSize' => 'text-[18px]',
        ],
        'lg' => [
            'height' => 'max-h-80',
            'rowPad' => 'px-4 py-3',
            'text' => 'text-sm',
            'avatar' => 'w-8 h-8 text-xs',
            'controlH' => 'h-10 text-sm',
            'iconSize' => 'text-[20px]',
        ],
    ];
    $cfg = $sizeMap[$size] ?? $sizeMap['md'];
    $listHeight = $height ?? $cfg['height'];

    $surfaceClass = $floating
        ? 'bg-[color-mix(in_srgb,var(--md-sys-color-surface)_92%,transparent)] dark:bg-[var(--md-sys-color-surface)] backdrop-blur-xl shadow-[0_12px_32px_color-mix(in_srgb,var(--md-sys-color-primary)_22%,transparent)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)]'
        : 'bg-[var(--md-sys-color-surface)] shadow-sm border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)]';

    $rootKey = $attributes->get('wire:key') ?? 'member-picker';
    $hasNone = $mode === 'single' && (bool) $noneLabel;
    $rows = $server ? [] : collect($candidates)->map(fn ($c) => [
        'id' => $c['id'],
        'name' => $c[$nameKey] ?? '',
        'dept' => (string) ($c['dept'] ?? ''),
    ])->values()->all();
    $loadedIds = $server ? collect($candidates)->pluck('id')->map(fn ($id) => (string) $id)->values()->all() : [];
    $pickerConfig = [
        'model' => $model,
        'rows' => $rows,
        'server' => $server,
        'mode' => $mode,
        'disabled' => (bool) $disabled,
        'hasNone' => $hasNone,
        'deptModel' => $deptModel,
        'searchModel' => $searchModel,
    ];
    $rowBorder = 'border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)]';
@endphp

@if(count($candidates) > 0 || $server || $hasNone)
    <div
        dir="rtl"
        {{ $attributes->except('wire:key')->merge([
            'class' => 'flex flex-col overflow-hidden transition-all duration-300 ' .
                       $rounded . ' ' .
                       $surfaceClass . ' ' .
                       ($disabled ? 'opacity-60 pointer-events-none select-none' : '')
        ]) }}
        wire:key="{{ $rootKey }}{{ $server ? '' : '-' . substr(md5(json_encode([$rows, (bool) $disabled])), 0, 8) }}"
        x-data="memberPicker(@js($pickerConfig))"
    >
        @if($search || count($deptOptions) > 0)
            <div class="p-2.5 bg-[var(--md-sys-color-surface-container-low)] border-b border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] flex flex-col gap-2">
                @if($search || count($deptOptions) > 0)
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                        @if($search)
                            <div class="relative flex-1 min-w-0 group">
                                <span class="material-symbols-rounded {{ $cfg['iconSize'] }} absolute right-3 top-1/2 -translate-y-1/2 text-[var(--md-sys-color-on-surface-variant)] group-focus-within:text-[var(--md-sys-color-primary)] transition-colors duration-200 pointer-events-none">
                                    search
                                </span>

                                @if($server)
                                    <input
                                        type="text"
                                        wire:model.live.debounce.500ms="{{ $searchModel }}"
                                        placeholder="{{ $placeholder }}"
                                        aria-label="{{ $placeholder }}"
                                        autocomplete="off"
                                        spellcheck="false"
                                        class="w-full {{ $cfg['controlH'] }} pr-9 pl-8 rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] placeholder:text-[var(--md-sys-color-on-surface-variant)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] focus:border-[var(--md-sys-color-primary)] transition-all duration-200"
                                    />
                                @else
                                    <input
                                        type="text"
                                        x-model="memberQuery"
                                        placeholder="{{ $placeholder }}"
                                        aria-label="{{ $placeholder }}"
                                        autocomplete="off"
                                        spellcheck="false"
                                        class="w-full {{ $cfg['controlH'] }} pr-9 pl-8 rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] placeholder:text-[var(--md-sys-color-on-surface-variant)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] focus:border-[var(--md-sys-color-primary)] transition-all duration-200"
                                    />
                                    <button
                                        type="button"
                                        x-show="memberQuery !== ''"
                                        x-on:click="memberQuery = ''"
                                        title="حذف"
                                        aria-label="حذف"
                                        style="display: none;"
                                        class="absolute left-2 top-1/2 -translate-y-1/2 w-6 h-6 rounded-md inline-flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-highest)] hover:text-[var(--md-sys-color-on-surface)] active:scale-95 transition-all duration-200"
                                    >
                                        <span class="material-symbols-rounded text-[16px]">close</span>
                                    </button>
                                @endif
                            </div>
                        @endif

                        @if(count($deptOptions) > 0)
                            <div class="relative shrink-0 sm:w-44">
                                <select
                                    @if($server) wire:model.live="{{ $deptModel }}" @else x-model="dept" @endif
                                    aria-label="همه دپارتمان‌ها"
                                    title="همه دپارتمان‌ها"
                                class="w-full {{ $cfg['controlH'] }} px-3 rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] focus:border-[var(--md-sys-color-primary)] font-medium truncate transition-all duration-200 cursor-pointer"
                                >
                                    <option value="">همه دپارتمان‌ها</option>
                                    @foreach($deptOptions as $code => $name)
                                        <option value="{{ $code }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                @endif

                @if(!$server)
                    <div x-show="memberQuery !== '' || dept !== ''" style="display: none;" class="flex items-center pt-0.5 text-[11px]">
                        <span class="px-2 py-0.5 rounded-md bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] font-medium tabular-nums">
                            <span x-text="matched"></span> نتیجه
                        </span>
                    </div>
                @endif
            </div>
        @endif

        <div @if($mode === 'single') role="radiogroup" @endif class="{{ $listHeight }} overflow-y-auto overscroll-contain">
            @if($hasNone)
                <button
                    type="button"
                    wire:key="up-none"
                    @disabled($disabled)
                    role="radio"
                    x-bind:aria-checked="selectedIds.length === 0"
                    x-on:click="$wire.set('{{ $model }}', null)"
                    :class="selectedIds.length === 0
                        ? 'bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)]'
                        : 'hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_6%,transparent)]'"
                    class="group flex items-center gap-3 w-full {{ $cfg['rowPad'] }} text-right transition-colors duration-200 cursor-pointer"
                >
                    <div class="relative inline-flex items-center justify-center w-5 h-5 shrink-0">
                        <div
                            class="w-5 h-5 rounded-[6px] border-2 transition-all duration-200"
                            :class="selectedIds.length === 0
                                ? 'border-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-primary)] shadow-[0_2px_10px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)]'
                                : 'border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_80%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-highest)_40%,transparent)] group-hover:border-[color-mix(in_srgb,var(--md-sys-color-primary)_70%,transparent)]'"
                        ></div>
                        <span
                            class="material-symbols-rounded absolute text-[var(--md-sys-color-on-primary)] text-[15px] font-bold transition-all duration-200 pointer-events-none"
                            :class="selectedIds.length === 0 ? 'scale-100 opacity-100' : 'scale-0 opacity-0'"
                        >check</span>
                    </div>

                    <span class="{{ $cfg['text'] }} font-medium text-[var(--md-sys-color-on-surface-variant)] truncate flex-1">
                        {{ $noneLabel }}
                    </span>
                </button>
            @endif

            @foreach($candidates as $candidate)
                @php
                    $cId = $candidate['id'];
                    $cName = $candidate[$nameKey] ?? '';
                    $cDeptCode = $candidate['dept'] ?? null;
                    $cDeptName = $candidate[$deptLabelKey] ?? ($cDeptCode && isset($deptOptions[$cDeptCode]) ? $deptOptions[$cDeptCode] : $cDeptCode);
                    $initial = mb_substr(trim($cName), 0, 1);
                @endphp

                @if($mode === 'single')
                    <button
                        type="button"
                        wire:key="up-single-{{ $cId }}"
                        @disabled($disabled)
                        role="radio"
                        x-bind:aria-checked="isSelected('{{ $cId }}')"
                        @unless($server) x-show="isVisible('{{ $cId }}')" @endunless
                        x-on:click="$wire.set('{{ $model }}', {{ is_numeric($cId) ? $cId : "'$cId'" }})"
                        :class="[
                            isSelected('{{ $cId }}')
                                ? 'bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)]'
                                : 'hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_6%,transparent)]',
                            @if($server) {{ ($loop->first && !$hasNone) ? "''" : "'border-t'" }} @else isFirst('{{ $cId }}') ? '' : 'border-t' @endif
                        ]"
                        class="group flex items-center gap-3 w-full {{ $rowBorder }} {{ $cfg['rowPad'] }} text-right transition-colors duration-200 cursor-pointer"
                    >
                        <div class="relative inline-flex items-center justify-center w-5 h-5 shrink-0">
                            <div
                                class="w-5 h-5 rounded-[6px] border-2 transition-all duration-200"
                                :class="isSelected('{{ $cId }}')
                                    ? 'border-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-primary)] shadow-[0_2px_10px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)]'
                                    : 'border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_80%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-highest)_40%,transparent)] group-hover:border-[color-mix(in_srgb,var(--md-sys-color-primary)_70%,transparent)]'"
                            ></div>
                            <span
                                class="material-symbols-rounded absolute text-[var(--md-sys-color-on-primary)] text-[15px] font-bold transition-all duration-200 pointer-events-none"
                                :class="isSelected('{{ $cId }}') ? 'scale-100 opacity-100' : 'scale-0 opacity-0'"
                            >check</span>
                        </div>

                        <div aria-hidden="true" class="{{ $cfg['avatar'] }} rounded-[28%] bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] font-bold inline-flex items-center justify-center shrink-0">
                            {{ $initial }}
                        </div>

                        <div class="flex items-center justify-between gap-2 min-w-0 flex-1">
                            <span class="{{ $cfg['text'] }} font-bold text-[var(--md-sys-color-on-surface)] group-hover:text-[var(--md-sys-color-primary)] transition-colors duration-200 truncate">
                                {{ $cName }}
                            </span>
                            @if($cDeptName)
                                <span class="shrink-0 max-w-[45%] truncate px-2 py-0.5 rounded-lg text-[10px] font-bold bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                                    {{ $cDeptName }}
                                </span>
                            @endif
                        </div>
                    </button>
                @else
                    <label
                        wire:key="up-multi-{{ $cId }}"
                        @unless($server) x-show="isVisible('{{ $cId }}')" @endunless
                        :class="[
                            isSelected('{{ $cId }}')
                                ? 'bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)]'
                                : 'hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_6%,transparent)]',
                            @if($server) {{ $loop->first ? "''" : "'border-t'" }} @else isFirst('{{ $cId }}') ? '' : 'border-t' @endif
                        ]"
                        class="group/cb flex items-center gap-3 w-full {{ $rowBorder }} {{ $cfg['rowPad'] }} transition-colors duration-200 cursor-pointer select-none"
                    >
                        <div class="relative inline-flex items-center justify-center w-5 h-5 shrink-0">
                            <input
                                type="checkbox"
                                value="{{ $cId }}"
                                wire:model="{{ $model }}"
                                @disabled($disabled)
                                class="peer appearance-none w-5 h-5 rounded-[6px] border-2 border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_80%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-highest)_40%,transparent)] checked:border-[var(--md-sys-color-primary)] checked:bg-[var(--md-sys-color-primary)] hover:border-[color-mix(in_srgb,var(--md-sys-color-primary)_70%,transparent)] focus:outline-none focus:ring-4 focus:ring-[color-mix(in_srgb,var(--md-sys-color-primary)_20%,transparent)] focus:ring-offset-0 checked:shadow-[0_2px_10px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)] transition-all duration-200 cursor-pointer"
                            />
                            <span class="material-symbols-rounded absolute text-[var(--md-sys-color-on-primary)] text-[15px] font-bold scale-0 opacity-0 peer-checked:scale-100 peer-checked:opacity-100 pointer-events-none transition-all duration-200">
                                check
                            </span>
                        </div>

                        <div aria-hidden="true" class="{{ $cfg['avatar'] }} rounded-[28%] bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] font-bold inline-flex items-center justify-center shrink-0">
                            {{ $initial }}
                        </div>

                        <div class="flex items-center justify-between gap-2 min-w-0 flex-1">
                            <span class="{{ $cfg['text'] }} font-bold text-[var(--md-sys-color-on-surface)] group-hover/cb:text-[var(--md-sys-color-primary)] transition-colors duration-200 truncate">
                                {{ $cName }}
                            </span>
                            @if($cDeptName)
                                <span class="shrink-0 max-w-[45%] truncate px-2 py-0.5 rounded-lg text-[10px] font-bold bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                                    {{ $cDeptName }}
                                </span>
                            @endif
                        </div>
                    </label>
                @endif
            @endforeach

            @if(!$server)
                <div
                    x-show="matched === 0"
                    style="display: none;"
                    class="py-8 px-4 flex flex-col items-center justify-center text-center opacity-60 animate-toast-in"
                >
                    <div class="p-3 rounded-2xl bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)] mb-2">
                        <span class="material-symbols-rounded text-[24px]">person_search</span>
                    </div>
                    <p class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)]">
                        کاربری با این مشخصات یافت نشد
                    </p>
                    <button
                        type="button"
                        x-show="memberQuery !== '' || dept !== '' || onlySelected"
                        x-on:click="memberQuery = ''; dept = ''; onlySelected = false"
                        class="mt-2 px-2.5 py-1 rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] text-[11px] font-bold active:scale-95 transition-transform duration-200"
                    >
                        پاک کردن فیلترها
                    </button>
                </div>
            @endif

            @if($server && count($candidates) === 0)
                <div wire:key="up-empty" class="py-8 px-4 flex flex-col items-center justify-center text-center opacity-60 animate-toast-in">
                    <div class="p-3 rounded-2xl bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)] mb-2">
                        <span class="material-symbols-rounded text-[24px]">person_search</span>
                    </div>
                    <p class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)]">
                        {{ $emptyTitle }}
                    </p>
                </div>
            @endif
        </div>

        @if(!$server)
            <div
                wire:key="up-footer"
                x-show="{{ $mode === 'multi' ? 'matched > 0 || selectedIds.length > 0' : 'matched > shown' }}"
                style="display: none;"
                class="px-3.5 py-2 bg-[var(--md-sys-color-surface-container-low)] border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] flex flex-wrap items-center {{ $mode === 'multi' ? 'justify-between' : 'justify-center' }} gap-2 text-[11px]"
            >
                @if($mode === 'multi' && $bulk)
                    <x-ui.buttons.form
                        variant="tonal"
                        x-bind:disabled="busy || bulkBlocked"
                        x-bind:aria-pressed="allSelected"
                        x-bind:class="busy ? 'animate-pulse' : ''"
                        x-on:click="toggleBulk()"
                        class="!h-7 !px-2.5 !rounded-lg !text-[11px] font-bold"
                    >
                        <span x-show="!allSelected" class="material-symbols-rounded text-[14px]">done_all</span>
                        <span x-show="allSelected" style="display: none;" class="material-symbols-rounded text-[14px]">deselect</span>
                        <span x-text="allSelected ? (currentDept === '' ? 'برداشتن همه' : 'برداشتن همهٔ دپارتمان') : (currentDept === '' ? 'انتخاب همه' : 'انتخاب همهٔ دپارتمان')">انتخاب همه</span>
                    </x-ui.buttons.form>
                @endif

                <button
                    type="button"
                    x-show="matched > shown"
                    style="display: none;"
                    x-on:click="showMore()"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] font-bold hover:bg-[var(--md-sys-color-surface-container-highest)] active:scale-95 transition-all duration-200 cursor-pointer"
                >
                    <span>نمایش بیشتر</span>
                    <span class="material-symbols-rounded text-[14px]">expand_more</span>
                </button>

                @if($mode === 'multi')
                    <button
                        type="button"
                        x-on:click="onlySelected = !onlySelected"
                        x-bind:aria-pressed="onlySelected"
                        @disabled($disabled)
                        :class="onlySelected
                            ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_12px_color-mix(in_srgb,var(--md-sys-color-primary)_30%,transparent)]'
                            : 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-highest)]'"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg font-bold transition-all duration-200 active:scale-95 cursor-pointer"
                    >
                        <span class="material-symbols-rounded text-[14px]">check_box</span>
                        <span class="tabular-nums" x-text="selectedCount">0</span>
                        <span>انتخاب شده</span>
                        <span class="opacity-40">·</span>
                        <span class="tabular-nums font-medium">نمایش <strong x-text="Math.min(shown, matched)"></strong> از <strong x-text="matched"></strong></span>
                    </button>
                @endif
            </div>
        @elseif($server && ($hasMore || ($mode === 'multi' && $bulk)))
            <div wire:key="up-load-more" x-effect="sync(@js($loadedIds), @js((bool) $hasMore), @js((bool) $disabled))" class="px-3.5 py-2 bg-[var(--md-sys-color-surface-container-low)] border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] flex flex-wrap items-center {{ $mode === 'multi' ? 'justify-between' : 'justify-center' }} gap-2 text-[11px]">
                @if($mode === 'multi' && $bulk)
                    <x-ui.buttons.form
                        variant="tonal"
                        x-bind:disabled="busy || bulkBlocked"
                        x-bind:aria-pressed="allSelected"
                        x-bind:class="busy ? 'animate-pulse' : ''"
                        x-on:click="toggleBulk()"
                        class="!h-7 !px-2.5 !rounded-lg !text-[11px] font-bold"
                    >
                        <span x-show="!allSelected" class="material-symbols-rounded text-[14px]">done_all</span>
                        <span x-show="allSelected" style="display: none;" class="material-symbols-rounded text-[14px]">deselect</span>
                        <span x-text="allSelected ? (currentDept === '' ? 'برداشتن همه' : 'برداشتن همهٔ دپارتمان') : (currentDept === '' ? 'انتخاب همه' : 'انتخاب همهٔ دپارتمان')">انتخاب همه</span>
                    </x-ui.buttons.form>
                @endif

                @if($hasMore)
                    <x-ui.buttons.load-more wire:key="up-load-more-button" :action="$loadMoreAction" text="نمایش بیشتر" icon="expand_more" :icon-size="'text-[14px]'" class="px-3 py-1 rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] text-[11px] font-bold hover:bg-[var(--md-sys-color-surface-container-highest)] active:scale-95 transition-all duration-200"/>
                @endif

                @if($mode === 'multi')
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg font-bold bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)]">
                        <span class="material-symbols-rounded text-[14px]">check_box</span>
                        <span class="tabular-nums" x-text="selectedCount">0</span>
                        <span>انتخاب شده</span>
                    </span>
                @endif
            </div>
        @endif
    </div>
@else
    <div {{ $attributes->except('wire:key') }} wire:key="{{ $rootKey }}-empty" class="contents">
        <x-ui.empty icon="group" title="{{ $emptyTitle }}"/>
    </div>
@endif
