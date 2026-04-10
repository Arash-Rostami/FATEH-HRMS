@props([
    'name' => 'search',
    'model' => 'search',
    'placeholder' => 'جستجو...',
    'debounce' => 400,
    'icon' => 'search',
    'clearable' => true,
    'disabled' => false,
])

<div class="relative group w-full" wire:ignore.self>
    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-[var(--md-sys-color-on-surface-variant)] group-focus-within:text-[var(--md-sys-color-primary)] transition-colors z-10">
        <span class="material-symbols-rounded text-[20px]">{{ $icon }}</span>
    </div>

    <input
        type="text"
        name="{{ $name }}"
        id="{{ $name }}"
        wire:model.live.debounce.{{ $debounce }}ms="{{ $model }}"
        {{ $disabled ? 'disabled' : '' }}
        {{ $attributes->merge([
            'class' => 'md3-input peer pr-10 pl-10 h-10 rounded-xl text-sm outline-none transition-all focus:ring-2
                       bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)]
                       border border-[var(--md-sys-color-outline-variant)]/50
                       placeholder-transparent focus:placeholder-transparent
                       peer-placeholder-shown:placeholder-[var(--md-sys-color-on-surface-variant)]'
        ]) }}
        placeholder="{{ $placeholder }}"
        autocomplete="off"
        spellcheck="false"
    />

    @if($clearable)
        <button
            title="حذف"
            type="button"
            wire:click="$set('{{ $model }}', '')"
            wire:loading.attr="disabled"
            class="absolute inset-y-0 left-0 pl-3 flex items-center text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] transition-colors {{ '$' . $model }} ? 'opacity-100' : 'opacity-0 pointer-events-none'"
            x-data="{ show: @entangle($model).live }"
            x-show="show && show.length > 0"
            x-transition:opacity
        >
            <span class="material-symbols-rounded text-[18px]">close</span>
        </button>
    @endif

    <label for="{{ $name }}"
           class="md3-label peer-placeholder-shown:right-10 peer-focus:right-4 right-4">
        {{ $placeholder }}
    </label>

    @error($name)
    <p class="mt-1 text-xs text-[var(--md-sys-color-error)] animate-pulse">{{ $message }}</p>
    @enderror
</div>
