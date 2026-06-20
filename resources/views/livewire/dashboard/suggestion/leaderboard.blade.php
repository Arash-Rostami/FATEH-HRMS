@if($this->topContributors->count() > 0)
    <div x-data="{ open: false }" class="fixed right-0 top-[20%] z-[60] flex items-start">
        <button @click="open = true"
                class="bg-gradient-to-b from-[var(--md-sys-color-surface-variant)] to-[var(--md-sys-color-surface)] border-y border-l border-[var(--md-sys-color-outline-variant)]/50 shadow-md p-3 rounded-l-2xl flex flex-col items-center justify-center transition-all duration-300 hover:pr-4"
                x-show="!open"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-x-full opacity-0"
                x-transition:enter-end="translate-x-0 opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-x-0 opacity-100"
                x-transition:leave-end="translate-x-full opacity-0">
            <span class="material-symbols-rounded text-2xl text-[var(--md-sys-color-primary)]">workspace_premium</span>
        </button>

        <div
            class="overflow-hidden transition-all duration-300 ease-in-out bg-gradient-to-r from-[var(--md-sys-color-surface-variant)] to-[var(--md-sys-color-surface)] border-[var(--md-sys-color-outline-variant)]/50 shadow-2xl rounded-l-2xl flex flex-col"
            :class="open ? 'w-[320px] max-w-[85vw] opacity-100 border-y border-l' : 'w-0 opacity-0 border-none'">
            <div class="w-[320px] max-w-[85vw] p-4 flex flex-col gap-4">
                <div class="flex items-center justify-between text-[var(--md-sys-color-on-surface)]">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-rounded text-xl text-[var(--md-sys-color-primary)]">workspace_premium</span>
                        <h3 class="font-bold text-sm">برترین مشارکت‌کنندگان</h3>
                    </div>
                    <button @click="open = false"
                            class="text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-primary)] transition-colors rounded-lg p-1 bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/30 flex items-center justify-center">
                        <span class="material-symbols-rounded text-lg">close</span>
                    </button>
                </div>

                <div class="flex flex-col gap-2 max-h-[60vh] overflow-y-auto pr-1" style="scrollbar-width: thin;">
                    @foreach($this->topContributors as $index => $contributor)
                        <div @class([
                            'flex items-center justify-between text-xs p-2 rounded-xl border transition-all duration-300',
                            'bg-gradient-to-r from-amber-100 via-yellow-50 to-amber-200 border-amber-300/80 hover:from-amber-200 hover:via-yellow-100 hover:to-amber-300 text-amber-950 shadow-[0_1px_0_rgba(255,255,255,0.75)_inset,0_6px_18px_rgba(180,120,20,0.12)]' => $index === 0,
                            'bg-gradient-to-r from-slate-100 via-stone-50 to-zinc-200 border-slate-300/80 hover:from-slate-200 hover:via-stone-100 hover:to-zinc-300 text-slate-950 shadow-[0_1px_0_rgba(255,255,255,0.8)_inset,0_6px_18px_rgba(90,95,110,0.10)]' => $index === 1,
                            'bg-gradient-to-r from-orange-100 via-amber-50 to-stone-200 border-orange-300/80 hover:from-orange-200 hover:via-amber-100 hover:to-stone-300 text-orange-950 shadow-[0_1px_0_rgba(255,255,255,0.75)_inset,0_6px_18px_rgba(170,100,40,0.12)]' => $index === 2,
                            'bg-[var(--md-sys-color-surface)] border-[var(--md-sys-color-outline-variant)]/30 hover:bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)]' => $index > 2,
                        ])>
                            <div class="flex items-center gap-2">
                            <span
                                @class([
                                        'flex items-center justify-center w-6 h-6 rounded-lg font-bold text-[10px] shrink-0 shadow-sm',
                                        'bg-gradient-to-br from-[#F8E7A1] via-[#D4AF37] to-[#8C6A11] text-[#2A1B00] ring-1 ring-[#F6E7B8]' => $index === 0,
                                        'bg-gradient-to-br from-[#F8F9FA] via-[#C0C7D1] to-[#7A8594] text-[#111827] ring-1 ring-[#EEF1F4]' => $index === 1,
                                        'bg-gradient-to-br from-[#F4D2A7] via-[#CD7F32] to-[#7A4316] text-[#2A1306] ring-1 ring-[#F1D7B7]' => $index === 2,
                                        'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/50 shadow-none' => $index > 2,
                                    ])>
                                        {{ $index + 1 }}
                           </span>
                                <span class="font-medium truncate max-w-[120px]">
                                    {{ $contributor->user->name ?? 'کاربر ناشناس' }}
                                </span>
                            </div>
                            <div @class([
                                'flex items-center gap-3 text-[10px] shrink-0',
                                'text-amber-900' => $index === 0,
                                'text-slate-900' => $index === 1,
                                'text-orange-900' => $index === 2,
                                'text-[var(--md-sys-color-on-surface-variant)]' => $index > 2,
                            ])>
                                <span class="flex items-center gap-1 cursor-help" title="مجموع پیشنهادات">
                                    <span class="material-symbols-rounded !text-[14px]">lightbulb</span>
                                    {{ $contributor->total_suggestions }}
                                </span>
                                <span @class([
                                    'flex items-center gap-1 cursor-help',
                                    'text-[var(--md-sys-color-primary)]' => $index > 2,
                                ]) title="پیشنهادات پذیرفته شده">
                                    <span class="material-symbols-rounded !text-[14px]">check_circle</span>
                                    {{ $contributor->accepted_suggestions ?? 0 }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endif
