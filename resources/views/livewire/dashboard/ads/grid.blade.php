<div class="@container w-full page-wrapper" dir="rtl">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4 pb-12">
        @forelse($this->ads as $ad)
            <div wire:key="ad-{{ $ad->id }}"
                 data-rf="ad-{{ $ad->id }}"
                 x-data
                 @record-focus.window="if ($event.detail.type === 'ad' && $event.detail.id === {{ $ad->id }}) $refs.flip{{ $ad->id }}.checked = true"
                 class="ad-flip-card group relative h-[380px] w-full max-w-[400px] mx-auto"
                 style="animation: slide-up-fade 0.45s both; animation-delay: {{ $loop->index * 0.05 }}s;">

                <input x-ref="flip{{ $ad->id }}" type="checkbox" id="flip-{{ $ad->id }}" class="ad-flip-toggle hidden">

                <div class="ad-flip-card-inner w-full h-full">

                    <label for="flip-{{ $ad->id }}"
                           class="ad-flip-card-front absolute inset-0 flex flex-col bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] shadow-sm rounded-2xl overflow-hidden hover:shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_18%,transparent)] transition-all duration-300 cursor-pointer z-10">

                        <div
                            class="absolute top-0 inset-x-0 h-[3px] bg-[var(--md-sys-color-primary)] shadow-[0_2px_8px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)] z-20"></div>

                        <div
                            class="px-6 py-4 border-b border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] flex items-center gap-3">
                            <div
                                class="shrink-0 w-10 h-10 rounded-xl bg-[var(--md-sys-color-primary-container)] flex items-center justify-center">
                                <span class="material-symbols-rounded font-fill text-[20px] text-[var(--md-sys-color-on-primary-container)]">
                                    {{ $ad->avatar['icon'] ?? 'engineering' }}
                                </span>
                            </div>
                            <div class="flex flex-col">
                                <h2 class="text-sm font-bold text-[var(--md-sys-color-on-surface)] leading-tight line-clamp-1">
                                    {!! strip_tags($ad->position) !!}
                                </h2>
                                <span class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] mt-0.5">
                                    {{ $ad->avatar['title'] ?? 'موقعیت شغلی' }}
                                </span>
                            </div>
                        </div>

                        <div class="p-5 md:p-6 flex-1 flex flex-col justify-between">
                            <div
                                class="bg-[var(--md-sys-color-surface-variant)] rounded-2xl p-5 border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)] flex-1 flex flex-col justify-start">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="material-symbols-rounded text-base text-[var(--md-sys-color-primary)]">school</span>
                                    <h4 class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">مدرک
                                        تحصیلی:</h4>
                                </div>
                                <p class="text-sm leading-[2] text-[var(--md-sys-color-on-surface-variant)] text-justify line-clamp-3">
                                    {!! strip_tags($ad->certificate) !!}
                                </p>
                            </div>

                            <div
                                class="mt-5 flex items-center justify-between h-11 px-4 text-sm font-bold text-[var(--md-sys-color-primary)] bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] rounded-xl active:scale-[0.97] transition-transform">
                                <span>مشاهده جزئیات</span>
                                <span class="material-symbols-rounded text-[20px]">flip_camera_android</span>
                            </div>
                        </div>
                    </label>

                    <div
                        class="ad-flip-card-back absolute inset-0 flex flex-col bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] shadow-sm hover:shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_18%,transparent)] rounded-2xl overflow-hidden transition-all duration-300">

                        <div
                            class="px-6 py-4 border-b border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-8 h-8 rounded-xl bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] flex items-center justify-center">
                                    <span class="material-symbols-rounded text-base font-fill">info</span>
                                </div>
                                <h3 class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">جزئیات موقعیت</h3>
                            </div>

                            <label for="flip-{{ $ad->id }}"
                                   class="shrink-0 w-11 h-11 flex items-center justify-center bg-[var(--md-sys-color-surface)] rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-error-container)_10%,transparent)] hover:border-[color-mix(in_srgb,var(--md-sys-color-error-container)_40%,transparent)] hover:text-[var(--md-sys-color-error-container)] active:scale-[0.97] transition-all cursor-pointer">
                                <span class="material-symbols-rounded text-[20px]">close</span>
                            </label>
                        </div>

                        <div class="p-5 md:p-6 flex-1 flex flex-col overflow-y-auto custom-scrollbar space-y-5">
                            <div class="bg-[var(--md-sys-color-secondary-container)] rounded-2xl p-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="material-symbols-rounded text-base text-[var(--md-sys-color-primary)]">work_history</span>
                                    <h4 class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">سابقه کار:</h4>
                                </div>
                                <p class="text-sm leading-[2] text-[var(--md-sys-color-on-surface-variant)] text-justify">
                                    {!! strip_tags($ad->experience) !!}
                                </p>
                            </div>

                            <div class="bg-[var(--md-sys-color-secondary-container)] rounded-2xl p-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="material-symbols-rounded text-base text-[var(--md-sys-color-primary)]">psychology</span>
                                    <h4 class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">مهارت‌ها:</h4>
                                </div>
                                <p class="text-sm leading-[2] text-[var(--md-sys-color-on-surface-variant)] text-justify">
                                    {!! strip_tags($ad->skill) !!}
                                </p>
                            </div>

                            @if($ad->extra)
                                @php
                                    $extrasArray = is_string($ad->extra) ? json_decode($ad->extra, true) : json_decode(json_encode($ad->extra), true);
                                @endphp
                                @if(is_array($extrasArray) && count($extrasArray) > 0)
                                    @foreach($extrasArray as $item)
                                        @if(isset($item['key']) && isset($item['value']))
                                            <div class="bg-[var(--md-sys-color-secondary-container)] rounded-2xl p-4">
                                                <div class="flex items-center gap-2 mb-2">
                                                    <span class="material-symbols-rounded text-base text-[var(--md-sys-color-primary)]">info</span>
                                                    <h4 class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">{{ $item['key'] }}:</h4>
                                                </div>
                                                <p class="text-sm leading-[2] text-[var(--md-sys-color-on-surface-variant)] text-justify">
                                                    {!! strip_tags($item['value']) !!}
                                                </p>
                                            </div>
                                        @endif
                                    @endforeach
                                @endif
                            @endif
                        </div>

                        <div class="p-5 md:p-6 pt-0 mt-auto flex items-center gap-3">
                            <input type="text"
                                   class="h-11 flex-1 bg-[var(--md-sys-color-surface-variant)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] rounded-xl px-4 font-mono text-sm dir-ltr text-[var(--md-sys-color-on-surface)] truncate focus:outline-none focus:ring-2 focus:ring-[color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)] transition-all cursor-text"
                                   readonly value="{!! strip_tags($ad->link) !!}"
                                   onclick="event.stopPropagation(); this.select()">

                            <button
                                onclick="event.stopPropagation(); navigator.clipboard.writeText('{!! strip_tags($ad->link) !!}'); window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'لینک کپی شد', type: 'success' } }))"
                                class="shrink-0 w-11 h-11 flex items-center justify-center bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-surface)] rounded-xl hover:brightness-95 active:scale-[0.97] focus:outline-none focus:ring-2 focus:ring-[color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)] transition-all"
                                title="کپی لینک">
                                <span class="material-symbols-rounded text-[20px]">content_copy</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div
                class="col-span-full flex flex-col items-center justify-center py-24 gap-5 bg-[var(--md-sys-color-surface)] rounded-2xl border border-dashed border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)]">
                <div
                    class="w-16 h-16 bg-[var(--md-sys-color-secondary-container)] rounded-2xl flex items-center justify-center text-[var(--md-sys-color-primary)]">
                    <span class="material-symbols-rounded text-3xl">search_off</span>
                </div>
                <p class="text-base font-bold text-[var(--md-sys-color-on-surface-variant)]">هیچ فرصت شغلی در این بخش
                    یافت نشد</p>
            </div>
        @endforelse
    </div>
</div>
