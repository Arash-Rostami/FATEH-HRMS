@php
    $firstErrorStep = collect($stepFields)->search(fn($fields) => $errors->hasAny($fields));
@endphp

<form wire:submit.prevent="save"
      class="space-y-5"
      x-data="profile"
      x-init="$nextTick(() => { const e = {{ $firstErrorStep ?: 'null' }}; if (e) setStep(e); })"
      dir="rtl">

    {{-- Step Rail --}}
    <div
        class="flex items-stretch gap-0 bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] rounded-2xl overflow-hidden shadow-sm">
        @foreach($steps as $i => $s)
            <button type="button"
                    @click="setStep({{ $i }})"
                    class="relative flex-1 flex flex-col items-center justify-center gap-1 py-4 px-2 text-sm font-semibold transition-all duration-200 focus:outline-none group"
                    :class="step === {{ $i }}
                        ? 'bg-[var(--md-sys-color-primary)]/10 text-[var(--md-sys-color-primary)]'
                        : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/50 hover:text-[var(--md-sys-color-on-surface)]'">

                @if($i < 3)
                    <span class="absolute left-0 top-1/4 h-1/2 w-px bg-[var(--md-sys-color-outline-variant)]/50"></span>
                @endif

                <span :class="step === {{ $i }} ? 'opacity-100' : 'opacity-0'"
                      class="absolute bottom-0 right-0 left-0 h-[2px] bg-[var(--md-sys-color-primary)] transition-opacity duration-200"
                      style="box-shadow: 0 0 8px color-mix(in srgb, var(--md-sys-color-primary) 60%, transparent)"></span>

                <span class="flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold transition-colors"
                      :class="step === {{ $i }}
                          ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]'
                          : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]'">
                    {{ $i }}
                </span>

                <span class="hidden sm:block text-[13px] font-bold">{{ $s['label'] }}</span>
                <span class="hidden md:block text-[10px] font-normal opacity-60 -mt-0.5">{{ $s['sub'] }}</span>
                @if($errors->hasAny($stepFields[$i]))
                    <span
                        class="absolute bottom-1.5 animate-pulse left-1.5 w-2 h-2 rounded-full bg-[var(--md-sys-color-error)] shadow-sm"></span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- Step 1: Basic --}}
    <div x-show="step === 1"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-x-3"
         x-transition:enter-end="opacity-100 translate-x-0"
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
                        @error('form.image')
                        <p class="text-xs text-[var(--md-sys-color-error)] font-bold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="relative group mx-auto w-32 h-32">
                        <div
                            class="relative w-full h-full rounded-2xl overflow-hidden border-2 border-[var(--md-sys-color-outline-variant)] shadow-sm transition-all duration-300 group-hover:shadow-md group-hover:border-[var(--md-sys-color-primary)]/50">
                            <x-ui.avatar :image="$form->image ?? null" :existingImage="$existingImage"/>
                            <div wire:loading wire:target="form.image"
                                 class="absolute inset-0 bg-black/50 flex items-center justify-center z-10">
                                <x-ui.loaders.spinner size="sm" class="text-white"/>
                            </div>
                        </div>

                        <label for="profile-image-upload"
                               class="absolute -bottom-2 -right-2 flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md cursor-pointer hover:scale-110 active:scale-95 transition-transform z-20 border-2 border-[var(--md-sys-color-surface)]">
                            <span class="material-symbols-rounded text-[18px]">photo_camera</span>
                            <input type="file" id="profile-image-upload" wire:model="form.image" class="hidden"
                                   accept="image/*"/>
                        </label>

                        @if($existingImage && !$form->image)
                            <button type="button"
                                    wire:click="$dispatch('open-confirmation', {
                                        title: 'حذف تصویر پروفایل',
                                        message: 'آیا از حذف تصویر پروفایل خود اطمینان دارید؟ این عملیات غیرقابل بازگشت است.',
                                        method: 'confirmAction',
                                        params: 'confirm-delete-profile-image'
                                    })"
                                    class="absolute -top-2 -left-2 flex items-center justify-center w-8 h-8 rounded-xl bg-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-error)] shadow-md hover:scale-110 active:scale-95 transition-transform z-20 border-2 border-[var(--md-sys-color-surface)]">
                                <span class="material-symbols-rounded text-[16px]">delete</span>
                            </button>
                        @endif

                        @if($form->image)
                            <button type="button" wire:click="$set('form.image', null)"
                                    class="absolute -top-2 -left-2 flex items-center justify-center w-8 h-8 rounded-xl bg-[var(--md-sys-color-secondary)] text-[var(--md-sys-color-on-secondary)] shadow-md hover:scale-110 active:scale-95 transition-transform z-20 border-2 border-[var(--md-sys-color-surface)]">
                                <span class="material-symbols-rounded text-[16px]">close</span>
                            </button>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Step 2: Identity --}}
    <div x-show="step === 2"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-x-3"
         x-transition:enter-end="opacity-100 translate-x-0"
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
                    <x-ui.forms.select label="جنسیت" name="form.gender" wire:model="form.gender" icon="wc">
                        <option value="">انتخاب کنید</option>
                        <option value="male">مرد</option>
                        <option value="female">زن</option>
                    </x-ui.forms.select>

                    <x-ui.forms.select label="وضعیت تاهل" name="form.marital_status" wire:model="form.marital_status"
                                       icon="diversity_2">
                        <option value="">انتخاب کنید</option>
                        <option value="single">مجرد</option>
                        <option value="married">متاهل</option>
                    </x-ui.forms.select>

                    <x-ui.forms.input type="number" label="تعداد فرزندان" name="form.number_of_children"
                                      wire:model="form.number_of_children" icon="child_care"/>

                    <x-ui.forms.date
                        label="تاریخ تولد"
                        prefix="form.birth"
                        :startYear="\Morilog\Jalali\Jalalian::now()->getYear() - 70"
                        :endYear="\Morilog\Jalali\Jalalian::now()->getYear() - 15"
                    />

                    <x-ui.forms.input label="شماره ملی" name="form.id_card_number" wire:model="form.id_card_number"
                                      icon="fingerprint"/>
                    <x-ui.forms.input label="شماره شناسنامه" name="form.id_booklet_number"
                                      wire:model="form.id_booklet_number" icon="menu_book"/>
                </div>
            </div>
        </div>
    </div>

    {{-- Step 3: Contact & Supplementary --}}
    <div x-show="step === 3"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-x-3"
         x-transition:enter-end="opacity-100 translate-x-0"
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
                    <x-ui.forms.input label="تلفن همراه" name="form.cellphone" wire:model="form.cellphone"
                                      icon="smartphone"/>
                    <x-ui.forms.input label="تلفن ثابت" name="form.landline" wire:model="form.landline" icon="call"/>
                    <x-ui.forms.input label="کد پستی" name="form.zip_code" wire:model="form.zip_code"
                                      icon="markunread_mailbox"/>
                    <x-ui.forms.input label="تلفن ضروری" name="form.emergency_phone" wire:model="form.emergency_phone"
                                      icon="emergency"/>
                    <x-ui.forms.input label="نسبت فرد ضروری"
                                      @input="(e) => setDirection(e)"
                                      x-init="(e) => setDirection(e)"
                                      name="form.emergency_relationship"
                                      wire:model="form.emergency_relationship"
                                      icon="family_restroom"/>
                    <x-ui.forms.input label="شماره پلاک خودرو" name="form.license_plate" wire:model="form.license_plate"
                                      icon="directions_car"/>
                    <div class="col-span-1 md:col-span-2 lg:col-span-3">
                        <x-ui.forms.textarea label="آدرس دقیق"
                                             name="form.address"
                                             @input="(e) => setDirection(e)"
                                             x-init="(el) => { el.addEventListener('load', (e) => setDirection(e)); setDirection(el); }"
                                             wire:model="form.address"
                                             icon="location_on"
                                             rows="2"/>
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
                    <x-ui.forms.select label="مدرک تحصیلی" name="form.degree" wire:model="form.degree" icon="school">
                        <option value="">انتخاب مدرک</option>
                        <option value="undergraduate">دیپلم یا کاردانی</option>
                        <option value="graduate">کارشناسی</option>
                        <option value="postgraduate">کارشناسی ارشد یا دکترا</option>
                    </x-ui.forms.select>

                    <x-ui.forms.input label="رشته تحصیلی"
                                      name="form.field"
                                      @input="(e) => setDirection(e)"
                                      x-init="(el) => { el.addEventListener('load', (e) => setDirection(e)); setDirection(el); }"
                                      wire:model="form.field"
                                      icon="menu_book"/>

                    <x-ui.forms.input label="شماره بیمه" name="form.insurance" wire:model="form.insurance"
                                      icon="health_and_safety"/>
                    <x-ui.forms.select label="سابقه کار" name="form.work_experience" wire:model="form.work_experience"
                                      icon="history">
                                    <option value="" disabled selected hidden>سابقه کار</option>
                                    <option value="student">دانشجو / بدون سابقه</option>
                                    <option value="0-1">کمتر از ۱ سال</option>
                                    <option value="1-2">۱ تا ۲ سال</option>
                                    <option value="2-3">۲ تا ۳ سال</option>
                                    <option value="3-5">۳ تا ۵ سال</option>
                                    <option value="5-7">۵ تا ۷ سال</option>
                                    <option value="7-10">۷ تا ۱۰ سال</option>
                                    <option value="10-15">۱۰ تا ۱۵ سال</option>
                                    <option value="15-20">۱۵ تا ۲۰ سال</option>
                                    <option value="20+">بیشتر از ۲۰ سال</option>
                                    <option value="freelance">فریلنس / پروژه‌ای</option>
                                    <option value="career_change">تغییر مسیر شغلی / شروع مجدد</option>
                    </x-ui.forms.select>

                    <div class="col-span-1 md:col-span-3">
                        <x-ui.forms.textarea label="نیازهای ویژه (دسترسی)"
                                             @input="(e) => setDirection(e)"
                                             x-init="(el) => { el.addEventListener('load', (e) => setDirection(e)); setDirection(el); }"
                                             name="form.accessibility"
                                             wire:model="form.accessibility"
                                             icon="accessible"
                                             rows="2"/>
                    </div>

                    <div class="col-span-1 md:col-span-3">
                        <x-ui.forms.textarea label="علایق و سرگرمی‌ها"
                                             @input="(e) => setDirection(e)"
                                             x-init="(el) => { el.addEventListener('load', (e) => setDirection(e)); setDirection(el); }"
                                             name="form.interests"
                                             wire:model="form.interests"
                                             icon="favorite"
                                             rows="2"/>
                    </div>

                    <div
                        class="col-span-1 md:col-span-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/60 bg-[var(--md-sys-color-surface-variant)]/20 p-5">
                        <div class="flex items-center gap-2 mb-4">
                            <span
                                class="material-symbols-rounded text-[var(--md-sys-color-primary)] text-lg">palette</span>
                            <span
                                class="font-bold text-sm text-[var(--md-sys-color-on-surface)]">رنگ‌های مورد علاقه</span>
                        </div>
                        <div x-data="{ expanded: false, get colors() { return Array.isArray($wire.form.favoriteColors) ? $wire.form.favoriteColors : []; } }" class="relative">
                            <div class="flex flex-wrap gap-3">
                                @foreach($colors as $index => $color)
                                    <label class="cursor-pointer relative flex items-center justify-center transition-all duration-300"
                                           x-show="expanded || colors.includes('{{ $color }}') || {{ $index }} < 7"
                                           x-transition.opacity.duration.300ms>
                                        <input type="checkbox" wire:model="form.favoriteColors" value="{{ $color }}"
                                               class="peer sr-only">
                                        <div style="background-color: {{ $color }}"
                                             class="w-9 h-9 rounded-xl border border-[var(--md-sys-color-outline-variant)] shadow-sm peer-checked:ring-2 peer-checked:ring-[var(--md-sys-color-primary)] peer-checked:ring-offset-1 peer-checked:border-transparent transition-all hover:scale-110"></div>
                                        <span class="material-symbols-rounded absolute text-white drop-shadow-md mix-blend-difference text-sm transition-opacity duration-200"
                                              :class="colors.includes('{{ $color }}') ? 'opacity-100' : 'opacity-0'">check</span>
                                    </label>
                                @endforeach

                                <button type="button" @click="expanded = !expanded"
                                        class="w-9 h-9 flex items-center justify-center rounded-xl border border-dashed border-[var(--md-sys-color-outline)] bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] hover:text-[var(--md-sys-color-primary)] transition-all hover:scale-110 shadow-sm"
                                        title="نمایش بیشتر/کمتر">
                                    <span class="material-symbols-rounded text-[20px] transition-transform duration-300"
                                          :class="expanded ? 'rotate-180' : ''">expand_more</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="sticky bottom-4 z-30 flex justify-between items-center">
        <div class="flex gap-2">
            <button type="button"
                    x-show="step > 1"
                    @click="prevStep()"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] text-[var(--md-sys-color-on-surface)] text-sm font-bold shadow-md hover:bg-[var(--md-sys-color-surface-variant)] transition-colors">
                <span class="material-symbols-rounded text-base">arrow_forward</span>
                <span class="hidden md:block">مرحله قبل</span>
            </button>
            <button type="button"
                    x-show="step < maxSteps"
                    @click="nextStep()"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[var(--md-sys-color-secondary)] text-[var(--md-sys-color-on-secondary)] text-sm font-bold shadow-md hover:brightness-110 transition-all">
                <span class="hidden md:block">مرحله بعد</span>
                <span class="material-symbols-rounded text-base">arrow_back</span>
            </button>
        </div>
        <div class="rounded-xl p-2">
            <x-ui.buttons.form type="submit" loading="save" icon="save" variant="primary">
                ذخیره نهایی اطلاعات
            </x-ui.buttons.form>
        </div>
    </div>

</form>
