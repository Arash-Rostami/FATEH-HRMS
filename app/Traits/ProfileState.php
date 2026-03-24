<?php

namespace App\Traits;

trait ProfileState
{
    public array $state = [
        'personnel_id' => '',
        'gender' => '',
        'employment_type' => '',
        'marital_status' => '',
        'number_of_children' => 0,
        'employment_status' => '',
        'id_card_number' => '',
        'id_booklet_number' => '',
        'degree' => '',
        'field' => '',
        'landline' => '',
        'cellphone' => '',
        'license_plate' => '',
        'zip_code' => '',
        'address' => '',
        'accessibility' => '',
        'department_id' => '',
        'position' => '',
        'insurance' => '',
        'emergency_phone' => '',
        'emergency_relationship' => '',
        'work_experience' => '',
        'interests' => '',
        'email' => '',
    ];

    public $image;
    public ?string $existingImage = null;
    public array $favoriteColors = [];

    public $birthYear;
    public $birthMonth;
    public $birthDay;
}
