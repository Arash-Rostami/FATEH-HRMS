<?php

namespace App\Filament\Resources\LetterheadResource\Schemas;

use Closure;
use App\Models\Letterhead;
use App\Services\Correspondence\CorrespondenceFonts;
use App\Support\FileNamer;
use App\Traits\Filament\FilamentFormDivider;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class LetterheadFormPresenter
{
    use FilamentFormDivider;

    private const SENDER_BLOCK_ROWS = 10;
    private const SENDER_BLOCK_CHARS = 120;

    public static function configGroup(): Fieldset
    {
        return Fieldset::make(__('resources/correspondence/strings.admin.letterhead.groups.config'))
            ->schema([
                self::numberInput('config.header_height_mm', 0, 150),
                self::numberInput('config.footer_height_mm', 0, 100),
                self::numberInput('config.watermark_opacity', 0, 1, 0.01),
                self::choiceInput('config.page_number_format', 'page_number_format'),
                self::choiceInput('config.signature_position', 'signature_position'),
                self::choiceInput('config.stamp_position', 'stamp_position'),
                self::choiceInput('config.cc_position', 'cc_position'),
                self::choiceInput('config.table.border', 'table_border'),
                self::divider(),
                self::toggleInput('config.repeat_header'),
                self::toggleInput('config.repeat_footer'),
                self::toggleInput('config.table.repeat_head'),
            ])
            ->columns(3)
            ->columnSpanFull();
    }

    public static function fieldsBoxGroup(): Fieldset
    {
        return Fieldset::make(__('resources/correspondence/strings.admin.letterhead.groups.fields_box'))
            ->statePath('fields_box')
            ->schema([
                self::choiceInput('fields_box.side', 'side'),
                self::numberInput('fields_box.top_mm', 0, 200),
                self::numberInput('fields_box.offset_mm', 0, 200),
                self::numberInput('fields_box.font_size', 8, 24),
            ])
            ->dehydrateStateUsing(function (?array $state): ?array {
                $filled = array_filter((array) $state, fn ($value): bool => filled($value));

                return $filled === [] ? null : $filled;
            })
            ->columns(4)
            ->columnSpanFull();
    }

    public static function footerFrom(): Select
    {
        return self::borrowSelect('config.footer_from');
    }

    public static function footerImage(): FileUpload
    {
        return self::imageUpload('footer_image', __('resources/correspondence/strings.admin.letterhead.footer_image'))
            ->hidden(fn (Get $get): bool => filled($get('config.footer_from')));
    }

    public static function headerFrom(): Select
    {
        return self::borrowSelect('config.header_from');
    }

    public static function headerImage(): FileUpload
    {
        return self::imageUpload('header_image', __('resources/correspondence/strings.admin.letterhead.header_image'))
            ->hidden(fn (Get $get): bool => filled($get('config.header_from')));
    }

    public static function stampImage(): FileUpload
    {
        return self::imageUpload('stamp_image', __('resources/correspondence/strings.admin.letterhead.stamp_image'));
    }

    public static function kind(): Select
    {
        return Select::make('kind')
            ->label(__('resources/correspondence/strings.admin.letterhead.kind'))
            ->options(Letterhead::kindLabels())
            ->default(Letterhead::KIND_HEAD)
            ->native(false)
            ->live()
            ->afterStateUpdated(function (Set $set, ?string $state): void {
                $set('parent_id', null);

                if ($state === Letterhead::KIND_REGISTRY) {
                    $set('config.header_from', null);
                    $set('config.footer_from', null);
                }
            })
            ->required();
    }

    public static function lang(): Select
    {
        return Select::make('lang')
            ->label(__('resources/correspondence/strings.admin.letterhead.lang'))
            ->options(fn (): array => Arr::only(__('resources/correspondence/strings.admin.letterhead.langs'), (array) config('ai.correspondence.languages')))
            ->default('fa')
            ->native(false)
            ->required();
    }

    public static function marginsGroup(): Fieldset
    {
        return Fieldset::make(__('resources/correspondence/strings.admin.letterhead.groups.margins'))
            ->schema([
                self::numberInput('margins.top', 0, 15, 0.01),
                self::numberInput('margins.right', 0, 15, 0.01),
                self::numberInput('margins.bottom', 0, 15, 0.01),
                self::numberInput('margins.left', 0, 15, 0.01),
            ])
            ->columns(4)
            ->columnSpanFull();
    }

    public static function name(): TextInput
    {
        return TextInput::make('name')
            ->label(__('resources/correspondence/strings.admin.letterhead.name'))
            ->required()
            ->maxLength(120);
    }

    public static function pageNumbers(): Toggle
    {
        return Toggle::make('page_numbers')
            ->label(__('resources/correspondence/strings.admin.letterhead.page_numbers'))
            ->default(false)
            ->helperText(__('resources/correspondence/strings.admin.letterhead.page_numbers_hint'));
    }

    public static function parentId(): Select
    {
        return Select::make('parent_id')
            ->label(__('resources/correspondence/strings.admin.letterhead.parent'))
            ->options(fn (Get $get, ?Letterhead $record): array => Letterhead::query()
                ->where('kind', $get('kind') ?? Letterhead::KIND_HEAD)
                ->whereNull('parent_id')
                ->when($record?->exists, fn ($query) => $query->whereKeyNot($record->getKey()))
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all())
            ->searchable()
            ->nullable()
            ->live()
            ->helperText(__('resources/correspondence/strings.admin.letterhead.parent_hint'));
    }

    public static function pattern(): TextInput
    {
        return TextInput::make('pattern')
            ->label(__('resources/correspondence/strings.admin.letterhead.pattern'))
            ->maxLength(120)
            ->live(onBlur: true)
            ->regex('/\{SEQ(:[1-9]\d?)?\}/')
            ->notRegex('/\{(?!(PREFIX|DEPT|TYPE|YYYY|YY|MM|SEQ(:[1-9]\d?)?)\})/')
            ->required()
            ->visible(fn (Get $get): bool => blank($get('parent_id')))
            ->helperText(__('resources/correspondence/strings.admin.letterhead.pattern_hint'));
    }

    public static function patternPreview(): Text
    {
        return Text::make(fn (Get $get, ?Letterhead $record): string => ($record ?? new Letterhead)
            ->replicate()
            ->fill(['pattern' => trim((string) $get('pattern')), 'parent_id' => null])
            ->preview())
            ->extraAttributes(['dir' => 'ltr'])
            ->visible(fn (Get $get): bool => blank($get('parent_id')) && preg_match('/\{SEQ(:[1-9]\d?)?\}/', (string) $get('pattern')) === 1)
            ->columnSpanFull();
    }

    public static function prefix(): TextInput
    {
        return TextInput::make('config.prefix')
            ->label(self::label('config.prefix'))
            ->helperText(__('resources/correspondence/strings.admin.letterhead.prefix_hint'))
            ->maxLength(20)
            ->regex('/^[A-Za-z0-9_\/-]*$/')
            ->nullable()
            ->default(self::modelDefault('config.prefix'))
            ->visible(fn (Get $get): bool => blank($get('parent_id')))
            ->extraAttributes(['dir' => 'ltr']);
    }

    public static function senderBlock(): KeyValue
    {
        return KeyValue::make('sender_block')
            ->label(__('resources/correspondence/strings.admin.letterhead.sender_block'))
            ->keyLabel(__('resources/correspondence/strings.admin.letterhead.key'))
            ->valueLabel(__('resources/correspondence/strings.admin.letterhead.value'))
            ->helperText(__('resources/correspondence/strings.admin.letterhead.sender_block_hint'))
            ->addActionLabel(__('resources/correspondence/strings.admin.letterhead.add_row'))
            ->rules([
                'max:' . self::SENDER_BLOCK_ROWS,
                fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                    $tooLong = collect((array) $value)->contains(
                        fn ($text, $key): bool => mb_strlen((string) $key) > self::SENDER_BLOCK_CHARS
                            || (is_scalar($text) && mb_strlen((string) $text) > self::SENDER_BLOCK_CHARS)
                    );

                    $tooLong && $fail('validation.max.string')->translate(['max' => self::SENDER_BLOCK_CHARS]);
                },
            ])->columnSpanFull();
    }

    public static function signLayout(): Select
    {
        return Select::make('sign_layout')
            ->label(__('resources/correspondence/strings.admin.letterhead.sign_layout'))
            ->options(fn (): array => __('resources/correspondence/strings.admin.letterhead.options.sign_layout'))
            ->default(2)
            ->native(false)
            ->hidden()
            ->required();
    }

    public static function size(): Select
    {
        return Select::make('size')
            ->label(__('resources/correspondence/strings.admin.letterhead.size'))
            ->options([
                Letterhead::SIZE_A4 => __('resources/correspondence/strings.letterhead.size.a4'),
                Letterhead::SIZE_A5 => __('resources/correspondence/strings.letterhead.size.a5'),
                Letterhead::SIZE_FIT => __('resources/correspondence/strings.letterhead.size.fit'),
            ])
            ->default(Letterhead::SIZE_A4)
            ->native(false)
            ->live()
            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('margins', Letterhead::defaultMargins((string) $state)))
            ->required();
    }

    public static function startAt(): TextInput
    {
        return TextInput::make('start_at')
            ->label(__('resources/correspondence/strings.admin.letterhead.start_at'))
            ->integer()
            ->minValue(1)
            ->maxValue(4294967295)
            ->default(1)
            ->required()
            ->visible(fn (Get $get): bool => blank($get('parent_id')))
            ->suffixAction(fn (TextInput $component): Action => Action::make('continueFromLast')
                ->icon('heroicon-o-arrow-path')
                ->tooltip(__('resources/correspondence/strings.admin.letterhead.continue_from_last'))
                ->hidden(! $component->getRecord()?->exists)
                ->action(function (Set $set) use ($component): void {
                    $last = $component->getRecord()?->lastNumber();

                    if ($last !== null) {
                        $set('start_at', $last + 1);
                    }
                }))
            ->helperText(__('resources/correspondence/strings.admin.letterhead.start_at_hint'));
    }

    public static function styleGroup(): Fieldset
    {
        return Fieldset::make(__('resources/correspondence/strings.admin.letterhead.groups.style'))
            ->schema([
                self::fontSelect('style.font', 'fa'),
                self::numberInput('style.size', 8, 24),
                self::numberInput('style.subject_size', 8, 24),
                self::numberInput('style.copy_size', 8, 24),
                self::fontSelect('style.latin_font', 'latin'),
                self::numberInput('style.latin_sizes.0', 8, 24),
                self::numberInput('style.latin_sizes.1', 8, 24),
                self::numberInput('style.latin_sizes.2', 8, 24),
                self::numberInput('style.line_height', 1, 3, 0.05),
                self::numberInput('style.indent', 0, 30, 0.5),
            ])
            ->columns(4)
            ->columnSpanFull();
    }

    public static function watermark(): TextInput
    {
        return TextInput::make('watermark')
            ->label(__('resources/correspondence/strings.admin.letterhead.watermark'))
            ->maxLength(120)
            ->nullable()
            ->helperText(__('resources/correspondence/strings.admin.letterhead.watermark_hint'));
    }

    private static function imageUpload(string $name, string $label): FileUpload
    {
        return FileUpload::make($name)
            ->label($label)
            ->image()
            ->disk('public')
            ->directory('letterheads')
            ->maxSize(config('ai.correspondence.upload.image_kb'))
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->rule(Rule::dimensions()->maxWidth(config('ai.correspondence.upload.image_max_width')))
            ->getUploadedFileNameForStorageUsing(
                fn (TemporaryUploadedFile $file): string => FileNamer::stored('letterhead', $file)
            )
            ->nullable()
            ->helperText(__('resources/correspondence/strings.admin.letterhead.image_hint'));
    }

    private static function borrowSelect(string $path): Select
    {
        return Select::make($path)
            ->label(self::label($path))
            ->options(fn (?Letterhead $record): array => self::borrowable($record?->exists ? $record->getKey() : null))
            ->native(false)
            ->searchable()
            ->nullable()
            ->live()
            ->helperText(__('resources/correspondence/strings.admin.letterhead.borrow_hint'));
    }

    private static function borrowable(?int $exceptId): array
    {
        return once(fn (): array => Letterhead::query()
            ->select(['id', 'name', 'config'])
            ->where('active', true)
            ->where('kind', Letterhead::KIND_HEAD)
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->orderBy('name')
            ->get()
            ->filter(fn (Letterhead $letterhead): bool => blank($letterhead->config['header_from'] ?? null) && blank($letterhead->config['footer_from'] ?? null))
            ->pluck('name', 'id')
            ->all());
    }

    private static function choiceInput(string $path, string $options): Select
    {
        return Select::make(self::statePathFor($path))
            ->label(self::label($path))
            ->options(fn (): array => __("resources/correspondence/strings.admin.letterhead.options.{$options}"))
            ->default(self::modelDefault($path))
            ->required(self::modelDefault($path) !== null)
            ->native(false);
    }

    private static function statePathFor(string $path): string
    {
        return str_starts_with($path, 'fields_box.') ? substr($path, strlen('fields_box.')) : $path;
    }

    private static function label(string $path): string
    {
        return __("resources/correspondence/strings.admin.letterhead.fields.{$path}");
    }

    private static function modelDefault(string $path): mixed
    {
        [$group, $key] = explode('.', $path, 2);

        return Arr::get(match ($group) {
            'style' => Letterhead::defaultStyle(),
            'config' => Letterhead::defaultConfig(),
            'margins' => Letterhead::defaultMargins(Letterhead::SIZE_A4),
            default => [],
        }, $key);
    }

    private static function numberInput(string $path, int|float $min, int|float $max, int|float $step = 1): TextInput
    {
        return TextInput::make(self::statePathFor($path))
            ->label(self::label($path))
            ->numeric()
            ->minValue($min)
            ->maxValue($max)
            ->step($step)
            ->required(self::modelDefault($path) !== null)
            ->default(self::modelDefault($path))
            ->dehydrateStateUsing(fn ($state): int|float|null => is_numeric($state) ? $state + 0 : null);
    }

    private static function fontSelect(string $path, string $script): Select
    {
        return Select::make($path)
            ->label(self::label($path))
            ->options(fn (): array => CorrespondenceFonts::options($script))
            ->default(CorrespondenceFonts::pick(self::modelDefault($path), $script))
            ->native(false)
            ->required()
            ->helperText(__('resources/correspondence/strings.admin.letterhead.font_hint'));
    }

    private static function toggleInput(string $path): Toggle
    {
        $default = self::modelDefault($path);

        return Toggle::make($path)
            ->label(self::label($path))
            ->default($default)
            ->dehydrateStateUsing(fn ($state): bool => (bool) ($state ?? $default));
    }
}
