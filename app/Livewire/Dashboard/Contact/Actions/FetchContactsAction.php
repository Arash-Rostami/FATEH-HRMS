<?php

namespace App\Livewire\Dashboard\Contact\Actions;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class FetchContactsAction
{
    public function execute(int $viewerId, string $search = '', string $filter = 'all'): Collection
    {
        $lastMsgIdSub = Message::selectRaw('MAX(id)')
            ->withoutTrashed()
            ->where(fn($q) => $q
                ->where('sender_id', $viewerId)->whereColumn('recipient_id', 'users.id')
                ->orWhere('sender_id', 'users.id')->where('recipient_id', $viewerId)
            );

        $unreadSub = Message::selectRaw('COUNT(*)')
            ->withoutTrashed()
            ->where('recipient_id', $viewerId)
            ->whereColumn('sender_id', 'users.id')
            ->whereNull('read_at');

        return User::with(['profile.department'])
            ->active()
//            ->whereKeyNot($viewerId)
            ->select('users.*')
            ->addSelect(['last_message_id' => $lastMsgIdSub])
            ->addSelect(['unread_count' => $unreadSub])
            ->when(filled($search), fn($q) => $q
                ->where(fn($q) => $q
                    ->where('users.name', 'LIKE', "%{$search}%")
                    ->orWhereHas('profile', fn($p) => $p->where('position', 'LIKE', "%{$search}%"))
                )
            )
            ->when($filter === 'unread', fn($q) => $q->having('unread_count', '>', 0))
            ->orderByDesc('last_message_id')
            ->orderBy('users.name')
            ->get();
    }
}



