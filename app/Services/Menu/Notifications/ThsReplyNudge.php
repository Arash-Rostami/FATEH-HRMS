<?php

namespace App\Services\Menu\Notifications;

use App\Models\Reply;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Menu\Contracts\MenuNudge;
use Filament\Notifications\DatabaseNotification as FilamentDatabaseNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

class ThsReplyNudge implements MenuNudge
{
    public function body($subject, User $user): string
    {
        return (new ThsNudge())->body($this->ticket($subject), $user);
    }

    public function for($subject): Collection
    {
        return $this->recipients($this->ticket($subject), (int)$subject->user_id);
    }

    public function getKey(): string
    {
        return 'ths-controller:reply-nudge';
    }

    public function badgeSuppressesCreate(): bool
    {
        return false;
    }

    public function refresh(): bool
    {
        return false;
    }

    public function show($subject, User $user): bool
    {
        $ticket = $this->ticket($subject);

        if ($ticket->status === 'closed' && $ticket->satisfaction_score !== null) {
            return false;
        }

        return (int)$subject->user_id !== $user->id || $ticket->isActionRecipient($user);
    }

    public function title($subject, User $user): string
    {
        return (new ThsNudge())->title($this->ticket($subject), $user);
    }

    public function triggers(): array
    {
        return [
            ['class' => Reply::class, 'on' => ['created', 'deleted'], 'subject' => fn(Reply $reply) =>
                $reply->repliable_type === Ticket::class ? $reply : null],
            ['class' => Ticket::class, 'on' => ['updated'], 'subject' => function (Ticket $ticket) {
                $this->prune($ticket);

                return null;
            }],
        ];
    }

    public function url($subject): ?string
    {
        return route('ths', ['open' => $this->ticket($subject)->getKey()]);
    }

    private function prune(Ticket $ticket): void
    {
        if (!$ticket->wasChanged(['status', 'assigned_to', 'satisfaction_score', 'action_result', 'extra'])) {
            return;
        }

        $replyIds = $ticket->replies()->pluck('id')->map(fn($id) => (string)$id)->all();

        if (!$replyIds) {
            return;
        }

        $keep = $ticket->status === 'closed' && $ticket->satisfaction_score !== null
            ? []
            : $this->recipients($ticket)->pluck('id')->all();

        DatabaseNotification::query()
            ->where('type', FilamentDatabaseNotification::class)
            ->where('notifiable_type', User::class)
            ->where('data->menu_key', $this->getKey())
            ->whereIn('data->item_id', $replyIds)
            ->when($keep, fn($query) => $query->whereNotIn('notifiable_id', $keep))
            ->delete();
    }

    private function recipients(Ticket $ticket, ?int $authorId = null): Collection
    {
        $recipients = $ticket->currentActionRecipients();

        $otherPartyIds = $ticket->status === 'closed'
            ? []
            : array_filter([$ticket->requester_id, $ticket->assigned_to], fn($id) => $id !== null && (int)$id !== $authorId);

        if ($otherPartyIds) {
            $recipients = $recipients->merge(User::whereIn('id', $otherPartyIds)->get());
        }

        return $recipients->unique('id')->values();
    }

    private function ticket($subject): Ticket
    {
        return $subject->loadMissing('repliable')->repliable;
    }
}
