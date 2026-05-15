@php
    $items = $getState()['items'] ?? [];
    $empty = $getState()['empty'] ?? '';
@endphp

<div class="fi-section-content" dir="rtl">
    @if (empty($items))
        <div
            class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
            {{ $empty }}
        </div>
    @else
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            @foreach ($items as $item)
                @php
                    $review = $item['review'];
                    $color = match ($review->feedback) {
                        'agree'      => 'success',
                        'disagree'   => 'danger',
                        'neutral'    => 'gray',
                        'incomplete' => 'warning',
                        default      => 'gray',
                    };
                @endphp

                <div @class([
                    'rounded-xl border p-4 space-y-3 transition',
                    'border-primary-400 bg-primary-50 dark:border-primary-600 dark:bg-primary-950/30' => $item['is_ma'],
                    'border-warning-300 bg-warning-50 dark:border-warning-700 dark:bg-warning-950/30' => !$item['is_ma'] && $item['is_action'],
                    'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900' => !$item['is_ma'] && !$item['is_action'],
                ])>
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-sm font-bold text-gray-900 dark:text-gray-100 truncate">
                                {{ $item['label'] }}
                            </span>
                            @if ($item['is_ma'])
                                <x-filament::badge color="primary" size="xs">CEO</x-filament::badge>
                            @endif
                            @if ($item['is_action'])
                                <x-filament::badge color="warning" size="xs">
                                    @lang('resources/suggestion/strings.fields.referral_depts')
                                </x-filament::badge>
                            @endif
                        </div>
                        <x-filament::badge :color="$color" size="xs">
                            {{ $item['feedback_label'] }}
                        </x-filament::badge>
                    </div>

                    @if ($review->comments)
                        <p class="text-xs leading-relaxed text-gray-700 dark:text-gray-300">
                            {{ $review->comments }}
                        </p>
                    @endif

                    @if ($review->actions)
                        <div
                            class="rounded-lg border border-primary-200 bg-primary-50 px-3 py-2 dark:border-primary-700 dark:bg-primary-950/40">
                            <div class="text-[11px] font-bold text-primary-700 dark:text-primary-300">
                                @lang('resources/suggestion/strings.fields.referral_actions')
                            </div>
                            <p class="mt-1 text-xs leading-6 text-gray-800 dark:text-gray-200">
                                {{ $review->actions }}
                            </p>
                        </div>
                    @endif

                    <div
                        class="flex items-center justify-between border-t border-gray-200 pt-2 text-[11px] text-gray-500 dark:border-gray-700 dark:text-gray-400">
                        <span>{{ jdateOnly($review->created_at) }}</span>
                        @if ($item['is_action'])
                            @if ($review->complete)
                                <x-filament::badge color="success" icon="heroicon-o-check-circle" size="xs">
                                    @lang('resources/suggestion/strings.actions.mark_complete')
                                </x-filament::badge>
                            @else
                                <x-filament::badge color="gray" icon="heroicon-o-clock" size="xs">
                                    @lang('resources/suggestion/strings.tabs.pending')
                                </x-filament::badge>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
