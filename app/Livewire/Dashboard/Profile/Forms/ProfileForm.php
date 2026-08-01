<?php

namespace App\Livewire\Dashboard\Profile\Forms;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Form;
use Livewire\Attributes\Validate;
use Morilog\Jalali\CalendarUtils;

class ProfileForm extends Form
{
    #[Validate('required|in:male,female')]
    public string $gender = '';

    #[Validate('required|in:single,married')]
    public string $marital_status = '';

    #[Validate('required|integer|min:0')]
    public int $number_of_children = 0;

    #[Validate('nullable|string|max:50')]
    public ?string $employment_status = '';

    public ?string $id_card_number = '';

    public string $id_booklet_number = '';

    #[Validate('required|string|in:undergraduate,graduate,postgraduate,doctorate')]
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

    #[Validate('required|string|max:50')]
    public string $insurance = '';

    #[Validate('required|string|max:20')]
    public string $emergency_phone = '';

    #[Validate('required|string|max:50')]
    public string $emergency_relationship = '';

    #[Validate('required|string|in:student,0-1,1-2,2-3,3-5,5-7,7-10,10-15,15-20,20+,freelance,career_change')]
    public string $work_experience = '';

    #[Validate('nullable|string|max:1000')]
    public ?string $interests = '';

    #[Validate('nullable|string')]
    public ?string $email = '';

    #[Validate('nullable|image|max:2048')]
    public $image = null;

    public array $favoriteColors = [];

    public ?int $birthYear = null;

    public ?int $birthMonth = null;

    public ?int $birthDay = null;

    protected function rules(): array
    {
        $profileId = Auth::user()?->profile?->getKey();

        return [
            'id_card_number' => ['nullable', 'string', 'max:20', Rule::unique('profiles', 'id_card_number')->ignore($profileId)],
            'id_booklet_number' => ['required', 'string', 'max:20', Rule::unique('profiles', 'id_booklet_number')->ignore($profileId)],
            'birthYear' => 'nullable|integer|min:1300|max:1500|required_with:birthMonth,birthDay',
            'birthMonth' => 'nullable|integer|min:1|max:12|required_with:birthYear,birthDay',
            'birthDay' => ['nullable', 'integer', 'min:1', 'max:31', 'required_with:birthYear,birthMonth', $this->validBirthDate()],
            'favoriteColors' => 'nullable|array',
            'favoriteColors.*' => ['string', 'regex:/^(#[0-9a-fA-F]{3,8}|rgb\([^)]*\)|rgba\([^)]*\)|hsl\([^)]*\)|hsla\([^)]*\)|[a-z]+)$/'],
        ];
    }

    private function validBirthDate(): Closure
    {
        return function ($attribute, $value, $fail) {
            if (
                $this->birthYear && $this->birthMonth && $this->birthDay
                && !CalendarUtils::checkDate((int) $this->birthYear, (int) $this->birthMonth, (int) $this->birthDay, true)
            ) {
                $fail('تاریخ تولد واردشده معتبر نیست.');
            }
        };
    }

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
            'id_card_number.unique' => __('resources/profile/strings.validation.id_card_number.unique'),
            'id_booklet_number.unique' => __('resources/profile/strings.validation.id_booklet_number.unique'),
            'degree.required' => 'انتخاب مقطع تحصیلی الزامی می‌باشد.',
            'degree.in' => 'مقطع تحصیلی انتخاب‌شده نامعتبر است.',
            'work_experience.in' => 'سابقه کاری انتخاب‌شده نامعتبر است.',
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
