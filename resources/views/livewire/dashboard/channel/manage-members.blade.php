<x-ui.modals.action
    wire:model="isManageMembersOpen"
    title="مدیریت اعضای گروه"
    action="saveManageMembers"
    confirm-text="ذخیره"
    cancel-text="انصراف"
>
    <div class="modal-inner-card !w-full !max-w-none !p-5 md:!p-6" dir="rtl">

        <label class="block text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-2">کاربران فعال</label>

        <x-dashboard.member-picker wire:key="channel-members-candidates" model="memberRecipientIds" :candidates="$this->memberCandidates" height="h-[50vh] md:h-64"/>
    </div>
</x-ui.modals.action>