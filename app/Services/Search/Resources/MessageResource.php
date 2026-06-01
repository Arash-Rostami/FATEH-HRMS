<?php

namespace App\Services\Search\Resources;

use App\Models\Message;
use App\Services\Search\Contracts\SearchResource;
use Illuminate\Database\Eloquent\Builder;

class MessageResource extends SearchResource
{
    protected string $type = 'message';
    protected string $group = 'پیام‌رسان داخلی';
    protected string $icon = 'perm_contact_calendar';
    protected string $model = Message::class;
    protected array $columns = ['body'];
    protected ?string $subtitleField = 'body';

    /** Open the conversation with the OTHER party (works from either side). */
    public function action($row): string
    {
        $other = (int) $row->sender_id === $this->me()
            ? (int) $row->recipient_id
            : (int) $row->sender_id;

        return $this->route('contact', $other);
    }

    protected function scope(Builder $query): void
    {
        $me = $this->me();

        $query->where(fn (Builder $q) => $q
            ->where('sender_id', $me)
            ->orWhere('recipient_id', $me)
        );
    }

    protected function titleFor($row): string
    {
        return superClean((string) $row->body, 40) ?: 'پیام';
    }
}
