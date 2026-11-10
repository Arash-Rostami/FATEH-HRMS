<?php

namespace App\Livewire\Dashboard\Correspondence\Actions;

use App\Livewire\Dashboard\Correspondence\Forms\LetterForm;
use App\Models\Correspondence;
use App\Models\Letterhead;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use App\Support\CorrespondenceAccessPolicy;
use App\Support\ProjectAccessPolicy;
use App\Support\TicketAccessPolicy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Morilog\Jalali\CalendarUtils;
use Throwable;

class SaveLetterAction
{
    private const HEADER_FIELDS = ['type', 'classification', 'urgency', 'sign_mode', 'route_mode', 'letterhead_id'];

    public function execute(User $user, array $changes, ?Correspondence $letter = null): Correspondence
    {
        return LetterWrite::run($user, $changes, $letter, fn (Correspondence $fresh) => $this->save($fresh, $user, $changes));
    }

    private function save(Correspondence $letter, User $user, array $changes): void
    {
        $payload = is_array($changes['payload'] ?? null) ? $changes['payload'] : [];
        $isNew = !$letter->exists;
        $before = $isNew ? '' : LetterRules::contentHash($letter);
        $known = $isNew ? [] : LetterBody::knownPaths($letter);

        if ($isNew) {
            LetterWrite::begin($letter, $user);
        }

        $structural = LetterRules::canStructure($isNew ? null : $letter, $user);
        if ($structural) {
            $this->applyHeader($letter, $user, $payload, $changes);
        } elseif ($this->headerChanged($letter, $payload)) {
            throw ValidationException::withMessages(['form' => __('resources/correspondence/strings.errors.structure_locked')]);
        }

        $letter->subject = LetterRules::plain($payload['subject'] ?? $letter->subject, 200);
        $letter->spec = LetterBody::spec($letter, $payload, $known, $structural);
        LetterFlow::writeSteps($letter);

        if ($isNew) {
            $letter->save();
        }

        $stored = [];
        try {
            LetterUploads::store($letter, $changes, $stored);
            LetterWrite::commit($letter, $user, $before, !$isNew, LetterWrite::smartSettings($payload));
        } catch (Throwable $e) {
            Storage::disk('local')->delete($stored);

            throw $e;
        }

        LetterBody::forgetUnused($letter, $known);
    }

    private function headerChanged(Correspondence $letter, array $payload): bool
    {
        foreach (self::HEADER_FIELDS as $field) {
            if (array_key_exists($field, $payload) && (string) $payload[$field] !== (string) $letter->{$field}) {
                return true;
            }
        }

        return false;
    }

    private function applyHeader(Correspondence $letter, User $user, array $payload, array $changes): void
    {
        $type = $this->typeOf($payload['type'] ?? $letter->type, $letter->type);
        $direction = LetterForm::directionOf($type);
        $incoming = $direction === Correspondence::DIRECTION_IN;
        if ($incoming && $letter->direction !== Correspondence::DIRECTION_IN) {
            CorrespondenceAccessPolicy::isRegistry($user) || LetterRules::deny();
        }

        $letter->type = $type;
        $letter->direction = $direction;
        $letter->mode = $this->smartOn($payload) ? Correspondence::MODE_SMART : Correspondence::MODE_SIMPLE;
        $letter->classification = LetterRules::oneOf($payload['classification'] ?? null, (array) config('ai.correspondence.classifications'), Correspondence::CLASSIFICATION_NORMAL);
        $letter->urgency = LetterRules::oneOf($payload['urgency'] ?? null, (array) config('ai.correspondence.urgencies'), Correspondence::URGENCY_NORMAL);
        $letter->sign_mode = LetterRules::oneOf($payload['sign_mode'] ?? null, LetterForm::SIGN_MODES, Correspondence::SIGN_MODE_ELECTRONIC);
        $letter->route_mode = LetterRules::oneOf($payload['route_mode'] ?? null, LetterForm::ROUTES, Correspondence::ROUTE_SEQUENTIAL);
        $letter->sender_category = $incoming
            ? LetterRules::oneOf($payload['sender_category'] ?? null, LetterForm::CATEGORIES, Correspondence::CATEGORY_NORMAL)
            : null;
        $letter->letterhead_id = $this->letterheadId(isset($payload['letterhead_id']) ? (int) $payload['letterhead_id'] : null, $letter->letterhead_id, $incoming);
        [$letter->related_type, $letter->related_id] = $this->related($user, $letter, $payload['related_type'] ?? null, $payload['related_id'] ?? null);
        $letter->their_number = $incoming ? LetterRules::plain($payload['their_number'] ?? '', 64) : null;
        $letter->their_date = $incoming ? LetterRules::checkedDate((string) ($payload['their_date'] ?? ''), 'theirDate') : null;
        $letter->deadline_at = $this->deadline((string) ($payload['deadline'] ?? ''));
        $letter->tags = LetterRules::tags($payload['tags'] ?? []);
        if (!$letter->exists && is_numeric($changes['reply_to'] ?? null)) {
            $letter->in_reply_to_id = $this->replyTo($user, (int) $changes['reply_to']);
        }

        $letter->parties = LetterParties::clean(is_array($payload['parties'] ?? null) ? $payload['parties'] : [], $letter->parties ?? []);
        $letter->recipients = LetterRules::recipientIds($letter->parties);
    }

    private function letterheadId(?int $requested, ?int $current, bool $incoming): ?int
    {
        if ($requested === null || $requested < 1) {
            return null;
        }

        $head = Letterhead::query()->find($requested, ['id', 'kind', 'active']);
        $head !== null || LetterRules::invalid('form.letterheadId');

        $kind = $incoming ? Letterhead::KIND_REGISTRY : Letterhead::KIND_HEAD;
        if ($head->kind !== $kind || (!$head->active && $requested !== $current)) {
            LetterRules::invalid('form.letterheadId');
        }

        return (int) $head->id;
    }

    private function related(User $user, Correspondence $letter, mixed $type, mixed $id): array
    {
        if (!filled($type)) {
            return [null, null];
        }
        if (!in_array($type, (array) config('ai.correspondence.related_types'), true) || !is_numeric($id)) {
            LetterRules::invalid('form.relatedType');
        }

        $id = (int) $id;
        if ($letter->related_type === $type && (int) $letter->related_id === $id) {
            return [$type, $id];
        }

        $allowed = match ($type) {
            'ticket' => ($row = Ticket::query()->find($id)) !== null && TicketAccessPolicy::canView($row, $user),
            'project' => ($row = Project::query()->find($id)) !== null && ProjectAccessPolicy::canView($row, $user),
            'contact' => User::query()->visibleOnBoard()->whereKey($id)->exists(),
            default => false,
        };
        $allowed || LetterRules::deny();

        return [$type, $id];
    }

    private function replyTo(User $user, int $id): int
    {
        $parent = Correspondence::query()->find($id);
        if (!$parent || !CorrespondenceAccessPolicy::canView($user, $parent)) {
            LetterRules::deny();
        }

        return (int) $parent->id;
    }

    private function deadline(string $value): Carbon
    {
        $jalali = LetterRules::checkedDate($value, 'deadline');
        if ($jalali === null) {
            return now('Asia/Tehran')->addDay()->endOfDay();
        }

        [$year, $month, $day] = array_map('intval', explode('/', $jalali));
        $gregorian = CalendarUtils::createCarbonFromFormat('Y/m/d H:i:s', sprintf('%04d/%02d/%02d 00:00:00', $year, $month, $day));

        return Carbon::parse($gregorian->format('Y-m-d') . ' 23:59:59', 'Asia/Tehran');
    }

    private function typeOf(mixed $type, ?string $current): string
    {
        $type = is_string($type) ? $type : '';
        $config = config("ai.correspondence.types.{$type}");
        if (!is_array($config) || (($config['enabled'] ?? false) !== true && $type !== $current)) {
            LetterRules::invalid('form.type');
        }

        return $type;
    }

    private function smartOn(array $payload): bool
    {
        return ($payload['mode'] ?? null) === Correspondence::MODE_SMART
            && !tenantOff('correspondence:ai')
            && ($payload['classification'] ?? null) === Correspondence::CLASSIFICATION_NORMAL;
    }
}
