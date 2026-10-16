<?php

namespace App\Livewire\Dashboard\Ths\Actions;

use App\Filament\Resources\TaskResource\Enums\TaskPriority;
use App\Livewire\Dashboard\Ths\Presentation\TicketPresenter;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use App\Support\TicketAccessPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AssignTicketAction
{
    public const REPLY_PREFIX = 'تخصیص به ';

    public function __construct(
        private readonly TicketPresenter $presenter,
    ) {}

    public function execute(Ticket $ticket, int $assigneeId, User $actor, ?string $seenStatus = null): Ticket
    {
        abort_unless(TicketAccessPolicy::canAssign($ticket, $actor), 403);

        return DB::transaction(function () use ($ticket, $assigneeId, $actor, $seenStatus) {
            $locked = Ticket::lockForUpdate()->findOrFail($ticket->id);

            abort_unless($locked->status !== 'closed', 422, 'امکان تغییر مسئول تیکت بسته‌شده وجود ندارد.');
            abort_if(($seenStatus ?? $ticket->status) === 'open' && $locked->status !== 'open', 422, 'این تیکت قبلاً محول شده است.');
            abort_unless(TicketAccessPolicy::canAssign($locked, $actor), 403);
            $assigneeName = $locked->assignableUsers()->whereKey($assigneeId)->value('name');
            abort_if($assigneeName === null, 422, 'کاربر انتخاب‌شده متعلق به این واحد سازمانی نیست.');

            $locked->update(['assigned_to' => $assigneeId]);
            $locked->addReply($actor, self::REPLY_PREFIX . $assigneeName);

            return $locked->fresh();
        });
    }

    public function close(Ticket $ticket, User $actor): void
    {
        DB::transaction(function () use ($ticket, $actor) {
            $locked = Ticket::lockForUpdate()->find($ticket->id);

            abort_unless($locked && TicketAccessPolicy::canClose($locked, $actor), 403);

            $locked->update(['status' => 'closed']);
        });
    }

    public function syncForAdmin(Ticket $ticket, ?int $assigneeId): void
    {
        DB::transaction(function () use ($ticket, $assigneeId) {
            $locked = Ticket::lockForUpdate()->find($ticket->id);

            if ($locked && $locked->status !== 'closed') {
                $this->syncLinkedTask($locked, $assigneeId);
            }
        });
    }

    public function markLinkedTaskDone(Ticket $ticket): void
    {
        DB::transaction(function () use ($ticket) {
            $task = $this->findLinkedTask($ticket, ['id', 'project_id', 'assigned_to', 'user_id', 'priority', 'status']);

            if (!$task || $task->status === 'done') {
                return;
            }

            Task::syncingFromTicket(fn() => $task->update([
                'status' => 'done',
                'rank' => Task::rankForPriority(
                    $task->project_id,
                    $task->assigned_to ?? $task->user_id,
                    'done',
                    $task->priority?->value,
                    $task->id
                ),
            ]));
        });
    }

    private function syncLinkedTask(Ticket $ticket, ?int $assigneeId): void
    {
        Task::syncingFromTicket(fn() => $this->applyLinkedTask($ticket, $assigneeId));
    }

    private function applyLinkedTask(Ticket $ticket, ?int $assigneeId): void
    {
        $task = $this->findLinkedTask($ticket, ['*'], true);
        $priority = $this->mapTicketPriority($ticket);

        if ($task && $assigneeId === null) {
            $task->trashed() || $task->delete();
            return;
        }

        if ($task) {
            if ($task->status === 'done') {
                return;
            }

            $status = !$task->trashed() && $assigneeId === $task->assigned_to ? $task->status : 'todo';
            $task->trashed() && $task->restore();
            $payload = ['assigned_to' => $assigneeId, 'status' => $status];

            if ($ticket->completion_deadline) {
                $payload['deadline'] = $ticket->completion_deadline;
            }

            if ($task->priority?->value !== $priority || $task->status !== $status) {
                $payload['priority'] = $priority;
                $payload['rank'] = Task::rankForPriority(
                    $task->project_id,
                    $assigneeId ?? $task->user_id,
                    $status,
                    $priority,
                    $task->id
                );
            }

            $task->update($payload);
            return;
        }

        if ($assigneeId === null) {
            return;
        }

        $task = Task::create([
            'title' => Str::limit($ticket->request_subject, 180),
            'description' => $this->buildTaskDescription($ticket),
            'status' => 'todo',
            'deadline' => $ticket->completion_deadline,
            'user_id' => $ticket->requester_id,
            'assigned_to' => $assigneeId,
            'ticket_id' => $ticket->id,
            'priority' => $priority,
            'rank' => Task::rankForPriority(null, $assigneeId, 'todo', $priority),
        ]);

        $task->detail()->create([
            'department_id' => User::whereKey($assigneeId)->with('profile:user_id,department_id')->first(['id'])?->profile?->department_id,
            'action_source_domain' => 'تیکت',
            'action_source' => Str::limit($ticket->request_subject, 120, ''),
        ]);
    }

    private function findLinkedTask(Ticket $ticket, array $columns, bool $withTrashed = false): ?Task
    {
        return Task::query()
            ->when($withTrashed, fn ($q) => $q->withTrashed())
            ->where('ticket_id', $ticket->id)
            ->lockForUpdate()
            ->first($columns);
    }

    private function mapTicketPriority(Ticket $ticket): string
    {
        return match ($ticket->priority) {
            'high' => TaskPriority::High->value,
            'medium' => TaskPriority::Medium->value,
            default => TaskPriority::Low->value,
        };
    }

    private function buildTaskDescription(Ticket $ticket): string
    {
        $id = $this->presenter->formatId($ticket->toArray());
        $notes = trim((string) $ticket->description);

        return $notes === '' ? "#{$id}" : "#{$id} — " . Str::limit($notes, 160);
    }
}
