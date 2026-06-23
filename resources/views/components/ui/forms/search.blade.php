@props([
    'name' => 'search',
    'model' => 'search',
    'placeholder' => 'جستجو...',
    'debounce' => 400,
    'icon' => 'search',
    'clearable' => true,
    'disabled' => false,
])

<div class="relative group w-full"
     wire:ignore.self
     x-data="{ value: @entangle($model).live, fullscreen: false }">

    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-[var(--md-sys-color-on-surface-variant)] group-focus-within:text-[var(--md-sys-color-primary)] transition-colors z-10">
        <span class="material-symbols-rounded text-[20px]">{{ $icon }}</span>
    </div>

    <x-ui.forms.maximize-trigger class="inset-y-0 left-10"/>

    <input
        type="text"
        name="{{ $name }}"
        id="{{ $name }}"
        wire:model.live.debounce.{{ $debounce }}ms="{{ $model }}"
        {{ $disabled ? 'disabled' : '' }}
        {{ $attributes->merge([
            'class' => 'md3-input peer pr-10 pl-20 h-10 leading-[40px] rounded-xl text-sm outline-none transition-all focus:ring-2
                       bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)]
                       border border-[var(--md-sys-color-outline-variant)]/50
                       placeholder-transparent focus:placeholder-transparent'
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
            class="absolute inset-y-0 left-0 pl-3 flex items-center text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] transition-opacity duration-200"
            x-show="value && value.length > 0"
            x-transition:opacity
            style="display: none;"
        >
            <span class="material-symbols-rounded text-[18px]">close</span>
        </button>
    @endif

    <label for="{{ $name }}"
           class="md3-label absolute top-1/2 -translate-y-1/2 pointer-events-none peer-placeholder-shown:right-10 peer-focus:right-4 right-4 transition-all duration-200">
        {{ $placeholder }}
    </label>

    @error($name)
    <p class="mt-1 text-xs text-[var(--md-sys-color-error)] animate-pulse">{{ $message }}</p>
    @enderror

    <x-ui.forms.maximize-overlay :icon="$icon" :title="$placeholder" :disabled="$disabled"/>
</div>
