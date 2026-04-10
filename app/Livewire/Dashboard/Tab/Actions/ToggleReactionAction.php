<?php

namespace App\Livewire\Dashboard\Tab\Actions;

use App\Models\Reaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ToggleReactionAction
{
    public function execute(int $feedId, string $emoji): void
    {
        DB::transaction(function () use ($feedId, $emoji) {
            $reaction = Reaction::where('feed_id', $feedId)
                ->where('user_id', Auth::id())
                ->first();

            if ($reaction) {
                if ($reaction->emoji === $emoji) {
                    $reaction->delete();
                } else {
                    $reaction->update(['emoji' => $emoji]);
                }
            } else {
                Reaction::create([
                    'feed_id' => $feedId,
                    'user_id' => Auth::id(),
                    'emoji' => $emoji
                ]);
            }
        });
    }
}
