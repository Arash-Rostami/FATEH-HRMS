@props(['title' => '', 'description' => '', 'imageContent' => null])

<div
    class="flex items-center justify-center max-h-screen w-full p-0 bg-transparent animate-slide-in-top animate-delay-1250">

    <div class="group w-full max-w-[540px] rounded-[32px] relative overflow-hidden isolate bg-gradient-to-bl from-[var(--md-sys-color-surface)]/80 via-[var(--md-sys-color-surface)]/98 to-[var(--md-sys-color-surface-container-low)] shadow-[0_8px_32px_rgba(0,0,0,0.06),0_2px_8px_rgba(0,0,0,0.04)] ring-1 ring-inset ring-[var(--md-sys-color-outline-variant)]/20 transition-all duration-500 ease-[cubic-bezier(0.2,0.8,0.2,1)] hover:shadow-[0_32px_80px_-16px_rgba(0,0,0,0.15),0_8px_24px_-4px_rgba(0,0,0,0.1)] hover:ring-[var(--md-sys-color-outline-variant)]/40 hover:-translate-y-1.5 flex flex-col">

        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-[var(--md-sys-color-primary)]/30 to-transparent z-20"></div>

        <div class="absolute top-0 -right-32 pointer-events-none h-64 w-64 opacity-20 bg-[image:radial-gradient(circle,var(--md-sys-color-primary)_1px,transparent_1px)] bg-[size:32px_32px] transition-opacity duration-700 ease-out group-hover:opacity-40"></div>
        <div class="dark:hidden absolute -top-32 -left-32 pointer-events-none h-64 w-64 overflow-hidden rounded-full opacity-[0.06] bg-[image:repeating-linear-gradient(0deg,var(--md-sys-color-primary)_0,var(--md-sys-color-primary)_1px,transparent_1px,transparent_12px),repeating-linear-gradient(90deg,var(--md-sys-color-primary)_0,var(--md-sys-color-primary)_1px,transparent_1px,transparent_12px)] transition-opacity duration-700 ease-out group-hover:opacity-[0.12]"></div>
        <div class="dark:hidden absolute -bottom-32 -right-32 pointer-events-none h-64 w-64 opacity-[0.06] bg-[image:repeating-linear-gradient(45deg,var(--md-sys-color-tertiary)_0,var(--md-sys-color-tertiary)_1px,transparent_1px,transparent_14px)] transition-opacity duration-700 ease-out group-hover:opacity-[0.12]"></div>
        <div class="dark:hidden absolute -bottom-32 -left-32 pointer-events-none h-64 w-64 overflow-hidden rounded-full opacity-[0.06] bg-[image:repeating-linear-gradient(0deg,var(--md-sys-color-tertiary)_0,var(--md-sys-color-tertiary)_1px,transparent_1px,transparent_12px),repeating-linear-gradient(90deg,var(--md-sys-color-tertiary)_0,var(--md-sys-color-tertiary)_1px,transparent_1px,transparent_12px)] transition-opacity duration-700 ease-out group-hover:opacity-[0.12]"></div>

        <div class="pt-14 px-8 pb-8 flex flex-col items-center justify-center relative z-10 w-full animate-scale-in transition-transform ease-out animate-delay-2500">
            <img src="{{ asset('build/assets/img/dark.jpg') }}" alt="INTERRA" class="h-12 w-auto shadow-sm transition-all duration-700 ease-[cubic-bezier(0.2,0.8,0.2,1)] hidden rounded-2xl dark:block group-hover:scale-105 group-hover:shadow-md group-hover:-translate-y-0.5">
            <img src="{{ asset('build/assets/img/light.jpg') }}" alt="INTERRA" class="h-12 w-auto shadow-sm transition-all duration-700 ease-[cubic-bezier(0.2,0.8,0.2,1)] block rounded-2xl dark:hidden group-hover:scale-105 group-hover:shadow-md group-hover:-translate-y-0.5">
        </div>

        <div class="px-8 sm:px-14 pb-14 flex-1 flex flex-col relative z-10">
            <div class="mb-10 text-center space-y-3.5">
                <h1 class="text-[1.75rem] leading-tight sm:text-3xl font-bold text-[var(--md-sys-color-on-surface)] tracking-tight font-display">
                    {{ $title }}
                </h1>
                @if($description)
                    <p class="text-[var(--md-sys-color-on-surface-variant)] text-sm sm:text-[0.95rem] font-medium leading-relaxed max-w-[90%] mx-auto">
                        {{ $description }}
                    </p>
                @endif
            </div>

            <div class="w-full relative space-y-7">
                {{ $slot }}
            </div>
        </div>

        <div class="hidden dark:block absolute inset-0 z-0 pointer-events-none opacity-[0.06] bg-[image:radial-gradient(circle_at_25%_25%,var(--md-sys-color-on-surface)_1px,transparent_1px),radial-gradient(circle_at_75%_75%,var(--md-sys-color-on-surface)_1px,transparent_1px)] bg-[size:8px_8px] bg-[position:0_0,4px_4px]"></div>
    </div>

</div>
