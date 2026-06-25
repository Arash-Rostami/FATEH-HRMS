<?php

namespace App\Livewire\Dashboard\Tab\Actions;

use App\Models\Feed;
use App\Models\Poll;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VotePollAction
{
    public function execute(int $feedId, int $optionIndex): void
    {
        DB::transaction(function () use ($feedId, $optionIndex) {
            $feed = Feed::find($feedId);

            if (!$feed) return;

            $choices = $feed->pollChoices();

            if ($optionIndex < 0 || $optionIndex >= count($choices)) return;

            $multiple = ($feed->pollSettings()['mode'] ?? 'single') === 'multiple';

            if ($multiple) {
                $existing = Poll::query()
                    ->where('feed_id', $feedId)
                    ->where('user_id', Auth::id())
                    ->where('option_index', $optionIndex)
                    ->first();

                if ($existing) {
                    $existing->delete();
                    return;
                }

                Poll::create([
                    'feed_id'      => $feedId,
                    'user_id'      => Auth::id(),
                    'option_index' => $optionIndex,
                ]);

                return;
            }

            $rows = Poll::query()
                ->where('feed_id', $feedId)
                ->where('user_id', Auth::id())
                ->get();

            $current = $rows->isNotEmpty() ? (int) $rows->first()->option_index : null;

            Poll::query()
                ->where('feed_id', $feedId)
                ->where('user_id', Auth::id())
                ->delete();

            if ($current !== $optionIndex) {
                Poll::create([
                    'feed_id'      => $feedId,
                    'user_id'      => Auth::id(),
                    'option_index' => $optionIndex,
                ]);
            }
        });
    }
}