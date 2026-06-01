<?php

namespace App\Services\Search\Resources;

use App\Models\Ticket;
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

        $query->where(fn (Builder $q) => $q
            ->where('requester_id', $me)
            ->orWhere('assigned_to', $me)
        );
    }
}
