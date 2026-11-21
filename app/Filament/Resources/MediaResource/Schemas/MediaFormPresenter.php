<?php

namespace App\Filament\Resources\MediaResource\Schemas;

use App\Enums\MediaIcon;
use App\Filament\Resources\ReportResource\Schemas\ReportFormPresenter;
use App\Models\Media;
use App\Models\Report;
use App\Support\FileNamer;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class MediaFormPresenter extends ReportFormPresenter
{
    public static function kind(): Select
    {
        return Select::make('kind')
            ->label(__('resources/media/strings.fields.kind'))
            ->options([
                'series' => __('resources/media/strings.fields.kind_series'),
                'audio' => __('resources/media/strings.fields.kind_audio'),
                'video' => __('resources/media/strings.fields.kind_video'),
            ])
            ->required()
            ->default('audio')
            ->helperText(__('resources/media/strings.hints.kind'));
    }

    public static function topic(): TextInput
    {
        return TextInput::make('topic')
            ->label(__('resources/media/strings.fields.topic'))
            ->nullable()
            ->maxLength(160)
            ->helperText(__('resources/media/strings.hints.topic'));
    }

    public static function parentId(): Select
    {
        return Select::make('parent_id')
            ->label(__('resources/media/strings.fields.parent_id'))
            ->options(fn() => Media::query()
                ->select('id', 'title')
                ->whereNull('parent_id')
                ->where('kind', 'series')
                ->where('active', true)
                ->orderBy('title')
                ->pluck('title', 'id')
                ->all())
            ->searchable()
            ->nullable()
            ->helperText(__('resources/media/strings.hints.parent_id'));
    }

    public static function icon(): Select
    {
        return Select::make('icon')
            ->label(__('resources/media/strings.fields.icon'))
            ->options(fn() => static::curatedIconOptions(MediaIcon::class))
            ->native(false)
            ->allowHtml()
            ->getOptionLabelUsing(fn(string $value) => static::curatedIconOptionLabel($value, MediaIcon::class))
            ->default(MediaIcon::default()->value)
            ->searchable()
            ->nullable()
            ->helperText(__('resources/media/strings.hints.icon'));
    }

    public static function reportIds(): Select
    {
        return Select::make('report_ids')
            ->label(__('resources/media/strings.fields.report_ids'))
            ->options(fn() => Report::query()->select('id', 'title')->orderBy('title')->pluck('title', 'id')->all())
            ->multiple()
            ->searchable()
            ->preload()
            ->nullable()
            ->helperText(__('resources/media/strings.hints.report_ids'));
    }

    public static function filePath(): FileUpload
    {
        return FileUpload::make('file_path')
            ->label(__('resources/media/strings.fields.file_path'))
            ->downloadable()
            ->openable()
            ->acceptedFileTypes([
                'audio/mpeg',
                'audio/mp4',
                'audio/x-m4a',
                'audio/wav',
                'audio/x-wav',
                'audio/ogg',
                'video/mp4',
                'video/webm',
            ])
            ->maxSize(config('file_upload_limits.video'))
            ->disk('public')
            ->directory('media/files')
            ->getUploadedFileNameForStorageUsing(
                fn(TemporaryUploadedFile $file): string => FileNamer::stored('media', $file)
            )
            ->live()
            ->afterStateUpdated(function (?TemporaryUploadedFile $state, Set $set): void {
                $duration = $state ? Media::probeDuration($state->getRealPath()) : null;

                if ($duration !== null) {
                    $set('duration_seconds', $duration);
                }
            })
            ->nullable()
            ->helperText(__('resources/media/strings.hints.file_path'));
    }

    public static function durationSeconds(): TextInput
    {
        return TextInput::make('duration_seconds')
            ->label(__('resources/media/strings.fields.duration_seconds'))
            ->numeric()
            ->minValue(0)
            ->nullable()
            ->helperText(__('resources/media/strings.hints.duration_seconds'));
    }

    public static function transcriptPath(): FileUpload
    {
        return static::vttUpload('transcript_path')
            ->label(__('resources/media/strings.fields.transcript_path'))
            ->helperText(__('resources/media/strings.hints.transcript_path'));
    }

    public static function chaptersPath(): FileUpload
    {
        return static::vttUpload('chapters_path')
            ->label(__('resources/media/strings.fields.chapters_path'))
            ->helperText(__('resources/media/strings.hints.chapters_path'));
    }

    public static function coverImage(): FileUpload
    {
        return parent::coverImage()
            ->label(__('resources/media/strings.fields.cover_image'))
            ->directory('media/covers')
            ->getUploadedFileNameForStorageUsing(
                fn(TemporaryUploadedFile $file): string => FileNamer::stored('media', $file)
            )
            ->helperText(__('resources/media/strings.hints.cover_image'));
    }

    public static function active(): Toggle
    {
        return parent::active()
            ->label(__('resources/media/strings.fields.active'))
            ->helperText(__('resources/media/strings.hints.active'));
    }

    public static function pinned(): Toggle
    {
        return parent::pinned()
            ->label(__('resources/media/strings.fields.pinned'))
            ->helperText(__('resources/media/strings.hints.pinned'));
    }

    public static function title(): TextInput
    {
        return parent::title()
            ->label(__('resources/media/strings.fields.title'))
            ->helperText(__('resources/media/strings.hints.title'));
    }

    private static function vttUpload(string $name): FileUpload
    {
        return FileUpload::make($name)
            ->acceptedFileTypes(['text/vtt'])
            ->maxSize(config('file_upload_limits.document'))
            ->disk('public')
            ->directory('media/files')
            ->getUploadedFileNameForStorageUsing(
                fn(TemporaryUploadedFile $file): string => FileNamer::stored('media', $file)
            )
            ->downloadable()
            ->openable()
            ->nullable();
    }
}
