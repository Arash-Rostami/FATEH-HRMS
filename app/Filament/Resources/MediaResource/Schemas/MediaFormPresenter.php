<?php

namespace App\Filament\Resources\MediaResource\Schemas;

use App\Enums\MediaIcon;
use App\Filament\Resources\ReportResource\Schemas\ReportFormPresenter;
use App\Models\Media;
use App\Models\Report;
use App\Support\FileNamer;
use App\Traits\Filament\FilamentIconOptions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Symfony\Component\Mime\MimeTypes;

class MediaFormPresenter extends ReportFormPresenter
{
    use FilamentIconOptions;

    public static function kind(): Select
    {
        return Select::make('kind')
            ->label(__('resources/media/strings.fields.kind'))
            ->options(static::kindOptions())
            ->required()
            ->live()
            ->default('audio')
            ->disabled(fn(?Media $record): bool => $record?->kind === 'series' && $record->episodes()->exists())
            ->helperText(fn(?Media $record): string => $record?->kind === 'series' && $record->episodes()->exists()
                ? __('resources/media/strings.hints.kind_locked')
                : __('resources/media/strings.hints.kind'));
    }

    public static function episodeKind(): Select
    {
        return Select::make('kind')
            ->label(__('resources/media/strings.fields.kind'))
            ->options([
                'audio' => __('resources/media/strings.fields.kind_audio'),
                'video' => __('resources/media/strings.fields.kind_video'),
            ])
            ->required()
            ->live()
            ->default('audio')
            ->helperText(__('resources/media/strings.hints.episode_kind'));
    }

    private static function kindOptions(): array
    {
        return [
            'series' => __('resources/media/strings.fields.kind_series'),
            'audio' => __('resources/media/strings.fields.kind_audio'),
            'video' => __('resources/media/strings.fields.kind_video'),
        ];
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
            ->live()
            ->hidden(fn(Get $get): bool => $get('kind') === 'series')
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
            ->multiple()
            ->searchable()
            ->getSearchResultsUsing(fn(string $search): array => Report::query()
                ->select('id', 'title')
                ->where('title', 'like', '%' . $search . '%')
                ->orderBy('title')
                ->limit(50)
                ->pluck('title', 'id')
                ->all())
            ->getOptionLabelsUsing(fn(array $values): array => Report::query()
                ->select('id', 'title')
                ->whereIn('id', $values)
                ->pluck('title', 'id')
                ->all())
            ->nullable()
            ->helperText(__('resources/media/strings.hints.report_ids'));
    }

    public static function filePath(): FileUpload
    {
        return FileUpload::make('file_path')
            ->label(__('resources/media/strings.fields.file_path'))
            ->downloadable()
            ->openable()
            ->acceptedFileTypes(static::mediaMimes())
            ->maxSize(static::uploadLimitKb())
            ->disk('public')
            ->directory('media/files')
            ->getUploadedFileNameForStorageUsing(
                fn(TemporaryUploadedFile $file): string => FileNamer::stored('media', $file)
            )
            ->live()
            ->afterStateUpdated(function (mixed $state, Set $set): void {
                $duration = static::probedDuration($state instanceof TemporaryUploadedFile ? $state : null);

                if ($duration !== null) {
                    $set('duration_seconds', $duration);
                }
            })
            ->nullable()
            ->helperText(fn(): string => __('resources/media/strings.hints.file_path') . ' ' . __('resources/media/strings.hints.max_size', [
                'mb' => intdiv(static::uploadLimitKb(), 1024),
            ]));
    }

    public static function uploadLimitKb(): int
    {
        $server = (int) (min(ini_parse_quantity((string) ini_get('upload_max_filesize')), ini_parse_quantity((string) ini_get('post_max_size'))) / 1024);

        return $server > 0 ? min((int) config('file_upload_limits.video'), $server) : (int) config('file_upload_limits.video');
    }

    public static function probedDuration(?TemporaryUploadedFile $file): ?int
    {
        $path = $file?->getRealPath();

        return is_string($path) && $path !== '' ? Media::probeDuration($path) : null;
    }

    private static function mediaMimes(): array
    {
        $guesser = MimeTypes::getDefault();

        $mimes = [];
        foreach ((array) config('media.mimes', []) as $list) {
            foreach ((array) $list as $extension) {
                foreach ($guesser->getMimeTypes($extension) as $mime) {
                    $mimes[$mime] = true;
                }
            }
        }

        return array_keys($mimes);
    }

    public static function durationSeconds(): TextInput
    {
        return TextInput::make('duration_seconds')
            ->label(__('resources/media/strings.fields.duration_seconds'))
            ->numeric()
            ->minValue(1)
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
            ->acceptedFileTypes(['text/vtt', 'text/plain', 'application/octet-stream'])
            ->maxSize(config('file_upload_limits.document'))
            ->disk('public')
            ->directory('media/files')
            ->getUploadedFileNameForStorageUsing(
                fn(TemporaryUploadedFile $file): string => pathinfo(FileNamer::stored('media', $file), PATHINFO_FILENAME) . '.vtt'
            )
            ->rules(['extensions:vtt'])
            ->downloadable()
            ->openable()
            ->nullable();
    }
}
