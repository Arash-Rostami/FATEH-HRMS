<form wire:submit="save" class="relative" x-data="{ step: 1 }" dir="rtl">
    <div class="flex items-center justify-between mb-8 bg-[var(--md-sys-color-surface-container)] rounded-2xl p-2 shadow-sm">
        <button type="button" @click="step = 1" :class="step === 1 ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]'" class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl transition-all font-bold text-sm">
            <span class="material-symbols-rounded">corporate_fare</span>
            اطلاعات سازمانی
        </button>
        <button type="button" @click="step = 2" :class="step === 2 ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]'" class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl transition-all font-bold text-sm">
            <span class="material-symbols-rounded">person</span>
            اطلاعات فردی
        </button>
        <button type="button" @click="step = 3" :class="step === 3 ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]'" class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl transition-all font-bold text-sm">
            <span class="material-symbols-rounded">contact_mail</span>
            اطلاعات تکمیلی
        </button>
    </div>

    <div x-show="step === 1" x-cloak x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
        <x-dashboard.form.card title="اطلاعات سازمانی" description="این اطلاعات توسط واحد منابع انسانی تنظیم شده و غیرقابل تغییر است.">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <x-dashboard.form.input label="کد پرسنلی" name="state.personnel_id" wire:model="state.personnel_id" icon="badge" disabled />
                <x-dashboard.form.input label="ایمیل سازمانی" name="state.email" wire:model="state.email" icon="mail" disabled />
                <x-dashboard.form.select label="واحد سازمانی" name="state.department_id" wire:model="state.department_id" icon="domain" disabled>
                    <option value="">انتخاب واحد</option>
                    @foreach($departments as $code => $name)
                        <option value="{{ $code }}">{{ $name }}</option>
                    @endforeach
                </x-dashboard.form.select>
                <x-dashboard.form.input label="سمت" name="state.position" wire:model="state.position" icon="work" disabled />
                <x-dashboard.form.select label="نوع همکاری" name="state.employment_type" wire:model="state.employment_type" icon="handshake" disabled>
                    <option value="">انتخاب کنید</option>
                    <option value="fulltime">تمام وقت</option>
                    <option value="parttime">پاره وقت</option>
                    <option value="contract">قراردادی</option>
                </x-dashboard.form.select>
                <x-dashboard.form.select label="وضعیت اشتغال" name="state.employment_status" wire:model="state.employment_status" icon="verified" disabled>
                    <option value="">انتخاب کنید</option>
                    <option value="probational">آزمایشی</option>
                    <option value="working">مشغول به کار</option>
                    <option value="terminated">قطع همکاری</option>
                </x-dashboard.form.select>
            </div>
        </x-dashboard.form.card>
    </div>

    <div x-show="step === 2" x-cloak x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <div class="lg:col-span-1">
                <x-dashboard.form.card class="h-full flex flex-col items-center justify-center text-center">
                    <div class="relative group">
                        @if($image)
                            <img src="{{ $image->temporaryUrl() }}" class="w-32 h-32 rounded-full object-cover border-4 border-[var(--md-sys-color-surface)] shadow-lg" alt="New Profile" />
                        @elseif($existingImage)
                            <img src="{{ Storage::url($existingImage) }}" class="w-32 h-32 rounded-full object-cover border-4 border-[var(--md-sys-color-surface)] shadow-lg" alt="Current Profile" />
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

                    <h3 class="mt-4 text-lg font-bold text-[var(--md-sys-color-on-surface)]">{{ Auth::user()->name ?? '' }}</h3>

                    @if($existingImage && !$image)
                        <button type="button" wire:click="deleteImage" wire:confirm="آیا از حذف تصویر پروفایل اطمینان دارید؟" class="mt-4 text-xs font-bold px-4 py-2 rounded-lg bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)] transition-colors">حذف تصویر</button>
                    @endif
                    @error('image') <p class="text-xs text-[var(--md-sys-color-error)] mt-2">{{ $message }}</p> @enderror
                </x-dashboard.form.card>
            </div>

            <div class="lg:col-span-3">
                <x-dashboard.form.card title="اطلاعات هویتی" description="لطفاً مشخصات هویتی خود را با دقت وارد نمایید.">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <x-dashboard.form.select label="جنسیت" name="state.gender" wire:model="state.gender" icon="wc">
                            <option value="">انتخاب کنید</option>
                            <option value="male">مرد</option>
                            <option value="female">زن</option>
                        </x-dashboard.form.select>

                        <x-dashboard.form.select label="وضعیت تاهل" name="state.marital_status" wire:model="state.marital_status" icon="diversity_2">
                            <option value="">انتخاب کنید</option>
                            <option value="single">مجرد</option>
                            <option value="married">متاهل</option>
                        </x-dashboard.form.select>

                        <x-dashboard.form.input type="number" label="تعداد فرزندان" name="state.number_of_children" wire:model="state.number_of_children" icon="child_care" />

                        <div class="grid grid-cols-3 gap-2 col-span-1 md:col-span-2 lg:col-span-3 bg-[var(--md-sys-color-surface-container-lowest)] p-4 rounded-xl border border-[var(--md-sys-color-outline-variant)]">
                            <div class="col-span-3 mb-2 flex items-center gap-2 text-[var(--md-sys-color-on-surface-variant)]">
                                <span class="material-symbols-rounded">calendar_month</span>
                                <span class="text-sm font-bold">تاریخ تولد</span>
                            </div>
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

                        <x-dashboard.form.input label="شماره ملی" name="state.id_card_number" wire:model="state.id_card_number" icon="fingerprint" />
                        <x-dashboard.form.input label="شماره شناسنامه" name="state.id_booklet_number" wire:model="state.id_booklet_number" icon="menu_book" />
                    </div>
                </x-dashboard.form.card>
            </div>
        </div>
    </div>

    <div x-show="step === 3" x-cloak x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0" class="space-y-6">
        <x-dashboard.form.card title="اطلاعات تماس و آدرس" description="راه‌های ارتباطی و محل سکونت.">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <x-dashboard.form.input label="تلفن همراه" name="state.cellphone" wire:model="state.cellphone" icon="smartphone" />
                <x-dashboard.form.input label="تلفن ثابت" name="state.landline" wire:model="state.landline" icon="call" />
                <x-dashboard.form.input label="کد پستی" name="state.zip_code" wire:model="state.zip_code" icon="markunread_mailbox" />
                <x-dashboard.form.input label="تلفن ضروری" name="state.emergency_phone" wire:model="state.emergency_phone" icon="emergency" />
                <x-dashboard.form.input label="نسبت فرد ضروری" name="state.emergency_relationship" wire:model="state.emergency_relationship" icon="family_restroom" />
                <x-dashboard.form.input label="شماره پلاک خودرو" name="state.license_plate" wire:model="state.license_plate" icon="directions_car" />

                <div class="col-span-1 md:col-span-2 lg:col-span-3">
                    <x-dashboard.form.textarea label="آدرس دقیق" name="state.address" wire:model="state.address" icon="location_on" rows="2" />
                </div>
            </div>
        </x-dashboard.form.card>

        <x-dashboard.form.card title="اطلاعات تکمیلی" description="تحصیلات، علایق و سایر موارد.">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <x-dashboard.form.select label="مدرک تحصیلی" name="state.degree" wire:model="state.degree" icon="school">
                    <option value="">انتخاب مدرک</option>
                    <option value="undergraduate">دیپلم یا کاردانی</option>
                    <option value="graduate">کارشناسی</option>
                    <option value="postgraduate">کارشناسی ارشد یا دکترا</option>
                </x-dashboard.form.select>
                <x-dashboard.form.input label="رشته تحصیلی" name="state.field" wire:model="state.field" icon="menu_book" />
                <x-dashboard.form.input label="شماره بیمه" name="state.insurance" wire:model="state.insurance" icon="health_and_safety" />
                <x-dashboard.form.input label="سابقه کار" name="state.work_experience" wire:model="state.work_experience" icon="history" />

                <div class="col-span-1 md:col-span-3">
                    <x-dashboard.form.textarea label="نیازهای ویژه (دسترسی)" name="state.accessibility" wire:model="state.accessibility" icon="accessible" rows="2" />
                </div>
                <div class="col-span-1 md:col-span-3">
                    <x-dashboard.form.textarea label="علایق و سرگرمی‌ها" name="state.interests" wire:model="state.interests" icon="favorite" rows="2" />
                </div>

                <div class="col-span-1 md:col-span-3 bg-[var(--md-sys-color-surface-container-lowest)] border border-[var(--md-sys-color-outline-variant)] rounded-xl p-5">
                    <div class="flex items-center gap-2 mb-4 text-[var(--md-sys-color-on-surface)]">
                        <span class="material-symbols-rounded">palette</span>
                        <span class="font-bold">رنگ‌های مورد علاقه</span>
                    </div>
                    <div class="flex flex-wrap gap-4">
                        <template x-for="color in ['#ef4444', '#3b82f6', '#10b981', '#f59e0b', '#000000', '#ffffff', '#8b5cf6', '#ec4899', '#64748b', '#14b8a6']">
                            <label class="cursor-pointer relative flex items-center justify-center">
                                <input type="checkbox" wire:model="favoriteColors" :value="color" class="peer sr-only">
                                <div :style="`background-color: ${color}`" class="w-10 h-10 rounded-full border border-[var(--md-sys-color-outline)] shadow-sm peer-checked:ring-4 peer-checked:ring-[var(--md-sys-color-primary)] peer-checked:border-transparent transition-all"></div>
                                <span class="material-symbols-rounded absolute text-white opacity-0 peer-checked:opacity-100 drop-shadow-md mix-blend-difference">check</span>
                            </label>
                        </template>
                    </div>
                </div>
            </div>
        </x-dashboard.form.card>
    </div>

    <div class="sticky bottom-6 z-30 flex justify-between items-center mt-8">
        <div class="flex gap-2">
            <button type="button" x-show="step > 1" @click="step--" class="bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface)] px-4 py-2 rounded-xl shadow-lg font-bold flex items-center gap-2 hover:brightness-95 transition-all">
                <span class="material-symbols-rounded">arrow_forward</span>
                مرحله قبل
            </button>
            <button type="button" x-show="step < 3" @click="step++" class="bg-[var(--md-sys-color-secondary)] text-[var(--md-sys-color-on-secondary)] px-4 py-2 rounded-xl shadow-lg font-bold flex items-center gap-2 hover:brightness-110 transition-all">
                مرحله بعد
                <span class="material-symbols-rounded">arrow_back</span>
            </button>
        </div>

        <div class="glass-panel p-2 rounded-2xl shadow-2xl flex items-center gap-2 backdrop-blur-xl bg-[var(--md-sys-color-surface)]/80">
            <x-dashboard.form.button type="submit" loading="save" icon="save" variant="primary">
                ذخیره نهایی اطلاعات
            </x-dashboard.form.button>
        </div>
    </div>
</form>
