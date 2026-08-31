<?php

namespace App\Filament\Resources\ProfileResource\Schemas;

use App\Enums\ProfileDetailGroup;
use App\Enums\SkillRequestStatus;
use App\Enums\SkillTier;
use App\Filament\Resources\ProfileResource\Enums\Degree;
use App\Filament\Resources\ProfileResource\Enums\EmploymentStatus;
use App\Filament\Resources\ProfileResource\Enums\EmploymentType;
use App\Filament\Resources\ProfileResource\Enums\Gender;
use App\Filament\Resources\ProfileResource\Enums\MaritalStatus;
use App\Filament\Resources\ProfileResource\Enums\Position;
use App\Filament\Resources\ProfileResource\Enums\WorkExperience;
use Illuminate\Database\Eloquent\Model;
use App\Models\Profile;
use Filament\Infolists\Components\ColorEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;

class ProfileInfolistPresenter
{

    public static function skills(): RepeatableEntry
    {
        return RepeatableEntry::make('skills')
            ->label(__('resources/profile/strings.infolist.section_skills'))
            ->schema([
                TextEntry::make('skill_name')
                    ->label(__('resources/skill/strings.fields.skill'))
                    ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
                    ->weight('bold'),
                TextEntry::make('status')
                    ->label(__('resources/skill/strings.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn(SkillRequestStatus $state): string => $state->label())
                    ->color(fn(SkillRequestStatus $state): string => $state->color()),
                TextEntry::make('tier')
                    ->label(__('resources/skill/strings.fields.tier'))
                    ->badge()
                    ->formatStateUsing(fn(?SkillTier $state): string => $state?->label() ?? '-')
                    ->color(fn(?SkillTier $state): string => $state?->color() ?? 'gray'),
                TextEntry::make('endorsements_count')
                    ->label(__('resources/skill/strings.fields.endorsements_count')),
                TextEntry::make('last_used_at')
                    ->label(__('resources/skill/strings.fields.last_used_at'))
                    ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
                    ->placeholder('-')
                    ->color('gray'),
                IconEntry::make('is_mentoring')
                    ->label(__('resources/skill/strings.fields.is_mentoring'))
                    ->boolean(),
                IconEntry::make('is_private')
                    ->label(__('resources/skill/strings.fields.is_private'))
                    ->boolean(),
            ])
            ->columns(3)
            ->getStateUsing(fn (Profile $record): array => static::skillsState($record))
            ->placeholder(__('resources/profile/strings.form.no_skills'))
            ->columnSpanFull();
    }

    public static function skillsState(Profile $record): array
    {
        return ($record->user?->skillUsers()->with('skill')->latest('id')->get() ?? collect())
            ->map(fn ($skillUser): array => [
                'skill_name' => $skillUser->skill?->name ?? $skillUser->requested_name ?? '-',
                'status' => $skillUser->status,
                'tier' => $skillUser->status === SkillRequestStatus::Approved ? $skillUser->stateTier() : null,
                'endorsements_count' => $skillUser->endorsementLabel(),
                'last_used_at' => $skillUser->last_used_at,
                'is_mentoring' => $skillUser->is_mentoring,
                'is_private' => $skillUser->is_private,
            ])
            ->values()
            ->toArray();
    }

    public static function aboutMe(): RepeatableEntry
    {
        return RepeatableEntry::make('about_me')
            ->label(__('resources/profile/strings.infolist.about_me'))
            ->schema([
                TextEntry::make('key')
                    ->label(__('resources/profile/strings.infolist.about_me_key'))
                    ->weight('medium'),
                TextEntry::make('value')
                    ->label(__('resources/profile/strings.infolist.about_me_value'))
                    ->extraAttributes(['dir' => 'auto', 'style' => 'white-space: pre-wrap; unicode-bidi: isolate;'])
                    ->placeholder('-'),
            ])
            ->columns(2)
            ->getStateUsing(fn($record): array => collect($record->about_me ?? [])
                ->map(fn($v, $k) => ['key' => $k, 'value' => is_array($v) ? collect($v)->implode(' / ') : (string)$v])
                ->values()
                ->toArray()
            )
            ->columnSpanFull();
    }

    public static function details(): RepeatableEntry
    {
        return RepeatableEntry::make('details')
            ->label(__('resources/profile/strings.infolist.details'))
            ->schema([
                TextEntry::make('section')
                    ->label(__('resources/profile/strings.infolist.detail_section'))
                    ->badge()
                    ->formatStateUsing(fn($state): string => ProfileDetailGroup::tryFrom($state)?->getLabel() ?? $state)
                    ->color(fn($state): string|array => ProfileDetailGroup::tryFrom($state)?->getColor() ?? 'gray'),
                TextEntry::make('label')
                    ->label(__('resources/profile/strings.infolist.detail_key'))
                    ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
                    ->weight('medium'),
                TextEntry::make('display_value')
                    ->label(__('resources/profile/strings.infolist.detail_value'))
                    ->extraAttributes(['dir' => 'auto', 'style' => 'white-space: pre-wrap; unicode-bidi: isolate;'])
                    ->placeholder('-'),
            ])
            ->columns(3)
            ->columnSpanFull();
    }

    public static function accessibility(): TextEntry
    {
        return TextEntry::make('accessibility')
            ->label(__('resources/profile/strings.infolist.accessibility'))
            ->placeholder('-')
            ->extraAttributes(['dir' => 'auto', 'style' => 'white-space: pre-wrap; unicode-bidi: isolate;'])
            ->columnSpanFull();
    }

    public static function address(): TextEntry
    {
        return TextEntry::make('address')
            ->label(__('resources/profile/strings.infolist.address'))
            ->placeholder('-')
            ->extraAttributes(['dir' => 'auto', 'style' => 'white-space: pre-wrap; unicode-bidi: isolate;'])
            ->columnSpanFull();
    }

    public static function age(): TextEntry
    {
        return TextEntry::make('birthdate')
            ->label(__('resources/profile/strings.infolist.age'))
            ->getStateUsing(fn($record): string => $record->age() ? $record->age() . ' سال' : '-')
            ->placeholder('-');
    }

    public static function attachments(): RepeatableEntry
    {
        return RepeatableEntry::make('attachments')
            ->label(__('resources/profile/strings.infolist.attachments'))
            ->schema([
                TextEntry::make('key')
                    ->label(__('resources/profile/strings.infolist.attachment_name'))
                    ->weight('medium')
                    ->placeholder('-'),

                TextEntry::make('category')
                    ->label(__('resources/profile/strings.infolist.attachment_type'))
                    ->badge()
                    ->color('gray')
                    ->placeholder('-'),

                TextEntry::make('path')
                    ->hiddenLabel()
                    ->formatStateUsing(fn($state) => 'مشاهده فایل')
                    ->url(fn($state) => $state ? asset('storage/' . $state) : null)
                    ->openUrlInNewTab()
                    ->color('primary')
                    ->placeholder('-'),
            ])
            ->columns(2)
            ->columnSpanFull();
    }

    public static function birthdate(): TextEntry
    {
        return TextEntry::make('birthdate')
            ->label(__('resources/profile/strings.infolist.birthdate'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->placeholder('-');
    }

    public static function cellphone(): TextEntry
    {
        return TextEntry::make('cellphone')
            ->label(__('resources/profile/strings.infolist.cellphone'))
            ->copyable()
            ->icon('heroicon-o-phone')
            ->placeholder('-');
    }

    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/profile/strings.infolist.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->color('gray');
    }

    public static function degree(): TextEntry
    {
        return TextEntry::make('degree')
            ->label(__('resources/profile/strings.infolist.degree'))
            ->badge()
            ->formatStateUsing(fn(string $state): string => Degree::tryFrom($state)?->getLabel() ?? $state)
            ->color(fn(string $state): string => Degree::tryFrom($state)?->getColor() ?? 'gray');
    }

    public static function department(): TextEntry
    {
        return TextEntry::make('department.name')
            ->label(__('resources/profile/strings.infolist.department'))
            ->placeholder('-')
            ->formatStateUsing(fn(?Model $record): string => $record?->department?->displayLabel() ?? '-')
            ->tooltip(fn(?Model $record): string => $record?->department?->tooltipLabel() ?? '-');
    }

    public static function unit(): TextEntry
    {
        return TextEntry::make('unit')
            ->label(__('resources/profile/strings.infolist.unit'))
            ->placeholder('-')
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
            ->visible(fn(?Model $record): bool => filled($record?->detailsMap()->get('unit')))
            ->formatStateUsing(fn(?Model $record): string => (string) $record?->detailsMap()->get('unit'));
    }

    public static function section(): TextEntry
    {
        return TextEntry::make('section')
            ->label(__('resources/profile/strings.infolist.section'))
            ->placeholder('-')
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
            ->visible(fn(?Model $record): bool => filled($record?->detailsMap()->get('section')))
            ->formatStateUsing(fn(?Model $record): string => (string) $record?->detailsMap()->get('section'));
    }

    public static function emergencyPhone(): TextEntry
    {
        return TextEntry::make('emergency_phone')
            ->label(__('resources/profile/strings.infolist.emergency_phone'))
            ->icon('heroicon-o-phone')
            ->placeholder('-');
    }

    public static function emergencyRelationship(): TextEntry
    {
        return TextEntry::make('emergency_relationship')
            ->label(__('resources/profile/strings.infolist.emergency_relationship'))
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
            ->placeholder('-');
    }

    public static function employmentStatus(): TextEntry
    {
        return TextEntry::make('employment_status')
            ->label(__('resources/profile/strings.infolist.employment_status'))
            ->badge()
            ->formatStateUsing(fn(string $state): string => EmploymentStatus::tryFrom($state)?->getLabel() ?? $state)
            ->color(fn(string $state): string => EmploymentStatus::tryFrom($state)?->getColor() ?? 'gray')
            ->icon(fn(string $state): string => EmploymentStatus::tryFrom($state)?->getIcon() ?? '');
    }

    public static function employmentType(): TextEntry
    {
        return TextEntry::make('employment_type')
            ->label(__('resources/profile/strings.infolist.employment_type'))
            ->badge()
            ->formatStateUsing(fn(string $state): string => EmploymentType::tryFrom($state)?->getLabel() ?? $state)
            ->color(fn(string $state): string => EmploymentType::tryFrom($state)?->getColor() ?? 'gray')
            ->icon(fn(string $state): string => EmploymentType::tryFrom($state)?->getIcon() ?? '');
    }

    public static function endDate(): TextEntry
    {
        return TextEntry::make('end_date')
            ->label(__('resources/profile/strings.infolist.end_date'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->placeholder('-');
    }

    public static function favoriteColors(): RepeatableEntry
    {
        return RepeatableEntry::make('favorite_colors')
            ->label(__('resources/profile/strings.infolist.favorite_colors'))
            ->schema([
                ColorEntry::make('color')
                    ->label(__('resources/profile/strings.infolist.favorite_colors')),
            ])
            ->getStateUsing(fn($record): array => collect($record->favorite_colors ?? [])
                ->map(fn($v) => ['color' => $v])
                ->values()
                ->toArray()
            )
            ->columnSpanFull();
    }

    public static function field(): TextEntry
    {
        return TextEntry::make('field')
            ->label(__('resources/profile/strings.infolist.field'))
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
            ->placeholder('-');
    }

    public static function gender(): TextEntry
    {
        return TextEntry::make('gender')
            ->label(__('resources/profile/strings.infolist.gender'))
            ->badge()
            ->formatStateUsing(fn(string $state): string => Gender::tryFrom($state)?->getLabel() ?? $state)
            ->color(fn(string $state): string => Gender::tryFrom($state)?->getColor() ?? 'gray');
    }

    public static function id(): TextEntry
    {
        return TextEntry::make('id')
            ->label(__('resources/profile/strings.infolist.id'))
            ->color('gray');
    }

    public static function idBookletNumber(): TextEntry
    {
        return TextEntry::make('id_booklet_number')
            ->label(__('resources/profile/strings.infolist.id_booklet_number'))
            ->placeholder('-');
    }

    public static function idCardNumber(): TextEntry
    {
        return TextEntry::make('id_card_number')
            ->label(__('resources/profile/strings.infolist.id_card_number'))
            ->placeholder('-');
    }

    public static function image(): ImageEntry
    {
        return ImageEntry::make('profile_image')
            ->label(__('resources/profile/strings.infolist.image'))
            ->getStateUsing(fn(Profile $record): ?string => $record->getImageUrl())
            ->defaultImageUrl(fn(Profile $record): string => $record->getInitialsAvatarUrl())
            ->circular()
            ->imageHeight(120)
            ->columnSpanFull();
    }

    public static function insurance(): TextEntry
    {
        return TextEntry::make('insurance')
            ->label(__('resources/profile/strings.infolist.insurance'))
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
            ->placeholder('-');
    }

    public static function interests(): TextEntry
    {
        return TextEntry::make('interests')
            ->label(__('resources/profile/strings.infolist.interests'))
            ->placeholder('-')
            ->extraAttributes(['dir' => 'auto', 'style' => 'white-space: pre-wrap; unicode-bidi: isolate;'])
            ->columnSpanFull();
    }

    public static function landline(): TextEntry
    {
        return TextEntry::make('landline')
            ->label(__('resources/profile/strings.infolist.landline'))
            ->icon('heroicon-o-phone')
            ->placeholder('-');
    }

    public static function licensePlate(): TextEntry
    {
        return TextEntry::make('license_plate')
            ->label(__('resources/profile/strings.infolist.license_plate'))
            ->placeholder('-');
    }

    public static function maritalStatus(): TextEntry
    {
        return TextEntry::make('marital_status')
            ->label(__('resources/profile/strings.infolist.marital_status'))
            ->badge()
            ->formatStateUsing(fn(string $state): string => MaritalStatus::tryFrom($state)?->getLabel() ?? $state)
            ->color(fn(string $state): string => MaritalStatus::tryFrom($state)?->getColor() ?? 'gray');
    }

    public static function numberOfChildren(): TextEntry
    {
        return TextEntry::make('number_of_children')
            ->label(__('resources/profile/strings.infolist.number_of_children'))
            ->numeric()
            ->placeholder('۰');
    }

    public static function personnelId(): TextEntry
    {
        return TextEntry::make('personnel_id')
            ->label(__('resources/profile/strings.infolist.personnel_id'))
            ->placeholder('-');
    }

    public static function position(): TextEntry
    {
        return TextEntry::make('position')
            ->label(__('resources/profile/strings.infolist.position'))
            ->badge()
            ->formatStateUsing(fn(string $state, $record): string => $record->display_position ?: (Position::tryFrom($state)?->getLabel() ?? $state))
            ->color(fn(string $state): string => Position::tryFrom($state)?->getColor() ?? 'gray');
    }

    public static function startDate(): TextEntry
    {
        return TextEntry::make('start_date')
            ->label(__('resources/profile/strings.infolist.start_date'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->placeholder('-');
    }

    public static function updatedAt(): TextEntry
    {
        return TextEntry::make('updated_at')
            ->label(__('resources/profile/strings.infolist.updated_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->color('gray');
    }

    public static function userName(): TextEntry
    {
        return TextEntry::make('user.name')
            ->label(__('resources/profile/strings.infolist.user'))
            ->weight('bold');
    }

    public static function workExperience(): TextEntry
    {
        return TextEntry::make('work_experience')
            ->label(__('resources/profile/strings.infolist.work_experience'))
            ->badge()
            ->formatStateUsing(fn(string $state): string => WorkExperience::tryFrom($state)?->getLabel() ?? $state)
            ->color(fn(string $state): string => WorkExperience::tryFrom($state)?->getColor() ?? 'gray');
    }

    public static function zipCode(): TextEntry
    {
        return TextEntry::make('zip_code')
            ->label(__('resources/profile/strings.infolist.zip_code'))
            ->placeholder('-');
    }
}
