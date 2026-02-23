<form wire:submit.prevent="save" class="space-y-5" x-data="{
    step: 1, maxSteps: 3,
    nextStep() { if (this.step < this.maxSteps) { this.step++; window.scrollTo({ top: 0, behavior: 'smooth' }); } },
    prevStep() { if (this.step > 1) { this.step--; window.scrollTo({ top: 0, behavior: 'smooth' }); } },
    setStep(s) { if (s >= 1 && s <= this.maxSteps) { this.step = s; window.scrollTo({ top: 0, behavior: 'smooth' }); } }
}" dir="rtl">

    {{-- Step Rail --}}
    <div
        class="flex items-stretch gap-0 bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] rounded-2xl overflow-hidden shadow-sm">
        @foreach([1 => ['label' => 'شخصی', 'icon' => 'person', 'sub' => 'اطلاعات پایه'], 2 => ['label' => 'سازمانی', 'icon' => 'badge', 'sub' => 'مشخصات هویتی'], 3 => ['label' => 'تکمیلی', 'icon' => 'tune', 'sub' => 'تماس و سایر']] as $i => $s)
            <button type="button" @click="setStep({{ $i }})"
                    class="relative flex-1 flex flex-col items-center justify-center gap-1 py-4 px-2 text-sm font-semibold transition-all duration-200 focus:outline-none group"
                    :class="step === {{ $i }} ? 'bg-[var(--md-sys-color-primary)]/10 text-[var(--md-sys-color-primary)]' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/50 hover:text-[var(--md-sys-color-on-surface)]'">

                {{-- Separator line --}}
                @if($i < 3)
                    <span class="absolute left-0 top-1/4 h-1/2 w-px bg-[var(--md-sys-color-outline-variant)]/50"></span>
                @endif

                {{-- Active bottom bar --}}
                <span :class="step === {{ $i }} ? 'opacity-100' : 'opacity-0'"
                      class="absolute bottom-0 right-0 left-0 h-[2px] bg-[var(--md-sys-color-primary)] transition-opacity duration-200"
                      style="box-shadow: 0 0 8px color-mix(in srgb, var(--md-sys-color-primary) 60%, transparent)"></span>

                <span class="flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold transition-colors"
                      :class="step === {{ $i }} ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]'">{{ $i }}</span>

                <span class="hidden sm:block text-[13px] font-bold">{{ $s['label'] }}</span>
                <span class="hidden md:block text-[10px] font-normal opacity-60 -mt-0.5">{{ $s['sub'] }}</span>
            </button>
        @endforeach
    </div>

    {{-- Step 1: Basic --}}
    <div x-show="step === 1" x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-x-3" x-transition:enter-end="opacity-100 translate-x-0"
         class="space-y-5">
        <div
            class="bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] rounded-2xl overflow-hidden shadow-sm">
            <div class="px-6 py-4 border-b border-[var(--md-sys-color-outline-variant)]/60 flex items-center gap-3">
                <span class="material-symbols-rounded text-[var(--md-sys-color-primary)] text-xl">person</span>
                <div>
                    <h3 class="font-bold text-[var(--md-sys-color-on-surface)] text-sm">اطلاعات پایه</h3>
                    <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] mt-0.5">نام، ایمیل و تصویر
                        پروفایل خود را مدیریت کنید.</p>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                    {{-- Avatar Upload --}}
                    <div class="relative group mx-auto md:mx-0 w-32 h-32">
                        <div
                            class="relative w-full h-full rounded-2xl overflow-hidden border-2 border-[var(--md-sys-color-outline-variant)] shadow-sm transition-all duration-300 group-hover:shadow-md group-hover:border-[var(--md-sys-color-primary)]/50">
                            @if ($image)
                                <img src="{{ $image->temporaryUrl() }}" class="w-full h-full object-cover">
                            @elseif($existingImage)
                                <img src="{{ Storage::url($existingImage) }}" class="w-full h-full object-cover">
                            @else
                                <div
                                    class="w-full h-full bg-[var(--md-sys-color-surface-variant)] flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)]">
                                    <span class="material-symbols-rounded text-5xl">person</span>
                                </div>
                            @endif
                            <div wire:loading wire:target="image"
                                 class="absolute inset-0 bg-black/50 flex items-center justify-center z-10">
                                <x-dashboard.loader.spinner size="sm" class="text-white"/>
                            </div>
                        </div>

                        {{-- Upload Button (Bottom Right) --}}
                        <label for="profile-image-upload"
                               class="absolute -bottom-2 -right-2 flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md cursor-pointer hover:scale-110 active:scale-95 transition-transform z-20 border-2 border-[var(--md-sys-color-surface)]">
                            <span class="material-symbols-rounded text-[18px]">photo_camera</span>
                            <input type="file" id="profile-image-upload" wire:model="image" class="hidden"
                                   accept="image/*"/>
                        </label>

                        {{-- Delete Button (Top Left) --}}
                        @if($existingImage && !$image)
                            <button type="button" wire:click="confirmDeleteImage"
                                    class="absolute -top-2 -left-2 flex items-center justify-center w-8 h-8 rounded-xl bg-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-error)] shadow-md hover:scale-110 active:scale-95 transition-transform z-20 border-2 border-[var(--md-sys-color-surface)]"
                                    title="حذف تصویر">
                                <span class="material-symbols-rounded text-[16px]">delete</span>
                            </button>
                        @endif

                        {{-- Cancel New Upload Button (Top Left - if new image selected) --}}
                        @if($image)
                            <button type="button" wire:click="$set('image', null)"
                                    class="absolute -top-2 -left-2 flex items-center justify-center w-8 h-8 rounded-xl bg-[var(--md-sys-color-secondary)] text-[var(--md-sys-color-on-secondary)] shadow-md hover:scale-110 active:scale-95 transition-transform z-20 border-2 border-[var(--md-sys-color-surface)]"
                                    title="انصراف">
                                <span class="material-symbols-rounded text-[16px]">close</span>
                            </button>
                        @endif
                    </div>

                    {{-- User Info --}}
                    <div class="space-y-3">
                        <div
                            class="p-4 rounded-xl bg-[var(--md-sys-color-surface-variant)]/40 border border-[var(--md-sys-color-outline-variant)]/50">
                            <div
                                class="text-[10px] uppercase tracking-widest font-bold text-[var(--md-sys-color-on-surface-variant)] mb-1">
                                نام و نام خانوادگی
                            </div>
                            <div
                                class="text-base font-bold text-[var(--md-sys-color-on-surface)]">{{ Auth::user()->name }}</div>
                        </div>
                        <div
                            class="p-4 rounded-xl bg-[var(--md-sys-color-surface-variant)]/40 border border-[var(--md-sys-color-outline-variant)]/50 flex items-center justify-between">
                            <div>
                                <div
                                    class="text-[10px] uppercase tracking-widest font-bold text-[var(--md-sys-color-on-surface-variant)] mb-1">
                                    آدرس ایمیل
                                </div>
                                <div class="text-sm font-mono text-[var(--md-sys-color-on-surface)]"
                                     dir="ltr">{{ Auth::user()->email }}</div>
                            </div>
                            <span
                                class="material-symbols-rounded text-[var(--md-sys-color-primary)] text-xl">verified</span>
                        </div>
                        @error('image') <p
                            class="text-xs text-[var(--md-sys-color-error)] font-bold">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Step 2: Identity --}}
    <div x-show="step === 2" x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-x-3" x-transition:enter-end="opacity-100 translate-x-0"
         class="space-y-5">
        <div
            class="bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] rounded-2xl overflow-hidden shadow-sm">
            <div class="px-6 py-4 border-b border-[var(--md-sys-color-outline-variant)]/60 flex items-center gap-3">
                <span class="material-symbols-rounded text-[var(--md-sys-color-primary)] text-xl">badge</span>
                <div>
                    <h3 class="font-bold text-[var(--md-sys-color-on-surface)] text-sm">اطلاعات هویتی</h3>
                    <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] mt-0.5">لطفاً مشخصات هویتی خود
                        را با دقت وارد نمایید.</p>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <x-dashboard.form.select label="جنسیت" name="state.gender" wire:model="state.gender" icon="wc">
                        <option value="">انتخاب کنید</option>
                        <option value="male">مرد</option>
                        <option value="female">زن</option>
                    </x-dashboard.form.select>
                    <x-dashboard.form.select label="وضعیت تاهل" name="state.marital_status"
                                             wire:model="state.marital_status" icon="diversity_2">
                        <option value="">انتخاب کنید</option>
                        <option value="single">مجرد</option>
                        <option value="married">متاهل</option>
                    </x-dashboard.form.select>
                    <x-dashboard.form.input type="number" label="تعداد فرزندان" name="state.number_of_children"
                                            wire:model="state.number_of_children" icon="child_care"/>

                    <div
                        class="col-span-1 md:col-span-2 lg:col-span-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/60 bg-[var(--md-sys-color-surface-variant)]/20 p-4">
                        <div class="flex items-center gap-2 text-[var(--md-sys-color-on-surface-variant)] mb-4">
                            <span class="material-symbols-rounded text-lg">calendar_month</span>
                            <span class="text-sm font-bold">تاریخ تولد</span>
                        </div>
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

                    <x-dashboard.form.input label="شماره ملی" name="state.id_card_number"
                                            wire:model="state.id_card_number" icon="fingerprint"/>
                    <x-dashboard.form.input label="شماره شناسنامه" name="state.id_booklet_number"
                                            wire:model="state.id_booklet_number" icon="menu_book"/>
                </div>
            </div>
        </div>
    </div>

    {{-- Step 3: Contact & Supplementary --}}
    <div x-show="step === 3" x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-x-3" x-transition:enter-end="opacity-100 translate-x-0"
         class="space-y-5">
        <div
            class="bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] rounded-2xl overflow-hidden shadow-sm">
            <div class="px-6 py-4 border-b border-[var(--md-sys-color-outline-variant)]/60 flex items-center gap-3">
                <span class="material-symbols-rounded text-[var(--md-sys-color-primary)] text-xl">contact_phone</span>
                <div>
                    <h3 class="font-bold text-[var(--md-sys-color-on-surface)] text-sm">اطلاعات تماس و آدرس</h3>
                    <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] mt-0.5">راه‌های ارتباطی و محل
                        سکونت.</p>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <x-dashboard.form.input label="تلفن همراه" name="state.cellphone" wire:model="state.cellphone"
                                            icon="smartphone"/>
                    <x-dashboard.form.input label="تلفن ثابت" name="state.landline" wire:model="state.landline"
                                            icon="call"/>
                    <x-dashboard.form.input label="کد پستی" name="state.zip_code" wire:model="state.zip_code"
                                            icon="markunread_mailbox"/>
                    <x-dashboard.form.input label="تلفن ضروری" name="state.emergency_phone"
                                            wire:model="state.emergency_phone" icon="emergency"/>
                    <x-dashboard.form.input label="نسبت فرد ضروری" name="state.emergency_relationship"
                                            wire:model="state.emergency_relationship" icon="family_restroom"/>
                    <x-dashboard.form.input label="شماره پلاک خودرو" name="state.license_plate"
                                            wire:model="state.license_plate" icon="directions_car"/>
                    <div class="col-span-1 md:col-span-2 lg:col-span-3">
                        <x-dashboard.form.textarea label="آدرس دقیق" name="state.address" wire:model="state.address"
                                                   icon="location_on" rows="2"/>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] rounded-2xl overflow-hidden shadow-sm">
            <div class="px-6 py-4 border-b border-[var(--md-sys-color-outline-variant)]/60 flex items-center gap-3">
                <span class="material-symbols-rounded text-[var(--md-sys-color-primary)] text-xl">tune</span>
                <div>
                    <h3 class="font-bold text-[var(--md-sys-color-on-surface)] text-sm">اطلاعات تکمیلی</h3>
                    <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] mt-0.5">تحصیلات، علایق و سایر
                        موارد.</p>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <x-dashboard.form.select label="مدرک تحصیلی" name="state.degree" wire:model="state.degree"
                                             icon="school">
                        <option value="">انتخاب مدرک</option>
                        <option value="undergraduate">دیپلم یا کاردانی</option>
                        <option value="graduate">کارشناسی</option>
                        <option value="postgraduate">کارشناسی ارشد یا دکترا</option>
                    </x-dashboard.form.select>
                    <x-dashboard.form.input label="رشته تحصیلی" name="state.field" wire:model="state.field"
                                            icon="menu_book"/>
                    <x-dashboard.form.input label="شماره بیمه" name="state.insurance" wire:model="state.insurance"
                                            icon="health_and_safety"/>
                    <x-dashboard.form.input label="سابقه کار" name="state.work_experience"
                                            wire:model="state.work_experience" icon="history"/>
                    <div class="col-span-1 md:col-span-3">
                        <x-dashboard.form.textarea label="نیازهای ویژه (دسترسی)" name="state.accessibility"
                                                   wire:model="state.accessibility" icon="accessible" rows="2"/>
                    </div>
                    <div class="col-span-1 md:col-span-3">
                        <x-dashboard.form.textarea label="علایق و سرگرمی‌ها" name="state.interests"
                                                   wire:model="state.interests" icon="favorite" rows="2"/>
                    </div>

                    <div
                        class="col-span-1 md:col-span-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/60 bg-[var(--md-sys-color-surface-variant)]/20 p-5">
                        <div class="flex items-center gap-2 mb-4">
                            <span
                                class="material-symbols-rounded text-[var(--md-sys-color-primary)] text-lg">palette</span>
                            <span
                                class="font-bold text-sm text-[var(--md-sys-color-on-surface)]">رنگ‌های مورد علاقه</span>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <template
                                x-for="color in ['#ef4444','#3b82f6','#10b981','#f59e0b','#000000','#ffffff','#8b5cf6','#ec4899','#64748b','#14b8a6']">
                                <label class="cursor-pointer relative flex items-center justify-center">
                                    <input type="checkbox" wire:model="favoriteColors" :value="color"
                                           class="peer sr-only">
                                    <div :style="`background-color: ${color}`"
                                         class="w-9 h-9 rounded-xl border border-[var(--md-sys-color-outline-variant)] shadow-sm peer-checked:ring-2 peer-checked:ring-[var(--md-sys-color-primary)] peer-checked:ring-offset-1 peer-checked:border-transparent transition-all hover:scale-110"></div>
                                    <span
                                        class="material-symbols-rounded absolute text-white opacity-0 peer-checked:opacity-100 drop-shadow-md mix-blend-difference text-sm">check</span>
                                </label>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Sticky Footer Actions --}}
    <div class="sticky bottom-4 z-30 flex justify-between items-center">
        <div class="flex gap-2">
            <button type="button" x-show="step > 1" @click="prevStep()"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] text-[var(--md-sys-color-on-surface)] text-sm font-bold shadow-md hover:bg-[var(--md-sys-color-surface-variant)] transition-colors">
                <span class="material-symbols-rounded text-base">arrow_forward</span>
                <span class="hidden md:block"> مرحله قبل</span>
            </button>
            <button type="button" x-show="step < maxSteps" @click="nextStep()"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[var(--md-sys-color-secondary)] text-[var(--md-sys-color-on-secondary)] text-sm font-bold shadow-md hover:brightness-110 transition-all">
                <span class="hidden md:block">مرحله بعد</span>
                <span class="material-symbols-rounded text-base">arrow_back</span>
            </button>
        </div>
        <div class="rounded-xl  p-2">
            <x-dashboard.form.button type="submit" loading="save" icon="save" variant="primary">
                ذخیره نهایی اطلاعات
            </x-dashboard.form.button>
        </div>
    </div>
</form>
