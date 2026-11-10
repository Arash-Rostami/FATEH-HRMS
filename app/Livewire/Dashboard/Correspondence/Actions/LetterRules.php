<?php

namespace App\Livewire\Dashboard\Correspondence\Actions;

use App\Livewire\Dashboard\Correspondence\Forms\LetterForm;
use App\Models\Correspondence;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Morilog\Jalali\CalendarUtils;
use RuntimeException;

class LetterRules
{
    public const STALE = 409;

    public const MAIL_SUBJECT_MAX = 150;

    public const MAIL_BODY_MAX = 1500;

    private const HIDDEN_CHARS = '/[\x{202A}-\x{202E}\x{2066}-\x{2069}\x{200B}\x{200D}-\x{200F}\x{2060}\x{FEFF}\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u';

    public static function canStructure(?Correspondence $letter, ?User $user): bool
    {
        return $letter === null
            || ($letter->isOwnedBy($user) && in_array($letter->status, [...Correspondence::DRAFT_LIKE, Correspondence::STATUS_SAVED, Correspondence::STATUS_REJECTED], true));
    }

    public static function contentHash(Correspondence $letter): string
    {
        return hash('sha256', json_encode(self::sorted([
            'subject' => $letter->subject,
            'type' => $letter->type,
            'direction' => $letter->direction,
            'urgency' => $letter->urgency,
            'letterhead_id' => $letter->letterhead_id === null ? null : (int) $letter->letterhead_id,
            'classification' => $letter->classification,
            'sign_mode' => $letter->sign_mode,
            'route_mode' => $letter->route_mode,
            'sender_category' => $letter->sender_category,
            'spec' => $letter->spec,
            'parties' => LetterForm::partyRows($letter->parties ?? []),
        ]), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    public static function blockHash(array $block): string
    {
        return md5(json_encode(self::sorted($block), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    public static function modeValid(array $party): bool
    {
        return in_array($party['delivery'] ?? null, (array) config('ai.correspondence.delivery_modes'), true);
    }

    public static function emailValid(array $party): bool
    {
        $email = trim((string) ($party['email'] ?? ''));

        return mb_strlen($email) <= 254 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function linked(array $party): bool
    {
        return filled($party['token_hash'] ?? null) || filled($party['token_hash_next'] ?? null);
    }

    public static function lapsed(array $party): bool
    {
        foreach (['token_hash' => 'expires_at', 'token_hash_next' => 'expires_at_next'] as $hash => $expiry) {
            if (blank($party[$hash] ?? null)) {
                continue;
            }

            $at = $party[$expiry] ?? null;
            $stamp = is_string($at) && strlen($at) <= 40 ? strtotime($at) : false;

            if ($stamp === false || $stamp >= time()) {
                return false;
            }
        }

        return true;
    }

    public static function plain(mixed $value, int $cap): string
    {
        if (!is_scalar($value)) {
            return '';
        }

        $clean = preg_replace(self::HIDDEN_CHARS, '', strip_tags((string) $value));

        return mb_substr(trim($clean ?? ''), 0, $cap);
    }

    public static function tags(mixed $tags): array
    {
        if (!is_array($tags)) {
            return [];
        }

        $clean = array_map(fn ($tag) => self::plain($tag, 32), array_slice($tags, 0, 50));

        return array_slice(array_values(array_unique(array_filter($clean, fn (string $tag) => $tag !== ''))), 0, 12);
    }

    public static function recipientIds(array $parties): array
    {
        $ids = [];
        foreach ($parties as $party) {
            if (is_array($party) && filled($party['user_id'] ?? null)) {
                $ids[] = (int) $party['user_id'];
            }
        }

        return array_values(array_unique($ids));
    }

    public static function directory(int $id): string
    {
        return "correspondence/{$id}";
    }

    public static function ownsPath(string $path, int $id): bool
    {
        return $id > 0 && !str_contains($path, '..') && str_starts_with($path, self::directory($id) . '/');
    }

    public static function oneOf(mixed $value, array $allowed, string $default): string
    {
        if ($value === null || $value === '') {
            return $default;
        }

        in_array($value, $allowed, true) || self::invalid('form');

        return $value;
    }

    public static function checkedDate(string $value, string $field): ?string
    {
        $value = str_replace('-', '/', trim($value));
        if ($value === '') {
            return null;
        }

        if (preg_match('/^(\d{4})\/(\d{2})\/(\d{2})$/', $value, $m) !== 1 || !CalendarUtils::checkDate((int) $m[1], (int) $m[2], (int) $m[3], true)) {
            self::invalid("form.{$field}");
        }

        return $value;
    }

    public static function invalid(string $field): never
    {
        throw ValidationException::withMessages([$field => __('resources/correspondence/strings.errors.invalid_value')]);
    }

    public static function fail(string $field, string $key, array $replace = []): ValidationException
    {
        return ValidationException::withMessages([$field => __("resources/correspondence/strings.errors.{$key}", $replace)]);
    }

    public static function stale(): RuntimeException
    {
        return new RuntimeException(__('resources/correspondence/strings.errors.changed_elsewhere'), self::STALE);
    }

    public static function deny(): never
    {
        throw new RuntimeException(__('resources/correspondence/strings.errors.denied'));
    }

    private static function sorted(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }
        if (array_is_list($value)) {
            return array_map(self::sorted(...), $value);
        }
        ksort($value);

        return array_map(self::sorted(...), $value);
    }
}
