<x-ui.modals.action
    wire:model="isManageMembersOpen"
    title="مدیریت اعضای گروه"
    action="saveManageMembers"
    confirm-text="ذخیره"
    cancel-text="انصراف"
    confirm-when="$wire.memberRecipientIds.length === 0"
    confirm-prompt="با ذخیره، اعضایی که در زبانهٔ «اعضا» انتخاب نشده‌اند از گروه حذف می‌شوند. ادامه می‌دهید؟"
>
    <div class="modal-inner-card !w-full !max-w-none !p-5 md:!p-6" dir="rtl">

        <x-ui.buttons.tab-selector class="!mb-3" :tabs="$this->presenter->memberTabs()"/>

        <div wire:key="channel-members-pane" x-show="$wire.memberTab === 'members'">
            <label class="block text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-2">کاربران فعال</label>

            <x-dashboard.member-picker wire:key="channel-members-candidates" model="memberRecipientIds" :candidates="$this->memberCandidates" height="h-[50vh] md:h-64" bulk :dept-options="$this->deptOptions"/>
        </div>

        <div wire:key="channel-writers-pane" x-show="$wire.memberTab === 'writers'" x-cloak>
            <label class="block text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-2">تنظیم اجازهٔ نوشتن اعضا</label>
            <p class="mb-2 text-xs leading-6 text-[var(--md-sys-color-on-surface-variant)]">اعضای انتخاب‌شده اجازهٔ نوشتن دارند. اگر انتخاب عضوی را بردارید، پس از ذخیره با نقش «همراه» در گروه می‌ماند و همچنان می‌تواند پیام‌ها را بخواند و جست‌وجو کند. برای بازگرداندن اجازهٔ نوشتن، او را دوباره انتخاب کنید و «ذخیره» را بزنید. مالک و مدیران همیشه اجازهٔ نوشتن دارند.</p>

            <x-dashboard.member-picker wire:key="channel-writers-candidates" model="writerIds" :candidates="$this->writerCandidates" height="h-[50vh] md:h-64" :dept-options="$this->deptOptions" empty-title="در حال حاضر عضوی برای تغییر اجازهٔ نوشتن وجود ندارد."/>
        </div>
    </div>
</x-ui.modals.action>
