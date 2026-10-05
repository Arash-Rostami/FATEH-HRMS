@props([
    'model' => null,
    'candidates' => [],
    'height' => 'max-h-48',
    'rounded' => 'rounded-xl',
    'py' => 'py-2.5',
    'nameKey' => 'name',
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
])

@if(count($candidates) > 0 || $server || ($mode === 'single' && $noneLabel))
    <div {{ $attributes->merge(['class' => $disabled ? 'opacity-60 pointer-events-none' : '']) }} @if($search && !$server) x-data="{ memberQuery: '' }" @endif>
        @if($search)
            <x-dashboard.member-search :wire-model="$server ? $searchModel : null"/>
            @if($server && count($deptOptions) > 0)
                <select wire:model.live="{{ $deptModel }}"
                        class="md3-input w-full h-10 mb-2 rounded-xl text-sm outline-none transition-all focus:ring-2 bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] border border-[var(--md-sys-color-outline-variant)]/50">
                    <option value="">همه دپارتمان‌ها</option>
                    @foreach($deptOptions as $code => $name)
                        <option value="{{ $code }}">{{ $name }}</option>
                    @endforeach
                </select>
            @endif
        @endif

        <div class="{{ $height }} overflow-y-auto overscroll-contain custom-scrollbar {{ $rounded }} border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-surface)_85%,transparent)] shadow-sm">
            <div class="flex flex-col p-1.5 gap-1">
                @if($mode === 'single' && $noneLabel)
                    <button type="button" wire:key="up-none"
                            x-on:click="$wire.set('{{ $model }}', null)"
                            :class="!$wire.{{ $model }} ? 'bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)]' : ''"
                            class="relative flex items-center gap-3.5 px-3 {{ $py }} rounded-lg hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_6%,transparent)] hover:shadow-sm cursor-pointer transition-all duration-300 ease-out hover:-translate-y-[0.5px] w-full">
                        <div class="relative inline-flex items-center justify-center w-5 h-5 shrink-0">
                            <div class="w-5 h-5 rounded-full border-2 transition-all duration-300 ease-out"
                                 :class="!$wire.{{ $model }} ? 'border-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-primary)]' : 'border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_80%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-highest)_40%,transparent)]'"></div>
                            <span class="material-symbols-rounded absolute text-[var(--md-sys-color-on-primary)] text-[13px] font-black transition-all duration-300 ease-out"
                                  :class="!$wire.{{ $model }} ? 'scale-100 opacity-100' : 'scale-0 opacity-0'">check</span>
                        </div>
                        <span class="text-right text-[13.5px] font-semibold text-[var(--md-sys-color-on-surface)] truncate flex-1">{{ $noneLabel }}</span>
                    </button>
                @endif

                @foreach($candidates as $candidate)
                    @if($mode === 'single')
                        <button type="button" wire:key="up-{{ $candidate['id'] }}"
                                x-on:click="$wire.set('{{ $model }}', {{ $candidate['id'] }})"
                                :class="Number($wire.{{ $model }} ?? 0) === {{ $candidate['id'] }} ? 'bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)]' : ''"
                                class="relative flex items-center gap-3.5 px-3 {{ $py }} rounded-lg hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_6%,transparent)] hover:shadow-sm cursor-pointer transition-all duration-300 ease-out hover:-translate-y-[0.5px] w-full">
                            <div class="relative inline-flex items-center justify-center w-5 h-5 shrink-0">
                                <div class="w-5 h-5 rounded-full border-2 transition-all duration-300 ease-out"
                                     :class="Number($wire.{{ $model }} ?? 0) === {{ $candidate['id'] }} ? 'border-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-primary)] shadow-[0_2px_10px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)]' : 'border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_80%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-highest)_40%,transparent)]'"></div>
                                <span class="material-symbols-rounded absolute text-[var(--md-sys-color-on-primary)] text-[13px] font-black transition-all duration-300 ease-out"
                                      :class="Number($wire.{{ $model }} ?? 0) === {{ $candidate['id'] }} ? 'scale-100 opacity-100' : 'scale-0 opacity-0'">check</span>
                            </div>
                            <span class="text-right text-[13.5px] font-semibold text-[var(--md-sys-color-on-surface)] truncate flex-1">{{ $candidate[$nameKey] ?? '' }}</span>
                        </button>
                    @else
                        <label @if($server) wire:key="up-{{ $candidate['id'] }}" @endif
                               @unless($server) data-name="{{ $candidate[$nameKey] ?? '' }}" x-show="memberQuery === '' || $el.dataset.name.toLowerCase().includes(memberQuery.toLowerCase())" @endunless
                               class="group/cb relative flex items-center gap-3.5 px-3 {{ $py }} rounded-lg hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_6%,transparent)] hover:shadow-sm cursor-pointer transition-all duration-300 ease-out hover:-translate-y-[0.5px]">

                            <div class="relative inline-flex items-center justify-center w-5 h-5 shrink-0 transition-transform duration-300 ease-out group-hover/cb:scale-105">
                                <input type="checkbox"
                                       value="{{ $candidate['id'] }}"
                                       wire:model="{{ $model }}"
                                       @if($disabled) disabled @endif
                                       class="peer appearance-none w-5 h-5 rounded-[6px] border-2 border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_80%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-highest)_40%,transparent)] checked:border-[var(--md-sys-color-primary)] checked:bg-[var(--md-sys-color-primary)] hover:border-[color-mix(in_srgb,var(--md-sys-color-primary)_70%,transparent)] focus:outline-none focus:ring-4 focus:ring-[color-mix(in_srgb,var(--md-sys-color-primary)_20%,transparent)] focus:ring-offset-0 checked:shadow-[0_2px_10px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)] transition-all duration-300 ease-out cursor-pointer"/>
                                <span class="material-symbols-rounded absolute text-[var(--md-sys-color-on-primary)] text-[15px] font-black scale-0 opacity-0 peer-checked:scale-100 peer-checked:opacity-100 pointer-events-none transition-all duration-300 ease-out">check</span>
                            </div>

                            <span class="text-right text-[13.5px] font-semibold text-[var(--md-sys-color-on-surface)] group-hover/cb:text-[var(--md-sys-color-primary)] transition-colors duration-300 select-none truncate flex-1">
                                {{ $candidate[$nameKey] ?? '' }}
                            </span>
                        </label>
                    @endif
                @endforeach

                @if(!$server)
                    <div x-show="memberQuery !== '' && ![...$el.parentElement.querySelectorAll('[data-name]')].some(el => el.dataset.name.toLowerCase().includes(memberQuery.toLowerCase()))"
                         style="display: none;"
                         class="py-6 flex flex-col items-center justify-center text-center animate-toast-in">
                        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-on-surface-variant)] opacity-40 mb-2">person_search</span>
                        <span class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-highest)_60%,transparent)] text-[12px] font-medium text-[var(--md-sys-color-on-surface-variant)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] shadow-sm">
                            کاربری با این نام یافت نشد
                        </span>
                    </div>
                @endif

                @if($server && count($candidates) === 0)
                    <div wire:key="up-empty" class="py-6 flex flex-col items-center justify-center text-center animate-toast-in">
                        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-on-surface-variant)] opacity-40 mb-2">person_search</span>
                        <span class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-highest)_60%,transparent)] text-[12px] font-medium text-[var(--md-sys-color-on-surface-variant)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] shadow-sm">
                            {{ $emptyTitle }}
                        </span>
                    </div>
                @endif
            </div>
        </div>

        @if($server && $hasMore)
            <button type="button" wire:key="up-load-more" wire:click="{{ $loadMoreAction }}" wire:loading.attr="disabled" wire:target="{{ $loadMoreAction }}"
                    class="group mx-auto mt-1 flex items-center gap-0.5 px-2 py-0.5 rounded-md text-[10px] font-semibold text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-primary)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_8%,transparent)] active:scale-95 transition-all">
                <span class="material-symbols-rounded text-[13px] transition-transform group-hover:translate-y-0.5" wire:loading.remove wire:target="{{ $loadMoreAction }}">expand_more</span>
                <span wire:loading.remove wire:target="{{ $loadMoreAction }}">بیشتر</span>
                <span class="material-symbols-rounded text-[13px] animate-spin" wire:loading wire:target="{{ $loadMoreAction }}">progress_activity</span>
            </button>
        @endif
    </div>
@else
    <div {{ $attributes }} class="contents">
        <x-ui.empty icon="group" title="{{ $emptyTitle }}"/>
    </div>
@endif