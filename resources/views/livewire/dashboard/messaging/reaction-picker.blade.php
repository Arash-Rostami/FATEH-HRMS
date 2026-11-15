<template x-teleport="body">
    <div x-show="String($store.activityReactionPicker.entryId ?? '').startsWith('ch')" x-cloak
         x-transition:enter="animate-pop motion-reduce:!animate-none"
         @click.outside="$store.activityReactionPicker.close()"
         :style="{ top: $store.activityReactionPicker.top + 'px', right: $store.activityReactionPicker.right + 'px' }"
         class="fixed z-30 p-2 rounded-2xl bg-[var(--md-sys-color-surface-container-high)] border border-[var(--md-sys-color-outline-variant)]/50 shadow-[0_12px_32px_color-mix(in_srgb,var(--md-sys-color-scrim)_22%,transparent)]">
        <div class="grid grid-cols-7 gap-0.5 max-w-[260px]">
            <template x-for="emoji in $store.activityReactionPicker.reactions" :key="emoji">
                <button type="button"
                        @click="const rid = Number(String($store.activityReactionPicker.entryId).slice(2)); $store.activityReactionPicker.close(); react(rid, emoji)"
                        class="flex items-center justify-center w-8 h-8 rounded-lg hover:bg-[var(--md-sys-color-primary-container)]/40 hover:scale-110 active:scale-90 transition text-base">
                    <span x-text="emoji"></span>
                </button>
            </template>
        </div>
    </div>
</template>
