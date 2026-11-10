@props(['spec' => [], 'dir' => 'rtl', 'author' => null, 'live' => true, 'editable' => false, 'manual' => false])

<div class="ai-report-doc flex flex-col gap-6 p-5 sm:p-8 rounded-2xl bg-[color-mix(in_srgb,var(--md-sys-color-surface)_92%,transparent)] border border-[var(--md-sys-color-outline-variant)]/50 shadow-[0_8px_32px_color-mix(in_srgb,var(--md-sys-color-primary)_18%,transparent)] print:border-0 print:rounded-none print:p-0 print:shadow-none"
     dir="{{ $dir }}"
     data-title="{{ $spec['title'] ?? '' }}"
     x-data="{ wasDark: false, wasTitle: '' }"
     x-on:beforeprint.window="wasDark = document.documentElement.classList.contains('dark'); document.documentElement.classList.remove('dark'); wasTitle = document.title; const t = $el.dataset.title; if (t) document.title = t"
     x-on:afterprint.window="if (wasDark) document.documentElement.classList.add('dark'); if (wasTitle !== '') document.title = wasTitle">
    <style media="print">@@page { size: A4; margin: 14mm; @@bottom-center { content: counter(page) " / " counter(pages); font: 10px sans-serif; color: #000; } }</style>
    <p class="ai-report-print-head hidden print:flex items-center justify-between gap-4 text-[10px] text-[var(--md-sys-color-on-surface-variant)]" aria-hidden="true">
        <bdi>{{ $author ?? '' }}</bdi>
        <span class="tabular-nums">{{ ($spec['lang'] ?? 'fa') === 'en' ? now()->format('j F Y') : toJalali(now(), 'j F Y') }}</span>
    </p>
    @foreach(($spec['pages'] ?? []) as $p => $page)
        <div class="ai-report-page flex flex-col gap-6">
            @foreach(($page['blocks'] ?? []) as $b => $block)
                @php($view = match ($block['type'] ?? '') {
                    'title' => 'title',
                    'kpi_row', 'facts' => 'kpi-row',
                    'summary', 'section' => 'section',
                    'table', 'action_items', 'progress' => 'table',
                    'chart' => 'chart',
                    'callout' => 'callout',
                    default => null,
                })
                @if($view !== null)
                    <div wire:key="ai-report-block-{{ $p }}-{{ $b }}-{{ $view }}" class="relative{{ $editable && $manual ? ' sm:pe-16 print:pe-0' : ($editable || $manual ? ' sm:pe-9 print:pe-0' : '') }}">
                        @if($editable || $manual)
                            <div wire:key="ai-report-block-tools-{{ $p }}-{{ $b }}" class="print:hidden absolute top-0 end-0 z-10 flex items-center gap-0.5 max-sm:static max-sm:justify-end max-sm:mb-1 opacity-60 hover:opacity-100 focus-within:opacity-100 transition-opacity">
                                @if($editable)
                                    <button wire:key="ai-report-block-refine-{{ $p }}-{{ $b }}" type="button" wire:click="editBlock('{{ $p }}:{{ $b }}')" title="اصلاح این بخش با هوش مصنوعی" aria-label="اصلاح این بخش با هوش مصنوعی"
                                            class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--ai-color)] hover:bg-[var(--ai-bg)] hover:text-[var(--ai-text)] transition-colors">
                                        <span class="material-symbols-rounded text-lg">auto_awesome</span>
                                    </button>
                                @endif
                                @if($manual)
                                    <button wire:key="ai-report-block-manual-{{ $p }}-{{ $b }}" type="button" wire:click="openBlockEditor('{{ $p }}:{{ $b }}')" title="ویرایش دستی این بخش" aria-label="ویرایش دستی این بخش"
                                            class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors">
                                        <span class="material-symbols-rounded text-lg">edit</span>
                                    </button>
                                @endif
                            </div>
                        @endif
                        <x-dynamic-component :component="'dashboard.ai-report.' . $view" :block="$block" :lang="$spec['lang'] ?? 'fa'" :live="$live" :editable="$editable" :ref="$p . ':' . $b"/>
                    </div>
                @endif
            @endforeach
        </div>
    @endforeach

    <div class="h-px bg-[var(--md-sys-color-outline-variant)]/50" aria-hidden="true"></div>
    <p class="ai-report-disclaimer text-[11px] text-[var(--md-sys-color-on-surface-variant)]" dir="auto">
        {{ ($spec['lang'] ?? 'fa') === 'en'
            ? 'This report was generated with AI assistance; verify all figures before use.'
            : 'این گزارش با کمک هوش مصنوعی تولید شده است؛ ارقام را پیش از استفاده بررسی کنید.' }}
    </p>
</div>
