<?php

namespace App\Services\Menu\Indicators;

use App\Livewire\Dashboard\Correspondence\Forms\LetterForm;
use App\Livewire\Dashboard\Correspondence\Presentation\CorrespondencePresenter;
use App\Models\Correspondence;
use App\Services\Menu\Contracts\MenuBadge;
use Illuminate\Support\Collection;

class CorrespondenceBadge implements MenuBadge
{
    private const WAITING_MONTHS = 6;

    private const UNSEEN_DAYS = 30;

    private const SCAN_MONTHS = 12;

    private const PAGE = 40;

    public function getBody(): string
    {
        return 'نامه‌ای منتظر امضای شماست یا با شما به اشتراک گذاشته شده که هنوز آن را باز نکرده‌اید.';
    }

    public function getKey(): string
    {
        return 'correspondence-controller';
    }

    public function getTitle(): string
    {
        return 'مکاتبات نیازمند توجه';
    }

    public function isActive(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        if (Correspondence::query()
            ->where('awaiting_user_id', $user->id)
            ->where('status', Correspondence::STATUS_SHARED)
            ->exists()) {
            return true;
        }

        return $this->find($user->id)->isNotEmpty() || $this->incoming($user->id);
    }

    private function find(int $userId): Collection
    {
        $unseenSince = now()->subDays(self::UNSEEN_DAYS);
        $waitingSince = now()->subMonths(self::WAITING_MONTHS);
        $scanSince = now()->subMonths(self::SCAN_MONTHS);

        $hits = collect();

        for ($page = 0; $page < 2; $page++) {
            $rows = Correspondence::query()
                ->where('status', Correspondence::STATUS_SHARED)
                ->where('created_at', '>=', $scanSince)
                ->where(function ($query) use ($userId, $unseenSince, $waitingSince) {
                    $query->where(function ($parallel) use ($userId, $waitingSince) {
                        $parallel->where('route_mode', Correspondence::ROUTE_PARALLEL)
                            ->where('shared_at', '>=', $waitingSince)
                            ->whereJsonContains('parties', ['user_id' => $userId, 'role' => LetterForm::ROLE_APPROVER]);
                    })->orWhere(function ($unseen) use ($userId, $unseenSince) {
                        $unseen->where('shared_at', '>=', $unseenSince)
                            ->whereJsonContains('parties', ['user_id' => $userId]);
                    });
                })
                ->orderByDesc('shared_at')
                ->orderByDesc('id')
                ->offset($page * self::PAGE)
                ->limit(self::PAGE)
                ->get(['id', 'status', 'awaiting_user_id', 'route_mode', 'parties', 'shared_at', 'created_at']);

            $hits = $rows->filter(
                fn (Correspondence $letter): bool => CorrespondencePresenter::waitingForMe($letter, $userId) || $this->unseenForMe($letter, $userId)
            );

            if ($hits->isNotEmpty() || $rows->count() < self::PAGE) {
                break;
            }
        }

        return $hits;
    }

    private function unseenForMe(Correspondence $letter, int $userId): bool
    {
        if ($letter->status !== Correspondence::STATUS_SHARED || blank($letter->shared_at)) {
            return false;
        }

        return $this->unopened($letter, $userId);
    }

    private function incoming(int $userId): bool
    {
        return Correspondence::query()
            ->whereIn('status', [Correspondence::STATUS_RECEIVED, Correspondence::STATUS_ROUTED])
            ->where('updated_at', '>=', now()->subDays(self::UNSEEN_DAYS))
            ->whereJsonContains('parties', ['user_id' => $userId])
            ->orderByDesc('id')
            ->limit(self::PAGE)
            ->get(['id', 'status', 'parties'])
            ->contains(fn (Correspondence $letter): bool => $this->unopened($letter, $userId));
    }

    private function unopened(Correspondence $letter, int $userId): bool
    {
        foreach ($letter->parties ?? [] as $party) {
            if (is_array($party)
                && (int) ($party['user_id'] ?? 0) === $userId
                && blank($party['signed_at'] ?? null)
                && blank($party['opened_at'] ?? null)) {
                return true;
            }
        }

        return false;
    }
}
