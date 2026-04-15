@if ($ticketToRate)
    <div class="bg-[var(--md-sys-color-primary-container)]/10 p-6 sm:p-8 rounded-2xl shadow-sm max-w-2xl mx-auto relative overflow-hidden">
        {{-- Decorative background element --}}
        <div class="absolute -top-10 -right-10 w-32 h-32 bg-[var(--md-sys-color-primary)] opacity-5 rounded-full "></div>
        <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-[var(--md-sys-color-secondary)] opacity-5 rounded-full"></div>

        <div class="relative z-10 flex flex-col items-center text-center space-y-6">
            <div class="w-16 h-16 bg-[var(--md-sys-color-primary-container)] rounded-full flex items-center justify-center text-[var(--md-sys-color-on-primary-container)] mb-2 shadow-inner">
                <span class="material-symbols-rounded text-3xl">task_alt</span>
            </div>

            <div>
                <h3 class="text-xl font-bold text-[var(--md-sys-color-on-surface)] mb-2 tracking-tight">ارزیابی عملکرد پشتیبانی</h3>
                <p class="text-sm text-[var(--md-sys-color-on-surface-variant)] leading-relaxed max-w-md mx-auto">
                    تیکت <span class="font-mono font-bold text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-primary-container)]/30 px-1.5 py-0.5 rounded-md" dir="ltr">#{{ str_pad($ticketToRate->id, 4, '0', STR_PAD_LEFT) }}</span> با موضوع <span class="font-medium text-[var(--md-sys-color-on-surface)]">"{{ Str::limit($ticketToRate->request_subject, 40) }}"</span> بسته شده است. لطفا میزان رضایت خود را اعلام نمایید.
                </p>
            </div>

            <form wire:submit.prevent="submitRating" class="w-full space-y-8 mt-4">
                {{-- Star Rating Interaction --}}
                <div class="space-y-4" x-data="{
                        hoverRating: 0,
                        currentRating: @entangle('rating.score'),
                        setHover(val) { this.hoverRating = val; },
                        resetHover() { this.hoverRating = 0; },
                        rate(val) { this.currentRating = val; $wire.rate(val); }
                    }">
                    <label class="block text-sm font-semibold text-[var(--md-sys-color-on-surface)] mb-4">
                        امتیاز شما به نحوه رسیدگی:
                    </label>

                    <div class="flex items-center justify-center gap-2 sm:gap-4 ltr-direction flex-row-reverse" @mouseleave="resetHover()">
                        @for($i = 5; $i >= 1; $i--)
                            <button type="button"
                                    @mouseover="setHover({{ $i }})"
                                    @click="rate({{ $i }})"
                                    class="relative p-1 transition-all duration-300 transform outline-none rounded-full focus:ring-4 focus:ring-[var(--md-sys-color-primary)]/20"
                                    :class="(hoverRating >= {{ $i }} || (!hoverRating && currentRating >= {{ $i }})) ? 'scale-110 drop-shadow-md' : 'scale-100 opacity-60 hover:opacity-100'">

                                <span class="material-symbols-rounded text-4xl sm:text-5xl transition-colors duration-300"
                                      :class="(hoverRating >= {{ $i }} || (!hoverRating && currentRating >= {{ $i }}))
                                            ? 'text-[#FFD700] font-variation-fill'
                                            : 'text-[var(--md-sys-color-outline-variant)]'">
                                    star
                                </span>
                            </button>
                        @endfor
                    </div>
                    @error('rating.score')
                        <p class="text-[11px] text-[var(--md-sys-color-error)] mt-2 font-medium bg-[var(--md-sys-color-error-container)] inline-block px-3 py-1 rounded-full">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Comments Textarea --}}
                <div class="space-y-2 text-right">
                    <label for="satisfactionComment" class="flex items-center gap-2 text-sm font-medium text-[var(--md-sys-color-on-surface)] ml-auto">
                        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">chat</span>
                        نظرات پیشنهادی (اختیاری):
                    </label>
                    <textarea id="satisfactionComment" wire:model="rating.comment"
                              class="w-full bg-[var(--md-sys-color-surface-container-lowest)] border border-[var(--md-sys-color-outline)] text-[var(--md-sys-color-on-surface)] text-sm rounded-xl focus:ring-[var(--md-sys-color-primary)] focus:border-[var(--md-sys-color-primary)] block p-4 shadow-inner transition-colors placeholder:text-[var(--md-sys-color-on-surface-variant)]/50 min-h-[120px] resize-y"
                              placeholder="چگونه می‌توانیم خدمات خود را بهبود بخشیم؟"></textarea>
                    @error('rating.comment') <span class="text-[11px] text-[var(--md-sys-color-error)]">{{ $message }}</span> @enderror
                </div>

                <div class="pt-2 border-t border-[var(--md-sys-color-outline-variant)]/40">
                    <x-ui.buttons.submit
                        target="submitRating"
                        text="ثبت بازخورد"
                        loading-text="در حال ثبت..."
                        icon="thumb_up"
                        icon-size="text-[20px]"
                        class="w-full sm:w-auto px-8 py-3 font-bold rounded-full shadow-lg hover:shadow-xl mx-auto"
                    />
                </div>
            </form>
        </div>
    </div>
@endif
