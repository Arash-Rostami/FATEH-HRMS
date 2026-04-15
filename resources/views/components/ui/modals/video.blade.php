@props([
    'stateKey'   => 'openVideoModal',
    'srcKey'     => 'videoSrc',
    'titleKey'   => 'videoTitle',
    'maxWidth'   => 'max-w-5xl',
    'allowDownload' => false,
])

<template x-teleport="body">
    <div x-show="{{ $stateKey }}" x-cloak
         class="fixed inset-0 z-[200] flex flex-col items-center justify-center bg-[var(--md-sys-color-primary)]/60 p-4"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @click.self="handleVideoClose()"
         @keydown.escape.window="handleVideoClose()"
         style="display:none" dir="rtl">

        {{-- Top bar --}}
        <div class="w-full {{ $maxWidth }} mx-auto flex items-center justify-between mb-3 px-1">
            <p x-text="{{ $titleKey }}"
               class="text-white/80 text-sm font-semibold truncate max-w-[75%] leading-none"></p>

            <button @click="handleVideoClose()"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-white border border-white/15 transition-all group text-sm font-medium">
                <span
                    class="material-symbols-rounded text-[18px] group-hover:rotate-90 transition-transform duration-200">close</span>
                بستن
            </button>
        </div>

        {{-- Player container --}}
        <div
            class="relative w-full {{ $maxWidth }} mx-auto rounded-2xl overflow-hidden bg-black shadow-2xl ring-1 ring-white/10">
            <video x-ref="videoPlayer"
                   :src="{{ $srcKey }}"
                   controls
                   autoplay
                   preload="none"
                   @if(!$allowDownload) controlsList="nodownload noremoteplayback" @endif
                   disablePictureInPicture
                   class="w-full aspect-video block"
                   @ended="handleVideoClose()">
                <source :src="{{ $srcKey }}" type="video/mp4">
                <source :src="{{ $srcKey }}" type="video/webm">
                مرورگر شما از پخش ویدیو پشتیبانی نمی‌کند.
            </video>
        </div>

        {{-- Keyboard hint --}}
        <p class="mt-3 text-white/30 text-[10px] tracking-wider">ESC برای بستن</p>
    </div>
</template>
