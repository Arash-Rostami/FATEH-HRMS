<?php

namespace App\Models\Concerns\Hooks;

use App\Livewire\Dashboard\Ths\Actions\AssignTicketAction;
use App\Models\Profile;
use App\Models\Task;
use App\Models\Ticket;
use Illuminate\Validation\ValidationException;

trait HasTicketHooks
{
    protected static function bootHasTicketHooks(): void
    {
        static::deleting(self::cascadeLinkedRecordsOnDeleting(...));
        static::creating(self::seedExtraOnCreating(...));
        static::updated(self::syncLinkedTaskDeadlineOnUpdated(...));
        static::saved(self::syncLinkedTaskOnSaved(...));
        static::saving(self::enforceWorkflowOnSaving(...));
    }

    private static function cascadeLinkedRecordsOnDeleting(Ticket $ticket)
    {
        Task::syncingFromTicket(fn() => Task::where('ticket_id', $ticket->id)->get()->each->delete());
        $ticket->replies->each->delete();
        static::deleteStoredFiles($ticket->attachment);
        static::deleteStoredFiles($ticket->requester_files, ['file']);
        static::deleteStoredFiles($ticket->assignee_files, ['file']);
    }

    private static function seedExtraOnCreating(Ticket $ticket)
    {
        $extra = $ticket->extra ?? [];

        if (empty($ticket->targetDepartmentId)) {
            $extra['target_department'] = static::defaultTargetDepartment();
        }

        if (empty($extra['department']) && filled($ticket->requester_id)) {
            $dept = Profile::where('user_id', $ticket->requester_id)->value('department_id');
            if (filled($dept)) {
                $extra['department'] = $dept;
            }
        }

        $ticket->extra = $extra;
    }

    private static function syncLinkedTaskDeadlineOnUpdated(Ticket $ticket)
    {
        if ($ticket->wasChanged('completion_deadline')) {
            Task::syncingFromTicket(fn() => Task::where('ticket_id', $ticket->id)->first()?->update(['deadline' => $ticket->completion_deadline]));
        }
    }

    private static function syncLinkedTaskOnSaved(Ticket $ticket)
    {
        if ($ticket->wasRecentlyCreated) return;

        $syncs = $ticket->wasChanged(['assigned_to', 'priority']);
        $closes = $ticket->wasChanged('status') && static::isClosingStatus($ticket->status);

        if (!$syncs && !$closes) return;

        $assign = app(AssignTicketAction::class);

        if ($syncs) {
            $assign->syncForAdmin($ticket, $ticket->assigned_to);
        }

        if ($closes) {
            $assign->markLinkedTaskDone($ticket);
        }
    }

    private static function enforceWorkflowOnSaving(Ticket $ticket)
    {
        if ($ticket->exists && $ticket->isDirty('extra')) {
            $ticket->extra = [...($ticket->getOriginal('extra') ?? []), ...($ticket->extra ?? [])];
        }

        if ($ticket->exists && $ticket->isDirty('status') && $ticket->getOriginal('status') === 'closed' && !static::isClosingStatus($ticket->status)) {
            throw ValidationException::withMessages(['status' => 'تیکت بسته‌شده دوباره باز نمی‌شود.']);
        }

        if ($ticket->isDirty('assigned_to') && filled($ticket->assigned_to)) {
            if (static::isClosingStatus($ticket->status)) {
                throw ValidationException::withMessages(['assigned_to' => 'امکان تغییر مسئول تیکت بسته‌شده وجود ندارد.']);
            }

            if (!$ticket->assignableUsers()->whereKey($ticket->assigned_to)->exists()) {
                throw ValidationException::withMessages(['assigned_to' => 'کاربر انتخاب‌شده متعلق به این واحد سازمانی نیست.']);
            }
        }

        if ($ticket->exists && $ticket->isDirty('status') && $ticket->status === 'open') {
            $ticket->assigned_to = null;
        }

        if ($ticket->isDirty('assigned_to') && !$ticket->isDirty('status')) {
            if (blank($ticket->assigned_to) && $ticket->getOriginal('status') === 'in-progress') {
                $ticket->status = 'open';
            } elseif (filled($ticket->assigned_to) && in_array($ticket->getOriginal('status'), ['open', null], true)) {
                $ticket->status = 'in-progress';
            }
        }

        if (!$ticket->isDirty('status') || !static::isClosingStatus($ticket->status)) {
            return;
        }

        $ticket->completion_date ??= now();

        if ($ticket->action_result === static::WITHDRAWN_RESULT) {
            return;
        }

        if ($ticket->exists) {
            $ticket->action_result = $ticket->composeActionResultFromReplies();
        }
    }
}
