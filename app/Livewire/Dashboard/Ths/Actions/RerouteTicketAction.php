<?php

namespace App\Livewire\Dashboard\Ths\Actions;

use App\Livewire\Dashboard\Ths\Forms\ReplyForm;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use App\Support\TicketAccessPolicy;
use Illuminate\Support\Facades\DB;

class RerouteTicketAction
{
    public function execute(ReplyForm $form, Ticket $ticket, string $departmentCode, User $actor): void
    {
        $department = Department::getCachedModels()->get($departmentCode);

        abort_unless($department, 422, 'واحد انتخاب‌شده معتبر نیست.');
        abort_if(blank(trim($form->body)), 422, 'توضیح ارجاع را بنویسید.');

        DB::transaction(function () use ($form, $ticket, $department, $actor) {
            $locked = Ticket::lockForUpdate()->find($ticket->id);

            abort_unless($locked && TicketAccessPolicy::canReroute($locked, $actor), 403);
            abort_if($locked->effectiveTargetDepartment() === $department->code, 422, 'تیکت هم‌اکنون در این واحد است.');

            $locked->update([
                'assigned_to' => null,
                'status' => 'open',
                'extra' => [...($locked->extra ?? []), 'target_department' => $department->code],
            ]);
            $locked->addReply($actor, 'ارجاع به ' . $department->displayLabel() . ': ' . $form->body);
        });
    }
}
