<div x-show="deletingId === {{ $msg['id'] }}" style="display: none;"
     class="flex flex-col gap-2.5 px-4 py-3.5 rounded-lg bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)] shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-error)_12%,transparent)] ring-1 ring-[color-mix(in_srgb,var(--md-sys-color-error)_15%,transparent)]">
    <div class="flex items-center gap-2">
        <span class="material-symbols-rounded text-[15px] opacity-80">delete_forever</span>
        <span class="text-xs font-semibold">این پیام از گفتگو برای همه حذف شود؟</span>
    </div>
    <div class="flex items-center gap-2 justify-end">
        <button x-on:click.prevent="cancelDelete"
                class="px-3.5 py-1.5 rounded-lg text-[11px] font-semibold transition-all duration-150 hover:brightness-90 active:scale-95 bg-[color-mix(in_srgb,var(--md-sys-color-on-error-container)_10%,transparent)]">
            انصراف
        </button>
        <x-ui.buttons.form x-on:click.prevent="deleteMessage" wire:loading.attr="disabled" wire:target="deleteMessage"
                loading="deleteMessage"
                class="px-3.5 py-1.5 h-auto rounded-lg text-[11px] font-semibold transition-all duration-150 hover:brightness-110 hover:shadow-[0_2px_8px_color-mix(in_srgb,var(--md-sys-color-error)_35%,transparent)] bg-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-error)]">
            حذف
        </x-ui.buttons.form>
    </div>
</div>
