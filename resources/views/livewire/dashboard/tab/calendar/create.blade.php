<x-ui.modals.action
    wire:model="isCreateModalOpen"
    :title="$this->form->editingId ? 'ویرایش رویداد' : 'رویداد جدید'"
    action="saveEvent"
    :confirm-text="$this->form->editingId ? 'بروزرسانی تغییرات' : 'ثبت نهایی رویداد'"
    cancel-text="انصراف"
>
    <div class="modal-inner-card !w-full !max-w-none !p-5 md:!p-6" dir="rtl"
         x-data="{ ready: false }"
         x-effect="if (show && !ready) { setTimeout(() => { if (show) ready = true }, 1000) } else if (!show) { ready = false }"
         x-show="ready">
        <x-ui.forms.input label="عنوان رویداد" name="form.title" wire:model="form.title"/>

        <!-- Date & Time -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-ui.forms.date label="تاریخ" prefix="form.date" :startYear="jNow() - 50" :endYear="jNow() + 50" />
            <x-ui.forms.input label="زمان" name="form.time" type="time" wire:model="form.time" class="text-center"/>
        </div>

        <!-- Duration -->
        <x-ui.forms.select label="مدت رویداد" name="form.durationMinutes" wire:model="form.durationMinutes" icon="schedule">
            @foreach(\App\Models\Event::DURATION_MINUTES_OPTIONS as $minutes)
                <option value="{{ $minutes }}">{{ $minutes >= 60 ? (intdiv($minutes, 60) . ' ساعت' . ($minutes % 60 ? ' و ' . ($minutes % 60) . ' دقیقه' : '')) : $minutes . ' دقیقه' }}</option>
            @endforeach
        </x-ui.forms.select>

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
            <label class="cursor-pointer">
                <x-ui.forms.switch name="form.private"/>
            </label>
        </div>

        <!-- Reminder -->
        <x-ui.forms.select label="یادآوری قبل از رویداد" name="form.remind_hours" wire:model="form.remindHours" icon="notifications">
            <option value="">بدون یادآوری</option>
            @foreach(\App\Models\Event::REMIND_HOURS_OPTIONS as $hours)
                <option value="{{ $hours }}">{{ $hours }} ساعت قبل</option>
            @endforeach
        </x-ui.forms.select>
    </div>
</x-ui.modals.action>
