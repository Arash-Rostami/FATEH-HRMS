<?php

namespace App\Console\Commands;

use App\Livewire\Dashboard\Channel\Actions\ForceDeleteChannelAction;
use App\Livewire\Dashboard\Channel\Actions\ForceDeleteChannelMessageAction;
use App\Models\Channel;
use App\Models\ChannelMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PruneChannels extends Command
{
    protected $signature = 'channel:prune';

    protected $description = 'Force-delete channel messages and channels soft-deleted more than 30 days ago.';

    public function handle(ForceDeleteChannelMessageAction $messageAction, ForceDeleteChannelAction $channelAction): int
    {
        $cutoff = Carbon::now()->subDays(30);

        $messageCount = 0;
        ChannelMessage::onlyTrashed()
            ->where('deleted_at', '<=', $cutoff)
            ->chunkById(200, function ($messages) use ($messageAction, &$messageCount) {
                foreach ($messages as $message) {
                    $messageAction->execute($message);
                    $messageCount++;
                }
            });

        $channelCount = 0;
        Channel::onlyTrashed()
            ->where('deleted_at', '<=', $cutoff)
            ->chunkById(200, function ($channels) use ($channelAction, &$channelCount) {
                foreach ($channels as $channel) {
                    $channelAction->execute($channel);
                    $channelCount++;
                }
            });

        $this->info("Pruned {$messageCount} channel messages and {$channelCount} channels.");

        return self::SUCCESS;
    }
}