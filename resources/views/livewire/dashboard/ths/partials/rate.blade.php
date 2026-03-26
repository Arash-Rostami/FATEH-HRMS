<div class="space-y-8 animate-[fade-in-up_0.4s_ease-out]">
    @if($ticketToRate)
        <div class="bg-[var(--md-sys-color-surface-container-lowest)] border border-[var(--md-sys-color-outline-variant)]/40 p-6 md:p-8 rounded-3xl shadow-[0_8px_32px_rgba(0,0,0,0.06)] relative overflow-hidden">
             <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-[#FFD700] to-[var(--md-sys-color-primary)]"></div>

            <div class="text-center mb-8">
                 <div class="w-16 h-16 rounded-full bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center mx-auto mb-4 shadow-sm">
                    <span class="material-symbols-rounded text-3xl">rate_review</span>
                 </div>
                <h3 class="text-xl md:text-2xl font-bold text-[var(--md-sys-color-on-surface)]">ارزیابی عملکرد پشتیبانی</h3>
                <p class="text-sm text-[var(--md-sys-color-on-surface-variant)] mt-2 font-medium tracking-wide">
                    تیکت شماره
                    <span class="font-mono bg-[var(--md-sys-color-surface-container)] px-2 py-0.5 rounded-md text-[var(--md-sys-color-on-surface)] inline-flex flex-row-reverse mx-1 rtl:-scale-x-100 border border-[var(--md-sys-color-outline-variant)]/50" dir="ltr">
                        {{ $this->getFormattedTicketId($ticketToRate) }}
                    </span>
                    بسته شده است.
                </p>
                <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]/80 mt-1 max-w-lg mx-auto leading-relaxed">
                    لطفاً میزان رضایت خود را از نحوه پاسخگویی و رفع مشکل ثبت کنید. بازخورد شما به بهبود خدمات ما کمک می‌کند.
                </p>
            </div>

            <div class="max-w-xl mx-auto space-y-8">
                {{-- Stars Rating --}}
                <div class="flex flex-col items-center">
                    <label class="text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-4 flex items-center gap-2">
                        <span class="material-symbols-rounded text-lg text-[#FFD700]">star</span>
                        امتیاز شما (از ۱ تا ۵):
                    </label>

                    <div x-data="{
                            hoverRating: 0,
                            selectedRating: @entangle('ratingScore'),
                            setRating(r) { this.selectedRating = r; $wire.set('ratingScore', r) },
                            starClasses(r) {
                                let isActive = this.hoverRating ? r <= this.hoverRating : r <= this.selectedRating;
                                return isActive ? 'text-[#FFD700] font-variation-fill scale-110 drop-shadow-md' : 'text-[var(--md-sys-color-outline-variant)] opacity-50 scale-100 hover:text-[#FFD700]/70';
                            }
                        }"
                        class="flex justify-center gap-3 ltr-direction flex-row-reverse" @mouseleave="hoverRating = 0">

                        @for ($i = 5; $i >= 1; $i--)
                            <button type="button"
                                    @mouseover="hoverRating = {{ $i }}"
                                    @click="setRating({{ $i }})"
                                    class="p-2 transition-all duration-200 focus:outline-none outline-none group">
                                <span class="material-symbols-rounded text-4xl md:text-5xl transition-all duration-300 transform group-hover:-translate-y-1"
                                      :class="starClasses({{ $i }})">star</span>
                            </button>
                        @endfor
                    </div>
                    @error('ratingScore') <span class="text-xs font-bold text-[var(--md-sys-color-error)] mt-3 bg-[var(--md-sys-color-error-container)]/30 px-3 py-1 rounded-full">{{ $message }}</span> @enderror
                </div>

                {{-- Comment --}}
                <div class="space-y-3 bg-[var(--md-sys-color-surface-container-low)] p-5 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30 shadow-inner">
                    <label for="ratingComment" class="flex items-center gap-2 text-sm font-bold text-[var(--md-sys-color-on-surface)]">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">chat</span>
                        توضیحات تکمیلی (اختیاری):
                    </label>
                    <textarea id="ratingComment" wire:model="ratingComment"
                              class="w-full bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline)] text-[var(--md-sys-color-on-surface)] text-sm rounded-xl focus:ring-2 focus:ring-[var(--md-sys-color-primary)] focus:border-[var(--md-sys-color-primary)] block p-4 shadow-sm transition-all hover:border-[var(--md-sys-color-primary)]/50 placeholder:text-[var(--md-sys-color-on-surface-variant)]/50 min-h-[120px] resize-y"
                              placeholder="نقاط قوت، ضعف یا پیشنهادات خود را درباره این تیکت بنویسید..."></textarea>
                    @error('ratingComment') <span class="text-xs font-bold text-[var(--md-sys-color-error)] block mt-1 ml-1">{{ $message }}</span> @enderror
                </div>

                {{-- Actions --}}
                <div class="flex justify-end gap-3 pt-6 border-t border-[var(--md-sys-color-outline-variant)]/30 mt-8">
                    <button type="button" wire:click="$set('ticketToRate', null); $set('activeTab', 'log');"
                            class="px-6 py-2.5 rounded-xl text-sm font-bold text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] transition-all outline-none">
                        انصراف
                    </button>
                    <button type="button" wire:click="submitRating"
                            wire:loading.attr="disabled"
                            class="flex items-center gap-2 px-8 py-2.5 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] font-bold text-sm rounded-xl shadow-[0_4px_12px_rgba(0,0,0,0.15)] hover:shadow-[0_6px_16px_rgba(0,0,0,0.2)] hover:bg-opacity-90 transition-all duration-200 active:scale-[0.98] disabled:opacity-70 disabled:cursor-not-allowed outline-none focus:ring-4 focus:ring-[var(--md-sys-color-primary)]/30">
                        <span wire:loading.remove wire:target="submitRating">ثبت ارزیابی</span>
                        <span wire:loading wire:target="submitRating">در حال ثبت...</span>
                        <span class="material-symbols-rounded text-lg transition-transform" wire:loading.remove wire:target="submitRating">done</span>
                        <span class="material-symbols-rounded text-lg animate-spin" wire:loading wire:target="submitRating">progress_activity</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
