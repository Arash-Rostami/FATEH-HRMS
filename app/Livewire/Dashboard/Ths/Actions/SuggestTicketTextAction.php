<?php

namespace App\Livewire\Dashboard\Ths\Actions;

use App\Enums\TicketPriority;
use App\Livewire\Dashboard\Ths\Forms\TicketForm;
use App\Models\Department;
use App\Models\Ticket;
use App\Services\Ai\AiClient;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SuggestTicketTextAction
{
    private const CLASSIFICATION = ['targetDepartment', 'requestType', 'requestArea', 'priority'];

    private const BLANK = '«____»';

    private const SCHEMA = [
        'type' => 'object',
        'properties' => [
            'subject' => ['type' => 'string'],
            'description' => ['type' => 'string'],
            'question' => ['type' => ['string', 'null']],
        ],
        'required' => ['subject', 'description', 'question'],
        'additionalProperties' => false,
    ];

    private int $userId = 0;

    private int $spent = 0;

    public function __construct(private AiClient $client)
    {
    }

    public function execute(TicketForm $form): array
    {
        abort_unless(auth()->check() && thsAiOn(), 404);

        $this->userId = $userId = (int) auth()->id();
        $this->spent = 0;

        $form->validate(Arr::only($form->getRules(), self::CLASSIFICATION));

        $limit = (int) config('ai.ticket.input_max');

        if (mb_strlen($form->subject) > min($limit, (int) config('ai.ticket.subject_max')) || mb_strlen($form->description) > min($limit, (int) config('ai.ticket.description_max'))) {
            $this->log('long');

            return $this->result('none', null, 0, $userId, 'long');
        }

        $cooldown = (int) config('ai.ticket.cooldown');
        $window = 2 * ((int) AiClient::setting('ticket', 'timeout') + (int) AiClient::setting('ticket', 'connect_timeout')) + $cooldown;

        if (!Cache::add("ths:ai:busy:{$userId}", 1, $window)) {
            $this->log('busy');

            return $this->result('none', null, 0, $userId, 'busy');
        }

        try {
            return $this->generate($form, $userId);
        } finally {
            Cache::put("ths:ai:busy:{$userId}", 1, $cooldown);
        }
    }

    private function generate(TicketForm $form, int $userId): array
    {
        $labels = $this->labels($form);

        if (Cache::has('ths:ai:down')) {
            return $this->skeleton($form, $labels, $userId, 'unreachable', 'down');
        }

        $day = now()->format('Y-m-d');

        if (Ticket::consumeQuota($userId) > Ticket::quotaCap()) {
            Ticket::refundQuota($userId, $day);

            return $this->skeleton($form, $labels, $userId, 'quota', 'quota');
        }

        try {
            $draft = $this->ask($form, $labels);
        } catch (\DomainException | RequestException $e) {
            $code = $e instanceof \DomainException ? $e->getMessage() : 'unreachable';

            $outcome = $e instanceof RequestException ? 'http_' . $e->response->status() : $code;

            if ($e instanceof RequestException ? ($e->response->status() >= 500 || $e->response->status() === 429) : $code === 'unreachable') {
                Cache::put('ths:ai:down', 1, (int) config('ai.ticket.down_for'));
            }

            if (!in_array($code, (array) config('ai.ticket.no_refund'), true)) {
                Ticket::refundQuota($userId, $day);
            }

            return $this->skeleton($form, $labels, $userId, in_array($code, ['blocked', 'too_large'], true) ? $code : 'unreachable', $outcome);
        } catch (\Throwable $e) {
            Ticket::refundQuota($userId, $day);

            throw $e;
        }

        $blanks = $this->apply($form, $draft['subject'], $draft['description']);
        $question = $form->priority === 'high' && is_string($draft['question'] ?? null)
            ? ($this->clean($draft['question'], false, (int) config('ai.ticket.subject_max')) ?: null)
            : null;
        $this->log('ok');

        return $this->result('ai', $question, $blanks, $userId);
    }

    private function labels(TicketForm $form): array
    {
        $dept = in_array($form->targetDepartment, ['', 'N/A'], true) ? null : $form->targetDepartment;

        return [
            'department' => $dept ? (string) Department::getOfferedTicketOptions()->get($dept, '') : '',
            'type' => Ticket::getCustomRequestTypeLabel($form->requestType, $dept),
            'area' => Ticket::getCustomRequestAreaLabel($form->requestType, $form->requestArea, $dept),
            'priority' => TicketPriority::from($form->priority)->getLabel(),
        ];
    }

    private function ask(TicketForm $form, array $labels): array
    {
        $cut = fn(string $text): string => preg_replace('/[<>]{3,}/u', '', $text);
        $fence = Str::random(12);

        $messages = [
            ['role' => 'system', 'content' => (string) config('ai.ticket.rules')],
            ['role' => 'user', 'content' => "FENCE: {$fence}\nDEPARTMENT: {$labels['department']}\nREQUEST TYPE: {$labels['type']}\nREQUEST AREA: {$labels['area']}\nPRIORITY: {$form->priority} ({$labels['priority']})\n<<<USER_TEXT:{$fence}>>>\nSUBJECT: {$cut($form->subject)}\nDESCRIPTION: {$cut($form->description)}\n<<<END:{$fence}>>>"],
        ];

        for ($attempt = 0; $attempt < 2; $attempt++) {
            try {
                $reply = $this->client->json($messages, self::SCHEMA, 'ticket');
            } finally {
                $this->spent += $this->client->lastTokens();
            }

            try {
                if ($reply['content'] === null) {
                    throw new \InvalidArgumentException('empty content');
                }

                $data = AiClient::parseJson($reply['content']);

                if (!is_string($data['subject'] ?? null) || !is_string($data['description'] ?? null)) {
                    throw new \InvalidArgumentException('subject and description must be strings');
                }

                $subject = $this->clean($data['subject'], false, (int) config('ai.ticket.subject_max'));
                $description = $this->clean($data['description'], true, (int) config('ai.ticket.description_max'));

                if ($subject === '' || $description === '') {
                    throw new \InvalidArgumentException('subject and description must not be empty');
                }

                return ['subject' => $subject, 'description' => $description, 'question' => $data['question'] ?? null];
            } catch (\JsonException | \InvalidArgumentException $e) {
                if ($reply['content'] !== null) {
                    $messages[] = ['role' => 'assistant', 'content' => $reply['content']];
                    $messages[] = ['role' => 'user', 'content' => 'Your previous JSON was invalid: ' . $e->getMessage() . '. Return only corrected JSON.'];
                }
            }
        }

        throw new \DomainException('empty');
    }

    private function clean(string $text, bool $multiline, int $max): string
    {
        $text = preg_replace(['/[\x{200B}\x{200E}\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}\x{FEFF}\x{061C}\x{2060}\x{00AD}\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '/^[ \t]*(?:#{1,6}|[*\-•]|\d{1,2}[.)])[ \t]+/mu', '/\*\*|`+/u'], '', $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text);
        $text = $multiline ? preg_replace('/ ?\R\s*/u', "\n", $text) : preg_replace('/\s+/u', ' ', $text);

        return mb_substr(convertToPersian(trim($text)) ?? '', 0, $max);
    }

    private function skeleton(TicketForm $form, array $labels, int $userId, ?string $error, string $outcome): array
    {
        $config = config('ai.ticket.skeleton');
        $subject = trim($form->subject) !== ''
            ? trim($form->subject)
            : strtr($config['subject'], ['{type}' => $labels['type'], '{area}' => $labels['area']]);
        $key = fn(string $line): string => str_contains($line, ':') ? trim(strstr($line, ':', true)) : trim($line);
        $present = array_map($key, preg_split('/\R/u', $form->description));
        $missing = array_filter([...$config['lines'], ...($form->priority === 'high' ? [$config['urgency']] : [])], fn(string $line): bool => !in_array($key($line), $present, true));
        $lines = [trim($form->description), ...$missing];

        $blanks = $this->apply(
            $form,
            mb_substr($subject, 0, (int) config('ai.ticket.subject_max')),
            mb_substr(implode("\n", array_filter($lines, fn(string $line): bool => $line !== '')), 0, (int) config('ai.ticket.description_max')),
        );
        $this->log($outcome);

        return $this->result('skeleton', null, $blanks, $userId, $error);
    }

    private function apply(TicketForm $form, string $subject, string $description): int
    {
        $form->subject = $subject;
        $form->description = $description;

        return substr_count($subject . ' ' . $description, self::BLANK);
    }

    private function result(string $source, ?string $question, int $blanks, int $userId, ?string $error = null): array
    {
        return [
            'source' => $source,
            'question' => $question,
            'blanks' => $blanks,
            'left' => max(0, Ticket::quotaCap() - Ticket::quotaUsed($userId)),
            'error' => $error,
        ];
    }

    private function log(string $outcome): void
    {
        $context = ['user' => $this->userId, 'outcome' => $outcome, 'tokens' => $this->spent];

        match ($outcome) {
            'ok' => Log::info('ths-ai', $context),
            'busy', 'quota', 'long', 'down' => Log::debug('ths-ai', $context),
            default => Log::warning('ths-ai', $context),
        };
    }
}
