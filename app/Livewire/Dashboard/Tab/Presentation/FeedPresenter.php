<?php

namespace App\Livewire\Dashboard\Tab\Presentation;

use App\Filament\Resources\FeedResource\Enums\FeedCategory;
use App\Models\Comment;
use App\Models\Feed;
use Illuminate\Database\Eloquent\Model;

class FeedPresenter
{
    public function categoryValue(mixed $category): string
    {
        return (string) (($category?->value ?? $category) ?? '');
    }

    public function categoryEmoji(Feed $feed): string
    {
        return match ($this->categoryValue($feed->category)) {
            'General'          => '📢',
            'Event'            => '📅',
            'Birthday'         => '🎂',
            'Work Anniversary'  => '🏆',
            'Poll'             => '📊',
            default            => '💬',
        };
    }

    public function categoryLabels(): array
    {
        return [
            'General'          => '📢 عمومی',
            'Event'            => '📅 رویداد',
            'Birthday'         => '🎂 تولد',
            'Work Anniversary' => '🏆 سالگرد کاری',
            'Poll'             => '📊 نظرسنجی',
        ];
    }

    public function feedFlags(Feed $feed): array
    {
        $isPoll   = $this->categoryValue($feed->category) === FeedCategory::Poll->value;
        $settings = $isPoll
            ? $feed->pollSettings()
            : ['mode' => 'single', 'comments' => true, 'reactions' => true];

        return [
            'isPoll'        => $isPoll,
            'showComments'  => !$isPoll || $settings['comments'],
            'showReactions' => !$isPoll || $settings['reactions'],
            'settings'      => $settings,
        ];
    }

    public function pollData(Feed $feed): array
    {
        $results = $feed->pollResults();

        return [
            'isMultiple' => ($feed->pollSettings()['mode'] ?? 'single') === 'multiple',
            'options'    => $feed->pollChoices(),
            'total'      => $results['total'],
            'counts'     => $results['counts'],
            'userVotes'  => auth()->check()
                ? $feed->polls->where('user_id', auth()->id())->pluck('option_index')->map(fn ($v) => (int) $v)->all()
                : [],
        ];
    }

    public function optionState(int $index, array $pollData): array
    {
        $count = $pollData['counts'][$index] ?? 0;
        $total = $pollData['total'];

        return [
            'count'  => $count,
            'pct'    => $total > 0 ? (int) round(($count / $total) * 100) : 0,
            'isMine' => in_array($index, $pollData['userVotes'], true),
        ];
    }

    public function avatarUrl(?Model $user): string
    {
        return (string) ($user?->getProfileImageUrl() ?? $user?->getInitialsAvatarUrl() ?? asset('images/default-avatar.png'));
    }

    public function commentMeta(Comment $comment, ?int $editingCommentId): array
    {
        $user      = $comment?->user;
        $avatarUrl = $user?->getProfileImageUrl() ?? $user?->getInitialsAvatarUrl();

        return [
            'user'      => $user,
            'avatarUrl' => $avatarUrl ? (string) $avatarUrl : null,
            'hasPhoto'  => !empty($avatarUrl),
            'isOnline'  => $user?->isOnline() ?? false,
            'isOwner'   => auth()->id() === $comment->user_id,
            'isEditing' => $editingCommentId === ($comment?->id ?? null),
        ];
    }

    public function mediaGrid(array $media): array
    {
        $items  = array_values(array_filter($media ?? [], fn ($p) => !empty($p)));
        $images = array_values(array_filter($items, fn ($p) => !isVideo($p)));
        $count  = count($items);
        $cols   = $count > 1 ? 2 : 1;

        return [
            'items'  => $items,
            'images' => $images,
            'cols'   => $cols,
            'rows'   => (int) ceil($count / $cols),
        ];
    }
}