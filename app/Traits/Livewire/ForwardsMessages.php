<?php

namespace App\Traits\Livewire;

use App\Livewire\Dashboard\Messaging\Actions\ForwardMessageAction;
use App\Models\Channel;
use App\Models\ChannelMessage;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

trait ForwardsMessages
{
    public bool $isForwardOpen = false;
    public string $forwardDestinationType = 'contact';
    public ?int $forwardDestinationId = null;
    public string $forwardSearch = '';
    public string $forwardNote = '';

    #[Locked]
    public ?int $forwardSourceId = null;
    #[Locked]
    public array $forwardPreview = [];
    #[Locked]
    public int $forwardLimit = 30;
    #[Locked]
    public ?string $forwardResultUrl = null;

    abstract protected function forwardSourceType(): string;

    public function openForward(int $messageId, ForwardMessageAction $action): void
    {
        $source = $action->source($this->forwardSourceType(), $messageId);
        $this->reset('forwardDestinationType', 'forwardDestinationId', 'forwardSearch', 'forwardNote', 'forwardLimit', 'forwardResultUrl');
        $this->resetValidation(['forwardDestinationId', 'forwardNote', 'forwardMessage']);
        $this->forwardSourceId = $messageId;
        $this->forwardPreview = [
            'body' => ForwardMessageAction::parts((string) $source->body)['forwarded'] ?? $source->body,
            'files' => array_column($source->attachments ?? [], 'name'),
            'thread' => $source instanceof ChannelMessage ? (int) $source->channel_id
                : ((int) $source->sender_id === (int) auth()->id() ? (int) $source->recipient_id : (int) $source->sender_id),
        ];
        $this->isForwardOpen = true;
        unset($this->forwardCandidates);
    }

    public function setForwardDestinationType(string $type): void
    {
        if (!in_array($type, ['contact', 'channel'], true)) return;
        $this->forwardDestinationType = $type;
        $this->reset('forwardDestinationId', 'forwardSearch', 'forwardLimit');
        $this->resetValidation(['forwardDestinationId', 'forwardMessage']);
        unset($this->forwardCandidates);
    }

    public function updatedForwardSearch(): void
    {
        $this->forwardLimit = 30;
        $this->forwardDestinationId = null;
        unset($this->forwardCandidates);
    }

    public function loadMoreForwardDestinations(): void
    {
        $this->forwardLimit += 30;
        unset($this->forwardCandidates);
    }

    #[Computed]
    public function forwardCandidates(): array
    {
        if (!$this->isForwardOpen || $this->forwardResultUrl) return [];

        $userId = (int) auth()->id();
        $query = $this->forwardDestinationType === 'contact'
            ? User::visibleOnBoard()->where('id', '!=', $userId)
            : Channel::query()->writableBy($userId);

        return $query->select(['id', 'name'])
            ->when($this->forwardDestinationType === $this->forwardSourceType(), fn ($q) => $q->where('id', '!=', $this->forwardPreview['thread'] ?? 0))
            ->when(trim($this->forwardSearch) !== '', fn ($q) => $q->where('name', 'like', '%' . mb_substr(trim($this->forwardSearch), 0, 100) . '%'))
            ->orderBy('name')->orderBy('id')->take($this->forwardLimit + 1)->get()->toArray();
    }

    public function forwardMessage(ForwardMessageAction $action): void
    {
        if (!$this->isForwardOpen || !$this->forwardSourceId || $this->forwardResultUrl) return;

        $this->resetValidation(['forwardDestinationId', 'forwardNote', 'forwardMessage']);
        $this->validate([
            'forwardDestinationType' => 'required|in:contact,channel',
            'forwardDestinationId' => 'required|integer|min:1',
            'forwardNote' => 'nullable|string|max:500',
        ], [
            'forwardDestinationId.required' => 'مخاطب یا گروهی را برای بازارسال انتخاب کنید.',
            'forwardNote.max' => 'یادداشت نباید بیشتر از ۵۰۰ کاراکتر باشد.',
        ]);

        try {
            $message = $action->execute($this->forwardSourceType(), $this->forwardSourceId, $this->forwardDestinationType, $this->forwardDestinationId, $this->forwardNote);
            $this->forwardResultUrl = route($message instanceof Message ? 'contact' : 'channels', [
                'open' => $this->forwardDestinationId,
                'focus_msg' => $message->id,
            ]);
            $this->dispatch('message-forwarded')->self();
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->errors());
        } catch (ModelNotFoundException|HttpExceptionInterface $e) {
            $this->addError('forwardMessage', 'پیام یا مقصد دیگر در دسترس شما نیست؛ مقصد دیگری انتخاب کنید یا پنجره را دوباره باز کنید.');
        } catch (Throwable $e) {
            report($e);
            $this->addError('forwardMessage', 'بازارسال پیام انجام نشد. دوباره تلاش کنید.');
        }
    }
}
