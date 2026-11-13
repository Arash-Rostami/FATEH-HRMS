@use('App\Services\AiReport\BlockCatalog')
@props(['block' => [], 'lang' => 'fa', 'editable' => false, 'ref' => null])
@php($percent = $block['percent_column'] ?? null)
@php($status = $block['status_column'] ?? null)

<div class="flex flex-col gap-2 print:gap-1">
    @if(filled($block['heading'] ?? null) || ($block['check'] ?? null) === 'mismatch')
        <div wire:key="ai-report-table-head" class="flex flex-wrap items-center gap-2">
            @if(filled($block['heading'] ?? null))
                <h2 wire:key="ai-report-table-heading" class="text-sm font-bold text-[var(--md-sys-color-on-surface)]" dir="auto">{{ $block['heading'] }}</h2>
            @endif
            @if(($block['check'] ?? null) === 'mismatch')
                @if($editable && $ref !== null)
                    <button wire:key="ai-report-block-check-tap-{{ str_replace(':', '-', $ref) }}" type="button" wire:click="editBlock('{{ $ref }}', true)" aria-label="بررسی این رقم با هوش مصنوعی" title="بررسی این رقم با هوش مصنوعی"
                            class="print:hidden inline-flex rounded-md cursor-pointer hover:opacity-80 transition-opacity focus-visible:outline focus-visible:outline-2 focus-visible:outline-[var(--md-sys-color-primary)]">
                        <x-ui.badge variant="warning" class="items-center gap-1">
                            <span class="material-symbols-rounded text-[13px]">warning</span>
                            {{ $lang === 'en' ? 'Figures do not add up' : 'ارقام با یکدیگر همخوانی ندارند' }}
                        </x-ui.badge>
                    </button>
                    <x-ui.badge wire:key="ai-report-block-check-print-{{ str_replace(':', '-', $ref) }}" variant="warning" class="hidden print:inline-flex items-center gap-1">
                        <span class="material-symbols-rounded text-[13px]">warning</span>
                        {{ $lang === 'en' ? 'Figures do not add up' : 'ارقام با یکدیگر همخوانی ندارند' }}
                    </x-ui.badge>
                @else
                    <x-ui.badge wire:key="ai-report-block-check-static" variant="warning" class="items-center gap-1">
                        <span class="material-symbols-rounded text-[13px]">warning</span>
                        {{ $lang === 'en' ? 'Figures do not add up' : 'ارقام با یکدیگر همخوانی ندارند' }}
                    </x-ui.badge>
                @endif
            @endif
        </div>
    @endif

    <div class="overflow-x-auto print:overflow-visible rounded-xl border border-[var(--md-sys-color-outline-variant)]/50 print:rounded-none print:border-0">
        <table class="w-full text-[12px] print:text-xs border-collapse">
            <thead class="bg-[var(--md-sys-color-surface-container-high)]">
                <tr>
                    @foreach(($block['columns'] ?? []) as $column)
                        <th class="px-3 py-2 text-start font-bold text-[var(--md-sys-color-on-surface)]">{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-[var(--md-sys-color-outline-variant)]/40">
                @foreach(($block['rows'] ?? []) as $row)
                    <tr>
                        @foreach($row as $index => $cell)
                            @if($index === $percent)
                                @php($n = BlockCatalog::number($cell))
                                @php($bare = $n !== null && preg_match('/^[\s\d۰-۹٠-٩.,٬٫+-]+$/u', $cell) === 1)
                                <td class="px-3 py-1.5 text-start tabular-nums align-middle whitespace-nowrap">
                                    <span class="inline-flex items-center justify-start gap-1.5">
                                        <span class="inline-block w-14 h-1.5 rounded-full bg-[var(--md-sys-color-surface-variant)] overflow-hidden align-middle">
                                            @if($n !== null)
                                                <span wire:key="ai-report-table-percent-bar" class="block h-full rounded-full bg-[var(--md-sys-color-primary)]" style="width: {{ min(100, max(0, $n)) }}%"></span>
                                            @endif
                                        </span>
                                        <bdi>{{ $bare ? trim($cell) . ($lang === 'en' ? '%' : '٪') : $cell }}</bdi>
                                    </span>
                                </td>
                            @elseif($index === $status)
                                @php($tone = BlockCatalog::tone($cell))
                                <td class="px-3 py-1.5 align-middle {{ $tone !== null ? 'text-start whitespace-nowrap' : 'text-start break-words max-w-[12rem] text-[var(--md-sys-color-on-surface)]' }}">
                                    @if($tone !== null)
                                        <span wire:key="ai-report-table-status-badge" class="contents"><x-ui.badge :variant="$tone"><bdi>{{ $cell }}</bdi></x-ui.badge></span>
                                    @else
                                        <bdi wire:key="ai-report-table-status-text">{{ $cell }}</bdi>
                                    @endif
                                </td>
                            @else
                                @php($numeric = BlockCatalog::number($cell) !== null)
                                <td class="px-3 py-1.5 {{ $numeric ? 'text-start tabular-nums' : 'text-start break-words max-w-[12rem] text-[var(--md-sys-color-on-surface)]' }}"><bdi>{{ $cell }}</bdi></td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
                @if(!empty($block['total']))
                    <tr wire:key="ai-report-table-total" class="font-black border-t-2 border-[var(--md-sys-color-outline)]">
                        @foreach($block['total'] as $index => $cell)
                            @if($index === $percent)
                                @php($totalBare = BlockCatalog::number($cell) !== null && preg_match('/^[\s\d۰-۹٠-٩.,٬٫+-]+$/u', $cell) === 1)
                                <td class="px-3 py-2 text-start tabular-nums"><bdi>{{ $totalBare ? trim($cell) . ($lang === 'en' ? '%' : '٪') : $cell }}</bdi></td>
                            @else
                                @php($totalNumeric = BlockCatalog::number($cell) !== null)
                                <td class="px-3 py-2 {{ $totalNumeric ? 'text-start tabular-nums' : 'text-start break-words max-w-[12rem] text-[var(--md-sys-color-on-surface)]' }}"><bdi>{{ $cell }}</bdi></td>
                            @endif
                        @endforeach
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    @if(filled($block['footnote'] ?? null))
        <p wire:key="ai-report-table-footnote" class="text-[10.5px] leading-5 text-[var(--md-sys-color-on-surface-variant)]" dir="auto">{{ $block['footnote'] }}</p>
    @endif
</div>
