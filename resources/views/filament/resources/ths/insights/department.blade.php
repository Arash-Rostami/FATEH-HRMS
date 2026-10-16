@php($insight = $getState())

@if ($insight === null)
    <p wire:key="ths-insight-dept-none" class="text-sm text-gray-500 dark:text-gray-400" dir="rtl">
        @lang('resources/ths/strings.insight.dept_none')
    </p>
@else
    <div wire:key="ths-insight-dept-body" class="space-y-6" dir="rtl">
        <p class="text-xs text-gray-500 dark:text-gray-400">
            @lang('resources/ths/strings.insight.dept_window', ['department' => $insight['department']])
        </p>

        @if ($insight['empty'])
            <p wire:key="ths-insight-dept-empty" class="text-sm text-warning-600 dark:text-warning-400">
                @lang('resources/ths/strings.insight.dept_empty')
            </p>
        @endif

        @foreach ($insight['sections'] as $section)
            <div wire:key="ths-insight-dept-section-{{ $section['key'] }}" class="space-y-2">
                <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $section['label'] }}</p>
                @include('filament.resources.ths.insights.cards', ['cards' => $section['cards'], 'scope' => 'dept-' . $section['key']])
            </div>
        @endforeach

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
            @foreach ($insight['tables'] as $table)
                <div wire:key="ths-insight-dept-table-{{ $table['key'] }}"
                     class="space-y-2 rounded-xl p-4 ring-1 ring-gray-950/5 dark:ring-white/10">
                    <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $table['label'] }}</p>
                    <table class="w-full text-start text-xs">
                        <thead>
                        <tr class="text-gray-500 dark:text-gray-400">
                            @foreach ($table['columns'] as $column)
                                <th class="py-1 text-start font-medium">{{ $column }}</th>
                            @endforeach
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($table['rows'] as $row)
                            <tr wire:key="ths-insight-dept-{{ $table['key'] }}-{{ $loop->index }}"
                                class="border-t border-gray-950/5 text-gray-950 dark:border-white/10 dark:text-white">
                                @foreach ($row as $cell)
                                    <td class="py-1.5">{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr wire:key="ths-insight-dept-{{ $table['key'] }}-empty">
                                <td colspan="{{ count($table['columns']) }}" class="py-2 text-gray-500 dark:text-gray-400">
                                    @lang('resources/ths/strings.insight.table_empty')
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>
    </div>
@endif
