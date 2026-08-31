@props(['id', 'scope', 'pinNoun', 'muteNoun'])

@php
    $pinOff  = 'سنجاق کردن ' . $pinNoun;
    $pinOn   = 'برداشتن سنجاق ' . $pinNoun;
    $muteOff = 'بی‌صدا کردن ' . $muteNoun;
    $muteOn  = 'باصدا کردن ' . $muteNoun;
@endphp
<div class="absolute inset-0 -z-10 rounded-md pointer-events-none transition-colors duration-200"
     :style="{ 'background-color': $store.tagged.tagBg(@js($id), @js($scope)) }"></div>
<div class="absolute top-1.5 end-1.5 z-10 flex items-center gap-1 pointer-events-none">
    <button type="button"
            x-on:click.stop="$store.pinned.togglePin(@js($id), @js($scope))"
            x-on:keydown.enter.stop
            x-on:keydown.space.stop
            :aria-pressed="$store.pinned.isPinned(@js($id), @js($scope))"
            :aria-label="$store.pinned.isPinned(@js($id), @js($scope)) ? @js($pinOn) : @js($pinOff)"
            :title="$store.pinned.isPinned(@js($id), @js($scope)) ? @js($pinOn) : @js($pinOff)"
            class="inline-flex items-center justify-center w-6 h-6 rounded-full cursor-pointer transition-[opacity,transform,background-color] duration-200 ease-out"
            :class="$store.pinned.isPinned(@js($id), @js($scope))
                      ? 'opacity-100 scale-100 pointer-events-auto bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]'
                      : 'opacity-0 scale-90 pointer-events-none group-hover:opacity-100 group-hover:scale-100 group-hover:pointer-events-auto focus:opacity-100 focus:scale-100 focus:pointer-events-auto text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_70%,transparent)]'">
        <span class="material-symbols-rounded text-[14px] inline-block transition-transform duration-200"
              aria-hidden="true"
              :class="$store.pinned.isPinned(@js($id), @js($scope)) ? 'font-fill rotate-45' : ''">push_pin</span>
    </button>

    <button type="button"
            x-on:click.stop="$store.sound.toggleMute(@js($id), @js($scope))"
            x-on:keydown.enter.stop
            x-on:keydown.space.stop
            :aria-pressed="$store.sound.isMuted(@js($id), @js($scope))"
            :aria-label="$store.sound.isMuted(@js($id), @js($scope)) ? @js($muteOn) : @js($muteOff)"
            :title="$store.sound.isMuted(@js($id), @js($scope)) ? @js($muteOn) : @js($muteOff)"
            class="inline-flex items-center justify-center w-6 h-6 rounded-full cursor-pointer transition-[opacity,transform] duration-200 ease-out"
            :class="$store.sound.isMuted(@js($id), @js($scope))
                      ? 'opacity-100 scale-100 pointer-events-auto text-[var(--md-sys-color-primary)]'
                      : 'opacity-0 scale-90 pointer-events-none group-hover:opacity-100 group-hover:scale-100 group-hover:pointer-events-auto focus:opacity-100 focus:scale-100 focus:pointer-events-auto text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_70%,transparent)]'">
        <span class="material-symbols-rounded text-[14px]" aria-hidden="true"
              x-text="$store.sound.isMuted(@js($id), @js($scope)) ? 'volume_off' : 'volume_up'"></span>
    </button>

    <button type="button"
            x-on:click.stop="tagOpen = !tagOpen"
            x-on:keydown.enter.stop
            x-on:keydown.space.stop
            :aria-pressed="$store.tagged.isTagged(@js($id), @js($scope))"
            :aria-expanded="tagOpen"
            :aria-label="$store.tagged.isTagged(@js($id), @js($scope)) ? @js('تغییر رنگ') : @js('رنگ‌آمیزی')"
            :title="$store.tagged.isTagged(@js($id), @js($scope)) ? @js('تغییر رنگ') : @js('رنگ‌آمیزی')"
            class="inline-flex items-center justify-center w-6 h-6 rounded-full cursor-pointer transition-[opacity,transform] duration-200 ease-out"
            :class="$store.tagged.isTagged(@js($id), @js($scope))
                      ? 'opacity-100 scale-100 pointer-events-auto'
                      : 'opacity-0 scale-90 pointer-events-none group-hover:opacity-100 group-hover:scale-100 group-hover:pointer-events-auto focus:opacity-100 focus:scale-100 focus:pointer-events-auto text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_70%,transparent)]'">
        <span class="material-symbols-rounded text-[14px]" aria-hidden="true"
              :style="$store.tagged.isTagged(@js($id), @js($scope)) ? { color: $store.tagged.solid($store.tagged.getTag(@js($id), @js($scope))) } : null">palette</span>
    </button>
</div>

<div x-show="tagOpen" x-cloak
     x-on:click.outside="tagOpen = false"
     x-on:keydown.escape.window="tagOpen = false"
     class="absolute top-1.5 end-1.5 z-20 flex items-center gap-1 p-1 rounded-lg bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] shadow-lg">
    <template x-for="(col, i) in $store.tagged.palette" :key="i">
        <button type="button"
                x-on:click.stop="$store.tagged.setTag(@js($id), i, @js($scope)); tagOpen = false"
                :aria-label="'رنگ ' + (i + 1)"
                class="w-5 h-5 rounded-full border-2 transition-transform hover:scale-110"
                :class="$store.tagged.getTag(@js($id), @js($scope)) === i ? 'border-[var(--md-sys-color-on-surface)]' : 'border-transparent'"
                :style="{ 'background-color': col }"></button>
    </template>
    <button type="button"
            x-on:click.stop="$store.tagged.clearTag(@js($id), @js($scope)); tagOpen = false"
            aria-label="برداشتن رنگ"
            title="برداشتن رنگ"
            class="w-5 h-5 rounded-full flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-error)]">
        <span class="material-symbols-rounded text-[14px]">close</span>
    </button>
</div>
