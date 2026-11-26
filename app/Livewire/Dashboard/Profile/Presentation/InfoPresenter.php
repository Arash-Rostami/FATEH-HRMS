<?php

namespace App\Livewire\Dashboard\Profile\Presentation;

use App\Enums\FavoriteColor;
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
        1 => ['label' => 'شخصی', 'icon' => 'person', 'sub' => 'اطلاعات پایه'],
        2 => ['label' => 'سازمانی', 'icon' => 'badge', 'sub' => 'مشخصات هویتی'],
        3 => ['label' => 'تکمیلی', 'icon' => 'tune', 'sub' => 'تماس و سایر'],
    ];

    public static function getColors(): array
    {
        return array_map(fn($case) => $case->value, FavoriteColor::cases());
    }

    public static function firstInvalid(array $keys): array
    {
        $ordered = collect(self::$stepFields)->flatMap(
            fn(array $fields, int $step) => collect($fields)->map(fn(string $f) => ['step' => $step, 'field' => $f])
        );

        $first = $ordered->first(fn(array $row) => in_array($row['field'], $keys, true)) ?? ['step' => 1, 'field' => null];

        return $first + ['count' => count($keys)];
    }

    public function toView(ProfileForm $form, ?string $existingImage, array $errorKeys = []): array
    {
        return [
            'steps' => self::$steps,
            'stepFields' => self::$stepFields,
            'stepErrors' => collect(self::$stepFields)->map(fn(array $fields) => count(array_intersect($fields, $errorKeys)))->all(),
            'colors' => self::getColors(),
            'existingImage' => $existingImage,
        ];
    }
}
