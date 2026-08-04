<?php

namespace App\Services\Search\Resources;

use App\Models\ChannelMessage;
use App\Services\Search\Contracts\SearchResource;
use Illuminate\Database\Eloquent\Builder;

class ChannelMessageResource extends SearchResource
{
    protected string $type = 'channel-message';
    protected string $group = 'پیام‌های کانال';
    protected string $icon = 'forum';
    protected string $model = ChannelMessage::class;
    protected array $columns = ['body'];
    protected ?string $subtitleField = 'body';

    public function action($row): string
    {
        return 'url:' . route('channels', [
            'open' => (int) $row->channel_id,
            'focus_msg' => (int) $row->getKey(),
        ], false);
    }

    protected function scope(Builder $query): void
    {
        $me = $this->me();

        $query->whereHas('channel', function (Builder $q) use ($me) {
            $q->whereHas('members', function (Builder $q2) use ($me) {
                $q2->where('user_id', $me);
            });
        });
    }

    protected function titleFor($row): string
    {
        return superClean((string) $row->body, 40) ?: 'پیام';
    }
}