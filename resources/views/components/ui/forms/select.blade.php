@props(['label', 'name', 'disabled' => false, 'icon' => null, 'searchable' => false, 'options' => [], 'icons' => [], 'emptyText' => 'موردی یافت نشد'])

@php $withIcons = !empty($icons); @endphp

<div
    class="relative group w-full md3-input-group"
    @if($withIcons)
        x-data="{
            open: false, pos: {},
            selected: @entangle($attributes->wire('model')->value() ?? ''),
            options: @js($options),
            icons: @js($icons),
            get selectedLabel() { return this.options[this.selected] ?? ''; },
            get selectedIcon() { return this.icons[this.selected] ?? 'help_outline'; },
        }"
    @elseif($searchable)
        x-data="{
            open: false, pos: {}, search: '',
            selected: @entangle($attributes->wire('model')->value() ?? ''),
            options: @js($options),
            get filtered() {
                if (!this.search) return this.options.slice(0, 50);
                return this.options.filter(o => o.toLowerCase().includes(this.search.toLowerCase())).slice(0, 50);
            },
            select(v) {
                this.selected = this.search = v; this.open = false;
                this.$refs.hiddenInput.value = v;
                this.$refs.hiddenInput.dispatchEvent(new Event('input'));
            },
            init() { if (this.selected) this.search = this.selected; }
        }"
    @endif
>
    @if($searchable)
        <input type="hidden" name="{{ $name }}" x-ref="hiddenInput" {{ $attributes->wire('model') }}/>
    @endif

    @if($icon && !$withIcons)
        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-[var(--md-sys-color-on-surface-variant)] group-focus-within:text-[var(--md-sys-color-primary)] transition-colors">
            <span class="material-symbols-rounded text-[20px]">{{ $icon }}</span>
        </div>
    @endif

    @if($withIcons)

        <button type="button"
                @click="pos = $el.getBoundingClientRect(); open = !open"
                class="md3-input peer appearance-none w-full flex items-center gap-2 text-right cursor-pointer pr-3">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)] shrink-0"
                  x-text="selectedIcon"></span>
            <span class="flex-1 truncate text-sm" x-text="selectedLabel"></span>
        </button>

        <template x-teleport="body">
            <div
                x-show="open"
                @click.outside="open = false"
                x-cloak
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                :style="{ position:'fixed', zIndex:9999, top:(pos.bottom+4)+'px', left:pos.left+'px', width:pos.width+'px' }"
                class="bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] rounded-xl shadow-xl max-h-64 overflow-y-auto"
            >
                <template x-for="[value, optLabel] in Object.entries(options)" :key="value">
                    <div
                        @click="selected = value; open = false"
                        :class="selected === value
                            ? 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]'
                            : 'text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-container-high)]'"
                        class="flex items-center gap-3 px-3 py-2.5 cursor-pointer text-sm transition-colors"
                    >
                        <span class="material-symbols-rounded text-[var(--md-sys-color-primary)] text-[18px] shrink-0"
                              x-text="icons[value] ?? 'help_outline'"></span>
                        <span x-text="optLabel"></span>
                    </div>
                </template>
            </div>
        </template>

        <label class="md3-label right-4">{{ $label }}</label>

    @elseif(!$searchable)

        <select
            name="{{ $name }}" id="{{ $name }}"
            {{ $disabled ? 'disabled' : '' }}
            {!! $attributes->merge(['class' => 'md3-input peer appearance-none ' . ($icon ? 'pr-10' : '')]) !!}
        >{{ $slot }}</select>
        <label for="{{ $name }}" class="md3-label {{ $icon ? 'peer-placeholder-shown:right-10 peer-focus:right-4' : 'right-4' }}">{{ $label }}</label>

    @else

        <input
            type="text"
            x-model="search"
            @focus="pos = $el.getBoundingClientRect(); open = true"
            @click.away="open = false"
            placeholder="{{ $label }}"
            autocomplete="off"
            {!! $attributes->merge(['class' => 'md3-input peer appearance-none ' . ($icon ? 'pr-10' : '')]) !!}
        />
        <label class="md3-label {{ $icon ? 'peer-placeholder-shown:right-10 peer-focus:right-4' : 'right-4' }} pointer-events-none">{{ $label }}</label>

        <template x-teleport="body">
            <div
                x-show="open && filtered.length > 0"
                x-cloak x-transition
                :style="{ position:'fixed', zIndex:9999, top:pos.bottom+'px', left:pos.left+'px', width:pos.width+'px' }"
                class="mt-1 bg-white dark:bg-gray-800 border rounded-lg shadow-lg max-h-60 overflow-y-auto text-right"
            >
                <template x-for="opt in filtered" :key="opt">
                    <div
                        @click="select(opt)"
                        :class="selected === opt ? 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
                        class="px-4 py-2.5 cursor-pointer text-sm transition-colors"
                        x-text="opt"
                    ></div>
                </template>
            </div>
            <div
                x-show="open && !filtered.length && search.length"
                x-cloak
                :style="{ position:'fixed', zIndex:9999, top:pos.bottom+'px', left:pos.left+'px', width:pos.width+'px' }"
                class="mt-1 bg-white dark:bg-gray-800 border rounded-lg shadow-lg px-4 py-3 text-sm text-gray-500"
                x-text="'{{ $emptyText }}'"
            ></div>
        </template>

    @endif

    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-[var(--md-sys-color-on-surface-variant)] transition-transform"
         @if($withIcons) :class="{ 'rotate-180': open }" @endif>
        <span class="material-symbols-rounded">expand_more</span>
    </div>

    @error($name)
    <p class="mt-1 text-xs text-[var(--md-sys-color-error)] animate-pulse">{{ $message }}</p>
    @enderror
</div>
