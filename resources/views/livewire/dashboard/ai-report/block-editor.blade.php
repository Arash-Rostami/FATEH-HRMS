@php
    $labels = ['heading' => 'عنوان', 'subtitle' => 'زیرعنوان', 'highlight' => 'نکتهٔ برجسته', 'footnote' => 'پانویس', 'risk' => 'ریسک', 'bullets' => 'نکته', 'paragraphs' => 'بند', 'items' => 'مورد', 'options' => 'گزینه', 'columns' => 'ستون', 'label' => 'برچسب', 'value' => 'مقدار', 'hint' => 'توضیح', 'text' => 'متن', 'rows' => 'ردیف', 'total' => 'جمع', 'series' => 'سری', 'name' => 'نام', 'labels' => 'دسته'];
    $skip = ['type', 'check', 'percent_column', 'status_column', 'tone', 'variant', 'pct', 'kind', 'unit'];
    $fieldLabel = fn (string $dot): string => collect(explode('.', $dot))->map(fn ($seg) => is_numeric($seg) ? convertToPersian((int) $seg + 1) : ($labels[$seg] ?? $seg))->implode(' ');
    $fields = collect(\Illuminate\Support\Arr::dot($blockDraft))->filter(fn ($v, $k) => is_string($v) && !in_array(explode('.', $k)[0], [...$skip, 'rows', 'total'], true) && !in_array(\Illuminate\Support\Str::afterLast($k, '.'), $skip, true));
    $width = count($blockDraft['columns'] ?? []);
    $long = ['paragraphs', 'bullets', 'highlight', 'footnote', 'risk', 'text'];
    $isAssumptions = ($blockDraft['type'] ?? null) === 'section'
        && \App\Services\AiReport\BlockCatalog::isAssumptionsHeading((string) ($blockDraft['heading'] ?? ''), (($report->spec['lang'] ?? 'fa') === 'en') ? 'en' : 'fa');
@endphp

@if($blockRef !== null)
    <div wire:key="ai-report-block-editor-form" class="flex flex-col gap-3" dir="auto">
        <p class="text-[11px] leading-5 text-[var(--md-sys-color-on-surface-variant)]">متن‌ها و ارقام را مستقیم ویرایش کنید؛ تغییر به‌عنوان نسخهٔ تازه ذخیره می‌شود و جمع ستون‌های عددی خودکار به‌روز می‌شود. امکان افزودن یا حذف ردیف وجود ندارد.</p>
        @foreach($fields as $dot => $value)
            @php($head = explode('.', $dot)[0])
            <div wire:key="ai-report-block-field-{{ str_replace('.', '-', $dot) }}">
                @if(in_array($head, $long, true) || in_array(\Illuminate\Support\Str::afterLast($dot, '.'), $long, true))
                    <x-ui.forms.textarea :label="$fieldLabel($dot)" name="blockDraft.{{ $dot }}" wire:model="blockDraft.{{ $dot }}" :rows="2" :maxlength="(int) config('ai_report.spec.paragraph')"/>
                @else
                    <x-ui.forms.input :label="$fieldLabel($dot)" name="blockDraft.{{ $dot }}" wire:model="blockDraft.{{ $dot }}" :maxlength="(int) config('ai_report.spec.short')" :disabled="$dot === 'heading' && $isAssumptions"/>
                @endif
            </div>
        @endforeach
        @if($width > 0 && !empty($blockDraft['rows']))
            <div wire:key="ai-report-block-grid" class="overflow-x-auto">
                <div class="grid gap-1.5 min-w-[28rem]" style="grid-template-columns: repeat({{ $width }}, minmax(0, 1fr))">
                    @foreach($blockDraft['columns'] as $j => $column)
                        <div wire:key="ai-report-block-head-{{ $j }}" class="px-3 py-2 text-[11px] font-bold text-start bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)] rounded-lg">{{ $column }}</div>
                    @endforeach
                    @foreach($blockDraft['rows'] as $i => $row)
                        @foreach($row as $j => $cell)
                            <div wire:key="ai-report-block-cell-{{ $i }}-{{ $j }}" class="contents">
                                <x-ui.forms.input :label="$fieldLabel('rows.' . $i) . ' · ' . ($blockDraft['columns'][$j] ?? '')" name="blockDraft.rows.{{ $i }}.{{ $j }}" wire:model="blockDraft.rows.{{ $i }}.{{ $j }}" :maxlength="(int) config('ai_report.spec.cell')"/>
                            </div>
                        @endforeach
                    @endforeach
                    @if(!empty($blockDraft['total']))
                        @foreach($blockDraft['total'] as $j => $cell)
                            <div wire:key="ai-report-block-total-{{ $j }}" class="contents">
                                <x-ui.forms.input :label="$labels['total'] . ' · ' . ($blockDraft['columns'][$j] ?? '')" name="blockDraft.total.{{ $j }}" wire:model="blockDraft.total.{{ $j }}" :maxlength="(int) config('ai_report.spec.cell')"/>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        @endif
        @error('blockDraft') <p wire:key="ai-report-block-error" class="text-xs text-[var(--md-sys-color-error)] animate-pulse">{{ $message }}</p> @enderror
    </div>
@endif
