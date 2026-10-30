<?php

namespace App\Services\Notify;

use App\Enums\NotifyEvent;
use App\Models\NotifyRule;

final class MessageBuilder
{
    private const BUILTIN_KEYS = ['model', 'event', 'id', 'actor', 'date'];

    private const BIDI_PATTERN = '/[\x{200E}\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}]/u';

    private const LETTER_PATTERN = '/\p{L}/u';

    private const ARABIC_PATTERN = '/\p{Arabic}/u';

    public static function isolate(string $text): string
    {
        $text = preg_replace(self::BIDI_PATTERN, '', $text);

        if (preg_match(self::LETTER_PATTERN, $text, $first) === 1 && preg_match(self::ARABIC_PATTERN, $first[0]) === 1) {
            return "\u{202B}{$text}\u{202C}";
        }

        return $text;
    }

    public function build(NotifyRule $rule, array $payload): Message
    {
        $event = NotifyEvent::tryFrom($payload['event'] ?? '')?->label() ?? (string) ($payload['event'] ?? '');

        $map = [
            '{model}' => (string) ($payload['label'] ?? ''),
            '{event}' => $event,
            '{id}' => (string) ($payload['id'] ?? ''),
            '{actor}' => (string) ($payload['actor'] ?? ''),
            '{date}' => toJalali($payload['at'] ?? now(), 'Y/m/d H:i'),
        ];

        foreach (self::BUILTIN_KEYS as $key) {
            $map['{'.__("resources/notify_rule/strings.builtin_labels.{$key}").'}'] = $map['{'.$key.'}'];
        }

        $labels = NotifyRegistry::fieldLabels((string) ($payload['model'] ?? ''));

        foreach ($payload['values'] ?? [] as $key => $value) {
            $map['{'.$key.'}'] ??= (string) $value;

            if (isset($labels[$key])) {
                $map['{'.$labels[$key].'}'] ??= (string) $value;
            }
        }

        $subject = self::isolate(str_replace(["\r", "\n"], '', strtr($rule->subject, $map)));
        $lines = array_map(fn ($line) => strtr((string) $line, $map), $rule->lines ?? []);

        return new Message($subject, $lines, $rule->name, (string) ($payload['label'] ?? ''), $event, $rule->extra['template'] ?? 'plain');
    }
}
