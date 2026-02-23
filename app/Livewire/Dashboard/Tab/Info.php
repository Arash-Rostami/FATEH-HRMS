<?php

namespace App\Livewire\Dashboard\Tab;

use App\Models\Department;
use App\Models\Profile;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Morilog\Jalali\Jalalian;

class Info extends Component
{
    use WithFileUploads;

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
    public array $departments = [];
    public array $favoriteColors = [];

    public $birthYear;
    public $birthMonth;
    public $birthDay;

    protected function rules(): array
    {
        return [
            'state.gender' => 'required|in:male,female',
            'state.marital_status' => 'required|in:single,married',
            'state.number_of_children' => 'required|integer|min:0',
            'state.id_card_number' => 'nullable|string|max:20',
            'state.id_booklet_number' => 'required|string|max:20',
            'state.degree' => 'required|string|max:255',
            'state.field' => 'required|string|max:255',
            'state.landline' => 'nullable|string|max:20',
            'state.cellphone' => 'required|string|max:20',
            'state.license_plate' => 'nullable|string|max:20',
            'state.zip_code' => 'required|string|max:20',
            'state.address' => 'required|string|max:1000',
            'state.accessibility' => 'nullable|string|max:500',
            'state.insurance' => 'required|string|max:50',
            'state.emergency_phone' => 'required|string|max:20',
            'state.emergency_relationship' => 'required|string|max:50',
            'state.work_experience' => 'required|string|max:50',
            'state.interests' => 'nullable|string|max:1000',

            'state.personnel_id' => 'nullable|string',
            'state.department_id' => 'nullable|string',
            'state.position' => 'nullable|string',
            'state.employment_type' => 'nullable|string',
            'state.employment_status' => 'nullable|string',
            'state.email' => 'nullable|string',

            'birthYear' => 'required|integer',
            'birthMonth' => 'required|integer',
            'birthDay' => 'required|integer',
            'favoriteColors' => 'nullable|array',
            'image' => 'nullable|image|max:2048',
        ];
    }

    protected function messages(): array
    {
        return [
            'state.gender.required' => 'انتخاب جنسیت الزامی می‌باشد.',
            'state.marital_status.required' => 'انتخاب وضعیت تأهل الزامی می‌باشد.',
            'state.number_of_children.required' => 'وارد کردن تعداد فرزندان الزامی است.',
            'state.id_booklet_number.required' => 'وارد کردن شماره شناسنامه الزامی است.',
            'state.degree.required' => 'انتخاب مقطع تحصیلی الزامی می‌باشد.',
            'state.field.required' => 'وارد کردن رشته تحصیلی الزامی است.',
            'state.cellphone.required' => 'وارد کردن شماره تلفن همراه الزامی است.',
            'state.zip_code.required' => 'وارد کردن کد پستی الزامی است.',
            'state.address.required' => 'وارد کردن آدرس الزامی است.',
            'state.insurance.required' => 'وارد کردن شماره بیمه الزامی است.',
            'state.emergency_phone.required' => 'تلفن تماس ضروری الزامی است.',
            'state.emergency_relationship.required' => 'نسبت تماس ضروری الزامی است.',
            'state.work_experience.required' => 'وارد کردن سوابق کاری الزامی است.',
            'birthYear.required' => 'سال تولد الزامی است.',
            'birthMonth.required' => 'ماه تولد الزامی است.',
            'birthDay.required' => 'روز تولد الزامی است.',
            'image.image' => 'فایل باید یک تصویر باشد.',
            'image.max' => 'حجم تصویر نباید بیشتر از ۲ مگابایت باشد.',
        ];
    }

    public function mount(): void
    {
        $profile = Auth::user()->profile;
        $this->departments = Department::pluck('name', 'code')->toArray();

        if ($profile) {
            $this->state = array_merge($this->state, $profile->only(array_keys($this->state)));
            $this->existingImage = $profile->image;

            $dbColors = $profile->favorite_colors;
            $this->favoriteColors = is_array($dbColors) ? $dbColors : (is_string($dbColors) ? explode(',', $dbColors) : []);

            if ($profile->birthdate) {
                $jalali = Jalalian::fromCarbon($profile->birthdate);
                $this->birthYear = $jalali->getYear();
                $this->birthMonth = $jalali->getMonth();
                $this->birthDay = $jalali->getDay();
            }
        }

        $this->state['email'] = Auth::user()->email ?? '';
    }

    public function save(): void
    {
        $this->validate();

        $profile = Auth::user()->profile ?? new Profile(['user_id' => Auth::id()]);

        $profile->fill([
            'gender' => $this->state['gender'],
            'marital_status' => $this->state['marital_status'],
            'number_of_children' => $this->state['number_of_children'],
            'id_card_number' => $this->state['id_card_number'],
            'id_booklet_number' => $this->state['id_booklet_number'],
            'degree' => $this->state['degree'],
            'field' => $this->state['field'],
            'landline' => $this->state['landline'],
            'cellphone' => $this->state['cellphone'],
            'license_plate' => $this->state['license_plate'],
            'zip_code' => $this->state['zip_code'],
            'address' => $this->state['address'],
            'accessibility' => $this->state['accessibility'],
            'insurance' => $this->state['insurance'],
            'emergency_phone' => $this->state['emergency_phone'],
            'emergency_relationship' => $this->state['emergency_relationship'],
            'work_experience' => $this->state['work_experience'],
            'interests' => $this->state['interests'],
        ]);

        if ($this->birthYear && $this->birthMonth && $this->birthDay) {
            try {
                $profile->birthdate = Jalalian::fromFormat(
                    'Y/n/j',
                    "{$this->birthYear}/{$this->birthMonth}/{$this->birthDay}"
                )->toCarbon();
            } catch (\Exception $e) {
                $this->dispatch('toast', message: 'تاریخ تولد نامعتبر است.', type: 'error');
                return;
            }
        }

        if ($this->image) {
            $path = $this->image->store('profiles', 'public');
            $profile->image = $path;
            $this->existingImage = $path;
            $this->image = null;
        }

        $profile->favorite_colors = $this->favoriteColors;
        $profile->save();

        $this->dispatch('toast', message: 'اطلاعات پروفایل با موفقیت ذخیره شد.', type: 'success');
    }

    public function deleteImage(): void
    {
        $profile = Auth::user()->profile;
        if ($profile && $profile->image) {
            $profile->image = null;
            $profile->save();
            $this->existingImage = null;
        }
    }

    public function render()
    {
        return view('livewire.dashboard.tab.profile.info');
    }
}
