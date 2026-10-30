@unless(tenantOff('notify:sms'))
    <div x-data="{ name: '', fresh: true, t: $wire.entangle('smsText') }"
         x-init="window.__smsCommitHook ??= Livewire.hook('commit', ({ succeed }) => succeed(() => window.dispatchEvent(new CustomEvent('sms-committed'))))"
         @sms-committed.window="fresh = false"
         @open-sms.window="$wire.smsUserId = $event.detail.id; name = $event.detail.name; t = ''; fresh = true; $wire.showSmsModal = true;">
        <x-ui.modals.action
            wire:model="showSmsModal"
            wire:key="status-sms-modal"
            title="ارسال پیامک"
            action="submitSms"
            confirm-text="ارسال پیامک"
            cancel-text="انصراف"
            class="!max-w-lg !w-full"
        >
            <div wire:key="status-sms-modal-body" dir="rtl"
                 class="modal-inner-card !w-full !max-w-none !p-5 space-y-4">
                <div class="flex items-center gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[var(--md-sys-color-primary)]/10 text-[var(--md-sys-color-primary)]">
                        <span class="material-symbols-rounded text-[18px]">sms</span>
                    </span>
                    <div class="min-w-0">
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)]">گیرنده</p>
                        <p class="text-[13px] font-bold text-[var(--md-sys-color-on-surface)] truncate" x-text="name"></p>
                    </div>
                </div>

                <x-ui.forms.textarea name="smsText" label="متن پیام" icon="edit_note" rows="4" maxlength="140" x-model="t" />

                @error('smsText')
                    <p x-show="!fresh" class="text-[11.5px] font-bold text-[var(--md-sys-color-error)]">{{ $message }}</p>
                @enderror

                <div class="flex items-center justify-between gap-3 text-[11.5px] text-[var(--md-sys-color-on-surface-variant)]">
                    <span>پیام با نام شما شروع می‌شود.</span>
                    <span dir="ltr" class="tabular-nums font-bold" x-text="t.length + ' / 140'"></span>
                </div>
            </div>
        </x-ui.modals.action>
    </div>
@endunless
