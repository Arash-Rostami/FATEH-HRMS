@props(['label', 'name', 'type' => 'text', 'disabled' => false, 'icon' => null, 'maximizable' => false, 'columnHint' => null])

<div
    class="relative group w-full md3-input-group"
    @if($maximizable)
        wire:ignore.self
        x-data="{ value: @entangle($attributes->wire('model')->value() ?? '').live, fullscreen: false }"
    @endif
>
    @if($icon)
        <div
            class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-[var(--md-sys-color-on-surface-variant)] group-focus-within:text-[var(--md-sys-color-primary)] transition-colors">
            <span class="material-symbols-rounded text-[20px]">{{ $icon }}</span>
        </div>
    @endif

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        {{ $disabled ? 'disabled' : '' }}
        {!! $attributes->merge(['class' => 'md3-input peer ' . ($icon ? 'pr-10' : '') . ($maximizable ? ' pl-10' : '')]) !!}
        placeholder=" "
    />
    <label for="{{ $name }}"
           @if($columnHint) title="نام فیلد در پایگاه‌داده: {{ $columnHint }}" @endif
           class="md3-label {{ $icon ? 'peer-placeholder-shown:right-10 peer-focus:right-4' : 'right-4' }}">
        {{ $label }}
    </label>

    @if($maximizable)
        <x-ui.forms.maximize-trigger/>
        <x-ui.forms.maximize-overlay :icon="$icon ?? 'edit'" :title="$label" :disabled="$disabled"/>
    @endif

    @error($name)
    <p class="mt-1 text-xs text-[var(--md-sys-color-error)] animate-pulse">{{ $message }}</p>
    @enderror
</div>
