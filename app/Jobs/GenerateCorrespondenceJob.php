<?php

namespace App\Jobs;

use App\Livewire\Dashboard\Correspondence\Actions\LetterRules;
use App\Livewire\Dashboard\Correspondence\Forms\LetterForm;
use App\Models\Correspondence;
use App\Models\Letterhead;
use App\Models\User;
use App\Services\Ai\AiClient;
use App\Services\ContentSanitizerService;
use App\Support\CorrespondenceAccessPolicy;
use DomainException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\TimeoutExceededException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use JsonException;
use Morilog\Jalali\CalendarUtils;
use Throwable;

class GenerateCorrespondenceJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public int $timeout;

    public bool $failOnTimeout = true;

    public int $uniqueFor;

    private const BLOCK_TYPES = ['text', 'table'];

    private const HEADED_TYPES = ['contract', 'commitment'];

    private const NUMBER_REPEATS = 2;

    private const SCHEMA = [
        'type' => 'object',
        'properties' => [
            'subject' => ['type' => 'string'],
            'blocks' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => ['type' => 'string', 'enum' => self::BLOCK_TYPES],
                        'heading' => ['type' => ['string', 'null']],
                        'paragraphs' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'header' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'rows' => ['type' => 'array', 'items' => ['type' => 'array', 'items' => ['type' => 'string']]],
                    ],
                    'required' => ['type', 'heading', 'paragraphs', 'header', 'rows'],
                    'additionalProperties' => false,
                ],
            ],
        ],
        'required' => ['subject', 'blocks'],
        'additionalProperties' => false,
    ];

    public function __construct(public int $letterId, public string $run)
    {
        $padding = (int) config('ai.correspondence.job.padding');
        $this->timeout = 2 * ((int) AiClient::setting('correspondence', 'timeout') + (int) AiClient::setting('correspondence', 'connect_timeout')) + $padding;
        $ceiling = (int) config('queue.connections.' . config('queue.default') . '.retry_after') - (int) config('ai.correspondence.job.retry_margin');
        if ($ceiling >= 1 && $this->timeout > $ceiling) {
            $this->timeout = $ceiling;
        }
        $this->uniqueFor = $this->timeout + $padding;
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return $this->letterId . ':' . $this->run;
    }

    public function handle(AiClient $client): void
    {
        $letter = Correspondence::query()->find($this->letterId);
        if (!$letter || !$this->current($letter)) {
            return;
        }

        if (!$this->allowed($letter)) {
            $letter->markFailed('disabled');

            return;
        }

        try {
            $alive = Correspondence::query()->whereKey($this->letterId)->where('status', Correspondence::STATUS_GENERATING)->update(['updated_at' => now()]);
            if ($alive === 0) {
                return;
            }
            $this->store($this->compose($letter, $client));
        } catch (Throwable $e) {
            $fresh = Correspondence::query()->find($this->letterId);
            if ($fresh && $this->current($fresh)) {
                $this->failWith($fresh, $e);
            }
        }
    }

    public function failed(?Throwable $e): void
    {
        $letter = Correspondence::query()->find($this->letterId);
        if (!$letter || !$this->current($letter)) {
            return;
        }
        $letter->markFailed($e instanceof TimeoutExceededException ? 'timeout' : 'exception');
    }

    private function current(Correspondence $letter): bool
    {
        return $letter->status === Correspondence::STATUS_GENERATING && ($letter->lastVersion()['run'] ?? null) === $this->run;
    }

    private function allowed(Correspondence $letter): bool
    {
        $type = config("ai.correspondence.types.{$letter->type}");

        return correspondenceAiOn()
            && $letter->classification === Correspondence::CLASSIFICATION_NORMAL
            && is_array($type)
            && ($type['enabled'] ?? false) === true
            && ($type['direction'] ?? null) === Correspondence::DIRECTION_OUT;
    }

    private function compose(Correspondence $letter, AiClient $client): array
    {
        $ctx = $this->context($letter);

        return $this->ask($client, $this->messages($ctx), $ctx) + ['model' => $ctx['model']];
    }

    private function context(Correspondence $letter): array
    {
        $p = (array) config('ai.correspondence.prompt');
        $run = $letter->lastVersion();
        $spec = is_array($letter->spec) ? $letter->spec : [];
        $blocks = array_values(is_array($spec['blocks'] ?? null) ? $spec['blocks'] : []);
        $kind = ($run['kind'] ?? null) === 'refine' ? 'refine' : 'generate';
        $index = $kind === 'refine' ? (int) ($run['block'] ?? -1) : null;
        $type = (string) $letter->type;
        if (!isset($p['type_frames'][$type]) || ($kind === 'refine' && !is_array($blocks[$index] ?? null))) {
            throw new InvalidArgumentException('smart context');
        }

        $head = $letter->letterhead_id ? Letterhead::query()->find($letter->letterhead_id, ['id', 'size', 'lang']) : null;
        $lang = $head?->lang === 'en' ? 'en' : 'fa';
        $pageChars = (array) config('ai.correspondence.page_chars');
        $pageSize = (int) ($pageChars[(string) $head?->size] ?? $pageChars['a4'] ?? 2400);
        $pages = min((float) ($p['lengths'][$run['length'] ?? ''] ?? 1), (float) config('ai.correspondence.warn_pages'));
        $budget = (int) floor($pageSize * $pages);

        $recipient = is_array($spec['recipient'] ?? null) ? $spec['recipient'] : [];
        $sections = [
            'KEY_POINTS' => $this->datum($run['prompt'] ?? '', (int) $p['max']),
            'RECIPIENT' => implode("\n", array_filter(array_map(fn (string $key) => $this->datum($recipient[$key] ?? '', 200), ['organization', 'person', 'title']))),
            'CURRENT_SUBJECT' => $this->datum($letter->subject, 200),
        ];

        if ($kind === 'refine') {
            $rest = 0;
            $lines = [];
            foreach ($blocks as $i => $block) {
                $text = is_array($block) ? $this->blockText($block) : '';
                if ($i !== $index) {
                    $rest += mb_strlen($text);
                }
                if ($text !== '') {
                    $lines[] = "[{$i}] {$text}";
                }
            }
            $budget = max(300, $budget - $rest);
            $sections['INSTRUCTION'] = $this->datum($run['instruction'] ?? '', (int) $p['instruction_max']);
            $sections['LETTER'] = $this->datum(implode("\n", $lines), 8000);
        }

        $reply = $this->reply($letter);
        if ($reply !== null) {
            $sections['REPLY_TO'] = $reply;
        }

        $sections = array_filter($sections, fn (string $text) => $text !== '');
        $inputs = implode("\n", $sections);
        preg_match_all('/[A-Za-z][A-Za-z0-9]*/', $inputs, $words);

        return [
            'kind' => $kind,
            'block' => $index,
            'type' => $type,
            'lang' => $lang,
            'budget' => $budget,
            'sections' => $sections,
            'inputs' => $inputs,
            'fold' => $this->fold($inputs),
            'numbers' => array_count_values($this->numbers($inputs)),
            'words' => array_flip(array_map('strtolower', $words[0])),
            'tone' => $p['tones'][$run['tone'] ?? ''] ?? $p['tones']['formal'],
            'audience' => $p['audiences'][$run['audience'] ?? ''] ?? $p['audiences']['management'],
            'needs_subject' => $kind === 'generate' && trim((string) $letter->subject) === '',
            'model' => (string) AiClient::setting('correspondence', 'model'),
        ];
    }

    private function reply(Correspondence $letter): ?string
    {
        if (!$letter->in_reply_to_id) {
            return null;
        }

        $owner = User::query()->find($letter->user_id);
        $parent = Correspondence::query()->find($letter->in_reply_to_id);
        if (!$owner || !$parent || $parent->classification !== Correspondence::CLASSIFICATION_NORMAL || !CorrespondenceAccessPolicy::canView($owner, $parent)) {
            return null;
        }

        $body = implode("\n", array_map(
            fn ($block) => is_array($block) ? $this->blockText($block) : '',
            is_array($parent->spec['blocks'] ?? null) ? $parent->spec['blocks'] : []
        ));

        return $this->datum($parent->subject . "\n" . $body, (int) config('ai.correspondence.prompt.context_max'));
    }

    private function blockText(array $block): string
    {
        $html = is_string($block['html'] ?? null) ? preg_replace('/<\/(?:td|th|tr|p|li|h[2-4])>|<br\s*\/?>/i', ' ', $block['html']) : '';

        return trim(implode("\n", array_filter([
            is_string($block['heading'] ?? null) ? $block['heading'] : '',
            is_string($block['text'] ?? null) ? $block['text'] : '',
            stripHtml($html),
        ], fn (string $part) => $part !== '')));
    }

    private function datum(mixed $value, int $cap): string
    {
        if (!is_scalar($value)) {
            return '';
        }

        $text = (string) ContentSanitizerService::clean((string) $value);
        $text = preg_replace(['/[\x{2066}-\x{2069}\x{061C}]/u', '/[<>]{3,}/u', '/[ \t]+/u', '/\R{3,}/u'], ['', '', ' ', "\n\n"], $text) ?? '';

        return mb_substr(trim($text), 0, $cap);
    }

    private function messages(array $ctx): array
    {
        $p = (array) config('ai.correspondence.prompt');
        $lang = $ctx['lang'];
        $limits = (array) $p['output'];
        $refine = $ctx['kind'] === 'refine';

        $system = strtr($p['system'][$lang], [
            '{budget}' => (string) $ctx['budget'],
            '{blocks}' => (string) ($refine ? 1 : $limits['blocks']),
            '{subject}' => (string) $limits['subject'],
            '{paragraph}' => (string) $limits['paragraph'],
            '{heading}' => (string) $limits['heading'],
            '{rows}' => (string) $limits['rows'],
            '{cols}' => (string) $limits['cols'],
        ]);
        $examples = [$p['examples'][$lang]['title']];
        foreach ($p['examples'][$lang]['items'] as $item) {
            $examples[] = "[{$item['type']}] {$item['text']}";
        }
        $system .= "\n\n" . $p['type_frames'][$ctx['type']][$lang] . "\n" . $ctx['tone'][$lang] . "\n" . $ctx['audience'][$lang] . "\n\n" . implode("\n", $examples);
        if ($refine) {
            $system .= "\n\n" . $p['refine'][$lang];
        }

        $fence = Str::random(12);
        $lines = [
            "FENCE: {$fence}",
            "LANGUAGE: {$lang}",
            "LETTER_TYPE: {$ctx['type']}",
            'TASK: ' . ($refine ? 'REPLACE_BLOCK' : 'WRITE_LETTER'),
            "BUDGET_CHARS: {$ctx['budget']}",
            'MERGE_TOKENS: ' . implode(' ', LetterForm::MERGE_TOKENS),
        ];
        if ($refine) {
            $lines[] = "TARGET_BLOCK: {$ctx['block']}";
        }
        foreach ($ctx['sections'] as $label => $text) {
            $lines[] = "<<<{$label}:{$fence}>>>\n{$text}\n<<<END:{$fence}>>>";
        }

        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => implode("\n", $lines)],
        ];
    }

    private function ask(AiClient $client, array $messages, array $ctx): array
    {
        $p = (array) config('ai.correspondence.prompt');
        $calls = max(1, (int) config('ai.correspondence.provider.max_calls'));
        $needed = (int) AiClient::setting('correspondence', 'timeout') + (int) AiClient::setting('correspondence', 'connect_timeout') + 5;
        $started = microtime(true);
        $spent = 0;
        $kind = 'empty';

        for ($attempt = 0; $attempt < $calls; $attempt++) {
            try {
                $reply = $client->json($messages, self::SCHEMA, 'correspondence');
            } finally {
                $spent += $client->lastTokens();
            }

            $checked = null;
            if ($reply['content'] === null) {
                $kind = 'empty';
            } else {
                try {
                    $checked = $this->check(AiClient::parseJson($reply['content']), $ctx);
                    $kind = 'invalid_output';
                } catch (JsonException) {
                    $checked = ['found' => ['schema' => '']];
                    $kind = 'invalid_json';
                }
                if ($checked['found'] === []) {
                    return $checked + ['tokens' => $spent];
                }
            }

            if ($attempt + 1 >= $calls || $this->timeout - (microtime(true) - $started) < $needed) {
                break;
            }

            if ($checked !== null) {
                $reasons = [];
                foreach ($checked['found'] as $code => $note) {
                    $reasons[] = ($p['reasons'][$code] ?? $code) . ($note !== '' ? " ({$note})" : '');
                }
                $messages[] = ['role' => 'assistant', 'content' => $reply['content']];
                $messages[] = ['role' => 'user', 'content' => strtr($p['corrective'][$ctx['lang']], ['{reasons}' => implode('; ', $reasons)])];
            }
        }

        throw new DomainException($kind);
    }

    private function check(mixed $data, array $ctx): array
    {
        $out = (array) config('ai.correspondence.prompt.output');
        $blocks = is_array($data) ? ($data['blocks'] ?? null) : null;
        if (!is_array($data) || !is_string($data['subject'] ?? null) || !is_array($blocks) || !array_is_list($blocks)) {
            return ['found' => ['schema' => '']];
        }
        if ($blocks === [] || count($blocks) > ($ctx['kind'] === 'refine' ? 1 : (int) $out['blocks'])) {
            return ['found' => ['blocks' => '']];
        }

        $found = [];
        $hints = [];
        $all = [];
        $numbered = [];
        $clean = [];
        $total = 0;
        $subject = '';

        if ($ctx['needs_subject']) {
            $subject = preg_replace('/[\s.\x{3002}]+$/u', '', $this->line($data['subject'], $ctx, $hints)) ?? '';
            if ($subject === '' || mb_strlen($subject) > (int) $out['subject']) {
                $found['subject'] = '';
            }
            $all[] = $numbered[] = $subject;
        }

        $headed = in_array($ctx['type'], self::HEADED_TYPES, true);
        foreach ($blocks as $block) {
            if (!is_array($block) || !in_array($block['type'] ?? null, self::BLOCK_TYPES, true) || !(is_string($block['heading'] ?? null) || ($block['heading'] ?? null) === null)) {
                return ['found' => ['schema' => '']];
            }

            $heading = $headed && is_string($block['heading'] ?? null) ? $this->line($block['heading'], $ctx, $hints) : '';
            if (mb_strlen($heading) > (int) $out['heading']) {
                $found['heading'] = '';
            }
            if ($heading !== '') {
                $all[] = $numbered[] = $heading;
                $total += mb_strlen($heading);
            }

            if ($block['type'] === 'text') {
                $paragraphs = $block['paragraphs'] ?? null;
                if (!is_array($paragraphs) || !array_is_list($paragraphs) || count($paragraphs) > 40) {
                    return ['found' => ['schema' => '']];
                }
                $items = [];
                foreach ($paragraphs as $paragraph) {
                    if (!is_string($paragraph)) {
                        return ['found' => ['schema' => '']];
                    }
                    $text = $this->line($paragraph, $ctx, $hints);
                    if ($text === '') {
                        continue;
                    }
                    if (mb_strlen($text) > (int) $out['paragraph']) {
                        $found['paragraph'] = '';
                    }
                    $items[] = $text;
                    $total += mb_strlen($text);
                }
                if ($items === []) {
                    $found['empty'] = '';

                    continue;
                }
                $clean[] = ['type' => 'text', 'heading' => $heading, 'paragraphs' => $items];
                array_push($all, ...$items);
                array_push($numbered, ...$items);

                continue;
            }

            $header = $block['header'] ?? null;
            $rows = $block['rows'] ?? null;
            if (!is_array($header) || !array_is_list($header) || !is_array($rows) || !array_is_list($rows)) {
                return ['found' => ['schema' => '']];
            }
            if ($header === [] || $rows === [] || count($header) > (int) $out['cols'] || count($rows) > (int) $out['rows']) {
                $found['table'] = '';

                continue;
            }

            $head = [];
            foreach ($header as $cell) {
                if (!is_string($cell)) {
                    return ['found' => ['schema' => '']];
                }
                $head[] = $this->line($cell, $ctx, $hints);
            }
            $body = [];
            foreach ($rows as $n => $row) {
                if (!is_array($row) || !array_is_list($row)) {
                    return ['found' => ['schema' => '']];
                }
                if (count($row) !== count($head)) {
                    $found['table'] = '';

                    continue 2;
                }
                $cells = [];
                foreach ($row as $cell) {
                    if (!is_string($cell)) {
                        return ['found' => ['schema' => '']];
                    }
                    $cells[] = $this->line($cell, $ctx, $hints);
                }
                $body[] = $cells;
                $first = $this->digits($cells[0]);
                $numbered = [...$numbered, ...($first === (string) ($n + 1) ? array_slice($cells, 1) : $cells)];
            }

            $flat = [...$head, ...array_merge(...$body)];
            if (in_array('', $head, true) || array_filter($flat, fn (string $cell) => mb_strlen($cell) > (int) $out['cell']) !== []) {
                $found[in_array('', $head, true) ? 'empty' : 'table'] = '';
            }
            $total += array_sum(array_map('mb_strlen', $flat));
            array_push($all, ...$flat);
            array_push($numbered, ...$head);
            $clean[] = ['type' => 'table', 'heading' => $heading, 'header' => $head, 'rows' => $body];
        }

        $joined = implode("\n", $all);
        $folded = $this->fold($joined);

        if ($total > $ctx['budget'] * 1.1) {
            $found['budget'] = '';
        }

        foreach ((array) config("ai.correspondence.prompt.forbidden.{$ctx['lang']}") as $fragment) {
            $pattern = '~(?<![\p{L}\p{N}])(?:' . $fragment . ')~mu';
            if (preg_match($pattern, $folded, $hit) === 1 && preg_match($pattern, $ctx['fold']) !== 1) {
                $found['forbidden'] = trim($hit[0]);

                break;
            }
        }

        foreach ($all as $text) {
            if (preg_match('/<\/?[a-z!][^>]*>|^\s*(?:#{1,6}\s|[*\x{2022}\x{25AA}\x{25E6}\x{25CF}\x{25A0}>]\s|[-\x{2013}\x{2014}]\s)|\*\*|__|`|~~|\]\(/iu', $text) === 1) {
                $found['markup'] = '';

                break;
            }
        }

        if (preg_match('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{FE0F}]/u', $joined) === 1) {
            $found['emoji'] = '';
        }

        preg_match_all('~(?:https?://|www\.|mailto:)\S+~iu', $joined, $links);
        foreach ($links[0] as $link) {
            if (!str_contains($ctx['fold'], $this->fold(preg_replace('/[.,;)\x{BB}\x{61B}\x{60C}]+$/u', '', $link) ?? ''))) {
                $found['link'] = '';

                break;
            }
        }
        if ($ctx['lang'] === 'en') {
            preg_match_all('/[\w.+-]+@[\w-]+(?:\.[\w-]+)+|\b[a-z0-9-]+(?:\.[a-z0-9-]+)*\.(?:com|org|net|ir|info|biz|edu|gov|io|co)\b/iu', $joined, $domains);
            foreach ($domains[0] as $domain) {
                if (!str_contains($ctx['fold'], $this->fold($domain))) {
                    $found['link'] = '';

                    break;
                }
            }
        }

        $invented = [];
        $seen = [];
        foreach ($numbered as $text) {
            foreach ($this->outputNumbers($text) as $number) {
                $seen[$number] = ($seen[$number] ?? 0) + 1;
                $given = $ctx['numbers'][$number] ?? 0;
                if ($given === 0 || $seen[$number] > $given + self::NUMBER_REPEATS) {
                    $invented[$number] = true;
                }
            }
        }
        if ($ctx['lang'] === 'fa') {
            preg_match_all('/[A-Za-z][A-Za-z0-9]*/', $joined, $words);
            foreach ($words[0] as $word) {
                if (!isset($ctx['words'][strtolower($word)])) {
                    $invented[$word] = true;
                }
            }
        }
        if ($invented !== []) {
            $found['invented'] = implode(', ', array_slice(array_map('strval', array_keys($invented)), 0, 5));
        }

        $placeholders = substr_count($joined, (string) config('ai.correspondence.placeholder'));
        if ($placeholders > (int) $out['placeholders']) {
            $found['placeholders'] = '';
        }

        return [
            'found' => $found,
            'subject' => $subject,
            'blocks' => $clean,
            'hints' => array_slice(array_values(array_unique($hints)), 0, 40),
            'placeholders' => $placeholders,
        ];
    }

    private function line(string $value, array $ctx, array &$hints): string
    {
        $value = (string) ContentSanitizerService::clean($value);
        $value = preg_replace(['/[\x{2066}-\x{2069}\x{061C}]/u', '/\s+/u'], ['', ' '], $value) ?? '';
        if ($ctx['lang'] === 'fa') {
            $value = strtr($value, ["\u{064A}" => "\u{06CC}", "\u{0643}" => "\u{06A9}"]);
        }
        $placeholder = (string) config('ai.correspondence.placeholder');

        return preg_replace_callback('/\[([^\[\]]{0,80})\]/u', function (array $match) use ($placeholder, $ctx, &$hints) {
            $hint = preg_replace('/^[\s.\x{2026}]+|[\s.\x{2026}]+$/u', '', $match[1]) ?? '';
            if ($hint !== '' && str_contains($ctx['inputs'], $match[0])) {
                return $match[0];
            }
            if ($hint !== '') {
                $hints[] = $hint;
            }

            return $placeholder;
        }, trim($value)) ?? '';
    }

    private function fold(string $value): string
    {
        $value = strtr($value, ["\u{064A}" => "\u{06CC}", "\u{0643}" => "\u{06A9}"]);

        return mb_strtolower(preg_replace('/[\x{200B}-\x{200D}\h]+/u', ' ', $value) ?? '');
    }

    private function digits(string $value): string
    {
        return trim(str_replace(mb_str_split('٠١٢٣٤٥٦٧٨٩'), range(0, 9), CalendarUtils::convertNumbers($value, true)));
    }

    private function numbers(string $text): array
    {
        $text = preg_replace('/(?<=\d)[,\x{066C}\x{060C}](?=\d{3}(?!\d))/u', '', $this->digits($text)) ?? '';
        preg_match_all('/\d+/', $text, $match);

        return array_map(fn (string $number) => ltrim($number, '0') ?: '0', $match[0]);
    }

    private function outputNumbers(string $text): array
    {
        $text = preg_replace(
            [
                '/^\s*\(?\d{1,2}(?:[.\-]\d{1,2})*[.)\-\x{2013}:]\s*/u',
                '/(?<![\d\p{L}])\(?[1-9][.)](?=\s)/u',
                '/(?:ماده|بند|تبصره|صفحه|مورد|clause|article|section|page|item)\s*\d{1,2}(?:\s*[-\x{2013}\x{2014}.\/]\s*\d{1,2})*/iu',
            ],
            '',
            $this->digits($text)
        ) ?? '';

        return $this->numbers($text);
    }

    private function convert(array $blocks): array
    {
        $out = [];
        foreach ($blocks as $block) {
            if ($block['type'] === 'text') {
                $out[] = ['type' => 'text', 'heading' => $block['heading'], 'text' => implode("\n\n", $block['paragraphs'])];

                continue;
            }
            $row = fn (array $cells, string $tag) => '<tr>' . implode('', array_map(fn (string $cell) => "<{$tag}>" . e($cell) . "</{$tag}>", $cells)) . '</tr>';
            $table = '<table><thead>' . $row($block['header'], 'th') . '</thead><tbody>' . implode('', array_map(fn (array $cells) => $row($cells, 'td'), $block['rows'])) . '</tbody></table>';
            $out[] = ['type' => 'html', 'heading' => $block['heading'], 'html' => Correspondence::sanitizeHtml($table)];
        }

        return $out;
    }

    private function store(array $result): void
    {
        $blocks = $this->convert($result['blocks']);

        DB::transaction(function () use ($result, $blocks) {
            $fresh = Correspondence::query()->whereKey($this->letterId)->lockForUpdate()->first();
            if (!$fresh || !$this->current($fresh)) {
                return;
            }
            if (!$this->allowed($fresh)) {
                $fresh->markFailed('disabled');

                return;
            }

            $versions = $fresh->versions ?? [];
            $last = array_key_last($versions);
            $run = $versions[$last];
            $spec = is_array($fresh->spec) ? $fresh->spec : [];
            $before = $this->paths($spec);

            if (($run['kind'] ?? null) === 'refine') {
                $existing = array_values(is_array($spec['blocks'] ?? null) ? $spec['blocks'] : []);
                $index = (int) ($run['block'] ?? -1);
                if (!isset($existing[$index])) {
                    throw new InvalidArgumentException('block missing');
                }
                if (!in_array($fresh->type, self::HEADED_TYPES, true)) {
                    $blocks[0]['heading'] = is_string($existing[$index]['heading'] ?? null) ? $existing[$index]['heading'] : '';
                }
                $existing[$index] = $blocks[0];
                $spec['blocks'] = $existing;
            } else {
                $spec['blocks'] = $blocks;
                if (trim((string) $fresh->subject) === '' && $result['subject'] !== '') {
                    $fresh->subject = $result['subject'];
                }
            }

            $fresh->spec = $spec;
            $versions[$last] = [
                ...$run,
                'model' => $result['model'],
                'tokens' => $result['tokens'],
                'placeholders' => $result['placeholders'],
                'blanks' => $result['hints'],
            ];
            $fresh->versions = $versions;
            $fresh->forceFill([
                'status' => ($run['from'] ?? null) === Correspondence::STATUS_SAVED ? Correspondence::STATUS_SAVED : Correspondence::STATUS_DRAFT,
                'fail_reason' => null,
                'model' => mb_substr($result['model'], 0, 64),
                'tokens_total' => (int) $fresh->tokens_total + $result['tokens'],
            ])->save();

            $versions[$last]['spec'] = $fresh->spec;
            $versions[$last]['subject'] = $fresh->subject;
            $fresh->versions = $versions;
            $fresh->forceFill(['content_hash' => LetterRules::contentHash($fresh)])->save();

            $kept = $this->paths($fresh->spec ?? []);
            foreach ($versions as $version) {
                $kept = [...$kept, ...$this->paths(is_array($version['spec'] ?? null) ? $version['spec'] : [])];
            }
            $id = (int) $fresh->id;
            $unused = array_values(array_filter(array_diff($before, $kept), fn (string $path) => LetterRules::ownsPath($path, $id)));
            if ($unused !== []) {
                DB::afterCommit(fn () => Storage::disk('local')->delete($unused));
            }
        }, 3);
    }

    private function paths(array $spec): array
    {
        $paths = [];
        foreach ([...($spec['blocks'] ?? []), ...($spec['attachments'] ?? [])] as $item) {
            if (is_array($item) && is_string($item['path'] ?? null)) {
                $paths[] = $item['path'];
            }
        }

        return $paths;
    }

    private function failWith(Correspondence $letter, Throwable $e): void
    {
        if ($e instanceof QueryException) {
            Log::error('ai-correspondence.db', ['letter_id' => $letter->id, 'sqlstate' => (string) ($e->errorInfo[0] ?? $e->getCode())]);
            $letter->markFailed('exception');

            return;
        }

        if ($e instanceof RequestException) {
            $status = $e->response?->status() ?? 0;
            $decoded = json_decode((string) $e->response?->body(), true);
            $error = is_array($decoded) ? ($decoded['error'] ?? null) : null;
            Log::warning('ai-correspondence.http', [
                'letter_id' => $letter->id,
                'status' => $status,
                'retry_after' => $status === 429 ? $e->response?->header('Retry-After') : null,
                'type' => is_array($error) && is_scalar($error['type'] ?? null) ? (string) $error['type'] : null,
                'code' => is_array($error) && is_scalar($error['code'] ?? null) ? (string) $error['code'] : null,
            ]);
            $code = match (true) {
                $status === 429 => 'http_429',
                $status >= 500 && $status <= 599 => 'http_5xx',
                $status >= 400 && $status <= 499 => 'http_4xx',
                default => 'exception',
            };
            if ($code === 'exception') {
                report($e);
            }
            $letter->markFailed($code);

            return;
        }

        $code = match (true) {
            $e instanceof JsonException => 'invalid_json',
            $e instanceof InvalidArgumentException, $e instanceof ValidationException => 'invalid_output',
            $e instanceof DomainException => match ($e->getMessage()) {
                'too_large', 'blocked', 'empty', 'invalid_json', 'invalid_output' => $e->getMessage(),
                'unreachable' => 'timeout',
                default => 'exception',
            },
            default => 'exception',
        };
        if ($code === 'exception') {
            report($e);
        }
        $letter->markFailed($code);
    }
}
