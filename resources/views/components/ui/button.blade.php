@props(['type' => 'button', 'variant' => 'primary', 'icon' => null, 'loading' => false])

@php
    $classes = match($variant) {
        'primary' => 'bg-blue-600 hover:bg-blue-700 text-white shadow-lg shadow-blue-500/30 focus:ring-blue-500',
        'secondary' => 'bg-white hover:bg-gray-50 text-gray-900 border border-gray-200 dark:bg-white/5 dark:hover:bg-white/10 dark:text-white dark:border-white/10 focus:ring-gray-500',
        'danger' => 'bg-red-500 hover:bg-red-600 text-white shadow-lg shadow-red-500/30 focus:ring-red-500',
        'outline' => 'border border-gray-300 bg-transparent hover:bg-gray-50 text-gray-700 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5 focus:ring-gray-500',
        'ghost' => 'bg-transparent hover:bg-gray-100 text-gray-700 dark:text-gray-300 dark:hover:bg-white/5',
        default => 'bg-blue-600 text-white',
    };
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => "relative inline-flex items-center justify-center rounded-xl px-5 py-2.5 text-sm font-semibold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transform active:scale-95 $classes"]) }}>
    @if($loading)
        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    @elseif($icon)
        <i class="{{ $icon }} mr-2 text-lg"></i>
    @endif
    {{ $slot }}
</button>
