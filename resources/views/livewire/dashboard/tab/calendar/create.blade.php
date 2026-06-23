<x-ui.modals.action
    wire:model="isCreateModalOpen"
    :title="$this->form->editingId ? 'ویرایش رویداد' : 'رویداد جدید'"
    action="saveEvent"
    :confirm-text="$this->form->editingId ? 'بروزرسانی تغییرات' : 'ثبت نهایی رویداد'"
    cancel-text="انصراف"
>
    <div class="modal-inner-card" dir="rtl">
        <x-ui.forms.input label="عنوان رویداد" name="form.title" wire:model="form.title"/>

        <!-- Date & Time -->
        <div class="grid grid-cols-2 gap-4">
            <x-ui.forms.input label="تاریخ" name="form.date" wire:model="form.date" class="text-center ltr"/>
            <x-ui.forms.input label="زمان" name="form.time" type="time" wire:model="form.time" class="text-center"/>
        </div>

        <!-- Description -->
        <x-ui.forms.textarea label="توضیحات تکمیلی" name="form.description" wire:model="form.description" rows="3" :maximizable="true"/>

        <!-- Privacy Toggle -->
        <div
            class="flex items-center justify-between bg-[var(--md-sys-color-surface-container)] p-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/20">
            <div class="flex items-center gap-3 text-[var(--md-sys-color-on-surface)]">
                <div
                    class="p-2 bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] rounded-lg">
                    <span class="material-symbols-rounded">lock</span>
                </div>
                <div>
                    <p class="font-bold text-sm">حریم خصوصی</p>
                    <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">نمایش فقط برای خودم</p>
                </div>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox"
                       wire:model="form.private"
                       class="sr-only peer">
                <div
                    class="w-12 h-7 bg-[var(--md-sys-color-surface-variant)] peer-focus:outline-none rounded-xl peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:start-[4px] after:bg-white after:border-gray-300 after:border after:rounded-xl after:h-5 after:w-5 after:transition-all peer-checked:bg-[var(--md-sys-color-primary)] shadow-inner"></div>
            </label>
        </div>
    </div>
</x-ui.modals.action>
