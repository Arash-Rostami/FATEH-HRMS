@props(['label', 'name', 'disabled' => false, 'icon' => null])

<div class="relative group w-full md3-input-group">
    @if($icon)
        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-[var(--md-sys-color-on-surface-variant)] group-focus-within:text-[var(--md-sys-color-primary)] transition-colors">
            <span class="material-symbols-rounded text-[20px]">{{ $icon }}</span>
        </div>
    @endif

    <select
        name="{{ $name }}"
        id="{{ $name }}"
        {{ $disabled ? 'disabled' : '' }}
        {!! $attributes->merge([
            'class' => 'md3-input peer appearance-none ' . ($icon ? 'pr-10' : '')
        ]) !!}
    >
        {{ $slot }}
    </select>
    <label for="{{ $name }}"
           class="md3-label {{ $icon ? 'peer-placeholder-shown:right-10 peer-focus:right-4' : 'right-4' }}">
        {{ $label }}
    </label>
    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-[var(--md-sys-color-on-surface-variant)]">
        <span class="material-symbols-rounded">expand_more</span>
    </div>

    @error($name)
        <p class="mt-1 text-xs text-[var(--md-sys-color-error)] animate-pulse">{{ $message }}</p>
    @enderror
</div>
