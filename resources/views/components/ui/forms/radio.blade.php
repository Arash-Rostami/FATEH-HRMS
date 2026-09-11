@props(['label', 'name', 'options' => [], 'disabled' => false, 'columnHint' => null])

<div class="relative group w-full md3-input-group">
    <div {{ $disabled ? 'aria-disabled="true"' : '' }}
         class="md3-radio md3-input flex flex-wrap items-center gap-x-5 gap-y-2 pt-6 pb-2.5">
        @foreach($options as $value => $optionText)
            <label class="flex items-center gap-2 cursor-pointer text-sm text-[var(--md-sys-color-on-surface)]">
                <input type="radio" name="{{ $name }}" value="{{ $value }}" {{ $disabled ? 'disabled' : '' }}
                       {!! $attributes->merge(['class' => 'md3-radio-dot']) !!}>
                <span>{{ $optionText }}</span>
            </label>
        @endforeach
    </div>

    <label @if($columnHint) title="نام فیلد در پایگاه‌داده: {{ $columnHint }}" @endif
           class="md3-label right-4 pointer-events-none">{{ $label }}</label>

    @error($name)
    <p class="mt-1 text-xs text-[var(--md-sys-color-error)] animate-pulse">{{ $message }}</p>
    @enderror
</div>