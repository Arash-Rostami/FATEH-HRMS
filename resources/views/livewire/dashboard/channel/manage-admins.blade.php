<x-ui.modals.action
    wire:model="isManageAdminsOpen"
    title="مدیران گروه"
    action="saveManageAdmins"
    confirm-text="ذخیره"
    cancel-text="انصراف"
>
    <div class="modal-inner-card !w-full !max-w-none !p-5 md:!p-6" dir="rtl">

        <label class="block text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-2">اعضای گروه</label>

        <x-dashboard.member-picker wire:key="channel-admins-candidates" model="adminRecipientIds" :candidates="$this->adminCandidates" height="h-[50vh] md:h-64" empty-title="عضو دیگری برای انتخاب به‌عنوان مدیر وجود ندارد."/>
    </div>
</x-ui.modals.action>
