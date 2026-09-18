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
    public function __construct(
        private readonly TicketPresenter $presenter,
    ) {}

    public function execute(Ticket $ticket, int $assigneeId, User $actor): Ticket
    {
        abort_unless(TicketAccessPolicy::canAssign($ticket, $actor), 403);
        abort_unless($ticket->status !== 'closed', 422, 'امکان تغییر مسئول تیکت بسته‌شده وجود ندارد.');

        $target = $ticket->targetDepartmentId ?: Ticket::defaultTargetDepartment();
        $isValidAssignee = User::whereKey($assigneeId)
            ->when($target, fn ($q) => $q->whereHas('profile', fn ($pq) => $pq->where('department_id', $target)))
            ->exists();

        abort_unless($isValidAssignee, 422, 'کاربر انتخاب‌شده متعلق به این واحد سازمانی نیست.');

        DB::transaction(function () use ($ticket, $assigneeId) {
            $ticket->update(['assigned_to' => $assigneeId]);
            $this->syncLinkedTask($ticket, $assigneeId);
        });

        return $ticket->fresh();
    }

    public function syncForAdmin(Ticket $ticket, ?int $assigneeId): void
    {
        DB::transaction(fn () => $this->syncLinkedTask($ticket, $assigneeId));
    }

    public function markLinkedTaskDone(Ticket $ticket): void
    {
        DB::transaction(function () use ($ticket) {
            $task = $this->findLinkedTask($ticket, ['id', 'project_id', 'assigned_to', 'user_id', 'priority', 'status']);

            if (!$task || $task->status === 'done') {
                return;
            }

            $task->update([
                'status' => 'done',
                'rank' => Task::rankForPriority(
                    $task->project_id,
                    $task->assigned_to ?? $task->user_id,
                    'done',
                    $task->priority?->value,
                    $task->id
                ),
            ]);
        });
    }

    private function syncLinkedTask(Ticket $ticket, ?int $assigneeId): void
    {
        $task = $this->findLinkedTask($ticket, ['id', 'project_id', 'user_id', 'priority', 'status']);
        $priority = $this->mapTicketPriority($ticket);
        $status = $assigneeId !== null ? 'in-progress' : 'todo';

        if ($task) {
            $payload = ['assigned_to' => $assigneeId, 'status' => $status];

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
            'status' => $status,
            'user_id' => $ticket->requester_id,
            'assigned_to' => $assigneeId,
            'ticket_id' => $ticket->id,
            'priority' => $priority,
            'rank' => Task::rankForPriority(null, $assigneeId, $status, $priority),
        ]);

        $task->detail()->create([]);
    }

    private function findLinkedTask(Ticket $ticket, array $columns): ?Task
    {
        return Task::where('ticket_id', $ticket->id)->first($columns);
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
