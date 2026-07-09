<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use App\Models\User;
use App\Models\Channel;
use App\Models\Message;
use App\Livewire\Dashboard\Channel\Main as ChannelMain;
use App\Livewire\Dashboard\Contact\Main as ContactMain;
use App\Filament\Resources\ChannelResource\Pages\EditChannel;

class WorkflowExerciseCommand extends Command
{
    protected $signature = 'workflow:exercise';
    protected $description = 'Exercise channel + contact + admin workflows end-to-end and report failures';

    private int $fail = 0;

    public function handle(): int
    {
        $u = User::first();
        if (!$u) {
            $this->line('NO USER');
            return self::FAILURE;
        }
        Auth::login($u);
        $this->line("ACTING AS user_id={$u->id}");

        $owned = Channel::where('owner_id', $u->id)->latest('id')->first();
        $anyChannel = $owned ?? Channel::latest('id')->first();
        $channelId = $anyChannel?->id;

        $t = $this->step('channel.render', fn() => Livewire::test(ChannelMain::class));
        if ($t) {
            $this->step('channel.openCreate',   fn() => $t->call('openCreate'));
            $this->step('channel.closeCreate',  fn() => $t->call('closeCreate'));
            $this->step('channel.toggleBrowse', fn() => $t->call('toggleBrowse'));
            $this->step('channel.backToList',   fn() => $t->call('backToList'));
            if ($channelId) {
                $this->step('channel.selectChannel', fn() => $t->call('selectChannel', $channelId));
                $this->step('channel.refreshUnread', fn() => $t->call('refreshUnread'));
                $this->step('channel.loadOlder',     fn() => $t->call('loadOlder'));
                $this->step('channel.messageSearch', fn() => $t->set('messageSearch', 'test')->assertHasNoErrors());
                $mid = $anyChannel->messages()->latest('id')->value('id');
                if ($mid) $this->step('channel.focusMessage', fn() => $t->call('focusMessage', $mid));
                $this->step('channel.send-empty',  fn() => $t->set('composer.body', '')->call('send'));
                $this->step('channel.cancelReply', fn() => $t->call('cancelReply'));
                $this->step('channel.cancelEdit',  fn() => $t->call('cancelEdit'));
                if ($owned) {
                    $this->step('channel.openManageMembers', fn() => $t->call('openManageMembers', $owned->id));
                    $others = User::getCachedActiveOptions()->except($u->id)->keys()->take(2)->all();
                    $this->step('channel.saveManageMembers', fn() => $t->set('memberRecipientIds', array_map('intval', $others))->call('saveManageMembers'));
                    $this->step('channel.closeManageMembers', fn() => $t->set('isManageMembersOpen', false));
                }
            }
        }

        $peer = User::where('id', '!=', $u->id)->first();
        $c = $this->step('contact.render', fn() => Livewire::test(ContactMain::class));
        if ($c && $peer) {
            $this->step('contact.selectPeer', fn() => $c->call('selectContact', $peer->id));
            $cm = Message::where(fn($q) => $q->where('sender_id', $u->id)->where('recipient_id', $peer->id)
                        ->orWhere(fn($q2) => $q2->where('sender_id', $peer->id)->where('recipient_id', $u->id)))
                        ->latest('id')->value('id');
            if ($cm) $this->step('contact.focusMessage', fn() => $c->call('focusMessage', $cm));
        }

        if ($anyChannel) {
            $this->step('admin.EditChannel.render', fn() => Livewire::test(EditChannel::class, ['record' => $anyChannel->getRouteKey()]));
        }

        $this->line("TOTAL FAILURES: {$this->fail}");
        return $this->fail > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function step(string $label, callable $fn): mixed
    {
        try {
            $r = $fn();
            $this->line("OK   $label");
            return $r ?? null;
        } catch (\Throwable $e) {
            $this->fail++;
            $msg = trim(preg_replace('/\s+/', ' ', $e->getMessage()));
            $this->line("FAIL $label :: $msg");
            return null;
        }
    }
}