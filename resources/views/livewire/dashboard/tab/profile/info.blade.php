<form wire:submit="save" class="space-y-6">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

        <!-- Profile Image -->
        <div class="lg:col-span-1">
            <x-dashboard.form.card class="h-full flex flex-col items-center justify-center text-center p-6">
                <div class="relative w-32 h-32 mb-4 group">
                    <div class="w-full h-full rounded-3xl overflow-hidden border-4 border-[var(--md-sys-color-primary)]/20 shadow-xl bg-[var(--md-sys-color-surface-variant)] flex items-center justify-center">
                        @if($user->profile->image)
                            <img src="{{ Storage::url($user->profile->image) }}" alt="Profile" class="w-full h-full object-cover">
                        @else
                            <span class="material-symbols-rounded text-6xl text-[var(--md-sys-color-on-surface-variant)]">person</span>
                        @endif
                    </div>
                    <!-- Upload overlay (optional future feature) -->
                </div>
                <h3 class="text-lg font-bold text-[var(--md-sys-color-on-surface)] mb-1">{{ $user->name }}</h3>
                <p class="text-sm text-[var(--md-sys-color-on-surface-variant)]">{{ $user->email }}</p>

                <div class="w-full mt-6 pt-6 border-t border-[var(--md-sys-color-outline-variant)]">
                    <div class="flex items-center justify-between text-sm mb-2">
                        <span class="text-[var(--md-sys-color-on-surface-variant)]">تکمیل پروفایل</span>
                        <span class="font-bold text-[var(--md-sys-color-primary)]">{{ $completion }}%</span>
                    </div>
                    <div class="w-full h-2 bg-[var(--md-sys-color-surface-variant)] rounded-3xl overflow-hidden">
                        <div class="h-full bg-[var(--md-sys-color-primary)] rounded-3xl transition-all duration-1000" style="width: {{ $completion }}%"></div>
                    </div>
                </div>
            </x-dashboard.form.card>
        </div>

        <!-- Personal Information -->
        <div class="lg:col-span-3">
            <x-dashboard.form.card title="اطلاعات فردی" description="اطلاعات شناسایی و هویتی پایه." class="h-full">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <x-dashboard.form.input label="کد پرسنلی" name="profileData.personnel_id" wire:model="profileData.personnel_id" icon="badge" />

                    <x-dashboard.form.select label="جنسیت" name="profileData.gender" wire:model="profileData.gender" icon="wc">
                        <option value="" class="bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)]">انتخاب کنید</option>
                        <option value="male" class="bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)]">مرد</option>
                        <option value="female" class="bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)]">زن</option>
                    </x-dashboard.form.select>

                    <x-dashboard.form.select label="وضعیت تاهل" name="profileData.marital_status" wire:model="profileData.marital_status" icon="diversity_2">
                        <option value="" class="bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)]">انتخاب کنید</option>
                        <option value="single" class="bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)]">مجرد</option>
                        <option value="married" class="bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)]">متاهل</option>
                    </x-dashboard.form.select>

                    <x-dashboard.form.input type="number" label="تعداد فرزندان" name="profileData.number_of_children" wire:model="profileData.number_of_children" icon="child_care" />

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 col-span-1 md:col-span-2 lg:col-span-2">
                         <x-dashboard.form.input type="date" label="تاریخ تولد" name="profileData.birthdate" wire:model="profileData.birthdate" icon="calendar_month" />
                    </div>

                    <x-dashboard.form.input label="شماره ملی" name="profileData.id_card_number" wire:model="profileData.id_card_number" icon="fingerprint" />
                    <x-dashboard.form.input label="شماره شناسنامه" name="profileData.id_booklet_number" wire:model="profileData.id_booklet_number" icon="menu_book" />
                </div>
            </x-dashboard.form.card>
        </div>
    </div>

    <!-- Job Details -->
    <x-dashboard.form.card title="اطلاعات شغلی" description="واحد سازمانی، سمت و وضعیت همکاری.">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <x-dashboard.form.select label="واحد سازمانی" name="profileData.department_id" wire:model="profileData.department_id" icon="domain">
                <option value="" class="bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)]">انتخاب واحد</option>
                @foreach($departments as $code => $name)
                    <option value="{{ $code }}" class="bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)]">{{ $name }}</option>
                @endforeach
            </x-dashboard.form.select>

            <x-dashboard.form.input label="سمت" name="profileData.position" wire:model="profileData.position" icon="work" />

            <x-dashboard.form.select label="نوع همکاری" name="profileData.employment_type" wire:model="profileData.employment_type" icon="handshake">
                <option value="" class="bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)]">انتخاب کنید</option>
                <option value="fulltime" class="bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)]">تمام وقت</option>
                <option value="parttime" class="bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)]">پاره وقت</option>
                <option value="contract" class="bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)]">قراردادی</option>
            </x-dashboard.form.select>

            <x-dashboard.form.select label="وضعیت اشتغال" name="profileData.employment_status" wire:model="profileData.employment_status" icon="verified">
                <option value="" class="bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)]">انتخاب کنید</option>
                <option value="probational" class="bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)]">آزمایشی</option>
                <option value="working" class="bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)]">مشغول به کار</option>
                <option value="terminated" class="bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)]">قطع همکاری</option>
            </x-dashboard.form.select>

            <x-dashboard.form.input label="شماره بیمه" name="profileData.insurance" wire:model="profileData.insurance" icon="health_and_safety" />
            <x-dashboard.form.input label="سابقه کار (سال)" name="profileData.work_experience" wire:model="profileData.work_experience" icon="history" />
        </div>
    </x-dashboard.form.card>

    <!-- Contact & Address -->
    <x-dashboard.form.card title="اطلاعات تماس و آدرس" description="راه‌های ارتباطی و محل سکونت.">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <x-dashboard.form.input label="تلفن همراه" name="profileData.cellphone" wire:model="profileData.cellphone" icon="smartphone" />
            <x-dashboard.form.input label="تلفن ثابت" name="profileData.landline" wire:model="profileData.landline" icon="call" />
            <x-dashboard.form.input label="تلفن ضروری" name="profileData.emergency_phone" wire:model="profileData.emergency_phone" icon="emergency" />
            <x-dashboard.form.input label="نسبت فرد ضروری" name="profileData.emergency_relationship" wire:model="profileData.emergency_relationship" icon="family_restroom" />
            <x-dashboard.form.input label="کد پستی" name="profileData.zip_code" wire:model="profileData.zip_code" icon="markunread_mailbox" />

            <div class="col-span-1 md:col-span-2 lg:col-span-3">
                <x-dashboard.form.textarea label="آدرس دقیق" name="profileData.address" wire:model="profileData.address" icon="location_on" rows="2" />
            </div>
        </div>
    </x-dashboard.form.card>

    <!-- Other Info -->
    <x-dashboard.form.card title="سایر اطلاعات" description="تحصیلات، علایق و موارد خاص.">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <x-dashboard.form.select label="مدرک تحصیلی" name="profileData.degree" wire:model="profileData.degree" icon="school">
                <option value="" class="bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)]">انتخاب مدرک</option>
                <option value="undergraduate" class="bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)]">کارشناسی و پایین‌تر</option>
                <option value="graduate" class="bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)]">کارشناسی ارشد</option>
                <option value="postgraduate" class="bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)]">دکتری و بالاتر</option>
            </x-dashboard.form.select>
            <x-dashboard.form.input label="رشته تحصیلی" name="profileData.field" wire:model="profileData.field" icon="menu_book" />

            <div class="col-span-1 md:col-span-2">
                <x-dashboard.form.textarea label="علایق و سرگرمی‌ها" name="profileData.interests" wire:model="profileData.interests" icon="favorite" rows="2" />
            </div>
            <div class="col-span-1 md:col-span-2">
                <x-dashboard.form.textarea label="نیازهای ویژه (دسترسی)" name="profileData.accessibility" wire:model="profileData.accessibility" icon="accessible" rows="2" />
            </div>
        </div>
    </x-dashboard.form.card>

    <div class="sticky bottom-6 z-30 flex justify-end">
        <div class="glass-panel p-2 rounded-2xl shadow-2xl flex items-center gap-2 backdrop-blur-xl">
            <x-dashboard.form.button type="submit" loading="save" icon="save" variant="primary">
                ذخیره تغییرات
            </x-dashboard.form.button>
        </div>
    </div>
</form>
