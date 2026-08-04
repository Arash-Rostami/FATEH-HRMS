<?php

namespace App\Livewire\Dashboard\Ths\Actions;

use App\Livewire\Dashboard\Ths\Presentation\TicketPresenter;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use App\Support\TicketAccessPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AssignTicketAction
{
    public function execute(Ticket $ticket, int $assigneeId, User $actor): Ticket
    {
        abort_unless(TicketAccessPolicy::canAssign($ticket, $actor), 403);

        $target = $ticket->targetDepartmentId ?: Ticket::defaultTargetDepartment();
        $isValidAssignee = User::whereKey($assigneeId)
            ->when($target, fn($q) => $q->whereHas('profile', fn($pq) => $pq->where('department_id', $target)))
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

    private function syncLinkedTask(Ticket $ticket, ?int $assigneeId): void
    {
        $task = Task::where('ticket_id', $ticket->id)->first();

        if ($task) {
            $task->update(['assigned_to' => $assigneeId]);
            return;
        }

        if ($assigneeId === null) {
            return;
        }

        $task = Task::create([
            'title' => Str::limit($ticket->request_subject, 180),
            'description' => $this->buildTaskDescription($ticket),
            'status' => 'todo',
            'user_id' => $ticket->requester_id,
            'assigned_to' => $assigneeId,
            'ticket_id' => $ticket->id,
        ]);

        $task->detail()->create([]);
    }

    private function buildTaskDescription(Ticket $ticket): string
    {
        $id = (new TicketPresenter())->formatId($ticket->toArray());
        $notes = trim((string) $ticket->description);

        return $notes === '' ? "#{$id}" : "#{$id} — " . Str::limit($notes, 160);
    }
}
