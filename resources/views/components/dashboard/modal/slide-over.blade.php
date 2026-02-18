@props(['show' => 'false', 'header' => '', 'body' => '', 'actions' => '', 'onClose' => ''])

<div
    class="fixed inset-0 z-[100]"
    x-show="{{ $show }}"
    x-cloak
    style="display: none;"
>
    {{-- Backdrop --}}
    <div
        class="absolute inset-0 bg-black/40 backdrop-blur-sm transition-opacity duration-500"
        x-show="{{ $show }}"
        x-transition:enter="ease-out duration-500"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @if($onClose) @click="{{ $onClose }}" @endif
    ></div>

    {{-- Panel Content --}}
    <div
        class="absolute inset-y-0 left-0 max-w-3xl w-full bg-[var(--md-sys-color-surface)] shadow-2xl flex flex-col transform transition-transform duration-500 ease-[cubic-bezier(0.32,0.72,0,1)] border-r border-[var(--md-sys-color-outline-variant)]/20"
        x-show="{{ $show }}"
        x-transition:enter="translate-x-full rtl:-translate-x-full"
        x-transition:enter-start="translate-x-full rtl:-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="translate-x-full rtl:-translate-x-full"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full rtl:-translate-x-full"
    >
        @if($header)
            <div {{ $header->attributes->class(['relative h-72 sm:h-96 w-full shrink-0 group']) }}>
                {{ $header }}
            </div>
        @endif

        {{-- Scrollable Body --}}
        <div class="flex-1 overflow-y-auto custom-scrollbar p-8 sm:p-10 bg-[var(--md-sys-color-surface)]">
            {{ $body ?? $slot }}
        </div>

        @if($actions)
            {{-- Footer Actions --}}
            <div
                class="p-5 border-t border-[var(--md-sys-color-outline-variant)]/30 bg-[var(--md-sys-color-surface-container-low)] flex justify-between items-center shrink-0 shadow-[0_-4px_20px_-10px_rgba(0,0,0,0.1)] relative">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>
