@props(['label', 'name', 'disabled' => false, 'icon' => null])

<div class="relative group w-full">
    @if($icon)
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-500 transition-colors">
            <i class="{{ $icon }}"></i>
        </div>
    @endif

    <select
        name="{{ $name }}"
        id="{{ $name }}"
        {{ $disabled ? 'disabled' : '' }}
        {!! $attributes->merge([
            'class' => 'peer block w-full rounded-xl border border-gray-200 bg-white/50 py-3 px-4 text-gray-900 shadow-sm transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 disabled:opacity-50 disabled:cursor-not-allowed dark:border-white/10 dark:bg-white/5 dark:text-white dark:focus:border-blue-400 appearance-none ' . ($icon ? 'pl-10' : '')
        ]) !!}
    >
        {{ $slot }}
    </select>
    <label for="{{ $name }}"
           class="absolute left-3 top-3 z-10 origin-[0] -translate-y-6 scale-75 transform text-sm text-gray-500 duration-300 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-100 peer-focus:left-3 peer-focus:-translate-y-6 peer-focus:scale-75 peer-focus:text-blue-500 dark:text-gray-400 dark:peer-focus:text-blue-400 {{ $icon ? 'peer-placeholder-shown:left-10' : '' }}">
        {{ $label }}
    </label>
    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
    </div>

    @error($name)
        <p class="mt-1 text-xs text-red-500 animate-pulse">{{ $message }}</p>
    @enderror
</div>
