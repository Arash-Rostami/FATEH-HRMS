<div class="fixed bottom-4 right-4 left-4 sm:left-auto z-50 flex flex-col gap-3 w-auto sm:w-full sm:max-w-[360px]" x-show="mentionToasts.length > 0" x-cloak>
    <template x-for="t in mentionToasts" :key="t.channel_id + '-' + t.message_id">
        <div class="bg-[color-mix(in_srgb,var(--md-sys-color-surface)_92%,transparent)] dark:bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_60%,transparent)] shadow-[0_8px_32px_color-mix(in_srgb,var(--md-sys-color-primary)_25%,transparent)] dark:shadow-[0_12px_40px_rgba(0,0,0,0.6)] rounded-2xl p-4 sm:p-5"
             x-transition:enter="animate-toast-in"
             x-transition:leave="animate-fade-out">
            <div class="flex items-start gap-3">
                <span class="material-symbols-rounded text-[22px] text-[var(--md-sys-color-primary)] flex-shrink-0 mt-0.5">alternate_email</span>
                <div class="min-w-0 flex-1">
                    <p class="text-[12px] font-medium text-[var(--md-sys-color-on-surface-variant)] leading-tight" x-text="t.sender_name + ' به شما اشاره کرد'"></p>
                    <p class="text-[14px] font-bold text-[var(--md-sys-color-on-surface)] truncate mt-1" x-text="t.channel_name"></p>
                </div>
            </div>
            <div class="flex items-center gap-2.5 mt-4">
                <button type="button" x-on:click="openMentionToast(t)"
                        class="flex-1 px-3 py-2.5 rounded-xl text-[12.5px] font-bold whitespace-nowrap bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] hover:opacity-90 active:scale-[0.97] transition-all flex items-center justify-center">
                    رفتن به پیام
                </button>
                <button type="button" x-on:click="dismissMentionToast(t)"
                        class="flex-1 px-3 py-2.5 rounded-xl text-[12.5px] font-bold whitespace-nowrap bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_20%,transparent)] active:scale-[0.97] transition-all flex items-center justify-center">
                    بستن
                </button>
            </div>
        </div>
    </template>
</div>