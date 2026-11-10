<?php

namespace App\Livewire\Dashboard\Correspondence\Actions;

use App\Livewire\Dashboard\Correspondence\Forms\LetterForm;
use App\Models\Correspondence;
use App\Models\Letterhead;
use App\Models\User;
use App\Support\CorrespondenceAccessPolicy;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LetterWrite
{
    private const SMART_KEYS = ['prompt', 'audience', 'tone', 'length'];

    private const EDIT_EVENTS_CAP = 900;

    public static function run(User $user, array $changes, ?Correspondence $letter, Closure $apply): Correspondence
    {
        if ($letter !== null) {
            self::authorize($letter, $user, $changes);
        }

        return DB::transaction(function () use ($user, $changes, $letter, $apply) {
            $fresh = $letter === null
                ? new Correspondence()
                : Correspondence::query()->whereKey($letter->id)->lockForUpdate()->first();

            if ($letter !== null && !$fresh) {
                LetterRules::deny();
            }

            if ($letter !== null) {
                self::authorize($fresh, $user, $changes);
                self::guardHash($fresh, $changes);
            }

            $apply($fresh);

            if ($letter !== null) {
                $letter->setRawAttributes($fresh->getAttributes(), true);
                $letter->syncOriginal();
            }

            return $fresh;
        });
    }

    public static function begin(Correspondence $letter, User $user): void
    {
        $letter->user_id = $user->id;
        $letter->status = Correspondence::STATUS_DRAFT;
        $letter->is_template = false;
        $letter->deadline_at = now('Asia/Tehran')->addDay()->endOfDay();
        $letter->events = [['at' => now()->toIso8601String(), 'user_id' => $user->id, 'action' => 'created']];
    }

    public static function inherit(Correspondence $target, Correspondence $source): void
    {
        $types = array_filter(
            (array) config('ai.correspondence.types'),
            fn ($type) => is_array($type) && ($type['enabled'] ?? false) === true && ($type['direction'] ?? null) === $source->direction
        );
        $target->type = isset($types[$source->type]) ? $source->type : (string) array_key_first($types);
        $target->direction = $source->direction;
        $target->mode = Correspondence::MODE_SIMPLE;
        $target->classification = $source->classification;
        $target->urgency = $source->urgency;
        $target->sign_mode = $source->sign_mode;
        $target->route_mode = $source->route_mode;
        $target->sender_category = $source->sender_category;
        $target->letterhead_id = filled($source->letterhead_id)
            && Letterhead::query()->active()->where('kind', $source->direction === Correspondence::DIRECTION_IN ? Letterhead::KIND_REGISTRY : Letterhead::KIND_HEAD)->whereKey($source->letterhead_id)->exists()
            ? $source->letterhead_id
            : null;
    }

    public static function commit(Correspondence $letter, User $user, string $before, bool $stamp = true, ?array $settings = null): void
    {
        $letter->save();

        $hash = LetterRules::contentHash($letter);
        if ($hash === $before) {
            return;
        }

        if (!in_array($letter->status, Correspondence::DRAFT_LIKE, true)) {
            $letter->parties = LetterParties::withoutActs($letter->parties ?? []);
            LetterFlow::writeSteps($letter);
        }
        if ($stamp) {
            self::stampEdit($letter, $user);
        }
        $letter->content_hash = $hash;
        $was = LetterBody::allPaths($letter);
        self::pushManual($letter, $user, $settings);
        $letter->save();
        LetterBody::forgetUnused($letter, $was);
    }

    public static function smartSettings(array $payload): array
    {
        $settings = ['prompt' => LetterRules::plain($payload['prompt'] ?? '', LetterForm::limits()['prompt_max'])];
        foreach (['audience' => LetterForm::AUDIENCES, 'tone' => LetterForm::TONES, 'length' => LetterForm::LENGTHS] as $key => $allowed) {
            if (in_array($payload[$key] ?? null, $allowed, true)) {
                $settings[$key] = $payload[$key];
            }
        }

        return $settings;
    }

    private static function authorize(Correspondence $letter, User $user, array $changes): void
    {
        if ($letter->status === Correspondence::STATUS_GENERATING) {
            LetterRules::deny();
        }

        if (($changes['delete'] ?? false) === true && (!$letter->isOwnedBy($user) || !in_array($letter->status, Correspondence::DRAFT_LIKE, true))) {
            LetterRules::deny();
        }

        CorrespondenceAccessPolicy::canEdit($user, $letter) || LetterRules::deny();
    }

    private static function guardHash(Correspondence $letter, array $changes): void
    {
        $expected = $changes['expected_hash'] ?? null;
        if (!is_string($expected) || $expected === '' || $letter->content_hash === null || $letter->content_hash === '') {
            return;
        }
        if (!hash_equals((string) $letter->content_hash, $expected)) {
            throw LetterRules::stale();
        }
    }

    private static function stampEdit(Correspondence $letter, User $user): void
    {
        $events = $letter->events ?? [];
        if (count($events) >= self::EDIT_EVENTS_CAP) {
            return;
        }
        $last = $events === [] ? null : $events[array_key_last($events)];
        if (is_array($last) && ($last['action'] ?? null) === 'edited' && (int) ($last['user_id'] ?? 0) === $user->id && isset($last['at']) && Carbon::parse($last['at'])->gt(now()->subMinutes(10))) {
            return;
        }
        $events[] = ['at' => now()->toIso8601String(), 'user_id' => $user->id, 'action' => 'edited'];
        $letter->events = $events;
    }

    private static function pushManual(Correspondence $letter, User $user, ?array $settings): void
    {
        $letter->pushVersion([
            'spec' => $letter->spec,
            'subject' => $letter->subject,
            'at' => now()->toIso8601String(),
            'user_id' => $user->id,
            'manual' => true,
            ...($settings ?? array_intersect_key($letter->lastVersion(), array_flip(self::SMART_KEYS))),
        ]);
    }
}
