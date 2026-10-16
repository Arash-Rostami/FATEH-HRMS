<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
    @foreach ($cards as $card)
        <div wire:key="ths-insight-{{ $scope }}-{{ $card['key'] }}"
             class="space-y-2 rounded-xl bg-gray-50/30 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
            <div class="flex items-center gap-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                <x-filament::icon :icon="$card['icon']" class="h-4 w-4"/>
                {{ $card['label'] }}
            </div>
            <x-filament::badge :color="$card['color']" size="lg">{{ $card['value'] }}</x-filament::badge>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $card['hint'] }}</p>
        </div>
    @endforeach
</div>
