<x-dashboard.modal.slideover name="edit-task-modal" title="ویرایش وظیفه" icon="edit_document">
    <form wire:submit="updateTask" class="flex flex-col h-full bg-[var(--md-sys-color-surface)]" dir="rtl">
        <div class="flex-1 overflow-y-auto p-4 md:p-6 space-y-6 custom-scrollbar">
            <div>
                <x-dashboard.form.input label="عنوان وظیفه" name="editingTask.title" wire:model="editingTask.title" icon="title" placeholder="مثال: بررسی گزارش ماهیانه فروش" required />
            </div>

            <div>
                <x-dashboard.form.textarea label="توضیحات (اختیاری)" name="editingTask.description" wire:model="editingTask.description" icon="description" placeholder="جزئیات بیشتر درباره این وظیفه..." rows="4" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-dashboard.form.select label="وضعیت" name="editingTask.status" wire:model="editingTask.status" icon="pending_actions" required>
                    <option value="todo">برای انجام</option>
                    <option value="in-progress">در حال انجام</option>
                    <option value="done">انجام شده</option>
                </x-dashboard.form.select>

                <div x-data="{
                        open: false,
                        search: '',
                        get users() {
                            return @js($delegationUsers->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'avatar' => $u->profile_photo_url])->values()->all());
                        },
                        get filteredUsers() {
                            if (this.search === '') return this.users;
                            return this.users.filter(u => u.name.includes(this.search));
                        },
                        get selectedUser() {
                            return this.users.find(u => u.id == $wire.get('editingTask.assigned_to'));
                        }
                    }" class="relative">
                    <label class="block text-sm font-medium text-[var(--md-sys-color-on-surface)] mb-1.5 px-1">واگذاری به (اختیاری)</label>
                    <div @click="open = !open" @click.away="open = false" class="flex items-center justify-between w-full h-11 px-3 bg-[var(--md-sys-color-surface-variant)]/30 border border-[var(--md-sys-color-outline-variant)] rounded-xl cursor-pointer hover:border-[var(--md-sys-color-primary)] transition-colors">
                        <div class="flex items-center gap-2 overflow-hidden">
                            <template x-if="selectedUser">
                                <div class="flex items-center gap-2">
                                    <img :src="selectedUser.avatar" class="w-6 h-6 rounded-full object-cover">
                                    <span x-text="selectedUser.name" class="text-sm text-[var(--md-sys-color-on-surface)] truncate"></span>
                                </div>
                            </template>
                            <template x-if="!selectedUser">
                                <span class="text-sm text-[var(--md-sys-color-on-surface-variant)]">بدون واگذاری (شخصی)</span>
                            </template>
                        </div>
                        <span class="material-symbols-rounded text-[var(--md-sys-color-on-surface-variant)] transition-transform duration-200" :class="open ? 'rotate-180' : ''">expand_more</span>
                    </div>

                    <div x-show="open" x-transition x-cloak class="absolute z-50 w-full mt-1 bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] rounded-xl shadow-lg overflow-hidden flex flex-col max-h-60">
                        <div class="p-2 border-b border-[var(--md-sys-color-outline-variant)]">
                            <div class="relative">
                                <span class="material-symbols-rounded absolute right-2.5 top-1/2 -translate-y-1/2 text-[var(--md-sys-color-on-surface-variant)] text-sm">search</span>
                                <input type="text" x-model="search" placeholder="جستجوی همکار..." class="w-full pl-3 pr-8 py-1.5 bg-[var(--md-sys-color-surface-variant)]/50 border-none rounded-lg text-sm focus:ring-1 focus:ring-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-surface)]" @click.stop>
                            </div>
                        </div>
                        <ul class="flex-1 overflow-y-auto py-1 custom-scrollbar">
                            <li @click="$wire.set('editingTask.assigned_to', null); open = false; search = ''" class="flex items-center gap-2 px-3 py-2 cursor-pointer hover:bg-[var(--md-sys-color-surface-variant)]/50 transition-colors" :class="!selectedUser ? 'bg-[var(--md-sys-color-primary)]/10 text-[var(--md-sys-color-primary)]' : 'text-[var(--md-sys-color-on-surface)]'">
                                <div class="w-8 h-8 rounded-full bg-[var(--md-sys-color-surface-variant)] flex items-center justify-center">
                                    <span class="material-symbols-rounded text-sm">person_off</span>
                                </div>
                                <span class="text-sm font-medium">بدون واگذاری (شخصی)</span>
                            </li>
                            <template x-for="user in filteredUsers" :key="user.id">
                                <li @click="$wire.set('editingTask.assigned_to', user.id); open = false; search = ''" class="flex items-center gap-2 px-3 py-2 cursor-pointer hover:bg-[var(--md-sys-color-surface-variant)]/50 transition-colors" :class="selectedUser?.id == user.id ? 'bg-[var(--md-sys-color-primary)]/10 text-[var(--md-sys-color-primary)]' : 'text-[var(--md-sys-color-on-surface)]'">
                                    <img :src="user.avatar" class="w-8 h-8 rounded-full object-cover">
                                    <span x-text="user.name" class="text-sm font-medium"></span>
                                    <span class="material-symbols-rounded mr-auto text-sm" x-show="selectedUser?.id == user.id">check</span>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
            </div>

            <div>
                <x-dashboard.form.input type="datetime-local" label="مهلت انجام (اختیاری)" name="editingTask.deadline" wire:model="editingTask.deadline" icon="event" />
                <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] mt-1 px-1">تاریخ و ساعت پایان کار را مشخص کنید.</p>
            </div>
        </div>

        <div class="p-4 border-t border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] flex gap-3">
            <x-dashboard.form.button type="submit" variant="primary" icon="save" loading="updateTask" class="flex-1">ذخیره تغییرات</x-dashboard.form.button>
            <x-dashboard.form.button type="button" variant="outline" @click="$dispatch('close-modal', 'edit-task-modal')">انصراف</x-dashboard.form.button>
        </div>
    </form>
</x-dashboard.modal.slideover>
