@props(['title' => null, 'description' => null, 'actions' => null])

<div {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-2xl bg-white/60 p-6 shadow-xl ring-1 ring-gray-900/5 backdrop-blur-xl dark:bg-gray-800/40 dark:ring-white/10 transition-all hover:shadow-2xl hover:bg-white/70 dark:hover:bg-gray-800/50']) }}>
    @if($title)
        <div class="mb-6 flex items-center justify-between border-b border-gray-100 pb-4 dark:border-white/5">
            <div>
                <h3 class="text-lg font-semibold leading-6 text-gray-900 dark:text-white">{{ $title }}</h3>
                @if($description)
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
                @endif
            </div>
            @if($actions)
                <div class="flex items-center gap-2">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    {{ $slot }}
</div>
