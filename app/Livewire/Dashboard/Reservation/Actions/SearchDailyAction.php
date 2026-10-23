<?php

namespace App\Livewire\Dashboard\Reservation\Actions;

use App\Livewire\Dashboard\Reservation\Presentation\DailyPresenter;
use App\Models\Reservation;

class SearchDailyAction
{
    public function execute(array $state, array $permittedTypes, int $viewerId): array
    {
        $scope = DailyPresenter::scope($state);
        [$from, $to] = DailyPresenter::range($scope, DailyPresenter::anchor($state), $state);
        $type = $state['type'] ?? '';
        $types = in_array($type, $permittedTypes, true) ? [$type] : $permittedTypes;
        $search = trim((string) ($state['search'] ?? ''));
        $limit = min(max(DailyPresenter::PER_PAGE, (int) ($state['limit'] ?? DailyPresenter::PER_PAGE)), DailyPresenter::MAX_ROWS);

        $query = Reservation::query()
            ->held()
            ->overlapping($from, $to)
            ->whereHas('resource', fn($q) => $q->whereIn('type', $types))
            ->when(! empty($state['mine']), fn($q) => $q->forUser($viewerId))
            ->when($search !== '', fn($q) => $q->where(fn($w) => $w
                ->whereHas('resource', fn($r) => $r->matching($search))
                ->orWhereHas('user', fn($u) => $u->whereRaw('INSTR(name, ?) > 0', [$search]))));

        $totals = (clone $query)->toBase()->selectRaw('COUNT(*) AS total, COUNT(DISTINCT resource_id) AS resources')->first();
        $total = (int) $totals->total;
        $with = in_array('meeting', $types, true)
            ? ['resource.relatedUser.profile', 'user.profile.department']
            : ['resource', 'user.profile.department'];

        $rows = $query->with($with)
            ->orderBy('start_time')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        return [
            'groups' => DailyPresenter::groups($rows, $viewerId, $from),
            'total' => $total,
            'resources' => (int) $totals->resources,
            'shown' => $rows->count(),
            'more' => $total > $rows->count() && $limit < DailyPresenter::MAX_ROWS,
            'capped' => $total > $rows->count() && $limit >= DailyPresenter::MAX_ROWS,
            'label' => DailyPresenter::label($scope, $from, $to),
            'presets' => DailyPresenter::messagePresets(ContactColleagueAction::PRESETS),
        ];
    }
}
