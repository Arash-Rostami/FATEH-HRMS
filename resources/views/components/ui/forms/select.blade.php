@props(['label', 'name', 'disabled' => false, 'icon' => null, 'searchable' => false, 'options' => [], 'emptyText' => 'موردی یافت نشد', 'columnHint' => null])
@php($objectMode = $searchable && filled($options) && !is_string(\Illuminate\Support\Arr::first($options)))

<div
    class="relative group w-full md3-input-group"
    @if($searchable)
        x-data="{
        open: false, pos: {}, search: '',
        selected: @entangle($attributes->wire('model')->value() ?? ''),
        options: @js($options),
        objectMode: @js($objectMode),
        norm(v) { return (v ?? '').toString().toLowerCase().replace(/ي/g, 'ی').replace(/ك/g, 'ک'); },
        get filtered() {
            const q = this.norm(this.search);
            const list = !q ? this.options : this.options.filter(o => this.norm(this.objectMode ? o.label : o).includes(q));
            return list.slice(0, 50);
        },
        select(opt) {
            this.selected = this.objectMode ? opt.value : opt;
            this.search = this.objectMode ? opt.label : opt;
            this.open = false;
            this.$refs.hiddenInput.value = this.selected;
            this.$refs.hiddenInput.dispatchEvent(new Event('input'));
        },
        init() {
            if (!this.selected) return;
            this.search = this.objectMode
                ? (this.options.find(o => o.value == this.selected)?.label ?? this.selected)
                : this.selected;
        }
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

    @if(!$searchable)
        <select
            name="{{ $name }}" id="{{ $name }}"
            {{ $disabled ? 'disabled' : '' }}
            {!! $attributes->merge(['class' => 'md3-input peer appearance-none ' . ($icon ? 'pr-10' : '')]) !!}
        >{{ $slot }}</select>
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
                <template x-for="(opt, index) in filtered" :key="objectMode ? opt.value : opt">
                    <div>
                        <div
                            x-show="objectMode && opt.category && (index === 0 || filtered[index - 1].category !== opt.category)"
                            class="px-4 py-1.5 text-[10px] font-bold text-[var(--md-sys-color-on-surface-variant)] bg-[var(--md-sys-color-surface-container-high)]"
                            x-text="opt.category"
                        ></div>
                        <div
                            @click="select(opt)"
                            :class="(objectMode ? selected === opt.value : selected === opt) ? 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
                            class="px-4 py-2.5 cursor-pointer text-sm transition-colors"
                            x-text="objectMode ? opt.label : opt"
                        ></div>
                    </div>
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
        <label for="{{ $name }}" @if($columnHint) title="نام فیلد در پایگاه‌داده: {{ $columnHint }}" @endif class="md3-label {{ $icon ? 'peer-placeholder-shown:right-10 peer-focus:right-4' : 'right-4' }}">{{ $label }}</label>
    @else
        <label @if($columnHint) title="نام فیلد در پایگاه‌داده: {{ $columnHint }}" @endif class="md3-label {{ $icon ? 'peer-placeholder-shown:right-10 peer-focus:right-4' : 'right-4' }} pointer-events-none">{{ $label }}</label>
    @endif

    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-[var(--md-sys-color-on-surface-variant)]">
        <span class="material-symbols-rounded">expand_more</span>
    </div>

    @error($name)
    <p class="mt-1 text-xs text-[var(--md-sys-color-error)] animate-pulse">{{ $message }}</p>
    @enderror
</div>
