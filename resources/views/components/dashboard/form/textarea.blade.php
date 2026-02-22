@props(['label', 'name', 'disabled' => false, 'icon' => null, 'rows' => 3])

<div class="relative group w-full md3-input-group">
    @if($icon)
        <div class="absolute top-4 right-0 pr-3 flex items-start pointer-events-none text-[var(--md-sys-color-on-surface-variant)] group-focus-within:text-[var(--md-sys-color-primary)] transition-colors">
            <span class="material-symbols-rounded text-[20px]">{{ $icon }}</span>
        </div>
    @endif

    <textarea
        name="{{ $name }}"
        id="{{ $name }}"
        rows="{{ $rows }}"
        {{ $disabled ? 'disabled' : '' }}
        {!! $attributes->merge([
            'class' => 'md3-input peer ' . ($icon ? 'pr-10' : '')
        ]) !!}
        placeholder=" "
    >{{ $slot }}</textarea>
    <label for="{{ $name }}"
           class="md3-label {{ $icon ? 'peer-placeholder-shown:right-10 peer-focus:right-4' : 'right-4' }}">
        {{ $label }}
    </label>

    @error($name)
        <p class="mt-1 text-xs text-[var(--md-sys-color-error)] animate-pulse">{{ $message }}</p>
    @enderror
</div>
