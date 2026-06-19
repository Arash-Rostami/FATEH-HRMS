@props(['label', 'name', 'disabled' => false, 'icon' => null, 'searchable' => false, 'hasIcons' => false, 'options' => [], 'emptyText' => 'موردی یافت نشد'])

<div
    class="relative group w-full md3-input-group"
    @if($searchable)
        x-data="{
        open: false, pos: {}, search: '',
        selected: @if($attributes->wire('model')) @entangle($attributes->wire('model')->value()) @else null @endif,
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

    @if($icon)
        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-[var(--md-sys-color-on-surface-variant)] group-focus-within:text-[var(--md-sys-color-primary)] transition-colors">
            <span class="material-symbols-rounded text-[20px]">{{ $icon }}</span>
        </div>
    @endif

    @if(!$searchable && !$hasIcons)
        <select
            name="{{ $name }}" id="{{ $name }}"
            {{ $disabled ? 'disabled' : '' }}
            {!! $attributes->merge(['class' => 'md3-input peer appearance-none ' . ($icon ? 'pr-10' : '')]) !!}
        >{{ $slot }}</select>
    @elseif($hasIcons)
        <div x-data="{
            open: false, pos: {},
            selected: @if($attributes->wire('model')) @entangle($attributes->wire('model')->value()) @else null @endif,
            opts: [],
            updateOpts() {
                this.opts = Array.from(this.$refs.native.options).map(o => ({ v: o.value, t: o.text, svg: o.dataset.svg || '' }));
            },
            get selectedText() { return this.opts.find(o => o.v == this.selected)?.t || ''; },
            get selectedSvg() { return this.opts.find(o => o.v == this.selected)?.svg || ''; },
            select(v) {
                this.selected = v; this.open = false;
                this.$refs.native.value = v;
                this.$refs.native.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }"
        x-init="
            updateOpts();
            new MutationObserver(() => updateOpts()).observe($refs.native, { childList: true });
        "
        class="relative"
        >
            <select x-ref="native" name="{{ $name }}" id="{{ $name }}" {{ $attributes->wire('model') }} class="hidden">{{ $slot }}</select>

            <div
                @click="pos = $el.getBoundingClientRect(); open = !open"
                @click.away="open = false"
                {!! $attributes->merge(['class' => 'md3-input peer cursor-pointer flex items-center h-[3.5rem] ' . ($icon ? 'pr-10' : '')]) !!}
            >
                <div class="flex items-center gap-3 w-full truncate text-sm text-[var(--md-sys-color-on-surface)]">
                    <template x-if="selectedSvg">
                        <span class="text-[var(--md-sys-color-primary)] w-5 h-5 flex items-center justify-center shrink-0" x-html="selectedSvg"></span>
                    </template>
                    <span x-text="selectedText"></span>
                </div>
            </div>

            <template x-teleport="body">
                <div
                    x-show="open"
                    x-cloak x-transition
                    :style="{ position:'fixed', zIndex:9999, top:pos.bottom+'px', left:pos.left+'px', width:pos.width+'px' }"
                    class="mt-1 bg-white dark:bg-gray-800 border border-[var(--md-sys-color-outline-variant)] rounded-xl shadow-lg max-h-60 overflow-y-auto text-right"
                >
                    <template x-for="opt in opts" :key="opt.v">
                        <div
                            @click="select(opt.v)"
                            class="flex items-center gap-3 px-4 py-2.5 cursor-pointer text-sm transition-colors"
                            :class="selected == opt.v ? 'bg-[var(--md-sys-color-primary-container)]' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
                        >
                            <template x-if="opt.svg">
                                <span class="text-[var(--md-sys-color-primary)] w-5 h-5 flex items-center justify-center shrink-0" x-html="opt.svg"></span>
                            </template>
                            <span x-text="opt.t"></span>
                        </div>
                    </template>
                </div>
            </template>
        </div>
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

    @if(!$searchable)
        <label for="{{ $name }}" class="md3-label {{ $icon ? 'peer-placeholder-shown:right-10 peer-focus:right-4' : 'right-4' }}">{{ $label }}</label>
    @else
        <label class="md3-label {{ $icon ? 'peer-placeholder-shown:right-10 peer-focus:right-4' : 'right-4' }} pointer-events-none">{{ $label }}</label>
    @endif

    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-[var(--md-sys-color-on-surface-variant)]">
        <span class="material-symbols-rounded">expand_more</span>
    </div>

    @error($name)
    <p class="mt-1 text-xs text-[var(--md-sys-color-error)] animate-pulse">{{ $message }}</p>
    @enderror
</div>
