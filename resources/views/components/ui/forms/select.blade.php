@props(['label', 'name', 'disabled' => false, 'icon' => null, 'searchable' => false, 'options' => [], 'htmlOptions' => [], 'emptyText' => 'موردی یافت نشد'])

<div
    class="relative group w-full md3-input-group"
    @if($searchable)
        x-data="{
        open: false, pos: {}, search: '',
        selected: @entangle($attributes->wire('model')?->value() ?? ''),
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

    @if(!$searchable && empty($htmlOptions))
        <select
            name="{{ $name }}" id="{{ $name }}"
            {{ $disabled ? 'disabled' : '' }}
            {!! $attributes->merge(['class' => 'md3-input peer appearance-none ' . ($icon ? 'pr-10' : '')]) !!}
        >{{ $slot }}</select>
    @elseif(!empty($htmlOptions))
        <input type="hidden" name="{{ $name }}" x-ref="hiddenInput" {{ $attributes->wire('model') }}/>
        <div x-data="{
            open: false, pos: {},
            selected: @entangle($attributes->wire('model')?->value() ?? ''),
            htmlOptions: @js($htmlOptions),
            select(v) {
                this.selected = v; this.open = false;
                if(this.$refs.hiddenInput) {
                    this.$refs.hiddenInput.value = v;
                    this.$refs.hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }
        }">
            <div
                @click="pos = $el.getBoundingClientRect(); open = !open"
                @click.away="open = false"
                {!! $attributes->merge(['class' => 'md3-input peer cursor-pointer flex items-center min-h-[3.5rem] ' . ($icon ? 'pr-10' : '')]) !!}
            >
                <div x-html="(selected !== null && selected !== '' && htmlOptions[selected]) ? htmlOptions[selected] : ''" class="w-full truncate text-sm"></div>
            </div>

            <template x-teleport="body">
                <div
                    x-show="open"
                    x-cloak x-transition
                    :style="{ position:'fixed', zIndex:9999, top:pos.bottom+'px', left:pos.left+'px', width:pos.width+'px' }"
                    class="mt-1 bg-white dark:bg-gray-800 border border-[var(--md-sys-color-outline-variant)] rounded-xl shadow-lg max-h-60 overflow-y-auto text-right"
                >
                    <template x-for="(html, val) in htmlOptions" :key="val">
                        <div
                            @click="select(val)"
                            class="px-4 py-2.5 cursor-pointer text-sm transition-colors"
                            :class="selected == val ? 'bg-[var(--md-sys-color-primary-container)]' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
                            x-html="html">
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
