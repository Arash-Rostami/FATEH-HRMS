<x-auth.card-no-signup title="ورود به سیستم" description="برای ورود، اطلاعات حساب خود را وارد کنید." :siblings="tenantOff('auth:sibling') ? [] : config('app.siblings')">
    <div x-data="{ showPassword: false, remember: @entangle('remember') }">
        @php
            $otp = $otpOn && $smsLogin;
            $submit = $otp ? ($codeSent ? 'verifyCode' : 'sendCode') : 'login';
            $submitIcon = match ($outcome) {
                'success' => 'check_circle',
                'fail' => 'cancel',
                default => $otp && ! $codeSent ? 'sms' : 'login',
            };
            $submitLabel = match ($outcome) {
                'success' => 'ورود موفق',
                'fail' => 'ورود ناموفق',
                default => $otp ? ($codeSent ? 'تأیید و ورود' : 'ارسال کد') : 'ورود',
            };
            $submitClass = 'w-full h-12 rounded-xl text-[15px] font-semibold tracking-tight border border-white/15 bg-gradient-to-b from-white/[0.08] to-transparent shadow-[0_6px_16px_-4px_color-mix(in_srgb,var(--md-sys-color-primary)_32%,transparent),0_2px_6px_-1px_rgba(0,0,0,0.08),inset_0_1px_0_0_rgba(255,255,255,0.22)] hover:shadow-[0_10px_22px_-4px_color-mix(in_srgb,var(--md-sys-color-primary)_42%,transparent),0_4px_10px_-2px_rgba(0,0,0,0.12),inset_0_1px_0_0_rgba(255,255,255,0.3)] hover:-translate-y-[1px] active:translate-y-0 active:scale-[0.99] active:shadow-inner transition-all duration-200 ease-out focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-primary)]/50 focus-visible:ring-offset-2' . match ($outcome) {
                'success' => ' !bg-[var(--md-sys-color-success)] !text-[var(--md-sys-color-on-success)] !border-white/25 !shadow-[0_6px_16px_-4px_color-mix(in_srgb,var(--md-sys-color-success)_35%,transparent),inset_0_1px_0_0_rgba(255,255,255,0.25)] pointer-events-none',
                'fail' => ' !bg-[var(--md-sys-color-error)] !text-[var(--md-sys-color-on-error)] !border-white/25 !shadow-[0_6px_16px_-4px_color-mix(in_srgb,var(--md-sys-color-error)_35%,transparent),inset_0_1px_0_0_rgba(255,255,255,0.25)]',
                default => '',
            };
        @endphp
        <form wire:submit.prevent="{{ $submit }}" x-data="authResend" @login-code-sent.window="start($event.detail.seconds)" class="space-y-6">
            <div class="space-y-5">
                @if($otp)
                    <div wire:key="login-mode-otp" class="space-y-5">
                        <div class="md3-input-group group relative animate-slide-up-fade motion-reduce:!animate-none">
                            <div class="relative transition-transform duration-200 focus-within:scale-[1.01]">
                                <input
                                    wire:model="phone"
                                    type="tel"
                                    id="phone"
                                    dir="ltr"
                                    inputmode="numeric"
                                    class="md3-input peer ps-14 pe-4 py-3.5 text-base bg-[var(--md-sys-color-surface-container)]/50 focus:bg-[var(--md-sys-color-surface-container)]"
                                    placeholder=" "
                                    required
                                    autocomplete="tel"
                                    @readonly($codeSent)
                                />
                                <label for="phone"
                                       class="md3-label rtl:right-14 ltr:left-14 text-sm peer-focus:rtl:!right-4 peer-focus:ltr:!left-4 peer-[:not(:placeholder-shown)]:rtl:!right-4 peer-[:not(:placeholder-shown)]:ltr:!left-4">
                                    شمارهٔ موبایل
                                </label>
                                <span
                                    class="material-symbols-rounded absolute top-[18px] rtl:right-4 ltr:left-4 text-[22px] text-[var(--md-sys-color-on-surface-variant)] transition-colors duration-200 peer-focus:text-[var(--md-sys-color-primary)] pointer-events-none">
                                    smartphone
                                </span>
                            </div>
                            @error('phone')
                            <div
                                class="mt-1 flex items-center gap-2 text-xs font-medium text-[var(--md-sys-color-error)] animate-slide-up"
                                role="alert">
                                <span class="material-symbols-rounded text-[16px]">error</span>
                                <span>{{ $message }}</span>
                            </div>
                            @enderror
                        </div>

                        <div wire:key="login-otp-code" @class(['md3-input-group group relative animate-slide-up-fade animate-delay-100 motion-reduce:!animate-none', 'pointer-events-none' => ! $codeSent])>
                                <div @class(['relative transition-all duration-200 focus-within:scale-[1.01]', 'opacity-50' => ! $codeSent])>
                                    <input
                                        wire:model="code"
                                        type="text"
                                        id="code"
                                        dir="ltr"
                                        inputmode="numeric"
                                        maxlength="5"
                                        class="md3-input peer ps-14 pe-4 py-3.5 text-base bg-[var(--md-sys-color-surface-container)]/50 focus:bg-[var(--md-sys-color-surface-container)]"
                                        placeholder=" "
                                        required
                                        autocomplete="one-time-code"
                                        @disabled(! $codeSent)
                                    />
                                    <label for="code"
                                           class="md3-label rtl:right-14 ltr:left-14 text-sm peer-focus:rtl:!right-4 peer-focus:ltr:!left-4 peer-[:not(:placeholder-shown)]:rtl:!right-4 peer-[:not(:placeholder-shown)]:ltr:!left-4">
                                        کد دریافتی
                                    </label>
                                    <span
                                        class="material-symbols-rounded absolute top-[18px] rtl:right-4 ltr:left-4 text-[22px] text-[var(--md-sys-color-on-surface-variant)] transition-colors duration-200 peer-focus:text-[var(--md-sys-color-primary)] pointer-events-none">
                                        pin
                                    </span>
                                </div>
                                @error('code')
                                <div
                                    class="mt-1 flex items-center gap-2 text-xs font-medium text-[var(--md-sys-color-error)] animate-slide-up"
                                    role="alert">
                                    <span class="material-symbols-rounded text-[16px]">error</span>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror
                        </div>
                    </div>
                @else
                    <div wire:key="login-mode-password" class="space-y-5">
                        {{-- Email Input --}}
                        <div class="md3-input-group group relative animate-slide-up-fade motion-reduce:!animate-none">
                            <div class="relative transition-transform duration-200 focus-within:scale-[1.01]">
                                <input
                                    wire:model.lazy="email"
                                    type="email"
                                    id="email"
                                    class="md3-input peer ps-14 pe-4 py-3.5 text-base bg-[var(--md-sys-color-surface-container)]/50 focus:bg-[var(--md-sys-color-surface-container)]"
                                    placeholder=" "
                                    required
                                    autocomplete="username"
                                />
                                <label for="email"
                                       class="md3-label rtl:right-14 ltr:left-14 text-sm peer-focus:rtl:!right-4 peer-focus:ltr:!left-4 peer-[:not(:placeholder-shown)]:rtl:!right-4 peer-[:not(:placeholder-shown)]:ltr:!left-4">
                                    آدرس ایمیل سازمانی
                                </label>
                                <span
                                    class="material-symbols-rounded absolute top-[18px] rtl:right-4 ltr:left-4 text-[22px] text-[var(--md-sys-color-on-surface-variant)] transition-colors duration-200 peer-focus:text-[var(--md-sys-color-primary)] pointer-events-none">
                            admin_panel_settings
                        </span>
                            </div>
                            @error('email')
                            <div
                                class="mt-1 flex items-center gap-2 text-xs font-medium text-[var(--md-sys-color-error)] animate-slide-up"
                                role="alert">
                                <span class="material-symbols-rounded text-[16px]">error</span>
                                <span>{{ $message }}</span>
                            </div>
                            @enderror
                        </div>

                        {{-- Password Input --}}
                        <div class="md3-input-group group relative animate-slide-up-fade animate-delay-100 motion-reduce:!animate-none">
                            <div class="relative transition-transform duration-200 focus-within:scale-[1.01]">
                                <input
                                    wire:model.lazy="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    id="password"
                                    class="md3-input peer ps-14 pe-14 py-3.5 text-base bg-[var(--md-sys-color-surface-container)]/50 focus:bg-[var(--md-sys-color-surface-container)]"
                                    placeholder=" "
                                    required
                                    autocomplete="current-password"
                                />
                                <label for="password"
                                       class="md3-label rtl:right-14 ltr:left-14 text-sm peer-focus:rtl:!right-4 peer-focus:ltr:!left-4 peer-[:not(:placeholder-shown)]:rtl:!right-4 peer-[:not(:placeholder-shown)]:ltr:!left-4">
                                    کد امنیتی / رمز عبور
                                </label>
                                <span
                                    class="material-symbols-rounded absolute top-[18px] rtl:right-4 ltr:left-4 text-[22px] text-[var(--md-sys-color-on-surface-variant)] transition-colors duration-200 peer-focus:text-[var(--md-sys-color-primary)] pointer-events-none">
                            key
                        </span>
                                <button type="button"
                                        @click="showPassword = !showPassword"
                                        class="absolute top-1/2 -translate-y-1/2 rtl:left-2 ltr:right-2 p-2 rounded-lg border border-transparent hover:border-[var(--md-sys-color-outline-variant)]/40 hover:bg-[var(--md-sys-color-on-surface)]/[0.06] text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-primary)] transition-all duration-150 active:scale-95 outline-none focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-primary)]/40"
                                        tabindex="-1">
                            <span class="material-symbols-rounded text-[22px] block"
                                  x-text="showPassword ? 'visibility_off' : 'visibility'"></span>
                                </button>
                            </div>
                            @error('password')
                            <div
                                class="mt-1 flex items-center gap-2 text-xs font-medium text-[var(--md-sys-color-error)] animate-slide-up"
                                role="alert">
                                <span class="material-symbols-rounded text-[16px]">error</span>
                                <span>{{ $message }}</span>
                            </div>
                            @enderror
                        </div>
                    </div>
                @endif
            </div>

            <div class="flex flex-nowrap items-center justify-between gap-x-3 px-1 whitespace-nowrap">
                <div class="flex flex-nowrap items-center gap-x-3">
                    <label class="flex items-center gap-2.5 cursor-pointer group select-none py-1">
                        <x-ui.forms.switch x-model="remember"/>
                        <span
                            class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)] group-hover:text-[var(--md-sys-color-on-surface)] transition-colors">
                            مرا به خاطر بسپار
                        </span>
                    </label>
                    @if($otpOn)
                        <label wire:key="login-sms-toggle" class="flex items-center gap-2.5 cursor-pointer group select-none py-1">
                            <x-ui.forms.switch name="smsLogin" :live="true"/>
                            <span
                                class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)] group-hover:text-[var(--md-sys-color-on-surface)] transition-colors">
                                ورود با پیامک
                            </span>
                        </label>
                    @endif
                </div>
                @if($otp && $codeSent)
                    <x-ui.buttons.form
                        wire:key="login-otp-resend"
                        variant="tonal"
                        wire:click="sendCode"
                        loading="sendCode"
                        x-bind:disabled="left > 0"
                        class="!h-8 !px-3 rounded-lg text-xs font-bold tabular-nums">
                        <span x-show="left === 0" class="material-symbols-rounded text-[18px] leading-none">refresh</span>
                        <span x-show="left === 0">ارسال مجدد</span>
                        <span x-show="left > 0" x-cloak x-text="'ارسال مجدد ' + fa(left) + ' ثانیه'"></span>
                    </x-ui.buttons.form>
                @elseif(! tenantOff('password-reset'))
                    <x-ui.buttons.form
                        wire:key="login-forgot-password"
                        variant="tonal"
                        :href="route('password.request')"
                        :class="($otp ? 'invisible ' : '') . '!h-8 !px-3 rounded-lg text-xs font-bold'">
                        <span class="material-symbols-rounded text-[18px] leading-none">lock_reset</span>
                        <span>فراموشی رمز عبور؟</span>
                    </x-ui.buttons.form>
                @endif
            </div>

            <div class="space-y-3.5 pt-2">
                <x-ui.buttons.form
                    type="submit"
                    :icon="$submitIcon"
                    loading="login,sendCode,verifyCode"
                    loading-text="در حال بررسی..."
                    x-data
                    x-effect="$wire.outcome === 'fail' && setTimeout(() => $wire.set('outcome', '', false), 2200)"
                    :disabled="$outcome === 'success'"
                    :class="$submitClass">
                    {{ $submitLabel }}
                </x-ui.buttons.form>
                {{--                <div class="relative flex py-1 items-center">--}}
                {{--                    <div class="flex-grow border-t border-[var(--md-sys-color-outline-variant)]/30"></div>--}}
                {{--                    <span--}}
                {{--                        class="flex-shrink-0 mx-4 text-[10px] font-bold text-[var(--md-sys-color-outline)] uppercase tracking-[0.2em] opacity-70">یا</span>--}}
                {{--                    <div class="flex-grow border-t border-[var(--md-sys-color-outline-variant)]/30"></div>--}}
                {{--                </div>--}}

                {{--                <a href="{{ route('register') }}"--}}
                {{--                   class="md3-btn-outlined w-full h-12 rounded-xl text-[15px] font-semibold border border-[var(--md-sys-color-outline-variant)]/70 bg-[var(--md-sys-color-surface)]/50 text-[var(--md-sys-color-on-surface-variant)] shadow-sm transition-all duration-200 hover:border-[var(--md-sys-color-primary)]/60 hover:text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary)]/[0.04] hover:shadow-md active:scale-[0.99]">--}}
                {{--                    <span class="material-symbols-rounded text-[20px]">person_add_alt</span>--}}
                {{--                    <span class="font-bold">ثبت نام کاربر جدید</span>--}}
                {{--                </a>--}}
            </div>
        </form>
    </div>
</x-auth.card-no-signup>
