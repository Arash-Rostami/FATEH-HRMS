<div
    x-data="{ show: false }"
    @open-modal.window="if ($event.detail === 'create-task-modal') show = true"
    @task-created.window="show = false"
    @keydown.escape.window="show = false"
    style="display: none;"
    x-show="show"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
>
    <div
        x-show="show"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 transition-opacity bg-gray-500/75 backdrop-blur-sm"
        @click="show = false"
    ></div>

    <div
        x-show="show"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="relative transform overflow-hidden rounded-2xl bg-[var(--md-sys-color-surface)] text-left shadow-xl transition-all w-full max-w-lg"
    >
        <div class="px-6 py-4 border-b border-[var(--md-sys-color-outline-variant)]/40 flex items-center justify-between">
            <h3 class="text-lg font-bold text-[var(--md-sys-color-on-surface)]">ایجاد وظیفه جدید</h3>
            <button @click="show = false" class="text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-primary)]">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        <div class="p-6 space-y-4">
            <!-- Title -->
            <div>
                <label class="block text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-1">عنوان وظیفه <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    wire:model="newTitle"
                    class="w-full rounded-xl border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface)] focus:border-[var(--md-sys-color-primary)] focus:ring-[var(--md-sys-color-primary)] shadow-sm"
                    placeholder="عنوان وظیفه را وارد کنید..."
                >
                @error('newTitle') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
            </div>

            <!-- Description -->
            <div>
                <label class="block text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-1">توضیحات</label>
                <textarea
                    wire:model="newDescription"
                    rows="3"
                    class="w-full rounded-xl border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface)] focus:border-[var(--md-sys-color-primary)] focus:ring-[var(--md-sys-color-primary)] shadow-sm"
                    placeholder="توضیحات تکمیلی..."
                ></textarea>
                @error('newDescription') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
            </div>

            <!-- Deadline -->
            <div>
                <label class="block text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-1">مهلت انجام</label>
                <div class="grid grid-cols-3 gap-2" dir="ltr">
                    <select wire:model="deadlineYear" class="rounded-xl border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface)] text-center text-sm">
                        <option value="">سال</option>
                        @foreach($years as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                    <select wire:model="deadlineMonth" class="rounded-xl border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface)] text-center text-sm">
                        <option value="">ماه</option>
                        @foreach($months as $num => $name)
                            <option value="{{ $num }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    <select wire:model="deadlineDay" class="rounded-xl border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface)] text-center text-sm">
                        <option value="">روز</option>
                        @foreach(range(1, 31) as $day)
                            <option value="{{ $day }}">{{ $day }}</option>
                        @endforeach
                    </select>
                </div>
                @error('deadline') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
            </div>

            <!-- Assignee -->
            <div>
                <label class="block text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-1">مسئول انجام</label>
                <select
                    wire:model="selectedAssignee"
                    class="w-full rounded-xl border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface)] focus:border-[var(--md-sys-color-primary)] focus:ring-[var(--md-sys-color-primary)] shadow-sm"
                >
                    <option value="">خودم (شخصی)</option>
                    @foreach($staffMembers as $staff)
                        <option value="{{ $staff['id'] }}">{{ $staff['full_name'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="bg-[var(--md-sys-color-surface-container-low)] px-6 py-4 flex flex-row-reverse gap-2">
            <button
                wire:click="createTask"
                class="inline-flex items-center justify-center rounded-xl bg-[var(--md-sys-color-primary)] px-4 py-2 text-sm font-semibold text-[var(--md-sys-color-on-primary)] shadow-sm hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] sm:w-auto transition-all"
            >
                ایجاد وظیفه
            </button>
            <button
                @click="show = false"
                class="inline-flex items-center justify-center rounded-xl bg-[var(--md-sys-color-surface)] px-4 py-2 text-sm font-semibold text-[var(--md-sys-color-on-surface)] shadow-sm ring-1 ring-inset ring-[var(--md-sys-color-outline-variant)] hover:bg-[var(--md-sys-color-surface-container)] sm:mt-0 sm:w-auto transition-all"
            >
                انصراف
            </button>
        </div>
    </div>
</div>
