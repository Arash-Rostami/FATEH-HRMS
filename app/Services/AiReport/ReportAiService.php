<?php

namespace App\Services\AiReport;

use App\Services\Ai\AiClient;
use DomainException;
use InvalidArgumentException;
use JsonException;

class ReportAiService
{
    public int $spent = 0;

    public function __construct(private AiClient $client = new AiClient())
    {
    }

    public static function isConfigured(): bool
    {
        return AiClient::isConfigured();
    }

    public function generate(string $prompt, string $extractedText = '', array $inlineParts = [], ?array $parentSpec = null, array $notes = [], array $options = []): array
    {
        $this->spent = 0;
        $parts = [];
        foreach (array_values($inlineParts) as $index => $part) {
            if (!is_array($part)) {
                continue;
            }
            $built = $this->inlinePart($part, $index);
            if ($built !== null) {
                $parts[] = $built;
            }
        }
        $parts[] = ['type' => 'text', 'text' => $this->userBlock($prompt, $extractedText, $parentSpec, $notes, is_string($options['block'] ?? null) ? $options['block'] : null)];

        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt($options)],
            ['role' => 'user', 'content' => $parts],
        ];
        $blockMode = is_string($options['block'] ?? null);
        $replyLang = $blockMode && is_string($parentSpec['lang'] ?? null) ? (string) $parentSpec['lang'] : null;
        $budget = (int) config('ai.report.provider.max_calls');
        $ask = function (array $messages) use (&$budget): array {
            if ($budget < 1) {
                throw new DomainException('empty');
            }
            $budget--;

            $reply = $this->client->json($messages, BlockCatalog::schema(), 'report');
            $this->spent += $reply['tokens'];

            return $reply;
        };
        $first = $ask($messages);
        $tokens = $first['tokens'];
        $content = $first['content'];
        if ($content === null && $budget > 0) {
            $second = $ask($messages);
            $tokens += $second['tokens'];
            $content = $second['content'];
        }
        if ($content === null) {
            throw new DomainException('empty');
        }

        try {
            $spec = $this->decode($content, $replyLang);
        } catch (JsonException | InvalidArgumentException $e) {
            if ($budget < 1) {
                throw $e;
            }
            $messages[] = ['role' => 'assistant', 'content' => $content];
            $messages[] = ['role' => 'user', 'content' => 'Your previous JSON was invalid: ' . $e->getMessage() . '. Return only corrected JSON.'];
            $repair = $ask($messages);
            $tokens += $repair['tokens'];
            if ($repair['content'] === null) {
                throw new DomainException('empty');
            }
            $spec = $this->decode($repair['content'], $replyLang);
        }

        return [
            'spec' => $spec,
            'model' => (string) AiClient::setting('report', 'model'),
            'tokens' => $tokens,
        ];
    }

    private function systemPrompt(array $options = []): string
    {
        $map = [
            '{blocks}' => (int) config('ai.report.spec.blocks'),
            '{one_page_blocks}' => (int) config('ai.report.spec.one_page_blocks'),
            '{columns}' => (int) config('ai.report.spec.columns'),
            '{kpi}' => (int) config('ai.report.spec.kpi_items'),
            '{kpi_min}' => (int) config('ai.report.spec.kpi_min'),
            '{facts}' => (int) config('ai.report.spec.facts_items'),
            '{assume_fa}' => (string) config('ai.report.assumptions.heading.fa'),
            '{assume_en}' => (string) config('ai.report.assumptions.heading.en'),
            '{chart_labels}' => (int) config('ai.report.spec.chart_labels'),
            '{chart_series}' => (int) config('ai.report.spec.chart_series'),
            '{pie_slices}' => (int) config('ai.report.spec.pie_slices'),
        ];
        $prompt = strtr((string) config('ai.report.rules'), $map);
        $blockMode = is_string($options['block'] ?? null);
        if (!$blockMode) {
            $type = config('ai.report.types.' . (string) ($options['type'] ?? ''));
            if (is_array($type) && ($type['blocks'] ?? []) !== []) {
                $prompt .= "\nREPORT TYPE: required block order for pages[0] (overrides the default structure in rule 3): " . implode(', ', $type['blocks']) . '. ' . strtr((string) $type['rules'], $map) . ' Always keep the assumptions section from rule 10 immediately before the final callout. When the LENGTH line asks for several pages, spread these blocks across the pages in the same order.';
            }
        }
        if ($blockMode) {
            $prompt .= "\nBLOCK MODE: pages[0].blocks holds EXACTLY ONE block — the revised version of the block given as CURRENT_BLOCK. Return the full revised block with the same type and all its keys, not a fragment. Rule 3's default structure and rule 10 do not apply to this reply: add no assumptions section, no title and no other block.";
        }
        foreach (['length' => 'lengths', 'audience' => 'audiences'] as $key => $group) {
            if ($blockMode && $key === 'length') {
                continue;
            }
            $rules = config("ai.report.{$group}." . (string) ($options[$key] ?? '') . '.rules');
            if (is_string($rules) && $rules !== '') {
                $prompt .= "\n" . strtoupper($key) . ': ' . strtr($rules, $map);
            }
        }

        return $prompt;
    }

    private function decode(string $content, ?string $lang = null): array
    {
        $spec = AiClient::parseJson($content);
        if ($lang !== null) {
            $spec['lang'] = $lang;
        }

        return BlockCatalog::validate($spec);
    }

    private function userBlock(string $prompt, string $extractedText, ?array $parentSpec, array $notes, ?string $blockRef = null): string
    {
        $block = 'Today in Asia/Tehran: Jalali ' . toJalali(now(), 'l j F Y')
            . ' | Gregorian ' . now()->format('Y-m-d (l)')
            . "\nUSER REQUEST:\n" . $prompt;
        if ($blockRef !== null && $parentSpec !== null && ($target = BlockCatalog::resolveBlock($parentSpec, $blockRef)) !== null) {
            $block .= "\nCURRENT_BLOCK (revise ONLY this one block; keep its type unchanged; output the revised block as the single element of pages[0].blocks; keep every key of the original block you are not asked to change):\n"
                . json_encode($target['block'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $headings = collect($parentSpec['pages'] ?? [])
                ->flatMap(fn ($page) => $page['blocks'] ?? [])
                ->map(fn ($other) => is_string($other['heading'] ?? null) ? mb_substr($other['heading'], 0, 80) : null)
                ->filter()
                ->take(16)
                ->implode(' | ');
            $block .= "\nREPORT CONTEXT (read-only, never output it, never change other sections): report title: " . (string) ($parentSpec['title'] ?? '') . '; section headings in order: ' . $headings . '.';
        } elseif ($parentSpec !== null) {
            $block .= "\nCURRENT_SPEC (revise this, keep what the user did not ask to change):\n"
                . json_encode($parentSpec, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }
        if ($extractedText !== '') {
            $block .= "\nATTACHED TEXT:\n" . $extractedText;
        }
        if ($notes !== []) {
            $block .= "\nSYSTEM NOTES:\n" . implode("\n", array_map(fn (string $note): string => '- ' . $note, $notes));
        }
        $block .= "\nFINAL REMINDER: answer the USER REQUEST above with the JSON object only; use only the request and the attached material for facts and figures; write \"—\" and state what is missing in the assumptions section instead of guessing; follow the format rules of the system message.";

        return $block;
    }

    private function inlinePart(array $part, int $index): ?array
    {
        $mime = (string) ($part['mime'] ?? '');
        $data = (string) ($part['data'] ?? '');
        if ($data === '') {
            return null;
        }
        if ($mime === 'application/pdf') {
            return ['type' => 'file', 'file' => [
                'filename' => filled($part['name'] ?? null) ? (string) $part['name'] : 'file-' . ($index + 1) . '.pdf',
                'file_data' => 'data:application/pdf;base64,' . $data,
            ]];
        }
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return null;
        }

        return ['type' => 'image_url', 'image_url' => ['url' => 'data:' . $mime . ';base64,' . $data]];
    }
}
