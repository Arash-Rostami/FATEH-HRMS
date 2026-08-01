<?php

namespace App\Filament\Resources\SuggestionResource\Pages;

use App\Filament\Resources\SuggestionResource;
use App\Filament\Resources\SuggestionResource\Enums\ReviewFeedback;
use App\Livewire\Dashboard\Suggestion\Actions\MarkImplementationCompleteAction;
use App\Livewire\Dashboard\Suggestion\Actions\SubmitDecisionAction;
use App\Livewire\Dashboard\Suggestion\Actions\SubmitFeedbackAction;
use App\Livewire\Dashboard\Suggestion\Forms\DecisionForm;
use App\Livewire\Dashboard\Suggestion\Forms\FeedbackForm;
use App\Models\Department;
use App\Models\Suggestion;
use App\Support\SuggestionAccessPolicy;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Auth;

class ViewSuggestion extends ViewRecord
{
    protected static string $resource = SuggestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->feedbackHeaderAction(),
            $this->decisionHeaderAction(),
            $this->markCompleteHeaderAction(),
            EditAction::make()
                ->icon('heroicon-o-pencil')
                ->label(__('resources/general/strings.table.action_edit')),
        ];
    }

    protected function resolveRecord(int | string $key): Suggestion
    {
        return parent::resolveRecord($key)->loadMissing([
            'user.profile.department',
            'reviews.department',
            'reviews.user',
        ]);
    }

    private function decisionHeaderAction(): Action
    {
        return Action::make('decision')
            ->label(__('resources/suggestion/strings.actions.decision'))
            ->icon('heroicon-o-scale')
            ->color('warning')
            ->modalHeading(__('resources/suggestion/strings.actions.decision_heading'))
            ->modalDescription(__('resources/suggestion/strings.actions.decision_description'))
            ->visible(fn(): bool => SuggestionAccessPolicy::canDecide($this->getSuggestion()))
            ->schema([
                Radio::make('decision')
                    ->label(__('resources/suggestion/strings.decision.decision'))
                    ->options(__('resources/suggestion/strings.decision.options'))
                    ->required()
                    ->inline()
                    ->inlineLabel(false),

                Textarea::make('decisionComment')
                    ->label(__('resources/suggestion/strings.decision.comment'))
                    ->rows(3),

                Select::make('referralDepts')
                    ->label(__('resources/suggestion/strings.decision.referral_depts'))
                    ->options(fn(): array => Department::getCachedOptions()->toArray())
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->visible(fn(Get $get): bool => $get('decision') === 'accepted'),

                Textarea::make('referralActions')
                    ->label(__('resources/suggestion/strings.decision.referral_actions'))
                    ->rows(3)
                    ->visible(fn(Get $get): bool => $get('decision') === 'accepted'),
            ])
            ->action(function (array $data, SubmitDecisionAction $service): void {
                $form = new DecisionForm();
                $form->decision         = $data['decision'] ?? '';
                $form->decisionComment  = (string) ($data['decisionComment'] ?? '');
                $form->referralDepts    = (array) ($data['referralDepts'] ?? []);
                $form->referralActions  = (string) ($data['referralActions'] ?? '');

                $service->execute($form, $this->getSuggestion());

                Notification::make()
                    ->title(__('resources/suggestion/strings.notifications.decision_saved'))
                    ->success()
                    ->send();

                $this->refreshFormData(['stage', 'comments']);
            });
    }

    private function feedbackHeaderAction(): Action
    {
        return Action::make('feedback')
            ->label(__('resources/suggestion/strings.actions.feedback'))
            ->icon('heroicon-o-chat-bubble-bottom-center-text')
            ->color('success')
            ->modalHeading(__('resources/suggestion/strings.actions.feedback_heading'))
            ->modalDescription(__('resources/suggestion/strings.actions.feedback_description'))
            ->visible(fn(): bool => SuggestionAccessPolicy::canGiveFeedback($this->getSuggestion()))
            ->schema([
                Radio::make('feedback')
                    ->label(__('resources/suggestion/strings.feedback.label'))
                    ->options([
                        ReviewFeedback::Agree->value    => __('resources/suggestion/strings.feedback.agree'),
                        ReviewFeedback::Neutral->value  => __('resources/suggestion/strings.feedback.neutral'),
                        ReviewFeedback::Disagree->value => __('resources/suggestion/strings.feedback.disagree'),
                    ])
                    ->required()
                    ->inline()
                    ->inlineLabel(false),

                Textarea::make('comment')
                    ->label(__('resources/suggestion/strings.feedback.comment'))
                    ->rows(4),
            ])
            ->action(function (array $data, SubmitFeedbackAction $service): void {
                $form = new FeedbackForm();
                $form->feedback = $data['feedback'] ?? '';
                $form->comment  = (string) ($data['comment'] ?? '');

                $service->execute($form, $this->getSuggestion());

                Notification::make()
                    ->title(__('resources/suggestion/strings.notifications.feedback_saved'))
                    ->success()
                    ->send();

                $this->refreshFormData(['stage']);
            });
    }

    private function getSuggestion(): Suggestion
    {
        return $this->getRecord();
    }

    private function markCompleteHeaderAction(): Action
    {
        return Action::make('mark_complete')
            ->label(__('resources/suggestion/strings.actions.mark_complete'))
            ->icon('heroicon-o-check-badge')
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading(__('resources/suggestion/strings.actions.mark_complete_heading'))
            ->modalDescription(__('resources/suggestion/strings.actions.mark_complete_description'))
            ->visible(fn(): bool => SuggestionAccessPolicy::canMarkComplete($this->getSuggestion()))
            ->action(function (MarkImplementationCompleteAction $service): void {
                $service->execute($this->getSuggestion());

                Notification::make()
                    ->title(__('resources/suggestion/strings.notifications.completion_saved'))
                    ->success()
                    ->send();

                $this->refreshFormData(['stage']);
            });
    }
}
