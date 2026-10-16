@php($insight = $getState())

<div class="space-y-5" dir="rtl">
    @include('filament.resources.ths.insights.cards', ['cards' => $insight['cards'], 'scope' => 'ticket'])

    <div class="space-y-2">
        <p class="text-sm font-semibold text-gray-950 dark:text-white">@lang('resources/ths/strings.insight.similar_title')</p>

        @forelse ($insight['similar'] as $row)
            <a wire:key="ths-insight-similar-{{ $row['id'] }}"
               href="{{ \App\Filament\Resources\ThsResource::getUrl('edit', ['record' => $row['id']]) }}"
               class="flex items-center justify-between gap-3 rounded-xl px-4 py-2.5 ring-1 ring-gray-950/5 transition hover:bg-gray-50 dark:ring-white/10 dark:hover:bg-white/5">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="font-mono text-xs text-gray-500 dark:text-gray-400" dir="ltr">{{ $row['code'] }}</span>
                    <span class="truncate text-sm text-gray-950 dark:text-white">{{ $row['subject'] }}</span>
                </span>
                <x-filament::badge :color="$row['color']" size="xs">{{ $row['status'] }}</x-filament::badge>
            </a>
        @empty
            <p wire:key="ths-insight-similar-empty" class="text-sm text-gray-500 dark:text-gray-400">
                @lang('resources/ths/strings.insight.similar_empty')
            </p>
        @endforelse
    </div>
</div>
