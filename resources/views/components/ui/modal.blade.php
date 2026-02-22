@props(['name', 'title' => null])

<div
    x-data="{ show: false, name: '{{ $name }}' }"
    x-show="show"
    x-on:open-modal.window="show = ($event.detail.name === name)"
    x-on:close-modal.window="show = false"
    x-on:keydown.escape.window="show = false"
    style="display: none;"
    class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
    x-cloak
>
    <div x-show="show" class="fixed inset-0 transform transition-all" x-on:click="show = false" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-md"></div>
    </div>

    <div x-show="show" class="mb-6 relative transform rounded-2xl overflow-hidden bg-white/90 shadow-2xl ring-1 ring-gray-900/5 transition-all sm:w-full sm:max-w-2xl sm:mx-auto dark:bg-gray-800/90 dark:ring-white/10 backdrop-blur-xl"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
        @if($title)
        <div class="bg-white/50 px-6 py-4 border-b border-gray-200/50 dark:bg-white/5 dark:border-white/5 flex justify-between items-center backdrop-blur-sm">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $title }}</h3>
            <button x-on:click="show = false" class="text-gray-400 hover:text-red-500 transition-colors rounded-lg p-1 hover:bg-red-50 dark:hover:bg-white/5">
                <span class="sr-only">Close</span>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        @endif

        <div class="p-6">
            {{ $slot }}
        </div>
    </div>
</div>
