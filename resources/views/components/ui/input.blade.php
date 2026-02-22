@props(['label', 'name', 'type' => 'text', 'disabled' => false, 'icon' => null])

<div class="relative group w-full">
    @if($icon)
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-500 transition-colors">
            <i class="{{ $icon }}"></i>
        </div>
    @endif

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        {{ $disabled ? 'disabled' : '' }}
        {!! $attributes->merge([
            'class' => 'peer block w-full rounded-xl border border-gray-200 bg-white/50 py-3 px-4 text-gray-900 shadow-sm transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 disabled:opacity-50 disabled:cursor-not-allowed dark:border-white/10 dark:bg-white/5 dark:text-white dark:focus:border-blue-400 ' . ($icon ? 'pl-10' : '')
        ]) !!}
        placeholder=" "
    />
    <label for="{{ $name }}"
           class="absolute left-3 top-3 z-10 origin-[0] -translate-y-6 scale-75 transform text-sm text-gray-500 duration-300 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-100 peer-focus:left-3 peer-focus:-translate-y-6 peer-focus:scale-75 peer-focus:text-blue-500 dark:text-gray-400 dark:peer-focus:text-blue-400 {{ $icon ? 'peer-placeholder-shown:left-10' : '' }}">
        {{ $label }}
    </label>

    @error($name)
        <p class="mt-1 text-xs text-red-500 animate-pulse">{{ $message }}</p>
    @enderror
</div>
