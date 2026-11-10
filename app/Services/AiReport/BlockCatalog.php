<?php

namespace App\Services\AiReport;

final class BlockCatalog
{
    public const TYPES = ['title', 'kpi_row', 'facts', 'summary', 'table', 'action_items', 'progress', 'chart', 'callout', 'section'];

    private const HEADERS = [
        'action_items' => ['fa' => ['مسئول', 'اقدام', 'مهلت', 'وضعیت'], 'en' => ['Owner', 'Action', 'Due', 'Status']],
        'progress' => ['fa' => ['شاخص', 'هدف', 'تحقق', 'پیشرفت'], 'en' => ['Item', 'Target', 'Actual', 'Progress']],
    ];

    private const OTHER = ['fa' => 'سایر', 'en' => 'Other'];

    public static function schema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['lang', 'title', 'pages'],
            'properties' => [
                'lang' => ['type' => 'string', 'enum' => ['fa', 'en']],
                'title' => ['type' => 'string'],
                'pages' => [
                    'type' => 'array',
                    'minItems' => 1,
                    'maxItems' => (int) config('ai.report.spec.pages'),
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['blocks'],
                        'properties' => [
                            'blocks' => [
                                'type' => 'array',
                                'minItems' => 1,
                                'maxItems' => (int) config('ai.report.spec.blocks'),
                                'items' => ['$ref' => '#/$defs/block'],
                            ],
                        ],
                    ],
                ],
            ],
            '$defs' => [
                'block' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['type', 'heading', 'subtitle', 'items', 'bullets', 'highlight', 'columns', 'rows', 'total', 'percent_column', 'status_column', 'footnote', 'variant', 'paragraphs', 'options', 'risk'],
                    'properties' => [
                        'type' => ['type' => 'string', 'enum' => self::TYPES],
                        'heading' => ['type' => ['string', 'null']],
                        'subtitle' => ['type' => ['string', 'null']],
                        'items' => [
                            'type' => ['array', 'null'],
                            'maxItems' => max((int) config('ai.report.spec.kpi_items'), (int) config('ai.report.spec.facts_items')),
                            'items' => [
                                'type' => 'object',
                                'additionalProperties' => false,
                                'required' => ['label', 'value', 'hint', 'tone'],
                                'properties' => [
                                    'label' => ['type' => 'string'],
                                    'value' => ['type' => 'string'],
                                    'hint' => ['type' => ['string', 'null']],
                                    'tone' => ['type' => ['string', 'null'], 'enum' => ['neutral', 'success', 'warning', 'danger', 'info', null]],
                                ],
                            ],
                        ],
                        'bullets' => ['type' => ['array', 'null'], 'maxItems' => (int) config('ai.report.spec.items'), 'items' => ['type' => 'string']],
                        'highlight' => ['type' => ['string', 'null']],
                        'columns' => [
                            'type' => ['array', 'null'],
                            'maxItems' => max((int) config('ai.report.spec.columns'), (int) config('ai.report.spec.chart_labels')),
                            'description' => 'Tables: at most ' . (int) config('ai.report.spec.columns') . ' columns, split wider data into two tables. Charts: category labels, at most ' . (int) config('ai.report.spec.chart_labels') . '.',
                            'items' => ['type' => 'string'],
                        ],
                        'rows' => ['type' => ['array', 'null'], 'maxItems' => (int) config('ai.report.spec.rows'), 'items' => ['type' => 'array', 'items' => ['type' => 'string']]],
                        'total' => ['type' => ['array', 'null'], 'items' => ['type' => 'string']],
                        'percent_column' => ['type' => ['integer', 'null']],
                        'status_column' => ['type' => ['integer', 'null']],
                        'footnote' => ['type' => ['string', 'null']],
                        'variant' => ['type' => ['string', 'null'], 'enum' => ['info', 'warning', 'decision', 'bar', 'pie', null]],
                        'paragraphs' => ['type' => ['array', 'null'], 'maxItems' => (int) config('ai.report.spec.items'), 'items' => ['type' => 'string']],
                        'options' => [
                            'type' => ['array', 'null'],
                            'maxItems' => (int) config('ai.report.spec.options'),
                            'items' => [
                                'type' => 'object',
                                'additionalProperties' => false,
                                'required' => ['label', 'text'],
                                'properties' => [
                                    'label' => ['type' => 'string'],
                                    'text' => ['type' => 'string'],
                                ],
                            ],
                        ],
                        'risk' => ['type' => ['string', 'null']],
                    ],
                ],
            ],
        ];
    }

    public static function validate(mixed $json): array
    {
        if (!is_array($json)) {
            throw new \InvalidArgumentException('spec must be an object');
        }

        $errors = [];
        $lang = $json['lang'] ?? null;
        if (!in_array($lang, ['fa', 'en'], true)) {
            $errors[] = 'lang must be fa or en';
        }

        $title = self::text($json['title'] ?? null, (int) config('ai.report.spec.short'));
        if ($title === null) {
            $errors[] = 'title is required';
        }

        $pages = $json['pages'] ?? null;
        if (!is_array($pages) || $pages === []) {
            $errors[] = 'pages are required';
            $pages = [];
        } elseif (count($pages) > (int) config('ai.report.spec.pages')) {
            $errors[] = 'pages exceed ' . (int) config('ai.report.spec.pages');
            $pages = [];
        }

        $outPages = [];
        foreach ($pages as $page) {
            if (!is_array($page) || !is_array($page['blocks'] ?? null)) {
                $errors[] = 'page blocks are required';
                continue;
            }
            if (count($page['blocks']) > (int) config('ai.report.spec.blocks')) {
                $errors[] = 'page blocks exceed ' . (int) config('ai.report.spec.blocks');
                continue;
            }
            $blocks = [];
            foreach ($page['blocks'] as $block) {
                $normalized = self::block(is_array($block) ? $block : [], $errors, $lang === 'en' ? 'en' : 'fa');
                if ($normalized !== null) {
                    $blocks[] = $normalized;
                }
            }
            if ($blocks !== []) {
                $outPages[] = ['blocks' => $blocks];
            }
        }

        if ($errors !== []) {
            throw new \InvalidArgumentException(implode('; ', $errors));
        }
        if ($outPages === []) {
            throw new \InvalidArgumentException('pages are required');
        }

        $spec = ['lang' => $lang, 'title' => $title, 'pages' => self::withAssumptions($outPages, $lang)];
        $encoded = json_encode($spec, JSON_UNESCAPED_UNICODE);
        if ($encoded === false || strlen($encoded) > (int) config('ai.report.spec.bytes')) {
            throw new \InvalidArgumentException('spec exceeds ' . (int) config('ai.report.spec.bytes') . ' bytes');
        }

        return $spec;
    }

    public static function clean(string $s, int $max): string
    {
        $s = trim($s);
        $s = str_replace("\n", "\u{E000}", $s);
        $s = preg_replace('/\p{Cc}/u', '', $s) ?? $s;
        $s = str_replace("\u{E000}", "\n", $s);
        $s = preg_replace('/[\x{202A}-\x{202E}\x{2066}-\x{2069}\x{200B}\x{2060}\x{061C}]/u', '', $s) ?? $s;
        if (class_exists(\Normalizer::class)) {
            $normalized = \Normalizer::normalize($s, \Normalizer::FORM_C);
            if (is_string($normalized)) {
                $s = $normalized;
            }
        }

        return mb_substr($s, 0, $max);
    }

    public static function number(string $s): ?float
    {
        $s = strtr($s, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '٫' => '.',
        ]);
        $s = str_replace(['٬', '،', ',', ' ', "\u{00A0}", "\u{202F}"], '', $s);
        $s = preg_replace('/[٪%]+$/u', '', trim($s)) ?? '';
        if (!preg_match('/^-?\d+(\.\d+)?(\s*\p{L}+)?$/u', $s)) {
            return null;
        }

        return (float) $s;
    }

    public static function normalizeBlock(array $block, string $lang): ?array
    {
        $errors = [];

        return self::block($block, $errors, $lang);
    }

    public static function retotal(array $block, ?array $original = null): array
    {
        if (($block['type'] ?? null) !== 'table' || !is_array($block['rows'] ?? null) || !is_array($block['total'] ?? null)) {
            return $block;
        }
        for ($j = 0, $width = count($block['columns'] ?? []); $j < $width; $j++) {
            if ((string) ($block['total'][$j] ?? '') !== (string) ($original['total'][$j] ?? '') || preg_match('/\p{L}/u', (string) ($original['total'][$j] ?? '')) === 1) {
                continue;
            }
            $old = self::columnNumbers($original ?? [], $j);
            $declared = self::number((string) ($original['total'][$j] ?? ''));
            if ($old === [] || $declared === null || abs(array_sum($old) - $declared) > max(1, abs($declared) * (float) config('ai.report.check.total_tolerance'))) {
                continue;
            }
            $nums = self::columnNumbers($block, $j);
            if ($nums === []) {
                continue;
            }
            $sum = (string) round(array_sum($nums), 2);
            $block['total'][$j] = self::hasPersianDigits($block['rows'], $j) ? (string) convertToPersian($sum) : $sum;
        }

        return $block;
    }

    private static function columnNumbers(array $block, int $j): array
    {
        $nums = [];
        foreach ($block['rows'] ?? [] as $row) {
            $cell = (string) ($row[$j] ?? '');
            if (preg_match('/\p{L}/u', $cell) === 1 || ($num = self::number($cell)) === null) {
                return [];
            }
            $nums[] = $num;
        }

        return $nums;
    }

    private static function hasPersianDigits(array $rows, int $j): bool
    {
        foreach ($rows as $row) {
            if (preg_match('/[۰-۹٠-٩]/u', (string) ($row[$j] ?? '')) === 1) {
                return true;
            }
        }

        return false;
    }

    public static function resolveBlock(array $spec, string $ref): ?array
    {
        if (preg_match('/^(\d{1,2}):(\d{1,2})$/', $ref, $m) !== 1) {
            return null;
        }
        $block = $spec['pages'][(int) $m[1]]['blocks'][(int) $m[2]] ?? null;

        return is_array($block) ? ['p' => (int) $m[1], 'b' => (int) $m[2], 'block' => $block] : null;
    }

    private static function block(array $block, array &$errors, string $lang): ?array
    {
        $type = $block['type'] ?? null;
        if (!in_array($type, self::TYPES, true)) {
            $errors[] = 'block ' . (is_string($type) ? $type : 'unknown') . ': unknown type';

            return null;
        }

        $out = match ($type) {
            'title' => self::titleBlock($block, $errors),
            'kpi_row', 'facts' => self::kpiBlock($block, $errors, $type),
            'summary' => self::summaryBlock($block, $errors),
            'table' => self::tableBlock($block, $errors),
            'action_items' => self::actionItemsBlock($block, $errors, $lang),
            'progress' => self::progressBlock($block, $errors, $lang),
            'chart' => self::chartBlock($block, $errors, $lang),
            'callout' => self::calloutBlock($block, $errors),
            'section' => self::sectionBlock($block, $errors),
        };

        return $out !== null && $lang !== 'en' ? self::persianDigits($out) : $out;
    }

    private static function withAssumptions(array $pages, string $lang): array
    {
        foreach ($pages as $page) {
            foreach ($page['blocks'] as $block) {
                if ($block['type'] === 'section' && self::isAssumptionsHeading($block['heading'], $lang)) {
                    return $pages;
                }
            }
        }

        $last = array_key_last($pages);
        $heading = (string) config("ai.report.assumptions.heading.{$lang}");
        $blocks = $pages[$last]['blocks'];
        $ignored = [];
        $section = self::sectionBlock(['heading' => $heading, 'paragraphs' => [config("ai.report.assumptions.fallback.{$lang}")]], $ignored);
        if ($section === null) {
            return $pages;
        }
        if (count($blocks) >= (int) config('ai.report.spec.blocks')) {
            if (count($pages) < (int) config('ai.report.spec.pages')) {
                $pages[] = ['blocks' => [$section]];
            }

            return $pages;
        }

        $tail = array_key_last($blocks);
        array_splice($blocks, $blocks[$tail]['type'] === 'callout' ? $tail : count($blocks), 0, [$section]);
        $pages[$last]['blocks'] = $blocks;

        return $pages;
    }

    private static function persianDigits(array $block): array
    {
        $cell = fn (string $s): string => preg_match('/[A-Za-z]/', $s) === 1 ? $s : (string) convertToPersian($s);
        if (isset($block['rows'])) {
            $block['rows'] = array_map(fn (array $row): array => array_map($cell, $row), $block['rows']);
        }
        if (isset($block['total'])) {
            $block['total'] = array_map($cell, $block['total']);
        }
        if (isset($block['items'])) {
            $block['items'] = array_map(fn (array $item): array => array_merge($item, ['value' => $cell($item['value'])]), $block['items']);
        }

        return $block;
    }

    private static function titleBlock(array $block, array &$errors): ?array
    {
        $heading = self::text($block['heading'] ?? null, (int) config('ai.report.spec.short'));
        if ($heading === null) {
            $errors[] = 'block title: heading is required';

            return null;
        }

        $out = ['type' => 'title', 'heading' => $heading];
        $subtitle = self::text($block['subtitle'] ?? null, (int) config('ai.report.spec.short'));
        if ($subtitle !== null) {
            $out['subtitle'] = $subtitle;
        }

        return $out;
    }

    private static function kpiBlock(array $block, array &$errors, string $type = 'kpi_row'): ?array
    {
        $facts = $type === 'facts';
        $cap = (int) config($facts ? 'ai.report.spec.facts_items' : 'ai.report.spec.kpi_items');
        $min = $facts ? 1 : (int) config('ai.report.spec.kpi_min');
        $items = [];
        foreach (array_slice(is_array($block['items'] ?? null) ? $block['items'] : [], 0, $cap) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $label = self::text($item['label'] ?? null, (int) config('ai.report.spec.short'));
            $value = self::text($item['value'] ?? null, (int) config('ai.report.spec.short'));
            if ($label === null || $value === null) {
                continue;
            }
            $row = ['label' => $label, 'value' => $value];
            $hint = self::text($item['hint'] ?? null, (int) config('ai.report.spec.short'));
            if ($hint !== null) {
                $row['hint'] = $hint;
            }
            if (in_array($item['tone'] ?? null, ['neutral', 'success', 'warning', 'danger', 'info'], true)) {
                $row['tone'] = $item['tone'];
            }
            $items[] = $row;
        }
        if (count($items) < $min) {
            $errors[] = "block {$type}: items are required";

            return null;
        }

        $out = ['type' => $type, 'items' => $items];
        $heading = $facts ? self::text($block['heading'] ?? null, (int) config('ai.report.spec.short')) : null;
        if ($heading !== null) {
            $out['heading'] = $heading;
        }

        return $out;
    }

    private static function summaryBlock(array $block, array &$errors): ?array
    {
        $bullets = self::lines($block['bullets'] ?? null, (int) config('ai.report.spec.items'), (int) config('ai.report.spec.paragraph'));
        if ($bullets === []) {
            $errors[] = 'block summary: bullets are required';

            return null;
        }

        $out = ['type' => 'summary', 'bullets' => $bullets];
        $heading = self::text($block['heading'] ?? null, (int) config('ai.report.spec.short'));
        if ($heading !== null) {
            $out['heading'] = $heading;
        }
        $highlight = self::text($block['highlight'] ?? null, (int) config('ai.report.spec.paragraph'));
        if ($highlight !== null) {
            $out['highlight'] = $highlight;
        }

        return $out;
    }

    private static function tableBlock(array $block, array &$errors): ?array
    {
        $rawColumns = is_array($block['columns'] ?? null) ? array_values($block['columns']) : [];
        if (count($rawColumns) > (int) config('ai.report.spec.columns')) {
            $errors[] = 'block table: too many columns (at most ' . (int) config('ai.report.spec.columns') . ')';

            return null;
        }

        $columns = [];
        foreach ($rawColumns as $header) {
            $columns[] = self::text($header, (int) config('ai.report.spec.short')) ?? '';
        }
        $rawRows = is_array($block['rows'] ?? null) ? array_slice($block['rows'], 0, (int) config('ai.report.spec.rows')) : [];
        if ($columns === [] || implode('', $columns) === '' || $rawRows === []) {
            $errors[] = 'block table: columns and rows are required';

            return null;
        }

        $width = count($columns);
        $rows = [];
        foreach ($rawRows as $row) {
            $cells = [];
            foreach (array_slice(is_array($row) ? $row : [], 0, $width) as $cell) {
                $cells[] = self::cell($cell);
            }
            $rows[] = array_pad($cells, $width, '');
        }

        $out = ['type' => 'table', 'columns' => $columns, 'rows' => $rows];
        $heading = self::text($block['heading'] ?? null, (int) config('ai.report.spec.short'));
        if ($heading !== null) {
            $out['heading'] = $heading;
        }
        if (is_array($block['total'] ?? null)) {
            $total = [];
            foreach (array_slice($block['total'], 0, $width) as $cell) {
                $total[] = self::cell($cell);
            }
            $total = array_pad($total, $width, '');
            if (array_diff($total, ['', '—']) !== []) {
                $out['total'] = $total;
            }
        }
        $percentColumn = $block['percent_column'] ?? null;
        if (is_numeric($percentColumn) && (int) $percentColumn == $percentColumn && (int) $percentColumn >= 0 && (int) $percentColumn < $width) {
            $out['percent_column'] = (int) $percentColumn;
        }
        $statusColumn = $block['status_column'] ?? null;
        if (is_numeric($statusColumn) && (int) $statusColumn == $statusColumn && (int) $statusColumn >= 0 && (int) $statusColumn < $width && (int) $statusColumn !== ($out['percent_column'] ?? null)) {
            $out['status_column'] = (int) $statusColumn;
        }
        $footnote = self::text($block['footnote'] ?? null, (int) config('ai.report.spec.paragraph'));
        if ($footnote !== null) {
            $out['footnote'] = $footnote;
        }

        return self::checkTable($out);
    }

    private static function calloutBlock(array $block, array &$errors): ?array
    {
        $variant = $block['variant'] ?? null;
        if (!in_array($variant, ['info', 'warning', 'decision'], true)) {
            $errors[] = 'block callout: variant is required';

            return null;
        }

        $paragraphs = self::lines($block['paragraphs'] ?? null, (int) config('ai.report.spec.items'), (int) config('ai.report.spec.paragraph'));
        $options = [];
        foreach (array_slice(is_array($block['options'] ?? null) ? $block['options'] : [], 0, (int) config('ai.report.spec.options')) as $option) {
            if (!is_array($option)) {
                continue;
            }
            $label = self::text($option['label'] ?? null, (int) config('ai.report.spec.short'));
            $text = self::text($option['text'] ?? null, (int) config('ai.report.spec.paragraph'));
            if ($label === null || $text === null) {
                continue;
            }
            $options[] = ['label' => $label, 'text' => $text];
        }
        if ($paragraphs === [] && $options === []) {
            $errors[] = 'block callout: paragraphs or options are required';

            return null;
        }

        $out = ['type' => 'callout', 'variant' => $variant, 'paragraphs' => $paragraphs];
        $heading = self::text($block['heading'] ?? null, (int) config('ai.report.spec.short'));
        if ($heading !== null) {
            $out['heading'] = $heading;
        }
        if ($options !== []) {
            $out['options'] = $options;
        }
        $risk = self::text($block['risk'] ?? null, (int) config('ai.report.spec.paragraph'));
        if ($risk !== null) {
            $out['risk'] = $risk;
        }

        return $out;
    }

    private static function sectionBlock(array $block, array &$errors): ?array
    {
        $heading = self::text($block['heading'] ?? null, (int) config('ai.report.spec.short'));
        if ($heading === null) {
            $errors[] = 'block section: heading is required';

            return null;
        }

        $out = ['type' => 'section', 'heading' => $heading];
        $paragraphs = self::lines($block['paragraphs'] ?? null, (int) config('ai.report.spec.items'), (int) config('ai.report.spec.paragraph'));
        if ($paragraphs !== []) {
            $out['paragraphs'] = $paragraphs;
        }
        $bullets = self::lines($block['bullets'] ?? null, (int) config('ai.report.spec.items'), (int) config('ai.report.spec.paragraph'));
        if ($bullets !== []) {
            $out['bullets'] = $bullets;
        }

        return $out;
    }

    private static function actionItemsBlock(array $block, array &$errors, string $lang): ?array
    {
        $rows = array_values(array_filter(self::rowsOf($block, 4), fn (array $row): bool => $row[1] !== ''));
        if ($rows === []) {
            $errors[] = 'block action_items: rows are required';

            return null;
        }

        return self::titled($block, ['type' => 'action_items', 'columns' => self::HEADERS['action_items'][$lang], 'rows' => $rows, 'status_column' => 3]);
    }

    private static function progressBlock(array $block, array &$errors, string $lang): ?array
    {
        $rows = [];
        foreach (self::rowsOf($block, 3) as [$label, $target, $actual]) {
            if ($label === '') {
                continue;
            }
            $t = self::number($target);
            $a = self::number($actual);
            $rows[] = [$label, $target, $actual, $t !== null && $t > 0 && $a !== null ? (string) (int) round($a / $t * 100) : ''];
        }
        if ($rows === []) {
            $errors[] = 'block progress: rows are required';

            return null;
        }

        return self::titled($block, ['type' => 'progress', 'columns' => self::HEADERS['progress'][$lang], 'rows' => $rows, 'percent_column' => 3]);
    }

    private static function chartBlock(array $block, array &$errors, string $lang): ?array
    {
        if (is_array($block['labels'] ?? null) && is_array($block['series'] ?? null)) {
            $block = [
                'variant' => $block['kind'] ?? null,
                'columns' => $block['labels'],
                'rows' => array_map(fn (mixed $s): array => is_array($s)
                    ? [$s['name'] ?? '', ...array_map(fn (mixed $v): string => is_numeric($v) ? number_format((float) $v, 4, '.', '') : '', array_values(is_array($s['values'] ?? null) ? $s['values'] : []))]
                    : [], array_values($block['series'])),
                'heading' => $block['heading'] ?? null,
                'footnote' => $block['unit'] ?? null,
            ];
        }
        $kind = $block['variant'] ?? null;
        if (!in_array($kind, ['bar', 'pie'], true)) {
            $errors[] = 'block chart: variant must be bar or pie';

            return null;
        }
        $labels = [];
        $named = false;
        foreach (is_array($block['columns'] ?? null) ? array_values($block['columns']) : [] as $label) {
            $label = self::text($label, (int) config('ai.report.spec.chart_label_chars'));
            $named = $named || $label !== null;
            $labels[] = $label ?? '—';
        }
        $rawRows = is_array($block['rows'] ?? null) ? array_values($block['rows']) : [];
        if (!$named || $rawRows === []) {
            $errors[] = 'block chart: columns and rows are required';

            return null;
        }
        if (count($labels) > (int) config('ai.report.spec.chart_labels') || count($rawRows) > (int) config('ai.report.spec.chart_series')) {
            $errors[] = 'block chart: too many categories or series';

            return null;
        }
        if ($kind === 'pie') {
            $rawRows = array_slice($rawRows, 0, 1);
        }
        $limit = (float) config('ai.report.spec.chart_max_abs');
        $series = [];
        foreach ($rawRows as $row) {
            $row = is_array($row) ? array_values($row) : [];
            $values = [];
            foreach (array_slice($row, 1) as $cell) {
                $n = self::number(self::cell($cell));
                if ($n === null || !is_finite($n)) {
                    $errors[] = 'block chart: values must be plain numbers';

                    return null;
                }
                if (abs($n) > $limit) {
                    $errors[] = 'block chart: values are out of range';

                    return null;
                }
                if ($kind === 'pie' && $n < 0) {
                    $errors[] = 'block chart: pie values must not be negative';

                    return null;
                }
                $values[] = round($n, 4) + 0.0;
            }
            if (count($values) !== count($labels)) {
                $errors[] = 'block chart: each series needs one value per category';

                return null;
            }
            $series[] = ['name' => self::text($row[0] ?? null, (int) config('ai.report.spec.chart_label_chars')) ?? '', 'values' => $values];
        }
        if ($kind === 'pie') {
            $max = (int) config('ai.report.spec.pie_slices');
            if (count($labels) > $max) {
                $series[0]['values'] = [...array_slice($series[0]['values'], 0, $max - 1), round(array_sum(array_slice($series[0]['values'], $max - 1)), 4)];
                $labels = [...array_slice($labels, 0, $max - 1), self::OTHER[$lang]];
            }
            if (array_sum($series[0]['values']) <= 0) {
                $errors[] = 'block chart: pie values sum to zero';

                return null;
            }
        }

        $out = ['type' => 'chart', 'kind' => $kind, 'labels' => $labels, 'series' => $series];
        $heading = self::text($block['heading'] ?? null, (int) config('ai.report.spec.short'));
        if ($heading !== null) {
            $out['heading'] = $heading;
        }
        $unit = self::text($block['footnote'] ?? null, (int) config('ai.report.spec.short'));
        if ($unit !== null) {
            $out['unit'] = $unit;
        }

        return $out;
    }

    private static function rowsOf(array $block, int $width): array
    {
        $rows = [];
        foreach (array_slice(is_array($block['rows'] ?? null) ? $block['rows'] : [], 0, (int) config('ai.report.spec.rows')) as $row) {
            $cells = [];
            foreach (array_slice(is_array($row) ? array_values($row) : [], 0, $width) as $cell) {
                $cells[] = self::cell($cell);
            }
            $rows[] = array_pad($cells, $width, '');
        }

        return $rows;
    }

    private static function titled(array $block, array $out): array
    {
        $heading = self::text($block['heading'] ?? null, (int) config('ai.report.spec.short'));
        if ($heading !== null) {
            $out['heading'] = $heading;
        }
        $footnote = self::text($block['footnote'] ?? null, (int) config('ai.report.spec.paragraph'));
        if ($footnote !== null) {
            $out['footnote'] = $footnote;
        }

        return $out;
    }

    public static function tone(string $cell): ?string
    {
        $needle = self::fold($cell);
        foreach ((array) config('ai.report.status_values') as $tone => $words) {
            foreach ((array) $words as $word) {
                if ($needle !== '' && $needle === self::fold((string) $word)) {
                    return (string) $tone;
                }
            }
        }

        return null;
    }

    public static function isAssumptionsHeading(string $heading, string $lang): bool
    {
        return self::fold($heading) === self::fold((string) config("ai.report.assumptions.heading.{$lang}"));
    }

    private static function fold(string $s): string
    {
        return trim(preg_replace('/\s+/u', ' ', str_replace(["\u{200C}", '_', '-', 'ي', 'ك'], [' ', ' ', ' ', 'ی', 'ک'], mb_strtolower($s))) ?? '');
    }

    private static function checkTable(array $block): array
    {
        if (self::mismatchColumns($block) !== []) {
            $block['check'] = 'mismatch';
        }

        return $block;
    }

    public static function mismatchColumns(array $block): array
    {
        $rows = $block['rows'] ?? [];
        $count = count($rows);
        $total = $block['total'] ?? null;
        $percent = $block['percent_column'] ?? null;
        $need = max(2, (int) ceil((float) config('ai.report.check.numeric_ratio') * $count));
        $bad = [];

        for ($j = 0, $width = count($block['columns'] ?? []); $j < $width; $j++) {
            $nums = [];
            foreach ($rows as $row) {
                $num = self::number((string) ($row[$j] ?? ''));
                if ($num !== null) {
                    $nums[] = $num;
                }
            }
            if (count($nums) < $need) {
                continue;
            }
            $sum = array_sum($nums);
            $declared = is_array($total) ? self::number((string) ($total[$j] ?? '')) : null;
            if (
                ($declared !== null && abs($sum - $declared) > max(1, abs($declared) * (float) config('ai.report.check.total_tolerance')))
                || ($percent === $j && abs($sum - 100) > (float) config('ai.report.check.percent_tolerance'))
            ) {
                $bad[] = $j;
            }
        }

        return $bad;
    }

    private static function text(mixed $value, int $max): ?string
    {
        if (is_int($value) || is_float($value)) {
            $value = (string) $value;
        }
        if (!is_string($value)) {
            return null;
        }
        $clean = self::clean($value, $max);

        return $clean === '' ? null : $clean;
    }

    private static function cell(mixed $value): string
    {
        return self::text($value, (int) config('ai.report.spec.cell')) ?? '';
    }

    private static function lines(mixed $value, int $max, int $length): array
    {
        if (!is_array($value)) {
            return [];
        }
        $out = [];
        foreach ($value as $item) {
            $line = self::text($item, $length);
            if ($line === null) {
                continue;
            }
            $out[] = $line;
            if (count($out) >= $max) {
                break;
            }
        }

        return $out;
    }
}
