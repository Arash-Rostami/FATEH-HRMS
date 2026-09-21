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
])

@if(count($candidates) > 0)
    <div {{ $attributes->merge(['class' => $disabled ? 'opacity-60 pointer-events-none' : '']) }} @if($search) x-data="{ memberQuery: '' }" @endif>
        @if($search)
            <x-dashboard.member-search/>
        @endif

        <div class="{{ $height }} overflow-y-auto overscroll-contain custom-scrollbar {{ $rounded }} border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-surface)_85%,transparent)] shadow-sm">
            <div class="flex flex-col p-1.5 gap-1">
                @foreach($candidates as $candidate)
                    <label data-name="{{ $candidate[$nameKey] ?? '' }}"
                           x-show="memberQuery === '' || $el.dataset.name.toLowerCase().includes(memberQuery.toLowerCase())"
                           class="group/cb relative flex items-center gap-3.5 px-3 {{ $py }} rounded-lg hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_6%,transparent)] hover:shadow-sm cursor-pointer transition-all duration-300 ease-out hover:-translate-y-[0.5px]">

                        <!-- Identical Checkbox Implementation -->
                        <div class="relative inline-flex items-center justify-center w-5 h-5 shrink-0 transition-transform duration-300 ease-out group-hover/cb:scale-105">
                            <input type="checkbox"
                                   value="{{ $candidate['id'] }}"
                                   wire:model="{{ $model }}"
                                   @if($disabled) disabled @endif
                                   class="peer appearance-none w-5 h-5 rounded-[6px] border-2 border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_80%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-highest)_40%,transparent)] checked:border-[var(--md-sys-color-primary)] checked:bg-[var(--md-sys-color-primary)] hover:border-[color-mix(in_srgb,var(--md-sys-color-primary)_70%,transparent)] focus:outline-none focus:ring-4 focus:ring-[color-mix(in_srgb,var(--md-sys-color-primary)_20%,transparent)] focus:ring-offset-0 checked:shadow-[0_2px_10px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)] transition-all duration-300 ease-out cursor-pointer"/>
                            <span class="material-symbols-rounded absolute text-[var(--md-sys-color-on-primary)] text-[15px] font-black scale-0 opacity-0 peer-checked:scale-100 peer-checked:opacity-100 pointer-events-none transition-all duration-300 ease-out">check</span>
                        </div>

                        <!-- Fixed: Added text-right so it doesn't center inside modals -->
                        <span class="text-right text-[13.5px] font-semibold text-[var(--md-sys-color-on-surface)] group-hover/cb:text-[var(--md-sys-color-primary)] transition-colors duration-300 select-none truncate flex-1">
                            {{ $candidate[$nameKey] ?? '' }}
                        </span>
                    </label>
                @endforeach

                <!-- Fancy Empty Search State -->
                <div x-show="memberQuery !== '' && ![...$el.parentElement.querySelectorAll('[data-name]')].some(el => el.dataset.name.toLowerCase().includes(memberQuery.toLowerCase()))"
                     style="display: none;"
                     class="py-6 flex flex-col items-center justify-center text-center animate-toast-in">
                    <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-on-surface-variant)] opacity-40 mb-2">person_search</span>
                    <span class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-highest)_60%,transparent)] text-[12px] font-medium text-[var(--md-sys-color-on-surface-variant)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] shadow-sm">
                        کاربری با این نام یافت نشد
                    </span>
                </div>
            </div>
        </div>
    </div>
@else
    <div {{ $attributes }} class="contents">
        <x-ui.empty icon="group" title="{{ $emptyTitle }}"/>
    </div>
@endif
