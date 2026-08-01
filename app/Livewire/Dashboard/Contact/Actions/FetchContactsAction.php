<?php

namespace App\Livewire\Dashboard\Contact\Actions;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FetchContactsAction
{
    public function execute(int $viewerId, string $search = '', string $filter = 'all'): Collection
    {
        $sent = DB::table('messages')
            ->selectRaw('recipient_id as contact_id, MAX(id) as max_id')
            ->where('sender_id', $viewerId)
            ->whereNull('deleted_at')
            ->groupBy('contact_id');

        $received = DB::table('messages')
            ->selectRaw('sender_id as contact_id, MAX(id) as max_id')
            ->where('recipient_id', $viewerId)
            ->whereNull('deleted_at')
            ->groupBy('contact_id');

        $lastMsgSub = DB::query()
            ->fromSub($sent->unionAll($received), 't')
            ->selectRaw('contact_id, MAX(max_id) as last_message_id')
            ->groupBy('contact_id');

        $unreadSub = DB::table('messages')
            ->selectRaw('sender_id as contact_id, COUNT(id) as unread_count')
            ->where('recipient_id', $viewerId)
            ->whereNull('deleted_at')
            ->whereNull('read_at')
            ->groupBy('contact_id');

        return User::with(['profile.department', 'profile.details'])
            ->active()
            ->select('users.*', 'lm.last_message_id', DB::raw('COALESCE(uc.unread_count, 0) as unread_count'))
            ->leftJoinSub($lastMsgSub, 'lm', 'users.id', '=', 'lm.contact_id')
            ->leftJoinSub($unreadSub, 'uc', 'users.id', '=', 'uc.contact_id')
            ->when(filled($search), fn($q) => $q
                ->where(fn($q) => $q
                    ->where('users.name', 'LIKE', "%{$search}%")
                    ->orWhereHas('profile', fn($p) => $p->where('position', 'LIKE', "%{$search}%"))
                )
            )
            ->when($filter === 'unread', fn($q) => $q->where('uc.unread_count', '>', 0))
            ->orderByDesc('lm.last_message_id')
            ->orderBy('users.name')
            ->get();
    }
}