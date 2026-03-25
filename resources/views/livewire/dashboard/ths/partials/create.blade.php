<form wire:submit.prevent="submitTicket" class="space-y-6 md:space-y-8">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Request Type --}}
        <div class="space-y-2">
            <label for="requestType" class="flex items-center gap-2 text-sm font-medium text-[var(--md-sys-color-on-surface)]">
                <span class="material-symbols-rounded text-lg text-[var(--md-sys-color-primary)]">tune</span>
                نوع درخواست:
            </label>
            <div class="relative">
                <select id="requestType" wire:model.live="ticket.requestType"
                        class="w-full appearance-none bg-[var(--md-sys-color-surface-container-low)] border border-[var(--md-sys-color-outline)] text-[var(--md-sys-color-on-surface)] text-sm rounded-xl focus:ring-[var(--md-sys-color-primary)] focus:border-[var(--md-sys-color-primary)] block p-3 pr-4 shadow-sm transition-colors">
                    <option value="support">پشتیبانی</option>
                    <option value="access">دسترسی</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center px-4 text-[var(--md-sys-color-on-surface-variant)]">
                    <span class="material-symbols-rounded text-xl">expand_more</span>
                </div>
            </div>
            @error('ticket.requestType') <span class="text-xs text-[var(--md-sys-color-error)]">{{ $message }}</span> @enderror
        </div>

        {{-- Request Area --}}
        <div class="space-y-2">
            <label for="requestArea" class="flex items-center gap-2 text-sm font-medium text-[var(--md-sys-color-on-surface)]">
                <span class="material-symbols-rounded text-lg text-[var(--md-sys-color-primary)]">location_on</span>
                حوزه درخواست:
            </label>
            <div class="relative">
                <select id="requestArea" wire:model="ticket.requestArea"
                        class="w-full appearance-none bg-[var(--md-sys-color-surface-container-low)] border border-[var(--md-sys-color-outline)] text-[var(--md-sys-color-on-surface)] text-sm rounded-xl focus:ring-[var(--md-sys-color-primary)] focus:border-[var(--md-sys-color-primary)] block p-3 pr-4 shadow-sm transition-colors">
                    @foreach($requestAreas as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center px-4 text-[var(--md-sys-color-on-surface-variant)]">
                    <span class="material-symbols-rounded text-xl">expand_more</span>
                </div>
            </div>
            @error('ticket.requestArea') <span class="text-xs text-[var(--md-sys-color-error)]">{{ $message }}</span> @enderror
        </div>

        {{-- Priority --}}
        <div class="space-y-2">
            <label for="priority" class="flex items-center gap-2 text-sm font-medium text-[var(--md-sys-color-on-surface)]">
                <span class="material-symbols-rounded text-lg text-[var(--md-sys-color-primary)]">warning</span>
                اولویت:
            </label>
            <div class="relative">
                <select id="priority" wire:model="ticket.priority"
                        class="w-full appearance-none bg-[var(--md-sys-color-surface-container-low)] border border-[var(--md-sys-color-outline)] text-[var(--md-sys-color-on-surface)] text-sm rounded-xl focus:ring-[var(--md-sys-color-primary)] focus:border-[var(--md-sys-color-primary)] block p-3 pr-4 shadow-sm transition-colors">
                    <option value="low">کم</option>
                    <option value="medium">متوسط</option>
                    <option value="high">زیاد</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center px-4 text-[var(--md-sys-color-on-surface-variant)]">
                    <span class="material-symbols-rounded text-xl">expand_more</span>
                </div>
            </div>
            @error('ticket.priority') <span class="text-xs text-[var(--md-sys-color-error)]">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
        {{-- Text Fields --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="space-y-2">
                <label for="subject" class="flex items-center gap-2 text-sm font-medium text-[var(--md-sys-color-on-surface)]">
                    <span class="material-symbols-rounded text-lg text-[var(--md-sys-color-primary)]">label</span>
                    موضوع تیکت:
                </label>
                <input type="text" id="subject" wire:model="ticket.subject"
                       class="w-full bg-[var(--md-sys-color-surface-container-low)] border border-[var(--md-sys-color-outline)] text-[var(--md-sys-color-on-surface)] text-sm rounded-xl focus:ring-[var(--md-sys-color-primary)] focus:border-[var(--md-sys-color-primary)] block p-3 shadow-sm transition-colors placeholder:text-[var(--md-sys-color-on-surface-variant)]/60"
                       placeholder="موضوع را به‌طور خلاصه وارد کنید">
                @error('ticket.subject') <span class="text-xs text-[var(--md-sys-color-error)]">{{ $message }}</span> @enderror
            </div>

            <div class="space-y-2">
                <label for="description" class="flex items-center gap-2 text-sm font-medium text-[var(--md-sys-color-on-surface)]">
                    <span class="material-symbols-rounded text-lg text-[var(--md-sys-color-primary)]">edit_note</span>
                    توضیحات تکمیلی:
                </label>
                <textarea id="description" wire:model="ticket.description"
                          class="w-full bg-[var(--md-sys-color-surface-container-low)] border border-[var(--md-sys-color-outline)] text-[var(--md-sys-color-on-surface)] text-sm rounded-xl focus:ring-[var(--md-sys-color-primary)] focus:border-[var(--md-sys-color-primary)] block p-3 shadow-sm transition-colors placeholder:text-[var(--md-sys-color-on-surface-variant)]/60 min-h-[160px] resize-y"
                          placeholder="توضیحات کامل مشکل یا درخواست خود را وارد کنید..."></textarea>
                @error('ticket.description') <span class="text-xs text-[var(--md-sys-color-error)]">{{ $message }}</span> @enderror
            </div>
        </div>

        {{-- File Uploads --}}
        <div class="space-y-4 bg-[var(--md-sys-color-surface-container-lowest)] border border-[var(--md-sys-color-outline-variant)]/40 p-5 rounded-2xl shadow-sm">
            <label class="flex items-center justify-between text-sm font-medium text-[var(--md-sys-color-on-surface)] border-b border-[var(--md-sys-color-outline-variant)]/30 pb-3">
                <span class="flex items-center gap-2">
                    <span class="material-symbols-rounded text-lg text-[var(--md-sys-color-primary)]">attach_file</span>
                    ضمائم (اختیاری)
                </span>
                <button type="button" wire:click="addFileInput" class="text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] rounded-full p-1 transition-colors" title="افزودن فایل">
                    <span class="material-symbols-rounded text-xl">add</span>
                </button>
            </label>

            <div class="space-y-3 pt-2">
                @foreach ($fileInputs as $index => $key)
                    <div class="relative group" wire:key="upload-wrapper-{{ $key }}">
                        <div x-data="{
                                url: '', name: '', size: '', type: '',
                                initFile(e) {
                                    const file = e.target.files[0];
                                    if (!file) return;
                                    this.name = file.name;
                                    this.size = (file.size / 1024 / 1024).toFixed(2);
                                    this.type = file.type;
                                    this.url = URL.createObjectURL(file);
                                }
                             }"
                             class="flex items-center">
                            <label for="dropzone-file-{{ $key }}"
                                   class="flex-1 flex flex-col items-center justify-center h-20 border-2 border-dashed rounded-xl cursor-pointer transition-colors"
                                   :class="$wire.errors.has('files.{{ $key }}') ? 'border-[var(--md-sys-color-error)] bg-[var(--md-sys-color-error-container)]/10' : 'border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container-low)] hover:bg-[var(--md-sys-color-surface-container)] hover:border-[var(--md-sys-color-primary)]/50'">

                                <div class="flex items-center justify-center w-full px-4 gap-3">
                                    <template x-if="url && type.startsWith('image/')">
                                        <div class="w-12 h-12 rounded-lg overflow-hidden shrink-0 border border-[var(--md-sys-color-outline-variant)]">
                                            <img :src="url" class="w-full h-full object-cover">
                                        </div>
                                    </template>
                                    <template x-if="url && !type.startsWith('image/')">
                                        <div class="w-10 h-10 rounded-lg bg-[var(--md-sys-color-secondary-container)] flex items-center justify-center shrink-0">
                                            <span class="material-symbols-rounded text-[var(--md-sys-color-on-secondary-container)]">description</span>
                                        </div>
                                    </template>
                                    <template x-if="!url">
                                        <div class="flex flex-col items-center">
                                            <span class="material-symbols-rounded text-[var(--md-sys-color-primary)] text-2xl mb-1">cloud_upload</span>
                                            <span class="text-xs font-medium text-[var(--md-sys-color-on-surface-variant)]">آپلود فایل</span>
                                        </div>
                                    </template>

                                    <div x-show="url" class="flex-1 min-w-0">
                                        <p class="text-xs font-bold text-[var(--md-sys-color-on-surface)] truncate" x-text="name"></p>
                                        <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] mt-0.5" x-text="size + ' MB'"></p>
                                    </div>
                                </div>
                                <input id="dropzone-file-{{ $key }}" type="file" class="hidden" wire:model="files.{{ $key }}" @change="initFile" />
                            </label>

                            @if($index > 0)
                                <button type="button" wire:click="removeFileInput('{{ $key }}')" class="absolute -right-2 -top-2 bg-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-error)] rounded-full p-0.5 shadow-sm opacity-0 group-hover:opacity-100 transition-opacity transform scale-90 hover:scale-100">
                                    <span class="material-symbols-rounded text-sm">close</span>
                                </button>
                            @endif
                        </div>
                        @error('files.' . $key) <p class="text-[10px] text-[var(--md-sys-color-error)] mt-1 ml-1">{{ $message }}</p> @enderror
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="flex justify-end pt-4 border-t border-[var(--md-sys-color-outline-variant)]/50 mt-8">
        <button type="submit"
                wire:loading.attr="disabled"
                class="flex items-center gap-2 px-6 py-2.5 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] font-medium text-sm rounded-full hover:bg-opacity-90 shadow-md transition-all active:scale-[0.98] disabled:opacity-70 disabled:cursor-not-allowed group">
            <span wire:loading.remove wire:target="submitTicket">ثبت درخواست</span>
            <span wire:loading wire:target="submitTicket">در حال ارسال...</span>
            <span class="material-symbols-rounded text-[18px] transform rtl:-scale-x-100 group-hover:translate-x-1 transition-transform" wire:loading.remove wire:target="submitTicket">send</span>
            <span class="material-symbols-rounded text-[18px] animate-spin" wire:loading wire:target="submitTicket">progress_activity</span>
        </button>
    </div>
</form>
