@php
    $loader = match (true) {
        request()->is('*admin*') => 'admin',
        request()->is('*dashboard*') => 'dashboard',
        default => 'default',
    };
@endphp

@switch($loader)
    @case('admin')
        <div class="global-app-loader flex flex-col" role="status" aria-label="در حال بارگذاری">
            <div class="flex gap-2 mb-5">
                @foreach([0, 0.15, 0.3] as $delay)
                    <span class="size-2 rounded-full bg-[var(--md-sys-color-primary)]"
                          style="animation: breathe 1.2s ease-in-out {{ $delay }}s infinite"></span>
                @endforeach
            </div>
            <div class="text-sm font-medium text-[var(--md-sys-color-on-surface-variant)]">
                بارگذاری صفحه ...
            </div>
        </div>
        @break

    @case('dashboard')
        <div class="global-app-loader">
            <div class="grid h-[80vh] w-80 shrink-0 snap-center place-items-center">
                <div class="grid place-items-center gap-4 text-[var(--md-sys-color-on-surface-variant)]">
                    <div
                        class="size-12 animate-spin rounded-full bg-[conic-gradient(var(--md-sys-color-primary)_0%,transparent_35%,var(--md-sys-color-primary)_50%,transparent_85%)] shadow-[0_0_12px_var(--md-sys-color-primary)] [mask-image:radial-gradient(farthest-side,transparent_calc(100%-3px),#000_calc(100%-3px))] [-webkit-mask-image:radial-gradient(farthest-side,transparent_calc(100%-3px),#000_calc(100%-3px))]"></div>
                    <span class="text-xs font-bold opacity-50">
                        بارگذاری موارد بیشتر...
                    </span>
                </div>
            </div>
        </div>
        @break

    @default
        <div class="global-app-loader flex items-center justify-center" role="status" aria-label="در حال بارگذاری">
            <span class="text-sm font-medium text-[var(--md-sys-color-on-surface-variant)]">
                بارگذاری...
            </span>
        </div>
@endswitch
