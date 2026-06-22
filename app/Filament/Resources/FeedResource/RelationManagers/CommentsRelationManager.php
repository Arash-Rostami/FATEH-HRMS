<?php

namespace App\Filament\Resources\FeedResource\RelationManagers;

use App\Models\Comment;
use App\Traits\FilamentActions;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\TextColor;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CommentsRelationManager extends RelationManager
{
    use FilamentActions;

    protected static string $relationship = 'comments';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                RichEditor::make('content')
                    ->label(__('resources/feed/strings.fields.comment_content'))
                    ->required()
                    ->maxLength(5000)
                    ->columnSpanFull()
                    ->textColors([
                        'primary' => TextColor::make('Primary', '#3b82f6', darkColor: '#60a5fa'),
                        ...TextColor::getDefaults(),
                    ])
                    ->customTextColors()
                    ->toolbarButtons([
                        ['textColor', 'bold', 'italic', 'underline', 'strike', 'clearFormatting'],
                        ['blockquote', 'bulletList', 'orderedList'],
                        ['link'],
                        ['undo', 'redo'],
                    ])
                    ->extraInputAttributes(['class' => 'fi-prose', 'style' => 'min-height: 160px;']),
            ])->columnSpanFull(),
        ])
            ->validationMessages([
                'required' => __('resources/feed/strings.validation.content.required'),
                'max' => __('resources/feed/strings.validation.content.max')
            ]);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('resources/feed/strings.fields.comments_count');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Comment::query()
                    ->where('feed_id', $this->ownerRecord->id)
                    ->selectRaw('*, (CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END) as type_order')
                    ->with(['user', 'parent.user', 'replies', 'replies.user'])
                    ->withCount('replies')
            )
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('user.name')
                    ->label(__('resources/feed/strings.fields.user'))
                    ->description(fn(Comment $record): ?string => $record->parent_id ? "در پاسخ به: {$record->parent?->user?->name}" : null)
                    ->searchable(),

                TextColumn::make('content')
                    ->label(__('resources/feed/strings.fields.comment_content'))
                    ->html()
                    ->limit(50)
                    ->tooltip(fn($record) => strip_tags($record->content ?? ''))
                    ->searchable(),

                TextColumn::make('replies_count')
                    ->label(__('resources/feed/strings.fields.replies_count'))
                    ->badge()
                    ->color('info')
                    ->state(fn(Comment $record): ?int => $record->parent_id === null ? $record->replies_count : null)
                    ->width('1%'),

                TextColumn::make('created_at')
                    ->label(__('resources/feed/strings.fields.created_at'))
                    ->formatStateUsing(fn(?string $state): string => $state ? toJalali($state, 'm/d H:i') : '—')
                    ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
                    ->width('1%')
                    ->size('sm')
                    ->color('gray')
                    ->sortable(),
            ])
            ->groups([
                Group::make('type_order')
                    ->label(__('resources/feed/strings.fields.type'))
                    ->getTitleFromRecordUsing(fn($record) => $record->type_order !== 0
                        ? __('resources/feed/strings.fields.reply') . ' ← ' . strip_tags(mb_substr($record->parent?->content ?? '', 0, 50))
                        : __('resources/feed/strings.fields.comment')
                    )
                    ->collapsible(),
            ])
            ->defaultGroup('type_order')
            ->recordActions([
                Action::make('view')
                    ->hiddenLabel()
                    ->icon('heroicon-m-eye')
                    ->color('gray')
                    ->extraAttributes(['dir' => 'rtl'])
                    ->modalHeading('مشاهده متن')
                    ->modalContent(fn(Comment $record) => view('filament.resources.feed.comment-thread', ['getRecord' => fn() => $record]))
                    ->modalSubmitAction(false),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-newspaper')
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
