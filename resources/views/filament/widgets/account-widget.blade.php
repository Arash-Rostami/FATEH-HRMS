@php($user = filament()->auth()->user())

<x-filament-widgets::widget>
    <div class="animate-fade w-full" dir="rtl" x-data="greeting('{{ addslashes($greeting) }}')">
        <div
            class="relative overflow-hidden rounded-2xl bg-[var(--md-sys-color-primary-container)] border border-[var(--md-sys-color-outline-variant)]/20 shadow-lg shadow-[0_4px_30px_color-mix(in_srgb,var(--md-sys-color-primary)_8%,transparent)]">

            <div class="absolute inset-0 pointer-events-none opacity-[0.05]"
                 style="background-image: radial-gradient(circle, var(--md-sys-color-primary) 1px, transparent 1px); background-size: 24px 24px;"></div>

            <div
                class="absolute -left-8 top-1/2 -translate-y-1/2 opacity-[0.06] pointer-events-none select-none transition-transform duration-500 hover:scale-105">
                <span class="material-symbols-rounded font-fill"
                      style="font-size: 260px; line-height: 1;">dashboard</span>
            </div>

            <div class="absolute top-0 right-0 w-72 h-72 rounded-full blur-3xl opacity-25 pointer-events-none"
                 style="background: var(--md-sys-color-primary); transform: translate(20%, -20%);"></div>
            <div class="absolute bottom-0 left-1/4 w-56 h-56 rounded-full blur-3xl opacity-20 pointer-events-none"
                 style="background: var(--md-sys-color-tertiary); transform: translateY(30%);"></div>

            <div
                class="relative z-10 px-5 py-5 md:px-6 md:py-6 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div class="space-y-4 flex-1 min-w-0">
                    <div
                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-[var(--md-sys-color-primary)]/10 border border-[var(--md-sys-color-primary)]/15">
                        <span class="w-2 h-2 rounded-full bg-[var(--md-sys-color-primary)] animate-pulse"></span>
                        <span
                            class="text-[11px] font-bold uppercase tracking-[0.15em] text-[var(--md-sys-color-on-primary-container)] opacity-90">
                                {{ config('app.company_name') }}
                            </span>
                    </div>
                    <div
                        class="flex flex-col sm:flex-row sm:items-center gap-4 bg-[var(--md-sys-color-on-primary-container)]/[0.03] border border-[var(--md-sys-color-on-primary-container)]/[0.05] p-4 rounded-2xl backdrop-blur-sm max-w-xl">
                        <div class="relative shrink-0">
                            <x-filament-panels::avatar.user size="lg" :user="$user" loading="lazy"
                                                            class="ring-2 ring-[var(--md-sys-color-primary)]/30 ring-offset-2 ring-offset-[var(--md-sys-color-primary-container)] shadow-md"/>
                            <span
                                class="absolute bottom-0 right-0 block h-3 w-3 rounded-full bg-emerald-500 ring-2 ring-[var(--md-sys-color-primary-container)]"></span>
                        </div>
                        <div class="flex flex-col justify-center space-y-0.5">
                                <span class="text-xs font-medium text-[var(--md-sys-color-on-primary-container)]/60">
                                    {{ __('filament-panels::widgets/account-widget.welcome', ['app' => config('app.name')]) }}
                                </span>
                            <h2 class="text-base font-bold text-[var(--md-sys-color-on-primary-container)] tracking-tight">
                                {{ filament()->getUserName($user) }}
                            </h2>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <h1 class="text-2xl md:text-4xl font-black tracking-tight text-[var(--md-sys-color-on-primary-container)] leading-tight flex items-center gap-2 min-h-[40px]"
                            x-text="displayed"></h1>
                        <p class="text-xs md:text-sm font-medium text-[var(--md-sys-color-on-primary-container)]/60 flex items-center gap-1.5">
                            <span class="material-symbols-rounded text-sm text-[var(--md-sys-color-primary)]">calendar_today</span>
                            {{ $jalaliDate }}
                        </p>
                    </div>
                </div>

                <div
                    class="flex flex-col sm:flex-row lg:flex-col xl:flex-row items-stretch sm:items-center lg:items-stretch xl:items-center gap-4 shrink-0 self-stretch lg:self-center">
                    <div class="flex flex-wrap gap-3 items-center justify-start">
                        @foreach(array_filter([
                            $roleLabel  ? ['icon' => 'badge',           'label' => 'نقش',  'value' => $roleLabel]  : null,
                            $department ? ['icon' => 'corporate_fare',  'label' => 'واحد', 'value' => $department] : null,
                            ['icon' => 'person', 'label' => 'کاربر', 'value' => $user?->name ?? '—'],
                        ]) as $chip)
                            <div
                                class="flex flex-col items-center justify-center gap-1 px-4 py-3 rounded-2xl bg-[var(--md-sys-color-on-primary-container)]/[0.06] border border-[var(--md-sys-color-on-primary-container)]/[0.1] min-w-[100px] transition-all duration-300 hover:bg-[var(--md-sys-color-on-primary-container)]/[0.09] hover:scale-[1.02]">
                                <span
                                    class="material-symbols-rounded text-xl text-[var(--md-sys-color-primary)]">{{ $chip['icon'] }}</span>
                                <span
                                    class="text-xs font-extrabold text-[var(--md-sys-color-on-primary-container)] max-w-none break-words text-center"
                                    title="{{ $chip['value'] }}">{{ $chip['value'] }}</span>
                                <span
                                    class="text-[10px] font-medium text-[var(--md-sys-color-on-primary-container)]/50 tracking-wide">{{ $chip['label'] }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div
                        class="h-px sm:h-12 lg:h-px xl:h-12 w-full sm:w-px lg:w-full xl:w-px bg-[var(--md-sys-color-on-primary-container)]/10 self-center"></div>

                    <form action="{{ filament()->getLogoutUrl() }}" method="post" class="shrink-0 flex sm:block">
                        @csrf
                        <x-filament::button
                            color="gray"
                            :icon="\Filament\Support\Icons\Heroicon::ArrowLeftEndOnRectangle"
                            :icon-alias="\Filament\View\PanelsIconAlias::WIDGETS_ACCOUNT_LOGOUT_BUTTON"
                            labeled-from="sm"
                            tag="button"
                            type="submit"
                            class="w-full sm:w-auto !rounded-xl !py-2.5 shadow-sm hover:shadow transition-all duration-200"
                        >
                            {{ __('filament-panels::widgets/account-widget.actions.logout.label') }}
                        </x-filament::button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
