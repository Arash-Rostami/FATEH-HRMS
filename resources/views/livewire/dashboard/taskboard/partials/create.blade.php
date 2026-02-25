<x-dashboard.modal.create
    wire:model="isCreateModalOpen"
    wire:key="create-task-modal"
    title="ایجاد وظیفه جدید"
    action="createTask"
    confirm-text="ایجاد وظیفه"
    cancel-text="انصراف"
>
    <div class="modal-inner-card" dir="rtl">
        <div class="space-y-5 md:space-y-6">

            <!-- Title Input -->
            <div>
                <label class="block text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-2">
                    عنوان وظیفه
                    <span class="text-[var(--md-sys-color-error)]">*</span>
                </label>
                <input
                    type="text"
                    wire:model="newTitle"
                    class="w-full min-h-[44px] px-4 py-3 rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface)] placeholder:text-[var(--md-sys-color-on-surface-variant)]/50 focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)]/40 focus:border-[var(--md-sys-color-primary)] transition-all text-sm"
                    placeholder="عنوان وظیفه را وارد کنید..."
                >
                @error('newTitle')
                <div class="flex items-center gap-1.5 mt-2 text-[11px] text-[var(--md-sys-color-error)]">
                    <span class="material-symbols-rounded text-sm">error</span>
                    <span>{{ $message }}</span>
                </div>
                @enderror
            </div>

            <!-- Description Textarea -->
            <div>
                <label class="block text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-2">
                    توضیحات
                </label>
                <textarea
                    wire:model="newDescription"
                    rows="3"
                    class="w-full px-4 py-3 rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface)] placeholder:text-[var(--md-sys-color-on-surface-variant)]/50 focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)]/40 focus:border-[var(--md-sys-color-primary)] transition-all text-sm resize-none"
                    placeholder="توضیحات تکمیلی..."
                ></textarea>
                @error('newDescription')
                <div class="flex items-center gap-1.5 mt-2 text-[11px] text-[var(--md-sys-color-error)]">
                    <span class="material-symbols-rounded text-sm">error</span>
                    <span>{{ $message }}</span>
                </div>
                @enderror
            </div>

            <!-- Deadline Date Picker -->
            <div>
                <label class="block text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-2">
                    مهلت انجام
                </label>
                <div class="grid grid-cols-3 gap-2 md:gap-3" dir="ltr">
                    <select
                        wire:model="deadlineYear"
                        class="min-h-[44px] px-3 py-2.5 rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface)] text-center text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)]/40 focus:border-[var(--md-sys-color-primary)] transition-all"
                    >
                        <option value="">سال</option>
                        @foreach($years as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>

                    <select
                        wire:model="deadlineMonth"
                        class="min-h-[44px] px-3 py-2.5 rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface)] text-center text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)]/40 focus:border-[var(--md-sys-color-primary)] transition-all"
                    >
                        <option value="">ماه</option>
                        @foreach($months as $num => $name)
                            <option value="{{ $num }}">{{ $name }}</option>
                        @endforeach
                    </select>

                    <select
                        wire:model="deadlineDay"
                        class="min-h-[44px] px-3 py-2.5 rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface)] text-center text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)]/40 focus:border-[var(--md-sys-color-primary)] transition-all"
                    >
                        <option value="">روز</option>
                        @foreach(range(1, 31) as $day)
                            <option value="{{ $day }}">{{ $day }}</option>
                        @endforeach
                    </select>
                </div>
                @error('deadline')
                <div class="flex items-center gap-1.5 mt-2 text-[11px] text-[var(--md-sys-color-error)]">
                    <span class="material-symbols-rounded text-sm">error</span>
                    <span>{{ $message }}</span>
                </div>
                @enderror
            </div>

            <!-- Assignee Select -->
            <div>
                <label class="block text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-2">
                    مسئول انجام
                </label>
                <select
                    wire:model="selectedAssignee"
                    class="w-full min-h-[44px] px-4 py-3 rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface)] focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)]/40 focus:border-[var(--md-sys-color-primary)] transition-all text-sm"
                >
                    <option value="">خودم (شخصی)</option>
                    @foreach($staffMembers as $staff)
                        <option value="{{ $staff['id'] }}">{{ $staff['full_name'] }}</option>
                    @endforeach
                </select>
                @error('selectedAssignee')
                <div class="flex items-center gap-1.5 mt-2 text-[11px] text-[var(--md-sys-color-error)]">
                    <span class="material-symbols-rounded text-sm">error</span>
                    <span>{{ $message }}</span>
                </div>
                @enderror
            </div>
        </div>
    </div>
</x-dashboard.modal.create>
