@php
    $items = $getState()['items'] ?? [];
    $current = $getState()['current_step'] ?? 1;
@endphp

<div class="fi-section-content space-y-3" dir="rtl">
    @foreach ($items as $item)
        @php
            $state = $item['state'] ?? 'pending';
            $stateColor = match ($state) {
                'active'   => 'primary',
                'done'     => 'success',
                'rejected' => 'danger',
                default    => 'gray',
            };
            $stateLabel = $item['badge_label'] ?? '';
        @endphp

        <div @class([
            'flex items-start gap-4 rounded-xl border p-4 transition',
            'border-primary-300 bg-primary-50 dark:border-primary-700 dark:bg-primary-950/30' => $state === 'active',
            'border-success-300 bg-success-50 dark:border-success-700 dark:bg-success-950/30' => $state === 'done',
            'border-danger-300 bg-danger-50 dark:border-danger-700 dark:bg-danger-950/30' => $state === 'rejected',
            'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/30' => $state === 'pending',
        ])>
            <div @class([
                'flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-bold',
                'bg-primary-500 text-white' => $state === 'active',
                'bg-success-500 text-white' => $state === 'done',
                'bg-danger-500 text-white' => $state === 'rejected',
                'bg-gray-300 text-gray-700 dark:bg-gray-700 dark:text-gray-200' => $state === 'pending',
            ])>
                {{ $item['step'] }}
            </div>

            <div class="flex-1">
                <div class="flex items-center justify-between gap-2">
                    <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100">
                        {{ $item['title'] }}
                    </h4>
                    <x-filament::badge :color="$stateColor" size="xs">
                        {{ $stateLabel }}
                    </x-filament::badge>
                </div>
                <p class="mt-1 text-xs leading-6 text-gray-600 dark:text-gray-400">
                    {{ $item['description'] }}
                </p>
            </div>
        </div>
    @endforeach
</div>
