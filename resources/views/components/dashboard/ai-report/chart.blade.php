@props(['block' => [], 'lang' => 'fa', 'live' => true])
@php
    $fa = $lang !== 'en';
    $arr = fn ($v): array => is_array($v) ? array_values($v) : [];
    $str = fn ($v): string => is_scalar($v) ? (string) $v : '';
    $fmt = function ($n) use ($fa): string {
        if (!is_numeric($n) || !is_finite((float) $n)) {
            return '—';
        }
        $text = number_format((float) $n, 2, '.', ',');
        $text = rtrim(rtrim($text, '0'), '.');

        return $fa ? strtr((string) convertToPersian($text), [',' => '٬', '.' => '٫']) : $text;
    };
    $labels = array_map($str, $arr($block['labels'] ?? null));
    $series = array_values(array_filter($arr($block['series'] ?? null), 'is_array'));
    $heading = $str($block['heading'] ?? null);
    $unit = $str($block['unit'] ?? null);
    $json = json_encode($block, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG | JSON_PARTIAL_OUTPUT_ON_ERROR) ?: '{}';
@endphp

<div wire:key="ai-report-chart-{{ substr(md5($json), 0, 8) }}"
     class="ai-report-keep ai-report-chart flex flex-col gap-2 print:gap-1"
     x-data="{{ $live ? 'aiReportChart' : '{ drawn: false }' }}"
     data-chart="{{ $json }}" data-lang="{{ $lang }}">
    @if($heading !== '')
        <h2 class="text-sm font-bold text-[var(--md-sys-color-on-surface)]" dir="auto">{{ $heading }}</h2>
    @endif
    <div x-cloak x-show="drawn" class="relative w-full h-64 max-h-64 p-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/50 print:p-0 print:rounded-none print:border-0">
        <canvas x-ref="canvas" role="img" aria-label="{{ $heading }}"></canvas>
    </div>
    <div :class="drawn ? 'sr-only' : ''" class="overflow-x-auto print:overflow-visible rounded-xl border border-[var(--md-sys-color-outline-variant)]/50 print:rounded-none print:border-0">
        <table class="w-full text-[12px] print:text-xs border-collapse">
            <thead class="bg-[var(--md-sys-color-surface-container-high)]">
                <tr>
                    <th class="px-3 py-2 text-start font-bold text-[var(--md-sys-color-on-surface)]">{{ $fa ? 'دسته' : 'Category' }}</th>
                    @foreach($series as $serie)
                        <th class="px-3 py-2 text-start font-bold text-[var(--md-sys-color-on-surface)]"><bdi>{{ $str($serie['name'] ?? null) }}</bdi></th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-[var(--md-sys-color-outline-variant)]/40">
                @foreach($labels as $i => $label)
                    <tr>
                        <td class="px-3 py-1.5 text-start break-words max-w-[12rem] text-[var(--md-sys-color-on-surface)]"><bdi>{{ $label }}</bdi></td>
                        @foreach($series as $serie)
                            <td class="px-3 py-1.5 text-start tabular-nums"><bdi>{{ $fmt($arr($serie['values'] ?? null)[$i] ?? null) }}</bdi></td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($unit !== '')
        <p class="text-[10.5px] leading-5 text-[var(--md-sys-color-on-surface-variant)]" dir="auto">{{ $unit }}</p>
    @endif
</div>
