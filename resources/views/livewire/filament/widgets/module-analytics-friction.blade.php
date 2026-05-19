<x-filament-widgets::widget>
    <x-filament::card>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Inter-Departmental Friction Index') }}
            </h2>
            @if ($filter)
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="filter">
                        <option value="module_a">Human Capital Burnout Predictor</option>
                        <option value="module_b">Inter-Departmental Friction Index</option>
                        <option value="module_c">Innovation Funnel</option>
                        <option value="module_d">Asset Saturation</option>
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            @endif
        </div>

        @if(empty($frictionData))
            <div class="flex items-center justify-center p-6 text-gray-500 dark:text-gray-400">
                <div class="text-center">
                    <x-filament::icon
                        icon="heroicon-o-x-circle"
                        class="mx-auto h-12 w-12 text-gray-400"
                    />
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No Data Available</h3>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" dir="rtl">
                @foreach ($frictionData as $data)
                    @php
                        $avgTime = floatval($data->avg_resolution_time);
                        $opacity = min(max($avgTime / 48, 0.1), 1);
                        $textColor = $opacity > 0.5 ? 'text-white' : 'text-gray-900 dark:text-gray-100';
                    @endphp
                    <div
                        class="p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 transition-all"
                        style="background-color: rgba(239, 68, 68, {{ $opacity }});"
                    >
                        <div class="flex flex-col space-y-2">
                            <div class="flex justify-between items-center {{ $textColor }}">
                                <span class="text-xs font-semibold uppercase tracking-wider opacity-80">Origin</span>
                                <span class="text-sm font-bold">{{ $data->origin }}</span>
                            </div>
                            <div class="flex justify-between items-center {{ $textColor }}">
                                <span class="text-xs font-semibold uppercase tracking-wider opacity-80">Destination</span>
                                <span class="text-sm font-bold">{{ $data->destination }}</span>
                            </div>
                            <div class="mt-4 pt-4 border-t border-white/20 flex justify-between items-center {{ $textColor }}">
                                <span class="text-xs font-medium">Avg Resolution</span>
                                <span class="text-lg font-bold">{{ number_format($avgTime, 1) }} hrs</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::card>
</x-filament-widgets::widget>
