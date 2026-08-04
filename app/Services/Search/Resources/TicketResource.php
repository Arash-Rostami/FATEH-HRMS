<?php

namespace App\Services\Search\Resources;

use App\Models\Ticket;
use App\Models\User;
use App\Services\Search\Contracts\SearchResource;
use Illuminate\Database\Eloquent\Builder;

class TicketResource extends SearchResource
{
    protected string $type = 'ticket';
    protected string $group = 'سیستم تیکت';
    protected string $icon = 'support_agent';
    protected string $model = Ticket::class;
    protected array $columns = ['request_subject', 'description', 'request_type'];
    protected ?string $titleField = 'request_subject';
    protected ?string $subtitleField = 'description';

    public function action($row): string
    {
        return $this->route('ths', $row->getKey());
    }

    protected function scope(Builder $query): void
    {
        $me = $this->me();
        $user = auth()->user();
        $deptCode = $user?->profile?->department_id;
        $isHead = $deptCode && User::highestRankingInDepartment($deptCode)?->is($user);

        $query->where(function (Builder $q) use ($me, $isHead, $deptCode) {
            $q->where('requester_id', $me)->orWhere('assigned_to', $me);

            if ($isHead) {
                $q->orWhere(function (Builder $sq) use ($deptCode) {
                    $sq->where('status', 'open')
                        ->whereRaw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(extra, '$.target_department')), ?) = ?", [
                            Ticket::defaultTargetDepartment(),
                            $deptCode,
                        ]);
                });
            }
        });
    }
}
