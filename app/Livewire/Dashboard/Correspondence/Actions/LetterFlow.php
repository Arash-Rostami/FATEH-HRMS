<?php

namespace App\Livewire\Dashboard\Correspondence\Actions;

use App\Livewire\Dashboard\Correspondence\Forms\LetterForm;
use App\Livewire\Dashboard\Correspondence\Presentation\CorrespondencePresenter;
use App\Models\Correspondence;
use App\Models\Letterhead;
use App\Models\Profile;
use App\Models\User;
use App\Support\CorrespondenceAccessPolicy;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Morilog\Jalali\CalendarUtils;
use Throwable;

class LetterFlow
{
    public const LIFECYCLE_EVENTS = ['shared', 'approved', 'approved_instead', 'rejected', 'withdrawn', 'voided', 'superseded', 'closed', 'registered', 'routed', 'printed', 'wet_signed', 'delivered_manually'];

    public const LINK_ATTEMPTS = 10;

    public const SOFT_EVENTS = 900;

    private const MAX_EVENTS = 1000;

    public static function run(int $id, Closure $step, array &$copied = [], array &$strikes = []): void
    {
        $actor = auth()->user();
        if (!$actor instanceof User || !$actor->isActive()) {
            LetterRules::deny();
        }

        try {
            DB::transaction(function () use ($id, $step, $actor) {
                $row = Correspondence::query()->whereKey($id)->lockForUpdate()->first() ?? LetterRules::deny();

                $step($row, $actor);
            });
        } catch (Throwable $e) {
            foreach ((array) ($strikes['keys'] ?? []) as $key) {
                RateLimiter::hit($key, (int) $strikes['decay']);
            }
            $strikes = [];

            if ($copied !== []) {
                Storage::disk('local')->delete($copied);
            }

            throw $e instanceof QueryException && ($e->errorInfo[0] ?? null) === '23000'
                ? LetterRules::fail('letterhead_id', 'number_unavailable')
                : $e;
        }
    }

    public static function presenter(): CorrespondencePresenter
    {
        return new CorrespondencePresenter();
    }

    public static function expect(Correspondence $row, array $payload): void
    {
        $hash = $payload['expected_hash'] ?? null;

        if (is_string($hash) && $hash !== '' && !hash_equals(LetterRules::contentHash($row), $hash)) {
            throw LetterRules::stale();
        }

        if (array_key_exists('expected_awaiting', $payload) && (int) $payload['expected_awaiting'] !== (int) $row->awaiting_user_id) {
            throw LetterRules::stale();
        }
    }

    public static function incomingRegistry(Correspondence $row, User $actor, array $statuses): void
    {
        if (!CorrespondenceAccessPolicy::isRegistry($actor) || !CorrespondenceAccessPolicy::canSteward($actor, $row) || $row->direction !== Correspondence::DIRECTION_IN) {
            LetterRules::deny();
        }
        if (!in_array($row->status, $statuses, true)) {
            throw LetterRules::fail('status', 'invalid_state');
        }
    }

    public static function settle(Correspondence $row, User $actor, string $status, string $event): void
    {
        self::incomingRegistry($row, $actor, [Correspondence::STATUS_RECEIVED, Correspondence::STATUS_ROUTED]);

        $row->forceFill(['status' => $status]);
        self::log($row, $actor, $event);
        $row->save();
    }

    public static function requireLetterhead(Correspondence $row, string $kind): Letterhead
    {
        return Letterhead::query()->active()->where('kind', $kind)->find($row->letterhead_id)
            ?? throw LetterRules::fail('letterhead_id', 'invalid_value');
    }

    public static function requireRoute(array $parties): void
    {
        $active = User::getCachedActiveOptions();
        $seen = [];

        foreach ($parties as $party) {
            if (!in_array($party['role'] ?? null, LetterForm::ROLES, true)) {
                throw LetterRules::fail('form', 'route_incomplete');
            }

            if (blank($party['user_id'] ?? null)) {
                continue;
            }

            $userId = (int) $party['user_id'];
            if (!$active->has($userId) || isset($seen[$userId])) {
                throw LetterRules::fail('form', 'route_incomplete');
            }
            $seen[$userId] = true;
        }
    }

    public static function numberContext(Correspondence $row, CarbonInterface $moment): array
    {
        return [
            'type' => (string) config("ai.correspondence.types.{$row->type}.code"),
            'dept' => (string) Profile::query()->where('user_id', $row->user_id)->value('department_id'),
            'date' => $moment,
        ];
    }

    public static function withCeo(array $parties, int $authorId): array
    {
        $have = array_map(fn (array $party) => (int) ($party['user_id'] ?? 0), $parties);
        $ceos = User::query()->visibleOnBoard()
            ->whereHas('profile', fn ($query) => $query->where('position', 'ceo'))
            ->orderBy('id')->limit(3)->get(['id', 'name']);

        foreach ($ceos as $ceo) {
            $id = (int) $ceo->id;

            if ($id !== $authorId && !in_array($id, $have, true) && count($parties) < (int) config('ai.correspondence.max_recipients')) {
                $parties[] = [...LetterForm::blankParty(), 'user_id' => $id, 'role' => LetterForm::ROLE_FYI, 'name' => (string) $ceo->name, 'order' => count($parties) + 1];
            }
        }

        return $parties;
    }

    public static function hasBody(Correspondence $row): bool
    {
        foreach ((array) ($row->spec['blocks'] ?? []) as $block) {
            if (!is_array($block)) {
                continue;
            }
            if (($block['type'] ?? null) === 'image') {
                if (filled($block['path'] ?? null)) {
                    return true;
                }
                continue;
            }
            foreach (['heading', 'html', 'text'] as $key) {
                $value = $block[$key] ?? null;
                if (!is_string($value)) {
                    continue;
                }
                $visible = trim(str_replace("\xC2\xA0", ' ', html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
                if ($visible !== '' || ($key === 'html' && preg_match('#<t[dh][\s>]#i', $value) === 1)) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function parties(Correspondence $row): array
    {
        return array_values(array_filter((array) ($row->parties ?? []), fn ($party) => is_array($party)));
    }

    public static function approverIndex(array $parties, int $userId): ?int
    {
        foreach ($parties as $index => $party) {
            if ((int) ($party['user_id'] ?? 0) === $userId && ($party['role'] ?? null) === LetterForm::ROLE_APPROVER) {
                return $index;
            }
        }

        return null;
    }

    public static function approverCount(array $parties): int
    {
        return count(array_filter($parties, fn ($party) => ($party['role'] ?? null) === LetterForm::ROLE_APPROVER));
    }

    public static function unsignedApprovers(array $parties): int
    {
        return count(array_filter($parties, fn ($party) => is_array($party)
            && ($party['role'] ?? null) === LetterForm::ROLE_APPROVER
            && !filled($party['signed_at'] ?? null)));
    }

    public static function withoutSignatures(array $parties): array
    {
        return array_map(function (array $party) {
            unset($party['signed_at'], $party['signature_path']);
            if (filled($party['user_id'] ?? null)) {
                unset($party['opened_at']);
            }

            return $party;
        }, $parties);
    }

    public static function hasEvent(Correspondence $row, string $action): bool
    {
        foreach ((array) ($row->events ?? []) as $event) {
            if (is_array($event) && ($event['action'] ?? null) === $action) {
                return true;
            }
        }

        return false;
    }

    public static function log(Correspondence $row, User $actor, string $action, array $extra = []): void
    {
        $events = (array) ($row->events ?? []);
        if (count($events) >= (in_array($action, self::LIFECYCLE_EVENTS, true) ? self::MAX_EVENTS : self::SOFT_EVENTS)) {
            throw LetterRules::fail('form', 'too_many_events');
        }
        $events[] = ['at' => now()->toIso8601String(), 'user_id' => (int) $actor->id, 'action' => $action, ...$extra];
        $row->events = $events;
    }

    public static function jalali(CarbonInterface $moment): string
    {
        [$year, $month, $day] = CalendarUtils::toJalali($moment->year, $moment->month, $moment->day);

        return sprintf('%04d/%02d/%02d', $year, $month, $day);
    }

    public static function writeSteps(Correspondence $letter): void
    {
        $approvers = array_values(array_filter(
            $letter->parties ?? [],
            fn ($party) => is_array($party) && ($party['role'] ?? null) === LetterForm::ROLE_APPROVER
        ));
        $letter->steps_total = $approvers === [] ? null : count($approvers);
        $letter->step_no = null;
        if ($letter->status !== Correspondence::STATUS_SHARED) {
            return;
        }

        $awaiting = null;
        foreach ($approvers as $i => $party) {
            $letter->step_no = $i + 1;
            if (!filled($party['signed_at'] ?? null)) {
                $awaiting = $party['user_id'];
                break;
            }
        }
        $letter->awaiting_user_id = filled($awaiting) ? (int) $awaiting : null;
    }
}
