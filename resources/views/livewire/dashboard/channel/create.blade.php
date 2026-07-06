<div class="flex-1 overflow-y-auto px-4 md:px-8 py-6 msg-scrollbar">
    <div class="max-w-3xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-rounded text-[22px] text-[var(--md-sys-color-primary)]">add_box</span>
                <div>
                    <h2 class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">ساخت کانال جدید</h2>
                    <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">کانال عمومی — همه می‌توانند پیوستن</p>
                </div>
            </div>
            <button x-on:click="closeCreate()" aria-label="بستن"
                    class="w-8 h-8 rounded-lg flex items-center justify-center bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:brightness-95 active:scale-90 transition-all">
                <span class="material-symbols-rounded text-[18px]">close</span>
            </button>
        </div>

        <div class="bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)] rounded-xl p-5 space-y-4">
            <div>
                <label for="create-name" class="block text-[11px] font-semibold mb-1.5 text-[var(--md-sys-color-on-surface-variant)]">نام کانال</label>
                <input id="create-name" type="text" wire:model="create.name" maxlength="100" autocomplete="off"
                       placeholder="مثلاً اطلاعیه‌های فروش"
                       class="md3-input peer w-full rounded-xl text-sm outline-none focus:ring-2 px-4 h-11 bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] border border-[var(--md-sys-color-outline-variant)]/50" />
            </div>

            <div>
                <label for="create-desc" class="block text-[11px] font-semibold mb-1.5 text-[var(--md-sys-color-on-surface-variant)]">توضیحات (اختیاری)</label>
                <textarea id="create-desc" rows="3" wire:model="create.description" maxlength="500" autocomplete="off"
                          placeholder="توضیحی کوتاه درباره موضوع کانال"
                          class="md3-input peer w-full rounded-xl text-sm outline-none focus:ring-2 px-4 py-2.5 bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] border border-[var(--md-sys-color-outline-variant)]/50 resize-none"></textarea>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <button x-on:click="createChannel()" wire:loading.attr="disabled" wire:target="createChannel"
                        class="flex-shrink-0 px-5 py-2.5 rounded-lg text-[12px] font-semibold transition-all hover:brightness-110 active:scale-95 disabled:opacity-50 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]">
                    <span wire:loading.remove wire:target="createChannel">ساخت کانال</span>
                    <span wire:loading wire:target="createChannel" class="material-symbols-rounded text-[14px] animate-spin">progress_activity</span>
                </button>
                <button x-on:click="closeCreate()"
                        class="px-4 py-2.5 rounded-lg text-[12px] font-semibold transition-all hover:brightness-95 active:scale-95 bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]">
                    انصراف
                </button>
            </div>
        </div>
    </div>
</div>