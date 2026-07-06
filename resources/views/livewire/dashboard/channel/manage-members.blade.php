<x-ui.modals.action
    wire:model="isManageMembersOpen"
    title="مدیریت اعضای کانال"
    action="saveManageMembers"
    confirm-text="ذخیره"
    cancel-text="انصراف"
>
    <div class="modal-inner-card !w-full !max-w-none !p-5 md:!p-6" dir="rtl">

        <label class="block text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-2">کاربران فعال</label>

        @if(count($this->memberCandidates) > 0)
            <div class="max-h-64 overflow-y-auto custom-scrollbar rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 divide-y divide-[var(--md-sys-color-outline-variant)]/20">
                @foreach($this->memberCandidates as $u)
                    <label class="flex items-center gap-3 px-4 py-3 hover:bg-[var(--md-sys-color-surface-container)]/60 cursor-pointer transition-colors">
                        <input
                            type="checkbox"
                            value="{{ $u['id'] }}"
                            wire:model="memberRecipientIds"
                            class="w-4 h-4 rounded text-[var(--md-sys-color-primary)] border-[var(--md-sys-color-outline-variant)] focus:ring-[var(--md-sys-color-primary)]"
                        >
                        <span class="text-sm font-medium text-[var(--md-sys-color-on-surface)]">{{ $u['name'] }}</span>
                    </label>
                @endforeach
            </div>
        @else
            <div class="text-center py-10 text-[var(--md-sys-color-on-surface-variant)]">
                <span class="material-symbols-rounded text-4xl mb-2 block opacity-40">group</span>
                <p class="text-sm">کاربر فعال دیگری برای افزودن وجود ندارد.</p>
            </div>
        @endif
    </div>
</x-ui.modals.action>