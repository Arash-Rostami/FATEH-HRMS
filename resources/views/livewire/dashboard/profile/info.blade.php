<form wire:submit="save" x-data="{ step: @entangle('step'), maxSteps: 3 }" class="relative" dir="rtl">
    <div class="mb-8 flex items-center justify-between relative px-2 md:px-6">
        <div class="absolute left-6 right-6 top-1/2 -translate-y-1/2 h-1 bg-[var(--md-sys-color-surface-variant)] rounded-full z-0"></div>
        <div class="absolute right-6 top-1/2 -translate-y-1/2 h-1 bg-[var(--md-sys-color-primary)] rounded-full z-0 transition-all duration-500 ease-out shadow-[0_0_8px_color-mix(in_srgb,var(--md-sys-color-primary)_60%,transparent)]"
             :style="'width: ' + ((step - 1) / (maxSteps - 1) * 100) + '%'"></div>

        @foreach(['هویتی', 'پایه', 'تکمیلی'] as $index => $label)
            <div class="relative z-10 flex flex-col items-center gap-2 group cursor-pointer" @click="if({{ $index + 1 }} <= step) step = {{ $index + 1 }}">
                <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold border-2 transition-all duration-300"
                    :class="{
                        'bg-[var(--md-sys-color-primary)] border-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md': step > {{ $index + 1 }},
                        'bg-[var(--md-sys-color-surface)] border-[var(--md-sys-color-primary)] text-[var(--md-sys-color-primary)] shadow-[0_0_12px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)] scale-110': step === {{ $index + 1 }},
                        'bg-[var(--md-sys-color-surface)] border-[var(--md-sys-color-outline-variant)] text-[var(--md-sys-color-on-surface-variant)]': step < {{ $index + 1 }}
                    }">
                    <span x-show="step > {{ $index + 1 }}" class="material-symbols-rounded text-lg">check</span>
                    <span x-show="step <= {{ $index + 1 }}">{{ $index + 1 }}</span>
                </div>
                <span class="text-[10px] md:text-xs font-bold transition-colors"
                    :class="step >= {{ $index + 1 }} ? 'text-[var(--md-sys-color-primary)]' : 'text-[var(--md-sys-color-on-surface-variant)]'">
                    {{ $label }}
                </span>
            </div>
        @endforeach
    </div>

    <div x-show="step === 1" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-3" x-transition:enter-end="opacity-100 translate-x-0" class="space-y-5">
        <div class="bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/50 rounded-2xl overflow-hidden shadow-sm hover:border-[var(--md-sys-color-primary)]/30 transition-colors">
            <div class="px-6 py-4 border-b border-[var(--md-sys-color-outline-variant)]/40 flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center">
                    <span class="material-symbols-rounded text-base">badge</span>
                </div>
                <div>
                    <h3 class="font-bold text-[var(--md-sys-color-on-surface)] text-sm">اطلاعات هویتی پایه</h3>
                    <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] mt-0.5">مشخصات اصلی و تصویر پروفایل شما.</p>
                </div>
            </div>
            <div class="p-6">
                <div class="flex flex-col sm:flex-row gap-8 items-start">
                    <div class="w-full sm:w-32 flex flex-col items-center gap-3">
                        <div class="relative group cursor-pointer w-28 h-28">
                            <input type="file" wire:model.live="newAvatar" class="absolute inset-0 w-full h-full opacity-0 z-20 cursor-pointer" accept="image/jpeg, image/png, image/webp" aria-label="آپلود تصویر پروفایل">
                            @if($newAvatar)
                                <img src="{{ $newAvatar->temporaryUrl() }}" alt="Preview" class="w-full h-full rounded-full object-cover border-4 border-[var(--md-sys-color-surface)] shadow-lg shadow-[var(--md-sys-color-primary)]/20">
                            @elseif($user->profile_photo_path)
                                <img src="{{ Storage::url($user->profile_photo_path) }}" alt="{{ $user->name }}" class="w-full h-full rounded-full object-cover border-4 border-[var(--md-sys-color-surface)] shadow-sm">
                            @else
                                <div class="w-full h-full rounded-full bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center border-4 border-[var(--md-sys-color-surface)] shadow-sm">
                                    <span class="material-symbols-rounded text-4xl">person</span>
                                </div>
                            @endif
                            <div class="absolute inset-0 bg-black/40 rounded-full opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center z-10 pointer-events-none">
                                <span class="material-symbols-rounded text-white text-2xl drop-shadow-md">add_a_photo</span>
                            </div>
                            <div wire:loading wire:target="newAvatar" class="absolute inset-0 bg-[var(--md-sys-color-surface)]/80 rounded-full flex items-center justify-center z-30">
                                <span class="material-symbols-rounded animate-spin text-[var(--md-sys-color-primary)] text-2xl">sync</span>
                            </div>
                        </div>
                        <p class="text-[9px] text-[var(--md-sys-color-on-surface-variant)] text-center leading-relaxed">فقط JPG، PNG<br>حداکثر ۲ مگابایت</p>
                        @error('newAvatar') <p class="text-[10px] text-[var(--md-sys-color-error)] font-bold text-center">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex-1 w-full grid grid-cols-1 md:grid-cols-2 gap-5">
                        <x-dashboard.form.input label="نام (فارسی)" name="state.first_name" wire:model="state.first_name" icon="person"/>
                        <x-dashboard.form.input label="نام خانوادگی (فارسی)" name="state.last_name" wire:model="state.last_name" icon="person_filled"/>
                        <x-dashboard.form.input label="نام و نام خانوادگی (انگلیسی)" name="state.english_name" wire:model="state.english_name" icon="language" dir="ltr"/>
                        <x-dashboard.form.input label="نام پدر" name="state.father_name" wire:model="state.father_name" icon="escalator_warning"/>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-show="step === 2" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-3" x-transition:enter-end="opacity-100 translate-x-0" class="space-y-5">
        <div class="bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/50 rounded-2xl overflow-hidden shadow-sm hover:border-[var(--md-sys-color-primary)]/30 transition-colors">
            <div class="px-6 py-4 border-b border-[var(--md-sys-color-outline-variant)]/40 flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] flex items-center justify-center">
                    <span class="material-symbols-rounded text-base">fingerprint</span>
                </div>
                <div>
                    <h3 class="font-bold text-[var(--md-sys-color-on-surface)] text-sm">مشخصات فردی</h3>
                    <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] mt-0.5">اطلاعات شناسنامه‌ای و وضعیت تأهل.</p>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-dashboard.form.select label="جنسیت" name="state.gender" wire:model="state.gender" icon="wc">
                        <option value="">انتخاب کنید</option>
                        <option value="male">مرد</option>
                        <option value="female">زن</option>
                    </x-dashboard.form.select>
                    <x-dashboard.form.select label="وضعیت تأهل" name="state.marital_status" wire:model="state.marital_status" icon="favorite">
                        <option value="">انتخاب کنید</option>
                        <option value="single">مجرد</option>
                        <option value="married">متأهل</option>
                    </x-dashboard.form.select>
                    <div class="col-span-1 md:col-span-2 space-y-2">
                        <label class="block text-xs font-bold text-[var(--md-sys-color-on-surface)]">تاریخ تولد</label>
                        <div class="grid grid-cols-3 gap-3">
                            <x-dashboard.form.select label="سال" name="birthYear" wire:model="birthYear">
                                <option value="">سال</option>
                                @for($i = 1330; $i <= 1410; $i++)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </x-dashboard.form.select>
                            <x-dashboard.form.select label="ماه" name="birthMonth" wire:model="birthMonth">
                                <option value="">ماه</option>
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </x-dashboard.form.select>
                            <x-dashboard.form.select label="روز" name="birthDay" wire:model="birthDay">
                                <option value="">روز</option>
                                @for($i = 1; $i <= 31; $i++)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </x-dashboard.form.select>
                        </div>
                    </div>
                    <x-dashboard.form.input label="شماره ملی" name="state.id_card_number" wire:model="state.id_card_number" icon="fingerprint"/>
                    <x-dashboard.form.input label="شماره شناسنامه" name="state.id_booklet_number" wire:model="state.id_booklet_number" icon="menu_book"/>
                </div>
            </div>
        </div>
    </div>

    <div x-show="step === 3" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-3" x-transition:enter-end="opacity-100 translate-x-0" class="space-y-5">
        <div class="bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/50 rounded-2xl overflow-hidden shadow-sm hover:border-[var(--md-sys-color-primary)]/30 transition-colors">
            <div class="px-6 py-4 border-b border-[var(--md-sys-color-outline-variant)]/40 flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] flex items-center justify-center">
                    <span class="material-symbols-rounded text-base">contact_phone</span>
                </div>
                <div>
                    <h3 class="font-bold text-[var(--md-sys-color-on-surface)] text-sm">اطلاعات تماس و آدرس</h3>
                    <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] mt-0.5">راه‌های ارتباطی و محل سکونت.</p>
                </div>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <x-dashboard.form.input label="تلفن همراه" name="state.cellphone" wire:model="state.cellphone" icon="smartphone"/>
                <x-dashboard.form.input label="تلفن ثابت" name="state.landline" wire:model="state.landline" icon="call"/>
                <x-dashboard.form.input label="کد پستی" name="state.zip_code" wire:model="state.zip_code" icon="markunread_mailbox"/>
                <x-dashboard.form.input label="تلفن ضروری" name="state.emergency_phone" wire:model="state.emergency_phone" icon="emergency"/>
                <x-dashboard.form.input label="نسبت فرد ضروری" name="state.emergency_relationship" wire:model="state.emergency_relationship" icon="family_restroom"/>
                <x-dashboard.form.input label="شماره پلاک خودرو" name="state.license_plate" wire:model="state.license_plate" icon="directions_car"/>
                <div class="col-span-1 md:col-span-2 lg:col-span-3">
                    <x-dashboard.form.textarea label="آدرس دقیق" name="state.address" wire:model="state.address" icon="location_on" rows="2"/>
                </div>
            </div>
        </div>

        <div class="bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/50 rounded-2xl overflow-hidden shadow-sm hover:border-[var(--md-sys-color-primary)]/30 transition-colors">
            <div class="px-6 py-4 border-b border-[var(--md-sys-color-outline-variant)]/40 flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center">
                    <span class="material-symbols-rounded text-base">tune</span>
                </div>
                <div>
                    <h3 class="font-bold text-[var(--md-sys-color-on-surface)] text-sm">اطلاعات تکمیلی</h3>
                    <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] mt-0.5">تحصیلات، علایق و سایر موارد.</p>
                </div>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                <x-dashboard.form.select label="مدرک تحصیلی" name="state.degree" wire:model="state.degree" icon="school">
                    <option value="">انتخاب مدرک</option>
                    <option value="undergraduate">دیپلم یا کاردانی</option>
                    <option value="graduate">کارشناسی</option>
                    <option value="postgraduate">کارشناسی ارشد یا دکترا</option>
                </x-dashboard.form.select>
                <x-dashboard.form.input label="رشته تحصیلی" name="state.field" wire:model="state.field" icon="menu_book"/>
                <x-dashboard.form.input label="شماره بیمه" name="state.insurance" wire:model="state.insurance" icon="health_and_safety"/>
                <x-dashboard.form.input label="سابقه کار" name="state.work_experience" wire:model="state.work_experience" icon="history"/>
                <div class="col-span-1 md:col-span-3">
                    <x-dashboard.form.textarea label="نیازهای ویژه (دسترسی)" name="state.accessibility" wire:model="state.accessibility" icon="accessible" rows="2"/>
                </div>
                <div class="col-span-1 md:col-span-3">
                    <x-dashboard.form.textarea label="علایق و سرگرمی‌ها" name="state.interests" wire:model="state.interests" icon="favorite" rows="2"/>
                </div>

                <div class="col-span-1 md:col-span-3 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-variant)]/10 p-5">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] flex items-center justify-center">
                            <span class="material-symbols-rounded text-base">palette</span>
                        </div>
                        <span class="font-bold text-sm text-[var(--md-sys-color-on-surface)]">رنگ‌های مورد علاقه</span>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <template x-for="color in ['#ef4444','#3b82f6','#10b981','#f59e0b','#000000','#ffffff','#8b5cf6','#ec4899','#64748b','#14b8a6']">
                            <label class="cursor-pointer relative flex items-center justify-center">
                                <input type="checkbox" wire:model="favoriteColors" :value="color" class="peer sr-only">
                                <div :style="`background-color: ${color}`" class="w-10 h-10 rounded-xl border border-[var(--md-sys-color-outline-variant)] shadow-sm peer-checked:ring-2 peer-checked:ring-[var(--md-sys-color-primary)] peer-checked:ring-offset-2 peer-checked:ring-offset-[var(--md-sys-color-surface)] peer-checked:border-transparent transition-all hover:scale-110"></div>
                                <span class="material-symbols-rounded absolute text-white opacity-0 peer-checked:opacity-100 drop-shadow-md mix-blend-difference text-sm pointer-events-none">check</span>
                            </label>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="sticky bottom-4 z-30 flex justify-between items-center bg-[var(--md-sys-color-surface)]/80 backdrop-blur-md p-3 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/50 shadow-[0_8px_32px_rgba(0,0,0,0.1)] mt-8">
        <div class="flex gap-2">
            <button type="button" x-show="step > 1" @click="prevStep()" class="flex items-center gap-2 px-4 py-2 rounded-xl text-[var(--md-sys-color-on-surface-variant)] text-sm font-bold hover:bg-[var(--md-sys-color-surface-variant)] transition-colors">
                <span class="material-symbols-rounded text-base">arrow_forward</span>
                <span class="hidden md:block">مرحله قبل</span>
            </button>
            <button type="button" x-show="step < maxSteps" @click="nextStep()" class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] text-sm font-bold shadow-sm hover:brightness-95 active:scale-[0.98] transition-all">
                <span class="hidden md:block">مرحله بعد</span>
                <span class="material-symbols-rounded text-base">arrow_back</span>
            </button>
        </div>
        <x-dashboard.form.button type="submit" loading="save" icon="save" class="!rounded-xl !h-11 shadow-[0_4px_12px_color-mix(in_srgb,var(--md-sys-color-primary)_30%,transparent)]">
            ذخیره اطلاعات
        </x-dashboard.form.button>
    </div>
</form>
