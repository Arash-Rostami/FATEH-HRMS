<div class="flex-1 overflow-y-auto px-4 md:px-8 py-6 msg-scrollbar">
    <div class="max-w-3xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-rounded text-[22px] text-[var(--md-sys-color-primary)]">add_box</span>
                <div>
                    <h2 class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">ساخت کانال جدید</h2>
                    <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]"
                       x-text="$wire.create.type === 'private' ? 'کانال خصوصی — فقط با دعوت مدیر' : 'کانال عمومی — همه می‌توانند پیوستن'"></p>
                </div>
            </div>
            <button x-on:click="closeCreate()" aria-label="بستن"
                    class="w-8 h-8 rounded-lg flex items-center justify-center bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:brightness-95 active:scale-90 transition-all">
                <span class="material-symbols-rounded text-[18px]">close</span>
            </button>
        </div>

        <div class="bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)] rounded-xl p-5 space-y-4">
            <div>
                <label for="create-name" class="block text-[11px] font-semibold mb-1.5 text-[var(--md-sys-color-on-surface-variant)]">نام کانال</label>
                <input id="create-name" type="text" wire:model="create.name" maxlength="100" autocomplete="off"
                       placeholder="مثلاً اطلاعیه‌های فروش"
                       class="md3-input peer w-full rounded-xl text-sm outline-none focus:ring-2 px-4 h-11 bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] border border-[var(--md-sys-color-outline-variant)]/50" />
            </div>

            <div>
                <label for="create-desc" class="block text-[11px] font-semibold mb-1.5 text-[var(--md-sys-color-on-surface-variant)]">توضیحات (اختیاری)</label>
                <textarea id="create-desc" rows="3" wire:model="create.description" maxlength="500" autocomplete="off"
                          placeholder="توضیحی کوتاه درباره موضوع کانال"
                          class="md3-input peer w-full rounded-xl text-sm outline-none focus:ring-2 px-4 py-2.5 bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] border border-[var(--md-sys-color-outline-variant)]/50 resize-none"></textarea>
            </div>

            <div>
                <label class="block text-[11px] font-semibold mb-1.5 text-[var(--md-sys-color-on-surface-variant)]">نوع کانال</label>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" x-on:click="$wire.set('create.type', 'open')"
                            @class([
                                'flex items-center gap-2 px-3 py-2.5 rounded-xl text-[12px] font-semibold transition-all border',
                                'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] border-[var(--md-sys-color-primary)]' => $create->type === 'open',
                                'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline-variant)]/50' => $create->type !== 'open',
                            ])>
                        <span class="material-symbols-rounded text-[18px]">campaign</span>
                        عمومی
                    </button>
                    <button type="button" x-on:click="$wire.set('create.type', 'private')"
                            @class([
                                'flex items-center gap-2 px-3 py-2.5 rounded-xl text-[12px] font-semibold transition-all border',
                                'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] border-[var(--md-sys-color-primary)]' => $create->type === 'private',
                                'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline-variant)]/50' => $create->type !== 'private',
                            ])>
                        <span class="material-symbols-rounded text-[18px]">lock</span>
                        خصوصی
                    </button>
                </div>
            </div>

            <div x-data="{ memberQuery: '' }">
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-[11px] font-semibold text-[var(--md-sys-color-on-surface-variant)]">
                        اعضای اولیه
                        <span class="text-[var(--md-sys-color-primary)] font-bold"
                              x-text="$wire.createRecipientIds.length ? '(' + $wire.createRecipientIds.length + ' نفر)' : ''"></span>
                    </label>
                </div>
                <p class="text-[10px] mb-2 text-[var(--md-sys-color-on-surface-variant)]"
                   x-text="$wire.create.type === 'private' ? 'تنها اعضای انتخاب‌شده به کانال خصوصی دسترسی دارند — دیگران نمی‌توانند خودشان پیوستن.' : 'اختیاری — در کانال عمومی دیگران می‌توانند از طریق مرور پیوستن.'"></p>

                @if(count($this->memberCandidates) > 0)
                    <div class="relative mb-2">
                        <span class="material-symbols-rounded absolute right-3 top-1/2 -translate-y-1/2 text-[16px] text-[var(--md-sys-color-on-surface-variant)] pointer-events-none">search</span>
                        <input type="text" x-model="memberQuery" placeholder="جستجوی کاربر..."
                               class="md3-input w-full rounded-xl text-sm outline-none focus:ring-2 pr-9 h-10 bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] border border-[var(--md-sys-color-outline-variant)]/50">
                    </div>

                    <div class="max-h-56 overflow-y-auto custom-scrollbar rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 divide-y divide-[var(--md-sys-color-outline-variant)]/20">
                        @foreach($this->memberCandidates as $u)
                            <label x-show="memberQuery === '' || @js($u['name'] ?? '').toLowerCase().includes(memberQuery.toLowerCase())"
                                   class="flex items-center gap-3 px-4 py-3 hover:bg-[var(--md-sys-color-surface-container)]/60 cursor-pointer transition-colors">
                                <input type="checkbox" value="{{ $u['id'] }}" wire:model="createRecipientIds"
                                       class="w-4 h-4 rounded text-[var(--md-sys-color-primary)] border-[var(--md-sys-color-outline-variant)] focus:ring-[var(--md-sys-color-primary)]">
                                <span class="text-sm font-medium text-[var(--md-sys-color-on-surface)]">{{ $u['name'] }}</span>
                            </label>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 text-[var(--md-sys-color-on-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/40 rounded-2xl">
                        <span class="material-symbols-rounded text-4xl mb-2 block opacity-40">group</span>
                        <p class="text-sm">کاربر فعال دیگری برای افزودن وجود ندارد.</p>
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-2 pt-1">
                <button x-on:click="createChannel()" wire:loading.attr="disabled" wire:target="createChannel"
                        class="flex-shrink-0 px-5 py-2.5 rounded-lg text-[12px] font-semibold transition-all hover:brightness-110 active:scale-95 disabled:opacity-50 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]">
                    <span wire:loading.remove wire:target="createChannel">ساخت کانال</span>
                    <span wire:loading wire:target="createChannel" class="material-symbols-rounded text-[14px] animate-spin">progress_activity</span>
                </button>
                <button x-on:click="closeCreate()"
                        class="px-4 py-2.5 rounded-lg text-[12px] font-semibold transition-all hover:brightness-95 active:scale-95 bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]">
                    انصراف
                </button>
            </div>
        </div>
    </div>
</div>