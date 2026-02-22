<form wire:submit="save" class="relative">
    <div class="space-y-8">
        <!-- Profile Picture & Personal Info -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Profile Picture Card -->
            <div class="lg:col-span-1">
                <x-dashboard.form.card class="h-full flex flex-col items-center justify-center text-center">
                    <div class="relative group">
                        @if($image)
                            <img src="{{ $image->temporaryUrl() }}" class="w-32 h-32 rounded-full object-cover border-4 border-[var(--md-sys-color-surface)] shadow-lg" alt="New Profile" />
                        @elseif($profile->image)
                            <img src="{{ Storage::url($profile->image) }}" class="w-32 h-32 rounded-full object-cover border-4 border-[var(--md-sys-color-surface)] shadow-lg" alt="Current Profile" />
                        @else
                            <div class="w-32 h-32 rounded-full bg-[var(--md-sys-color-surface-variant)] flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)] border-4 border-[var(--md-sys-color-surface)] shadow-lg">
                                <span class="material-symbols-rounded text-6xl">person</span>
                            </div>
                        @endif

                        <label for="profile-image-upload" class="absolute bottom-0 right-0 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] p-2 rounded-full shadow-lg cursor-pointer hover:brightness-110 transition-all transform hover:scale-105">
                            <span class="material-symbols-rounded text-[20px]">photo_camera</span>
                            <input type="file" id="profile-image-upload" wire:model="image" class="hidden" accept="image/*" />
                        </label>
                    </div>

                    <h3 class="mt-4 text-lg font-bold text-[var(--md-sys-color-on-surface)]">{{ Auth::user()->name }}</h3>
                    <p class="text-sm text-[var(--md-sys-color-on-surface-variant)] mb-4">{{ $profile->position ?? 'سمت تعیین نشده' }}</p>

                    @if($profile->image && !$image)
                        <button type="button" wire:click="deleteImage" wire:confirm="آیا از حذف تصویر پروفایل اطمینان دارید؟" class="text-xs text-[var(--md-sys-color-error)] hover:text-red-700 underline transition-colors">حذف تصویر</button>
                    @endif
                    @error('image') <p class="text-xs text-[var(--md-sys-color-error)] mt-2">{{ $message }}</p> @enderror
                </x-dashboard.form.card>
            </div>

            <!-- Personal Information -->
            <div class="lg:col-span-3">
                <x-dashboard.form.card title="اطلاعات فردی" description="اطلاعات شناسایی و هویتی پایه." class="h-full">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <x-dashboard.form.input label="کد پرسنلی" name="profile.personnel_id" wire:model="profile.personnel_id" icon="badge" />

                        <x-dashboard.form.select label="جنسیت" name="profile.gender" wire:model="profile.gender" icon="wc">
                            <option value="">انتخاب کنید</option>
                            <option value="male">مرد</option>
                            <option value="female">زن</option>
                        </x-dashboard.form.select>

                        <x-dashboard.form.select label="وضعیت تاهل" name="profile.marital_status" wire:model="profile.marital_status" icon="diversity_2">
                            <option value="">انتخاب کنید</option>
                            <option value="single">مجرد</option>
                            <option value="married">متاهل</option>
                        </x-dashboard.form.select>

                        <x-dashboard.form.input type="number" label="تعداد فرزندان" name="profile.number_of_children" wire:model="profile.number_of_children" icon="child_care" />

                        <div class="grid grid-cols-3 gap-2 col-span-1 md:col-span-2 lg:col-span-1">
                            <x-dashboard.form.select label="سال" name="birthYear" wire:model="birthYear">
                                <option value="">سال</option>
                                @for($i = 1330; $i <= 1410; $i++) <option value="{{ $i }}">{{ $i }}</option> @endfor
                            </x-dashboard.form.select>
                            <x-dashboard.form.select label="ماه" name="birthMonth" wire:model="birthMonth">
                                <option value="">ماه</option>
                                @for($i = 1; $i <= 12; $i++) <option value="{{ $i }}">{{ $i }}</option> @endfor
                            </x-dashboard.form.select>
                            <x-dashboard.form.select label="روز" name="birthDay" wire:model="birthDay">
                                <option value="">روز</option>
                                @for($i = 1; $i <= 31; $i++) <option value="{{ $i }}">{{ $i }}</option> @endfor
                            </x-dashboard.form.select>
                        </div>

                        <x-dashboard.form.input label="شماره ملی" name="profile.id_card_number" wire:model="profile.id_card_number" icon="fingerprint" />
                        <x-dashboard.form.input label="شماره شناسنامه" name="profile.id_booklet_number" wire:model="profile.id_booklet_number" icon="menu_book" />
                    </div>
                </x-dashboard.form.card>
            </div>
        </div>

        <!-- Job Details -->
        <x-dashboard.form.card title="اطلاعات شغلی" description="واحد سازمانی، سمت و وضعیت همکاری.">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <x-dashboard.form.select label="واحد سازمانی" name="profile.department_id" wire:model="profile.department_id" icon="domain">
                    <option value="">انتخاب واحد</option>
                    @foreach($departments as $code => $name)
                        <option value="{{ $code }}">{{ $name }}</option>
                    @endforeach
                </x-dashboard.form.select>

                <x-dashboard.form.input label="سمت" name="profile.position" wire:model="profile.position" icon="work" />

                <x-dashboard.form.select label="نوع همکاری" name="profile.employment_type" wire:model="profile.employment_type" icon="handshake">
                    <option value="">انتخاب کنید</option>
                    <option value="fulltime">تمام وقت</option>
                    <option value="parttime">پاره وقت</option>
                    <option value="contract">قراردادی</option>
                </x-dashboard.form.select>

                <x-dashboard.form.select label="وضعیت اشتغال" name="profile.employment_status" wire:model="profile.employment_status" icon="verified">
                    <option value="">انتخاب کنید</option>
                    <option value="probational">آزمایشی</option>
                    <option value="working">مشغول به کار</option>
                    <option value="terminated">قطع همکاری</option>
                </x-dashboard.form.select>

                <x-dashboard.form.input label="شماره بیمه" name="profile.insurance" wire:model="profile.insurance" icon="health_and_safety" />
                <x-dashboard.form.input label="سابقه کار (سال)" name="profile.work_experience" wire:model="profile.work_experience" icon="history" />
            </div>
        </x-dashboard.form.card>

        <!-- Contact & Address -->
        <x-dashboard.form.card title="اطلاعات تماس و آدرس" description="راه‌های ارتباطی و محل سکونت.">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <x-dashboard.form.input label="تلفن همراه" name="profile.cellphone" wire:model="profile.cellphone" icon="smartphone" />
                <x-dashboard.form.input label="تلفن ثابت" name="profile.landline" wire:model="profile.landline" icon="call" />
                <x-dashboard.form.input label="تلفن ضروری" name="profile.emergency_phone" wire:model="profile.emergency_phone" icon="emergency" />
                <x-dashboard.form.input label="نسبت فرد ضروری" name="profile.emergency_relationship" wire:model="profile.emergency_relationship" icon="family_restroom" />
                <x-dashboard.form.input label="کد پستی" name="profile.zip_code" wire:model="profile.zip_code" icon="markunread_mailbox" />

                <div class="col-span-1 md:col-span-2 lg:col-span-3">
                    <x-dashboard.form.textarea label="آدرس دقیق" name="profile.address" wire:model="profile.address" icon="location_on" rows="2" />
                </div>
            </div>
        </x-dashboard.form.card>

        <!-- Other Info -->
        <x-dashboard.form.card title="سایر اطلاعات" description="تحصیلات، علایق و موارد خاص.">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-dashboard.form.select label="مدرک تحصیلی" name="profile.degree" wire:model="profile.degree" icon="school">
                    <option value="">انتخاب مدرک</option>
                    <option value="undergraduate">کارشناسی و پایین‌تر</option>
                    <option value="graduate">کارشناسی ارشد</option>
                    <option value="postgraduate">دکتری و بالاتر</option>
                </x-dashboard.form.select>
                <x-dashboard.form.input label="رشته تحصیلی" name="profile.field" wire:model="profile.field" icon="menu_book" />

                <div class="col-span-1 md:col-span-2">
                    <x-dashboard.form.textarea label="علایق و سرگرمی‌ها" name="profile.interests" wire:model="profile.interests" icon="favorite" rows="2" />
                </div>
                <div class="col-span-1 md:col-span-2">
                    <x-dashboard.form.textarea label="نیازهای ویژه (دسترسی)" name="profile.accessibility" wire:model="profile.accessibility" icon="accessible" rows="2" />
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
    </div>
</form>
