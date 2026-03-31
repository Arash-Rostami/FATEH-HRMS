<div class="@container w-full" dir="rtl">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 pb-12">
        @forelse($this->ads as $ad)
            <div wire:key="ad-{{ $ad->id }}"
                 x-data="{ showBack: false }"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="group relative h-[360px] perspective-1000 cursor-pointer w-full max-w-[400px] mx-auto"
                 @click="showBack = !showBack"
                 @mouseenter="setTimeout(() => { if (!showBack) showBack = true }, 600)"
                 @mouseleave="setTimeout(() => { if (showBack) showBack = false }, 200)">

                <div class="w-full h-full transition-transform duration-700 preserve-3d"
                     :class="{ 'rotate-y-180': showBack }">

                    {{-- Front Side --}}
                    <div class="absolute inset-0 backface-hidden flex flex-col items-center bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/40 shadow-sm rounded-2xl p-6 overflow-hidden hover:shadow-md transition-shadow">
                        @php
                            $avatarDetails = match($ad->gender) {
                                'Male'   => ['icon' => 'face_6', 'color' => 'blue', 'title' => 'آقایان'],
                                'Female' => ['icon' => 'face_3', 'color' => 'pink', 'title' => 'خانم‌ها'],
                                default  => ['icon' => 'group', 'color' => 'emerald', 'title' => 'همه'],
                            };
                        @endphp

                        <div class="relative w-20 h-20 mb-4 rounded-full bg-{{ $avatarDetails['color'] }}-100/50 flex items-center justify-center border-4 border-[var(--md-sys-color-surface)] shadow-sm ring-2 ring-{{ $avatarDetails['color'] }}-500/20 group-hover:scale-105 transition-transform duration-500">
                            <span class="material-symbols-rounded text-4xl text-{{ $avatarDetails['color'] }}-600">{{ $avatarDetails['icon'] }}</span>
                            <div class="absolute -bottom-2 px-2 py-0.5 rounded-full bg-[var(--md-sys-color-surface-container-highest)] border border-[var(--md-sys-color-outline-variant)] shadow-sm text-[10px] font-bold text-[var(--md-sys-color-on-surface)]" title="جنسیت مورد نیاز">
                                {{ $avatarDetails['title'] }}
                            </div>
                        </div>

                        <h2 class="text-lg font-bold text-[var(--md-sys-color-on-surface)] text-center mb-4 line-clamp-2">
                            {!! strip_tags($ad->position) !!}
                        </h2>

                        <div class="w-full bg-[var(--md-sys-color-surface-container-lowest)] rounded-xl p-3 flex-1 flex flex-col justify-start border border-[var(--md-sys-color-surface-container-high)]">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="material-symbols-rounded text-sm text-[var(--md-sys-color-primary)]">school</span>
                                <h4 class="text-xs font-bold text-[var(--md-sys-color-on-surface)]">مدرک تحصیلی:</h4>
                            </div>
                            <p class="text-xs leading-relaxed text-[var(--md-sys-color-on-surface-variant)] text-justify line-clamp-4">
                                {!! strip_tags($ad->certificate) !!}
                            </p>
                        </div>

                        <div class="mt-4 flex items-center gap-1 text-[10px] text-[var(--md-sys-color-outline)]">
                            <span class="material-symbols-rounded text-[14px]">touch_app</span>
                            <span>برای جزئیات کلیک کنید</span>
                        </div>
                    </div>

                    {{-- Back Side --}}
                    <div class="absolute inset-0 backface-hidden rotate-y-180 flex flex-col bg-[var(--md-sys-color-surface-container-low)] border border-[var(--md-sys-color-outline-variant)]/60 shadow-lg rounded-2xl p-5 overflow-hidden">

                        <div class="flex-1 overflow-y-auto custom-scrollbar pr-1 pb-2 space-y-4">
                            <div>
                                <div class="flex items-center gap-2 mb-1.5 pb-1 border-b border-[var(--md-sys-color-outline-variant)]/30">
                                    <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-tertiary)]">work_history</span>
                                    <h4 class="text-xs font-bold text-[var(--md-sys-color-on-surface)]">سابقه کار:</h4>
                                </div>
                                <p class="text-[11px] leading-relaxed text-[var(--md-sys-color-on-surface-variant)] text-justify">
                                    {!! strip_tags($ad->experience) !!}
                                </p>
                            </div>

                            <div>
                                <div class="flex items-center gap-2 mb-1.5 pb-1 border-b border-[var(--md-sys-color-outline-variant)]/30">
                                    <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-secondary)]">psychology</span>
                                    <h4 class="text-xs font-bold text-[var(--md-sys-color-on-surface)]">مهارت‌ها:</h4>
                                </div>
                                <p class="text-[11px] leading-relaxed text-[var(--md-sys-color-on-surface-variant)] text-justify">
                                    {!! strip_tags($ad->skill) !!}
                                </p>
                            </div>
                        </div>

                        <div class="pt-3 mt-auto border-t border-[var(--md-sys-color-outline-variant)] flex items-center justify-between gap-2 bg-[var(--md-sys-color-surface-container-low)] relative z-10">
                            <input type="text" class="flex-1 bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline)] rounded-lg px-3 py-2 text-[10px] text-[var(--md-sys-color-on-surface)] truncate focus:outline-none focus:border-[var(--md-sys-color-primary)] transition-colors" readonly value="{!! strip_tags($ad->link) !!}" @click.stop="$el.select()">

                            <button @click.stop="navigator.clipboard.writeText('{!! strip_tags($ad->link) !!}'); $dispatch('toast', { message: 'لینک کپی شد', type: 'success' })"
                                    class="shrink-0 flex items-center justify-center w-9 h-9 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] rounded-lg hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
                                    title="کپی لینک">
                                <span class="material-symbols-rounded text-[18px]">content_copy</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full flex flex-col items-center justify-center py-20 gap-4 text-[var(--md-sys-color-on-surface-variant)]/40">
                <span class="material-symbols-rounded text-6xl">search_off</span>
                <p class="text-sm font-medium">هیچ فرصت شغلی در این بخش یافت نشد</p>
            </div>
        @endforelse
    </div>
</div>

<style>
.perspective-1000 { perspective: 1000px; }
.preserve-3d { transform-style: preserve-3d; }
.backface-hidden { backface-visibility: hidden; }
.rotate-y-180 { transform: rotateY(180deg); }
</style>
