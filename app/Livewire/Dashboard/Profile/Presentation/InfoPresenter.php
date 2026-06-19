<?php

namespace App\Livewire\Dashboard\Profile\Presentation;

use App\Livewire\Dashboard\Profile\Forms\ProfileForm;

class InfoPresenter
{
    public static array $stepFields = [
        1 => ['form.image'],
        2 => [
            'form.gender', 'form.marital_status', 'form.number_of_children',
            'form.birthYear', 'form.birthMonth', 'form.birthDay',
            'form.id_card_number', 'form.id_booklet_number',
        ],
        3 => [
            'form.cellphone', 'form.landline', 'form.zip_code',
            'form.emergency_phone', 'form.emergency_relationship',
            'form.license_plate', 'form.address', 'form.degree',
            'form.field', 'form.insurance', 'form.work_experience',
            'form.accessibility', 'form.interests', 'form.favoriteColors',
        ],
    ];

    public static array $steps = [
        1 => ['label' => 'شخصی',   'icon' => 'person', 'sub' => 'اطلاعات پایه'],
        2 => ['label' => 'سازمانی', 'icon' => 'badge',  'sub' => 'مشخصات هویتی'],
        3 => ['label' => 'تکمیلی',  'icon' => 'tune',   'sub' => 'تماس و سایر'],
    ];

        public static function getColors(): array
    {
        return array_map(fn($case) => $case->value, \App\Filament\Resources\ProfileResource\Enums\FavoriteColor::cases());
    }

    public static array $birthYearRange = [];

    public function __construct()
    {
        $now = now()->year;
        self::$birthYearRange = range($now - 696, $now - 616);
    }

    public function toView(ProfileForm $form, ?string $existingImage, array $departments): array
    {
        return [
            'steps'          => self::$steps,
            'stepFields'     => self::$stepFields,
            'colors'         => self::getColors(),
            'birthYearRange' => self::$birthYearRange,
            'existingImage'  => $existingImage,
            'departments'    => $departments,
        ];
    }
}
