@use('App\Livewire\Dashboard\Correspondence\Presentation\CorrespondencePresenter')
@use('App\Services\Correspondence\CorrespondenceFonts')
@php
    $d = $d ?? ($presenter ?? new CorrespondencePresenter())->paperData($letter, $mode ?? 'screen');
    $web = $d['browser'];
    $m = array_map(fn ($v) => (float) $v, $d['margins']);
    $s = $d['style'];
    $c = $d['config'];
    $box = $d['box'];
    $font = preg_replace('/[^\w \-]/u', '', (string) $s['font']);
    $latin = preg_replace('/[^\w \-]/u', '', (string) $s['latin_font']);
    $headMm = (float) $c['header_height_mm'];
    $footMm = (float) $c['footer_height_mm'];
    $fixedHead = $c['repeat_header'] === true;
    $fixedFoot = $c['repeat_footer'] === true;
    $bottomSign = $c['signature_position'] === 'fixed_bottom';
    $flowStyle = $bottomSign ? 'height:' . $d['flow_mm'] . 'mm;' : '';
    $mainRowStyle = $bottomSign ? 'height:100%;' : '';
    $ccZone = $c['cc_position'] === 'after_body' ? 'main' : 'sign';
    $boxRight = ($box['side'] ?? 'left') === 'right';
    $solid = ($c['table']['border'] ?? 'solid') === 'solid';
    $stampOver = $c['stamp_position'] === 'over_signature' && $d['stamp_src'] !== '';
    $stampBeside = $c['stamp_position'] === 'beside_signature' && $d['stamp_src'] !== '';
    $bg = fn (string $src, string $position): string => $src === '' ? '' : 'background-image:url(' . $src . ');background-repeat:no-repeat;background-size:100% auto;background-position:' . $position . ';';
    $pageTop = $web ? $m['top'] : max(0, $m['top'] - $headMm / 10);
    $pageBottom = $web ? $m['bottom'] : max(0, $m['bottom'] - $footMm / 10);
@endphp
<style>
@if($web){!! CorrespondenceFonts::fontFace($d['fonts']) !!}@endif
@@page { size: {{ $d['page'] }}; margin: {{ $pageTop }}cm {{ $m['right'] }}cm {{ $pageBottom }}cm {{ $m['left'] }}cm;
@if($web && $d['page_numbers'])
@@bottom-center { @if($c['page_number_format'] === 'page_only') content: counter(page, persian); @else content: counter(page, persian) " / " counter(pages, persian); @endif font-family: "{{ $font }}", serif; font-size: {{ (int) $s['copy_size'] }}pt; color: #000; vertical-align: bottom; }
@endif
}
.correspondence-paper { position: relative; box-sizing: border-box; background: #fff; color: #000; font-family: "{{ $font }}", "{{ $latin }}", serif; font-size: {{ (int) $s['size'] }}pt; line-height: {{ (float) $s['line_height'] }}; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
.correspondence-paper table { border-collapse: collapse; }
.correspondence-paper p { margin: 0; }
.correspondence-paper .paper-flow { position: relative; z-index: 1; width: 100%; }
.correspondence-paper .paper-subject { font-size: {{ (int) $s['subject_size'] }}pt; font-weight: 700; margin: 0.5em 0; }
.correspondence-paper .paper-heading { font-weight: 700; margin-top: 0.5em; }
.correspondence-paper .paper-text { white-space: pre-line; }
.correspondence-paper .paper-cc { font-size: {{ (int) $s['copy_size'] }}pt; margin-top: 0.8em; page-break-inside: avoid; }
.correspondence-paper .paper-sign { page-break-inside: avoid; }
.correspondence-paper .paper-latin { font-family: "{{ $latin }}", serif; font-size: {{ (int) ($s['latin_sizes'][0] ?? 13) }}pt; }
.correspondence-paper .paper-html > p { text-indent: {{ (float) $s['indent'] }}mm; margin-bottom: 0.4em; }
.correspondence-paper .paper-html h2 { font-size: 1.2em; font-weight: 700; margin: 0.5em 0 0.3em; }
.correspondence-paper .paper-html h3 { font-size: 1.1em; font-weight: 700; margin: 0.5em 0 0.3em; }
.correspondence-paper .paper-html h4 { font-size: 1em; font-weight: 700; margin: 0.5em 0 0.3em; }
.correspondence-paper .paper-html ul { list-style: disc; padding-right: 1.5em; margin-bottom: 0.4em; }
.correspondence-paper .paper-html ol { list-style: decimal; padding-right: 1.5em; margin-bottom: 0.4em; }
.correspondence-paper .paper-html blockquote { margin: 0.4em 1.5em; padding-right: 0.8em; border-right: 2px solid #000; }
.correspondence-paper .paper-html hr { border: 0; border-top: 1px solid #000; margin: 0.6em 0; }
.correspondence-paper .paper-html a { color: #000; text-decoration: underline; }
.correspondence-paper .paper-html mark { color: #000; }
.correspondence-paper .paper-html table { width: 100%; margin: 0.4em 0; }
.correspondence-paper .paper-html td, .correspondence-paper .paper-html th { padding: 0.15em 0.4em; vertical-align: top; text-align: right; }
.correspondence-paper .paper-html th { font-weight: 700; }
.correspondence-paper .paper-html tr { page-break-inside: avoid; }
.correspondence-paper .paper-html thead { display: table-header-group; }
@if($solid)
.correspondence-paper .paper-html table, .correspondence-paper .paper-html th, .correspondence-paper .paper-html td { border: 1px solid #000; }
@endif
@if($web)
.correspondence-paper .paper-mark { position: absolute; left: 0; right: 0; text-align: center; font-weight: 700; pointer-events: none; user-select: none; z-index: 0; transform: rotate(-30deg); }
.correspondence-paper .paper-mark-draft { top: 38%; font-size: 72pt; opacity: 0.1; }
.correspondence-paper .paper-mark-text { top: 55%; font-size: 48pt; }
@media screen {
.correspondence-paper .paper-html hr[style*="page-break-after"] { border-top: 1px dashed #9ca3af; }
.correspondence-paper { flex-shrink: 0; width: {{ $d['width'] }}mm; min-height: {{ $d['height'] }}mm; margin: 0 auto; padding: {{ $m['top'] }}cm {{ $m['right'] }}cm {{ $m['bottom'] }}cm {{ $m['left'] }}cm; }
.correspondence-paper .paper-head { position: absolute; top: 0; left: 0; right: 0; }
.correspondence-paper .paper-foot { position: absolute; bottom: {{ $d['foot_gap'] }}cm; left: 0; right: 0; }
}
@media print {
.correspondence-paper .paper-html hr[style*="page-break-after"] { border: 0; margin: 0; height: 0; break-after: page; }
.correspondence-paper { width: auto; min-height: 0; padding: 0 !important; margin: 0 !important; margin-bottom: 0 !important; transform: none !important; }
.correspondence-paper .paper-head { position: {{ $fixedHead ? 'fixed' : 'absolute' }}; top: -{{ $m['top'] }}cm; left: -{{ $m['left'] }}cm; right: -{{ $m['right'] }}cm; }
@if($fixedFoot)
.correspondence-paper .paper-foot { position: fixed; bottom: -{{ max(0, $m['bottom'] - $d['foot_gap']) }}cm; left: -{{ $m['left'] }}cm; right: -{{ $m['right'] }}cm; }
@endif
.correspondence-paper .paper-mark { position: fixed; }
}
@endif
</style>
<article x-ref="paper" class="correspondence-paper" dir="rtl" lang="fa">
    <div class="paper-head">
        <table style="width:100%;height:{{ $headMm }}mm;">
            <tr>
                <td style="vertical-align:top;height:{{ $headMm }}mm;{{ $bg($d['head_src'], 'top center') }}">
                    <table style="width:100%;">
                        <tr>
                            @foreach($boxRight ? ['box', 'gap'] : ['gap', 'box'] as $part)
                                @if($part === 'box')
                                    <td style="width:40%;vertical-align:top;text-align:right;font-size:{{ (int) $box['font_size'] }}pt;padding-top:{{ (float) $box['top_mm'] }}mm;padding-{{ $boxRight ? 'right' : 'left' }}:{{ (float) $box['offset_mm'] }}mm;">
                                        <p>تاریخ: {{ $d['date'] !== '' ? $d['date'] : '……' }}</p>
                                        <p>شماره: <bdi>@if($d['number'] !== ''){!! $d['number'] !!}@else……@endif</bdi></p>
                                        <p>پیوست: {{ $d['attachment'] }}</p>
                                    </td>
                                @else
                                    <td style="width:60%;"></td>
                                @endif
                            @endforeach
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    @if($web && $d['draft'])
        <div wire:key="correspondence-paper-draft" class="paper-mark paper-mark-draft" aria-hidden="true">پیش‌نویس</div>
    @endif
    @if($web && $d['watermark'] !== '')
        <div wire:key="correspondence-paper-watermark" class="paper-mark paper-mark-text" aria-hidden="true" style="opacity:{{ (float) $c['watermark_opacity'] }};">{!! $d['watermark'] !!}</div>
    @endif

    <table class="paper-flow" style="{{ $flowStyle }}">
        @foreach(['main', 'sign'] as $zone)
            <tr style="{{ $zone === 'main' ? $mainRowStyle : '' }}">
                <td style="vertical-align:top;">
                    @if($zone === 'main')
                        @foreach($d['recipient'] as $line)
                            <p>{!! $line !!}</p>
                        @endforeach
                        <p class="paper-subject">موضوع: <bdi>{!! $d['subject'] !!}</bdi></p>
                        @if($d['frame']['opening'] !== '')
                            <p>{!! $d['frame']['opening'] !!}</p>
                        @endif
                        @foreach($d['blocks'] as $block)
                            <div wire:key="correspondence-paper-block-{{ $loop->index }}" class="contents">
                            @if($block['heading'] !== '')
                                <p class="paper-heading">{!! $block['heading'] !!}</p>
                            @endif
                            @if($block['type'] === 'image')
                                @if($block['src'] !== '')
                                    <div style="text-align:{{ $block['align'] }};margin:0.4em 0;">
                                        <img src="{{ $block['src'] }}" alt="" style="width:{{ $block['width'] }};height:auto;">
                                        @if($block['caption'] !== '')
                                            <p style="font-size:{{ (int) $s['copy_size'] }}pt;">{!! $block['caption'] !!}</p>
                                        @endif
                                    </div>
                                @endif
                            @elseif($block['type'] === 'html')
                                @foreach($block['parts'] as $part)
                                    @unless($loop->first)
                                        </div></td></tr></table><pagebreak /><table class="paper-flow" style="{{ $flowStyle }}"><tr style="{{ $mainRowStyle }}"><td style="vertical-align:top;"><div class="contents">
                                    @endunless
                                    <div class="paper-html">{!! $part !!}</div>
                                @endforeach
                            @else
                                <p class="paper-text">{!! $block['text'] !!}</p>
                            @endif
                            </div>
                        @endforeach
                        @if($d['frame']['closing'] !== '')
                            <p>{!! $d['frame']['closing'] !!}</p>
                        @endif
                    @elseif($d['signer'] !== null || $d['counterparty'])
                        <table wire:key="correspondence-paper-sign" class="paper-sign" style="width:100%;margin-top:1em;">
                            <tr>
                                <td style="width:40%;vertical-align:top;text-align:center;">
                                    @if($d['counterparty'])
                                        <p>طرف مقابل</p>
                                        <div style="height:18mm;"></div>
                                        <p>نام و امضا: ………………</p>
                                    @endif
                                </td>
                                <td style="width:20%;vertical-align:middle;text-align:center;">
                                    @if($stampBeside)
                                        <img src="{{ $d['stamp_src'] }}" alt="" style="height:20mm;width:auto;">
                                    @endif
                                </td>
                                <td style="width:40%;vertical-align:top;text-align:center;{{ $stampOver ? 'background-image:url(' . $d['stamp_src'] . ');background-repeat:no-repeat;background-position:center;background-size:auto 24mm;' : '' }}">
                                    @if($d['signer'] !== null)
                                        @if($d['signer']['src'] !== '')
                                            <img src="{{ $d['signer']['src'] }}" alt="" style="height:18mm;width:auto;">
                                        @else
                                            <div style="height:18mm;"></div>
                                        @endif
                                        <p style="font-weight:700;">{!! $d['signer']['name'] !!}</p>
                                        @if($d['signer']['title'] !== '')
                                            <p>{!! $d['signer']['title'] !!}</p>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        </table>
                    @endif
                    @if($ccZone === $zone && $d['cc'] !== [])
                        <div wire:key="correspondence-paper-cc" class="paper-cc">
                            <p>رونوشت:</p>
                            @foreach($d['cc'] as $copy)
                                <p>{{ convertToPersian($loop->iteration) }}- {!! $copy !!}</p>
                            @endforeach
                        </div>
                    @endif
                    @if($zone === 'sign' && $d['files'] !== [])
                        <div wire:key="correspondence-paper-files" class="paper-cc">
                            <p>پیوست:</p>
                            @foreach($d['files'] as $file)
                                <p>{{ convertToPersian($loop->iteration) }}- {!! $file !!}</p>
                            @endforeach
                        </div>
                    @endif
                </td>
            </tr>
        @endforeach
    </table>

    @if($d['foot_src'] !== '')
        <div class="paper-foot" wire:key="correspondence-paper-foot">
            <table style="width:100%;">
                <tr>
                    <td style="height:{{ $footMm }}mm;{{ $bg($d['foot_src'], 'bottom center') }}"></td>
                </tr>
            </table>
        </div>
    @endif
</article>
