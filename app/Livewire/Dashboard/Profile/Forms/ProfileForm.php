<?php

namespace App\Livewire\Dashboard\Profile\Forms;

use Livewire\Form;
use Livewire\Attributes\Validate;

class ProfileForm extends Form
{
    #[Validate('nullable|string|max:50')]
    public ?string $personnel_id = '';

    #[Validate('required|in:male,female')]
    public string $gender = '';

    #[Validate('nullable|string|max:50')]
    public ?string $employment_type = '';

    #[Validate('required|in:single,married')]
    public string $marital_status = '';

    #[Validate('required|integer|min:0')]
    public int $number_of_children = 0;

    #[Validate('nullable|string|max:50')]
    public ?string $employment_status = '';

    #[Validate('nullable|string|max:20')]
    public ?string $id_card_number = '';

    #[Validate('required|string|max:20')]
    public string $id_booklet_number = '';

    #[Validate('required|string|max:255')]
    public string $degree = '';

    #[Validate('required|string|max:255')]
    public string $field = '';

    #[Validate('nullable|string|max:20')]
    public ?string $landline = '';

    #[Validate('required|string|max:20')]
    public string $cellphone = '';

    #[Validate('nullable|string|max:20')]
    public ?string $license_plate = '';

    #[Validate('required|string|max:20')]
    public string $zip_code = '';

    #[Validate('required|string|max:1000')]
    public string $address = '';

    #[Validate('nullable|string|max:500')]
    public ?string $accessibility = '';

    #[Validate('nullable|string|max:50')]
    public ?string $department_id = '';

    #[Validate('nullable|string|max:50')]
    public ?string $position = '';

    #[Validate('required|string|max:50')]
    public string $insurance = '';

    #[Validate('required|string|max:20')]
    public string $emergency_phone = '';

    #[Validate('required|string|max:50')]
    public string $emergency_relationship = '';

    #[Validate('required|string|max:50')]
    public string $work_experience = '';

    #[Validate('nullable|string|max:1000')]
    public ?string $interests = '';

    #[Validate('nullable|string')]
    public ?string $email = '';

    #[Validate('nullable|image|max:2048')]
    public $image = null;

    #[Validate('nullable|array')]
    public array $favoriteColors = [];

    #[Validate('required|integer')]
    public ?int $birthYear = null;

    #[Validate('required|integer')]
    public ?int $birthMonth = null;

    #[Validate('required|integer')]
    public ?int $birthDay = null;

    /**
     * Get validation messages (Persian)
     */
    protected function messages(): array
    {
        return [
            'gender.required' => 'انتخاب جنسیت الزامی می‌باشد.',
            'marital_status.required' => 'انتخاب وضعیت تأهل الزامی می‌باشد.',
            'number_of_children.required' => 'وارد کردن تعداد فرزندان الزامی است.',
            'id_booklet_number.required' => 'وارد کردن شماره شناسنامه الزامی است.',
            'degree.required' => 'انتخاب مقطع تحصیلی الزامی می‌باشد.',
            'field.required' => 'وارد کردن رشته تحصیلی الزامی است.',
            'cellphone.required' => 'وارد کردن شماره تلفن همراه الزامی است.',
            'zip_code.required' => 'وارد کردن کد پستی الزامی است.',
            'address.required' => 'وارد کردن آدرس الزامی است.',
            'insurance.required' => 'وارد کردن شماره بیمه الزامی است.',
            'emergency_phone.required' => 'تلفن تماس ضروری الزامی است.',
            'emergency_relationship.required' => 'نسبت تماس ضروری الزامی است.',
            'work_experience.required' => 'وارد کردن سوابق کاری الزامی است.',
            'birthYear.required' => 'سال تولد الزامی است.',
            'birthMonth.required' => 'ماه تولد الزامی است.',
            'birthDay.required' => 'روز تولد الزامی است.',
            'image.image' => 'فایل باید یک تصویر باشد.',
            'image.max' => 'حجم تصویر نباید بیشتر از ۲ مگابایت باشد.',
        ];
    }

    /**
     * Get only the profile data (excluding birthdate components and image)
     */
    public function getProfileData(): array
    {
        return [
            'gender' => $this->gender,
            'marital_status' => $this->marital_status,
            'number_of_children' => $this->number_of_children,
            'id_card_number' => $this->id_card_number,
            'id_booklet_number' => $this->id_booklet_number,
            'degree' => $this->degree,
            'field' => $this->field,
            'landline' => $this->landline,
            'cellphone' => $this->cellphone,
            'license_plate' => $this->license_plate,
            'zip_code' => $this->zip_code,
            'address' => $this->address,
            'accessibility' => $this->accessibility,
            'insurance' => $this->insurance,
            'emergency_phone' => $this->emergency_phone,
            'emergency_relationship' => $this->emergency_relationship,
            'work_experience' => $this->work_experience,
            'interests' => $this->interests,
        ];
    }
}
