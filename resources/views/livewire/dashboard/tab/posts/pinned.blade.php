<aside class="w-full lg:w-1/3 xl:w-2/5 flex-shrink-0 max-h-[70vh] lg:max-h-full flex flex-col overflow-hidden">
    <div class="sticky top-0 z-10 h-full flex flex-col">
        <div class="flex items-center gap-3 mb-4 px-1 animate-pulse">
            <div class="w-8 h-8 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] flex items-center justify-center shadow-sm">
                <span class="material-symbols-rounded text-[14px] font-fill rotate-12">keep</span>
            </div>
            <h2 class="text-[13px] font-bold tracking-wide text-[var(--md-sys-color-on-surface)]">
                سنجاق شده
            </h2>
            <div class="flex-1 h-px bg-[var(--md-sys-color-outline-variant)]/50"></div>
        </div>

        @if($this->pins->isNotEmpty())
            @foreach($this->pins as $pin)
                @php
                    $isFresh = $pin->isFresh();
                    $isSeen = $this->seenIds->has($pin->id);
                @endphp

                <div class="relative group cursor-pointer rounded-[32px] overflow-hidden bg-[var(--md-sys-color-surface)] transition-all duration-[var(--theme-transition-duration)] ease-[var(--theme-transition-easing)] hover:scale-[1.015] flex-grow flex flex-col shadow-sm border border-[var(--md-sys-color-outline-variant)]/20 hover:-translate-y-1 hover:border-[var(--md-sys-color-primary)]/30"
                     wire:click="selectPost({{ $pin->id }})">

                    <div class="absolute top-0 inset-x-0 h-[5px] bg-gradient-to-r from-[var(--md-sys-color-primary)] via-[var(--md-sys-color-tertiary)] to-[var(--md-sys-color-primary)] z-30 transform origin-left scale-x-100 transition-transform duration-500"></div>

                    <div class="relative h-52 lg:h-60 xl:h-64 w-full overflow-hidden shrink-0 border-b border-[var(--md-sys-color-outline-variant)]/30">
                        <img src="{{ $pin->image_url }}"
                             alt="{{ superClean($pin->title, 200) }}"
                             class="w-full h-full object-cover transition-transform duration-700 ease-[var(--theme-transition-easing)] group-hover:scale-105">

                        <div class="absolute top-4 right-4 bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] text-[11px] font-semibold px-3 py-1 rounded-md shadow-sm border border-[var(--md-sys-color-tertiary)]/20 tracking-wide">
                            مهم
                        </div>
                    </div>

                    <div class="p-6 lg:p-8 flex flex-col flex-grow gap-3 transition-colors duration-[var(--theme-transition-duration)] ease-[var(--theme-transition-easing)] bg-gradient-to-b from-[var(--md-sys-color-primary)] via-[var(--md-sys-color-primary)] to-[var(--md-sys-color-on-primary-container)] group-hover:from-[var(--md-sys-color-primary-container)] group-hover:via-[var(--md-sys-color-primary-container)] group-hover:to-[var(--md-sys-color-primary-container)]">

                        <h2 class="text-2xl lg:text-3xl font-bold leading-tight text-[var(--md-sys-color-on-primary)] transition-colors duration-300 group-hover:text-[var(--md-sys-color-on-primary-container)]">
                            {{ superClean($pin->title, 100) }}
                        </h2>

                        <p class="text-sm lg:text-base font-light leading-relaxed line-clamp-3 text-[var(--md-sys-color-on-primary)] group-hover:text-[var(--md-sys-color-on-primary-container)]">
                            {{ superClean($pin->body, 150) }}
                        </p>

                        <div class="flex flex-wrap items-center gap-0 -mr-2 mt-auto pt-4 border-t border-[var(--md-sys-color-outline-variant)]/30">

                            @if($isFresh)
                                <div @class([
                                        'inline-flex items-center gap-1.5 px-3 py-1 rounded-md border shadow-sm transition-all duration-200 scale-75 origin-right',
                                        'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline-variant)]/40' => $isSeen,
                                        'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] border-[var(--md-sys-color-primary)]/30' => ! $isSeen,
                                    ])>
                                    @if($isSeen)
                                        <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-tertiary)]">check_circle</span>
                                        <span class="text-[11px] font-semibold tracking-wide text-[var(--md-sys-color-tertiary)]">دیده شد</span>
                                    @else
                                        <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-tertiary)]">new_releases</span>
                                        <span class="text-[11px] font-semibold tracking-wide text-[var(--md-sys-color-tertiary)]">جدید</span>
                                    @endif
                                </div>
                            @endif

                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md border shadow-sm bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline-variant)]/40 transition-colors duration-200 scale-75 origin-right">
                                <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-primary)]">calendar_today</span>
                                <span dir="rtl" class="text-[11px] font-semibold font-sans">{{ toJalali($pin->created_at, 'j F Y') }}</span>
                            </div>

                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md border shadow-sm bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline-variant)]/40 transition-colors duration-200 scale-75 origin-right">
                                <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-primary)]">person</span>
                                <span class="text-[11px] font-semibold">{{ $pin->user?->name ?? 'ادمین' }}</span>
                            </div>

                        </div>

                    </div>
                </div>
            @endforeach
        @else
            <div class="flex-grow flex items-center justify-center bg-gradient-to-br from-[var(--md-sys-color-surface-container)] to-[var(--md-sys-color-surface-container-low)] rounded-[32px] border border-[var(--md-sys-color-outline-variant)]/40 shadow-inner">
                <x-ui.empty icon="campaign" title="خوش آمدید" variant="welcome">
                    <p class="text-sm leading-relaxed max-w-xs mx-auto">
                        در حال حاضر اعلان مهمی پین نشده است.<br>
                        اخبار جدید را در بخش تازه‌ترین‌ها دنبال کنید.
                    </p>
                </x-ui.empty>
            </div>
        @endif
    </div>
</aside>
