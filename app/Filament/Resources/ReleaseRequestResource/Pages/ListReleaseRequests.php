<?php

namespace App\Filament\Resources\ReleaseRequestResource\Pages;

use App\Enums\ReleaseRequestStatus;
use App\Filament\Resources\ReleaseRequestResource;
use App\Filament\Resources\ReleaseRequestResource\Schemas\ReleaseRequestFormPresenter;
use App\Models\ReleaseRequest;
use App\Traits\FilamentHeaderActions;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListReleaseRequests extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = ReleaseRequestResource::class;

    public function getTabs(): array
    {
        if (!(auth()->user()?->getPreference('show_list_tabs', true) ?? true)) {
            return [];
        }

        return [
            'all' => Tab::make('همه')
                ->icon('heroicon-o-list-bullet'),

            'open' => Tab::make(ReleaseRequestStatus::Open->getLabel())
                ->icon(ReleaseRequestStatus::Open->getIcon())
                ->badge(fn() => $this->getStats()->open_count ?: null)
                ->badgeColor(ReleaseRequestStatus::Open->getColor())
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', ReleaseRequestStatus::Open->value)),

            'in_review' => Tab::make(ReleaseRequestStatus::InReview->getLabel())
                ->icon(ReleaseRequestStatus::InReview->getIcon())
                ->badge(fn() => $this->getStats()->in_review_count ?: null)
                ->badgeColor(ReleaseRequestStatus::InReview->getColor())
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', ReleaseRequestStatus::InReview->value)),

            'resolved' => Tab::make(ReleaseRequestStatus::Resolved->getLabel())
                ->icon(ReleaseRequestStatus::Resolved->getIcon())
                ->badge(fn() => $this->getStats()->resolved_count ?: null)
                ->badgeColor(ReleaseRequestStatus::Resolved->getColor())
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', ReleaseRequestStatus::Resolved->value)),

            'rejected' => Tab::make(ReleaseRequestStatus::Rejected->getLabel())
                ->icon(ReleaseRequestStatus::Rejected->getIcon())
                ->badge(fn() => $this->getStats()->rejected_count ?: null)
                ->badgeColor(ReleaseRequestStatus::Rejected->getColor())
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', ReleaseRequestStatus::Rejected->value)),
        ];
    }

    private function getStats(): object
    {
        return once(fn() => ReleaseRequest::query()
            ->selectRaw("
                SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) AS open_count,
                SUM(CASE WHEN status = 'in_review' THEN 1 ELSE 0 END) AS in_review_count,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) AS resolved_count,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected_count
            ")
            ->first());
    }

    protected function listHeaderActions(): array
    {
        return [
            ReleaseRequestResource::setupGuideAction(),

            Action::make('submitRequest')
                ->label(__('resources/release_request/strings.action.submit'))
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->visible(fn() => $this->getResource()::canCreate())
                ->authorize('create', ReleaseRequest::class)
                ->modalHeading(__('resources/release_request/strings.action.submit_heading'))
                ->modalDescription(__('resources/release_request/strings.action.submit_description'))
                ->modalSubmitActionLabel(__('resources/release_request/strings.action.submit'))
                ->schema([
                    ReleaseRequestFormPresenter::type(),
                    ReleaseRequestFormPresenter::title(),
                    ReleaseRequestFormPresenter::body(),
                ])
                ->action(function (array $data): void {
                    ReleaseRequest::create([
                        'user_id' => (int) Auth::id(),
                        'type'    => $data['type'],
                        'title'   => $data['title'],
                        'body'    => $data['body'],
                        'status'  => ReleaseRequestStatus::Open->value,
                    ]);

                    Notification::make()
                        ->title(__('resources/release_request/strings.notifications.submitted'))
                        ->success()
                        ->send();
                }),

            CreateAction::make()
                ->icon('heroicon-o-sparkles')
                ->label(__('resources/release_request/strings.action.create')),
        ];
    }
}