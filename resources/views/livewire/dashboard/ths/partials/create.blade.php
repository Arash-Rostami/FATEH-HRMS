<form wire:submit.prevent="submitTicket" class="space-y-6">

    {{-- ── Section 1: Classification ── --}}
    <div class="space-y-4">
        <x-dashboard.tab.title icon="tune" title="دسته‌بندی تیکت" />

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">

            <x-dashboard.form.select label="نوع درخواست" name="ticket.requestType"
                                     wire:model.live="ticket.requestType" icon="category">
                <option value="support">پشتیبانی</option>
                <option value="access">دسترسی</option>
            </x-dashboard.form.select>

            <x-dashboard.form.select label="حوزه درخواست" name="ticket.requestArea"
                                     wire:model="ticket.requestArea" icon="location_on">
                @foreach($requestAreas as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-dashboard.form.select>

            <div class="space-y-1.5">
                <label class="text-[11px] font-bold uppercase tracking-widest text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-1.5">
                    <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-primary)]">flag</span>
                    اولویت
                </label>
                <div class="grid grid-cols-3 gap-1.5 h-11">
                    @foreach([
                        'low'    => ['کم',    'low_priority', '--md-sys-color-secondary', '--md-sys-color-secondary-container', '--md-sys-color-on-secondary-container', '100%'],
                        'medium' => ['متوسط', 'drag_handle',  '--md-sys-color-tertiary',  '--md-sys-color-tertiary-container',  '--md-sys-color-on-tertiary-container',  '100%'],
                        'high'   => ['زیاد',  'priority_high','--md-sys-color-error',     '--md-sys-color-error',              '--md-sys-color-on-error',               '15%'],
                    ] as $val => [$label, $icon, $border, $bg, $text, $bgOpacity])
                        <label class="cursor-pointer" x-data>
                            <input type="radio" wire:model.live="ticket.priority" value="{{ $val }}" class="sr-only">
                            <div class="h-11 flex flex-col items-center justify-center rounded-xl border text-[10px] font-bold gap-0.5 transition-all hover:border-[var(--md-sys-color-outline)]"
                                 :style="$wire.ticket.priority === '{{ $val }}'
                                     ? 'border-color:var({{ $border }});background-color:color-mix(in srgb,var({{ $bg }}) {{ $bgOpacity }},transparent);color:var({{ $text }})'
                                     : 'border-color:var(--md-sys-color-outline-variant);background-color:var(--md-sys-color-surface-container-low);color:var(--md-sys-color-on-surface-variant)'">
                                <span class="material-symbols-rounded text-[16px]">{{ $icon }}</span>
                                {{ $label }}
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('ticket.priority') <p class="text-[10px] text-[var(--md-sys-color-error)] mt-1">{{ $message }}</p> @enderror
            </div>

        </div>
    </div>

    {{-- ── Section 2: Content + Attachments ── --}}
    <div class="space-y-4">
        <x-dashboard.tab.title icon="edit_note" title="محتوای تیکت" />

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">

            <div class="lg:col-span-3 space-y-5">
                <x-dashboard.form.input label=" موضوع تیکت *" name="ticket.subject"
                                        wire:model="ticket.subject" icon="label"/>
                <x-dashboard.form.textarea label="توضیحات تکمیلی *" name="ticket.description"
                                           wire:model="ticket.description" icon="description" rows="5"/>
            </div>

            <div class="lg:col-span-2 bg-[var(--md-sys-color-surface-variant)]/20 border border-[var(--md-sys-color-outline-variant)]/60 rounded-xl overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-[var(--md-sys-color-outline-variant)]/60">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-rounded text-[var(--md-sys-color-primary)] text-base">attach_file</span>
                        <span class="text-xs font-bold text-[var(--md-sys-color-on-surface)]">ضمائم</span>
                        <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] bg-[var(--md-sys-color-surface-variant)] px-2 py-0.5 rounded-lg">اختیاری</span>
                    </div>
                    <button type="button" wire:click="addFileInput"
                            class="w-7 h-7 flex items-center justify-center rounded-lg text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] active:scale-95 transition-all">
                        <span class="material-symbols-rounded text-lg">add</span>
                    </button>
                </div>

                <div class="p-3 space-y-2 max-h-[240px] overflow-y-auto">
                    @foreach ($ticket->fileInputs as $index => $key)
                        <div class="relative group" wire:key="upload-wrapper-{{ $key }}">
                            <div x-data="{ url:'', name:'', size:'', type:'',
                                           initFile(e) {
                                               const f=e.target.files[0]; if(!f) return;
                                               this.name=f.name; this.size=(f.size/1024/1024).toFixed(2);
                                               this.type=f.type; this.url=URL.createObjectURL(f);
                                           }}"
                                 class="flex items-center gap-2">

                                <label for="dropzone-file-{{ $key }}"
                                       class="flex-1 flex items-center gap-3 h-[52px] px-3 border border-dashed rounded-xl cursor-pointer transition-all"
                                       :class="$wire.errors?.has?.('files.{{ $key }}')
                                           ? 'border-[var(--md-sys-color-error)] bg-[color-mix(in_srgb,var(--md-sys-color-error)_8%,transparent)]'
                                           : 'border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] hover:bg-[var(--md-sys-color-surface-container)] hover:border-[var(--md-sys-color-primary)]'">

                                    <template x-if="url && type.startsWith('image/')">
                                        <div class="w-8 h-8 rounded-lg overflow-hidden shrink-0 border border-[var(--md-sys-color-outline-variant)]">
                                            <img :src="url" class="w-full h-full object-cover">
                                        </div>
                                    </template>
                                    <template x-if="url && !type.startsWith('image/')">
                                        <div class="w-8 h-8 rounded-lg bg-[var(--md-sys-color-secondary-container)] flex items-center justify-center shrink-0">
                                            <span class="material-symbols-rounded text-sm text-[var(--md-sys-color-on-secondary-container)]">description</span>
                                        </div>
                                    </template>
                                    <template x-if="!url">
                                        <div class="w-8 h-8 rounded-lg bg-[var(--md-sys-color-surface-container)] flex items-center justify-center shrink-0">
                                            <span class="material-symbols-rounded text-lg text-[var(--md-sys-color-primary)]">cloud_upload</span>
                                        </div>
                                    </template>

                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-bold text-[var(--md-sys-color-on-surface)] truncate" x-text="name || 'انتخاب فایل'"></p>
                                        <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]" x-show="size" x-text="size + ' MB'"></p>
                                        <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]" x-show="!size">حداکثر ۱۰ مگابایت</p>
                                    </div>

                                    <button type="button" x-show="url"
                                            @click.prevent="url='';name='';size='';type='';
                                                             $el.closest('label').querySelector('input').value='';
                                                             $wire.set('ticket.files.{{ $key }}', null)"
                                            class="shrink-0 w-6 h-6 flex items-center justify-center rounded-lg text-[var(--md-sys-color-error)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-error)_12%,transparent)] transition-colors">
                                        <span class="material-symbols-rounded text-sm">close</span>
                                    </button>

                                    <input id="dropzone-file-{{ $key }}" type="file" class="hidden"
                                           wire:model="ticket.files.{{ $key }}" @change="initFile" />
                                </label>

                                @if($index > 0)
                                    <button type="button" wire:click="removeFileInput('{{ $key }}')"
                                            class="shrink-0 w-7 h-7 flex items-center justify-center rounded-lg bg-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-error)] opacity-0 group-hover:opacity-100 active:scale-95 transition-all shadow-sm">
                                        <span class="material-symbols-rounded text-sm">close</span>
                                    </button>
                                @endif
                            </div>
                            @error('ticket.files.' . $key) <p class="text-[10px] text-[var(--md-sys-color-error)] mt-1 pr-1">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>

    {{-- ── Actions ── --}}
    <div class="flex items-center justify-between gap-3 pt-2 border-t border-[var(--md-sys-color-outline-variant)]/50">
        <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] hidden sm:flex items-center gap-1.5">
            <span class="material-symbols-rounded text-[13px] text-[var(--md-sys-color-primary)]">info</span>
            فیلدهای  * (ضروری) را تکمیل کنید
        </p>
        <x-dashboard.button.submit
            target="submitTicket"
            text="ثبت درخواست"
            loading-text="در حال ارسال..."
            icon="send"
            class="px-6 h-11 font-bold rounded-xl shadow-md mr-auto"
        />
    </div>

</form>
