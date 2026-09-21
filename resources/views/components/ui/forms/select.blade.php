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
        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-[var(--md-sys-color-on-surface-variant)] group-focus-within:text-[var(--md-sys-color-primary)] transition-colors duration-300">
            <span class="material-symbols-rounded text-[20px]">{{ $icon }}</span>
        </div>
    @endif

    @if(!$searchable)
        <select
            name="{{ $name }}" id="{{ $name }}"
            {{ $disabled ? 'disabled' : '' }}
            {!! $attributes->merge(['class' => 'md3-input peer appearance-none transition-all duration-300 ' . ($icon ? 'pr-10' : '')]) !!}
        >{{ $slot }}</select>
    @else
        <input
            type="text"
            x-model="search"
            @focus="pos = $el.getBoundingClientRect(); open = true"
            @click.away="open = false"
            placeholder="{{ $label }}"
            autocomplete="off"
            {!! $attributes->merge(['class' => 'md3-input peer appearance-none transition-all duration-300 ' . ($icon ? 'pr-10' : '')]) !!}
        />

        <template x-teleport="body">
            <!-- Glassmorphic Dropdown Menu -->
            <div
                x-show="open && filtered.length > 0"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2 scale-[0.98]"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-2 scale-[0.98]"
                :style="{ position:'fixed', zIndex:9999, top:pos.bottom+'px', left:pos.left+'px', width:pos.width+'px' }"
                class="mt-2 bg-[color-mix(in_srgb,var(--md-sys-color-surface)_85%,transparent)] backdrop-blur-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] rounded-2xl shadow-[0_12px_40px_color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] max-h-64 overflow-y-auto custom-scrollbar text-right overflow-hidden"
            >
                <div class="flex flex-col p-1.5 gap-0.5">
                    <template x-for="(opt, index) in filtered" :key="objectMode ? opt.value : opt">
                        <div>
                            <!-- Sticky Category Header -->
                            <div
                                x-show="objectMode && opt.category && (index === 0 || filtered[index - 1].category !== opt.category)"
                                class="sticky top-0 z-10 px-3 pt-3 pb-1.5 text-[11px] font-black text-[var(--md-sys-color-primary)] uppercase tracking-wider bg-[color-mix(in_srgb,var(--md-sys-color-surface)_90%,transparent)] backdrop-blur-md select-none"
                                x-text="opt.category"
                            ></div>

                            <!-- Option Item -->
                            <div
                                @click="select(opt)"
                                :class="(objectMode ? selected === opt.value : selected === opt) ? 'bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] text-[var(--md-sys-color-primary)] font-bold' : 'text-[var(--md-sys-color-on-surface)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_8%,transparent)] hover:text-[var(--md-sys-color-primary)] font-medium'"
                                class="flex items-center justify-between px-3 py-2.5 rounded-xl cursor-pointer text-[13px] transition-all duration-200 ease-out select-none"
                            >
                                <span class="truncate" x-text="objectMode ? opt.label : opt"></span>

                                <!-- Active Checkmark -->
                                <span x-show="objectMode ? selected === opt.value : selected === opt" class="material-symbols-rounded text-[18px]">check</span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Empty State Dropdown -->
            <div
                x-show="open && !filtered.length && search.length"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2 scale-[0.98]"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                :style="{ position:'fixed', zIndex:9999, top:pos.bottom+'px', left:pos.left+'px', width:pos.width+'px' }"
                class="mt-2 bg-[color-mix(in_srgb,var(--md-sys-color-surface)_85%,transparent)] backdrop-blur-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] rounded-2xl shadow-sm px-4 py-8 flex flex-col items-center justify-center text-center"
            >
                <span class="material-symbols-rounded text-[28px] text-[var(--md-sys-color-on-surface-variant)] opacity-40 mb-2">search_off</span>
                <span class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-highest)_60%,transparent)] text-[12px] font-medium text-[var(--md-sys-color-on-surface-variant)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] shadow-sm" x-text="'{{ $emptyText }}'"></span>
            </div>
        </template>
    @endif

    @if(!$searchable)
        <label for="{{ $name }}" @if($columnHint) title="نام فیلد در پایگاه‌داده: {{ $columnHint }}" @endif class="md3-label transition-all duration-300 {{ $icon ? 'peer-placeholder-shown:right-10 peer-focus:right-4' : 'right-4' }}">{{ $label }}</label>
    @else
        <label @if($columnHint) title="نام فیلد در پایگاه‌داده: {{ $columnHint }}" @endif class="md3-label transition-all duration-300 {{ $icon ? 'peer-placeholder-shown:right-10 peer-focus:right-4' : 'right-4' }} pointer-events-none">{{ $label }}</label>
    @endif

    <!-- Caret Icon -->
    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-[var(--md-sys-color-on-surface-variant)] group-focus-within:text-[var(--md-sys-color-primary)] transition-colors duration-300">
        <span class="material-symbols-rounded transition-transform duration-300 ease-out" @if($searchable) :class="open ? 'rotate-180' : ''" @endif>expand_more</span>
    </div>

    <!-- Error Message -->
    @error($name)
    <p class="flex items-center gap-1 mt-1 text-[12px] font-medium text-[var(--md-sys-color-error)] animate-toast-in">
        <span class="material-symbols-rounded text-[14px]">error</span>
        {{ $message }}
    </p>
    @enderror
</div>
