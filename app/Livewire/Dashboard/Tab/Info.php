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

    public array  = [
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

    public ;
    public ?string  = null;
    public array  = [];
    public array  = [];

    public ;
    public ;
    public ;

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
         = Auth::user()->profile;
        ->departments = Department::pluck('name', 'code')->toArray();

        if () {
            ->state = array_merge(->state, ->only(array_keys(->state)));
            ->existingImage = ->image;

             = ->favorite_colors;
            ->favoriteColors = is_array() ?  : (is_string() ? explode(',', ) : []);

            if (->birthdate) {
                 = Jalalian::fromCarbon(->birthdate);
                ->birthYear = ->getYear();
                ->birthMonth = ->getMonth();
                ->birthDay = ->getDay();
            }
        }

        ->state['email'] = Auth::user()->email ?? '';
    }

    public function save(): void
    {
        ->validate();

         = Auth::user()->profile ?? new Profile(['user_id' => Auth::id()]);

        ->fill([
            'gender' => ->state['gender'],
            'marital_status' => ->state['marital_status'],
            'number_of_children' => ->state['number_of_children'],
            'id_card_number' => ->state['id_card_number'],
            'id_booklet_number' => ->state['id_booklet_number'],
            'degree' => ->state['degree'],
            'field' => ->state['field'],
            'landline' => ->state['landline'],
            'cellphone' => ->state['cellphone'],
            'license_plate' => ->state['license_plate'],
            'zip_code' => ->state['zip_code'],
            'address' => ->state['address'],
            'accessibility' => ->state['accessibility'],
            'insurance' => ->state['insurance'],
            'emergency_phone' => ->state['emergency_phone'],
            'emergency_relationship' => ->state['emergency_relationship'],
            'work_experience' => ->state['work_experience'],
            'interests' => ->state['interests'],
        ]);

        if (->birthYear && ->birthMonth && ->birthDay) {
            try {
                ->birthdate = Jalalian::fromFormat(
                    'Y/n/j',
                    "{->birthYear}/{->birthMonth}/{->birthDay}"
                )->toCarbon();
            } catch (\Exception ) {
                ->dispatch('toast', message: 'تاریخ تولد نامعتبر است.', type: 'error');
                return;
            }
        }

        if (->image) {
             = ->image->store('profiles', 'public');
            ->image = ;
            ->existingImage = ;
            ->image = null;
        }

        ->favorite_colors = ->favoriteColors;
        ->save();

        ->dispatch('toast', message: 'اطلاعات پروفایل با موفقیت ذخیره شد.', type: 'success');
    }

    public function confirmDeleteImage(): void
    {
        ->dispatch('open-confirmation',
            title: 'حذف تصویر پروفایل',
            message: 'آیا از حذف تصویر پروفایل خود اطمینان دارید؟ این عملیات غیرقابل بازگشت است.',
            method: 'deleteImage'
        );
    }

    public function deleteImage(): void
    {
         = Auth::user()->profile;
        if ( && ->image) {
            ->image = null;
            ->save();
            ->existingImage = null;
            ->dispatch('toast', message: 'تصویر پروفایل با موفقیت حذف شد.', type: 'success');
        }
    }

    public function render()
    {
        return view('livewire.dashboard.tab.profile.info');
    }
}
