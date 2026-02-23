@props(['translatePage' => false])

<div x-data>
    <button
        @click="location.href='?translatePage={{ !$translatePage }}'"
        class="group relative flex items-center gap-3 pl-1 pr-3 py-1.5 rounded-full transition-all duration-300 border border-[var(--md-sys-color-outline-variant)] hover:border-[var(--md-sys-color-primary)] active:scale-95"
        :class="{
            'bg-[var(--md-sys-color-surface-container-high)]': !{{ $translatePage ? 'true' : 'false' }},
            'bg-[var(--md-sys-color-primary-container)]': {{ $translatePage ? 'true' : 'false' }}
        }"
        title="{{ $translatePage ? 'غیرفعال کردن ترجمه' : 'فعال کردن ترجمه' }}"
    >
        <!-- Icon Container -->
        <div
            class="flex items-center justify-center w-8 h-8 rounded-full shadow-sm transition-all duration-300"
            :class="{
                'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)]': !{{ $translatePage ? 'true' : 'false' }},
                'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]': {{ $translatePage ? 'true' : 'false' }}
            }"
        >
            <span class="material-symbols-rounded text-[18px]">translate</span>
        </div>

        <!-- Text/Status -->
        <div class="flex items-center gap-2">
            <span
                class="text-xs font-medium transition-colors"
                :class="{
                    'text-[var(--md-sys-color-on-surface)]': !{{ $translatePage ? 'true' : 'false' }},
                    'text-[var(--md-sys-color-on-primary-container)]': {{ $translatePage ? 'true' : 'false' }}
                }"
            >
                {{ $translatePage ? 'ترجمه فعال' : 'ترجمه' }}
            </span>

            <!-- Status Dot -->
            <span
                class="w-2 h-2 rounded-full transition-colors duration-300"
                :class="{
                    'bg-[var(--md-sys-color-outline)]': !{{ $translatePage ? 'true' : 'false' }},
                    'bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.6)]': {{ $translatePage ? 'true' : 'false' }}
                }"
            ></span>
        </div>
    </button>

    @if ($translatePage)
        <x-user.google-translate />
    @endif
</div>
