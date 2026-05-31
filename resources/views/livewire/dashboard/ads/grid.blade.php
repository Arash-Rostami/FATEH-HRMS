<div class="@container w-full" dir="rtl">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 pb-12">
        @forelse($this->ads as $ad)
            <div wire:key="ad-{{ $ad->id }}" data-rf="ad-{{ $ad->id }}" x-data @record-focus.window="if ($event.detail.type === 'ad' && $event.detail.id === {{ $ad->id }}) $refs.flip{{ $ad->id }}.checked = true" class="ad-flip-card group relative h-[380px] w-full max-w-[400px] mx-auto">

                <input x-ref="flip{{ $ad->id }}" type="checkbox" id="flip-{{ $ad->id }}" class="ad-flip-toggle hidden">

                <div class="ad-flip-card-inner w-full h-full">

                    <label for="flip-{{ $ad->id }}" class="ad-flip-card-front absolute inset-0 flex flex-col items-center bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/40 shadow-sm rounded-3xl p-6 overflow-hidden hover:shadow-lg transition-shadow duration-300 cursor-pointer">
                        <div class="avatar-wrapper avatar-{{ $ad->avatar['color'] }}">
                            <span class="material-symbols-rounded avatar-icon">
                                {{ $ad->avatar['icon'] }}
                            </span>
                            <div class="avatar-badge" title="جنسیت مورد نیاز">
                                {{ $ad->avatar['title'] }}
                            </div>
                        </div>

                        <h2 class="text-lg font-bold text-[var(--md-sys-color-on-surface)] text-center mb-5 line-clamp-2 leading-tight">
                            {!! strip_tags($ad->position) !!}
                        </h2>

                        <div class="w-full bg-[var(--md-sys-color-surface-container-lowest)] rounded-2xl p-4 flex-1 flex flex-col justify-start border border-[var(--md-sys-color-surface-container-high)]">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="material-symbols-rounded text-base text-[var(--md-sys-color-primary)]">school</span>
                                <h4 class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">مدرک تحصیلی:</h4>
                            </div>
                            <p class="text-xs leading-relaxed text-[var(--md-sys-color-on-surface-variant)] text-justify line-clamp-3">
                                {!! strip_tags($ad->certificate) !!}
                            </p>
                        </div>

                        <div class="mt-5 flex items-center gap-2 text-xs font-medium text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-primary)]/10 px-4 py-2 rounded-lg opacity-80 group-hover:opacity-100 transition-opacity">
                            <span>مشاهده جزئیات</span>
                            <span class="material-symbols-rounded text-[18px]">flip_camera_android</span>
                        </div>
                    </label>

                    <div class="ad-flip-card-back absolute inset-0 flex flex-col bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/60 shadow-xl rounded-3xl p-6 overflow-hidden">
                        <div class="flex items-center justify-between mb-4 pb-2 border-b border-[var(--md-sys-color-outline-variant)]/30">
                            <h3 class="font-bold text-[var(--md-sys-color-on-surface)]">جزئیات موقعیت</h3>
                            <label for="flip-{{ $ad->id }}" class="flex items-center justify-center w-8 h-8 bg-[var(--md-sys-color-surface)] rounded-lg border border-[var(--md-sys-color-outline-variant)] shadow-sm text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-error)] hover:border-[var(--md-sys-color-error)]/50 transition-all cursor-pointer">
                                <span class="material-symbols-rounded text-[20px]">close</span>
                            </label>
                        </div>

                        <div class="flex-1 overflow-y-auto custom-scrollbar pr-2 pb-2 space-y-5">
                            <div class="bg-[var(--md-sys-color-surface-container-low)] rounded-lg p-3 border border-[var(--md-sys-color-outline-variant)]/30">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-tertiary)]">work_history</span>
                                    <h4 class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">سابقه کار:</h4>
                                </div>
                                <p class="text-[12px] leading-relaxed text-[var(--md-sys-color-on-surface-variant)] text-justify">
                                    {!! strip_tags($ad->experience) !!}
                                </p>
                            </div>

                            <div class="bg-[var(--md-sys-color-surface-container-low)] rounded-lg p-3 border border-[var(--md-sys-color-outline-variant)]/30">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-secondary)]">psychology</span>
                                    <h4 class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">مهارت‌ها:</h4>
                                </div>
                                <p class="text-[12px] leading-relaxed text-[var(--md-sys-color-on-surface-variant)] text-justify">
                                    {!! strip_tags($ad->skill) !!}
                                </p>
                            </div>
                        </div>

                        <div class="pt-4 mt-auto flex items-center gap-2 relative z-10">
                            <input type="text"
                                   class="flex-1 bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline)] rounded-lg px-4 py-2.5 text-xs text-[var(--md-sys-color-on-surface)] truncate focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)]/50 transition-all cursor-text"
                                   readonly value="{!! strip_tags($ad->link) !!}" onclick="event.stopPropagation(); this.select()">

                            <button onclick="event.stopPropagation(); navigator.clipboard.writeText('{!! strip_tags($ad->link) !!}'); window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'لینک کپی شد', type: 'success' } }))"
                                    class="shrink-0 flex items-center justify-center w-11 h-11 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] rounded-lg hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] active:scale-95 transition-all shadow-md"
                                    title="کپی لینک">
                                <span class="material-symbols-rounded text-[20px]">content_copy</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full flex flex-col items-center justify-center py-24 gap-5 bg-[var(--md-sys-color-surface-container-lowest)] rounded-3xl border border-dashed border-[var(--md-sys-color-outline-variant)]">
                <div class="p-4 bg-[var(--md-sys-color-surface-container-low)] rounded-full text-[var(--md-sys-color-on-surface-variant)]/50">
                    <span class="material-symbols-rounded text-6xl">search_off</span>
                </div>
                <p class="text-base font-bold text-[var(--md-sys-color-on-surface-variant)]">هیچ فرصت شغلی در این بخش یافت نشد</p>
            </div>
        @endforelse
    </div>
</div>
