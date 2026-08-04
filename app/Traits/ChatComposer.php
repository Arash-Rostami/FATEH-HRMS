<?php

namespace App\Traits;

use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Js;

trait ChatComposer
{
    public function updated(string $name): void
    {
        if ($name === 'composer.attachments') {
            $this->dispatch('attachments-updated')->self();
        }
    }

    public function syncAttachments(): void
    {
        unset($this->groupedMessages);
    }

    public function removeAttachment(int $index): void
    {
        $attachments = $this->composer->attachments;
        unset($attachments[$index]);
        $this->composer->attachments = array_values($attachments);
    }

    #[Computed]
    public function groupedMessages(): array
    {
        return collect($this->messages)
            ->groupBy(fn($m) => Carbon::parse($m['created_at'])->setTimezone(config('app.timezone'))->toDateString())
            ->map(fn($group) => $group->values()->all())
            ->all();
    }

    #[Computed]
    public function lastMessageId(): ?int
    {
        $id = collect($this->messages)->max('id');
        return $id === null ? null : (int) $id;
    }

    #[Js]
    public function cancelReply()
    {
        return <<<'JS'
            $wire.composer.replyToId = null
        JS;
    }
}
